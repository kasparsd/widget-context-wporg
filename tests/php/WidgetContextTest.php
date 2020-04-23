<?php

namespace Preseto\WidgetContextTest;

use WP_Mock;
use Mockery;
use WidgetContext;
use Preseto\WidgetContextTest\WidgetContextTestCase;

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
		$plugin = Mockery::mock( 'Preseto\WidgetContext\Plugin' );
		$widget_context = new WidgetContext( $plugin );

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

	public function testRequestPathResolver() {
		$this->assertEquals(
			'path-to-a/url.html?true=2',
			$this->plugin->path_from_uri( 'https://example.com:8999/path-to-a/url.html?true=2' )
		);

		$this->assertEquals(
			'path-to-a/url.html',
			$this->plugin->path_from_uri( 'path-to-a/url.html' )
		);
	}

}
