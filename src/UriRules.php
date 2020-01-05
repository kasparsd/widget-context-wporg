<?php

namespace Preseto\WidgetContext;

/**
 * URL path rules.
 */
class UriRules {

	/**
	 * Keep all the rules.
	 *
	 * @var array
	 */
	private $rules = array();

	/**
	 * Setup the pattern matcher.
	 *
	 * @param array $patterns List of regex-like match patterns.
	 */
	public function __construct( $rules ) {
		$this->rules = array_map( 'trim', $rules );
	}

	/**
	 * Return just the inverted rules that start with '!'.
	 *
	 * @return array
	 */
	public function inverted() {
		$rules = array_diff( $this->rules, $this->positive() );

		// Remove the inverted prefix.
		return array_map(
			function( $rule ) {
				return substr( $rule, 1 );
			},
			$rules
		);
	}

	/**
	 * Return just the positive rules that don't start with '!'.
	 *
	 * @return array
	 */
	public function positive() {
		return array_filter(
			$this->rules,
			function( $rule ) {
				return ( 0 !== strpos( $rule, '!' ) );
			}
		);
	}

	/**
	 * Check if any of the rules demand query string matching.
	 *
	 * @return boolean
	 */
	public function has_rules_with_query_strings() {
		foreach ( $this->rules as $rule ) {
			if ( false !== strpos( $rule, '?' ) ) {
				return true;
			}
		}

		return false;
	}
}
