<?php

namespace Preseto\WidgetContextTest;

use WP_Mock;
use Preseto\WidgetContext\ContextOptions;
use Preseto\WidgetContext\ContextSettings;
use WidgetContext;

class WidgetContextTest extends WidgetContextTestCase {

	protected $plugin;

	/**
	 * Per-widget visibility option store.
	 *
	 * @var Preseto\WidgetContext\ContextOptions
	 */
	protected $context_options_store;

	/**
	 * Global plugin settings store.
	 *
	 * @var Preseto\WidgetContext\ContextSettings
	 */
	protected $context_settings_store;

	private $post_backup;

	public function setUp(): void {
		parent::setUp();

		$this->post_backup = $_POST;

		$this->context_options_store = new ContextOptions( 'widget_logic_options' );
		$this->context_settings_store = new ContextSettings( 'widget_context_settings' );

		$this->plugin = new \WidgetContext(
			null,
			$this->context_options_store,
			$this->context_settings_store
		);

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
				'return' => 'https://example.test/wp-admin/',
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

	public function testSaveWidgetContextSettingsCleansStaleOptionsWithoutAnyContextInput() {
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

		// No 'wl' input at all, for example a plain widget save without
		// any context fields posted.
		$_POST = array();

		WP_Mock::userFunction(
			'current_user_can',
			array(
				'args'   => array( 'edit_theme_options' ),
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
				'args'  => array(
					'widget_logic_options',
					array(
						'text-2' => array(
							'incexc' => array(
								'condition' => 'hide',
							),
						),
					),
				),
				'times' => 1,
			)
		);

		$this->plugin->save_widget_context_settings();

		$this->assertSame(
			array(
				'text-2' => array(
					'incexc' => array(
						'condition' => 'hide',
					),
				),
			),
			$this->plugin->get_context_options()
		);
	}

	public function testGetContextOptionsDelegatesToTheOptionStore() {
		$context_options = array(
			'text-2' => array(
				'incexc' => array(
					'condition' => 'hide',
				),
			),
		);

		$this->setContextOptions( $context_options );

		$this->assertSame( $context_options, $this->plugin->get_context_options() );
		$this->assertSame(
			$context_options['text-2'],
			$this->plugin->get_context_options( 'text-2' )
		);
		$this->assertNull( $this->plugin->get_context_options( 'unknown-5' ) );
	}

	public function testCheckWidgetVisibilityShowsWidgetsWithoutAnyContextSet() {
		$this->setContextOptions( array() );

		$this->assertTrue( $this->plugin->check_widget_visibility( 'text-2' ) );
	}

	public function testCheckWidgetVisibilityWithoutContextOptionsShowsWidget() {
		$this->setContextOptions(
			array(
				'text-2' => array(
					'incexc' => array(
						'condition' => '',
					),
				),
			)
		);

		$this->assertTrue( $this->plugin->check_widget_visibility( 'text-2' ) );
	}

	public function testCheckWidgetVisibilityForceRules() {
		$this->setContextOptions(
			array(
				'text-show' => array(
					'incexc' => array(
						'condition' => 'show',
					),
				),
				'text-hide' => array(
					'incexc' => array(
						'condition' => 'hide',
					),
				),
			)
		);

		$this->assertTrue( $this->plugin->check_widget_visibility( 'text-show' ) );
		$this->assertFalse( $this->plugin->check_widget_visibility( 'text-hide' ) );
	}

	public function testCheckWidgetVisibilityOnMatchWithoutMatchingContexts() {
		$this->setContextOptions(
			array(
				'text-2' => array(
					'incexc' => array(
						'condition' => 'selected',
					),
				),
				'text-3' => array(
					'incexc' => array(
						'condition' => 'notselected',
					),
				),
			)
		);

		// No contexts registered: no matches at all.
		$this->assertFalse( $this->plugin->check_widget_visibility( 'text-2' ), 'Selected without a match hides' );
		$this->assertTrue( $this->plugin->check_widget_visibility( 'text-3' ), 'Notselected without a match shows' );
	}

		/**
	 * Stub per-context check filters with match results.
	 *
	 * @param array $results Map of context ID => match result. Null values are not stubbed.
	 */
	private function stubContextFilters( $results ) {
		foreach ( $results as $context_id => $result ) {
			if ( null === $result ) {
				continue;
			}

			// WP_Mock::withAnyArgs() is static state that leaks across tests,
			// so always stub with the exact args these fixtures send.
			WP_Mock::onFilter( 'widget_context_check-' . $context_id )
				->with( null, array() )
				->reply( $result );
		}
	}

	public function testCheckWidgetVisibilityOnPositiveMatch() {
		$this->setContexts( array( 'url' => array() ) );
		$this->setContextOptions(
			array(
				'text-2' => array(
					'incexc' => array(
						'condition' => 'selected',
					),
				),
				'text-3' => array(
					'incexc' => array(
						'condition' => 'notselected',
					),
				),
			)
		);

		$this->stubContextFilters( array( 'url' => true ) );

		$this->assertTrue( $this->plugin->check_widget_visibility( 'text-2' ), 'Selected on match shows' );
		$this->assertFalse( $this->plugin->check_widget_visibility( 'text-3' ), 'Notselected on match hides' );
	}

	/**
	 * @dataProvider visibilityDecisionsProvider
	 *
	 * @param string $match_rule    Match rule.
	 * @param array  $filter_results Check filter stubs as context ID => match result.
	 * @param bool   $expected       Expected visibility.
	 */
	public function testVisibilityDecisions( $match_rule, $filter_results, $expected ) {
		$this->setContexts( array_fill_keys( array_keys( $filter_results ), array() ) );
		$this->stubContextFilters( $filter_results );

		$this->setContextOptions(
			array(
				'text-2' => array(
					'incexc' => array(
						'condition' => $match_rule,
					),
				),
			)
		);

		$this->assertSame( $expected, $this->plugin->check_widget_visibility( 'text-2' ) );
	}

	public function visibilityDecisionsProvider(): array {
		return array(
			'Force show' => array(
				'show',
				array(),
				true,
			),
			'Force hide' => array(
				'hide',
				array( 'url' => true ),
				false,
			),
			'Selected without matches hides' => array(
				'selected',
				array( 'url' => null, 'location' => false ),
				false,
			),
			'Selected with a positive match shows' => array(
				'selected',
				array( 'url' => true, 'location' => null ),
				true,
			),
			'Selected with only null matches hides' => array(
				'selected',
				array(),
				false,
			),
			'Notselected without matches shows' => array(
				'notselected',
				array(),
				true,
			),
			'Notselected with a positive match hides' => array(
				'notselected',
				array( 'url' => true ),
				false,
			),
			'Inverted rule overrides a positive match for selected' => array(
				'selected',
				array( 'url' => true, 'urls_invert' => false ),
				false,
			),
			'Inverted rule overrides a positive match for notselected' => array(
				'notselected',
				array( 'url' => true, 'urls_invert' => false ),
				true,
			),
		);
	}

	public function testCheckWidgetVisibilityInvertedRuleOverridesPositiveMatch() {
		$this->setContexts(
			array(
				'url'      => array(),
				'location' => array(),
			)
		);
		$this->stubContextFilters(
			array(
				'url'      => true,
				'location' => false,
			)
		);

		$this->setContextOptions(
			array(
				'text-2' => array(
					'incexc' => array(
						'condition' => 'selected',
					),
				),
				'text-3' => array(
					'incexc' => array(
						'condition' => 'notselected',
					),
				),
			)
		);

		// URL matches but the URL invert rule does not: no positive match.
		$this->stubContextFilters(
			array(
				'url'      => true,
				'location' => false,
			)
		);

		$this->assertFalse( $this->plugin->check_widget_visibility( 'text-2' ), 'Selected without a positive match hides' );
		$this->assertTrue( $this->plugin->check_widget_visibility( 'text-3' ), 'Notselected without a positive match shows' );
	}

	public function testContextMatchesReturnsMatchesForEveryRegisteredContext() {
		// Context gating resolves the global settings from the database.
		WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'return' => array(),
			)
		);

		$this->setContexts(
			array(
				'url'      => array(),
				'location' => array(),
			)
		);
		$this->setContextOptions(
			array(
				'text-2' => array(
					'url' => array(
						'paths' => 'news/*',
					),
				),
			)
		);

		// The URL check only replies for the widget's URL settings.
		WP_Mock::onFilter( 'widget_context_check-url' )
			->with( null, array( 'paths' => 'news/*' ) )
			->reply( true );

		WP_Mock::onFilter( 'widget_context_check-location' )
			->with( null, array() )
			->reply( false );

		$matches = $this->plugin->context_matches_for_widget_id( 'text-2' );

		$this->assertSame(
			array(
				'url'      => true,
				'location' => false,
			),
			$matches,
			'The filters only answer when the widget settings were passed through'
		);
	}

	public function testContextMatchesSkipDisabledContexts() {
		$this->setContexts(
			array(
				'url'      => array(),
				'location' => array(),
			)
		);
		$this->setContextOptions( array() );

		$this->context_settings_store->set(
			array(
				'contexts' => array(
					'location' => 0,
				),
			)
		);

		WP_Mock::onFilter( 'widget_context_check-url' )
			->with( null, array() )
			->reply( true );

		$this->assertSame(
			array( 'url' => true ),
			$this->plugin->context_matches_for_widget_id( 'text-2' ),
			'Disabled contexts are not checked and not part of the matches'
		);
	}

	public function testMaybeUnsetWidgetsByContextFiltersWidgetsOutOfSidebars() {
		WP_Mock::userFunction(
			'is_admin',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		$this->setContextOptions(
			array(
				'text-2' => array(
					'incexc' => array(
						'condition' => 'hide',
					),
				),
			)
		);

		$sidebars_widgets = array(
			'wp_inactive_widgets' => array( 'text-1' ),
			'sidebar-1'           => array(
				0 => 'text-2',
				1 => 'text-3',
			),
			'sidebar-2'           => array(),
		);

		$filtered = $this->plugin->maybe_unset_widgets_by_context( $sidebars_widgets );

		$this->assertSame(
			array(
				'wp_inactive_widgets' => array( 'text-1' ),
				'sidebar-1'           => array(
					1 => 'text-3',
				),
				'sidebar-2'           => array(),
			),
			$filtered,
			'Hiddens widgets are removed while keys are preserved'
		);

		$this->assertSame(
			$sidebars_widgets,
			$this->plugin->get_sidebars_widgets_copy(),
			'The original widget locations are kept in the copy'
		);
	}

	public function testMaybeUnsetWidgetsByContextIsCachedForSubsequentRuns() {
		WP_Mock::userFunction(
			'is_admin',
			array(
				'times'  => 2,
				'return' => false,
			)
		);

		$this->setContextOptions( array() );

		$sidebars_widgets = array(
			'sidebar-1' => array( 'text-2' ),
		);

		$first = $this->plugin->maybe_unset_widgets_by_context( $sidebars_widgets );
		$second = $this->plugin->maybe_unset_widgets_by_context(
			array(
				'sidebar-1' => array( 'text-other' ),
			)
		);

		$this->assertSame( $first, $second, 'Second run returns the cached result' );

		$this->assertSame(
			array( 'sidebar-1' => array( 'text-2' ) ),
			$second,
			'New input is ignored once checks have been done'
		);
	}

	public function testMaybeUnsetWidgetsByContextSkipsTheBackend() {
		WP_Mock::userFunction(
			'is_admin',
			array(
				'times'  => 1,
				'return' => true,
			)
		);

		$sidebars_widgets = array(
			'sidebar-1' => array( 'text-2' ),
		);

		$this->assertSame(
			$sidebars_widgets,
			$this->plugin->maybe_unset_widgets_by_context( $sidebars_widgets )
		);

		$this->assertNull( $this->plugin->get_sidebars_widgets_copy() );
	}

	private function setContextOptions( $context_options ) {
		$this->context_options_store->set( $context_options );
	}

	/**
	 * Seed the plugin context registry, normally filled by define_widget_contexts().
	 *
	 * @param array $contexts Map of context ID => context args.
	 */
	private function setContexts( $contexts ) {
		$property = new \ReflectionProperty( \WidgetContext::class, 'contexts' );

		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}

		$property->setValue( $this->plugin, $contexts );
	}
}
