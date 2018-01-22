<?php if ($col_type == 'label') { ?>
	<a href="<?=$row['notification_settings_link']?>"><?=$row['notification_label']?></a>
	<br /><span class="meta-info">&mdash; <?=$row['notification_name']?></span>
<?php } ?>

<?php if ($col_type == 'subject') { ?>
	<?=$row['email_subject']?>
<?php } ?>

<?php if ($col_type == 'manage') { ?>
	<div class="toolbar-wrap">
		<ul class="toolbar">
			<li class="edit"><a href="<?=$row['notification_settings_link']?>" title="<?=lang('edit')?>"></a></li>
			<li class="copy"><a href="<?=$row['notification_duplicate_link']?>" title="<?=lang('duplicate')?>"></a></li>
		</ul>
	</div>
<?php } ?>