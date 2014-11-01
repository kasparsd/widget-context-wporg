<?php

class Debug_Widget_Context extends Debug_Bar_Panel {

	function init() {
		$this->title( __( 'Widget Context', 'widget-context' ) );
	}

	function prerender() {
		$this->set_visible( ! is_admin() );
	}

	function render() {
		
		$wc = widget_context::instance();

		$sidebars_widgets = $wc->get_sidebars_widgets_copy();

		foreach ( $sidebars_widgets as $widget_area => $widgets ) {

			if ( 'wp_inactive_widgets' == $widget_area )
				continue;

			foreach ( $widgets as $widget_i => $widget_id ) {

				$a = array();

				foreach ( $wc->contexts as $context_id => $context ) {
					
					if ( isset( $wc->context_options[ $widget_id ][ $context_id ] ) )
						$widget_context_args = $wc->context_options[ $widget_id ][ $context_id ];
					else
						$widget_context_args = array();
					
					$check = apply_filters( 
							'widget_context_check-' . $context_id, 
							null, 
							$widget_context_args
						);

					$a[] = sprintf( 
							'<pre>%s: %s
								%s</pre>', 
							$context_id, 
							$check ? __( 'Yes', 'widget-context' ) : __( 'No', 'widget-context' ),
							esc_html( print_r( $widget_context_args, true ) )
						);

				}

				if ( $wc->check_widget_visibility( $widget_id ) )
					$status = sprintf( __( 'Showing "%s" at "%s"' ), $widget_id, $widget_area );
				else
					$status = sprintf( __( 'Hiding "%s" at "%s"' ), $widget_id, $widget_area );

				$out[] = sprintf( 
						'<h4>%s</h4>
						<ul>%s</ul>', 
						esc_html( $status ),
						implode( '', $a )
					);

			}
		}

		echo implode( '', $out );


		printf( 
			'<h3>Widget Contexts for the Current View:</h3>
			<pre>%s</pre>
			<h3>Registered Contexts:</h3>
			<pre>%s</pre>',
			'a',
			esc_html( print_r( $wc->contexts, true ) )
		);

	}

}

