<div class="solspace_tag_group" id="solspace_tag_field_<?=$field_id?>">
	<textarea style="display:none;" id="solspace_tag_ta_<?=$field_id?>"
			  name="<?=$field_name?>"><?=$hidden_tag_data?></textarea>

	<div class="solspace_tag_new_tags">
		<div class="tag_section_data">
	<?php if ($explode_separator):?>
			<div class="tag_note">
				<span class="tag_note_contents">
					<?=$lang_explode_input_on_separator_note?>
				<?php if ($enable_explode_controls):?>
					<div class="tag_clear"></div>
					<label><?=lang('select_delimiter')?></label>
				<?php endif;?>
				</span>
		<?php if ($enable_explode_controls):?>
				<?=form_dropdown('explode_delimiter', $delimiter_lang, $tag_separator_name)?>
				&nbsp;
				<label>
				<input
					type="checkbox"
					name="tag_explode_input_enable_<?=$field_id?>"
					checked="checked"
					value="y"
					/>
				<?=lang('enabled')?>
				</label>
		<?php else:?>
				<input
					type="checkbox"
					name="tag_explode_input_enable_<?=$field_id?>"
					value="y"
					checked="checked"
					style="display:none"
					/>
		<?php endif;?>
			</div>
			<div class="tag_clear"></div>
	<?php endif;?>
			<div class="solspace_tag_input_area">
				<div class="tag_error_dialog">
					<span class="notice">Error text</span>
				</div>
				<div>
					<input
						type="text"
						name="tag_input"
						value=""
						class="tag_input"
						placeholder="<?=lang('press_enter_after_each_tags')?>"
						/>
				</div>
				<div class="solspace_tag_current_tags tag_clear">
					<?php foreach ($current_tags as $tag):?>
						<div class="current_tag white_grad" data-tag="<?=str_replace('"', '&quot;', $tag)?>">
							<span class="ex"></span>
							<span class="tag_name"><?=$tag?></span>
						</div>
					<?php endforeach;?>
					<div class="tag_clear"></div>
				</div>
			<?php if ( ! $input_only):?>
				<div class="suggest_tags white_grad">
					<span class="glass"></span>
					<span class="tag_button_label"><?=lang('suggest_tags');?></span>
				</div>
				<div class="top_tags white_grad">
					<span class="star"></span>
					<span class="tag_button_label"><?=lang('top_tags');?></span>
				</div>
			<?php endif;?>
			</div>
			<div class="tag_clear"></div>
		</div>
		<div class="tag_clear"></div>
	</div>
<?php if ( ! $input_only):?>
	<div class="solspace_tag_suggest_tags">
		<span class="tag_section_closer ex"></span>
		<div class="tag_section_name">
			<div class="staticwrap">
				<span class="glass"></span>
				<?=lang('suggest_tags');?>
			</div>
		</div>
		<div class="tag_section_data">
			<div class="refresh_suggest_tags white_grad">
				<span class="refresh"></span>
				<span class="loading"></span>
			</div>
			<div class="tag_clear"></div>
		</div>
		<div class="tag_clear"></div>
	</div>

	<div class="solspace_tag_top_tags">
		<span class="tag_section_closer ex"></span>
		<div class="tag_section_name">
			<div class="staticwrap">
				<span class="star"></span>
				<?=lang('top_tags');?>
			</div>
		</div>
		<div class="tag_section_data">
			<?php foreach ($top_tags as $tag => $tag_count):?>
				<div class="top_tag white_grad" data-tag="<?=str_replace('"', '&quot;', $tag)?>">
					<span class="tag_count"><?=$tag_count?></span>
					<span class="plus"></span>
					<span class="tag_name"><?=$tag?></span>
				</div>
			<?php endforeach;?>
			<div class="tag_clear"></div>
		</div>
		<div class="tag_clear"></div>
	</div>
<?php endif;?>
</div>
<script type="text/javascript">
	(function(global){

		/*jslint ignore: start*/
		//defaults and tag sets for the external JS
		var data = global.solspaceTag			= global.solspaceTag || {};

		//global items (ok to overwrite)

		//secureFormHash
		data.secureFormHash						= '<?=$CSRF_TOKEN?>';
		//this might be localized to fields later
		data.tagLimit							= '<?=$tag_limit?>';

		data.langItems							= {
			'error'						: "<?=addslashes(lang('error'))?>",
			'tag_limit_reached'			: "<?=addslashes(lang('tag_limit_reached'))?>",
			'explode_separator_desc'	: "<?=addslashes(lang('error'))?>"
		};

		data.tabName							= '<?=$tab_name?>';
		data.delimiters							= <?=$delimiter_json?>;

		//per field items

		//current tags
		data['currentTags']						= data['currentTags'] || {};
		data['currentTags']['<?=$field_id?>'] 	= [<?php
			if(count($current_tags_escaped)):
				?>'<?=implode("','", $current_tags_escaped)?>'<?php
			endif;?>];
		//top tags
		data['topTags']							= data['topTags'] || {};
		data['topTags']['<?=$field_id?>'] 		= [<?php
			if(count($top_tags_escaped)):
				?>'<?=implode("','", $top_tags_escaped)?>'<?php
			endif;?>];
		//all open
		data['allOpen']							= data['allOpen'] || {};
		data['allOpen']['<?=$field_id?>']		= '<?=$all_open?>';
		//xids
		data['xids']							= data['xids'] || {};
		data['xids']['<?=$field_id?>']			= '<?=$CSRF_TOKEN?>';
		//auto complete
		data['autocompleteURL']					= data['autocompleteURL'] || {};
		data['autocompleteURL']['<?=$field_id?>']= '<?=$autocomplete_url?>';
		//suggest tags
		data['suggestTagsURL']					= data['suggestTagsURL'] || {};
		data['suggestTagsURL']['<?=$field_id?>']= '<?=$suggest_tags_url?>';
		//suggest fields
		data['suggestFields']					= data['suggestFields'] || {};
		data['suggestFields']['<?=$field_id?>'] = [<?php
			if(count($suggest_fields)):
				?>'<?=implode("','", $suggest_fields)?>'<?php
			endif;?>];
		//EXPLOOOOOOODDDEE!
		data['explodeSeparator']				= data['explodeSeparator'] || {};
		data['explodeSeparator']['<?=$field_id?>'] = <?=(
			$explode_separator ? 'true' : 'false'
		)?>;
		//separator
		data['separator']						= data['separator'] || {};
		data['separator']['<?=$field_id?>']		= '<?=(($tag_separator == "\n") ? '\n' : $tag_separator)?>';

		//if people load these via ajax, we need to set the events
		if (typeof 	global.solspaceTag.domReadyFired !== 'undefined' &&
					global.solspaceTag.domReadyFired == true)
		{
			global.solspaceTag.setFieldEvents("#solspace_tag_field_<?=$field_id?>");
		}
		/*jslint ignore: end*/
	})(window);
</script>