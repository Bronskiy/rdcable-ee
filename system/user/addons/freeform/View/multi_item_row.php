<!-- start list section, prefix: '<?=$pre?>' -->

<!-- List type choices -->
<div id="<?=$pre?>_list_types" class="ss_clearfix push_bottom">
	<input type="hidden" id="<?=$pre?>_list_type" name="<?=$pre?>_list_type" value="<?=$list_setting?>"/>
	<label for="<?=$pre?>_list_type" class="pill_label"><?=lang('list_type')?>:</label>

	<select name="<?=$pre?>_list_type">
		<option value="list"<?=$list_setting=="list" ? " selected" : ""?>>
			<?=lang('list')?>
		</option>
		<option value="value_label"<?=$list_setting=="value_label" ? " selected" : ""?>>
			<?=lang('value_label_list')?>
		</option>
		<option value="channel_field"<?=$list_setting=="channel_field" ? " selected" : ""?>>
			<?=lang('load_from_channel_field')?>
		</option>
		<option value="nld_textarea"<?=$list_setting=="nld_textarea" ? " selected" : ""?>>
			<?=lang('nld_textarea')?>
		</option>
	</select>
</div>

<!-- List type data selection -->
<div id="<?=$pre?>_option_holder" class="field_option_holder">

	<!-- single word list -->
	<div id="<?=$pre?>_type_list_holder">
		<p class="subtext"><?=lang('type_list_desc')?></p>
<?php if ($list_setting == 'list' AND ! empty($field_list_items)):?>
	<?php foreach ($field_list_items as $list_item):?>
		<div class="list_holder_input">
			<input type="text" name="<?=$pre?>_list_holder_input[]" value="<?=$list_item?>"/>
			<div class="freeform_delete_button"></div>
		</div>
	<?php endforeach;?>
<?php endif;?>
		<div class="list_holder_input">
			<input type="text" name="<?=$pre?>_list_holder_input[]" />
			<div class="freeform_delete_button"></div>
		</div>
	</div>

	<!-- key value list -->
	<div id="<?=$pre?>_type_value_label_holder">
		<p class="subtext"><?=lang('type_value_label_desc')?></p>
		<div class="value_label_header">
			<span class="value_label_header_sub">
				<?=lang('value')?>
			</span>
			<span class="value_label_header_sub">
				<?=lang('label')?>
			</span>
		</div>
<?php $counter = 0;
	if ($list_setting == 'value_label' AND ! empty($field_list_items)):?>
	<?php foreach ($field_list_items as $list_key => $list_value):?>
		<div class="value_label_holder_input">
			<input 	type="text"
					name="<?=$pre?>_list_value_holder_input[<?=$counter?>]"
					value="<?=$list_key?>"/>
			<input 	type="text"
					name="<?=$pre?>_list_label_holder_input[<?=$counter?>]"
					value="<?=$list_value?>"/>
			<div class="freeform_delete_button"></div>
		</div>
	<?php $counter++;
		endforeach;?>
<?php endif;?>
		<div class="value_label_holder_input">
			<input 	type="text"
					name="<?=$pre?>_list_value_holder_input[<?=$counter?>]" />
			<input 	type="text"
					name="<?=$pre?>_list_label_holder_input[<?=$counter?>]" />
			<div class="freeform_delete_button"></div>
		</div>
	</div>

	<!-- channel id list -->
	<div id="<?=$pre?>_type_channel_field_holder">
		<p class="subtext"><?=lang('channel_field_list_desc')?></p>
		<select id="<?=$pre?>_channel_field" name="<?=$pre?>_channel_field">
	<?php foreach ($channel_field_list as $channel_title => $fields ):?>
		<?php if ( ! empty($fields) AND is_array($fields)):?>
			<optgroup label="<?=$channel_title?>">
		<?php  foreach ($fields as $field_value => $field_title):?>
				<option value="<?=$field_value?>" <?php
					if ($list_setting == 'channel_field' AND
						$field_value  == $field_list_items)
					{
						echo 'selected="selected"';
					}
				?>><?=$field_title?></option>
		<?php endforeach?>
			</optgroup>
		<?php endif;?>
	<?php endforeach;?>
		</select>
	</div>

	<!-- newline delimited textarea -->

	<div id="<?=$pre?>_type_nld_textarea_holder">
		<p class="subtext"><?=lang('type_nld_textarea_desc')?></p>
		<textarea rows="6" name="<?=$pre?>_list_nld_textarea_input"><?php
			if ($list_setting == 'nld_textarea')
			{
				echo $field_list_items;
			}
		?></textarea>
	</div>
</div>

<script type="text/javascript">
	if (typeof jQuery != 'undefined')
	{
		jQuery(function(){
			Freeform.setupMultiRowDelegate('<?=$list_setting?>', '<?=$pre?>');
		});
	}
	else
	{
		//since we dont have access to jQuery at this point in the page
		document.addEventListener("DOMContentLoaded", function(event) {
			Freeform.setupMultiRowDelegate('<?=$list_setting?>', '<?=$pre?>');
		});
	}
</script>

<!-- end list section, prefix: '<?=$pre?>_' -->
