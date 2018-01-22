<?php if ($col_type == 'label') { ?>
	<a href="<?=$row['form_settings_link']?>"><?=$row['form_label']?></a>
	<br /><span class="meta-info">&mdash; <?=$row['form_name']?></span>
<?php } ?>

<?php if ($col_type == 'submissions') { ?>
	<span class="form_submissions<?php if($row['submissions_count'] == 0):?> zero<?php endif;?>">
		<a href="<?=$row['form_submissions_link']?>">
			<?=lang('submissions')?> (<?=$row['submissions_count']?>)
		</a>
	</span>
<?php } ?>

<?php if ($col_type == 'moderate') { ?>
	<span class="form_moderate<?php if($row['moderate_count'] == 0):?> zero<?php endif;?>">
		<a href="<?=$row['form_moderate_link']?>">
			<?=lang('moderate')?> (<?=$row['moderate_count']?>)
		</a>
	</span>
<?php } ?>

<?php if ($col_type == 'manage') { ?>
	<div class="toolbar-wrap">
		<ul class="toolbar">
			<?php if ($row['has_composer']) { ?>
			<li class="composer"><a href="<?=$row['form_edit_composer_link']?>" title="<?=lang('composer')?>"></a></li>
			<?php } ?>
			<li class="edit"><a href="<?=$row['form_settings_link']?>" title="<?=lang('edit')?>"></a></li>
			<li class="copy"><a href="<?=$row['form_duplicate_link']?>" title="<?=lang('duplicate')?>"></a></li>
		</ul>
	</div>
<?php } ?>