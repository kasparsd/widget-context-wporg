<?php
/**
 * Global plugin settings store.
 *
 * @package Preseto\WidgetContext
 */

namespace Preseto\WidgetContext;

/**
 * Store for the global widget context admin settings.
 */
class ContextSettings {

	/**
	 * Resolved global settings.
	 *
	 * @var array|null
	 */
	private ?array $settings;

	/**
	 * WP option name where the settings are stored.
	 *
	 * @var string
	 */
	private string $option_name;

	/**
	 * Setup the store.
	 *
	 * @param string $option_name WP option name.
	 */
	public function __construct( string $option_name ) {
		$this->option_name = $option_name;

		unset( $this->settings ); // Resolve on first use.
	}

	/**
	 * Get all settings, resolved on first use.
	 *
	 * @return array
	 */
	public function all(): array {
		if ( ! isset( $this->settings ) ) {
			$this->settings = \wp_parse_args(
				(array) get_option( $this->option_name, array() ),
				array(
					'contexts' => array(),
				)
			);
		}

		return $this->settings;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Value returned if the key is not set.
	 * @return mixed
	 */
	public function get( string $key, $fallback = null ) {
		return $this->all()[ $key ] ?? $fallback;
	}

	/**
	 * Overwrite the resolved instance without saving to the database.
	 *
	 * @param array $settings Resolved settings.
	 */
	public function set( array $settings ): void {
		$this->settings = \wp_parse_args(
			(array) $settings,
			array(
				'contexts' => array(),
			)
		);
	}

	/**
	 * Get the context enable states.
	 *
	 * @return array
	 */
	public function contexts(): array {
		return $this->all()['contexts'] ?? array();
	}

	/**
	 * If the context rule is enabled and should be checked.
	 *
	 * Contexts not present in the settings are enabled by default.
	 *
	 * @param string $context_id Context ID.
	 * @return bool
	 */
	public function is_context_enabled( string $context_id ): bool {
		$contexts = $this->contexts();

		return ! isset( $contexts[ $context_id ] )
			|| ! empty( $contexts[ $context_id ] );
	}

	/**
	 * Enable any contexts that don't have a state defined yet.
	 *
	 * @param array $context_ids Context IDs to default-enable.
	 */
	public function ensure_context_defaults( array $context_ids ): void {
		$contexts = $this->contexts();

		foreach ( $context_ids as $context_id ) {
			if ( ! isset( $contexts[ $context_id ] ) ) {
				$contexts[ $context_id ] = 1;
			}
		}

		$this->settings['contexts'] = $contexts;
	}

	/**
	 * If the legacy widgets interface is enabled.
	 *
	 * @return bool
	 */
	public function is_legacy_widgets_enabled(): bool {
		return ! empty( $this->get( 'enable-legacy-widgets' ) );
	}
}
