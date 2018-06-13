<?php

use PHPUnit\Framework\TestCase;

class WidgetContextTest extends TestCase {

	protected $plugin;

	public function __construct() {
		$this->plugin = new widget_context();
	}

	public function testUrlMatchesWithoutQuery() {
		//var_dump($this->plugin);
		$this->assertTrue(true);
	}

	public function testUrlMatchesWithQuery() {
		//var_dump($this->plugin);
		$this->assertTrue(true);
	}

}
