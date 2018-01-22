
<table id="tag_statistics">
	<thead>
		<tr>
			<th style='width:5%;'><?=lang('tags');?></th>
			<th style='width:15%;'><?=lang('total_channel_entries_tagged');?></th>
			<th style='width:15%;'><?=lang('percent_channel_entries_tagged');?></th>

			<?php if ( ee()->db->table_exists('exp_gallery_entries') ) : ?>
				<th style='width:15%;'><?=lang('total_gallery_entries_tagged');?></th>
				<th style='width:15%;'><?=lang('percent_gallery_entries_tagged');?></th>
			<?php endif;?>

			<th style='width:25%;'><?=lang('top_five_tags');?></th>
		</tr>
	</thead>
	<tbody>
		<tr class='odd' >
			<td><?=$total_tags;?></td>
			<td><?=$total_channel_entries_tagged;?></td>
			<td><?=$percent_channel_entries_tagged;?>%</td>

			<td><?php
				$count = 0;
				foreach($top_five_tags as $tag_name => $tag_count):
					$count++;
				?>
				<?=$tag_name?> (<?=$tag_count?>)<?=(($count != count($top_five_tags)) ? ',' : '')?>
			<?php endforeach;?></td>
		</tr>
	</tbody>
</table>
