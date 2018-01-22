/**
 * Solspace - Freeform
 *
 * @package		Solspace:Freeform
 * @author		Solspace DevTeam
 * @copyright	Copyright (c) 2008-2011, Solspace, Inc.
 * @link		http://solspace.com/docs/addon/c/Freeform/
 * @version		4.0.8.b1
 * @filesource	./system/expressionengine/third_party/freeform/
 */

/**
 * Freeform - Composer CP JS
 *
 * @package		Solspace:Freeform
 * @author		Solspace DevTeam
 * @filesource	./themes/third_party/freeform/js/edit_field_cp.js
 */

;(function(global, $){
	//es5 strict mode
	"use strict";

	//JSHint globals
	/*global _:true, Freeform:true, Security:true, jQuery:true, EE:true */

	var Freeform = global.Freeform = global.Freeform || {};

	Freeform.initFieldEvents = function ()
	{
		var $context			= $('#edit_field form:first');
		var $fieldTypeDescs		= $('.field_type_desc',		$context);
		var $fieldDesc			= $('[name="field_description"]',	$context);
		var $fieldLabel			= $('[name="field_label"]',	$context);
		var $fieldName			= $('[name="field_name"]',	$context);
		var $optionsInsert		= $('#options_insert',		$context);
		var $fieldSettings		= $('#field_settings',		$context);
		var $fieldSettingsInner = $('#field_settings_inner',$context);
		var $formList			= $('#form_list',			$context);
		var $chosenFormList		= $('#chosen_form_list',	$context);
		var $formIdsRow			= $('#form_ids_row',		$context);
		var $formIds			= $('[name=form_ids]',	    $context);
		var $fieldtypeSelect	= $('select[name="field_type"]', $context);
		var $submitBlock		= $('fieldset.form-ctrls', $context);

		//auto generate name if checkbox checked
		Freeform.autoGenerateShortname($fieldLabel, $fieldName);

		// -------------------------------------
		//	headers and sub items are not
		//	wrapped in a parent container
		//	so we have to loop siblings
		//	and build the set for each
		//	custom fieldtype
		// -------------------------------------

		var fieldTypeSets = {};

		$fieldtypeSelect.find('option').each(function(e){

			var name = $(this).val();

			var $h2	= $('h2[data-section-group="' + name +'"]', $context);

			if ($h2.length)
			{
				fieldTypeSets[name] = $h2;

				var $current = $h2;

				$current = $current.next();

				while ($current.length && $current.is('fieldset.col-group'))
				{
					fieldTypeSets[name] = fieldTypeSets[name].add($current);

					$current = $current.next();
				}
			}
		});

		// -------------------------------------
		//	jQuery remove doesn't delete since
		//	we still have the object pointers
		//	so it just pulls it from the visible
		//	DOM but the items still exist as
		//	jQuery elements in our array.
		//	We can then add and remove them
		//	at will.
		// -------------------------------------

		$fieldtypeSelect.change(function(){
			var type		= $(this).val();
			var	$settings	= $('.field_settings[data-field="' + type + '"]', $context);

			$.each(fieldTypeSets, function(i, item){
				item.remove();
			});

			if (typeof fieldTypeSets[type] !== 'undefined')
			{
				$submitBlock.before(fieldTypeSets[type]);
			}

			//hack to get yes/no radio buttons
			//to show their correct value
			//not sure why this isn't working
			//normally. Might be something to do
			//with a listener on yes/no check boxes
			//in ee's common.js cp file.
			//look for $('body').on('click change', '.choice input', function() {
			//therein.
			$('[type="radio"][checked]', $context).click();

			$('body').trigger("fieldTypeChanged", [type]);

		}).change(); //run immediately

		// -------------------------------------
		//	resets the field IDs
		// -------------------------------------

		var setFormIds = function()
		{
			var formIds = [];

			$('.field_tag', $chosenFormList).each(function(){
				formIds.push($(this).data('form-id'));
			});

			$formIds.val(formIds.join('|'));
		};

		// -------------------------------------
		//	field ids
		// -------------------------------------

		//move to the new table and sort elements on it
		//no need to sort the table its removed from as
		//it just pops this item out from its place
		$formList.on('click', '.field_tag', function(){
			$chosenFormList.
				append($(this).remove()).children().
				sortElements(function(a, b){
					return $(a).text().toLowerCase() > $(b).text().toLowerCase() ? 1 : -1;
				});
			setFormIds();
		});

		//same crap, different tag
		$chosenFormList.on('click', '.field_tag', function(){
			$formList.
				append($(this).remove()).children().
				sortElements(function(a, b){
					return $(a).text().toLowerCase() > $(b).text().toLowerCase() ? 1 : -1;
				});
			setFormIds();
		});

		// -------------------------------------
		//	setup fields on load to be in
		//	the correct field.
		// -------------------------------------

		var currentForms = ($.trim($formIds.val()) !== '') ? $formIds.val().split('|') : [];

		if (currentForms.length > 0)
		{
			$.each(currentForms, function(i, item){
				$('.field_tag[data-form-id="' + item + '"]', $formList).click();
			});
		}
	};
	//END Freeform.initFieldEvents


	//The multi_item_row.php template sets some JS that must
	//run before our events here run so we have to use
	//DOMContentLoaded listener because jQuery has an optimized
	//on ready event that has the potential to fire before
	//DOMContentLoaded. If that happens, $fieldtypeSelect.change(
	//fires and elements get removed before Freeform.setupMultiRowDelegate
	//has a chance to fire on the fields that it needs to.
	//We are using the setTimeout trick to force us to go last to
	//anything else listening on this event, just in case.

	//if this is the standard field page, laod the events
	$(function() {
		if ($('#edit_field form:first').length > 0)
		{
			Freeform.initFieldEvents();
		}
	});

}(window, jQuery));
