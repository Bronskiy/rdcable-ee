<?php

class Tag_ft extends EE_Fieldtype
{
	private $tag_ob;

	public	$info	= array(
		'name'		=> 'Tag',
		'version'	=> ''
	);

	public $field_name	= 'default';
	public $field_id	= 'default';

	public $has_array_data = true;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 */

	public function __construct()
	{
		parent::__construct();

		$this->info = require 'addon.setup.php';

		ee()->lang->loadfile('tag');

		$this->field_id 	= isset($this->settings['field_id']) ?
								$this->settings['field_id'] :
								$this->field_id;
		$this->field_name 	= isset($this->settings['field_name']) ?
								$this->settings['field_name'] :
								$this->field_name;


		$this->field_name = 'field_id_' . $this->field_id;

		if (isset(ee()->cp)) {
			ee()->cp->add_to_head(
				'<link rel="stylesheet" type="text/css" href="' . URL_THIRD_THEMES . 'tag/css/solspace-fa.css">'
			);
		}
	}
	// END constructor


	// --------------------------------------------------------------------

	/**
	 * tag object setter. We dont want to set this in the constructor
	 * because updates hit it and it could make a mess.
	 *
	 * @access	private
	 * @return	object	tag object
	 */

	private function tob()
	{
		if ( ! is_object($this->tag_ob))
		{
			require_once  rtrim(__DIR__, '/') . '/mod.tag.php';

			$this->tag_ob = new Tag();
		}

		return $this->tag_ob;
	}
	//END tob()


	// --------------------------------------------------------------------

	/**
	 * get tag group
	 * if this has a tag group lets use it, otherwise this is the old style
	 * and we can just use default
	 *
	 * @access	protected
	 * @return	string
	 */

	protected function get_tag_group()
	{
		return (
			isset($this->settings['tag_group']) ?
				$this->settings['tag_group'] :
				1
		);
	}
	//END get_tag_group()


	// --------------------------------------------------------------------

	/**
	 * preprocess tag data
	 *
	 * @access	public
	 * @param	array 	data for preprocessing
	 * @return	string
	 */

	/*public function pre_process($data)
	{
		return $data;
	}*/
	//END pre_process


	// --------------------------------------------------------------------

	/**
	 * replace tag pair data
	 *
	 * @access	public
	 * @param	array 	data for preprocessing
	 * @param	array 	tag params
	 * @param	string 	tagdata
	 * @return	string	processed tag data
	 */

	public function replace_tag($data, $params = array(), $tagdata = false)
	{
		if ( ! isset(ee()->TMPL))
		{
			ee()->load->library('template', null, 'TMPL');
		}

		if ( ! isset(ee()->TMPL->tagparams))
		{
			ee()->TMPL->tagparams = array();
		}

		$this->tob();

		//save old
		$old_tagdata			= ee()->TMPL->tagdata;
		$old_params				= ee()->TMPL->tagparams;

		if (empty($tagdata))
		{
			$tagdata = '{tag}, ';

			if ( ! isset($params['backspace']))
			{
				$params['backspace'] = '2';
			}
		}

		//replace to trick tag :D
		ee()->TMPL->tagdata		= $tagdata;

		ee()->TMPL->tagparams	= array_merge(
			array(
				'entry_id'		=> $this->row['entry_id'],
				'tag_group_id'	=> $this->get_tag_group()
			),
			$params
		);

		//better workflow preview mode?
		if (ee()->input->get('bwf_dp') !== false)
		{
			$return	= $this->tag_ob->tags(true, $data);
		}
		else
		{
			$return	= $this->tag_ob->tags();
		}

		//reset
		ee()->TMPL->tagdata		= $old_tagdata;
		ee()->TMPL->tagparams	= $old_params;

		return $return;
	}
	//END replace_tag


	// --------------------------------------------------------------------

	/**
	 * Display Field Settings
	 *
	 * allows adding of rows to the displayed table
	 * this table api is just weird
	 *
	 * @access	public
	 * @param	array 	$settings
	 */

	public function display_settings($settings)
	{
		ee()->cp->load_package_js('tag_group_settings');

		$settings = array(
			'tag' => array(
				'label'		=> 'tag',
				'group'		=> 'tag',
				'settings' => array(
					array(
						'title' => 'tag_group',
						'desc' => 'tag_group_desc',
						'fields' => array(
							'tag_group' => array(
								'type' => 'html',
								'content' => $this->tob()->view(
									'tag_group_settings',
									 array(
										'tag_groups'				=> $this->tob()->model('Data')->get_tag_groups(),
										'current_group_id'			=> isset($settings['tag_group']) ?
																		$settings['tag_group'] :
																		$this->get_tag_group(),
										'lang_insert_new_tag_group'	=> lang('insert_new_tag_group'),
										'lang_new_tag_group_name'	=> lang('new_tag_group_name'),
										'lang_cancel'				=> lang('cancel'),
										'lang_new_group_name'		=> lang('new_group_name'),
										'lang_short_name'			=> lang('short_name'),
										'id_wrapper'				=> 'ss_tag_field'
									),
									true
								)
								//end 'content' => $this->tob()->view(
							)
							//end 'tag_group' => array(
						)
						//END 'fields' => array(
					),
					//end 'settings' => array( [0=>] array(

					//all open
					array(
						'title' => 'all_open',
						'desc' => 'all_open_desc',
						'fields' => array(
							'all_open' => array(
								'type' => 'yes_no',
								'value' => (
									isset($settings['all_open']) AND
									$settings['all_open'] == 'yes'
								) ? 'yes' : 'no'
							)
						)
					),
					//end array

					//suggest from
					array(
						'title' => 'suggest_from',
						'desc' => 'suggest_from_desc',
						'fields' => array(
							'suggest_from' => array(
								'type' => 'radio',
								'choices' => array(
									'all'			=> lang('all_groups'),
									'this_group'	=> lang('this_group')
								),
								'value' => (
									isset($settings['suggest_from']) AND
									$settings['suggest_from'] == 'all'
								) ? 'all' : 'this_group'
							)
						)
					),
					//end array

					//Suggest tags from group or all groups
					array(
						'title' => 'top_tag_limit',
						'desc' => 'top_tag_limit_desc',
						'fields' => array(
							'top_tag_limit' => array(
								'type' => 'text',
								'value' => (
									isset($settings['top_tag_limit']) ?
									$settings['top_tag_limit'] :
									5
								),
								'id'		=> 'top_tag_limit',
							)
						)
					)
					//end 'settings' => array(  array(
				)
				//end 'settings' => array(
			),
			//end 'tag' => array(
		);
		//end $settings = array(

		return $settings;
	}
	//END display_settings()


	// --------------------------------------------------------------------

	/**
	 * save_settings
	 * @access	public
	 * @return	string
	 */

	public function save_settings($data)
	{
		$this->tob();

		//check tag group
		$tag_group_id = 1;

		//new group id?
		$newTagGroupName = ee()->input->get_post('new_tag_group_name');
		$newTagGroupName = trim($newTagGroupName);

		if ($newTagGroupName && !AJAX_REQUEST)
		{
			$tag_group_name = $newTagGroupName;
			$tag_group_short_name = ee()->input->get_post('new_tag_group_short_name');

			//lets make sure it worked
			$newGroupId = $this->tag_ob->model('Data')->insert_new_tag_group($tag_group_name, $tag_group_short_name);
			if ($newGroupId) {
				$tag_group_id = $newGroupId;
			} else {
				return $this->tob()->show_error(lang('tag_group_name_taken'));
			}
		} else {
			$tagGroup = ee()->input->get_post('tag_group');

			if (is_numeric($tagGroup) && $tagGroup > 0) {
                $tag_group_id = $tagGroup;
            }
		}

		return array(
			'all_open'		=> ($this->tag_ob->check_yes(ee()->input->get_post('all_open')) ? "yes" : "no"),
			'suggest_from'	=> (ee()->input->get_post('suggest_from') === 'all' ? "all" : "group"),
			'tag_group'		=> $tag_group_id,
			'field_name'	=> ee()->input->get_post('field_name'),
			'top_tag_limit'	=> (ctype_digit((string) ee()->input->get_post('top_tag_limit')) ?
									ee()->input->get_post('top_tag_limit') : '5')
		);
	}
	//END save_settings()


	// --------------------------------------------------------------------

	/**
	 * displays field for publish/saef
	 *
	 * @access	public
	 * @param	string	$data	any incoming data from the channel entry
	 * @return	string	html output view
	 */

	public function display_field($data)
	{
		$this->field_name = 'field_id_' . $this->field_id;
		$output = "";

		$this->tob();

		// --------------------------------------------
		//  Add in our JavaScript/CSS
		// --------------------------------------------

		$ac_js		= $this->tag_ob->model('Data')->tag_field_autocomplete_js();

		$tag_css 	= $this->tag_ob->model('Data')->tag_field_css();

		$tag_js		= $this->tag_ob->model('Data')->tag_field_js();

		$front_css 	= $this->tag_ob->model('Data')->tag_front_css();

		$ss_cache	=& ee()->session->cache['solspace'];

		//prevent double loading in case this is used more than once
		//jquery autocomplete js
		if ( ! isset($ss_cache['scripts']['jquery']['tag_autocomplete']))
		{
			if (isset(ee()->cp) && is_object(ee()->cp))
			{
				//ee()->cp->add_to_head($ac_css);
				ee()->cp->add_to_foot($ac_js);
			}
			else
			{
				$output .= $ac_js . "\n";
			}

			$ss_cache['scripts']['jquery']['tag_autocomplete'] = true;
		}

		//jquery autocomplete js
		if ( ! isset($ss_cache['scripts']['tag']['field']))
		{
			if (isset(ee()->cp) AND is_object(ee()->cp))
			{
				ee()->cp->add_to_head($tag_css);
				ee()->cp->add_to_foot($tag_js);
			}
			else
			{
				$output .= $tag_css . "\n" . $tag_js . "\n" . $front_css . "\n";
			}

			$ss_cache['scripts']['tag']['field'] = true;
		}


		//--------------------------------------------
		//	views widgets, whatever
		//--------------------------------------------

		$field_data = array(
			'field_data' 	=> $data,
			'field_name'	=> ($this->field_name == 'default') ?
									'tag_f' :
									$this->field_name,
			'field_id'		=> $this->field_id,
			'tag_group_id'	=> $this->get_tag_group(),
			'all_open'		=> (isset($this->settings['all_open']) ?
									$this->settings['all_open'] : 'no'),
			'top_tag_limit' => (isset($this->settings['top_tag_limit']) ?
									$this->settings['top_tag_limit'] : 5),
			'suggest_from'	=> (isset($this->settings['suggest_from']) ?
									$this->settings['suggest_from'] : 'group')
		);

		//entry_id
		if (isset($this->content_id))
		{
			$field_data['entry_id'] = $this->content_id;
		}

		$output 	.= $this->tag_ob->field_type_widget($field_data);

		return $output;
	}
	// END display_field()


	// --------------------------------------------------------------------

	/**
	 * delete. gets called when entries are deleted
	 *
	 * @access	public
	 * @param	array	$ids ids of the entries being deleted
	 * @return	null
	 */

	public function delete($ids)
	{
		$this->tob()->delete($ids);
	}
	//ENd delete


	// --------------------------------------------------------------------

	/**
	 * post_save. we arent using the intial save() because it doesn't
	 * have the entry id available yet, so it's somewhat useless to us here
	 *
	 * @access	public
	 * @param	string	$data	any incoming data from the channel entry
	 * @return	null	html output view
	 */

	public function post_save($data)
	{
		$this->field_name = 'field_id_' . $this->field_id;
		$this->tob();

		$entry_id = ($this->content_id);

		$this->tag_ob->site_id			= ee()->config->item('site_id');
		$this->tag_ob->entry_id			= $entry_id;
		$this->tag_ob->str				= $data;
		$this->tag_ob->from_ft			= true;
		$this->tag_ob->field_id			= $this->field_id;
		$this->tag_ob->tag_group_id		= $this->get_tag_group();
		$this->tag_ob->type				= 'channel';
		//everything is stored hidden as newline separation
		$this->tag_ob->separator_override = 'newline';

		$this->tag_ob->parse();

		return;
	}
	//END post_save


	// --------------------------------------------------------------------

	/**
	 * Update
	 *
	 * This is required for EE to update fieldtype version numbers.
	 *
	 * @access	public
	 * @param	string $version		current version number coming from EE
	 * @return	boolean				should EE update the version number.
	 */

	public function update($version)
	{
		return ($version != $this->info['version']);
	}
	//END update

}
// END Tag_ft class

// End of file ft.tag.php
