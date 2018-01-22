;(function(global, $){
	//es5 strict mode
	"use strict";

	var Freeform = global.Freeform = global.Freeform || {};

	// -------------------------------------
	//	short name
	// -------------------------------------

	Freeform.shortName = function(a)
	{
		var b = "",
			c = "_",
			f = "",
			d = {
				"223": "ss",
				"224": "a",
				"225": "a",
				"226": "a",
				"229": "a",
				"227": "ae",
				"230": "ae",
				"228": "ae",
				"231": "c",
				"232": "e",
				"233": "e",
				"234": "e",
				"235": "e",
				"236": "i",
				"237": "i",
				"238": "i",
				"239": "i",
				"241": "n",
				"242": "o",
				"243": "o",
				"244": "o",
				"245": "o",
				"246": "oe",
				"249": "u",
				"250": "u",
				"251": "u",
				"252": "ue",
				"255": "y",
				"257": "aa",
				"269": "ch",
				"275": "ee",
				"291": "gj",
				"299": "ii",
				"311": "kj",
				"316": "lj",
				"326": "nj",
				"353": "sh",
				"363": "uu",
				"382": "zh",
				"256": "aa",
				"268": "ch",
				"274": "ee",
				"290": "gj",
				"298": "ii",
				"310": "kj",
				"315": "lj",
				"325": "nj",
				"352": "sh",
				"362": "uu",
				"381": "zh"
			};

		if (b !== "")
		{
			if (a.substr(0, b.length) == b)
			{
				a = a.substr(b.length);
			}
		}

		a = a.toLowerCase();
		b = 0;

		for (var g = a.length; b < g; b++)
		{
			var e = a.charCodeAt(b);

			if (e >= 32 && e < 128)
			{
				f += a.charAt(b);
			}
			else if (d.hasOwnProperty(e))
			{
				f += d[e];
			}
		}

		d = new RegExp(c + "{2,}", "g");
		a = f;
		a = a.replace("/<(.*?)>/g", "");
		a = a.replace(/\s+/g, c);
		a = a.replace(/\//g, c);
		a = a.replace(/[^a-z0-9\-\_]/g, "");
		a = a.replace(/\+/g, c);
		a = a.replace(d, c);
		a = a.replace(/-$/g, "");
		a = a.replace(/_$/g, "");
		a = a.replace(/^_/g, "");
		a = a.replace(/^-/g, "");
		a = a.replace(/\.+$/g, "");
		return a;
	};
	//END Freeform.shortName


	//there might be a better way to do this, but this works
	Freeform.insertToTextarea = function (field, insert, ie)
	{
		ie = ie || false;

		//good browsers
		if ( ! ie &&
			typeof field.selectionEnd !== 'undefined' &&
			! isNaN(field.selectionEnd))
		{
			var selLength	= field.textLength;
			var selStart	= field.selectionStart;
			var selEnd		= field.selectionEnd;

			//if (selEnd <= 2 && typeof selLength !== 'undefined')
			//{
			//	selEnd = selLength;
			//}

			var s1			= (field.value).substring(0, selStart);
			var s2			= (field.value).substring(selStart, selEnd);
			var s3			= (field.value).substring(selEnd, selLength);
			var newStart	= selStart + insert.length;

			field.value = s1 + insert + s3;

			field.focus();
			field.selectionStart	= newStart;
			field.selectionEnd	= newStart;
		}
		//stupid IE
		else if (document.selection)
		{
			field.focus();
			document.selection.createRange().text = insert;
			field.blur();
			field.focus();
		}
		else
		{
			//all else fails
			field.value += insert;
		}
	};

	//because this should have been default years ago
	Freeform.tabTextarea = function (event, node)
	{
		var tabSpace = "    ";

		if (event.keyCode == 9)
		{
			if (typeof event.preventDefault !== 'undefined')
			{
				event.preventDefault();
			}

			var height = node.scrollTop;

			//don't allow this to move on
			event.returnValue = false;

			//good browsers
			if (node.setSelectionRange)
			{
				var d = node.selectionStart + 4;
				node.value = node.value.substring(0, node.selectionStart) +
							tabSpace +
							node.value.substring(node.selectionEnd, node.value.length);

				setTimeout(function(){
					node.focus();
					node.setSelectionRange(d, d);
				}, 0);
			}
			//crappy IE
			else
			{
				node.focus();
				document.selection.createRange().text = tabSpace;
				node.blur();
				node.focus();
			}

			node.scrollTop = height;
			node.focus();
		}
	};

	// -------------------------------------
	//	auto generate shortname form elements
	// -------------------------------------

	Freeform.autoGenerateShortname = function($label, $name, $autoGenerateCheckbox)
	{
		//check initial. If it gets clicked, set again.
		//bool is faster than attr check
		//this is intially off for edits.
		var autoGenerate = $label.val().length == 0;

		if ($autoGenerateCheckbox && $autoGenerateCheckbox['length'])
		{
			autoGenerate = ($autoGenerateCheckbox.attr('checked') == 'checked');

			$autoGenerateCheckbox.change(function(){
				autoGenerate = ($autoGenerateCheckbox.attr('checked') == 'checked');

				//when they check, lets do the work so they don't have to type again
				if (autoGenerate)
				{
					$label.keyup();
				}
			});
		}

		//generate on each keyup because... because.
		$label.keyup(function(){
			if (autoGenerate)
			{
				$name.val(Freeform.shortName($label.val()));
			}
		}).keyup();
	};
	//END Freeform.autoGenerateShortname


	// -------------------------------------
	//	form prep
	// -------------------------------------

	//preps items to be placed in a value element and not be
	//parsed as html
	Freeform.formPrep = function (str)
	{
		return  $.trim(
			str.replace(/"/g, '&quot;').
				replace(/'/g, '&#39;').
				replace(/</g, '&lt;').
				replace(/>/g, '&gt;')
		);
	};
	//end Freeform.formPrep


	// -------------------------------------
	//	autoDupeLastInput (private)
	// -------------------------------------

	//this checks any of the inputs on keyup, and if its the last
	//available one, it auto adds a new field below it and exposes the
	//delete button for the current one
	//this is mostly used for field_settings, but better to not load it
	//many times
	function autoDupeLastInput ($parentHolder, input_class)
	{
		var timer = 0;

		$parentHolder.find('.freeform_delete_button:last').hide();

		$parentHolder.on('keyup', '.' + input_class + ' input', function()
		{
			//this keyword not avail inside functions
			var that = this;

			clearTimeout(timer);

			timer = setTimeout(function(){
				var $that	= $(that),
					$parent	= $that.parent();

				//if the last item is not empty
				//and it is indeed the last item, lets dupe a new one
				if ($.trim($that.val()) !== '' &&
					$parent.is($('.' + input_class + ':last', $parentHolder)))
				{
					//clone BEEP BOOP BORP KILL ALL HUMANS
					var $newHolder = $parent.clone();

					//empties the inputs and
					//increments names like list_value_holder_input[10] to
					// list_value_holder_input[11]
					$newHolder.find('input').each(function(i, item){
						var $input = $(this);

						if ($input.is('[type="text"]'))
						{
							$input.val('');
						}
						else
						{
							//remove attr doesn't work for checked and selected
							//in IE
							if ($input.attr('selected'))
							{
								$input.attr('selected', false);
							}

							if ($input.attr('checked'))
							{
								$input.attr('checked', false);
							}
						}

						var match = /([a-zA-Z\_\-]+)\[([0-9]+)\]/ig.exec(
							$(this).attr('name')
						);

						if (match)
						{
							$(this).attr('name',
								match[1] + '[' +
									(parseInt(match[2], 10) + 1) +
								']'
							);
						}
					});

					//add to parent
					$parent.parent().append($newHolder);
					//show delete button for current
					$parent.find('.freeform_delete_button').show();
				}
			}, 250);
			//end setTimeout
		});
		//end delegate
	}
	//end autoDupeLastInput

	Freeform.autoDupeLastInput = autoDupeLastInput;

	// -------------------------------------
	//	carry over inputs (private)
	// -------------------------------------

	//	carries data from one type to the next
	//	on the field options for multi-line
	function carryOverInputs (oldType, newType, prefix)
	{
		//we cannot do anything with channel_field data
		if (newType == 'channel_field' ||
			oldType == 'channel_field')
		{
			return;
		}

		var data		= [];
			//these get called every hit because we need the dynamic
			//ones to recalc
		var $nld_ta		= $('textarea[name="' + prefix + '_list_nld_textarea_input"]');
		var $list		= $('input[name*="' + prefix + '_list_holder_input"]');
		var $lvList		= $('input[name*="' + prefix + '_list_value_holder_input"]');
		var $llList		= $('input[name*="' + prefix + '_list_label_holder_input"]');
		var $vlHolder	= $('#' + prefix + '_type_value_label_holder');
		var $listHolder	= $('#' + prefix + '_type_list_holder');
		var i;
		var l;


		// -------------------------------------
		//	get data
		// -------------------------------------

		if (oldType == 'nld_textarea')
		{
			//split on newline
			var temp_data = $nld_ta.val().split(/\n\r|\n|\r/ig);

			//remove blanks
			for (i = 0, l = temp_data.length; i < l; i++)
			{
				var trimmed = temp_data[i];

				if (trimmed !== '')
				{
					data.push(trimmed);
				}
			}
		}
		else if (oldType == 'value_label')
		{
			//remove blanks
			//and we are just getting the labels
			//because nothing else supports the value set

			$llList.each(function()
			{
				var trimmed = $(this).val();

				if (trimmed !== '')
				{
					data.push(trimmed);
				}

			});
		}
		else if (oldType == 'list')
		{
			//remove blanks
			$list.each(function()
			{
				var trimmed = $(this).val();

				if (trimmed !== '')
				{
					data.push(trimmed);
				}
			});
		}

		//no data? scram
		if (data.length === 0)
		{
			return;
		}

		// -------------------------------------
		//	set data
		// -------------------------------------

		var $inputs;
		var $clone;

		if (newType == 'nld_textarea')
		{
			$nld_ta.val(data.join('\n'));
		}
		else if (newType == 'value_label')
		{
			var vlHoldover = {};

			//get old labels and salvage values, or auto create
			//this way a user doesn't lose their value sets
			//if they edit/re-order in another type
			$llList.each(function(i){
				var that_label = Freeform.formPrep($llList.eq(i).val());
				var that_value = Freeform.formPrep($lvList.eq(i).val());

				if (that_label !== '' && that_value !== '' )
				{
					vlHoldover[that_label]	= that_value;
				}
			});

			//remove oldies and get a clone
			$inputs	= $('.value_label_holder_input', $vlHolder);
			$clone	= $inputs.eq(0).clone();

			//cleaaaan
			$inputs.remove();

			//this will make a blank one for us
			data.push('');

			for (i = 0, l = data.length; i < l; i++)
			{
				var shortname = Freeform.shortName(data[i]);

				if (shortname === '')
				{
					shortname = data[i];
				}

				//need to be implicit here instead of input:first/last
				//because third party devs might want to inject items
				var $item	= $clone.clone();
				var $value	= $item.find('input[name*="' + prefix + '_list_value_holder_input"]');
				var $label	= $item.find('input[name*="' + prefix + '_list_label_holder_input"]');

				$value.attr('name', prefix + '_list_value_holder_input[' + i + ']').
					val(
						(typeof vlHoldover[data[i]] !== 'undefined') ?
							vlHoldover[data[i]] :
							shortname
					);
				$label.attr('name', prefix + '_list_label_holder_input[' + i + ']').val(data[i]);

				$vlHolder.append($item);
			}

			//shows all deletes, then hids the last for the blank row
			$vlHolder.find('.freeform_delete_button').show().last().hide();
		}
		else if (newType == 'list')
		{
			//remove oldies and get a clone
			$inputs	= $('.list_holder_input', $listHolder);
			$clone	= $inputs.eq(0).clone();

			//cleaaaan
			$inputs.remove();

			//this will make a blank one for us
			data.push('');

			for (i = 0, l = data.length; i < l; i++)
			{
				$listHolder.append(
					$clone.clone().
						find('input[name*="' + prefix + '_list_holder_input"]').
							val(data[i]).
						end()
				);
			}

			//shows all deletes, then hids the last for the blank row
			$listHolder.find('.freeform_delete_button').show().last().hide();
		}
	}
	//END carryOverInputs


	// -------------------------------------
	//	set up delegation for multi row set
	// -------------------------------------

	Freeform.setupMultiRowDelegate = function (currentChoice, prefix)
	{
		currentChoice			= currentChoice || 'list';
		prefix					= prefix || '';

		$('body').on('fieldTypeChanged', function(event, type)
		{
			if (type != prefix)
			{
				return;
			}

			var $listType		= $('#' + prefix + '_list_type');
			var $listTypeSelect	= $('select[name="' + prefix + '_list_type"]');
			var $listHolders	= $('#' + prefix + '_option_holder > div');
			var $typeListHolder	= $('#' + prefix + '_type_list_holder');
			var $typeKvlHolder	= $('#' + prefix + '_type_value_label_holder');
			var $typeNLDTholder	= $('#' + prefix + '_type_nld_textarea_holder');
			var listTypes		= [];

			$listTypeSelect.find('option').each(function(){
				listTypes.push($(this).val());
			});

			// -------------------------------------
			//	list holder auto new
			// -------------------------------------

			autoDupeLastInput($typeListHolder, 'list_holder_input');
			autoDupeLastInput($typeKvlHolder, 'value_label_holder_input');

			// -------------------------------------
			//	delete buttons
			// -------------------------------------

			$listHolders.on('click','.freeform_delete_button', function(){
				$(this).parent().remove();
			});

			// -------------------------------------
			//	type chooser
			// -------------------------------------

			$listHolders.hide();

			var fieldTypeGroups = [];

			//shows the list holder for the chosen item
			$.each(listTypes, function(item, i){
				var $that	= $('#' + prefix + '_type_' + item + '_holder');
				var type	= item;

				//this will only run onload
				//and will show the correct current input
				if (type == currentChoice)
				{
					$that.addClass('active');
					$that.show();
				}
			});


			$listTypeSelect.change(function(e){
				var type = $listTypeSelect.val();

				var $that = $('#' + prefix + '_type_' + type + '_holder');

				//swap active
				$listHolders.removeClass('active');
				$that.addClass('active');

				//swap display
				$listHolders.hide();
				$that.show();

				carryOverInputs(currentChoice, type, prefix);

				//we don't to change on channel_field
				//because it has its own list data and we are just
				//using this to help the carryOverInputs function
				if (type != 'channel_field')
				{
					currentChoice = type;
				}

				$listType.val(type);

				e.preventDefault();
				return false;
			}).change();
			//end $that.click
		});
		//end $('body').on('fieldTypeChanged', function(event, type)
	};
	//END Freeform.setupMultiRowDelegate


	// -------------------------------------
	//	jQuery.fn.sortElements
	// -------------------------------------

	//	https://github.com/jamespadolsey/jQuery-Plugins/tree/master/sortElements

	//added extra layer in case this is defined
	$.fn.sortElements = $.fn.sortElements || (function()
	{
		var sort = [].sort;

		return function(comparator, getSortable)
		{
			getSortable = getSortable || function(){return this;};

			var placements = this.map(function(){

				var sortElement = getSortable.call(this),
					parentNode = sortElement.parentNode,

					// Since the element itself will change position, we have
					// to have some way of storing its original position in
					// the DOM. The easiest way is to have a 'flag' node:
					nextSibling = parentNode.insertBefore(
						document.createTextNode(''),
						sortElement.nextSibling
					);

				return function()
				{
					if (parentNode === this)
					{
						/*throw new Error(
							"You can't sort elements if any one is a descendant of another."
						);*/

						return;
					}

					// Insert before flag:
					parentNode.insertBefore(this, nextSibling);
					// Remove flag:
					parentNode.removeChild(nextSibling);
				};

			});

			return sort.call(this, comparator).each(function(i){
				placements[i].call(getSortable.call(this));
			});
		};
	}());
	//end sortElements

	// -------------------------------------
	//	jQuery outerHTML
	// -------------------------------------

	$.fn.outerHTML = $.fn.outerHTML || function(){
		return $(this)[0].outerHTML;
	};
	//end $.fn.outerHTML

	// -------------------------------------
	//	jQuery remove tags
	//	jQuery("#container").find(
	//		":not(b, strong, i, em, u, br, pre, blockquote, ul, ol, li, a)"
	//	).removeTags();
	// -------------------------------------

	$.fn.removeTags = $.fn.removeTags || function()
	{
		this.each(function()
		{
			if(jQuery(this).children().length === 0)
			{
				jQuery(this).replaceWith(jQuery(this).text());
			}
			else
			{
				jQuery(this).children().unwrap();
			}
		});

		return this;
	};


	// --------------------------------------------------------------------

	/**
	 * Fires a jQuery UI dialog immediatly or delays it for onclick
	 *
	 * @access	public
	 * @param	{Array}	options	options array
	 * @return	{Mixed}			returns a function or fires immediatly
	 */

	Freeform.jQUIDialog = function(options)
	{
		options	= $.extend({
				'message'			: 'No Message Defined',
				'ok'				: 'OK',
				'title'				: 'Alert',
				'preventDefault'	: true,
				'immediate'			: false,
				'modal'				: true
		}, options);

		var buttonsOptions	= {};
		var dialogInstalled	= (typeof $.fn.dialog !== 'undefined');
		var $dialog			= $('<div></div>').html(options.message);

		// -------------------------------------
		//	denied dialog
		// -------------------------------------

		if (dialogInstalled)
		{
			//cancel button?
			if (typeof options.cancel !== 'undefined')
			{
				buttonsOptions[options.cancel] = {
					'click'	: function()
					{
						if (typeof options.cancelClick !== 'undefined')
						{
							options.cancelClick();
						}

						$(this).dialog("close");
					},
					'class'	: 'submit btn ',
					'text'	: options.cancel
				};
			}

			//we need at least an ok button
			buttonsOptions[options.ok] = {
				'click'	: function()
				{
					if (typeof options.close !== 'undefined')
					{
						options.close();
					}

					if (typeof options.okClick !== 'undefined')
					{
						options.okClick();
					}

					$(this).dialog("close");
				},
				'class'	: 'submit btn action',
				'text'	: options.ok
			};

			$dialog.dialog({
				"autoOpen"		: false,
				"title"			: options.title,
				"buttons"		: buttonsOptions,
				"dialogClass"	: 'ss_jqui_dialog',
				"zIndex"		: 50,
				"modal"			: options.modal,
				"close"			: function(){
					$('.ui-dialog.ss_jqui_dialog').remove();
				}
			});
		}

		// -------------------------------------
		//	dialog fire!
		// -------------------------------------

		var returnFunction = function (e)
		{
			if (e && options.preventDefault)
			{
				e.preventDefault();
			}

			if (dialogInstalled)
			{
				$dialog.dialog('open');
			}
			else
			{
				window.alert(options.message);
			}

			// prevent the default action, e.g., following a link
			if (e && options.preventDefault)
			{
				return false;
			}
		};

		// -------------------------------------
		//	fire right now?
		// -------------------------------------

		if (options.immediate)
		{
			returnFunction();
		}
		else
		{
			return returnFunction;
		}
	};
	//END Freeform.prepJQUIDialog


	// --------------------------------------------------------------------

	/**
	 * Setup process for chosen select dropdowns
	 *
	 * @access	public
	 * @param	{Object}	$context dom element for context
	 */

	Freeform.setUpChosenSelect = function($context)
	{
		return;
		/*
		$context = $context || $('body');

		if (typeof $.fn.chosen !== 'undefined')
		{
			$(".chzn_select", $context).chosen();
			$(".chzn_select_no_search", $context).each(function(){
				var $that	= $(this);

				$that.chosen();
				$('#' + $that.attr('id') + '_chzn .chzn-search').hide();
			});
		}*/
	};
	//END Freeform.setUpChosenSelect


	// --------------------------------------------------------------------

	/**
	 * Allows a text field to filter down elements via visibility
	 *
	 * @access	public
	 * @param	{String} searchFieldQuery	css query to find input
	 * @param	{String} resultElementQuery	css query to find filtered elements
	 * @param	{String} onQuery			inner element of elements to filter on
	 * @param	{String} onAttr				body to search. html/text/attr()
	 * @return	{Ojbect}					jquery object of search element
	 */

	Freeform.elementSearch	= function (searchFieldQuery, resultElementQuery, onQuery, onAttr)
	{
		var $searchField		= $(searchFieldQuery);
		var $resultElements		= $(resultElementQuery);

		$searchField.bind('keyup', function(e){
			var $that = $(this);

			//hide all unless its empty or a placeholder
			//replacement helper
			if ($that.val() === '' ||
				($that.attr('placeholder') &&
				$that.val() == $that.attr('placeholder'))
			)
			{
				$resultElements.show();
			}
			else
			{
				$resultElements.hide();
			}

			//build regex early
			var search = new RegExp($that.val().toLowerCase());

			//filter results to matches and show
			$resultElements.filter(function(index) {
				var $this		= $(this),
					$searchOn	= $this,
					find;

				//are we looking on a sub element?
				if (onQuery)
				{
					$searchOn = $searchOn.find(onQuery);
				}

				//what are we searching, html(), text(), attr()?
				if (onAttr != 'html' && onAttr != 'text' && $searchOn.attr(onAttr))
				{
					find = $searchOn.attr(onAttr);
				}
				else if (onAttr == 'text')
				{
					find = $searchOn.text();
				}
				else
				{
					find = $searchOn.html();
				}

				//if this matches our regex build, show
				return search.exec(find.toLowerCase());
			}).show();
		});

		//prevent enter from submitting a form
		$searchField.bind('keydown', function(e){
			if (e.keyCode == 13)
			{
				return false;
			}
		});

		return $searchField;
	};
	//END elementSearch


	// --------------------------------------------------------------------

	/**
	 * String Repeat
	 *
	 * @access	public
	 * @param	{String}	str	string to repeat
	 * @param	{Int}		num	how many times to repeat it
	 * @return	{String}		string repeated
	 */

	Freeform.strRepeat = function (str, num)
	{
		var ret = '';

		while (num--)
		{
			ret += str;
		}

		return ret;
	};
	//END strRepeat


	// --------------------------------------------------------------------

	/**
	 * Fraction To Float
	 *
	 * returns the fraction as a percent float to 2 decimal places
	 *
	 * @access	public
	 * @param	{Int}	numerator
	 * @param	{Int}	denominator
	 * @parram	{Int}	base number to divide by (100 is default)
	 * @return	{Float}
	 */

	Freeform.fractionToFloat = function (numerator, denominator, base)
	{
		base = base || 100;

		return (Math.round(100 * ((numerator/denominator) * base))/100);
	};
	//END fractionToFloat


	// --------------------------------------------------------------------

	/**
	 * Get Url Arguments
	 *
	 * @access	public
	 * @param	{String}	url	url to parse. Window.location.href default
	 * @return	{Array}			array of arguments
	 */

	Freeform.getUrlArgs = function (url)
	{
		url			= url || window.location.href;
		var urlVars	= {};
		var parts	= url.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value)
		{
			urlVars[key] = value;
		});

		return urlVars;
	};
	//END getUrlArgs


	// --------------------------------------------------------------------

	var cycleSwtiches = [];

	/**
	 * Cycle over an array of items or a set of args
	 *
	 * @access	public
	 * @param	{Mixed}	items	items to cycle over
	 * @return	{Mixed}			returned cycle item
	 */

	Freeform.cycle = function(items)
	{
		if ( ! $.isArray(items))
		{
			items = Array.prototype.slice.call(arguments);
		}

		var hash = items.join('|');

		if ( typeof cycleSwtiches[hash] === 'undefined' ||
			typeof items[cycleSwtiches[hash] + 1] === 'undefined')
		{
			cycleSwtiches[hash] = 0;
		}
		else
		{
			cycleSwtiches[hash]++;
		}

		return items[cycleSwtiches[hash]];
	};


	/**
	 * clear the cycle functions cache
	 *
	 * @access	public
	 * @return	{object}		this for chaining
	 */

	Freeform.clearCycle = function()
	{
		cycleSwtiches = [];
		return this;
	};


	// --------------------------------------------------------------------

	/**
	 * Show Field Validation Errors for Field Edit/Create
	 *
	 * @access	public
	 * @param	{Object}	errors		key/value list of errors
	 * @param	{Object}	$context	instance of $('#field_edit_form') to forgo repeat jQ work
	 * @param	{Boolean}	autoScroll	autoscroll to first element. Requires jQuery.smoothScroll
	 * @return	{Null}
	 */

	Freeform.showValidationErrors = function(errors)
	{
		var outHtml = '<ul>';

		var errorHolder = $('#freeform-shared-model-errors');

		errorHolder.html('Error building errors.');

		$.each(errors, function (i, item){
			//if this is an array, just join line breaks
			outHtml += '<li>';
			outHtml += ($.isArray(item)) ?
							item.join('</li><li>') :
							item ;

			outHtml += '</li>';
		});

		errorHolder.html(outHtml);

		$('.freeform-error-modal').trigger('modal:open');
	};
	//END showFieldValidationErrors


	// -------------------------------------
	//	stuff to happen on document ready
	// -------------------------------------

	$(function(){

		// -------------------------------------
		//	close notices
		// -------------------------------------

		$('body').on('click', '.freeform_notice .notice_close', function(e){
			e.preventDefault();
			$(this).parent().slideUp();
			return false;
		});

		$('input[name="form_fields[]"]:first, input[name="form_ids[]"]:first').parents('.setting-field').addClass('scroll-wrap');

		var $fancyWrapper		= $('#fancybox-content');
		var $solspaceWrapper	= $('#mainContent .solspace_ui_wrapper:first');
	});

}(window, jQuery));
