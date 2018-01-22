<?php $this->extend('_layouts/table_form_wrapper')?>
<h1><?=lang('tag_field_sync')?></h1>
<p class="subtext"><?=lang('tag_field_sync_desc')?></p>
<?php if (empty($total_entries_count)) { ?>
<p><?=lang('tag_field_sync_empty')?></p>
<?php } ?>
<div id="progress">
	<hr/>
	<p id="current_entry_updating">
		<img id="progress_inidicator" src="<?=$addon_theme_url?>images/indicator.gif" />
		<?=lang('updating_tag_fields_for_entry_')?><strong><span id="updating_entry_title"></span></strong>
	</p>
	<div class="ss_clearfix">
		<?=$this->embed('ee:_shared/progress_bar')?>
		<div id="update_percent">0%</div>
	</div>
	<div id="number_updated">
		<p>
			<?=lang('number_of_entries_updated')?>:
			<span class="notice">
				<span id="updates_completed">0</span>/
				<span id="total_to_update"><?=$total_entries_count?></span>
			</span>
		</p>

	</div>
	<div id="finished_message">
		<p>
			<?=lang('tag_fields_complete')?>
		</p>
	</div>
</div>
<?php if (! empty($total_entries_count)) { ?>
<fieldset class="form-ctrls">
	<button id="tag_field_sync" class='btn submit'><?=lang('sync_tags_fields')?></button>
	<button id="pause_tag_counts" class='btn submit'><?=lang('pause')?></button>
	<button id="resume_tag_counts" class='btn submit'><?=lang('resume')?></button>
</fieldset>
<?php } ?>
<script type="text/javascript">
	(function(global){
		/*jshint ignore: start*/
		var Solspace = global.Solspace = global.Solspace || {};
		Solspace.updateList	= <?=$entries_json?>;
		Solspace.ajaxUrl	= '<?=$ajax_url?>';
		/*jshint ignore: end*/
	}(window));
</script>