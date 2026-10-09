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
	private array $rules = array();

	/**
	 * Setup the pattern matcher.
	 *
	 * @param array $patterns List of regex-like match patterns.
	 */
	public function __construct( array $rules ) {
		$this->rules = array_map( 'trim', $rules );
	}

	/**
	 * Return the URIs.
	 *
	 * @return array List of URIs.
	 */
	public function rules(): array {
		return $this->rules;
	}

	/**
	 * Check if any of the rules demand query string matching.
	 *
	 * @return boolean
	 */
	public function has_rules_with_query_strings(): bool {
		foreach ( $this->rules as $rule ) {
			// Assume that only query parameters can contain equal signs.
			if ( false !== strpos( $rule, '=' ) ) {
				return true;
			}
		}

		return false;
	}
}
