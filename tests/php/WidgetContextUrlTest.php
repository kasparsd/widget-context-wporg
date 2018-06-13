<?php

use PHPUnit\Framework\TestCase;

class WidgetContextTest extends TestCase {

	protected $plugin;

	protected $map_absolute = array(
		'http://example.com' => '',
		'http://example.com/' => '',
		'http://example.com/page' => 'page',
		'http://example.com/page/' => 'page',
		'http://example.com/?query=param' => '?query=param',
		'http://example.com:9000/page/subpage/?query=param' => 'page/subpage?query=param',
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

	public function __construct() {
		$this->plugin = new widget_context();
	}

	public function testUrlMatch() {
		$this->assertTrue(
			$this->plugin->match_path( 'page', 'page' ),
			'Simple direct'
		);

		$this->assertTrue(
			$this->plugin->match_path( 'page', 'page/' ),
			'Direct with trailing'
		);
	}

	public function testUrlWildcards() {
		$this->assertTrue(
			$this->plugin->match_path( 'page/subpage', 'page/*' ),
			'Wildcard for all sub-pages'
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

		$this->assertFalse(
			$this->plugin->match_path( 'campaigns?cc=automotive', 'campaigns/?has=query' ),
			'Ignore query string because no rules use it'
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

}
