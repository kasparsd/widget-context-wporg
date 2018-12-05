<?php
/**
 * Plugin Name: Widget Context
 * Plugin URI: https://widgetcontext.com
 * Description: Show or hide widgets depending on the section of the site that is being viewed.
 * Version: 1.1.1
 * Author: Preseto
 * Author URI: https://preseto.com
 * Text Domain: widget-context
 */

// TODO Switch to proper autoloading.
require_once dirname( __FILE__ ) . '/src/WidgetContext.php';
require_once dirname( __FILE__ ) . '/src/modules/custom-post-types-taxonomies/module.php';
require_once dirname( __FILE__ ) . '/src/modules/word-count/module.php';

$plugin = new WidgetContext( dirname( __FILE__ ) );

$plugin->register_module( new WidgetContextCustomCptTax( $plugin ) );
$plugin->register_module( new WidgetContextWordCount( $plugin ) );

$plugin->init();
