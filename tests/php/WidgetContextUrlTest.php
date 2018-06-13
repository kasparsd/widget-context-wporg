<?php

use PHPUnit\Framework\TestCase;

class WidgetContextTest extends TestCase {

	protected $plugin;

	public function __construct() {
		$this->plugin = new widget_context();
	}

	public function testUrlWildcards() {
		$this->assertTrue( $this->plugin->match_path( 'page/subpage', 'page/*' ) );
		$this->assertFalse( $this->plugin->match_path( 'another-page', 'page/*' ) );
	}

	public function testUrlQueryStrings() {
		$this->assertTrue( $this->plugin->match_path( 'page/subpage?query=string', 'page/*' ) );
		$this->assertFalse( $this->plugin->match_path( 'campaigns/?cc=automotive', 'campaigns/' ) );
		$this->assertTrue( $this->plugin->match_path( 'campaigns/?cc=automotive', 'campaigns/*' ) );
		$this->assertTrue( $this->plugin->match_path( 'campaigns/?cc=automotive', 'campaigns/?cc=*' ) );
	}

	public function testPathResolverAbsolute() {
		$this->assertEquals( $this->plugin->get_request_path( 'http://example.com' ), '' );
		$this->assertEquals( $this->plugin->get_request_path( 'http://example.com/' ), '' );
		$this->assertEquals( $this->plugin->get_request_path( 'http://example.com/page' ), 'page' );
		$this->assertEquals( $this->plugin->get_request_path( 'http://example.com/page/' ), 'page/' );
		$this->assertEquals( $this->plugin->get_request_path( 'http://example.com/?query=param' ), '?query=param' );
		$this->assertEquals( $this->plugin->get_request_path( 'http://example.com:9000/page/subpage/?query=param' ), 'page/subpage/?query=param' );
	}

	public function testPathResolverRelative() {
		$this->assertEquals( $this->plugin->get_request_path( '' ), '' );
		$this->assertEquals( $this->plugin->get_request_path( '/' ), '' );
		$this->assertEquals( $this->plugin->get_request_path( '/page' ), 'page' );
		$this->assertEquals( $this->plugin->get_request_path( '/page/' ), 'page/' );
		$this->assertEquals( $this->plugin->get_request_path( '/page/sub-page/' ), 'page/sub-page/' );
		$this->assertEquals( $this->plugin->get_request_path( '/page?query=string' ), 'page?query=string' );
		$this->assertEquals( $this->plugin->get_request_path( '/page/?query=string' ), 'page/?query=string' );
	}

}
