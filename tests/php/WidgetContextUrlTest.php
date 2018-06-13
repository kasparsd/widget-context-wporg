<?php

use PHPUnit\Framework\TestCase;

class WidgetContextTest extends TestCase {

	protected $plugin;

	protected $map_absolute = array(
		'http://example.com' => '',
		'http://example.com/' => '',
		'http://example.com/page' => 'page',
		'http://example.com/page/' => 'page/',
		'http://example.com/?query=param' => '?query=param',
		'http://example.com:9000/page/subpage/?query=param' => 'page/subpage/?query=param',
	);

	protected $map_relative = array(
		'' => '',
		'/' => '',
		'/page' => 'page',
		'/page/' => 'page/',
		'/page/sub-page/' => 'page/sub-page/',
		'/page?query=string' => 'page?query=string',
		'/page/?query=string' => 'page/?query=string',
	);

	public function __construct() {
		$this->plugin = new widget_context();
	}

	public function testUrlWildcards() {
		$this->assertTrue( $this->plugin->match_path( 'page/subpage', 'page/*' ) );
		$this->assertFalse( $this->plugin->match_path( 'another-page', 'page/*' ) );
	}

	public function testUrlQueryStrings() {
		$this->assertTrue( $this->plugin->match_path( 'page/subpage?query=string', 'page/*' ) );
		$this->assertTrue( $this->plugin->match_path( 'campaigns/?cc=automotive', 'campaigns/*' ) );
		$this->assertTrue( $this->plugin->match_path( 'campaigns/?cc=automotive', 'campaigns/?cc=*' ) );

		$this->assertFalse( $this->plugin->match_path( 'campaigns/?cc=automotive', 'campaigns/' ) );
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
