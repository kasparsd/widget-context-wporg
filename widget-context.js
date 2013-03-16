jQuery(window).ready(function($) {

	$('.widget-context .context-group-wrap').addClass('collapsed');

	$('.widget-context .context-toggle').click(function() {
		$(this).siblings('.context-group-wrap').toggleClass('collapsed');

		return false;
	});

});