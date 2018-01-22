!(function($, global){
	"use strict";

	$(function(){
		//--------------------------------------------
		//	site table switcher
		//--------------------------------------------

		var $siteTables = $('.siteTable');

		$siteTables.each(function(index, item){
			var $that		= $(this);
			var id			= $that.attr('id');
			var $select		= $that.find('.siteSwitcher:first');
			var site_id		= $select.attr('data-site-id');

			$select.change(function(){
				var new_site_id = $select.val();
				var id			= '#site_table_' + new_site_id;

				$(	'table[data-site-id="' + site_id + '"].siteTable, ' +
					'table[data-site-id="' + site_id + '"].channelTable').hide();

				//show new channel
				$(id).show();

				//reset this for later use
				$(this).val(site_id);

				//show first table for this item
				$('table[data-site-id="' + new_site_id + '"].channelTable:first').show();
			}).
			//need to reset in case someone refreshes the page
			val(site_id);
		});

		//--------------------------------------------
		//	channel switcher
		//--------------------------------------------

		var $channelTables = $('.channelTable');

		$channelTables.each(function(index, item){
			var $that		= $(this);
			var id			= $that.attr('id');
			var $select		= $that.find('.channelSwitcher:first');
			var site_id		= $select.attr('data-site-id');
			var channel_id	= $select.attr('data-channel-id');

			$select.change(function(){
				var new_channel_id	= $select.val();
				var id				= '#channel_table_' + site_id +
										'_' + new_channel_id;
				//hide all
				$channelTables.hide();

				//show new channel
				$(id).show();

				//reset this for later use
				$(this).val(channel_id);
			}).
			//need to reset in case someone refreshes the page
			val(channel_id);
		});
	});
}(jQuery, window));