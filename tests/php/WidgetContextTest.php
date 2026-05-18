<?php

namespace Preseto\WidgetContextTest;

use WP_Mock;
use WidgetContext;

class WidgetContextTest extends WidgetContextTestCase {

	protected $plugin;

	private $post_backup;

	public function setUp(): void {
		parent::setUp();

		$this->post_backup = $_POST;

		$this->plugin = new \WidgetContext( null );

		WP_Mock::userFunction( 'wp_parse_args' )
			->andReturnUsing(
				function ( $args, $defaults ) {
					return array_merge( $defaults, $args );
				}
			);

		WP_Mock::alias( 'wp_parse_url', 'parse_url' );
	}

	public function tearDown(): void {
		$_POST = $this->post_backup;

		parent::tearDown();
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

	public function testSaveWidgetContextSettingsUpdatesValidWidgetContextsAndCleansStaleOptions() {
		$this->setContextOptions(
			array(
				'text-2'     => array(
					'incexc' => array(
						'condition' => 'hide',
					),
				),
				'archives-3' => array(
					'incexc' => array(
						'condition' => 'show',
					),
				),
			)
		);

		$_POST = array(
			'wl'                      => array(
				'text-2'        => array(
					'incexc' => array(
						'condition' => 'selected',
					),
					'url'    => array(
						'paths' => 'news/*',
					),
				),
				'custom_html-4' => array(
					'incexc' => array(
						'condition' => 'notselected',
					),
				),
			),
			'widget-context--text-2' => 'valid-nonce',
		);

		$expected_options = array(
			'text-2' => array(
				'incexc' => array(
					'condition' => 'selected',
				),
				'url'    => array(
					'paths' => 'news/*',
				),
			),
		);

		WP_Mock::userFunction(
			'current_user_can',
			array(
				'args'   => array( 'edit_theme_options' ),
				'times'  => 1,
				'return' => true,
			)
		);

		WP_Mock::userFunction( 'wp_verify_nonce' )
			->andReturnUsing(
				function ( $nonce, $action ) {
					return 'valid-nonce' === $nonce && \WidgetContext::SAVE_NONCE_ACTION === $action;
				}
			);

		WP_Mock::userFunction(
			'wp_get_sidebars_widgets',
			array(
				'times'  => 1,
				'return' => array(
					'sidebar-1' => array( 'text-2', 'custom_html-4' ),
				),
			)
		);

		WP_Mock::userFunction(
			'update_option',
			array(
				'args'  => array( 'widget_logic_options', $expected_options ),
				'times' => 1,
			)
		);

		$this->plugin->save_widget_context_settings();

		$this->assertSame( $expected_options, $this->plugin->get_context_options() );
	}

	public function testSaveWidgetContextSettingsDeletesWidgetContext() {
		$this->setContextOptions(
			array(
				'text-2' => array(
					'incexc' => array(
						'condition' => 'selected',
					),
				),
			)
		);

		$_POST = array(
			'delete_widget'           => 1,
			'wl'                      => array(
				'text-2' => array(
					'incexc' => array(
						'condition' => 'selected',
					),
				),
			),
			'widget-context--text-2' => 'valid-nonce',
		);

		WP_Mock::userFunction(
			'current_user_can',
			array(
				'args'   => array( 'edit_theme_options' ),
				'times'  => 1,
				'return' => true,
			)
		);

		WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'args'   => array( 'valid-nonce', \WidgetContext::SAVE_NONCE_ACTION ),
				'times'  => 1,
				'return' => true,
			)
		);

		WP_Mock::userFunction(
			'wp_get_sidebars_widgets',
			array(
				'times'  => 1,
				'return' => array(
					'sidebar-1' => array( 'text-2' ),
				),
			)
		);

		WP_Mock::userFunction(
			'update_option',
			array(
				'args'  => array( 'widget_logic_options', array() ),
				'times' => 1,
			)
		);

		$this->plugin->save_widget_context_settings();

		$this->assertSame( array(), $this->plugin->get_context_options() );
	}

	private function setContextOptions( $context_options ) {
		$property = new \ReflectionProperty( \WidgetContext::class, 'context_options' );

		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}

		$property->setValue( $this->plugin, $context_options );
	}
}
