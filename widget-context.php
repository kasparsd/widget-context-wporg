<?php
/**
 * Plugin Name: Widget Context
 * Plugin URI: https://widgetcontext.com
 * Description: Show or hide widgets depending on the section of the site that is being viewed.
 * Version: 1.0.6
 * Author: Kaspars Dambis
 * Author URI: https://kaspars.net
 * Text Domain: widget-context
 */

include dirname( __FILE__ ) . '/class/class-widget-context.php';

// Go!
$plugin = widget_context::instance();
$plugin->init();
