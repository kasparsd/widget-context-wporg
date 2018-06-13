<?php
/**
 * Include the autoloader and register a few mocks.
 *
 * @todo Sort out the mocks situation. We don't want to load the whole WP.
 */

require_once( __DIR__ . '/../../vendor/autoload.php' );

function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, $args );
}

function wp_parse_url( $url, $components = -1 ) {
	return parse_url( $url, $components );
}
