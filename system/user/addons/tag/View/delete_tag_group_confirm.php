<form action="<?=$form_url?>" method="post">
	<input type="hidden" name="<?=$csrf_hidden_name?>"	value="<?=$CSRF_TOKEN?>" />
	<?php foreach($tag_group_ids as $group_id) : ?>
		<input type="hidden" name="delete[]" value="<?=$group_id?>" />
	<?php endforeach;?>

	<p><strong><?=$lang_tag_group_delete_question?></strong></p>

	<p class='notice'><?=lang('action_can_not_be_undone');?></p>

	<p><input type="submit" name="submit" value="<?=lang('delete');?>" class='btn submit'  /></p>
</form>