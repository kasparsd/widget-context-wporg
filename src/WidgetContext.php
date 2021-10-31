<?php

use Preseto\WidgetContext\UriRuleMatcher;
use Preseto\WidgetContext\UriRules;

/**
 * Widget Context plugin core.
 */
class WidgetContext {

	/**
	 * Rule ID for the invert by URL.
	 *
	 * @var string
	 */
	const RULE_KEY_URLS_INVERT = 'urls_invert';

	private $sidebars_widgets;
	private $options_name = 'widget_logic_options'; // Context settings for widgets (visibility, etc)
	private $settings_name = 'widget_context_settings'; // Widget Context global settings
	private $sidebars_widgets_copy;

	private $context_options = array(); // Store visibility settings
	private $context_settings = array(); // Store admin settings
	private $contexts = array();

	/**
	 * Instance of the abstract plugin.
	 *
	 * @var Preseto\WidgetContext\Plugin
	 */
	private $plugin;

	/**
	 * Instance of the current class for legacy purposes.
	 *
	 * @var WidgetContext
	 */
	protected static $instance;

	/**
	 * Start the plugin.
	 *
	 * @param Preseto\WidgetContext\Plugin $path Instance of the abstract plugin.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;

		// Keep an instance for legacy purposes.
		self::$instance = $this;
	}

	/**
	 * Legacy singleton instance getter.
	 *
	 * @return WidgetContext
	 */
	public static function instance() {
		return self::$instance;
	}

	/**
	 * Interface for registering modules.
	 *
	 * @param  mixed $module Instance of the module.
	 *
	 * @return void
	 */
	public function register_module( $module ) {
		$module->init();
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

		// Register admin settings.
		add_action( 'admin_init', array( $this, 'widget_context_settings_init' ) );

		// Add quick links to the plugin list.
		add_action(
			'plugin_action_links_' . $this->plugin->basename(),
			array( $this, 'plugin_action_links' )
		);
	}


	function define_widget_contexts() {
		$this->context_options = apply_filters(
			'widget_context_options',
			(array) get_option( $this->options_name, array() )
		);

		$this->context_settings = wp_parse_args(
			(array) get_option( $this->settings_name, array() ),
			array(
				'contexts' => array(),
			)
		);

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
				'description' => __( 'Match using the standard WordPress template tags.', 'widget-context' ),
				'weight' => 10,
			),
			'url' => array(
				'label' => __( 'Target by URL', 'widget-context' ),
				'description' => __( 'Match using URL patterns.', 'widget-context' ),
				'weight' => 20,
			),
			self::RULE_KEY_URLS_INVERT => array(
				'label' => __( 'Exclude by URL', 'widget-context' ),
				'description' => __( 'Override other matches using URL patterns.', 'widget-context' ),
				'weight' => 25,
			),
			'admin_notes' => array(
				'label' => __( 'Notes (invisible to public)', 'widget-context' ),
				'description' => __( 'Keep private notes on widget context settings.', 'widget-context' ),
				'weight' => 90,
			),
		);

		// Add default context controls and checks
		foreach ( array_keys( $default_contexts ) as $context_name ) {
			add_filter( 'widget_context_control-' . $context_name, array( $this, 'control_' . $context_name ), 10, 2 );
			add_filter( 'widget_context_check-' . $context_name, array( $this, 'context_check_' . $context_name ), 10, 2 );
		}

		// Enable other plugins and themes to specify their own contexts
		$this->contexts = apply_filters( 'widget_contexts', $default_contexts );

		// Sort contexts by their weight
		uasort( $this->contexts, array( $this, 'sort_context_by_weight' ) );
	}


	public function get_context_options( $widget_id = null ) {
		if ( ! $widget_id ) {
			return $this->context_options;
		}

		if ( isset( $this->context_options[ $widget_id ] ) ) {
			return $this->context_options[ $widget_id ];
		}

		return null;
	}


	public function get_context_settings( $widget_id = null ) {
		if ( ! $widget_id ) {
			return $this->context_settings;
		}

		if ( isset( $this->context_settings[ $widget_id ] ) ) {
			return $this->context_settings[ $widget_id ];
		}

		return null;
	}


	public function get_contexts() {
		return $this->contexts;
	}


	function sort_context_by_weight( $a, $b ) {
		if ( ! isset( $a['weight'] ) ) {
			$a['weight'] = 10;
		}

		if ( ! isset( $b['weight'] ) ) {
			$b['weight'] = 10;
		}

		return ( $a['weight'] < $b['weight'] ) ? -1 : 1;
	}


	/**
	 * Get the state of the PRO nag.
	 *
	 * @return boolean
	 */
	public function pro_nag_enabled() {
		return (bool) apply_filters( 'widget_context_pro_nag', true );
	}

	/**
	 * Add a link to the plugin settings in the plugin list.
	 *
	 * @return array List of links.
	 */
	public function plugin_action_links( $links ) {
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $this->plugin_settings_admin_url() ),
			esc_html__( 'Settings', 'widget-context' )
		);

		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $this->customize_widgets_admin_url() ),
			esc_html__( 'Configure Widgets', 'widget-context' )
		);

		if ( $this->pro_nag_enabled() ) {
			$links[] = sprintf(
				'<a href="%s" target="_blank">PRO 🚀</a>',
				esc_url( 'https://widgetcontext.com/pro' )
			);
		}

		return $links;
	}

	function set_widget_contexts_frontend() {
		// Hide/show widgets for is_active_sidebar() to work
		add_filter( 'sidebars_widgets', array( $this, 'maybe_unset_widgets_by_context' ), 10 );
	}


	function admin_scripts( $page ) {
		// Enqueue only on widgets and customizer view
		if ( ! in_array( $page, array( 'widgets.php', 'appearance_page_widget_context_settings' ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'widget-context-css',
			$this->plugin->asset_url( 'assets/css/admin.css' ),
			null,
			$this->plugin->asset_version()
		);

		wp_enqueue_script(
			'widget-context-js',
			$this->plugin->asset_url( 'assets/js/widget-context.js' ),
			array( 'jquery' ),
			$this->plugin->asset_version()
		);
	}


	function widget_context_controls( $object, $return, $instance ) {
		echo $this->display_widget_context( $object->id );
	}


	function save_widget_context_settings() {
		if ( ! current_user_can( 'edit_theme_options' ) || empty( $_POST ) || ! isset( $_POST['wl'] ) ) {
			return;
		}

		// Delete a widget
		if ( isset( $_POST['delete_widget'] ) && isset( $_POST['the-widget-id'] ) ) {
			unset( $this->context_options[ $_POST['the-widget-id'] ] );
		}

		// Add / Update
		$this->context_options = array_merge( $this->context_options, $_POST['wl'] );

		$sidebars_widgets = wp_get_sidebars_widgets();
		$all_widget_ids = array();

		// Get a lits of all widget IDs
		foreach ( $sidebars_widgets as $widget_area => $widgets ) {
			foreach ( $widgets as $widget_order => $widget_id ) {
				$all_widget_ids[] = $widget_id;
			}
		}

		// Remove non-existant widget contexts from the settings
		foreach ( $this->context_options as $widget_id => $widget_context ) {
			if ( ! in_array( $widget_id, $all_widget_ids, true ) ) {
				unset( $this->context_options[ $widget_id ] );
			}
		}

		update_option( $this->options_name, $this->context_options );
	}


	function maybe_unset_widgets_by_context( $sidebars_widgets ) {
		// Don't run this at the backend or before
		// post query has been run
		if ( is_admin() ) {
			return $sidebars_widgets;
		}

		// Return from cache if we have done the context checks already
		if ( ! empty( $this->sidebars_widgets ) ) {
			return $this->sidebars_widgets;
		}

		// Store a local copy of the original widget location
		$this->sidebars_widgets_copy = $sidebars_widgets;

		foreach ( $sidebars_widgets as $widget_area => $widget_list ) {

			if ( 'wp_inactive_widgets' === $widget_area || empty( $widget_list ) ) {
				continue;
			}

			foreach ( $widget_list as $pos => $widget_id ) {

				if ( ! $this->check_widget_visibility( $widget_id ) ) {
					unset( $sidebars_widgets[ $widget_area ][ $pos ] );
				}
			}
		}

		// Store in class cache
		$this->sidebars_widgets = $sidebars_widgets;

		return $sidebars_widgets;
	}

	/**
	 * Determine widget visibility according to the current global context.
	 *
	 * @param string $widget_id Widget ID.
	 *
	 * @return boolean
	 */
	public function check_widget_visibility( $widget_id ) {
		// Check if this widget even has context set.
		if ( ! isset( $this->context_options[ $widget_id ] ) ) {
			return true;
		}

		// Get the match rule for this widget (show/hide/selected/notselected).
		$match_rule = $this->context_options[ $widget_id ]['incexc']['condition'];

		// Force show or hide the widget!
		if ( 'show' === $match_rule ) {
			return true;
		} elseif ( 'hide' === $match_rule ) {
			return false;
		}

		// Show or hide on match.
		$condition = ( 'selected' === $match_rule );

		if ( $this->context_matches_condition_for_widget_id( $widget_id ) ) {
			return $condition;
		}

		return ! $condition;
	}

	/**
	 * Check if widget visibility rules match the current context.
	 *
	 * @param string $widget_id Widget ID.
	 *
	 * @return boolean
	 */
	public function context_matches_condition_for_widget_id( $widget_id ) {
		$matches = $this->context_matches_for_widget_id( $widget_id );

		// Inverted rules can only override another positive match.
		return ( in_array( true, $matches, true ) && ! in_array( false, $matches, true ) );
	}

	/**
	 * Get context rule matches for a widget ID.
	 *
	 * @param string $widget_id Widget ID.
	 *
	 * @return array
	 */
	public function context_matches_for_widget_id( $widget_id ) {
		$matches = array();

		foreach ( $this->get_contexts() as $context_id => $context_settings ) {
			// This context check has been disabled in the plugin settings
			if ( isset( $this->context_settings['contexts'][ $context_id ] ) && ! $this->context_settings['contexts'][ $context_id ] ) {
				continue;
			}

			$widget_context_args = array();

			// Make sure that context settings for this widget are defined
			if ( ! empty( $this->context_options[ $widget_id ][ $context_id ] ) ) {
				$widget_context_args = $this->context_options[ $widget_id ][ $context_id ];
			}

			$matches[ $context_id ] = apply_filters(
				'widget_context_check-' . $context_id,
				null,
				$widget_context_args
			);
		}

		return $matches;
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
			'is_author' => is_author(),
		);

		$matched = array_intersect_assoc( $settings, $status );

		if ( ! empty( $matched ) ) {
			return true;
		}

		return $check;
	}

	/**
	 * Fetch a setting value for the context setting as a string.
	 *
	 * @param array $settings List of all settings by setting key.
	 * @param string $key Setting key to check.
	 *
	 * @return string
	 */
	protected function get_setting_as_string( $settings, $key ) {
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings = wp_parse_args(
			$settings,
			array(
				$key => null,
			)
		);

		return trim( (string) $settings[ $key ] );
	}

	/**
	 * Check if a set of URL paths match the current request.
	 *
	 * @param  bool  $check Current visibility state.
	 * @param  array $settings Visibility settings.
	 *
	 * @return bool
	 */
	public function context_check_url( $check, $settings ) {
		$path = $this->get_request_path();
		$urls = $this->get_setting_as_string( $settings, 'urls' );

		if ( ! empty( $urls ) && $this->match_path( $path, $urls ) ) {
			return true;
		}

		return $check;
	}

	/**
	 * Check if a set of URL paths match the current request.
	 *
	 * @param  bool  $check Current visibility state.
	 * @param  array $settings Visibility settings.
	 *
	 * @return bool
	 */
	public function context_check_urls_invert( $check, $settings ) {
		$path = $this->get_request_path();
		$urls = $this->get_setting_as_string( $settings, self::RULE_KEY_URLS_INVERT );

		if ( ! empty( $urls ) && $this->match_path( $path, $urls ) ) {
			return false; // Override any positive matches.
		}

		return $check;
	}

	/**
	 * Fetch the request path for the current request.
	 *
	 * @return string
	 */
	protected function get_request_path() {
		static $path;

		if ( ! isset( $path ) ) {
			$path = $this->path_from_uri( $_SERVER['REQUEST_URI'] );
		}

		return $path;
	}

	/**
	 * Return the path relative to the root of the hostname. We always remove
	 * the leading and trailing slashes around the URI path.
	 *
	 * @param  string $uri Current request URI.
	 *
	 * @return string
	 */
	public function path_from_uri( $uri ) {
		$parts = wp_parse_args(
			wp_parse_url( $uri ),
			array(
				'path' => '',
			)
		);

		$path = trim( $parts['path'], '/' );

		if ( ! empty( $parts['query'] ) ) {
			$path .= '?' . $parts['query'];
		}

		return $path;
	}

	/**
	 * Parse a text blob of URI fragments into URI rules.
	 *
	 * @param string $paths String of URI paths seperated by line breaks.
	 *
	 * @return array List of formatted URI paths.
	 */
	protected function uri_rules_from_paths( $paths ) {
		$patterns = explode( "\n", $paths );

		$patterns = array_map(
			function( $pattern ) {
				// Resolve rule paths the same way as the request URI.
				return $this->path_from_uri( trim( $pattern ) );
			},
			$patterns
		);

		return array_filter( $patterns );
	}

	/**
	 * Check if the current request matches path rules.
	 *
	 * @param  string $path  Current request relative to the root of the hostname.
	 * @param  string $rules A list of path patterns seperated by new line.
	 *
	 * @return bool|null Return `null` if no rules to match against.
	 */
	public function match_path( $path, $rules ) {
		$uri_rules = new UriRules( $this->uri_rules_from_paths( $rules ) );
		$uri_rules_paths = $uri_rules->rules();

		/**
		 * Ignore query parameters in path unless any of the rules actually use them.
		 * Defaults to matching paths with any query parameters.
		 */
		if ( ! $uri_rules->has_rules_with_query_strings() ) {
			$path = strtok( $path, '?' );
		}

		if ( ! empty( $uri_rules_paths ) ) {
			$matcher = new UriRuleMatcher();

			return $matcher->uri_matches_rules( $path, $uri_rules_paths );
		}

		return null;
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
				sprintf( 'context-group-%s', esc_attr( $context_name ) ),
			);

			// Hide this context from the admin UX. We can't remove them
			// because settings will get lost if this page is submitted.
			if ( isset( $this->context_settings['contexts'][ $context_name ] ) && ! $this->context_settings['contexts'][ $context_name ] ) {
				$context_classes[] = 'context-inactive';
				$controls_disabled[] = $context_name;
			}

			// Store core controls
			if ( isset( $context_settings['type'] ) && 'core' === $context_settings['type'] ) {
				$controls_core[] = $context_name;
			}

			$control_args = array(
				'name' => $context_name,
				'input_prefix' => 'wl' . $this->get_field_name( array( $widget_id, $context_name ) ),
				'settings' => $this->get_field_value( array( $widget_id, $context_name ) ),
				'widget_id' => $widget_id,
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
				$controls = array(
					sprintf(
						'<p class="error">%s</p>',
						sprintf(
							/* translators: %s is a URL to the settings page. */
							__( 'No widget controls enabled. You can enable them in <a href="%s">Widget Context settings</a>.', 'widget-context' ),
							$this->plugin_settings_admin_url()
						)
					),
				);
			} else {
				$controls = array(
					sprintf(
						'<p class="error">%s</p>',
						__( 'No widget controls enabled.', 'widget-context' )
					),
				);
			}
		}

		$settings_link = array();

		if ( current_user_can( 'edit_theme_options' ) ) {
			$settings_link[] = sprintf(
				'<a href="%s" title="%s" target="_blank">%s</a>',
				esc_url( $this->plugin_settings_admin_url() ),
				esc_attr__( 'Widget Context Settings', 'widget-context' ),
				esc_html__( 'Settings', 'widget-context' )
			);

			if ( $this->pro_nag_enabled() ) {
				$settings_link[] = sprintf(
					'<a href="%s" target="_blank">PRO 🚀</a>',
					esc_url( 'https://widgetcontext.com/pro' )
				);
			}
		}

		return sprintf(
			'<div class="widget-context">
				<div class="widget-context-header">
					<h3>%s</h3>
					<span class="widget-context-settings-link">%s</span>
				</div>
				<div class="widget-context-inside" id="widget-context-%s" data-widget-id="%s">
					%s
				</div>
			</div>',
			__( 'Widget Context', 'widget-context' ),
			implode( ' | ', $settings_link ),
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
			'hide' => __( 'Hide widget everywhere', 'widget-context' ),
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
			'is_author' => __( 'All author archives', 'widget-context' ),
		);

		foreach ( $options as $option => $label ) {
			$out[] = $this->make_simple_checkbox( $control_args, $option, $label );
		}

		return implode( '', $out );
	}


	function control_url( $control_args ) {
		return sprintf(
			'<div>%s</div>
			<p class="help">%s</p>',
			$this->make_simple_textarea( $control_args, 'urls' ),
			__( 'Enter one location fragment per line. Use <strong>*</strong> character as a wildcard. Example: <code>page/example</code> to target a specific page or <code>page/*</code> to target all children of a page.', 'widget-context' )
		);
	}


	function control_urls_invert( $control_args ) {
		return sprintf(
			'<div>%s</div>
			<p class="help">%s</p>',
			$this->make_simple_textarea( $control_args, self::RULE_KEY_URLS_INVERT ),
			__( 'Specify URLs to override the Target by URLs settings. Useful for excluding specific URLs when using wildcards in Target by URL.', 'widget-context' )
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
		$value = false;

		if ( isset( $control_args['settings'][ $option ] ) && $control_args['settings'][ $option ] ) {
			$value = true;
		}

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
		$value = '';

		if ( isset( $control_args['settings'][ $option ] ) ) {
			$value = esc_textarea( $control_args['settings'][ $option ] );
		}

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


	function make_simple_textfield( $control_args, $option, $label_before = null, $label_after = null ) {
		$value = false;

		if ( isset( $control_args['settings'][ $option ] ) ) {
			$value = esc_attr( $control_args['settings'][ $option ] );
		}

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
		$value = false;

		if ( isset( $control_args['settings'][ $option ] ) ) {
			$value = $control_args['settings'][ $option ];
		}

		if ( empty( $selection ) ) {
			$options[] = sprintf(
				'<option value="">%s</option>',
				esc_html__( 'No options available', 'widget-context' )
			);
		}

		foreach ( $selection as $sid => $svalue ) {
			$options[] = sprintf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $sid ),
				selected( $value, $sid, false ),
				esc_html( $svalue )
			);
		}

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
	 *
	 * @param  array $parts i.e. array( 'part1', 'part2', 'part3' )
	 * @return string        i.e. [part1][part2][partN]
	 */
	function get_field_name( $parts ) {
		return esc_attr( sprintf( '[%s]', implode( '][', $parts ) ) );
	}

	function get_field_classname( $name ) {
		if ( is_array( $name ) ) {
			$name = end( $name );
		}

		return sanitize_html_class( str_replace( '_', '-', $name ) );
	}


	/**
	 * Given option keys return its value
	 *
	 * @param  array $parts   i.e. array( 'part1', 'part2', 'part3' )
	 * @param  array $options i.e. array( 'part1' => array( 'part2' => array( 'part3' => 'VALUE' ) ) )
	 * @return string          Returns option value
	 */
	function get_field_value( $parts, $options = null ) {
		if ( null === $options ) {
			$options = $this->context_options;
		}

		$value = false;

		if ( empty( $parts ) || ! is_array( $parts ) ) {
			return false;
		}

		$part = array_shift( $parts );

		if ( ! empty( $parts ) && isset( $options[ $part ] ) && is_array( $options[ $part ] ) ) {
			$value = $this->get_field_value( $parts, $options[ $part ] );
		} elseif ( isset( $options[ $part ] ) ) {
			return $options[ $part ];
		}

		return $value;
	}


	function fix_legacy_options( $options ) {
		if ( empty( $options ) || ! is_array( $options ) ) {
			return $options;
		}

		foreach ( $options as $widget_id => $option ) {
			// This doesn't have an include/exclude rule defined
			if ( ! isset( $option['incexc'] ) ) {
				unset( $options[ $widget_id ] );
			}

			// We moved from [incexc] = 1/0 to [incexc][condition] = 1/0
			if ( isset( $option['incexc'] ) && ! is_array( $option['incexc'] ) ) {
				$options[ $widget_id ]['incexc'] = array( 'condition' => $option['incexc'] );
			}

			// Move notes from "general" group to "admin_notes"
			if ( isset( $option['general']['notes'] ) ) {
				$options[ $widget_id ]['admin_notes']['notes'] = $option['general']['notes'];
				unset( $option['general']['notes'] );
			}

			// We moved word count out of location context group
			if ( isset( $option['location']['check_wordcount'] ) ) {
				$options[ $widget_id ]['word_count'] = array(
					'check_wordcount' => true,
					'check_wordcount_type' => $option['location']['check_wordcount_type'],
					'word_count' => $option['location']['word_count'],
				);
			}
		}

		return $options;
	}



	/**
	 * Admin Settings
	 */


	function widget_context_settings_menu() {
		add_theme_page(
			__( 'Widget Context Settings', 'widget-context' ),
			__( 'Widget Context', 'widget-context' ),
			'manage_options',
			$this->settings_name,
			array( $this, 'widget_context_admin_view' ),
			3 // Try to place it right under the Widgets.
		);
	}


	function widget_context_settings_init() {
		register_setting( $this->settings_name, $this->settings_name );
	}


	/**
	 * Return a link to the Customize Widgets admin page.
	 *
	 * @return string
	 */
	public function customize_widgets_admin_url() {
		return admin_url( 'customize.php?autofocus[panel]=widgets' );
	}


	/**
	 * Get the URL to the plugin settings page.
	 *
	 * @return string
	 */
	public function plugin_settings_admin_url() {
		return admin_url( 'themes.php?page=widget_context_settings' );
	}


	function widget_context_admin_view() {
		$context_controls = array();

		foreach ( $this->get_contexts() as $context_id => $context_args ) {
			// Hide core modules from being disabled
			if ( isset( $context_args['type'] ) && 'core' === $context_args['type'] ) {
				continue;
			}

			if ( ! empty( $context_args['description'] ) ) {
				$context_description = sprintf(
					'<p class="description">%s</p>',
					esc_html( $context_args['description'] )
				);
			} else {
				$context_description = null;
			}

			// Enable new modules by default
			if ( ! isset( $this->context_settings['contexts'][ $context_id ] ) ) {
				$this->context_settings['contexts'][ $context_id ] = 1;
			}

			$context_controls[] = sprintf(
				'<li class="enabled-contexts-item context-%s">
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

						<table class="form-table" role="presentation">
							<tr id="widget-context-pro">
								<th scrope="row">
									<?php esc_html_e( 'Support', 'widget-context' ); ?>
								</th>
								<td>
									<p>
										<a href="https://widgetcontext.com/pro">Subscribe to get premium support</a> and the 🚀 PRO version of the plugin for free when it's launched!
										Your support enables consistent maintenance and new feature development, and is greatly appreciated.
									</p>
								</td>
							</tr>
							<tr>
								<th scrope="row">
									<?php esc_html_e( 'Configure Widgets', 'widget-context' ); ?>
								</th>
								<td>
									<p>
										<a class="button button-primary" href="<?php echo esc_url( $this->customize_widgets_admin_url() ); ?>"><?php esc_html_e( 'Configure Widgets', 'widget-context' ); ?></a>
									</p>
									<p class="description">
										<?php esc_html_e( 'Configure widget context using the WordPress Customizer (with preview) or using the widget settings under "Appearance → Widgets".', 'widget-context' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scrope="row">
									<?php esc_html_e( 'Enabled Contexts', 'widget-context' ); ?>
								</th>
								<td>
									<p>
										<?php esc_html_e( 'Select the context rules available for all widgets and hide the unused ones:', 'widget-context' ); ?>
									</p>
									<?php printf( '<ul>%s</ul>', implode( '', $context_controls ) ); ?>
								</td>
							</tr>
						</table>

						<?php
							submit_button();
						?>
					</form>
				</div>

				<div class="widget-context-sidebar">
					<div class="wc-sidebar-in">

						<div class="wc-sidebar-section wc-sidebar-credits">
							<p>
								<img src="https://gravatar.com/avatar/661eb21385c25c01ad64ab9e13b37331?s=120" alt="Kaspars Dambis" width="60" height="60" />
								<?php
								printf(
									// translators: %s: link with an anchor text.
									esc_html__( 'Widget Context is created and maintained by %s.', 'widget-context' ),
									'<a href="https://widgetcontext.com/about">Kaspars Dambis</a>'
								);
								?>
							</p>
						</div>

						<div class="wc-sidebar-section wc-sidebar-newsletter">
							<h3><?php esc_html_e( 'News & Updates', 'widget-context' ); ?></h3>
							<p><?php esc_html_e( 'Subscribe to receive news and updates about the plugin.', 'widget-context' ); ?></p>
							<form action="//osc.us2.list-manage.com/subscribe/post?u=e8d173fc54c0fc4286a2b52e8&amp;id=8afe96c5a3" method="post" target="_blank">
								<?php $user = wp_get_current_user(); ?>
								<p><label><?php _e( 'Your Name', 'widget-context' ); ?>: <input type="text" name="NAME" value="<?php echo esc_attr( sprintf( '%s %s', $user->first_name, $user->last_name ) ); ?>" /></label></p>
								<p><label><?php _e( 'Your Email', 'widget-context' ); ?>: <input type="text" name="EMAIL" value="<?php echo esc_attr( $user->user_email ); ?>" /></label></p>
								<p><input class="button" name="subscribe" type="submit" value="<?php esc_attr_e( 'Subscribe', 'widget-context' ); ?>" /></p>
							</form>
							<h3>
								<?php esc_html_e( 'Suggested Plugins', 'widget-context' ); ?>
							</h3>
							<p>
								<?php esc_html_e( 'Here are some of my other plugins:', 'widget-context' ); ?>
							</p>
							<ul>
								<li>
									<strong><small>NEW:</small></strong>
									<a href="https://blockcontext.com?utm_source=wc">Block Context</a> for showing or hiding Gutenberg blocks in context.
								</li>
								<li>
									<a href="https://preseto.com/go/cf7-storage?utm_source=wc">Storage for Contact Form 7</a> saves all Contact Form 7 submissions (including attachments) in your WordPress database.
								</li>
								<li>
									<a href="https://formcontrols.com/?utm_source=wc">Contact Form 7 Controls</a> adds a simple interface for managing Contact Form 7 form settings.
								</li>
							</ul>
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

}
