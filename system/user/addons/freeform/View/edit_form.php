<div class="box">
	<?php $this->embed('ee:_shared/form')?>
</div>
<script type="text/javascript">
	var Freeform = window.Freeform = window.Freeform || {};

	//jshint ignore:start
	Freeform.lang				= Freeform.lang || {};
	Freeform.lang.save			= '<?=addslashes(lang("save"))?>';
	Freeform.lang.continue		= '<?=addslashes(lang("continue"))?>';
	Freeform.lang.saveAndFinish	= '<?=addslashes(lang("save_and_finish"))?>';

<?php if ($addon_info['freeform_pro']):?>
	Freeform.pro = true;
<?php else:?>
	Freeform.pro = false;
<?php endif;?>
	//jshint ignore:end

	Freeform.fieldOrder = '<?=$field_order?>';
</script>