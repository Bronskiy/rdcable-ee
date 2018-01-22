<table id="browse_tags">
	<thead>
		<tr>
			<th style="width: 50%; text-align:left;">
				<?=lang('browse_tags');?>
			</th>
			<th style="text-align:right; border-left:none;">
				<input
					type="text"
					size="20"
					style="width:50%;"
					id="tag_search_keywords"
					name="tag_search_keywords"
					class="tag_search_keywords"
					placeholder="<?=lang('search_tags')?>"
					value="<?=$caller->output(ee()->input->post('tag_search_keywords'));?>"
					/>
				<input name="tag_search_button" type="submit" value="<?=lang('search');?>" class='btn submit' />
			</th>
		</tr>
	</thead>

	<tbody>
		<?php if (count($tags_by_alpha) == 0) : ?>
			<tr class='odd' >
				<td colspan="2"><?=lang('no_tags_found');?></td>
			</tr>
		<?php else :?>
			<tr class='odd'>
				<td colspan="2" style="padding:0;">
					<div class="alpha-tag-box">
						<a href='<?=$base_alpha_url;?>'>
							<?=lang('all_tags');?><span class="small">(<?=$total_tags;?>)</span>
						</a>
					</div>
			<?php foreach($tags_by_alpha as $letter => $count) : ?>
					<div class="alpha-tag-box">
							<a href='<?=$base_alpha_url;?>&alpha=<?=$letter;?>'
								title="<?=lang('browse_tags_by').' '.$letter;?>">
							<?php echo strtoupper($letter);?><span class="small">(<?=$count?>)</span>
						</a>
					</div>
			<?php endforeach; ?>
					<div style="clear:both;"></div>
				</td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>