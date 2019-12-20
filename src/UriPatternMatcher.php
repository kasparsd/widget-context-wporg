<?php

namespace Preseto\WidgetContext;

/**
 * Match URI path patterns.
 */
class UriPatternMatcher {

	/**
	 * Delimiter used in the regex expressions.
	 */
	const DELIMITER = '/';

	/**
	 * Map quoted regex patterns to actual regex patterns.
	 *
	 * @var array
	 */
	const QUOTED_PATTERN_TO_REGEX = array(
		'\*' => '.*', // Enable the wildcard selectors.
	);

	/**
	 * Keep all the positive patterns.
	 *
	 * @var array
	 */
	private $positive_patterns = array();

	/**
	 * Keep all the inverted patterns.
	 *
	 * @var array
	 */
	private $inverted_patterns = array();

	/**
	 * Setup the pattern matcher.
	 *
	 * @param array $patterns List of regex-like match patterns.
	 */
	public function __construct( $rules ) {
		$this->positive_patterns = $this->quote_rules( $rules->positive() );
		$this->inverted_patterns = $this->quote_rules( $rules->inverted() );
	}

	/**
	 * Helper to sanitize and format rules for regex.
	 *
	 * @param array $rules List of regex-like rules.
	 *
	 * @return array
	 */
	protected function quote_rules( $rules ) {
		return array_map(
			function( $rule ) {
				// Escape regex chars before we enable back the wildcards.
				$rule = preg_quote( $rule, self::DELIMITER ); // Note that '/' is the delimiter we're using for the final expression below.

				// Enable the wildcard checks.
				return str_replace(
					array_keys( self::QUOTED_PATTERN_TO_REGEX ),
					self::QUOTED_PATTERN_TO_REGEX,
					$rule
				);
			},
			$rules
		);
	}

	/**
	 * Build a regex pattern for any set of rules.
	 *
	 * @param array $rules List of regular expression rules to match.
	 *
	 * @return string
	 */
	protected function rules_to_expression( $rules ) {
		$rules = array_map(
			function ( $rule ) {
				return sprintf( '(%s$)', $rule );
			},
			$rules
		);

		return sprintf(
			'%s^(%s)%si',
			self::DELIMITER,
			implode( '|', $rules ),
			self::DELIMITER
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
		$inverted_match = false;

		if ( ! empty( $this->inverted_patterns ) ) {
			$inverted_match = (bool) preg_match( $this->rules_to_expression( $this->inverted_patterns ), $path );
		}

		$positive_match = (bool) preg_match( $this->rules_to_expression( $this->positive_patterns ), $path );

		return ( $positive_match && ! $inverted_match );
	}

}
