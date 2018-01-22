<div id="export_dialog_holder">
	<div id="export_dialog">
		<h1><?=lang('export_entries')?></h1>
		<form id="export_form" action="<?=$export_url?>" method="POST" autocomplete="off">
			<input type="hidden" name="csrf_token" value="<?=$CSRF_TOKEN?>" />
			<input type="hidden" name="form_id" value="<?=$form_id?>" />
			<table style="margin-bottom:0;">
				<tbody>
					<tr>
						<td>
							<?=lang('export')?>
						</td>
						<td >
							<input 	id="export_shown_fields"	type="radio"
									name="export_fields"		value="shown"
									checked="checked"/>
							<label for="export_shown_fields">
								<?=lang('shown_fields')?>
							</label>
							&nbsp;
							&nbsp;
							<input 	id="export_all_fields" 	type="radio"
									name="export_fields" 	value="all" />
							<label for="export_all_fields">
								<?=lang('all_fields')?>
							</label>
						</td>
					</tr>
					<tr>
						<td>
							<?=lang('export_as')?>
						</td>
						<td>
							<input 	id="export_as_csv" 		type="radio"
									name="export_method" 	value="csv"
									checked="checked"/>
							<label for="export_as_csv">
								<?=lang('csv')?>
							</label>
							&nbsp;
							&nbsp;
							<input 	id="export_as_txt"		type="radio"
									name="export_method"	value="txt"/>
							<label for="export_as_txt">
								<?=lang('txt')?>
							</label>
							<?php
							
							?>
						</td>
					</tr>
					<tr>
						<td>
							<?=lang('format_dates')?>
						</td>
						<td>
							<input type="hidden" name="format_dates" />
							<input 	id="format_dates"	type="checkbox"
									name="format_dates"	value="y"
									checked="checked"/>
							<label for="format_dates">
								<?=lang('enable')?>
							</label>
						</td>
					</tr>
				</tbody>
			</table>
			<fieldset class="form-ctrls" style="margin-top:0px;">
					<input
						type="submit"
						name="submit"
						value="<?=lang('export')?>"
						class="btn" />
			</fieldset>
		</form>
	</div>
</div>