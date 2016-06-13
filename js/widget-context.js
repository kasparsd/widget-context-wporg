jQuery(document).ready(function($) {

	function show_hide_controls( widget_id ) {

		var condition = $( '#widget-context-' + widget_id + ' .wc-field-select-condition select' ).val();

		$( '#widget-context-' + widget_id ).toggleClass( 'context-global', ( condition == 'show' || condition == 'hide' ) );

	}

	$('.widget-context-inside').each(function() {

		show_hide_controls( $(this).data('widget-id') );

	});

	$('#widgets-right, #widgets-left, #customize-theme-controls').on( 'change', '.wc-field-select-condition select', function(){

		show_hide_controls( $(this).parent().data('widget-id') );

	});

	$( document ).on( 'widget-updated widget-added', function( e, widget ) {

		show_hide_controls( widget.find('input[name="widget-id"]').val() );

	});

});
