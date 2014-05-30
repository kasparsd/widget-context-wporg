jQuery(document).ready(function($) {

	function show_hide_controls( widget_id, value ) {

		if ( value == 'show' || value == 'hide' ) {
			$( '#widget-context-' + widget_id ).addClass('context-global')
		} else {
			$( '#widget-context-' + widget_id ).removeClass('context-global')
		}

	}

	$('.widget-context-inside').each(function() {

		show_hide_controls( $(this).data('widget-id'), $('.wc-field-select-condition select', this).val() );

	});

	$('#widgets-right, #widgets-left').on( 'change', '.wc-field-select-condition select', function(){
		
		show_hide_controls( $(this).parent().data('widget-id'), $(this).val() );

	});

});