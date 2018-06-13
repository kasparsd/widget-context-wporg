<?php
/**
 * Include the autoloader and register a few mocks.
 *
 * @todo Sort out the mocks situation. We don't want to load the whole WP.
 */

require_once( __DIR__ . '/../../vendor/autoload.php' );

if ( ! function_exists( 'wp_parse_args' ) ) {
	require __DIR__ . '/wp-mocks.php';
}
