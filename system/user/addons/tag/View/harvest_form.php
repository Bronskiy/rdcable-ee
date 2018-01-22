<p class="notice"><?=lang('harvest_description');?></p>

<form action="<?=$form_url?>" method="post" id="tag_harvest_form">
	<input type="hidden" name="<?=$csrf_hidden_name?>"	value="<?=$CSRF_TOKEN?>" />

	<table 	border='0'  cellspacing='0' cellpadding='0'
			style='width:100%;'  id="solspaceTable" class='' >

		<thead>
			<tr>
				<th colspan="2">
				</th>
			</tr>
		</thead>

		<tbody>
			<tr class="<?=$caller->cycle('odd', 'even');?>">
				<td valign="top" style="width: 35%;">
					<label for="harvest_sources"><?=lang('harvest_sources');?></label>
					<div style="margin-top:10px;" class="subtext"><?=lang('harvest_sources_desc')?></div>
				</td>
				<td valign="top" style="width: 65%;">
					<select name='harvest_sources[]'
							id='harvest_sources'
							class='select'
							multiple='multiple'
							size="8"
							style="width:50%;"
							>
					<?php foreach ( $groups as $group => $group_label):?>
						<optgroup label="<?=$group_label?>">
						<?php foreach ( $options[$group] as $p => $label):?>
							<option value='<?=$group.'_'.$p?>'><?=$label?></option>
						<?php endforeach;?>
						</optgroup>
					<?php endforeach;?>
					</select>
				</td>
			</tr>
			<tr class="<?=$caller->cycle('odd', 'even');?>">
				<td valign="top" style="width: 35%;">
					<label for="tag_group"><?=lang('tag_group');?></label>
				</td>
				<td valign="top" style="width: 65%;">
					<select name="tag_group" id="tag_group" class="select">
						<?php foreach($tag_groups as $group_id => $group_name):?>
							<option value="<?=$group_id?>"><?=$group_name?></option>
						<?php endforeach;?>
					</select>
				</td>
			</tr>
			<tr class="<?=$caller->cycle('odd', 'even');?>">
				<td valign="top" style="width: 35%;">
					<label for="per_batch"><?=lang('per_batch');?></label>
				</td>
				<td valign="top" style="width: 65%;">
					<select name="per_batch" id="per_batch" class="select">
						<?php foreach($per_batch_options as $option):?>
							<option value="<?=$option?>" <?php
								echo (($option == 250) ? 'selected="selected" ' : '' );
							?>><?=$option?></option>
						<?php endforeach;?>
					</select>
				</td>
			</tr>
		</tbody>
	</table>

	<p class="right-pull-submit">
		<input type="submit" name="submit" value="<?=lang('tag_harvest');?>" class='btn submit'  />
	</p>
</form>


