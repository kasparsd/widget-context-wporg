jQuery(window).ready(function($) {

	$( '.widget-context' ).on( 'change', '.context-type select', function() {

		var item_id = $(this).data('id');
		var widget_id = $(this).data('widget-id');
		var context_type = $(this).val();

		if ( '' == context_type )
			$( '#' + widget_id + '-' + item_id + ' .context-item' ).hide();
		else
			$( '#' + widget_id + '-' + item_id + ' .context-item-' + context_type ).show().siblings('.context-item').hide();

	}).on( 'click', '.context-actions input', function(e) {

		var widget_id = $(this).data('widget-id');
		var next_no = $( '#widget-context-' + widget_id + ' .context-selected-items' ).size() - 1;
		var placeholder = $('<div>').append( $( '#widget-context-' + widget_id + ' .context-item-placeholder' ).clone() );

		placeholder.children().removeClass('context-item-placeholder');

		$( '#widget-context-' + widget_id + ' .context-selected' ).append( placeholder.html().replace( /__i__/g, next_no ) );

	}).on('click', '.context-item-delete', function(e) {

		e.preventDefault();

		var item_id = $(this).data('id');
		var widget_id = $(this).data('widget-id');

		$( '#' + widget_id + '-' + item_id ).remove();

	});

});