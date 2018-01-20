<?php

/**
 * Widget Context plugin core.
 */
class widget_context {

	private $asset_version = '1.0.4';
	private $sidebars_widgets;
	private $options_name = 'widget_logic_options'; // Context settings for widgets (visibility, etc)
	private $settings_name = 'widget_context_settings'; // Widget Context global settings
	private $sidebars_widgets_copy;

	private $core_modules = array(
			'word-count',
			'custom-post-types-taxonomies'
		);

	private $context_options = array(); // Store visibility settings
	private $context_settings = array(); // Store admin settings
	private $contexts = array();
	private $plugin_path;


	static function instance() {

		static $instance;

		if ( ! $instance )
			$instance = new self();

		return $instance;

	}

	/**
	 * Use widget_context::instance() instead.
	 */
	private function __construct() {
		$this->plugin_path = dirname( dirname( __FILE__ ) );
	}

	/**
	 * Hook into WP.
	 */
	public function init() {
		// Define available widget contexts
		add_action( 'init', array( $this, 'define_widget_contexts' ), 5 );

		// Load plugin settings and show/hide widgets by altering the
		// $sidebars_widgets global variable
		add_action( 'wp', array( $this, 'set_widget_contexts_frontend' ) );

		// Enable localization
		add_action( 'plugins_loaded', array( $this, 'init_l10n' ) );

		// Append Widget Context settings to widget controls
		add_action( 'in_widget_form', array( $this, 'widget_context_controls' ), 10, 3 );

		// Add admin menu for config
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		// Save widget context settings, when in admin area
		add_action( 'sidebar_admin_setup', array( $this, 'save_widget_context_settings' ) );

		// Fix legacy context option naming
		add_filter( 'widget_context_options', array( $this, 'fix_legacy_options' ) );

		// Register admin settings menu
		add_action( 'admin_menu', array( $this, 'widget_context_settings_menu' ) );

		// Register admin settings
		add_action( 'admin_init', array( $this, 'widget_context_settings_init' ) );

		// Register our own debug bar panel
		add_filter( 'debug_bar_panels', array( $this, 'widget_context_debug_bar_init' ) );
		add_action( 'debug_bar_enqueue_scripts', array( $this, 'widget_context_debug_bar_scripts' ) );
	}


	function define_widget_contexts() {

		$this->context_options = apply_filters(
				'widget_context_options',
				(array) get_option( $this->options_name, array() )
			);

		$this->context_settings = wp_parse_args(
				(array) get_option( $this->settings_name, array() ),
				array(
					'contexts' => array()
				)
			);

		foreach ( $this->core_modules as $module ) {
			$module_file = sprintf( '%s/class/modules/%s/module.php', $this->plugin_path, $module );

			if ( file_exists( $module_file ) ) {
				include $module_file;
			}
		}

		// Default context
		$default_contexts = array(
			'incexc' => array(
				'label' => __( 'Widget Context', 'widget-context' ),
				'description' => __( 'Set the default logic to show or hide.', 'widget-context' ),
				'weight' => -100,
				'type' => 'core',
			),
			'location' => array(
				'label' => __( 'Global Sections', 'widget-context' ),
				'description' => __( 'Based on standard WordPress template tags.', 'widget-context' ),
				'weight' => 10
			),
			'url' => array(
				'label' => __( 'Target by URL', 'widget-context' ),
				'description' => __( 'Based on URL patterns.', 'widget-context' ),
				'weight' => 20
			),
			'admin_notes' => array(
				'label' => __( 'Notes (invisible to public)', 'widget-context' ),
				'description' => __( 'Enables private notes on widget context settings.', 'widget-context'),
				'weight' => 90
			)
		);

		// Add default context controls and checks
		foreach ( $default_contexts as $context_name => $context_desc ) {

			add_filter( 'widget_context_control-' . $context_name, array( $this, 'control_' . $context_name ), 10, 2 );
			add_filter( 'widget_context_check-' . $context_name, array( $this, 'context_check_' . $context_name ), 10, 2 );

		}

		// Enable other plugins and themes to specify their own contexts
		$this->contexts = apply_filters( 'widget_contexts', $default_contexts );

		// Sort contexts by their weight
		uasort( $this->contexts, array( $this, 'sort_context_by_weight' ) );

	}


	public function get_context_options( $widget_id = null ) {

		if ( ! $widget_id )
			return $this->context_options;

		if ( isset( $this->context_options[ $widget_id ] ) )
			return $this->context_options[ $widget_id ];
		else
			return null;

	}


	public function get_context_settings( $widget_id = null ) {

		if ( ! $widget_id )
			return $this->context_settings;

		if ( isset( $this->context_settings[ $widget_id ] ) )
			return $this->context_settings[ $widget_id ];
		else
			return null;

	}


	public function get_contexts() {

		return $this->contexts;

	}


	function sort_context_by_weight( $a, $b ) {

		if ( ! isset( $a['weight'] ) )
			$a['weight'] = 10;

		if ( ! isset( $b['weight'] ) )
			$b['weight'] = 10;

		return ( $a['weight'] < $b['weight'] ) ? -1 : 1;

	}


	function set_widget_contexts_frontend() {

		// Hide/show widgets for is_active_sidebar() to work
		add_filter( 'sidebars_widgets', array( $this, 'maybe_unset_widgets_by_context' ), 10 );

	}


	function init_l10n() {

		load_plugin_textdomain( 'widget-context', false, basename( $this->plugin_path ) . '/languages' );

	}


	function admin_scripts( $page ) {

		// Enqueue only on widgets and customizer view
		if ( ! in_array( $page, array( 'widgets.php', 'settings_page_widget_context_settings' ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'widget-context-css',
			$this->asset_url( '/css/admin.css' ),
			null,
			$this->asset_version
		);

		wp_enqueue_script(
			'widget-context-js',
			$this->asset_url( '/js/widget-context.js' ),
			array( 'jquery' ),
			$this->asset_version
		);

	}


	function widget_context_controls( $object, $return, $instance ) {

		echo $this->display_widget_context( $object->id );

	}


	function save_widget_context_settings() {

		if ( ! current_user_can( 'edit_theme_options' ) || empty( $_POST ) || ! isset( $_POST['wl'] ) )
			return;

		// Delete a widget
		if ( isset( $_POST['delete_widget'] ) && isset( $_POST['the-widget-id'] ) )
			unset( $this->context_options[ $_POST['the-widget-id'] ] );

		// Add / Update
		$this->context_options = array_merge( $this->context_options, $_POST['wl'] );

		$sidebars_widgets = wp_get_sidebars_widgets();
		$all_widget_ids = array();

		// Get a lits of all widget IDs
		foreach ( $sidebars_widgets as $widget_area => $widgets )
			foreach ( $widgets as $widget_order => $widget_id )
				$all_widget_ids[] = $widget_id;

		// Remove non-existant widget contexts from the settings
		foreach ( $this->context_options as $widget_id => $widget_context )
			if ( ! in_array( $widget_id, $all_widget_ids ) )
				unset( $this->context_options[ $widget_id ] );

		update_option( $this->options_name, $this->context_options );

	}


	function maybe_unset_widgets_by_context( $sidebars_widgets ) {

		// Don't run this at the backend or before
		// post query has been run
		if ( is_admin() )
			return $sidebars_widgets;

		// Return from cache if we have done the context checks already
		if ( ! empty( $this->sidebars_widgets ) )
			return $this->sidebars_widgets;

		// Store a local copy of the original widget location
		$this->sidebars_widgets_copy = $sidebars_widgets;

		foreach ( $sidebars_widgets as $widget_area => $widget_list ) {

			if ( $widget_area == 'wp_inactive_widgets' || empty( $widget_list ) )
				continue;

			foreach ( $widget_list as $pos => $widget_id ) {

				if ( ! $this->check_widget_visibility( $widget_id ) )
					unset( $sidebars_widgets[ $widget_area ][ $pos ] );

			}

		}

		// Store in class cache
		$this->sidebars_widgets = $sidebars_widgets;

		return $sidebars_widgets;

	}


	function check_widget_visibility( $widget_id ) {

		// Check if this widget even has context set
		if ( ! isset( $this->context_options[ $widget_id ] ) )
			return true;

		$matches = array();

		foreach ( $this->get_contexts() as $context_id => $context_settings ) {

			// This context check has been disabled in the plugin settings
			if ( isset( $this->context_settings['contexts'][ $context_id ] ) && ! $this->context_settings['contexts'][ $context_id ] )
				continue;

			// Make sure that context settings for this widget are defined
			if ( ! isset( $this->context_options[ $widget_id ][ $context_id ] ) )
				$widget_context_args = array();
			else
				$widget_context_args = $this->context_options[ $widget_id ][ $context_id ];

			$matches[ $context_id ] = apply_filters(
					'widget_context_check-' . $context_id,
					null,
					$widget_context_args
				);

		}

		// Get the match rule for this widget (show/hide/selected/notselected)
		$match_rule = $this->context_options[ $widget_id ][ 'incexc' ][ 'condition' ];

		// Force show or hide the widget!
		if ( $match_rule == 'show' )
			return true;
		elseif ( $match_rule == 'hide' )
			return false;

		if ( $match_rule == 'selected' )
			$inc = true;
		else
			$inc = false;

		if ( $inc && in_array( true, $matches ) )
			return true;
		elseif ( ! $inc && ! in_array( true, $matches ) )
			return true;
		else
			return false;

	}


	/**
	 * Default context checks
	 */

	function context_check_incexc( $check, $settings ) {

		return $check;

	}


	function context_check_location( $check, $settings ) {

		$status = array(
				'is_front_page' => is_front_page(),
				'is_home' => is_home(),
				'is_singular' => is_singular(),
				'is_single' => is_singular( 'post' ),
				'is_page' => ( is_page() && ! is_front_page() ),
				'is_attachment' => is_attachment(),
				'is_search' => is_search(),
				'is_404' => is_404(),
				'is_archive' => is_archive(),
				'is_date' => is_date(),
				'is_day' => is_day(),
				'is_month' => is_month(),
				'is_year' => is_year(),
				'is_category' => is_category(),
				'is_tag' => is_tag(),
				'is_author' => is_author()
			);

		$matched = array_intersect_assoc( $settings, $status );

		if ( ! empty( $matched ) )
			return true;

		return $check;

	}


	function context_check_url( $check, $settings ) {

		$settings = wp_parse_args(
				$settings,
				array(
					'urls' => null
				)
			);

		$urls = trim( $settings['urls'] );

		if ( empty( $urls ) )
			return $check;

		if ( $this->match_path( $urls ) )
			return true;

		return $check;

	}


	function match_path( $patterns ) {

		global $wp;

		$patterns_safe = array();

		// Get the request URI from WP
		$url_request = $wp->request;

		// Append the query string
		if ( ! empty( $_SERVER['QUERY_STRING'] ) )
			$url_request .= '?' . $_SERVER['QUERY_STRING'];

		$rows = explode( "\n", $patterns );

		foreach ( $rows as $pattern ) {

			// Trim trailing, leading slashes and whitespace
			$pattern = trim( trim( $pattern ), '/' );

			// Escape regex chars
			$pattern = preg_quote( $pattern, '/' );

			// Enable wildcard checks
			$pattern = str_replace( '\*', '.*', $pattern );

			$patterns_safe[] = $pattern;

		}

		// Remove empty patterns
		$patterns_safe = array_filter( $patterns_safe );

		$regexps = sprintf(
				'/^(%s)$/i',
				implode( '|', $patterns_safe )
			);

		return preg_match( $regexps, $url_request );

	}


	// Dummy function
	function context_check_admin_notes( $check, $widget_id ) {}


	// Dummy function
	function context_check_general( $check, $widget_id ) {}


	/*
		Widget Controls
	 */

	function display_widget_context( $widget_id = null ) {

		$controls = array();
		$controls_disabled = array();
		$controls_core = array();

		foreach ( $this->contexts as $context_name => $context_settings ) {

			$context_classes = array(
					'context-group',
					sprintf( 'context-group-%s', esc_attr( $context_name ) )
				);

			// Hide this context from the admin UX. We can't remove them
			// because settings will get lost if this page is submitted.
			if ( isset( $this->context_settings['contexts'][ $context_name ] ) && ! $this->context_settings['contexts'][ $context_name ] ) {
				$context_classes[] = 'context-inactive';
				$controls_disabled[] = $context_name;
			}

			// Store core controls
			if ( isset( $context_settings['type'] ) && 'core' == $context_settings['type'] ) {
				$controls_core[] = $context_name;
			}

			$control_args = array(
					'name' => $context_name,
					'input_prefix' => 'wl' . $this->get_field_name( array( $widget_id, $context_name ) ),
					'settings' => $this->get_field_value( array( $widget_id, $context_name ) ),
					'widget_id' => $widget_id
				);

			$context_controls = apply_filters( 'widget_context_control-' . $context_name, $control_args );
			$context_classes = apply_filters( 'widget_context_classes-' . $context_name, $context_classes, $control_args );

			if ( ! empty( $context_controls ) && is_string( $context_controls ) ) {

				$controls[ $context_name ] = sprintf(
						'<div class="%s">
							<h4 class="context-toggle">%s</h4>
							<div class="context-group-wrap">
								%s
							</div>
						</div>',
						esc_attr( implode( ' ', $context_classes ) ),
						esc_html( $context_settings['label'] ),
						$context_controls
					);

			}

		}



		// Non-core controls that should be visible if enabled
		$controls_not_core = array_diff( array_keys( $controls ), $controls_core );

		// Check if any non-core context controls have been enabled
		$has_controls = array_diff( $controls_not_core, $controls_disabled );

		if ( empty( $controls ) || empty( $has_controls ) ) {

			if ( current_user_can( 'edit_theme_options' ) ) {

				$controls = array( sprintf(
						'<p class="error">%s</p>',
						sprintf(
							__( 'No widget controls enabled. You can enable them in <a href="%s">Widget Context settings</a>.', 'widget-context' ),
							admin_url( 'options-general.php?page=widget_context_settings' )
						)
					) );

			} else {

				$controls = array( sprintf(
						'<p class="error">%s</p>',
						__( 'No widget controls enabled.', 'widget-context' )
					) );

			}

		}

		$settings_link = '';

		if ( current_user_can( 'edit_theme_options' ) ) {

			$settings_link = sprintf(
					'<a href="%s" class="widget-context-settings-link" title="%s">%s</a>',
					admin_url( 'options-general.php?page=widget_context_settings' ),
					esc_attr__( 'Widget Context Settings', 'widget-context' ),
					esc_html__( 'Settings', 'widget-context' )
				);

		}

		return sprintf(
				'<div class="widget-context">
					<div class="widget-context-header">
						<h3>%s</h3>
						%s
					</div>
					<div class="widget-context-inside" id="widget-context-%s" data-widget-id="%s">
						%s
					</div>
				</div>',
				__( 'Widget Context', 'widget-context' ),
				$settings_link,
				// Inslide classes
				esc_attr( $widget_id ),
				esc_attr( $widget_id ),
				// Controls
				implode( '', $controls )
			);

	}


	function control_incexc( $control_args ) {

		$options = array(
				'show' => __( 'Show widget everywhere', 'widget-context' ),
				'selected' => __( 'Show widget on selected', 'widget-context' ),
				'notselected' => __( 'Hide widget on selected', 'widget-context' ),
				'hide' => __( 'Hide widget everywhere', 'widget-context' )
			);

		return $this->make_simple_dropdown( $control_args, 'condition', $options );

	}


	function control_location( $control_args ) {

		$options = array(
				'is_front_page' => __( 'Front page', 'widget-context' ),
				'is_home' => __( 'Blog page', 'widget-context' ),
				'is_singular' => __( 'All posts, pages and custom post types', 'widget-context' ),
				'is_single' => __( 'All posts', 'widget-context' ),
				'is_page' => __( 'All pages', 'widget-context' ),
				'is_attachment' => __( 'All attachments', 'widget-context' ),
				'is_search' => __( 'Search results', 'widget-context' ),
				'is_404' => __( '404 error page', 'widget-context' ),
				'is_archive' => __( 'All archives', 'widget-context' ),
				'is_date' => __( 'All date archives', 'widget-context' ),
				'is_day' => __( 'Daily archives', 'widget-context' ),
				'is_month' => __( 'Monthly archives', 'widget-context' ),
				'is_year' => __( 'Yearly archives', 'widget-context' ),
				'is_category' => __( 'All category archives', 'widget-context' ),
				'is_tag' => __( 'All tag archives', 'widget-context' ),
				'is_author' => __( 'All author archives', 'widget-context' )
			);

		foreach ( $options as $option => $label )
			$out[] = $this->make_simple_checkbox( $control_args, $option, $label );

		return implode( '', $out );

	}


	function control_url( $control_args ) {

		return sprintf(
				'<div>%s</div>
				<p class="help">%s</p>',
				$this->make_simple_textarea( $control_args, 'urls' ),
				__( 'Enter one location fragment per line. Use <strong>*</strong> character as a wildcard. Example: <code>category/peace/*</code> to target all posts in category <em>peace</em>.', 'widget-context' )
			);

	}


	function control_admin_notes( $control_args ) {

		return sprintf(
				'<div>%s</div>',
				$this->make_simple_textarea( $control_args, 'notes' )
			);

	}



	/**
	 * Widget control helpers
	 */


	function make_simple_checkbox( $control_args, $option, $label ) {

		if ( isset( $control_args['settings'][ $option ] ) && $control_args['settings'][ $option ] )
			$value = true;
		else
			$value = false;

		return sprintf(
				'<label class="wc-field-checkbox-%s" data-widget-id="%s">
					<input type="hidden" value="0" name="%s[%s]" />
					<input type="checkbox" value="1" name="%s[%s]" %s />&nbsp;%s
				</label>',
				$this->get_field_classname( $option ),
				esc_attr( $control_args['widget_id'] ),
				// Input hidden
				$control_args['input_prefix'],
				esc_attr( $option ),
				// Input value
				$control_args['input_prefix'],
				esc_attr( $option ),
				checked( $value, true, false ),
				// Label
				esc_html( $label )
			);

	}


	function make_simple_textarea( $control_args, $option, $label = null ) {

		if ( isset( $control_args['settings'][ $option ] ) )
			$value = esc_textarea( $control_args['settings'][ $option ] );
		else
			$value = '';

		return sprintf(
				'<label class="wc-field-textarea-%s" data-widget-id="%s">
					<strong>%s</strong>
					<textarea name="%s[%s]">%s</textarea>
				</label>',
				$this->get_field_classname( $option ),
				esc_attr( $control_args['widget_id'] ),
				// Label
				esc_html( $label ),
				// Input
				$control_args['input_prefix'],
				$option,
				$value
			);

	}


	function make_simple_textfield( $control_args, $option, $label_before = null, $label_after = null) {

		if ( isset( $control_args['settings'][ $option ] ) )
			$value = esc_attr( $control_args['settings'][ $option ] );
		else
			$value = false;

		return sprintf(
				'<label class="wc-field-text-%s" data-widget-id="%s">
					%s
					<input type="text" name="%s[%s]" value="%s" />
					%s
				</label>',
				$this->get_field_classname( $option ),
				esc_attr( $control_args['widget_id'] ),
				// Before
				$label_before,
				// Input
				$control_args['input_prefix'],
				$option,
				esc_attr( $value ),
				// After
				esc_html( $label_after )
			);

	}


	function make_simple_dropdown( $control_args, $option, $selection = array(), $label_before = null, $label_after = null ) {

		$options = array();

		if ( isset( $control_args['settings'][ $option ] ) )
			$value = $control_args['settings'][ $option ];
		else
			$value = false;

		if ( empty( $selection ) )
			$options[] = sprintf(
					'<option value="">%s</option>',
					esc_html__( 'No options available', 'widget-context' )
				);

		foreach ( $selection as $sid => $svalue )
			$options[] = sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $sid ),
					selected( $value, $sid, false ),
					esc_html( $svalue )
				);

		return sprintf(
				'<label class="wc-field-select-%s" data-widget-id="%s">
					%s
					<select name="%s[%s]">
						%s
					</select>
					%s
				</label>',
				$this->get_field_classname( $option ),
				esc_attr( $control_args['widget_id'] ),
				// Before
				$label_before,
				// Input
				$control_args['input_prefix'],
				$option,
				implode( '', $options ),
				// After
				$label_after
			);

	}


	/**
	 * Returns [part1][part2][partN] from array( 'part1', 'part2', 'part3' )
	 * @param  array  $parts i.e. array( 'part1', 'part2', 'part3' )
	 * @return string        i.e. [part1][part2][partN]
	 */
	function get_field_name( $parts ) {

		return esc_attr( sprintf( '[%s]', implode( '][', $parts ) ) );

	}

	function get_field_classname( $name ) {

		if ( is_array( $name ) )
			$name = end( $name );

		return sanitize_html_class( str_replace( '_', '-', $name ) );

	}


	/**
	 * Given option keys return its value
	 * @param  array  $parts   i.e. array( 'part1', 'part2', 'part3' )
	 * @param  array  $options i.e. array( 'part1' => array( 'part2' => array( 'part3' => 'VALUE' ) ) )
	 * @return string          Returns option value
	 */
	function get_field_value( $parts, $options = null ) {

		if ( $options == null )
			$options = $this->context_options;

		$value = false;

		if ( empty( $parts ) || ! is_array( $parts ) )
			return false;

		$part = array_shift( $parts );

		if ( ! empty( $parts ) && isset( $options[ $part ] ) && is_array( $options[ $part ] ) )
			$value = $this->get_field_value( $parts, $options[ $part ] );
		elseif ( isset( $options[ $part ] ) )
			return $options[ $part ];

		return $value;

	}


	function fix_legacy_options( $options ) {

		if ( empty( $options ) || ! is_array( $options ) )
			return $options;

		foreach ( $options as $widget_id => $option ) {

			// This doesn't have an include/exclude rule defined
			if ( ! isset( $option['incexc'] ) )
				unset( $options[ $widget_id ] );

			// We moved from [incexc] = 1/0 to [incexc][condition] = 1/0
			if ( isset( $option['incexc'] ) && ! is_array( $option['incexc'] ) )
				$options[ $widget_id ]['incexc'] = array( 'condition' => $option['incexc'] );

			// Move notes from "general" group to "admin_notes"
			if ( isset( $option['general']['notes'] ) ) {
				$options[ $widget_id ]['admin_notes']['notes'] = $option['general']['notes'];
				unset( $option['general']['notes'] );
			}

			// We moved word count out of location context group
			if ( isset( $option['location']['check_wordcount'] ) )
				$options[ $widget_id ]['word_count'] = array(
						'check_wordcount' => true,
						'check_wordcount_type' => $option['location']['check_wordcount_type'],
						'word_count' => $option['location']['word_count']
					);

		}

		return $options;

	}



	/**
	 * Admin Settings
	 */


	function widget_context_settings_menu() {

		add_options_page(
			__( 'Widget Context Settings', 'widget-context' ),
			__( 'Widget Context', 'widget-context' ),
			'manage_options',
			$this->settings_name,
			array( $this, 'widget_context_admin_view' )
		);

	}


	function widget_context_settings_init() {

		register_setting( $this->settings_name, $this->settings_name );

	}


	function widget_context_admin_view() {

		$context_controls = array();

		foreach ( $this->get_contexts() as $context_id => $context_args ) {

			// Hide core modules from being disabled
			if ( isset( $context_args['type'] ) && $context_args['type'] == 'core' )
				continue;

			if ( ! empty( $context_args['description'] ) )
				$context_description = sprintf(
					'<p class="context-desc">%s</p>',
					esc_html( $context_args['description'] )
				);
			else
				$context_description = null;

			// Enable new modules by default
			if ( ! isset( $this->context_settings['contexts'][ $context_id ] ) )
				$this->context_settings['contexts'][ $context_id ] = 1;

			$context_controls[] = sprintf(
					'<li class="context-%s">
						<label>
							<input type="hidden" name="%s[contexts][%s]" value="0" />
							<input type="checkbox" name="%s[contexts][%s]" value="1" %s /> %s
						</label>
						%s
					</li>',
					esc_attr( $context_id ),
					$this->settings_name,
					esc_attr( $context_id ),
					$this->settings_name,
					esc_attr( $context_id ),
					checked( $this->context_settings['contexts'][ $context_id ], 1, false ),
					esc_html( $context_args['label'] ),
					$context_description
				);

		}

		?>
		<div class="wrap wrap-widget-context">
			<h2><?php esc_html_e( 'Widget Context Settings', 'widget-context' ); ?></h2>

			<div class="widget-context-settings-wrap">

				<div class="widget-context-form">
					<form method="post" action="options.php">
						<?php
							settings_fields( $this->settings_name );
							do_settings_sections( $this->settings_name );
						?>

						<?php
							printf(
								'<div class="settings-section settings-section-modules">
									<h3>%s</h3>
									<ul>%s</ul>
								</div>',
								esc_html__( 'Enabled Context Modules', 'widget-context' ),
								implode( '', $context_controls )
							);
						?>

						<?php
							submit_button();
						?>
					</form>
				</div>

				<div class="widget-context-sidebar">
					<div class="wc-sidebar-in">

						<div class="wc-sidebar-section wc-sidebar-credits">
							<p>
								<img src="http://gravatar.com/avatar/661eb21385c25c01ad64ab9e13b37331/?s=60" alt="Kaspars Dambis" width="60" height="60" />
								<?php printf( esc_html__( 'Widget Context is created and maintained by %s.' , 'widget-context'), '<a href="http://kaspars.net">Kaspars Dambis</a>' ); ?>
							</p>
						</div>

						<div class="wc-sidebar-section wc-sidebar-newsletter">
							<h3><?php esc_html_e( 'News & Updates' , 'widget-context'); ?></h3>
							<p><?php esc_html_e( 'Subscribe to receive news & updates about the plugin.' , 'widget-context'); ?></p>
							<form action="//osc.us2.list-manage.com/subscribe/post?u=e8d173fc54c0fc4286a2b52e8&amp;id=8afe96c5a3" method="post" target="_blank">
								<?php $user = wp_get_current_user(); ?>
								<p><label><?php _e( 'Your Name', 'widget-context' ); ?>: <input type="text" name="NAME" value="<?php echo esc_attr( sprintf( '%s %s', $user->first_name, $user->last_name ) ) ?>" /></label></p>
								<p><label><?php _e( 'Your Email', 'widget-context' ); ?>: <input type="text" name="EMAIL" value="<?php echo esc_attr( $user->user_email ); ?>" /></label></p>
								<p><input class="button" name="subscribe" type="submit" value="<?php _e( 'Subscribe', 'widget-context' ); ?>" /></p>
							</form>
						</div>

					</div>
				</div>

			</div>
		</div>
		<?php

	}


	public function get_sidebars_widgets_copy() {

		return $this->sidebars_widgets_copy;

	}


	function widget_context_debug_bar_init( $panels ) {

		include $this->plugin_path . '/debug/debug-bar.php';

		if ( class_exists( 'Debug_Widget_Context' ) )
			$panels[] = new Debug_Widget_Context();

		return $panels;

	}


	function widget_context_debug_bar_scripts() {

		wp_enqueue_script(
			'widget-context-debug-js',
			$this->asset_url( '/debug/debug.js' ),
			array( 'jquery' )
		);

	}

	/**
	 * Return the public URL of a plugin asset file.
	 *
	 * @param string $asset_relative_path Relative path to the asset file.
	 *
	 * @return string
	 */
	function asset_url( $asset_relative_path ) {
		return plugins_url( plugin_basename( $this->plugin_path ) . $asset_relative_path );
	}


}
