jQuery(function($){
	var Freeform		= window.Freeform || {};
	Freeform.autoDupeLastInput(
		$('.list_holder_input'),
		'list_input'
	);

	$('body').on('click', '.freeform_delete_button', function(){
		$(this).parent().remove();
	});
});