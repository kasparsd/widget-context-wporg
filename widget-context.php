<?php
/*
Plugin Name: Widget Context
Plugin URI: http://konstruktors.com/
Description: Display widgets in context.
Version: 0.7.1
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
	var $did_filter_sidebars = false;
	
	
	function widget_context() {
		$this->plugin_path = WP_CONTENT_URL . '/plugins/'. plugin_basename(dirname(__FILE__)) . '/';
		
		// Amend widget controls with Widget Context controls
		add_action('sidebar_admin_setup', array($this, 'attach_widget_context_controls'));
		// Enable widget context check only when viewed publicly,
		add_action('wp_head', array($this, 'replace_widget_output_callback'));
		// Add admin menu for config
		add_action('admin_enqueue_scripts', array($this, 'adminCSS'));
		// Save widget context settings, when in admin area
		add_filter('sidebars_widgets', array($this, 'filter_widgets'), 50);
		// Check the number of words on page
		add_action('wp_print_scripts', array($this, 'count_words_on_page'), 750);
	}
		
	
	function adminCSS() {
		wp_enqueue_style('widget-context-admin', $this->plugin_path . 'admin-style.css');
	}
	
	
	function filter_widgets($sidebars_widgets) {
		global $_wp_sidebars_widgets, $paged;

		if (isset($_POST['wl']) && !empty($_POST['wl']) && is_admin()) {
			$this->save_widget_context();
			unset($_POST['wl']);

		} elseif (!is_admin() && isset($paged)) {

			// Get widget context options and check visibility settings
			if (empty($this->context_options))
				$this->context_options = get_option($this->options_name);

			// If we have done this before, return the truth
			if ($this->did_filter_sidebars)
				return $sidebars_widgets; //return $this->active_sidebars;

			foreach ($sidebars_widgets as $sidebar_id => $widgets) {
				// Check if widget will be shown
				if ($sidebar_id != 'wp_inactive_widgets' && !empty($widgets)) {
					foreach ($widgets as $widget_no => $widget_id) {
						if (!$this->check_widget_visibility($this->context_options[$widget_id])) {
							unset($sidebars_widgets[$sidebar_id][$widget_no]);
							unset($_wp_sidebars_widgets[$sidebar_id][$widget_no]);
						}
					}
				}
			}
			
			$this->did_filter_sidebars = true;
		}
		
		return $sidebars_widgets;
	}	
	
	
	function save_widget_context() {
		global $wp_registered_widgets;
			
		if (empty($_POST['widget-id'])) 
			$_POST['widget-id'] = array();			
		
		if (isset($_POST['sidebar']) && !empty($_POST['sidebar'])) 
			$sidebar_id = strip_tags((string)$_POST['sidebar']);
		else
			return;
		
		// Load widget context settings
		$options = get_option($this->options_name);
		
		// Delete
		if (isset($_POST['delete_widget']) && $_POST['delete_widget']) {
			$del_id = $_POST['widget-id'];
			unset($options[$del_id]);
		}
		
		$new_widget_context_settings = array_values($_POST['wl']);	
		
		// Add/Update
		foreach($new_widget_context_settings as $widget_id => $widget_context) {
			if (empty($widget_context))
				$widget_context = array();
			// If neither type of widget logic behaviour is selected, set to default
			if (!isset($widget_context['incexc'])) 
				$widget_context['incexc'] = 'notselected';
				
			$options[(string)$_POST['widget-id']] = $widget_context;
		}

		update_option($this->options_name, (array)$options);
		
		return;
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
	
	
	function replace_widget_output_callback() {
		global $wp_registered_widgets;
		
		// Get widget logic options and check visibility settings
		if (empty($this->context_options))
			$this->context_options = get_option($this->options_name);
		
		foreach ($wp_registered_widgets as $widget_id => $widget_data) {
			// Check if widget will be shown
			$do_show = $this->check_widget_visibility($this->context_options[$widget_id]);
			
			if (!$do_show) { // If not shown, remove it temporeraly from the list of existing widgets
				unregister_sidebar_widget($widget_id);
			} else {
				//if (!$wp_registered_widgets[$widget_id]['params'][0]['widget_id']) {
				// Save the original widget id
				$wp_registered_widgets[$widget_id]['params'][]['widget_id'] = $widget_id;
				// Store original widget callbacks
				$wp_registered_widgets[$widget_id]['callback_original_wc'] = $wp_registered_widgets[$widget_id]['callback'];
				$wp_registered_widgets[$widget_id]['callback'] = array($this, 'replace_widget_output');
			}
		}
	}
	
	
	function replace_widget_output() {
		global $wp_registered_widgets;
		
		$all_params = func_get_args();
		
		if (is_array($all_params[2]))
			$widget_id = $all_params[2]['widget_id'];
		else
			$widget_id = $all_params[1]['widget_id'];
			
		$widget_callback = $wp_registered_widgets[$widget_id]['callback_original_wc'];
		
		if (is_callable($widget_callback)) {
			call_user_func_array($widget_callback, $all_params);
			return true;
		} elseif (!is_callable($widget_callback)) {
			// print_r($all_params);
			print '<!-- widget context: could not call the original callback function -->';
			return false;
		} else {
			return false;
		}
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
		
		print $this->display_widget_context($original_callback, $widget_id);
	}
	
	
	function get_current_url() {
		if ($_SERVER['REQUEST_URI'] == '') 
			$uri = $_SERVER['REDIRECT_URL'];
		else 
			$uri = $_SERVER['REQUEST_URI'];
		
		$url = (!empty($_SERVER['HTTPS'])) 
			? "https://".$_SERVER['SERVER_NAME'].$uri 
			: "http://".$_SERVER['SERVER_NAME'].$uri;
		
		if (substr($url, -1) == '/') 
			$url = substr($url, 0, -1);
			
		return $url;
	}
	
	// Thanks to Drupal: http://api.drupal.org/api/function/drupal_match_path/6
	function match_path($path, $patterns) {
		static $regexps;
		
		// get home url;
		$home_url = get_bloginfo('url');
		
		// add trailing slash if missing
		if (substr($home_url, -1) !== '/') 
			$home_url = $home_url . '/';
		
		// Check if user has specified the absolute url	
		// else strip home url and check only REQUEST_URI part
		if ($path !== $home_url && !strstr($patterns, $_SERVER['SERVER_NAME'])) 
			$path = str_replace($home_url, '', $path);
		
		// Remove http:// from the url user has specified
		if (strstr($patterns, 'http://'))
			$patterns = str_replace('http://', '', $patterns);
		
		// Remove http:// from the current url
		if (strstr($path, 'http://'))
			$path = str_replace('http://', '', $path);
		
		if (!isset($regexps[$patterns])) {
			$regexps[$patterns] = '/^('. preg_replace(array('/(\r\n?|\n)/', '/\\\\\*/', '/(^|\|)\\\\<home\\\\>($|\|)/'), array('|', '.*', '\1'. preg_quote($home_url, '/') .'\2'), preg_quote($patterns, '/')) .')$/';
		}
		return preg_match($regexps[$patterns], $path);
	}
	
	
	function count_words_on_page() {
		global $wp_query;
		
		$this->words_on_page = 0;
		
		if (count($wp_query->posts) > 0 && function_exists('strip_shortcodes')) {
			foreach ($wp_query->posts as $pid => $post_data) {
				if ($post_data->post_status == 'publish') {
					$pure_content = strip_shortcodes($post_data->post_content);
					if (!is_single() && !is_page()) {
						if (preg_match('/<!--more(.*?)?-->/', $pure_content, $matches)) {
							$pure_content = explode($matches[0], $pure_content, 2);
							$words_in_post = str_word_count(strip_tags($pure_content[0]));
						}
					} else {
						$words_in_post = str_word_count(strip_tags($pure_content));
					}
					
					$this->words_on_page += $words_in_post;
				}
			}
		}
	}
	
	function check_widget_visibility($vis_settings = array()) {
		global $paged;
		
		if (empty($vis_settings)) 
			return true;
		
		$do_show = true;
		$do_show_by_select = false;
		$do_show_by_url = false;
		$do_show_by_word_count = false;
		
		// Check by current URL
		if (!empty($vis_settings['url']['urls'])) {
			// Split on line breaks
			$split_urls = split("[\n ]+", (string)$vis_settings['url']['urls']);
			$current_url = $this->get_current_url();
			foreach ($split_urls as $id => $check_url) {
				$check_url = trim($check_url);
				if ($check_url !== '') {
					if ($this->match_path($current_url, $check_url)) 
						$do_show_by_url = true;
				} else {
					$ignore_url = true;
				}
			}
			
			if (!$ignore_url && $do_show_by_url)
				$do_show_by_url = true;
			else
				$do_show_by_url = false;
		}

		// Check by tag settings
		if (!empty($vis_settings['location'])) {
			$currently = array();
			
			if (is_front_page() && $paged < 2) $currently['is_front_page'] = true;
			if (is_home() && $paged < 2) $currently['is_home'] = true;
			if (is_page() && !is_attachment()) $currently['is_page'] = true;
			if (is_single() && !is_attachment()) $currently['is_single'] = true;
			if (is_archive()) $currently['is_archive'] = true;
			if (is_category()) $currently['is_category'] = true;
			if (is_tag()) $currently['is_tag'] = true;
			if (is_author()) $currently['is_author'] = true;
			if (is_search()) $currently['is_search'] = true;
			if (is_404()) $currently['is_404'] = true;
			if (is_attachment()) $currently['is_attachment'] = true;
			
			// Check for selected pages/sections
			$current_location = array_keys($currently); 
			$visibility_options = array_keys($vis_settings['location']);
			foreach($current_location as $location_id) {					
				if (in_array($location_id, $visibility_options)) 
					$do_show_by_select = true;
			}
			
			// Check for word count
			$word_count_to_check = (int)$vis_settings['location']['word_count'];
			
			if ($vis_settings['location']['check_wordcount'] == 'on' && $word_count_to_check > 1) {
				$check_type = $vis_settings['location']['check_wordcount_type'];
				
				if (($check_type == 'more') && ($this->words_on_page > $word_count_to_check)) {
					print '<!-- showing because '. $this->words_on_page .' > '. $word_count_to_check .' -->';
					$do_show_by_word_count = true;
				} elseif (($check_type == 'less') && ($this->words_on_page < $word_count_to_check)) {
					print '<!-- showing because '. $this->words_on_page .' < '. $word_count_to_check .' -->';
					$do_show_by_word_count = true;
				}
			} else {
				$ignore_word_count = true;
			}
			
			if (!$ignore_word_count && $do_show_by_word_count)
				$do_show_by_word_count = true;
			else 
				$do_show_by_word_count = false;
				
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
		
		// Hide if selected
		if ($vis_settings['incexc'] == 'hide') {
			$do_show = false;
		}
		
		return $do_show;
	}
	
	
	function display_widget_context($args = array(), $wid = null) {
		
		$group = 'location'; // Produces: wl[$wid][$group][homepage/singlepost/...]
		$options = get_option($this->options_name);
		
		$out = '<div class="widget-context"><div class="widget-context-inside">';
		$out .=   '<div class="wl-header"><h5>Widget Context:</h5>'
			. '<p class="wl-visibility">'		
				. $this->make_simple_radio($options, $wid, 'incexc', 'selected', '<strong>Show</strong> on selected') 
				. $this->make_simple_radio($options, $wid, 'incexc', 'notselected', '<strong>Hide</strong> on selected') 
				. $this->make_simple_radio($options, $wid, 'incexc', 'hide', 'Hide') 
			. '</p></div>'
			
			. $this->show_support() // you can comment out this line, if you want.
			
			. '<div class="wl-wrap-columns">'
			
				. '<div class="wl-columns">' 
				. '<div class="wl-column-2-1"><p>' 
				. $this->make_simple_checkbox($options, $wid, $group, 'is_front_page', __('Front Page'))
				. $this->make_simple_checkbox($options, $wid, $group, 'is_home', __('Blog Index'))
				. $this->make_simple_checkbox($options, $wid, $group, 'is_single', __('Single Post'))
				. $this->make_simple_checkbox($options, $wid, $group, 'is_page', __('Single Page'))
				. $this->make_simple_checkbox($options, $wid, $group, 'is_attachment', __('Attachment'))
				. $this->make_simple_checkbox($options, $wid, $group, 'is_search', __('Search'))
				. '</p></div>'
				. '<div class="wl-column-2-2"><p>' 
				. $this->make_simple_checkbox($options, $wid, $group, 'is_archive', __('All Archives'))
				. $this->make_simple_checkbox($options, $wid, $group, 'is_category', __('Category Archive'))
				. $this->make_simple_checkbox($options, $wid, $group, 'is_tag', __('Tag Archive'))
				. $this->make_simple_checkbox($options, $wid, $group, 'is_author', __('Author Archive'))
				. $this->make_simple_checkbox($options, $wid, $group, 'is_404', __('404 Error'))
				. '</p></div>'
				
				. '<div class="wl-word-count"><p>' 
				. $this->make_simple_checkbox($options, $wid, $group, 'check_wordcount', __('Has'))
				. $this->make_simple_dropdown($options, $wid, $group, 'check_wordcount_type', array('less' => __('less'), 'more' => __('more')), '', __('than'))
				. $this->make_simple_textfield($options, $wid, $group, 'word_count', null, __('words'))
				. '</p></div>'
				
				. '</div>'
				
				. '<div class="wl-options">'
				. $this->make_simple_textarea($options, $wid, 'url', 'urls', __('or target by URL'), __('Enter one location fragment per line. Use <strong>*</strong> character as a wildcard. Use <strong><code>&lt;home&gt;</code></strong> to select front page. Examples: <strong><code>category/peace/*</code></strong> to target all <em>peace</em> category posts; <strong><code>2012/*</code></strong> to target articles written in year 2012.'))
				. '</div>'
			
			. '</div>'
			
			. $this->make_simple_textarea($options, $wid, 'general', 'notes', __('Notes (invisible to public)'))
		. '</div></div>';
		
		return $out;
	}
	
	function printAdminOptions() {
		global $wp_registered_widget_controls, $wp_registered_widgets;
		
		print '<div class="wrap"><h2>'.__('Widget Context Plugin Settings') . '</h2></div>';		
		print '<pre>'; print_r(get_option($this->options_name)); print '</pre>';
	}
	
	
	
	/* 
		
		Interface constructors 
		
	*/
	
	function show_support() {
		$out = '';
		if (rand(1,3) == 1)	
			$out = '<p class="show-support"><small>If you find <em>Widget Context</em> plugin useful, please <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=kaspars%40konstruktors%2ecom&item_name=Widget%20Context%20Plugin%20for%20WordPress&no_shipping=1&no_note=1&tax=0&currency_code=EUR&lc=LV&bn=PP%2dDonationsBF&charset=UTF%2d8">donate</a> a few silver coins to support the development. Thanks. <a href="http://konstruktors.com/blog/">Kaspars</a></small></p>';
		
		return $out;
	}
	
	function makeSubmitButton() {
		return '<p class="submit"><input type="submit" name="Submit" value="' . __('Save Options') . '" /></p>';
	}
	
	function make_simple_checkbox($options, $prefix, $id, $fieldname = null, $label) {
		if ($fieldname !== null) {
			$value = strip_tags($options[$prefix][$id][$fieldname]);
			$fieldname = '[' . $fieldname . ']';
		} else {
			$value = strip_tags($options[$prefix][$id]);
			$fieldname = '';
		}
		
		$prefix = '[' . $prefix . ']';
		$id = '[' . $id . ']';
		
		if (!empty($value)) {
			$value = 1; $checked = 'checked="checked"'; $classname = 'wl-active';
		} else {
			$value = 0; $checked = ''; $classname = 'wl-inactive'; 
		}
		
		$out = '<label class="' . $classname . '"><input type="checkbox" name="wl'. $prefix . $id . $fieldname . '" '. $checked .' />&nbsp;' 
			. $label . '</label> ';
			
		return $out;
	}
	
	function make_simple_textarea($options, $prefix, $id, $fieldname = null, $label, $tip = null) {
		$classname = $fieldname;
		
		if ($fieldname !== null) {
			$value = $options[$prefix][$id][$fieldname];
			$fieldname = '[' . $fieldname . ']';
		} else {
			$value = $options[$prefix][$id];
			$fieldname = '';
		}
		$prefix = '[' . $prefix . ']';
		$id = '[' . $id . ']';
		
		if ($tip !== null) $tip = '<p class="wl-tip">' . $tip . '</p>';
		
		$out = '<div class="wl-'. $classname .'">'
			. '<label for="wl'. $prefix . $id . $fieldname . '"><strong>' . $label . '</strong></label>'
			. '<textarea name="wl'. $prefix . $id . $fieldname . '" id="wl'. $prefix . $id . $fieldname . '">'. stripslashes($value) .'</textarea>'
			. $tip . '</div>';
		return $out;
	}

	function make_simple_textfield($options, $prefix, $id, $fieldname = null, $label_before = null, $label_after = null) {
		$classname = $fieldname;
		
		if ($fieldname !== null) {
			$value = $options[$prefix][$id][$fieldname];
			$fieldname = '[' . $fieldname . ']';
		} else {
			$value = $options[$prefix][$id];
			$fieldname = '';
		}
		$prefix = '[' . $prefix . ']';
		$id = '[' . $id . ']';
		
		return '<label class="wl-'. $classname . '">' . $label_before . ' '
			. '<input type="text" name="wl'. $prefix . $id . $fieldname . '" value="'. $value .'" /> '
			. $label_after . '</label>';
	}	
	
	function make_simple_radio($options, $id, $fieldname, $value, $label = null) {
		if ($options[$id][$fieldname] == $value) {
			$checked = 'checked="checked"'; $classname = 'wl-active';
		} else {
			$checked = ''; $classname = 'wl-inactive'; 
		}
		
		$id = '[' . $id . ']';
		$fieldname = '[' . $fieldname . ']';
		
		$out = '<label class="' . $classname . ' label-'. $value .'"><input type="radio" name="wl'. $id . $fieldname . '" value="'. $value .'" '. $checked .' /> ' 
			. $label . '</label>';
			
		return $out;
	}

	function make_simple_dropdown($options, $prefix, $id, $fieldname = null, $selection = array(), $label_before = null, $label_after = null) {
		$classname = $fieldname;
		
		if ($fieldname !== null) {
			$value = $options[$prefix][$id][$fieldname];
			$fieldname = '[' . $fieldname . ']';
		} else {
			$value = $options[$prefix][$id];
			$fieldname = '';
		}
		$prefix = '[' . $prefix . ']';
		$id = '[' . $id . ']';
	
		$list = '<label class="wl-'. $classname .'"><select name="wl'. $prefix . $id . $fieldname . '">'
			. $label_before . ' ';
		
		if (!empty($selection)) {
			foreach ($selection as $sid => $svalue) {
				if ($value == $sid) {
					$selected = 'selected="selected"';
				} else {
					$selected = '';
				}
				
				$list .= '<option value="' . $sid . '" ' . $selected . '>' . $svalue . '</option>';
			}
		} else {
			$list .= '<option value="error" selected="selected">'. __('No options given') .'</option>';
		}
		
		$list .= '</select> ' . $label_after . '</label>';
		
		return $list;
	}
}

?>