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

		$map = array(
				'show' => __( 'Show widget everywhere', 'widget-context' ),
				'selected' => __( 'Show widget on selected', 'widget-context' ),
				'notselected' => __( 'Hide widget on selected', 'widget-context' ),
				'hide' => __( 'Hide widget everywhere', 'widget-context' )
			);

		foreach ( $sidebars_widgets as $widget_area => $widgets ) {

			if ( 'wp_inactive_widgets' == $widget_area )
				continue;

			foreach ( $widgets as $widget_i => $widget_id ) {

				$a = array(); // sorry
				$context_options = $wc->get_context_options( $widget_id );

				foreach ( $wc->get_contexts() as $context_id => $context ) {

					if ( isset( $context_options[ $context_id ] ) )
						$widget_context_args = $context_options[ $context_id ];
					else
						$widget_context_args = array();

					if ( $context_id == 'incexc' ) {
						if ( isset( $widget_context_args['condition'] ) && isset( $map[ $widget_context_args['condition'] ] ) )
							$set = $map[ $widget_context_args['condition'] ];
						else
							$set = __( 'Default', 'widget-context' );
					}

					$check = apply_filters(
							'widget_context_check-' . $context_id,
							null,
							$widget_context_args
						);

					$a[] = sprintf(
							'<tr>
								<th><strong>%s</strong></th>
								<td>%s</td>
								<td><pre>%s</pre></td>
							</tr>',
							$context_id,
							$check ? __( 'Yes', 'widget-context' ) : __( 'No', 'widget-context' ),
							esc_html( print_r( $widget_context_args, true ) )
						);

				}

				if ( $wc->check_widget_visibility( $widget_id ) )
					$status = sprintf( __( 'Showing <strong>%s</strong> in "%s"' ), esc_html( $widget_id ), esc_html( $widget_area ) );
				else
					$status = sprintf( __( 'Hiding <strong>%s</strong> in "%s"' ), esc_html( $widget_id ), esc_html( $widget_area ) );

				$out[] = sprintf(
						'<h3><a href="#widget-%d" class="toggle">%s</a> <strong>%s</strong> &mdash; %s</h3>
						<table width="100%%" id="widget-%d" style="display:none;">
							<tr>
								<th>Context</th>
								<th>Match</th>
								<th>Settings</th>
							</tr>
							%s
						</table>',
						$widget_i,
						__( 'Toggle', 'widget-context' ),
						esc_html( $set ),
						$status,
						$widget_i,
						implode( '', $a )
					);

			}
		}

		printf(
			'%s
			<h3>Registered Contexts:</h3>
			<pre>%s</pre>',
			implode( '', $out ),
			esc_html( print_r( $wc->get_contexts(), true ) )
		);

	}

}
