jQuery(document).ready(function($) {

	function show_hide_controls( widget_id ) {

		var value = $( '#widget-context-' + widget_id + ' .wc-field-select-condition select' ).val();

		if ( value == 'show' || value == 'hide' ) {
			$( '#widget-context-' + widget_id ).addClass('context-global');
		} else {
			$( '#widget-context-' + widget_id ).removeClass('context-global');
		}

	}

	$('.widget-context-inside').each(function() {

		show_hide_controls( $(this).data('widget-id') );

	});

	$('#widgets-right, #widgets-left, #customize-theme-controls').on( 'change', '.wc-field-select-condition select', function(){
		
		show_hide_controls( $(this).parent().data('widget-id') );

	});

	$(document).bind( 'widget-updated', function( e, widget ) {
		
		show_hide_controls( widget.find('input[name="widget-id"]').val() );

	});


});