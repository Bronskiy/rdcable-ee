<h1><?=lang('edit_field_layout')?></h1>
<form id="save-layout-form" method="post" action="<?=$save_layout_url?>">
	<input type="hidden" name="csrf_token"	value="<?=$CSRF_TOKEN?>" />
	<input type="hidden" name="form_id" value="<?=$form_id?>" />
	<input type="hidden" name="save_for[]" value="just_me" />
	<fieldset class="col-group last">
		<div class="setting-field col w-16 last">
			<?=$entries_layout_select?>
		</div>
	</fieldset>
	<fieldset class="form-ctrls" style="margin-top:0px;">
			<input
				type="submit"
				name="submit"
				value="<?=lang('save')?>"
				class="btn" />
	</fieldset>
</form>


