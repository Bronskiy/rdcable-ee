;(function($, global){

	"use strict";

	//--------------------------------------------
	//	vars and settings
	//--------------------------------------------

	var ssData = global.solspaceTag	= global.solspaceTag || {};

	global.solspaceTag.domReadyFired = false;

	var settings = {
		//static because this is for the hidden field
		'taSeparator' : "\n"
	};

	//--------------------------------------------
	//	cheap templates. No plugins required
	//	the array joining just makes reading them easier
	//--------------------------------------------

	var views = {};

	views.currentTag = [
		'<div class="current_tag white_grad" data-tag="{tagName}">',
			'<span class="ex"><\/span>',
			'<span class="tag_name">',
				'{tagName}',
			'<\/span>',
		'<\/div>'
	].join('\n');

	views.suggestTag = [
		'<div class="suggest_tag white_grad" data-tag="{tagName}">',
			'<span class="plus"><\/span>',
			'<span class="tag_name">',
				'{tagName}',
			'<\/span>',
		'<\/div>'
	].join('\n');

	//parses out brackets from view files a bit like EE
	/*
		use like:
		$users.append(view(views.choice, {
			'id'			: data.users[item].id,
			'screen_name'	: data.users[item].name
		}));
	*/
	function view(template, data)
	{
		for (var item in data)
		{
			if (data.hasOwnProperty(item))
			{
				do {
					template = template.replace('{' + item + '}', data[item]);
				}
				while(template.indexOf('{' + item + '}') > -1);
			}
		}
		return template;
	}
	//END view


	//--------------------------------------------
	//	stringy stringy cleany cleany
	//--------------------------------------------

	function cleanString(str)
	{
		str = $.trim(
			//strip HTML
			str.replace(/(<([^>]+)>)/ig,'').
			//strip EE tags
			replace(/(\{([^\}]+)\})/ig,'').
			//protect apostrophes inside words
			replace(/(\w)\'(\w)/ig,'$1__PROTECTED_APOSTROPHE__$2').
			//keep ampersands between two words
			replace(/(\w\s)\&(\s\w)/ig,'$1__PROTECTED_AMPERSAND__$2').
			// strip punctuation
			replace(/['";:,.\/\?\\-]/g, '').
			//old strip punctuation was a whitelist which incorrectly
			//removed utf-8 chars out of this range. Blacklist is better
			//here.
			//replace(/([^\u3400-\u4db5\u4e00-\u9fa5\uf900-\ufa6a\u3041-\u3094\u30a1-\u30fa\w\d\s]+)/ig, '').
			// unprotect ampersands and apostrophes
			replace('__PROTECTED_APOSTROPHE__', "'").
			replace('__PROTECTED_AMPERSAND__', "&")
		);

		return str.match(/^ *$/) ? '' : str.split(/\s+/g).join('||');
	}
	//END cleanString


	//--------------------------------------------
	//	gets all field's data for parsing suggestions
	//--------------------------------------------

	function getFieldData(fieldID)
	{
		var str			= [];
		var value		= '';
		var extraFields = ssData['suggestFields'][fieldID];

		if (extraFields.length > 0)
		{
			$.each(extraFields, function(i, item){
				$('[name^=' + item + ']').each(function(i)
				{
					value = $.trim(this.value);

					if (value !== '')
					{
						str.push(cleanString(value));
					}
				});
			});
		}

		//safecracker
		if ($('#cform').length !== 0)
		{
			$('#cform textarea, ' +
				'#cform input:not([name="password"])').each(function(i)
			{
				value = $.trim(this.value);

				if (value !== '')
				{
					str.push(cleanString(value));
				}
			});
		}

		$('textarea[name^=field_id], input[name^=field_id]').each(function(i)
		{
			value = $.trim(this.value);

			if (value !== '')
			{
				str.push(cleanString(value));
			}
		});

		//wygwam support
		$('[id^="cke_contents_"] iframe').each(function(i)
		{
			value = $.trim($(this).contents().find('body').html());

			if (value !== '')
			{
				str.push(cleanString(value));
			}
		});

		return str.join('||');
	}
	//END getFieldData

	//--------------------------------------------
	//	prevent default shortcut
	//	abstracted in case we need to add more
	//--------------------------------------------

	function preventDefault(event)
	{
		event.preventDefault();
		return false;
	}

	//--------------------------------------------
	//	add tag
	//--------------------------------------------

	function addTag(fieldID, tagName, callback, sep)
	{
		var currentTags			= ssData['currentTags'][fieldID];
		var domCache			= ssData['dom'][fieldID];
		var tagLimit			= Number(ssData.tagLimit);
		var explodeSeparator	= ssData['explodeSeparator'][fieldID];
		var separator			= ssData['separator'][fieldID];
			sep					= sep || false;

		// -------------------------------------
		//	splitting multiples?
		// -------------------------------------

		if ( ! sep &&
			explodeSeparator &&
			domCache['explode_input_enable'].is(':checked')
		)
		{
			var reg = '((\\\s+)?' + preg_quote(separator, '/') + '(\\\s+)?)+';
			var multiples = tagName.split(new RegExp(reg, 'igm'));

			$.each(multiples, function(i, item){
				var itemTrim = $.trim(item);
				if (itemTrim !== separator)
				{
					addTag(fieldID, itemTrim, callback, true);
				}
			});

			return;
		}

		// -------------------------------------
		//	blank?
		// -------------------------------------

		if (tagName === '' || (
			tagLimit !== 0 &&
			currentTags.length >= tagLimit
		))
		{
			return;
		}

		tagName = changeQuotes(tagName, 'real');

		//is it actually there?
		if ($.inArray(tagName, currentTags) == -1)
		{
			currentTags.push(tagName);

			//add tag to items div
			domCache['current_tags'].
				prepend(
					view(
						views.currentTag,
						{'tagName' : changeQuotes(tagName, 'entities')}
					)
				);

			//fix field
			domCache['hidden_input'].val(currentTags.join(settings.taSeparator));

			//hide ac window in case we didn't chose what it asked for
			$('.tag_ac_results').hide();

			//webkit has render problems with the tags
			if (global.solspaceTag.transForm == 'webkitTransform')
			{
				$('.current_tag[data-tag="' + tagName.replace(/"/mg, '\\"') + '"]',
					domCache['current_tags']).css('webkitTransform', 'scale(1)');
			}
		}

		//did top tags change?
		checkTopTags(fieldID);

		//did we hit our limit?
		checkTagLimit(fieldID);

		//was this a suggested tag?
		//remove any suggested tags with the same name
		$(
			'.suggest_tag[data-tag="' +
				tagName.replace(/"/mg, '\\"') + '"]',
			domCache['suggest_tags']
		).remove();

		if (typeof callback == 'function')
		{
			callback();
		}
	}

	// -------------------------------------
	//	changes &quot; to '"' and back
	// -------------------------------------

	function changeQuotes(tagName, convertTo)
	{
		return (
			(convertTo == 'real') ?
				tagName.replace(/\&quot\;/mg, '"').replace(/\&\#039\;/mg, "'") :
				tagName.replace(/"/mg, '&quot;').replace(/'/mg, '&#039;')
		);
	}

	//--------------------------------------------
	//	remove tag
	//--------------------------------------------

	function removeTag(fieldID, tagName)
	{
		var currentTags	= ssData['currentTags'][fieldID];
		var domCache	= ssData['dom'][fieldID];
			tagName		= changeQuotes(tagName, 'real');
			//is it there?
		var position	= $.inArray(tagName, currentTags);

		if (position > -1)
		{
			//remove tag
			currentTags.splice(position, 1);

			domCache['hidden_input'].val(currentTags.join(settings.taSeparator));
		}

		//did top tags change?
		checkTopTags(fieldID);

		//did we hit our limit?
		checkTagLimit(fieldID);
	}

	//--------------------------------------------
	//	suggest tags
	//--------------------------------------------

	function suggestTags(fieldID, xid, callback)
	{
		var currentTags	= ssData['currentTags'][fieldID],
			domCache	= ssData['dom'][fieldID];

		//clear (makes sure that eventlisteners are removed in IE)
		domCache['suggest_tags_holder'].
				find('.suggest_tag').remove().
			end().
			append(views.loading);

		$.post(
			ssData.suggestTagsURL[fieldID],
			{
				XID			: xid,
				str			: getFieldData(fieldID),
				existing	: currentTags.join('||'),
				return_type	: 'json'
			},
			function(data)
			{
				if (data.suggestions.length > 0)
				{
					addSuggestedTags(fieldID, data.suggestions);
				}

				if (typeof callback == 'function')
				{
					callback();
				}
			},
			'json'
		);
	}

	//--------------------------------------------
	//	insert suggested tags into view
	//--------------------------------------------

	function addSuggestedTags(fieldID, tags)
	{
		var currentTags	= ssData['currentTags'][fieldID];
		var domCache	= ssData['dom'][fieldID];

		$.each(tags, function(i, tag){
			//add tag to items div
			$(
				view(
					views.suggestTag,
					{
						'tagName' : changeQuotes(tag, 'entities')
					}
				)
			).insertAfter(domCache['refresh_suggest']);
		});
	}

	//--------------------------------------------
	//	top tags check
	//--------------------------------------------

	function checkTopTags(fieldID)
	{
		var currentTags		= ssData['currentTags'][fieldID];
		var topTags			= ssData['topTags'][fieldID];
		var domCache		= ssData['dom'][fieldID];
		var $top_tags		= domCache['top_tags'];

		//if any of our top tags are in current tags
		//'disable' enable if not
		$.each(topTags, function(i, tag){

			//ok, this is batty, but here you have to replace the quotes
			//with slashed ones because when '&quot;' is in a javascript
			//string, its the literall letters, but when its in html
			//javascript sees it as '"', so using '&quot;' here wont work :/
			var $tag = $(
				'.top_tag[data-tag=\"' + tag.replace(/"/mg, '\\"') + '\"]',
				$top_tags
			);

			if ($.inArray(tag, currentTags) > -1)
			{
				$tag.addClass('used').
					find('.plus').hide();
			}
			else
			{
				$tag.removeClass('used').
					find('.plus').show();
			}
		});
	}

	//--------------------------------------------
	//	get hight of a hidden element
	//--------------------------------------------

	function getJQHeight($element)
	{
		var height;
		var position;
		var cssHeight;

		if ($element.css('display') == "none")
		{
			position	= $element.css('position');
			cssHeight	= $element.css('height');

			$element.css({
				//'position'	:'absolute',
				'visibility':'hidden',
				'display'	:'block',
				'height'	: ''
			});

			height = $element.height();

			$element.css({
				//'position'	:position,
				'visibility':'visible',
				'display'	:'none',
				'height'	: cssHeight
			});
		}
		else
		{
			height = $element.height();
		}

		return height;
	}

	//--------------------------------------------
	//	errors
	//--------------------------------------------

	function checkTagLimit(fieldID)
	{
		var domCache			= ssData['dom'][fieldID];
		var currentTags			= ssData['currentTags'][fieldID];
		var tagLimit			= Number(ssData.tagLimit);
		var langLimitReached	= ssData.langItems.tag_limit_reached.replace('%num%', tagLimit);

		if (tagLimit !== 0 && currentTags.length >= tagLimit)
		{
			domCache['tag_input'].attr('disabled', 'disabled');
			domCache['error_dialog'].find('.notice:first').html(langLimitReached).end().show();
		}
		else
		{
			domCache['tag_input'].removeAttr("disabled").val('');
			domCache['error_dialog'].hide();
		}
	}

	// -------------------------------------
	//	preg quote
	// -------------------------------------

	//https://github.com/kvz/phpjs/blob/master/functions/pcre/preg_quote.js
	// example 1: preg_quote("$40");
	// returns 1: '\$40'
	// example 2: preg_quote("*RRRING* Hello?");
	// returns 2: '\*RRRING\* Hello\?'
	// example 3: preg_quote("\\.+*?[^]$(){}=!<>|:");
	// returns 3: '\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:'
	function preg_quote (str, delimiter)
	{
		return (str + '').
			replace(new RegExp(
				'[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' +
					(delimiter || '') +
					'-]',
				'g'
			), '\\$&');
	}

	// -------------------------------------
	//	get XID where possible
	// -------------------------------------

	function getXID(fieldID)
	{
		var EEXid = false;

		if (typeof EE !== 'undefined')
		{
			if (typeof EE.csrf_token !== 'undefined')
			{
				EEXid = EE.csrf_token;
			}
		}

		return EEXid || ssData['xids'][fieldID];
	}

	// -------------------------------------
	//	main field event function
	// -------------------------------------

	function setFieldEvents ($parent)
	{
		//--------------------------------------------
		//	yes i am breaking convention with underscores
		//	but this lets us know these are elements
		//	and not data
		//--------------------------------------------

		//dom item cache
		$parent						= (typeof $parent == 'string') ? $($parent) : $parent;

		var fieldID					= $parent.attr('id').replace('solspace_tag_field_', '');

		var $current_tags			= $('div.solspace_tag_current_tags', $parent);
		//var $new_tags_section_name	= $('div.solspace_tag_new_tags .tag_section_name', $parent);
		var $new_tags_section_data	= $('div.solspace_tag_new_tags .tag_section_data', $parent);
		var $suggest_tags			= $('div.solspace_tag_suggest_tags', $parent);
		var $suggest_tags_holder	= $('div.tag_section_data', $suggest_tags);
		var $top_tags				= $('div.solspace_tag_top_tags', $parent);
		var $tag_input				= $("input[name='tag_input']", $parent);
		var $refresh_suggest		= $('div.refresh_suggest_tags', $parent);
		var $suggest_tags_button	= $('div.suggest_tags', $parent);
		var $top_tags_button		= $('div.top_tags', $parent);
		var $error_dialog			= $('div.tag_error_dialog', $parent);
		var $explode_input_enable	= $('input[name="tag_explode_input_enable_' + fieldID + '"]:first', $parent);
		var $explode_delimiter		= $('select[name="explode_delimiter"]:first', $parent);

		//data
		var currentTags				= ssData['currentTags'][fieldID];
		var topTags					= ssData['topTags'][fieldID];
		var allOpen					= ssData['allOpen'][fieldID];
		var explodeSepatator		= ssData['explodeSeparator'][fieldID];

			//have to show these to get the heights properly
			//hidden later
		var suggest_tags_height		= $suggest_tags.show().height();
		var top_tags_height			= $top_tags.show().height();

		// -------------------------------------
		//	convert top and current quotes
		// -------------------------------------
		//	TODO: This is a mess and should be
		//	just one way or the other in here
		//	or in PHP, but for now, this fixes
		// -------------------------------------

		$.each(currentTags, function(i, item){
			currentTags[i] = changeQuotes(currentTags[i], 'real');
		});

		$.each(topTags, function(i, item){
			topTags[i] = changeQuotes(topTags[i], 'real');
		});

		//--------------------------------------------
		//	helper caches for universal functions
		//--------------------------------------------

		ssData['dom'][fieldID] = {
			'parent'				: $parent,
			'current_tags'			: $current_tags,
			'suggest_tags'			: $suggest_tags,
			'suggest_tags_holder'	: $suggest_tags_holder,
			'top_tags'				: $top_tags,
			'tag_input'				: $tag_input,
			'refresh_suggest'		: $refresh_suggest,
			'error_dialog'			: $error_dialog,
			'hidden_input'			: $('#solspace_tag_ta_' + fieldID, $parent),
			'explode_input_enable'	: $explode_input_enable,
			'explode_delimiter'		: $explode_delimiter
		};

		//--------------------------------------------
		//	need to set these to 0 because we needed
		//	thier heights for smooth animation
		//	also if the all open pref is here, we need
		//	open it up by default
		//--------------------------------------------

		if (allOpen !== "yes")
		{
			$suggest_tags.hide();//.css({ 'height' : 0 });
			$top_tags.hide();//.css({ 'height' : 0 });
		}
		else
		{
			$suggest_tags_button.hide();
			$top_tags_button.hide();
			//$new_tags_section_name.show();
		}

		//--------------------------------------------
		//	set top tags styles if used
		//--------------------------------------------

		checkTopTags(fieldID);

		//--------------------------------------------
		//	check tag limit coming in
		//--------------------------------------------

		checkTagLimit(fieldID);

		//--------------------------------------------
		//	close buttons for optional sections
		//--------------------------------------------

		$('.tag_section_closer', $suggest_tags).click(function(){

			$suggest_tags.hide('fast', function(){
				if ($suggest_tags.css('display') == 'none' &&
					$top_tags.css('display') == 'none')
				{
					//$new_tags_section_name.hide('fast', function(){
					//	$new_tags_section_data.addClass('all_closed');
					//});
				}
			});

			$suggest_tags_button.show().fadeIn('fast');
		});

		$('.tag_section_closer', $top_tags).click(function(){

			$top_tags.hide('fast', function(){
				if ($suggest_tags.css('display') == 'none' &&
					$top_tags.css('display') == 'none')
				{
					//$new_tags_section_name.hide('fast', function(){
					//	$new_tags_section_data.addClass('all_closed');
					//});
				}
			});

			$top_tags_button.show().fadeIn('fast');
		});

		//--------------------------------------------
		//	suggest opener
		//--------------------------------------------

		$suggest_tags_button.click(function(e){
			$new_tags_section_data.removeClass('all_closed');

			//sets it hidden height for smooth animation
			$suggest_tags.css('height', getJQHeight($suggest_tags));

			$suggest_tags.slideDown('fast', function(){
				//blank the height for dynamic expansion
				$suggest_tags.css('height', '');
			});

			$suggest_tags_button.fadeOut('fast', function() {
				$suggest_tags_button.hide();
			});

			suggestTags(fieldID, getXID(fieldID));

			return preventDefault(e);
		});


		//--------------------------------------------
		//	top tags opener
		//--------------------------------------------

		$top_tags_button.click(function(e){
			$new_tags_section_data.removeClass('all_closed');

			//sets it hidden height for smooth animation
			$suggest_tags.css('height', getJQHeight($top_tags));

			$top_tags.slideDown('fast', function(){
				//blank the height for dynamic expansion
				$suggest_tags.css('height', '');
			});

			$top_tags_button.fadeOut('fast', function() {
				$top_tags_button.hide();
			});

			return preventDefault(e);
		});

		//--------------------------------------------
		//	suggest tags
		//--------------------------------------------

		$refresh_suggest.click(function(e) {
			//makes it a loading indicator
			$refresh_suggest.
				find('.refresh').hide().
			end().
				find('.loading').show();

			suggestTags(fieldID, getXID(fieldID), function(){
				//resets
				$refresh_suggest.
					find('.loading').hide().
				end().
					find('.refresh').show();
			});

			return preventDefault(e);
		});

		//--------------------------------------------
		//	autocomplete
		//--------------------------------------------

		var useResult = false;

		if (typeof $.fn.tag_autocomplete !== 'undefined')
		{
			$tag_input.tag_autocomplete(ssData.autocompleteURL[fieldID],	{
				multiple			: false,
				mustMatch			: false,
				autoFill			: false,
				cacheLength			: 0,
				selectFirst			: false,
				delay				: 300,
				multipleSeparator	: '||',
				extraParams			: {
					XID			: getXID(fieldID),
					current_tags: function() {
						return currentTags.join('||');
					}
				}
			}).tag_result(function(that, tagName){

				//this likes to return array results? :|
				if ($.isArray(tagName))
				{
					tagName = tagName[0];
				}

				//fieldID is named earlier in this $.ready statement
				addTag(fieldID, tagName);

				//remove val
				$tag_input.val('');

				//prevent normal from running
				useResult = true;
			});

			$('body').on('keydown', function(e){

				//did someone click enter?
				if ($(':focus').is($tag_input) && e.which == 13)
				{
					//this has to be done here or we lose it :/
					var $that		= $tag_input;
					var tagName		= $.trim($that.val());

					//need to keep this from firing before the tag_result bind
					setTimeout(function(){
						//did we use the result processor?
						if (useResult)
						{
							useResult = false;
						}
						//we are adding normally
						else if (tagName !== '')
						{
							//fieldID is named earlier in this $.ready statement
							addTag(fieldID, tagName);

							//remove val
							$tag_input.val('');
						}
					}, 50);

					//prevent those events!
					preventDefault(e);
					return false;
				}
			});//END $tag_input.tag_autocomplete
		}

		//--------------------------------------------
		//	remove tag (live for new additions)
		//--------------------------------------------

		$parent.delegate('.current_tag .ex', 'click', function(e){
			var $that		= $(this);
			var $thatParent	= $that.parent();
			var tagName		= $thatParent.attr('data-tag');

			//fieldID is named earlier in this $.ready statement
			removeTag(fieldID, tagName);

			$thatParent.remove();

			return preventDefault(e);
		});

		//--------------------------------------------
		//	add suggestions (live for new additions)
		//--------------------------------------------

		$parent.delegate('.suggest_tag', 'click', function(e){
			var $that		= $(this);
			var tagName		= $that.attr('data-tag');

			//fieldID is named earlier in this $.ready statement
			addTag(fieldID, tagName);

			//done automatically in addTag
			//$thatParent.remove();

			return preventDefault(e);
		});

		//--------------------------------------------
		//	add top tag
		//--------------------------------------------

		$('.top_tag', $parent).click(function(e){
			var $that		= $(this);
			var tagName		= $that.attr('data-tag');

			//fieldID is named earlier in this $.ready statement
			addTag(fieldID, tagName, function(){
				//hide plus
				$that.find('.plus').hide();

				//this is now used
				$that.addClass('used');
			});

			return preventDefault(e);
		});

		// -------------------------------------
		//	enable click event
		// -------------------------------------

		$explode_input_enable.click(function(e){
			var $that		= $explode_input_enable;

			//checkboxes and :checked have this
			//funny way of falling out of sync
			//if you do this miniscule settimeout
			//it forces everything thats in the event
			//queue to resolve before this fires
			setTimeout(function(){
				var isChecked	= $that.is(':checked');
				var $parent		= $that.closest('.tag_note');
				var $select		= $explode_delimiter;
				var $note		= $parent.find('.tag_note_contents');

				if (isChecked)
				{
					$note.removeClass('disabled');
					$select.attr('disabled', false);
				}
				else
				{
					$note.addClass('disabled');
					$select.attr('disabled', 'disabled');
				}
			},50);
		});

		// -------------------------------------
		//	explode delimiter
		// -------------------------------------

		$explode_delimiter.change(function(){
			var $that	= $explode_delimiter;
			var $parent	= $that.closest('.tag_note');
			var $note	= $parent.find('.tag_note_contents');
			var val		= $that.val();
			var label	= $('option:selected', $that).text();
			var delim	= ssData.delimiters[val];
			ssData['separator'][fieldID] = delim;

			$note.find('.tag_sep').html(label.toLowerCase());
		}).change();
	}
	//END set field events

	global.solspaceTag.setFieldEvents = setFieldEvents;

	// -------------------------------------
	//	transform support
	// -------------------------------------

	global.solspaceTag.transForm = '';

	function hasTransform()
	{
		var el = document.createElement('p');
		var has3d;
		var support;
		var transforms = {
			'webkitTransform'	: '-webkit-transform',
			'msTransform'		: '-ms-transform',
			'MozTransform'		: '-moz-transform',
			'transform'			: 'transform'
		};

		// Add it to the body to get the computed style.
		document.body.insertBefore(el, null);

		for (var t in transforms)
		{
			if (el.style[t] !== undefined)
			{
				el.style[t] = "translate3d(1px,1px,1px)";

				has3d = window.getComputedStyle(el).getPropertyValue(transforms[t]);

				if (has3d !== undefined && has3d.length > 0 && has3d !== "none")
				{
					support = true;
					global.solspaceTag.transForm = t;
					break;
				}
			}
		}

		document.body.removeChild(el);

		return support;
	}
	//END hasTransform

	//--------------------------------------------
	//	this will function for all field instances
	//--------------------------------------------

	$(function(){

		//webkit transform support
		hasTransform();

		//this has to wait for document ready
		//so all can be filled before linking
		ssData				= global.solspaceTag;
		ssData['dom']		= {};
		ssData['localIDs']	= {};

		global.solspaceTag.domReadyFired = true;

		//if the tag tab needs to be renamed
		if (ssData.tabName !== '')
		{
			$("#menu_tag a").html(ssData.tabName);
		}

		//get each field instance
		$('div.solspace_tag_group').each(function(){
			setFieldEvents($(this));
		});

	});	//END document ready

}(jQuery, window));