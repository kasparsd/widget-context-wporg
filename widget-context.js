jQuery(window).ready(function($) {

	$('.widget-context-inside, .toggle-contexts .collapse').hide();

	$('.widget-context .toggle-contexts').click(function() {
		$(this).siblings('.widget-context-inside').slideToggle('fast');
		$('span', this).toggle();

		return false;
	});

});