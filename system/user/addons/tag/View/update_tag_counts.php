<?php $this->extend('_layouts/table_form_wrapper')?>
<h1><?=lang('update_tag_counts')?></h1>
<?php if ($show_update_msg):?>
<p class="notice"><?=lang('update_tag_count_from_upgrade_notice');?></p>
<?php endif;?>
<p><?=lang('update_tag_count_purpose');?></p>
<div id="progress">
	<hr/>
	<p id="current_entry_updating">
		<img id="progress_inidicator" src="<?=$addon_theme_url?>images/indicator.gif" />
		<?=lang('updating_counts_for_tag_')?><strong><span id="updating_entry_title"></span></strong>
	</p>
	<div class="ss_clearfix">
		<?=$this->embed('ee:_shared/progress_bar')?>
		<div id="update_percent">0%</div>
	</div>
	<div id="number_updated">
		<p>
			<?=lang('number_of_tags_updated')?>:
			<span class="notice">
				<span id="updates_completed">0</span>/
				<span id="total_to_update"><?=$total_tags_count?></span>
			</span>
		</p>

	</div>
	<div id="finished_message">
		<p>
			<?=lang('tag_recounts_complete')?>
		</p>
	</div>
</div>

<fieldset class="form-ctrls">
<button id="tag_field_sync" class='btn submit'><?=lang('update_all_tag_counts')?></button>
<button id="pause_tag_counts" class='btn submit'><?=lang('pause')?></button>
<button id="resume_tag_counts" class='btn submit'><?=lang('resume')?></button>
</fieldset>
<script type="text/javascript">
	(function(global){
		/*jshint ignore: start*/
		var Solspace = global.Solspace = global.Solspace || {};
		Solspace.updateList	= <?=$tag_json?>;
		Solspace.ajaxUrl	= '<?=$ajax_url?>';
		/*jshint ignore: end*/
	}(window));
</script>