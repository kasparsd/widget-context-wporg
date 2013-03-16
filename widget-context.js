jQuery(window).ready(function($) {

	$('.widget-context-inside, .toggle-contexts .collapse').hide();

	$(document).ajaxComplete( function( e, xhr, settings ) {
		$('.widget-context-inside, .toggle-contexts .collapse').hide();
	});

	$('.widget-context .toggle-contexts').live( 'click', function() {
		$(this).siblings('.widget-context-inside').slideToggle('fast');
		$('span', this).toggle();

		return false;
	});

});