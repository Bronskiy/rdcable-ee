(function(global, $){
	$(function(){
		var Freeform		= window.Freeform || {};
		var $publish		= $('div.publish');
		var $publishParent	= $publish.closest('fieldset');
		var $formType		= $('[name="form_type"]');
		var $fieldChoices	= $('[data-field="form_fields"]', $publish);
		var $orderChoices	= $('.relate-wrap', $publish).not($fieldChoices);
		var $fieldIds		= $('[name="field_ids"]');
		var $fieldOrder		= $('[name="field_order"]');
		var $formFields     = $('input[name="form_fields[]"]:first').parents('fieldset.col-group:first');

		//has to be specific like this because EE has 2+ forms on the page
		//with no IDs on them.
		var $submit			= $('form[action*="save_form"].settings fieldset.form-ctrls [type="submit"]');

		//data-submit-text is required to get the name correct
		//by EE's JS. -_-
		$submit.val(Freeform.lang.save).
			attr('data-submit-text', Freeform.lang.save);

		//auto generate name
		Freeform.autoGenerateShortname(
			$('[name="form_label"]:first'),
			$('[name="form_name"]:first')
		);

		if (Freeform.pro)
		{
			var $saveAndFinish	= $submit.clone().attr('id', 'save_and_finish');
			var $continue		= $submit.clone().attr('id', 'continue');
			$saveAndFinish.val(Freeform.lang.saveAndFinish).
				attr('data-submit-text', Freeform.lang.saveAndFinish);
			$continue.val(Freeform.lang.continue).
				attr('data-submit-text', Freeform.lang.continue);

			$submit.parent().append($saveAndFinish).append($continue);

			$formType.on('change', function(e){
				setTimeout(function(){
					if ($formType.val() == 'template')
					{
						$saveAndFinish.hide();
						$continue.hide();
						$submit.show();
						$publishParent.show();
						$formFields.show();
					}
					else
					{
						$saveAndFinish.show();
						$continue.show();
						$submit.hide();
						$publishParent.hide();
						$formFields.hide();
					}
				}, 100);
			}).change(); //fire once to get it all going to start

			//change return for different submit click
			$continue.on('click', function(){
				$('[name="ret"]').val('composer');
			});

			$saveAndFinish.on('click', function(){
				$('[name="ret"]').val('composer_save_finish');
			});
		}


		$publish.on('mouseup', function(e){
			//timeout because apparently jQuery UI has a delay on drop
			//or is running async
			setTimeout(function(){
				var fieldIds = [];
				$orderChoices.find('label[data-entry-id]').each(function(e){
					var $that = $(this);
					fieldIds.push($that.attr('data-entry-id'));
				});

				$fieldOrder.val(fieldIds.join('|'));
				$fieldIds.val(fieldIds.sort().join('|'));
			}, 100);

		}).mouseup();
	});
}(window, jQuery));
