<form action="<?=$base_uri.AMP.'method=delete_bad_tag';?>" method="post">

	<input type="hidden" name="<?=$csrf_hidden_name?>"	value="<?=$CSRF_TOKEN?>" />
	<?php foreach($tag_ids as $tag_id) : ?>
		<input type="hidden" name="delete[]" value="<?=$tag_id?>" />
	<?php endforeach;?>
	<p><strong><?=$bad_tag_delete_question?></strong></p>
	<p class='notice'><?=lang('action_can_not_be_undone');?></p>
	<p><input type="submit" name="submit" value="<?=lang('remove_bad_tags');?>" class='btn submit'  /></p>
</form>
