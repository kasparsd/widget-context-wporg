<?php
/**
 * Plugin Name: Widget Context
 * Plugin URI: https://widgetcontext.com
 * Description: Show or hide widgets depending on the section of the site that is being viewed.
 * Version: 1.1.0
 * Author: Kaspars Dambis
 * Author URI: https://kaspars.net
 * Text Domain: widget-context
 */

require_once dirname( __FILE__ ) . '/class/class-widget-context.php';

// Go!
$plugin = widget_context::instance();
$plugin->set_path( dirname( __FILE__ ) );
$plugin->init();
