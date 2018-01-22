<div class="list_holder_input">
<?php foreach ($list as $item):?>
	<div class="list_input">
		<input 	class="input" type="text"
				name="<?=$name?>[]" value="<?=$item?>" />
		<div class="freeform_delete_button"></div>
	</div>
<?php endforeach; ?>
	<div class="list_input">
		<input 	class="input" type="text"
				name="<?=$name?>[]" value="" />
		<div class="freeform_delete_button"></div>
	</div>
</div>