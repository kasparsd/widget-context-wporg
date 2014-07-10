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

	$('#widgets-right, #widgets-left').on( 'change', '.wc-field-select-condition select', function(){
		
		show_hide_controls( $(this).parent().data('widget-id') );

	});

	$(document).ajaxSuccess(function(e, xhr, settings) {
		var widget_id = get_query_arg_val( settings.data, 'widget-id' );
		if ( widget_id ) {
			show_hide_controls( widget_id );
		}
	});

	var get_query_arg_val = function( query, key ) {
		var vars = query.split('&');

		for ( var i=0; i<vars.length; i++ ) {
			pair = vars[i].split('=');
			if ( pair[0] == key ) {
				return pair[1];
			}
		}
		
		return false;
	};

});