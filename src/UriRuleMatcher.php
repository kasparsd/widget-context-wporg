<?php

namespace Preseto\WidgetContext;

/**
 * Match URI path regex-like rules.
 */
class UriRuleMatcher {

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
	 * Rules to use for lookup.
	 *
	 * @var \Preseto\WidgetContext\UriRules
	 */
	private $rules;

	/**
	 * Setup the pattern matcher.
	 *
	 * @param \Preseto\WidgetContext\UriRules $rules Instance of match rules.
	 */
	public function __construct( $rules ) {
		$this->rules = $rules;
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
			$this->quote_rules( $rules )
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
	 * @return bool|null
	 */
	public function match_path( $path ) {
		$match_positive = null;
		$match_inverted = null;

		$rules_positive = $this->rules->positive();
		$rules_inverted = $this->rules->inverted();

		if ( ! empty( $rules_positive ) ) {
			$match_positive = (bool) preg_match( $this->rules_to_expression( $rules_positive ), $path );
		}

		if ( ! empty( $rules_inverted ) ) {
			$match_inverted = (bool) preg_match( $this->rules_to_expression( $rules_inverted ), $path );
		}

		if ( null !== $match_positive && null !== $match_inverted ) {
			return ( $match_positive && ! $match_inverted );
		} elseif ( null !== $match_positive ) {
			return $match_positive;
		} elseif ( null !== $match_inverted ) {
			return ! $match_inverted;
		}

		return null;
	}
}
