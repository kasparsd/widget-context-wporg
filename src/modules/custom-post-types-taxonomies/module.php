<?php


class WidgetContextCustomCptTax {

	private static $instance;
	private $wc;
	public $post_types;
	public $taxonomies;

	public function __construct( $plugin ) {
		$this->wc = $plugin;
	}

	public function init() {
		add_filter( 'widget_contexts', array( $this, 'add_context' ) );

		add_filter( 'widget_context_control-custom_post_types_taxonomies', array( $this, 'context_controls' ), 10, 2 );

		add_filter( 'widget_context_check-custom_post_types_taxonomies', array( $this, 'context_check' ), 10, 2 );
	}

	function set_objects() {
		if ( is_array( $this->post_types ) ) {
			return;
		}

		$this->post_types = get_post_types(
			array(
				'public' => true,
				'_builtin' => false,
				'publicly_queryable' => true,
			),
			'objects'
		);

		$this->taxonomies = get_taxonomies(
			array(
				'public' => true,
				'_builtin' => false,
			),
			'objects'
		);
	}


	function add_context( $contexts ) {
		$contexts['custom_post_types_taxonomies'] = array(
			'label' => __( 'Custom Post Types and Taxonomies', 'widget-context' ),
			'description' => __( 'Match posts and archives of custom post types and taxonomies.', 'widget-context' ),
			'weight' => 10,
		);

		return $contexts;
	}


	function context_check( $check, $settings ) {
		if ( empty( $settings ) ) {
			return $check;
		}

		$status = array();

		if ( ! is_array( $this->post_types ) ) {
			$this->set_objects();
		}

		foreach ( $this->post_types as $post_type => $post_type_settings ) {

			if ( isset( $settings[ 'is_singular-' . $post_type ] ) && $settings[ 'is_singular-' . $post_type ] ) {
				$status[ 'is_singular-' . $post_type ] = is_singular( $post_type );
			}

			if ( isset( $settings[ 'is_archive-' . $post_type ] ) && $settings[ 'is_archive-' . $post_type ] ) {
				$status[ 'is_archive-' . $post_type ] = is_post_type_archive( $post_type );
			}
		}

		foreach ( $this->taxonomies as $taxonomy => $tax_settings ) {

			if ( isset( $settings[ 'is_tax-' . $taxonomy ] ) && $settings[ 'is_tax-' . $taxonomy ] ) {
				$status[ 'is_tax-' . $taxonomy ] = is_tax( $taxonomy );
			}
		}

		$matched = array_intersect_assoc( $settings, $status );

		if ( ! empty( $matched ) ) {
			return true;
		}

		return $check;
	}


	function context_controls( $control_args ) {
		$options = array();
		$out = array();

		if ( ! is_array( $this->post_types ) ) {
			$this->set_objects();
		}

		foreach ( $this->post_types as $post_type => $post_type_settings ) {
			$options[ 'is_singular-' . $post_type ] = sprintf(
				/* translators: %s is the post type label. */
				__( 'All "%s" posts', 'widget-context' ),
				$post_type_settings->label
			);

			if ( $post_type_settings->has_archive ) {
				$options[ 'is_archive-' . $post_type ] = sprintf(
					/* translators: %s is the post type label. */
					__( 'Archive of "%s" posts', 'widget-context' ),
					$post_type_settings->label
				);
			}
		}

		foreach ( $this->taxonomies as $taxonomy => $tax_settings ) {
			$options[ 'is_tax-' . $taxonomy ] = sprintf(
				/* translators: %s is the taxonomy label. */
				__( 'All "%s" taxonomy archives', 'widget-context' ),
				$tax_settings->label
			);
		}

		foreach ( $options as $option => $label ) {
			$out[] = $this->wc->make_simple_checkbox( $control_args, $option, $label );
		}

		if ( ! empty( $out ) ) {
			return implode( '', $out );
		}

		return sprintf( '%s', esc_html__( 'None.', 'widget-context' ) );
	}

}
