<?php

namespace Preseto\WidgetContextTest;

use Mockery;
use WidgetContext;
use Preseto\WidgetContextTest\WidgetContextTestCase;

class WidgetContextTest extends WidgetContextTestCase {

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

}
