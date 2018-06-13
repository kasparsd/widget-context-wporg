<?php
/**
 * Quick and dirty mocks for core WP helper methods.
 *
 */

function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, $args );
}

function wp_parse_url( $url, $components = -1 ) {
	return parse_url( $url, $components );
}
