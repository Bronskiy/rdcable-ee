(function(global, $){
	//on ready
	$(function(){

		var Solspace = global.Solspace = global.Solspace || {};

		var updateList			= Solspace.updateList;
		var totalEntries 		= updateList.length;
		var currentEntryIndex	= 0;

		function ajaxUpdateTag(index, callback)
		{
			$.ajax({
				url 		: Solspace.ajaxUrl,
				dataType	: 'JSON',
				type		: 'POST',
				data		: {
					id : updateList[index].id,
					CSRF_TOKEN: EE.CSRF_TOKEN
				},
				error		: function (jqXHR, textStatus, errorThrown){
					console.log(jqXHR);
				},
				success		: function (data, textStatus, jqXHR)
				{
					callback();
				}
			});
		}

		var $start					= $('#tag_field_sync');
		var $pause					= $('#pause_tag_counts');
		var $resume					= $('#resume_tag_counts');
		var $update_percent			= $('#update_percent');
		var $updates_completed		= $('#updates_completed');
		var $total_to_update		= $('#total_to_update');
		var $updating_entry_title	= $('#updating_entry_title');
		var $progressbar			= $('#progressbar');
		var $progress_inidicator	= $('#progress_inidicator');
		var runNextUpdate			= true;

		var updateProgress = function(e)
		{
			var n = $('.progress-bar');
			var i = $(".progress", n);

			n.is(":not(:visible)") && n.show();

			i.css("width", e+"%");
		};
		//END updateProgress

		updateProgress(0);

		var initiateAjaxUpdate = function()
		{
			//did someone click pause?
			if ( ! runNextUpdate) return;

			if (currentEntryIndex < totalEntries)
			{
				//update progress
				var percent = Math.floor((currentEntryIndex/totalEntries) * 100);

				$update_percent.html(percent + '%');
				updateProgress(percent);
				$updates_completed.html(currentEntryIndex);
				$updating_entry_title.html(updateList[currentEntryIndex]['title']);

				ajaxUpdateTag(currentEntryIndex, initiateAjaxUpdate);

				//the function's inner code is asynchronous
				//so this should always call before an HTTP request ever finishes
				currentEntryIndex++;
			}
			else
			{
				$update_percent.html(100 + '%');
				updateProgress(100);
				$updates_completed.html(totalEntries);
				$('#current_entry_updating').hide();
				$pause.hide();
				$resume.hide();
				$pause.parent().hide();
				$('#finished_message').show();
			}
		};
		//END initiateAjaxUpdate


		// -------------------------------------
		//	start button
		// -------------------------------------

		$start.click(function(e){
			currentEntryIndex = 0;

			$('#progress').show();
			$('#current_entry_updating').show();
			$start.hide();
			$pause.show();

			initiateAjaxUpdate();

			e.preventDefault();
			return false;
		});

		// -------------------------------------
		//	pause and resume
		// -------------------------------------

		$pause.click(function(e){
			runNextUpdate = false;

			$progress_inidicator.hide();
			$pause.hide();
			$resume.show();
			$updating_entry_title.html('');

			e.preventDefault();
			return false;
		});

		$resume.click(function(e){
			runNextUpdate = true;

			$progress_inidicator.show();
			$pause.show();
			$resume.hide();

			initiateAjaxUpdate();

			e.preventDefault();
			return false;
		});
	});
}(window, jQuery));