/* global jQuery */
jQuery( document ).ready( function( $ ) {
	function showHideControls( widgetId, state ) {
		if ( ! state ) {
			state = $( '#widget-context-' + widgetId + ' .wc-field-select-condition select' ).val();
		}

		$( '#widget-context-' + widgetId ).toggleClass( 'context-global', ( state === 'show' || state === 'hide' ) );
	}

	$( '.widget-context-inside' ).each( function() {
		showHideControls( $( this ).data( 'widget-id' ) );
	} );

	$( '#widgets-right, #widgets-left, #customize-theme-controls, .edit-widgets-block-editor' ).on( 'change', '.wc-field-select-condition select', function( condition ) {
		showHideControls( $( this ).parent().data( 'widget-id' ), condition.target.value );
	} );

	$( document ).on( 'widget-updated widget-added', function( e, widget ) {
		showHideControls( widget.find( 'input[name="widget-id"]' ).val() );
	} );
} );
