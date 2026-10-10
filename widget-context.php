<?php
/**
 * Plugin Name: Widget Context
 * Plugin URI: https://widgetcontext.com
 * Description: Show or hide widgets depending on the section of the site that is being viewed. Configure the widget visibility rules under the individual widget settings.
 * Version: 1.5.1-rc.1
 * Author: Kaspars Dambis
 * Author URI: https://widgetcontext.com
 * Text Domain: widget-context
 */

if ( ! function_exists( 'add_action' ) ) {
	return; // Ensure WP is loading the plugin.
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) && ! class_exists( Preseto\WidgetContext\Plugin::class ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

$plugin = new Preseto\WidgetContext\Plugin( __FILE__ );
$widget_context = new WidgetContext( $plugin );

$widget_context->register_module( new WidgetContextCustomCptTax( $widget_context ) );
$widget_context->register_module( new WidgetContextWordCount( $widget_context ) );

add_action( 'plugins_loaded', array( $widget_context, 'init' ) );
