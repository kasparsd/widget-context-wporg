jQuery(document).ready(function($) {

	$('#debug-menu-target-Debug_Widget_Context .toggle').on('click', function(e){
		e.preventDefault();
		$( $(this).attr('href') ).toggle();
	});

});
