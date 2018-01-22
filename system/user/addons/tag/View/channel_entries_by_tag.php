<?php $this->extend('_layouts/table_form_wrapper'); ?>


<!-- using $this and not $caller on purpose here -->
<?=$this->embed('ee:_shared/table', $entries_table)?>