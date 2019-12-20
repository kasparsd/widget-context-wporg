<?php

namespace Preseto\WidgetContext;

/**
 * Match URI path patterns.
 */
class UriPatternMatcher {

	/**
	 * Map quoted regex patterns to actual regex patterns.
	 *
	 * @var array
	 */
	const QUOTED_PATTERN_TO_REGEX = array(
		'\*' => '.*', // Enable the wildcard selectors.
		'\!' => '?!', // Enable the inverse lookup.
	);

	/**
	 * Keep all the registered patterns.
	 *
	 * @var array
	 */
	private $patterns = array();

	/**
	 * Setup the pattern matcher.
	 *
	 * @param array $patterns List of regex-like match patterns.
	 */
	public function __construct( $patterns ) {
		$this->patterns = $this->build_patterns( $patterns );
	}

	/**
	 * Helper to sanitize and format patterns for regex.
	 *
	 * @param array $patterns List of regex-like patterns.
	 *
	 * @return array
	 */
	protected function build_patterns( $patterns ) {
		return array_map(
			function( $pattern ) {
				// Escape regex chars before we enable back the wildcards and inverse matches.
				$pattern_quoted = preg_quote( trim( $pattern ), '/' ); // Note that '/' is the delimiter we're using for the final expression below.

				// Enable wildcard and inverted checks.
				$pattern_quoted = str_replace(
					array_keys( self::QUOTED_PATTERN_TO_REGEX ),
					self::QUOTED_PATTERN_TO_REGEX,
					$pattern_quoted
				);

				/**
				 * The negative look-ahead for the inverted must be in its own group
				 * and it can't have the $ (end of a string) rule to report a match.
				 */
				if ( '?!' === substr( $pattern_quoted, 0, 2 ) ) {
					return sprintf( '(%s)', $pattern_quoted );
				}

				return sprintf( '%s$', $pattern_quoted );
			},
			$patterns
		);
	}

	/**
	 * Check if a URI path matches any of the patterns.
	 *
	 * @param  string $path URI path to check.
	 *
	 * @return bool
	 */
	public function match_path( $path ) {
		$regex = sprintf(
			'/^(%s)/i',
			implode( '|', $this->patterns )
		);

		return (bool) preg_match( $regex, $path );
	}

}
