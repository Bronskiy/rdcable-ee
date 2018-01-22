<style type="text/css">
	#<?=$id_wrapper?> .insert_new_tag_holder {
		display:	none;
	}

	#<?=$id_wrapper?> .insert_new_tag_holder label{
		width: 		115px;
		display:	block;
		float:		left;
		margin-top:	3px;
	}

	#<?=$id_wrapper?> .cancel_new_group_link,
	#<?=$id_wrapper?> .new_group_link {
		float:		right;
	}
	#<?=$id_wrapper?> .new_tag_group_name,
	#<?=$id_wrapper?> .new_tag_group_short_name {
		width:50%;
	}

	button {
		cursor:pointer;
	}
</style>
<script type="text/javascript">
	var Solspace = window.Solspace || window.Solspace || {};
	Solspace.idWrapper = '<?=addslashes($id_wrapper)?>';
</script>
<div id="<?=$id_wrapper?>">
	<select class="tag_group_select" name="tag_group">
		<?php foreach($tag_groups as $group_id => $group_name):?>
		<option <?php if ($group_id == $current_group_id) {
			echo 'selected="selected"';
			}?> value="<?=$group_id?>"><?=$group_name?></option>
		<?php endforeach;?>
	</select>

	<button class="submit new_group_link btn" name="insert_new_tag_group"><?=lang('insert_new_tag_group')?></button>

	<span class="insert_new_tag_holder">
		<button class="submit cancel_new_group_link btn" name="cancel"><?=lang('cancel');?></button>
		<p><label><?=lang('new_group_name');?>: </label>
		<input type='text' name="new_tag_group_name" class="new_tag_group_name" maxlength="150" /></p>
		<p style="margin-bottom:5px"><label><?=lang('short_name');?>: </label>
		<input type='text' name="new_tag_group_short_name" class="new_tag_group_short_name" maxlength="150"/></p>
		<p><?=lang('field_settings_new_tag_group_desc')?></p>
	</span>
</div>