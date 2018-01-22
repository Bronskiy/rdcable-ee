jQuery(function($){
	var Freeform		= window.Freeform || {};
	//auto generate name if checkbox checked
	Freeform.autoGenerateShortname(
		$('[name="notification_label"]:first'),
		$('[name="notification_name"]:first')
	);
});