<div class="value_label_holder">
	<div class="value_label_header">
		<span class="value_label_header_sub">
			<?=lang('param_name')?>
		</span>
		<span class="value_label_header_sub">
			<?=lang('param_value')?>
		</span>
	</div>
<?php
	$counter = 0;
	if ( ! empty($param_data)):
		foreach($param_data as $param => $value):
?>
	<div class="value_label_holder_input">
		<input 	type="text"
				name="list_param_holder_input[<?=$counter?>]"
				value="<?=form_prep($param)?>"/>
		<input 	type="text"
				name="list_value_holder_input[<?=$counter?>]"
				value="<?=form_prep($value)?>"/>
		<div class="freeform_delete_button"></div>
	</div>
<?php
			$counter++;
		endforeach;
	endif;
?>
	<div class="value_label_holder_input">
		<input 	type="text"
				name="list_param_holder_input[<?=$counter?>]" />
		<input 	type="text"
				name="list_value_holder_input[<?=$counter?>]" />
		<div class="freeform_delete_button"></div>
	</div>
</div>