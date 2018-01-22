jQuery(function($){

	var Freeform		= window.Freeform || {};
	var $templateData	= $('textarea[name="template_data"]');
	var $label			= $('input[name="template_label"]');
	var $name			= $('input[name="template_name"]');

	Freeform.autoGenerateShortname($label, $name);

	Freeform.autoDupeLastInput(
		$('div.value_label_holder'),
		'value_label_holder_input'
	);

	$('body').on('click', '.freeform_delete_button', function(){
		$(this).parent().remove();
	});

	$templateData.on('keydown', function(e){
		Freeform.tabTextarea(e, this);
	});
});