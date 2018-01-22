<?php if ($multiple): ?>
<div data-field="<?=$field_name?>" data-settings='<?=$settings?>' class="col w-8 relate-wrap<?php if (empty($entries)) echo " empty"; ?>">
	<h4><?=lang('items_to_relate_with')?></h4>
<?php else: ?>
<div data-field="<?=$field_name?>" data-settings='<?=$settings?>' class="col w-16 relate-wrap<?php if (empty($entries) || empty($related)) echo " empty"; ?>">
	<h4><?=lang('item_to_relate_with')?></h4>
<?php endif; ?>
	<div class="relate-actions">
		<?php if (count($channels) > 1): ?>
		<div class="filters">
			<ul>
				<li>
					<a class="has-sub" href=""><?=lang('channel')?></a>
					<div class="sub-menu">
						<?php if (count($channels) > 9): ?><div class="scroll-wrap"><?php endif;?>
						<ul>
							<li><a href="" data-channel-id=""><?=lang('all_channels')?></a></li>
							<?php foreach($channels as $channel): ?>
								<li><a href="" data-channel-id="<?=$channel->channel_id?>"><?=$channel->channel_title?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php if (count($channels) > 9): ?></div><?php endif;?>
					</div>
				</li>
			</ul>
		</div>
		<?php endif; ?>
		<input class="relate-search" type="text" name="search" placeholder="<?=lang('search_available_entries')?>" data-ajax-validate="no">
	</div>
	<div class="scroll-wrap" data-template='<label class="choice block chosen relate-manage" data-entry-id="{entry-id}" data-search="{entry-title-lower}"><a href="" title="<?=lang('remove_relationship')?>" data-entry-id="{entry-id}"></a> {entry-title} <i>&mdash; {channel-title}</i></label>'>
		<?php
		// This input is just to keep track of what Grid namespacing, if any,
		// we should be using for dynamically-generated inputs ?>
		<input type="hidden" name="<?=$field_name?>[data][]" class="input-name" value="">
		<?php $chosen = NULL; ?>
			<div class="no-results<?php if ( ! empty($entries)) echo " hidden" ?>">
				<?=lang('no_entries_found')?>
				<?php if (count($channels) == 1): ?>
				<a class="btn action" href="<?=ee('CP/URL')->make('publish/create/' . $channels[0]->channel_id)?>" data-channel-id="<?=$channels[0]->channel_id?>" target="_blank"><?=lang('btn_create_new')?></a>
				<?php else: ?>
					<?php foreach($channels as $channel): ?>
						<a class="btn action hidden" href="<?=ee('CP/URL')->make('publish/create/' . $channel->channel_id)?>" data-channel-id="<?=$channel->channel_id?>" target="_blank"><?=lang('btn_create_new')?></a>
					<?php endforeach; ?>
				<div class="filters">
					<ul>
						<li>
							<a class="has-sub" href=""><?=lang('btn_create_new')?></a>
							<div class="sub-menu">
							<?php if (count($channels) > 9): ?><div class="scroll-wrap"><?php endif;?>
								<ul>
									<?php foreach($channels as $channel): ?>
										<li><a href="<?=ee('CP/URL')->make('publish/create/' . $channel->channel_id)?>"><?=$channel->channel_title?></a></li>
									<?php endforeach; ?>
								</ul>
							<?php if (count($channels) > 9): ?></div><?php endif;?>
							</div>
						</li>
					</ul>
				</div>
				<?php endif; ?>
			</div>
			<?php
		foreach ($entries as $entry):
			$class = 'choice block';
			$checked = FALSE;
			if (in_array($entry->entry_id, $selected))
			{
				$selected = array_diff($selected, array($entry->entry_id));
				$class = 'choice block chosen';
				$checked = TRUE;
				$chosen = $entry;
			}
		?>
		<label class="<?=$class?>" data-channel-id="<?=$entry->Channel->channel_id?>" data-channel-title="<?=htmlentities($entry->Channel->channel_title, ENT_QUOTES, 'UTF-8')?>" data-entry-title="<?=htmlentities($entry->title, ENT_QUOTES, 'UTF-8')?>">
			<?php
				if ($multiple)
				{
					echo form_checkbox('', $entry->entry_id, $checked);
				}
				else
				{
					echo form_radio($field_name.'[dummy][]', $entry->entry_id, $checked);
				}
			?>
			<?=htmlentities($entry->title, ENT_QUOTES, 'UTF-8')?> <i>&mdash; <?=htmlentities($entry->Channel->channel_title, ENT_QUOTES, 'UTF-8')?></i>
		</label>
		<?php endforeach; ?>
	</div>
	<?php if ( ! $multiple): ?>
		<?php if ( ! $chosen && ! empty($related)) $chosen = $related[0]; ?>
		<div class="relate-wrap-chosen">
			<?php if($chosen): ?>
			<label class="choice block chosen relate-manage">
				<a href="" title="<?=lang('remove_relationship')?>" data-entry-id="<?=$chosen->entry_id?>"></a> <?=htmlentities($chosen->title, ENT_QUOTES, 'UTF-8')?> <i>&mdash; <?=$chosen->Channel->channel_title?></i>
				<?=form_hidden($field_name.'[data][]', $chosen->entry_id)?>
			</label>
			<?php endif; ?>
			<label class="choice <?=($chosen) ? "hidden" : "block"?>">
				<div class="no-results"><?=lang('no_entry_related')?></div>
			</label>
		</div>
	<?php endif;?>
</div>
<?php if ($multiple): ?>
<div class="col w-8 relate-wrap<?php if ( ! count($related)) echo " empty"; ?> last">
	<h4><?=lang('items_related_to')?></h4>
	<div class="relate-actions">
		<input class="relate-search" name="search_related" type="text" placeholder="<?=lang('search_related_entries')?>" data-ajax-validate="no">
	</div>
	<div class="scroll-wrap">
		<?php if (count($related)): ?>
			<?php foreach ($related as $entry): ?>
			<label class="choice block chosen relate-manage" data-entry-id="<?=$entry->entry_id?>" data-search="<?=strtolower($entry->title)?>">
				<span class="relate-reorder"></span>
				<a href="" title="<?=lang('remove_relationship')?>" data-entry-id="<?=$entry->entry_id?>"></a> <?=htmlentities($entry->title, ENT_QUOTES, 'UTF-8')?> <i>&mdash; <?=$entry->Channel->channel_title?></i>
				<?=form_hidden($field_name.'[data][]', $entry->entry_id)?>
			</label>
			<?php endforeach; ?>
		<?php endif;?>
		<div class="no-results<?php if (count($related)): ?> hidden<?php endif ?>"><?=lang('no_entries_related')?></div>
	</div>
</div>
<?php endif; ?>
