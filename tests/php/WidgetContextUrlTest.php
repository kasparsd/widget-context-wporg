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

}
