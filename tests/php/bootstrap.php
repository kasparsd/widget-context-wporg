<?php
/**
 * Bootstrap PHPUnit related dependencies.
 */

WP_Mock::bootstrap();

/**
 * Patch the following error in PHP 8:
 * Uncaught Error: Class "PHP_Token_NAME_QUALIFIED" not found
 * in vendor/phpunit/php-token-stream/src/Token/Stream.php:189
 */
if ( ! class_exists( 'PHP_Token_NAME_QUALIFIED' ) ) {
	class PHP_Token_NAME_QUALIFIED extends PHP_Token {}
}
