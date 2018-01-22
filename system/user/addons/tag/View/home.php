<?php $this->extend('_layouts/table_form_wrapper'); ?>

<?php if ($tags_in_group):?>
	<h2><?=lang('viewing_tags_for_group');?>: <strong><?=$tag_group_name?></strong> <a style="float:right" href="<?=$base_uri?>"><?=lang('view_tags_in_all_groups');?></a></h2>
	<div class='clearfix'>&nbsp;</div>
<?php endif;?>

<?=$caller->view('statistics', NULL, true)?>


<?=$caller->view('browse', NULL, true)?>

<!-- using $this and not $caller on purpose here -->
<?=$this->embed('ee:_shared/table', $tag_table)?>