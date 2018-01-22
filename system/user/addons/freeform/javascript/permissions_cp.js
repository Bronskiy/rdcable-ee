jQuery(function($){
	var $settingFields = $('.setting-field select[name$="_allow_type"]');

	$settingFields.on('change', function(e){
		var $that = $(this);
		var $choices = $that.siblings('.choice');

		if ($that.val() == 'by_group')
		{
			$choices.show();
		}
		else
		{
			$choices.hide();
		}
	}).change();
});