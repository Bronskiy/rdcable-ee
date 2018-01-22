//Hack to make the EE datepicker work
//because it fails 'use strict' mode when
//combined with some other JS files that
//declare 'use strict' in the same document.
//thus completely failes on these undeclatred
//vars.

//This is happening because we are loading
//a few different JS files via
//ee()->cp->add_js_script and one of EE's
//other JS files has the 'use strict'
//declaration. Hence why this isn't an
//issue in the publish page.

var suffix, doy, diff, days_in_month;

// -------------------------------------
//	the rest of the story
// -------------------------------------

(function($){

	var Freeform = window.Freeform || {};

	$(function(){
		var $form			= $('#entry-filters');
		var $editLayout		= $('a.btn[href="#edit_field_layout"]');
		var $exportEntries	= $('a.btn[href="#export_entries"]');
		var $rangeStart		= $('#search_date_range_start').hide();
		var $rangeEnd		= $('#search_date_range_end').hide();
		//getting this from the view so we don't have
		//to write some insane regex to check for
		//different URL cases in case someone
		//decides to hide 'index.php?' in the
		//CP view.
		var act				= $form.attr('data-action');

		// -------------------------------------
		//	This seems rediculous, but the way
		//	that ElliLab has made URLs in EE 3
		//	you cannot submit the old
		//	m=addons&module=freeform
		//	style urls without errors, sending via
		//	POST screws up the back button on simple
		//	searches and trying to do things via
		//	GIT ignores the new fake segment URL
		//	and EE's url cleansing prevents
		//	us from passing the segments
		//	ourselves as a GET var with an empty value.
		//	So here we are doing this the stupid way.
		//	¬_¬
		// -------------------------------------

		$form.on('click', '#reset-search', function(e){
			e.preventDefault();

			//we have to remove the hash here
			var uri		= window.location.href.replace(/#(.*)?$/i, '');
			//merge old args and new ones
			var args	= Freeform.getUrlArgs(uri);
			var nargs	= {};

			//this is the only manditory id going on
			//so we keep this on reset
			nargs['form_id'] = args['form_id'];

			//use the fetched action url so we aren't
			//compounding the same args over and over again
			window.location.href = act + '&' + $.param(nargs);

			return false;
		});

		$form.on('submit', function(e){
			e.preventDefault();

			//we have to remove the hash here
			var uri		= window.location.href.replace(/#(.*)?$/i, '');
			//merge old args and new ones
			var args	= Freeform.getUrlArgs(uri);
			var fargs	= Freeform.getUrlArgs($form.serialize());

			//we need to override old values first
			for (var name in fargs)
			{
				args[name] = fargs[name];
			}

			//lets not annoy users with showing the msg
			//over and over again
			if (typeof args['msg'] !== 'undefined')
			{
				delete args['msg'];
			}

			//use the fetched action url so we aren't
			//compounding the same args over and over again
			window.location.href = act + '&' + $.param(args);

			return false;
		});

		// -------------------------------------
		//	edit field layout
		// -------------------------------------

		//.trigger('modal:open');
		//.trigger('modal:close');

		$editLayout.on('click',  function(e){
			e.preventDefault();

			$('.field-layout').trigger('modal:open');
			//have that sorter thing come up
			//see if we can build our own modal with
			//the modal class

			return false;
		});

		// -------------------------------------
		//	export entries
		// -------------------------------------

		$exportEntries.on('click',  function(e){
			e.preventDefault();

			$('.export-entries').trigger('modal:open');

			return false;
		});


		$('#search_date_range').change(function(){
			if ($(this).val() == 'date_range')
			{
				$rangeStart.show();
				$rangeEnd.show();
			}
			else
			{
				$rangeStart.hide().val('');
				$rangeEnd.hide().val('');
			}
		}).change();


		$('#save-layout-form').on('submit', function(e){
			e.preventDefault();

			$.fancybox.showActivity();

			var fields	= [];
			var $form	= $(this);
			var vars	= {};

			// -------------------------------------
			//	get visible fields
			// -------------------------------------

			$('.choice.block.chosen a', $form).each(function(){
				fields.push($(this).attr('data-entry-id'));
			});

			vars['shown_fields[]'] = fields;

			// -------------------------------------
			//	sort the rest of the options
			// -------------------------------------

			//this may be completely unncessary
			$.each($form.serializeArray(), function(i, item){
				//array?
				if (item.name.substr(-2) == '[]')
				{
					if (typeof vars[item.name] == 'undefined')
					{
						vars[item.name] = [];
					}

					vars[item.name].push(item.value);
				}
				else
				{
					vars[item.name] = item.value;
				}
			});

			vars['current_url'] = window.location.href;

			// -------------------------------------
			//	send
			// -------------------------------------

			$.post($form.attr('action'), vars, function(data){

				if (data.success)
				{
					$.fancybox.hideActivity();
					window.location.reload();
					$('.field-layout').trigger('modal:close');
				}
				else
				{
					$.fancybox.hideActivity();

					Freeform.showValidationErrors(data.errors);
				}
			}, "json");
		});
	});
}(jQuery));