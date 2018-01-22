<form action="<?=$base_uri.AMP.'method=process_harvest';?>" method="post">
	<input type="hidden" name="<?=$csrf_hidden_name?>"	value="<?=$CSRF_TOKEN?>" />
	<?php foreach($hidden_fields as $fields) : ?>
		<input type="hidden" name="<?=$fields[0]?>" value="<?=$fields[1]?>" />
	<?php endforeach;?>

	<p>
		<input type="submit" name="submit" value="<?php echo str_replace(array('%batch%', '%total%'), array($batch, $total), lang('tag_process_batch_of'));?>" class='btn submit'  />
	</p>

</form>
