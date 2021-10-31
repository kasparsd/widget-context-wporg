<?php

namespace Preseto\WidgetContextTest;

use WP_Mock;
use WidgetContext;

class WidgetContextTest extends WidgetContextTestCase {

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

	public function testLegacyInstance() {
		$widget_context = new WidgetContext( null );

		$this->assertSame(
			$widget_context,
			WidgetContext::instance(),
			'Legacy singleton instance is still available'
		);

		$this->assertInstanceOf(
			get_class( $widget_context ),
			WidgetContext::instance()
		);
	}

	public function testSettingsUrl() {
		WP_Mock::userFunction(
			'admin_url',
			array(
				'args' => array(
					WP_Mock\Functions::type( 'string' ),
				),
				'times' => 1,
			)
		);

		$this->plugin->plugin_settings_admin_url();
	}

	public function testRequestPathResolver() {
		$this->assertEquals(
			'path-to-a/url.html?true=2',
			$this->plugin->path_from_uri( 'https://example.com:8999/path-to-a/url.html?true=2' )
		);

		$this->assertEquals(
			'path-to-a/url.html',
			$this->plugin->path_from_uri( 'path-to-a/url.html' )
		);

		$this->assertEquals(
			'producte/cosmetica?pwb-brand-filter=clarins',
			$this->plugin->path_from_uri( 'producte/cosmetica/?pwb-brand-filter=clarins' ),
			'Normalize the path by removing the trailing slash'
		);
	}

}
