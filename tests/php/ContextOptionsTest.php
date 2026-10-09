<?php

namespace Preseto\WidgetContextTest;

use WP_Mock;
use Preseto\WidgetContext\ContextOptions;

class ContextOptionsTest extends WidgetContextTestCase {

	const OPTION_NAME = 'widget_logic_options';

	public function testLazyResolutionOnlyLoadsOnce() {
		$context_options = array(
			'text-2' => array(
				'incexc' => array(
					'condition' => 'hide',
				),
			),
		);

		WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( self::OPTION_NAME, array() ),
				'times'  => 1,
				'return' => $context_options,
			)
		);

		$store = new ContextOptions( self::OPTION_NAME );

		$this->assertSame(
			$context_options,
			$store->all(),
			'First call resolves the options from the database'
		);

		$this->assertSame(
			$context_options,
			$store->all(),
			'Second call returns the cached options'
		);
	}

	public function testLazyResolutionIsNotTriggeredByWidgetLookups() {
		$context_options = array(
			'text-2' => array(
				'incexc' => array(
					'condition' => 'show',
				),
			),
		);

		WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( self::OPTION_NAME, array() ),
				'times'  => 1,
				'return' => $context_options,
			)
		);

		$store = new ContextOptions( self::OPTION_NAME );

		$this->assertSame( $context_options['text-2'], $store->for_widget( 'text-2' ) );

		// Resolving a second widget does not hit the database again.
		$this->assertNull( $store->for_widget( 'custom_html-4' ) );
	}

	public function testMissingOptionResolvesToAnEmptyArray() {
		WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( self::OPTION_NAME, array() ),
				'times'  => 1,
				'return' => null,
			)
		);

		$store = new ContextOptions( self::OPTION_NAME );

		$this->assertSame( array(), $store->all() );
	}

	public function testWidgetContextOptionsFilterCanReplaceTheResolvedOptions() {
		$raw_options = array(
			'text-2' => array(
				'incexc' => 1, // Legacy format.
			),
		);

		$fixed_options = array(
			'text-2' => array(
				'incexc' => array(
					'condition' => 1,
				),
			),
		);

		WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( self::OPTION_NAME, array() ),
				'times'  => 1,
				'return' => $raw_options,
			)
		);

		WP_Mock::onFilter( 'widget_context_options' )
			->with( $raw_options )
			->reply( $fixed_options );

		$store = new ContextOptions( self::OPTION_NAME );

		$this->assertSame( $fixed_options, $store->all(), 'The filter output is what gets stored' );
	}

	public function testUnknownWidgetLookupReturnsNull() {
		$store = new ContextOptions( self::OPTION_NAME );
		$store->set(
			array(
				'text-2' => array(
					'incexc' => array(
						'condition' => 'hide',
					),
				),
			)
		);

		$this->assertNull( $store->for_widget( 'archives-3' ) );

		$this->assertSame(
			array(
				'incexc' => array(
					'condition' => 'hide',
				),
			),
			$store->for_widget( 'text-2' )
		);
	}

	public function testAllReturnsEveryOptionWithoutSaving() {
		$context_options = array(
			'text-2' => array(
				'incexc' => array(
					'condition' => 'hide',
				),
			),
		);

		$store = new ContextOptions( self::OPTION_NAME );
		$store->set( $context_options );

		$this->assertSame( $context_options, $store->all() );
	}

	public function testSavePersistsOptionsAndUpdatesTheResolvedInstance() {
		$context_options = array(
			'text-2' => array(
				'incexc' => array(
					'condition' => 'selected',
				),
			),
		);

		WP_Mock::userFunction(
			'update_option',
			array(
				'args'  => array( self::OPTION_NAME, $context_options ),
				'times' => 1,
			)
		);

		$store = new ContextOptions( self::OPTION_NAME );
		$store->save( $context_options );

		// Building on the stored instance does not round-trip the database.
		$this->assertSame( $context_options, $store->all() );
		$this->assertNull( $store->for_widget( 'unknown-5' ) );
	}

	public function testSetOnlyUpdatesTheResolvedInstanceWithoutSaving() {
		$context_options = array(
			'text-2' => array(
				'incexc' => array(
					'condition' => 'show',
				),
			),
		);

		$store = new ContextOptions( self::OPTION_NAME );

		// @phpstan-ignore-next-line
		$store->set( $context_options );

		// No update_option mocked; if save() ran, the test would error.
		$this->assertSame( $context_options, $store->all() );
	}

	public function testSetOverridesLazyResolution() {
		$context_options = array(
			'text-2' => array(
				'incexc' => array(
					'condition' => 'hide',
				),
			),
		);

		WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 0,
				'return' => array(),
			)
		);

		$store = new ContextOptions( self::OPTION_NAME );
		$store->set( $context_options );

		$this->assertSame( $context_options, $store->all() );
	}
}
