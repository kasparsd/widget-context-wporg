/* global jQuery, document */
jQuery( document ).ready( function( $ ) {
	function showHideControls( widgetId ) {
		var condition = $( '#widget-context-' + widgetId + ' .wc-field-select-condition select' ).val();

		$( '#widget-context-' + widgetId ).toggleClass( 'context-global', ( condition === 'show' || condition === 'hide' ) );
	}

	$( '.widget-context-inside' ).each( function() {
		showHideControls( $( this ).data( 'widget-id' ) );
	} );

	$( '#widgets-right, #widgets-left, #customize-theme-controls' ).on( 'change', '.wc-field-select-condition select', function() {
		showHideControls( $( this ).parent().data( 'widget-id' ) );
	} );

	$( document ).on( 'widget-updated widget-added', function( e, widget ) {
		showHideControls( widget.find( 'input[name="widget-id"]' ).val() );
	} );
} );
