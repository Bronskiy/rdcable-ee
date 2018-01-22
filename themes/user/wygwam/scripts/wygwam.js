(function($) {


window.Wygwam;


/**
 * Wygwam
 */
Wygwam = function(id, config, defer) {

    // Allow initializing by a jQuery object that matched something
    if (typeof id == "object" && typeof id.is == "function" && id.is('textarea'))
    {
        this.$element = id;
    }
    else
    {
        this.$element = $('#' + id);
    }

    // No luck
    if (this.$element.length == 0)
    {
        return;
    }

	this.id = id;

    if (typeof config == "undefined")
    {
        config = this.$element.data('config');
    }
    this.config = (Wygwam.configs[config] || Wygwam.configs['default']);

    if (typeof defer == "undefined")
    {
        this.defer = this.$element.data('defer') == "y";
    }
    else
    {
        this.defer = defer;
    }

	if (this.defer) {
		this.showIframe();
	} else {
		this.initCKEditor();
	}
};


Wygwam.prototype = {

	/**
	 * Show Iframe
	 */
	showIframe: function() {
		var width = (this.config.width ? this.config.width.toString() : '100%'),
			height = (this.config.height ? this.config.height.toString() : '200'),
			css = (this.config.contentsCss ? this.config.contentsCss : Wygwam.themeUrl+'lib/ckeditor/contents.css'),
			$textarea = this.$element.hide();

		if (width.match(/\d$/)) width += 'px';
		if (height.match(/\d$/)) height += 'px';

		this.$iframe = $('<iframe class="wygwam" style="width:'+width+'; height:'+height+';" frameborder="0" />').insertAfter($textarea);

		var iDoc = this.$iframe[0].contentWindow.document,
			html = '<html>'
			     +   '<head>'
			     +     '<link rel="stylesheet" type="text/css" href="'+css+'" />'
			     +     '<style type="text/css">* { cursor: pointer !important; }</style>'
			     +   '</head>'
			     +   '<body>'
			     +     $textarea.val()
			     +   '</body>'
			     + '</html>';

		iDoc.open();
		iDoc.write(html);
		iDoc.close();

		$(iDoc).click($.proxy(this, 'initCKEditor'));
	},

	/**
	 * Init CKEditor
	 */
	initCKEditor: function() {
		if (this.$iframe) {
			this.$iframe.remove();
		}

		// Load in any custom config values
		if (CKEDITOR.editorConfig)
		{
			CKEDITOR.editorConfig(this.config);
		}

		CKEDITOR.replace(this.$element[0], this.config);
	}
}


Wygwam.configs = {};
Wygwam.assetIds = [];

/**
 * Load Assets Sheet
 */
Wygwam.loadAssetsSheet = function(params, filedir, kind) {
	var sheet = new Assets.Sheet({
		filedirs: (filedir == 'all' ? filedir : [filedir]),
		kinds: (kind == 'any' ? kind : [kind]),

		onSelect: function(files) {
			var cacheBustUrl = files[0].url + '?cachebuster:' + parseInt(Math.random() * 100, 10);
			CKEDITOR.tools.callFunction(params.CKEditorFuncNum, cacheBustUrl);

			if (files[0].id) {
				if ($.inArray(files[0].id, Wygwam.assetIds) == -1) {
					for (var instanceName in CKEDITOR.instances) break;
					var ckeditorContainerElem = CKEDITOR.instances[instanceName].container.$;
					var $form = $(ckeditorContainerElem).closest('form');

					$('<input type="hidden" name="wygwam_asset_ids[]"/>').val(files[0].id).appendTo($form);
					$('<input type="hidden" name="wygwam_asset_urls[]"/>').val(files[0].url).appendTo($form);

					Wygwam.assetIds.push(files[0].id);
				}
			}
		}
	});

	sheet.show();
};


/**
 * Load EE File Browser
 */
Wygwam.loadEEFileBrowser = function(params, directory, content_type) {
	// Set up the temporary increase of z-indexes.
	var modalZIndex = $('.modal-file').css('z-index'),
		overlayZindex = $('.overlay').css('z-index');

	$('.modal-file').css({'z-index': 10012});
	$('.overlay').css({'z-index': 10011});

	// Set up restoration of original z-indexes.
	var restoreZIndexes = function (){
		$('.modal-file').css({'z-index': modalZIndex});
		$('.overlay').css({'z-index': overlayZindex});
	};

	var $trigger = $('<trigger class="m-link filepicker" rel="modal-file" href="' + Wygwam.fpUrl + '"/>').appendTo('body');

	$trigger.FilePicker({
		callback: function(data, references)
		{
			var url = Wygwam.filedirUrls[data.upload_location_id] + data.file_name;
			CKEDITOR.tools.callFunction(params.CKEditorFuncNum, url);
			references.modal.find('.m-close').click();
			$('body').off('modal:close', '.modal-file', restoreZIndexes);
		}
	});

	$trigger.click();

	// Set up the listener to restore the z-indexes.
	$('body').on('modal:close', '.modal-file', restoreZIndexes);
};


})(jQuery);
