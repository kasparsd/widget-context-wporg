<?php
/*
Plugin Name: Widget Context
Plugin URI: http://wordpress.org/extend/plugins/widget-context/
Description: Display widgets in context.
Version: 0.8.1
Author: Kaspars Dambis
Author URI: http://konstruktors.com/

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
new widget_context();

class widget_context {
	
	var $options_name = 'widget_logic_options'; // Widget context settings (visibility, etc)
	var $context_options = array();
	var $words_on_page = 0;
	
	
	function widget_context() {
		// Load plugin settings
		add_action( 'init', array( $this, 'load_plugin_settings' ) );
		// Amend widget controls with Widget Context controls
		add_action( 'sidebar_admin_setup', array( $this, 'attach_widget_context_controls' ) );
		// Hide the widget if necessary
		add_filter( 'widget_display_callback', array( $this, 'maybe_hide_widget' ), 10, 3 );
		// Add admin menu for config
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		// Save widget context settings, when in admin area
		add_filter( 'admin_init', array( $this, 'save_widget_context_settings' ) );
		// Check the number of words on page
		add_action( 'wp', array( $this, 'count_words_on_page' ) );
	}


	function load_plugin_settings() {
		$this->context_options = get_option( $this->options_name );

		if ( ! is_array( $this->context_options ) || empty( $this->context_options ) )
			$this->context_options = array();
	}
		
	
	function admin_scripts() {
		wp_enqueue_style( 'widget-context-admin', WP_CONTENT_URL . '/plugins/'. basename(__DIR__) . '/admin-style.css' );
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
	
	
	function attach_widget_context_controls() {
		global $wp_registered_widget_controls, $wp_registered_widgets;
		
		foreach ($wp_registered_widgets as $widget_id => $widget_data) {
			// Pass widget id as param, so that we can later call the original callback function
			$wp_registered_widget_controls[$widget_id]['params'][]['widget_id'] = $widget_id;
			// Store the original callback functions and replace them with Widget Context
			$wp_registered_widget_controls[$widget_id]['callback_original_wc'] = $wp_registered_widget_controls[$widget_id]['callback'];
			$wp_registered_widget_controls[$widget_id]['callback'] = array($this, 'replace_widget_control_callback');
		}
	}


	function maybe_hide_widget( $instance, $widget_object, $args ) {
		if ( ! $this->check_widget_visibility( $args['widget_id'] ) )
			return false;

		return $instance;
	}

	
	function replace_widget_control_callback() {
		global $wp_registered_widget_controls;
		
		$all_params = func_get_args();
		if (is_array($all_params[1]))
			$widget_id = $all_params[1]['widget_id'];
		else
			$widget_id = $all_params[0]['widget_id'];
			
		$original_callback = $wp_registered_widget_controls[$widget_id]['callback_original_wc'];
		
		// Display the original callback
		if (isset($original_callback) && is_callable($original_callback)) {
			call_user_func_array($original_callback, $all_params);
		} else {
			print '<!-- widget context [controls]: could not call the original callback function -->';
		}
		
		print $this->display_widget_context( $widget_id );
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

		// Debug
		//echo $regexps;
		//print_r(array_filter( $patterns_safe, 'trim' ));

		return preg_match( $regexps, $path );
	}
	
	
	function count_words_on_page() {
		global $wp_query;
		
		if ( empty( $wp_query->posts ) || is_admin() )
			return;

		foreach ( $wp_query->posts as $post_data )
			$this->words_on_page += str_word_count( strip_tags( strip_shortcodes( $post_data->post_content ) ) );
	}

	
	function check_widget_visibility( $widget_id ) {
		// Show widget because no context settings found
		if ( ! isset( $this->context_options[ $widget_id ] ) )
			return true;
		
		$vis_settings = $this->context_options[ $widget_id ];

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
		
		return $do_show;
	}
	
	
	function display_widget_context( $wid = null ) {
		
		return '<div class="widget-context"><div class="widget-context-inside">'
			. '<p class="wl-visibility">'
				. $this->make_simple_dropdown( array( $wid, 'incexc' ), array( 'show' => __('Show everywhere'), 'selected' => __('Show on selected'), 'notselected' => __('Hide on selected'), 'hide' => __('Hide everywhere') ), sprintf( '<strong>%s</strong>', __( 'Widget Context' ) ) )
			. '</p>'

			. '<div class="wl-columns">' 
			. '<div class="wl-column-2-1"><p>' 
				. $this->make_simple_checkbox( array( $wid, 'location', 'is_front_page' ), __('Front Page') )
				. $this->make_simple_checkbox( array( $wid, 'location', 'is_home' ), __('Blog Index') )
				. $this->make_simple_checkbox( array( $wid, 'location', 'is_single' ), __('All Posts') )
				. $this->make_simple_checkbox( array( $wid, 'location', 'is_page' ), __('All Pages') )
				. $this->make_simple_checkbox( array( $wid, 'location', 'is_attachment' ), __('All Attachments') )
				. $this->make_simple_checkbox( array( $wid, 'location', 'is_search' ), __('Search') )
			. '</p></div>'
			. '<div class="wl-column-2-2"><p>' 
				. $this->make_simple_checkbox( array( $wid, 'location', 'is_archive' ), __('All Archives') )
				. $this->make_simple_checkbox( array( $wid, 'location', 'is_category' ), __('Category Archive') )
				. $this->make_simple_checkbox( array( $wid, 'location', 'is_tag' ), __('Tag Archive') )
				. $this->make_simple_checkbox( array( $wid, 'location', 'is_author' ), __('Author Archive') )
				. $this->make_simple_checkbox( array( $wid, 'location', 'is_404' ), __('404 Error') )
			. '</p></div>'
			
			. '<div class="wl-word-count"><p>' 
				. $this->make_simple_checkbox( array( $wid, 'location', 'check_wordcount' ), __('Has') )
				. $this->make_simple_dropdown( array( $wid, 'location', 'check_wordcount_type' ), array('less' => __('less'), 'more' => __('more')), '', __('than') )
				. $this->make_simple_textfield( array( $wid, 'location', 'word_count' ), __('words') )
			. '</p></div>'
			. '</div>'
			
			. '<div class="wl-options">'
				. $this->make_simple_textarea( array( $wid, 'url', 'urls' ), __('or target by URL'), __('Enter one location fragment per line. Use <strong>*</strong> character as a wildcard. Example: <code>category/peace/*</code> to target all posts in category <em>peace</em>.') )
			. '</div>'
			
			. $this->make_simple_textarea( array( $wid, 'general', 'notes' ), __('Notes (invisible to public)'))
		. '</div></div>';

	}
	
	
	/* 
		
		Interface constructors 
		
	*/

	
	function make_simple_checkbox( $name, $label ) {
		return sprintf( 
				'<label class="wl-%s"><input type="checkbox" value="1" name="wl%s" %s />&nbsp;%s</label>',
				$this->get_field_classname( $name ),
				$this->get_field_name( $name ),
				checked( (bool) $this->get_field_value( $name ), 1, false ),
				$label
			);
	}

	
	function make_simple_textarea( $name, $label, $tip = null ) {
		if ( $tip )
			$tip = sprintf( '<p class="wl-tip">%s</p>', $tip );
		
		return sprintf(  
				'<div class="wl-%s">
					<label>
						<strong>%s</strong>
						<textarea name="wl%s">%s</textarea>
					</label>
					%s
				</div>',
				$this->get_field_classname( $name ),
				$label,
				$this->get_field_name( $name ),
				esc_textarea( $this->get_field_value( $name ) ),
				$tip
			);
	}


	function make_simple_textfield( $name, $label_before = null, $label_after = null) {
		return sprintf( 
				'<label class="wl-%s">%s <input type="text" name="wl%s" value="%s" /> %s</label>',
				$this->get_field_classname( $name ),
				$label_before,
				$this->get_field_name( $name ),
				esc_attr( $this->get_field_value( $name ) ),
				$label_after
			);
	}


	function make_simple_dropdown( $name, $selection = array(), $label_before = null, $label_after = null ) {
		$value = esc_attr( $this->get_field_value( $name ) );
		$options = array();

		if ( empty( $selection ) )
			$options[] = sprintf( '<option value="">%s</option>', __('No options given') );

		foreach ( $selection as $sid => $svalue )
			$options[] = sprintf( '<option value="%s" %s>%s</option>', $sid, selected( $value, $sid, false ), $svalue );

		return sprintf( 
				'<label class="wl-%s">
					%s 
					<select name="wl%s">
						%s
					</select> 
					%s
				</label>',
				$this->get_field_classname( $name ),
				$label_before, 
				$this->get_field_name( $name ), 
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

	function get_field_classname( $parts ) {
		return sanitize_html_class( str_replace( '_', '-', end( $parts ) ) );
	}


	/**
	 * Given option keys return its value
	 * @param  array  $parts   i.e. array( 'part1', 'part2', 'part3' )
	 * @param  array  $options i.e. array( 'part1' => array( 'part2' => array( 'part3' => 'VALUE' ) ) )
	 * @return string          Returns option value
	 */
	function get_field_value( $parts, $options = array() ) {
		if ( empty( $options ) )
			$options = $this->context_options;

		if ( ! empty( $parts ) )
			$part = array_shift( $parts );

		if ( isset( $part ) && isset( $options[ $part ] ) && is_array( $options[ $part ] ) )
			$value = $this->get_field_value( $parts, $options[ $part ] );
		elseif ( isset( $options[ $part ] ) )
			$value = $options[ $part ];
		else 
			$value = '';

		return trim( $value );
	}


}

