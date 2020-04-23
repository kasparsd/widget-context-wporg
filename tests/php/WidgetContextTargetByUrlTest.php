<?php

namespace Preseto\WidgetContextTest;

use WP_Mock;
use Preseto\WidgetContextTest\WidgetContextTestCase;

class WidgetContextTargetByUrlTest extends WidgetContextTestCase {

	protected $plugin;

	protected $map_absolute = array(
		'http://example.com' => '',
		'http://example.com/' => '',
		'http://example.com/page' => 'page',
		'http://example.com/page/' => 'page',
		'http://example.com/?query=param' => '?query=param',
		'http://example.com:9000/page/subpage/?query=param' => 'page/subpage?query=param',
		'http://example.com:9000/page/?another=param#hashtoo' => 'page?another=param',
	);

	protected $map_relative = array(
		'' => '',
		'/' => '',
		'/page' => 'page',
		'page/' => 'page',
		'/page/' => 'page',
		'/page/sub-page/' => 'page/sub-page',
		'/page?query=string' => 'page?query=string',
		'/page/?query=string' => 'page?query=string',
	);

	public function setUp() {
		parent::setUp();

		$this->plugin = new \WidgetContext( null );

		WP_Mock::userFunction( 'wp_parse_args' )
			->andReturnUsing(
				function( $args, $defaults ) {
					return array_merge( $defaults, $args );
				}
			);

		WP_Mock::alias( 'wp_parse_url', 'parse_url' );
	}

	public function testUrlMatch() {
		$this->assertTrue(
			$this->plugin->match_path( 'page/subpage', 'page/subpage' ),
			'Exact path'
		);

		$this->assertTrue(
			$this->plugin->match_path( 'page', 'page/' ),
			'Exact with trailing'
		);

		$this->assertFalse(
			$this->plugin->match_path( 'page-that-start-with-page', 'page' ),
			'Ignores prefixes without a wildcard'
		);
	}

	public function testUrlWildcards() {
		$this->assertTrue(
			$this->plugin->match_path( 'page/subpage', 'page/*' ),
			'Wildcard for all sub-pages'
		);

		$this->assertTrue(
			$this->plugin->match_path( 'page', 'page*' ),
			'Wildcard for all slugs with a pattern'
		);

		$this->assertTrue(
			$this->plugin->match_path( 'parent-page/page-slug', '*/page-slug' ),
			'Wildcard for any parent'
		);

		$this->assertFalse(
			$this->plugin->match_path( 'page', 'page/*' ),
			'Wildcard for children only'
		);

		$this->assertFalse(
			$this->plugin->match_path( 'another-page', 'page/*' ),
			'Wildcard for a totally different page'
		);
	}

	public function testUrlQueryStrings() {
		$this->assertTrue(
			$this->plugin->match_path( 'page/subpage/?query=string', 'page/*' ),
			'Wildcard for subpage and a query string'
		);

		$this->assertTrue(
			$this->plugin->match_path( 'campaigns/?cc=automotive', 'campaigns/*' ),
			'Wildcard for everything'
		);

		$this->assertTrue(
			$this->plugin->match_path( 'campaigns?cc=automotive', 'campaigns/?cc=*' ),
			'Wildcard for a specific query variable'
		);

		$this->assertFalse(
			$this->plugin->match_path( 'campaigns-another-page', 'campaigns/*' ),
			'Path not matching the wildcard'
		);
	}

	public function testUrlSpecial() {
		$this->assertTrue(
			$this->plugin->match_path( 'campaigns?cc=automotive', 'campaigns/' ),
			'Ignore query string because no rules use it'
		);

		$this->assertFalse(
			$this->plugin->match_path( 'campaigns?cc=automotive', 'campaigns/?some=other' ),
			'Respect query string if differen used'
		);
	}

	public function testPathResolverAbsolute() {
		foreach ( $this->map_absolute as $request => $path ) {
			$this->assertEquals( $this->plugin->get_request_path( $request ), $path );
		}
	}

	public function testPathResolverRelative() {
		foreach ( $this->map_relative as $request => $path ) {
			$this->assertEquals( $this->plugin->get_request_path( $request ), $path );
		}
	}

	public function testInversePattern() {
		$this->assertTrue(
			$this->plugin->match_path( 'another/page', implode( "\n", array( 'another/page', '!another/page/child' ) ) ),
			'Ignore a related inverse when a positive match is found'
		);

		$this->assertTrue(
			$this->plugin->match_path( 'child/page', implode( "\n", array( 'child/*', '!child/page/excluded' ) ) ),
			'Wildcard is still matched even if inverse does not match'
		);

		$this->assertFalse(
			$this->plugin->match_path( 'positive/page', implode( "\n", array( '!inverted/page', 'positive' ) ) ),
			'Inverse can only override a positive match'
		);

		$this->assertFalse(
			$this->plugin->match_path( 'this/page/child', implode( "\n", array( 'this/page/*', '!this/page/child' ) ) ),
			'Inverse can override a direct match'
		);

		$this->assertTrue(
			$this->plugin->match_path( 'page/one', implode( "\n", array( 'page/*', '!page/two' ) ) ),
			'Wildcard is honored even with an unrelated inverted rule'
		);

		$this->assertTrue(
			$this->plugin->match_path( 'random-path', implode( "\n", array( '!this/*' ) ) ),
			'Standalone inverted lookups are supported'
		);
	}

}
