<?php
/**
 * Include mocks if necessary. Note that phpunit includes the Composer
 * autoloader automatically.
 *
 * @todo Sort out the mocks situation. We don't want to load the whole WP.
 */

if ( ! function_exists( 'wp_parse_args' ) ) {
	require __DIR__ . '/wp-mocks.php';
}
