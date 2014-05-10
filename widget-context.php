<?php
/*
Plugin Name: Widget Context
Plugin URI: http://wordpress.org/extend/plugins/widget-context/
Description: Display widgets in context.
Version: 1.0-alpha
Author: Kaspars Dambis
Author URI: http://kaspars.net

For changelog see readme.txt
----------------------------
	
    Copyright 2009  Kaspars Dambis  (email: kaspars@konstruktors.com)
	
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Go!
widget_context::instance();

class widget_context {
	
	private static $instance;

	var $options_name = 'widget_logic_options'; // Widget context settings (visibility, etc)
	var $context_options = array();
	var $contexts = array();
	var $plugin_path;

	
	static function instance() {

		if ( ! self::$instance )
			self::$instance = new self();

		return self::$instance;

	}

	
	private function widget_context() {

		// Define available widget contexts
		add_action( 'init', array( $this, 'define_widget_contexts' ), 5 );

		// Load plugin settings
		add_action( 'init', array( $this, 'load_plugin_settings' ) );
		
		add_action( 'in_widget_form', array( $this, 'widget_context_controls' ), 10, 3 );
		
		// Hide the widget if necessary
		add_filter( 'widget_display_callback', array( $this, 'maybe_hide_widget' ), 10, 3 );
		
		// Add admin menu for config
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		
		// Save widget context settings, when in admin area
		add_action( 'sidebar_admin_setup', array( $this, 'save_widget_context_settings' ) );

	}


	function load_plugin_settings() {

		$this->context_options = wp_parse_args(
				(array) get_option( $this->options_name ),
				array_fill_keys( array_keys( $this->contexts ), null )			
			);

		$this->context_options = apply_filters( 'widget_context_options', $this->context_options );

	}
		
	
	function admin_scripts() {
		
		wp_enqueue_style( 
			'widget-context-css', 
			plugins_url( 'css/admin.css', plugin_basename( __FILE__ ) ) 
		);

		wp_enqueue_script( 
			'widget-context-js', 
			plugins_url( 'js/widget-context.js', plugin_basename( __FILE__ ) ), 
			array( 'jquery' ) 
		);
	
	}


	function widget_context_controls( $object, $return, $instance ) {

		echo $this->display_widget_context( $object->id );

	}

	
	function save_widget_context_settings() {

		if ( ! current_user_can( 'edit_theme_options' ) || empty( $_POST ) || ! isset( $_POST['sidebar'] ) || empty( $_POST['sidebar'] ) )
			return;
		
		// Delete
		if ( isset( $_POST['delete_widget'] ) && $_POST['delete_widget'] )
			unset( $this->context_options[ $_POST['widget-id'] ] );
		
		// Add / Update
		$this->context_options = array_merge( $this->context_options, $_POST['wl'] );

		update_option( $this->options_name, $this->context_options );

	}


	function define_widget_contexts() {

		// Default context
		$default_contexts = array(
			'incexc' => array(
				'label' => __( 'Widget Context' ),
				'description' => __( 'Set the default logic to show or hide.', 'widget-context' ),
				'weight' => -100
			),
			'location' => array(
				'label' => __( 'Global Sections' ),
				'description' => __( 'Context based on current template.', 'widget-context' ),
				'weight' => 10
			),
			'url' => array(
				'label' => __( 'Target by URL' ),
				'description' => __( 'Context based on URL pattern.', 'widget-context' ),
				'weight' => 10
			),
			'general' => array(
				'label' => __( 'Notes (invisible to public)', 'widget-context' ),
				'description' => __('Notes to admins.'),
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

		uasort( $this->contexts, array( $this, 'sort_context_by_weight' ) );

	}


	function sort_context_by_weight( $a, $b ) {

		if ( ! isset( $a['weight'] ) )
			$a['weight'] = 10;

		if ( ! isset( $b['weight'] ) )
			$b['weight'] = 10;

		return ( $a['weight'] < $b['weight'] ) ? -1 : 1;

	}


	function maybe_hide_widget( $instance, $widget_object, $args ) {

		// Make sure that widget is not being hidden already,
		// then check our visibility settings
		if ( $instance && ! $this->check_widget_visibility( $args['widget_id'] ) )
			return false;

		return $instance;

	}
	
	
	function get_current_url() {

		if ($_SERVER['REQUEST_URI'] == '') 
			$uri = $_SERVER['REDIRECT_URL'];
		else 
			$uri = $_SERVER['REQUEST_URI'];
		
		return (!empty($_SERVER['HTTPS'])) 
			? "https://".$_SERVER['SERVER_NAME'].$uri 
			: "http://".$_SERVER['SERVER_NAME'].$uri;

	}

	
	// Thanks to Drupal: http://api.drupal.org/api/function/drupal_match_path/6
	function match_path( $path, $patterns ) {

		$patterns_safe = array();

		// Strip home url and check only the REQUEST_URI part
		$path = trim( str_replace( trailingslashit( get_bloginfo('url') ), '', $path ), '/' );

		foreach ( explode( "\n", $patterns ) as $pattern )
			$patterns_safe[] = trim( trim( $pattern ), '/' );

		$regexps = '/^('. preg_replace( array( '/(\r\n|\n| )+/', '/\\\\\*/' ), array( '|', '.*' ), preg_quote( implode( "\n", array_filter( $patterns_safe, 'trim' ) ), '/' ) ) .')$/';

		return preg_match( $regexps, $path );

	}

	
	function check_widget_visibility( $widget_id ) {

		$matches = array();

		foreach ( $this->contexts as $context_id => $context_settings ) {

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

		/*
		// Hide/show if forced
		if ( $vis_settings['incexc'] == 'hide' )
			return false;
		elseif ( $vis_settings['incexc'] == 'show' )
			return true;
		
		$do_show = true;
		$do_show_by_select = false;
		$do_show_by_url = false;
		$do_show_by_word_count = false;
		
		// Check by current URL
		if ( ! empty( $vis_settings['url']['urls'] ) )
			if ( $this->match_path( $this->get_current_url(), $vis_settings['url']['urls'] ) ) 
				$do_show_by_url = true;

		// Check by tag settings
		if ( ! empty( $vis_settings['location'] ) ) {
			$currently = array();
			
			if ( is_front_page() && ! is_paged() ) $currently['is_front_page'] = true;
			if ( is_home() && ! is_paged() ) $currently['is_home'] = true;
			if ( is_page() && ! is_attachment() ) $currently['is_page'] = true;
			if ( is_single() && ! is_attachment() ) $currently['is_single'] = true;
			if ( is_archive() ) $currently['is_archive'] = true;
			if ( is_category() ) $currently['is_category'] = true;
			if ( is_tag() ) $currently['is_tag'] = true;
			if ( is_author() ) $currently['is_author'] = true;
			if ( is_search() ) $currently['is_search'] = true;
			if ( is_404() ) $currently['is_404'] = true;
			if ( is_attachment() ) $currently['is_attachment'] = true;
			
			// Check for selected pages/sections
			if ( array_intersect_key( $currently, $vis_settings['location'] ) )
				$do_show_by_select = true;

			// Word count
			if ( isset( $vis_settings['location']['check_wordcount'] ) ) {
				// Check for word count
				$word_count_to_check = intval( $vis_settings['location']['word_count'] );
				$check_type = $vis_settings['location']['check_wordcount_type'];

				if ( $this->words_on_page > $word_count_to_check && $check_type == 'more' )
					$do_show_by_word_count = true;
				elseif ( $this->words_on_page < $word_count_to_check && $check_type == 'less' )
					$do_show_by_word_count = true;
				else
					$do_show_by_word_count = false;
			}	
		}
		
		// Combine all context checks
		if ($do_show_by_word_count || $do_show_by_url || $do_show_by_select)
			$one_is_true = true;
		elseif (!$do_show_by_word_count || !$do_show_by_url || !$do_show_by_select)
			$one_is_true = false;	
		
		if (($vis_settings['incexc'] == 'selected') && $one_is_true) {
			// Show on selected
			$do_show = true;
		} elseif (($vis_settings['incexc'] == 'notselected') && !$one_is_true) {
			// Hide on selected
			$do_show = true;
		} elseif (!empty($vis_settings['incexc'])) {
			$do_show = false;
		} else {
			$do_show = true;
		}
		*/
		
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
				'is_single' => is_single(),
				'is_page' => is_page(),
				'is_attachment' => is_attachment(),
				'is_search' => is_search(),
				'is_404' => is_404(),
				'is_archive' => is_archive(),
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

		$urls = trim( $settings['urls'] );

		if ( empty( $urls ) )
			return $check;

		if ( $this->match_path( $this->get_current_url(), $urls ) ) 
			return true;

		return $check;

	}


	// Dummy function
	function context_check_notes( $check, $widget_id ) {}


	// Dummy function
	function context_check_general( $check, $widget_id ) {}


	/*
		Widget Controls
	 */

	function display_widget_context( $widget_id = null ) {

		$controls = array();

		foreach ( $this->contexts as $context_name => $context_settings ) {

			$control_args = array(
				'name' => $context_name,
				'input_prefix' => 'wl' . $this->get_field_name( array( $widget_id, $context_name ) ),
				'settings' => $this->get_field_value( array( $widget_id, $context_name ) )
			);

			if ( $context_controls = apply_filters( 'widget_context_control-' . $context_name, $control_args ) )
				if ( is_string( $context_controls ) )
					$controls[ $context_name ] = sprintf( 
							'<div class="context-group context-group-%1$s">
								<h4 class="context-toggle">%2$s</h4>
								<div class="context-group-wrap">
									%3$s
								</div>
							</div>',
							$context_name,
							$context_settings['label'],
							$context_controls
						);

		}

		if ( empty( $controls ) )
			$controls[] = sprintf( '<p class="error">%s</p>', __('No settings defined.') );

		return sprintf( 
				'<div class="widget-context">
					<div class="widget-context-header">
						<h3>%s</h3>
						<!-- <a href="#widget-context-%s" class="toggle-contexts hide-if-no-js">
							<span class="expand">%s</span>
							<span class="collapse">%s</span>
						</a> -->
					</div>
					<div class="widget-context-inside" id="widget-context-%s">
					%s
					</div>
				</div>',
				__( 'Widget Context', 'widget-context' ),
				esc_attr( $widget_id ),
				__( 'Expand', 'widget-context' ),
				__( 'Collapse', 'widget-context' ),
				esc_attr( $widget_id ),
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
				'is_front_page' => __( 'Front Page', 'widget-context' ),
				'is_home' => __( 'Blog Page', 'widget-context' ),
				'is_single' => __( 'All Posts', 'widget-context' ),
				'is_page' => __( 'All Pages', 'widget-context' ),
				'is_attachment' => __( 'All Attachments', 'widget-context' ),
				'is_search' => __( 'Search Results', 'widget-context' ),
				'is_404' => __( '404 Error Page', 'widget-context' ),
				'is_archive' => __( 'All Archives', 'widget-context' ),
				'is_category' => __( 'All Category Archives', 'widget-context' ),
				'is_tag' => __( 'All Tag Archives', 'widget-context' ),
				'is_author' => __( 'Author Archive', 'widget-context' )
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


	function control_general( $control_args ) {
		return sprintf( 
				'<p>%s</p>',
				$this->make_simple_textarea( $control_args, 'notes' )
			);
	}

	
	/* 
		
		Interface constructors 
		
	*/

	
	function make_simple_checkbox( $control_args, $option, $label ) {

		return sprintf(
				'<label class="wc-location-%s"><input type="checkbox" value="1" name="%s[%s]" %s />&nbsp;%s</label>',
				$this->get_field_classname( $option ),
				$control_args['input_prefix'],
				esc_attr( $option ),
				checked( isset( $control_args['settings'][ $option ] ), true, false ),
				$label
			);

	}

	
	function make_simple_textarea( $control_args, $option, $label = null ) {

		if ( isset( $control_args['settings'][ $option ] ) )
			$value = esc_textarea( $control_args['settings'][ $option ] );
		else
			$value = '';
		
		return sprintf(  
				'<label class="wc-%s">
					<strong>%s</strong>
					<textarea name="%s[%s]">%s</textarea>
				</label>',
				$this->get_field_classname( $option ),
				$label,
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
				'<label class="wl-%s">%s <input type="text" name="%s[%s]" value="%s" /> %s</label>',
				$this->get_field_classname( $option ),
				$label_before,
				$control_args['input_prefix'],
				$option,
				$value,
				$label_after
			);

	}


	function make_simple_dropdown( $control_args, $option, $selection = array(), $label_before = null, $label_after = null ) {

		$options = array();

		if ( isset( $control_args['settings'][ $option ] ) )
			$value = $control_args['settings'][ $option ];
		else
			$value = false;

		if ( empty( $selection ) )
			$options[] = sprintf( '<option value="">%s</option>', __('No options given') );

		foreach ( $selection as $sid => $svalue )
			$options[] = sprintf( '<option value="%s" %s>%s</option>', $sid, selected( $value, $sid, false ), $svalue );

		return sprintf( 
				'<label class="wl-%s">
					%s 
					<select name="%s[%s]">
						%s
					</select> 
					%s
				</label>',
				$this->get_field_classname( $option ),
				$label_before, 
				$control_args['input_prefix'], 
				$option,
				implode( '', $options ), 
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

			// We moved from [incexc] = 1/0 to [incexc][condition] = 1/0
			if ( ! is_array( $option['incexc'] ) )
				$options[ $widget_id ]['incexc'] = array( 'condition' => true );
			
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


}


/**
 * Load core modules
 */

include plugin_dir_path( __FILE__ ) . '/modules/word-count/word-count.php';


