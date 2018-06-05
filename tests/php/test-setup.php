<?php

class Tests_Setup extends WP_UnitTestCase {

	function test_plugin_loaded() {
		$this->assertTrue( class_exists( 'widget_context' ) );
	}

}
