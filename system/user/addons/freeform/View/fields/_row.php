<?php if ($col_type == 'label') { ?>
	<a href="<?=$row['field_settings_link']?>"><?=$row['field_label']?></a>
	<br /><span class="meta-info">&mdash; <?=$row['field_name']?></span>
<?php } ?>

<?php if ($col_type == 'type') { ?>
	<?=$fieldtype_name?>
<?php } ?>

<?php if ($col_type == 'description') { ?>
	<?=$row['field_description']?>
<?php } ?>

<?php if ($col_type == 'manage') { ?>
	<div class="toolbar-wrap">
		<ul class="toolbar">
			<li class="edit"><a href="<?=$row['field_settings_link']?>" title="<?=lang('edit')?>"></a></li>
			<li class="copy"><a href="<?=$row['field_duplicate_link']?>" title="<?=lang('duplicate')?>"></a></li>
		</ul>
	</div>
<?php } ?>