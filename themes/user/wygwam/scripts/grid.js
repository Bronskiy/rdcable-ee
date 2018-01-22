(function($) {


Wygwam.gridColConfigs = {};

var newGridRowCount = 0;
/**
 * Display
 */
var onDisplay = function(cell){

	var rowId = "";
	if (cell.data('row-id'))
	{
		rowId = cell.data('row-id');
	}
	else
	{
		rowId = 'new_row_' + ++newGridRowCount;
	}

	var $textarea = $('textarea', cell),
		config = Wygwam.gridColConfigs['col_id_' + cell.data('column-id')],
		id = cell.parents('.grid_field_container').attr('id')+'_'+rowId+'_'+cell.data('column-id')+'_'+Math.floor(Math.random()*100000000);

	id = id.replace(/\[/, '_').replace(/\]/, '');

	$textarea.attr('id', id);

	new Wygwam(id, config[0], config[1], cell);
};

Grid.bind('wygwam', 'display', onDisplay);

/**
 * Before Sort
 */
Grid.bind('wygwam', 'beforeSort', function(cell){
	var $textarea = $('textarea', cell),
		$iframe = $('iframe:first', cell);

	// has CKEditor been initialized?
	if (! $iframe.hasClass('wygwam')) {

		// Make a clone of the editor DOM
		$(cell).data('ckeClone', cell.children('.cke').clone());

		// save the latest HTML value to the textarea
		var id = $textarea.attr('id'),
			editor = CKEDITOR.instances[id];

		editor.updateElement();

		// destroy the CKEDITOR.editor instance
		editor.destroy();

		// make it look like nothing happened
		$textarea.hide();
		$(cell).data('ckeClone').appendTo(cell);
	}
});

/**
 * After Sort
 */
Grid.bind('wygwam', 'afterSort', function(cell) {
	if (typeof $(cell).data('ckeClone') != 'undefined')
	{
		$(cell).data('ckeClone').remove();
		$(cell).data('ckeClone', null);
	}
	onDisplay(cell);
});


})(jQuery);
