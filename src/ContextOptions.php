<?php
/**
 * Per-widget visibility option store.
 *
 * @package Preseto\WidgetContext
 */

namespace Preseto\WidgetContext;

/**
 * Lazy-loaded store for the widget context visibility options.
 */
class ContextOptions {

	/**
	 * Resolved visibility settings.
	 *
	 * @var array|null
	 */
	private ?array $options;

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

		unset( $this->options ); // Resolve on first use.
	}

	/**
	 * Get all widget context options, resolved on first use.
	 *
	 * @return array
	 */
	public function all(): array {
		if ( ! isset( $this->options ) ) {
			$this->options = apply_filters(
				'widget_context_options',
				(array) get_option( $this->option_name, array() )
			);
		}

		return $this->options;
	}

	/**
	 * Get the context options for a widget.
	 *
	 * @param string $widget_id Widget ID.
	 * @return array|null Null if the widget has no context options set.
	 */
	public function for_widget( string $widget_id ): ?array {
		$options = $this->all();

		if ( isset( $options[ $widget_id ] ) ) {
			return $options[ $widget_id ];
		}

		return null;
	}

	/**
	 * Persist the options and update the resolved instance.
	 *
	 * @param array $options Context options.
	 */
	public function save( array $options ): void {
		$this->options = $options; // Update the resolved instance.

		update_option( $this->option_name, $options );
	}

	/**
	 * Overwrite the in-memory instance without saving to the database.
	 *
	 * @param array $options Resolved context options.
	 */
	public function set( array $options ): void {
		$this->options = $options;
	}
}
