<?php

namespace Preseto\WidgetContextTest;

use WP_Mock;
use Preseto\WidgetContext\ContextSettings;

class ContextSettingsTest extends WidgetContextTestCase {

	const OPTION_NAME = 'widget_context_settings';

	public function setUp(): void {
		parent::setUp();

		WP_Mock::userFunction( 'wp_parse_args' )
			->andReturnUsing(
				function ( $args, $defaults ) {
					return array_merge( $defaults, $args );
				}
			);
	}

	public function testLazyResolutionOnlyLoadsOnce() {
		$settings = array(
			'contexts' => array(
				'url' => 1,
			),
		);

		WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( self::OPTION_NAME, array() ),
				'times'  => 1,
				'return' => $settings,
			)
		);

		$store = new ContextSettings( self::OPTION_NAME );

		$this->assertSame( $settings, $store->all(), 'First call resolves the settings' );
		$this->assertSame( $settings, $store->all(), 'Second call returns the cached settings' );
	}

	public function testSettingsDefaultToAnEmptyContextsList() {
		WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( self::OPTION_NAME, array() ),
				'times'  => 1,
				'return' => array(),
			)
		);

		$store = new ContextSettings( self::OPTION_NAME );

		$this->assertSame( array( 'contexts' => array() ), $store->all() );
		$this->assertSame( array(), $store->contexts() );
	}

	public function testContextsNotPresentInSettingsAreEnabledByDefault() {
		WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => array(),
			)
		);

		$store = new ContextSettings( self::OPTION_NAME );

		$this->assertTrue( $store->is_context_enabled( 'url' ) );
		$this->assertTrue( $store->is_context_enabled( 'location' ) );
	}

	public function testExplicitlyManagedContextStates() {
		WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => array(
					'contexts' => array(
						'url'       => 0,
						'location'  => 1,
						'string'    => '1',
						'empty'     => '',
					),
				),
			)
		);

		$store = new ContextSettings( self::OPTION_NAME );

		$this->assertFalse( $store->is_context_enabled( 'url' ), 'Disabled via 0' );
		$this->assertTrue( $store->is_context_enabled( 'location' ), 'Enabled via 1' );
		$this->assertTrue( $store->is_context_enabled( 'string' ), 'Stringy "1" counts as enabled' );
		$this->assertFalse( $store->is_context_enabled( 'empty' ), 'Empty string counts as disabled' );
	}

	public function testGetSupportsFallbackValues() {
		WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => array(
					'enable-legacy-widgets' => 1,
				),
			)
		);

		$store = new ContextSettings( self::OPTION_NAME );

		$this->assertSame( 1, $store->get( 'enable-legacy-widgets' ) );
		$this->assertSame( 'fallback', $store->get( 'unknown-key', 'fallback' ) );
		$this->assertTrue( $store->is_legacy_widgets_enabled() );
	}

	public function testEnsureContextDefaultsDoesNotOverrideExplicitStates() {
		WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => array(
					'contexts' => array(
						'url' => 0,
					),
				),
			)
		);

		$store = new ContextSettings( self::OPTION_NAME );
		$store->ensure_context_defaults( array( 'url', 'location', 'word_count' ) );

		$this->assertSame(
			array(
				'contexts' => array(
					'url'        => 0, // Explicitly disabled, not defaulted.
					'location'   => 1,
					'word_count' => 1,
				),
			),
			$store->all()
		);
	}

	public function testSetResolvesDefaultsForMissingKeys() {
		$store = new ContextSettings( self::OPTION_NAME );
		$store->set(
			array(
				'enable-legacy-widgets' => 1,
			)
		);

		$this->assertSame(
			array(
				'contexts'              => array(),
				'enable-legacy-widgets' => 1,
			),
			$store->all()
		);
	}
}
