<?php
/**
 * Plugin Name: Widget Context
 * Plugin URI: https://widgetcontext.com
 * Description: Show or hide widgets depending on the section of the site that is being viewed.
 * Version: 1.2.0
 * Author: Kaspars Dambis
 * Author URI: https://widgetcontext.com
 * Text Domain: widget-context
 */

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

$plugin = new WidgetContext( __FILE__ );

$plugin->register_module( new WidgetContextCustomCptTax( $plugin ) );
$plugin->register_module( new WidgetContextWordCount( $plugin ) );

$plugin->init();
