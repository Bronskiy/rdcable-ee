<?php

use Solspace\Addons\Tag\Library\AddonBuilder;
use EllisLab\ExpressionEngine\Library\CP\Table;

class Tag_mcp extends AddonBuilder
{
	protected $row_limit		= 50;
	protected $member_id		= 0;
	protected $entry_id			= '';
	protected $pref_id			= '';
	protected $tag_id			= '';

	//private

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct()
	{
		parent::__construct('module');

		// --------------------------------------------
		//  Module Menu Items
		// --------------------------------------------

		$this->set_nav(array(
			'manage_tags'		=> array(
				'link'  => $this->base,
				'title' => lang('manage_tags')
			),
			'manage_bad_tags'	=> array(
				'link'  => $this->mcp_link(array(
					'method' => 'manage_bad_tags'
				)),
				'title' => lang('manage_bad_tags'),
				'sub_list'	=> array(
					'add_bad_tags' => array(
						'link' => $this->mcp_link(array('method' => 'add_bad_tags_form')),
						'title' => lang('add_bad_tags'),
					)
				)
			),
			'tag_groups'			=> array(
				'link'  => $this->mcp_link(array(
					'method' => 'tag_groups'
				)),
				'title' => lang('tag_groups'),
			),
			'utilities'				=> array(
				'title' => lang('utilities'),
				'sub_list'	=> array(
					'update_tag_counts' => array(
						'link' => $this->mcp_link(array('method' => 'update_tag_counts')),
						'title' => lang('update_tag_counts'),
					),
					'tag_field_sync' => array(
						'link' => $this->mcp_link(array('method' => 'tag_field_sync')),
						'title' => lang('tag_field_sync'),
					)
				)
			),
			'preferences'		=> array(
				'link'  => $this->mcp_link(array(
					'method' => 'preferences'
				)),
				'title' => lang('preferences')
			),
			'demo_templates'		=> array(
				'link'  => $this->mcp_link(array(
					'method' => 'code_pack'
				)),
				'title' => lang('demo_templates'),
			),
            'resources'      => array(
                'title'    => lang('tag_resources'),
                'sub_list' => array(
                    'product_info'  => array(
                        'link'     => 'https://solspace.com/expressionengine/tag',
                        'title'    => lang('tag_product_info'),
                        'external' => true,
                    ),
                    'documentation' => array(
                        'link'     => $this->docs_url,
                        'title'    => lang('tag_documentation'),
                        'external' => true,
                    ),
                    'support'       => array(
                        'link'     => 'https://solspace.com/expressionengine/support',
                        'title'    => lang('tag_official_support'),
                        'external' => true,
                    ),
                ),
			),
		));


		// --------------------------------------------
		//  Sites
		// --------------------------------------------

		$this->cached_vars['sites']	= array();

		foreach($this->make('Data')->get_sites() as $site_id => $site_label)
		{
			$this->cached_vars['sites'][$site_id] = $site_label;
		}

		ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . URL_THIRD_THEMES . 'tag/css/solspace-fa.css">');
	}

	// END __construct()


	// --------------------------------------------------------------------

	/**
	 *	The Main CP Index Page
	 *
	 *	@access		public
	 *	@param		string		$message - That little message display thingy
	 *	@return		string
	 */

	public function index($message = '')
	{
		//--------------------------------------------
		//	message
		//--------------------------------------------

		$this->prep_message($message, true, true);

		//--------------------------------------------
		//	tag group?
		//--------------------------------------------

		$tags_in_group = false;

		$tag_group_id = $this->get_post_or_zero('tag_group_id');

		if ($tag_group_id > 0)
		{
			//cached result
			$tags_in_group = $this->model('Data')->get_tag_ids_by_group_id(
				$tag_group_id
			);

			//default name in case people are playing around or some crap gets force deleted.
			$tag_group_name = lang('undefined_tag_group');

			if ($tags_in_group)
			{
				$tqn_query = $this->model('Group')
								//->fields('tag_group_name')
								->filter('tag_group_id', $tag_group_id)
								->first();

				if ($tqn_query)
				{
					$tag_group_name = $tqn_query->tag_group_name;
				}
			}

			$this->cached_vars['tag_group_name'] = $tag_group_name;
		}
		//END if ($tag_group_id > 0)

		$this->cached_vars['tags_in_group'] = $tags_in_group;

		//----------------------------------------
		//  Queries for Channel Entries Tagged
		//----------------------------------------

		$this->cached_vars['percent_channel_entries_tagged'] = 0;

		//--------------------------------------------
		//	total tags
		//--------------------------------------------

		$q = $this->fetch('TagTag')
				->filter('site_id', ee()->config->item('site_id'));

		if ($tags_in_group)
		{
			$q->filter('tag_id', 'IN', $tags_in_group);
		}

		$this->cached_vars['total_tags'] = $q->count();

		//--------------------------------------------
		//	total entries tagged
		//--------------------------------------------


		//@TODO: change this out for a model
		//@NOTE: at the moment EE 3.x models don't support distinct
		ee()->db->select('COUNT(DISTINCT entry_id) AS count')
				->where('type', 'channel')
				->where('site_id', ee()->config->item('site_id'));

		if ($tags_in_group)
		{
			ee()->db->where('tag_group_id', $tag_group_id);
		}

		$query = ee()->db->get('tag_entries');

		$this->cached_vars['total_channel_entries_tagged'] = (
			$query->num_rows() == 0
		) ? 0 : $query->row('count');

		//--------------------------------------------
		//	%
		//--------------------------------------------

		$entry_count = ee('Model')
						->get('ChannelEntry')
						->filter('site_id', ee()->config->item('site_id'))
						->count();

		if ($entry_count > 0)
		{
			$this->cached_vars['percent_channel_entries_tagged'] = round(
				$this->cached_vars['total_channel_entries_tagged'] / $entry_count * 100,
				2
			);
		}

		// --------------------------------------------
		//  Top 5 Tags
		// --------------------------------------------

		$tagTags = $this->fetch('TagTag')
						//->fields('tag_name', 'total_entries')
						->filter('site_id', ee()->config->item('site_id'))
						->order('total_entries', 'desc')
						->limit(5);

		if ($tags_in_group)
		{
			$tagTags->filter('tag_id', 'IN', $tags_in_group);
		}

		$top5 = $tagTags->all();

		$this->cached_vars['top_five_tags'] = array();

		if ( ! empty($top5))
		{
			$this->cached_vars['top_five_tags'] = $top5->getDictionary(
				'tag_name',
				'total_entries'
			);
		}

		//	----------------------------------------
		//	Browse by First Character
		//	----------------------------------------

		ee()->db
			->select('tag_alpha, COUNT(tag_alpha) as count')
			->where('site_id', ee()->config->item('site_id'))
			->group_by('tag_alpha');

		if ($tags_in_group)
		{
			ee()->db->where_in('tag_id', $tags_in_group);
		}

		$query = ee()->db->get('tag_tags');

		$this->cached_vars['tags_by_alpha'] = array();

		if ( $query->num_rows() > 0 )
		{
			foreach ( $query->result_array() as $row )
			{
				$this->cached_vars['tags_by_alpha'][$row['tag_alpha']] = $row['count'];
			}
		}

		//--------------------------------------------
		//	base alpha url (ee 2.x only)
		//--------------------------------------------

		$this->cached_vars['base_alpha_url'] 	= $this->base . (
			($tags_in_group) ? AMP . 'tag_group_id=' . $tag_group_id : ''
		);

		// --------------------------------------------
		//  Sets tags to $this->cached_vars['tags']
		// --------------------------------------------

		$paginate		= '';
		$row_count		= 0;

		//	----------------------------------------
		//	Bad tags array
		//	----------------------------------------

		$bad_tags = $this->fetch('BadTag')
						//->fields('tag_name') //this is causing a limit(1) bug in ee 3.1
						->filter('site_id', ee()->config->item('site_id'))
						->all()
						->getDictionary('tag_name', 'tag_name');

		$this->cached_vars['bad_tags'] = array_values($bad_tags);

		// --------------------------------------------
		//  Build Our Tags Query
		// --------------------------------------------

		$tagTags = $this->fetch('TagTag')
						->filter('tag_name', '!=', '')
						->filter('site_id', ee()->config->item('site_id'));


		$alpha = ee()->input->get_post('alpha');
		$keywords = ee()->input->get_post('tag_search_keywords');

		if ($alpha !== false)
		{
			$first = $this->lib('Utils')->clean_str($alpha);

			// Non unicode characters need some special love
			// If you are to get the first "letter"
			if (function_exists('mb_substr')) {
				$first = mb_substr($first, 0, 1);
			} else {
				$first = substr($first, 0, 1);
			}

			$tagTags->filter('tag_alpha', $first);
		}

		if ($keywords !== false)
		{
			$tagTags->filter(
				'tag_name',
				'like',
				'%' . $this->lib('Utils')->clean_str($keywords) . '%'
			);
		}

		if ( ! empty($tags_in_group))
		{
			$tagTags->filter(
				'tag_id',
				'IN',
				$tags_in_group
			);
		}

		// -------------------------------------
		//	column sorting
		// -------------------------------------

		$col_map = array(
			'tag_id'		=> 'tag_id',
			'edit'			=> 'tag_name',
			'created_date'	=> 'entry_date',
			'edit_date'		=> 'edit_date',
			'count'			=> 'total_entries'
		);

		if (isset($col_map[ee()->input->get_post('sort_col')]))
		{
			$sort = (ee()->input->get_post('sort_dir') == 'asc') ? 'ASC' : 'DESC';

			$col = $col_map[ee()->input->get_post('sort_col')];

			$tagTags->order($col, $sort);
		}
		else
		{
			$tagTags->order('tag_name', 'asc');
		}

		// -------------------------------------
		//	this
		// -------------------------------------

		$tagTags->fields(
			'tag_name',
			'tag_id',
			'entry_date',
			'edit_date',
			'total_entries'
		);

		$total_count = $tagTags->count();

		//	----------------------------------------
		//	Paginate
		//	----------------------------------------

		$this->cached_vars['paginate'] = '';

		$page = 0;

		if ($total_count > $this->row_limit)
		{
			$page			= $this->get_post_or_zero('page') ?: 1;

			$mcp_link_array = array(
				'method' => 'index'
			);

			foreach (array(
				'alpha',
				'tag_search_keywords',
				'tag_group_id',
				'sort_col',
				'sort_dir'
			) as $get)
			{
				if (ee()->input->get_post($get) !== false)
				{
					$mcp_link_array[$get] = ee()->input->get_post($get, true);
				}
			}

			$this->cached_vars['pagination'] = ee('CP/Pagination', $total_count)
								->perPage($this->row_limit)
								->currentPage($page)
								->render($this->mcp_link($mcp_link_array, false));

			$tagTags->limit($this->row_limit)->offset(($page - 1) * $this->row_limit);
		}
		//END if ($total_count > $this->row_limit)

		// -------------------------------------
		//	biuld data
		// -------------------------------------

		$query = $tagTags->all();

		$this->cached_vars['tags'] = array();

		$member_ids = $tag_ids = array();

		if ( ! empty($query))
		{
			foreach($query as $rowObj)
			{
				//must do this or each result is the model object
				$row = $rowObj->asArray();

				if ( empty($row['edit_date']))
				{
					$row['edit_date'] = $row['entry_date'];
				}

				$this->cached_vars['tags'][$row['tag_id']] = $row;
			}
		}
		//END if ( ! empty($query))

		// -------------------------------------
		//	table
		// -------------------------------------

		$this->cached_vars['form_url']	= $this->mcp_link(array(
			'method' => 'manage_tags_process'
		));

		//--------------------------------------------
		//	tag table
		//--------------------------------------------

		$table = ee('CP/Table', array(
			'sortable'	=> true,
			'search'	=> false,
			'sort_col'	=> 'edit',
			'sort_dir'	=> 'desc'
		));

		$tableData = array();

		$tag_names = array();

		foreach ($this->cached_vars['tags'] as $data)
		{
			$tag_names[] = $data['tag_name'];
		}

		if ( ! empty($tag_names))
		{
			$bad_tags = $this->fetch('BadTag')
							//->fields('tag_name')
							->filter('tag_name', 'IN', $tag_names)
							->all()
							->getDictionary('tag_name', 'tag_name');
		}

		foreach ($this->cached_vars['tags'] as $data)
		{
			$item = array();

			//tag id
			$item[] = $data['tag_id'];

			//edit
			$item[] = array(
				'content'	=> $data['tag_name'],
				'href'		=>$this->mcp_link(array(
					'method'	=> 'edit_tag_form',
					'tag_id'	=> $data['tag_id']
				))
			);

			//count
			$item[] = array(
				'content'	=> $data['total_entries'],
				'href'		=> $this->mcp_link(array(
					'method'	=> 'channel_entries_by_tag',
					'tag_id'	=> $data['tag_id']
				))
			);

			//created date
			$item[]	= $this->human_time($data['entry_date']);

			//edit_date
			$item[]	= $this->human_time($data['edit_date']);

			//bad tag
			if (in_array($data['tag_name'], $bad_tags))
			{
				$item[] = lang('bad_tag');
			}
			else
			{
				$item[] = array(
					'content'	=> lang('make_bad'),
					'href'		=> $this->mcp_link(array(
						'method'	=> 'bad_tag',
						'tag_name'	=> urlencode(base64_encode($data['tag_name']))
					))
				);
			}

			//checkbox
			$item[] = array(
				'name'	=> 'toggle[]',
				'value'	=> $data['tag_id'],
				'data'	=> array(
					'confirm' => lang('tag') . ': <b>' . htmlentities($data['tag_name'], ENT_QUOTES) . '</b>'
				)
			);

			$tableData[] = $item;
		}

		// -------------------------------------
		//	setup table
		// -------------------------------------

		$table->setColumns(array(
			'tag_id',
			'edit',
			'count',
			'created_date',
			'edit_date',
			'bad_tag' => array(
				'sort' => false
			),
			array(
				'type' => Table::COL_CHECKBOX
			)
		));

		$table->setData($tableData);

		$table->setNoResultsText('no_tags');

		$tableLinkArray = array('method' => __FUNCTION__);

		//pagination preserve
		if ($page)
		{
			$tableLinkArray['page'] = $page;
		}

		$this->cached_vars['tag_table'] = $table->viewData(
			$this->mcp_link($tableLinkArray, false)
		);

		//ee()->cp->add_js_script('file', 'cp/sort_helper');

		$this->cached_vars['footer'] = array(
			'type'			=> 'bulk_action_form',
			'submit_lang'	=> lang('delete')
		);

		$this->cached_vars['cp_page_title'] = lang('manage_tags');

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		$this->mcp_modal_confirm(array(
			'form_url'		=> $this->mcp_link(array('method' => 'delete_tag')),
			'name'			=> 'tag',
			'kind'			=> lang('tag'),
		));

		return $this->mcp_view(array(
			'file'		=> 'home',
			'highlight'	=> 'manage_tags',
			'pkg_js'	=> array('auto_checkboxes'),
			'pkg_css'	=> array('mcp_defaults', 'tag_defaults'),
			'crumbs'	=> array(
				array(lang('manage_tags'))
			)
		));
	}
	// END index()


	// --------------------------------------------------------------------

	/**
	 * tag_groups MCP page
	 *
	 * @access	public
	 * @param	message	flashdata message for CP
	 * @return	string	output html
	 */

	public function tag_groups($message = '')
	{
		$this->prep_message($message);

		//--------------------------------------------
		//	get tag groups that arent being used
		//--------------------------------------------

		$ug_query = ee('Model')->get('ChannelField')
						//->fields('field_id', 'field_settings')
						->filter('field_type', $this->lower_name)
						->filter('site_id', ee()->config->item('site_id'))
						->all()
						->getDictionary('field_id', 'field_settings');


		//1 is always locked as its default
		$used_groups = array('1' => true);

		if ( ! empty($ug_query))
		{
			foreach ($ug_query as $data)
			{
				if (isset($data['tag_group']) AND $data['tag_group'] != 1)
				{
					$used_groups[$data['tag_group']] = true;
				}
			}
		}

		// -------------------------------------
		//	build table
		// -------------------------------------

		$table = ee('CP/Table', array(
			'sortable'	=> true,
			'search'	=> false,
			'sort_col'	=> 'tag_group_id',
			'sort_dir'	=> 'asc'
		));

		//--------------------------------------------
		//	get all groups
		//--------------------------------------------

		$tg_query = $this->fetch('Group')->order('tag_group_id', 'asc')->all();

		// -------------------------------------
		//	do sorting from EE table
		// -------------------------------------

		$col_map = array(
			'group_id'				=> 'tag_group_id',
			'group_name'			=> 'tag_group_name',
			'group_short_name'		=> 'tag_group_short_name',
			'total_tags_in_group'	=> 'tag_count',
		);

		$tag_groups = array();

		$tableData = array();

		if ( ! empty($tg_query))
		{

			// -------------------------------------
			//	pre-build data for sorting
			// -------------------------------------

			$rows = array_map(function($r)
			{
				$row = $r->asArray();

				//have to do it like this because new ORM doesn't yet
				//support distinct and count_all_results ignores
				//select and distinct
				$count_tags = ee()->db
							->distinct()
							->select('tag_id')
							->where('tag_group_id', $row['tag_group_id'])
							->get('tag_entries');

				//total tags in group
				$row['tag_count'] = $count_tags->num_rows();

				return $row;

			}, $tg_query->asArray());

			// -------------------------------------
			//	sort?
			// -------------------------------------

			if (isset($col_map[ee()->input->get_post('sort_col')]))
			{
				$sort = (ee()->input->get_post('sort_dir') == 'asc') ? 'ASC' : 'DESC';

				$col = $col_map[ee()->input->get_post('sort_col')];

				usort($rows, function($a, $b) use($col, $sort)
				{
					if ($sort == 'ASC')
					{
						return $a[$col] < $b[$col];
					}
					else
					{
						return $a[$col] > $b[$col];
					}
				});
			}

			// -------------------------------------
			//	build for table
			// -------------------------------------

			foreach ($rows as $row)
			{
				$item = array();

				$item[] = $row['tag_group_id'];

				//group_name
				$item[] = array(
					'content'	=> $row['tag_group_name'],
					'href'		=> $this->mcp_link(array(
						'method'		=> 'edit_tag_group_form',
						'tag_group_id'	=> $row['tag_group_id']
					))
				);

				//group short name
				$item[] = $row['tag_group_short_name'];

				//tag group tag count
				$item[] = $row['tag_count'];

				//view tags in group
				$item[] = array(
					'content'	=> lang('view_group_tags'),
					'href'		=> $this->mcp_link(array(
						'method'		=> 'index',
						'tag_group_id'	=> $row['tag_group_id']
					))
				);

				//checkbox
				if (! isset($used_groups[$row['tag_group_id']]))
				{
					$item[] = array(
						'name'	=> 'toggle[]',
						'value'	=> $row['tag_group_id'],
						'data'	=> array(
							'confirm' => lang('tag_group') . ': <b>' .
								htmlentities($row['tag_group_name'], ENT_QUOTES) .
								'</b>'
						)
					);
				}
				else
				{
					$item[] = array(
						'name'		=> 'disabled',
						'value'		=> 'disabled',
						'disabled'	=> true
					);
				}

				$tableData[] = $item;
			}
			//END foreach ($rows as $row)
		}
		//END if ( ! empty($tg_query))

		// -------------------------------------
		//	setup table
		// -------------------------------------

		$table->setColumns(array(
			'group_id',
			'group_name',
			'group_short_name',
			'total_tags_in_group',
			'view_group_tags' => array(
				'sort' => false
			),
			array(
				'type' => Table::COL_CHECKBOX
			)
		));

		$table->setData($tableData);

		$table->setNoResultsText('no_tags');

		$this->cached_vars['tag_group_table']		= $table->viewData(
			$this->mcp_link(array('method' => __FUNCTION__), false)
		);
		//--------------------------------------------
		//	other data
		//--------------------------------------------

		$this->cached_vars['cp_page_title']			= lang('tag_groups');

		$this->cached_vars['footer']				= array(
			'submit_lang'	=> lang('delete_tag_groups'),
			'type'			=> 'bulk_action_form'
		);

		$this->cached_vars['form_right_links']		= array(
			array(
				'link' => $this->mcp_link(array('method' => 'edit_tag_group_form')),
				'title' => lang('create_tag_group'),
			)
		);

		$this->mcp_modal_confirm(array(
			'form_url'		=> $this->mcp_link(array('method' => 'delete_tag_groups')),
			'name'			=> 'tag_group',
			'kind'			=> lang('tag_group'),
		));

		//---------------------------------------------
		//  Load page
		//---------------------------------------------

		return $this->mcp_view(array(
			'file'			=> 'tag_groups',
			'highlight'		=> 'tag_groups',
			'pkg_css'		=> array('mcp_defaults', 'tag_defaults'),
			'show_message'	=> false,
			'crumbs'		=> array(
				array(lang('tag_groups'))
			)
		));
	}
	//END tag_groups


	// --------------------------------------------------------------------

	/**
	 * edit_tag_group_form
	 * tag_groups_edit/create MCP page
	 *
	 * @access	public
	 * @param	message	flashdata message for CP
	 * @return	string	output html
	 */

	public function edit_tag_group_form($message = '')
	{
		$this->prep_message($message, true, true);

		//--------------------------------------------
		//	function mode
		//--------------------------------------------

		$tag_group_id = $this->get_post_or_zero('tag_group_id');

		$mode = $this->is_positive_intlike($tag_group_id) ? 'edit' : 'create';

		//--------------------------------------
		//  lang
		//--------------------------------------

		$submit_lang = lang(($mode == 'edit') ? 'update_tag_group' : 'create_tag_group');

		//--------------------------------------------
		//	get data
		//--------------------------------------------

		$name_field 		= '';
		$short_name_field 	= '';

		if ($mode == 'edit')
		{
			$query = $this->fetch('Group')
						->filter('tag_group_id', $tag_group_id)
						->first();

			if ( ! empty($query))
			{
				$row = $query->asArray();

				$name_field 		= $row['tag_group_name'];
				$short_name_field 	= $row['tag_group_short_name'];
			}
		}

		$this->cached_vars['tag_group_name'] 		= $name_field;
		$this->cached_vars['tag_group_short_name'] 	= $short_name_field;

		//build form

		$sections = array();

		$main_section = array();

		$main_section['tag_group_name'] = array(
			'title'		=> lang('tag_group_name'),
			'fields'	=> array(
				'tag_group_name' => array(
					'type'		=> 'text',
					'value'		=> $name_field,
					//we just require everything
					//its a settings form
					'required'	=> true
				)
			)
		);

		$main_section['tag_group_short_name'] = array(
			'title'		=> lang('tag_group_short_name'),
			'fields'	=> array(
				'tag_group_short_name' => array(
					'type'		=> 'text',
					'value'		=> $short_name_field,
					//we just require everything
					//its a settings form
					'required'	=> true
				)
			)
		);

		if ($mode == 'edit')
		{
			$main_section['tag_group_id'] = array(
				'fields'	=> array(
					'tag_group_id' => array(
						'type'		=> 'hidden',
						'value'		=> $tag_group_id,
					)
				)
			);
		}



		$sections[] = $main_section;

		$this->cached_vars['sections'] = $sections;

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'				=> $this->mcp_link(array(
				'method' => 'edit_tag_group'
			)),
			'cp_page_title'			=> lang($mode . '_tag_group'),
			'save_btn_text'			=> $submit_lang,
			'save_btn_text_working'	=> 'btn_saving'
		);

		//--------------------------------------------
		//	other data
		//--------------------------------------------

		$this->cached_vars['form_url'] 	= $this->mcp_link(array(
			'method' => 'edit_tag_group'
		));

		return $this->mcp_view(array(
			'file'		=> 'edit_tag_group',
			'highlight'	=> 'tag_groups',
			'pkg_css'	=> array('mcp_defaults'),
			'pkg_js'	=> array('edit_tag_group'),
			'crumbs'	=> array(
				array(lang('tag_groups'), $this->mcp_link(array('method' => 'tag_groups'))),
				array(lang($mode . '_tag_group'))
			)
		));
	}
	//END edit_tag_group_form


	// --------------------------------------------------------------------

	/**
	 * Edit Tag Group
	 *
	 * @access	public
	 * @return	void		return
	 */

	public function edit_tag_group()
	{
		//edit
		if (ee()->input->get_post('tag_group_id') AND
			is_numeric(ee()->input->get_post('tag_group_id')))
		{
			ee()->load->helper('url');

			$group_name = strtolower(url_title(ee()->input->get_post('tag_group_name'), 'underscore'));
			$short_name = strtolower(ee()->input->get_post('tag_group_short_name'));

			$tag_group_short_name	= ($group_name == $short_name) ? $group_name : $short_name;


			ee()->db->update(
				'exp_tag_groups',
				array(
					'tag_group_name' 		=> ee()->input->get_post('tag_group_name'),
					'tag_group_short_name'	=> $tag_group_short_name
				),
				array(
					'tag_group_id'		=> ee()->input->get_post('tag_group_id')
				)
			);

			$msg = 'tag_group_updated';
		}
		//create
		else
		{
			$this->model('Data')->insert_new_tag_group(
				ee()->input->get_post('tag_group_name'),
				ee()->input->get_post('tag_group_short_name')
			);

			$msg = 'tag_group_created';
		}

		ee()->functions->redirect($this->mcp_link(array(
			'method'	=> 'tag_groups',
			'msg'		=> $msg
		)));
	}
	//END edit_tag_group


	// --------------------------------------------------------------------

	/**
	 * Delete Tag Groups
	 *
	 * @access	public
	 * @return	void 	redirect
	 */

	public function delete_tag_groups()
	{
		$sql	= array();

		if ( ee()->input->post('toggle') === false OR !
			is_array(ee()->input->post('toggle')))
		{
			return ee()->functions->redirect($this->mcp_link());
		}

		//--------------------------------------------
		//	get tag groups that are being used
		//	so people wont delete stuff thats legit
		//--------------------------------------------

		$ug_query = ee()->db
						->select('field_settings')
						->where('field_type', 'tag')
						->get('channel_fields');

		$used_groups = array();

		if ($ug_query->num_rows() > 0)
		{
			foreach ($ug_query->result_array() as $row)
			{
				$data = unserialize(base64_decode($row['field_settings']));

				if (isset($data['tag_group']) AND $data['tag_group'] != 1)
				{
					$used_groups[] = $data['tag_group'];
				}
			}
		}

		$ids	= array();

		foreach ($_POST['toggle'] as $key => $val)
		{
			if (is_numeric($val) AND ! in_array($val, $used_groups))
			{
				$ids[] = $val;
			}
		}

		if ( ! empty($ids))
		{
			ee()->db->where_in('tag_group_id', $ids)->delete('tag_groups');

			//drop count columns
			foreach ($ids as $id)
			{
				$col	= ee()->db->escape_str('total_entries_' . $id);

				if (ee()->db->field_exists($col, 'exp_tag_tags'))
				{
					ee()->db->query(
						"ALTER TABLE 	`exp_tag_tags`
						 DROP COLUMN 	{$col}"
					);
				}
			}
		}

		ee()->functions->redirect(
			$this->mcp_link(array(
				'method'	=> 'tag_groups',
				'msg'		=> 'tag_group_deleted'
			))
		);
	}
	// END delete_tag_groups()


	// --------------------------------------------------------------------

	/**
	 *	Manage Tags Processing
	 *
	 *	Redirects to either a mass-edit, mass-delete, or a simple search
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function manage_tags_process()
	{
		$mcp_link_array = array(
			'method' => 'index'
		);

		foreach (array(
			'alpha',
			'tag_search_keywords',
			'tag_group_id',
			'sort_col',
			'sort_dir'
		) as $get)
		{
			if (ee()->input->get_post($get) !== false)
			{
				$mcp_link_array[$get] = ee()->input->get_post($get, true);
			}
		}

		return ee()->functions->redirect($this->mcp_link($mcp_link_array));
	}
	// END manage_tags_proces()


	// --------------------------------------------------------------------

	/**
	 * Channel Entries By tag
	 *
	 * @access	public
	 * @param	string $message		incoming message
	 * @return	array				mcp output array
	 */

	public function channel_entries_by_tag($message = '')
	{
		//	----------------------------------------
		//	 Fetch Tag Name
		//	----------------------------------------

		$tag_id = $this->get_post_or_zero('tag_id');

		if ($tag_id == 0)
		{
			return $this->show_error(lang('invalid_request'));
		}

		$query = $this->fetch('TagTag')
					//->fields('tag_name')
					->filter('site_id', ee()->config->item('site_id'))
					->filter('tag_id', $tag_id)
					->first();

		if (empty($query))
		{
			return $this->show_error(lang('invalid_request'));
		}

		$row = $query->asArray();
		$tag_name = $row['tag_name'];

		//	----------------------------------------
		//	Query
		//	----------------------------------------

		$tag_entries = $this->fetch('Entry')
						//->fields('entry_id')
						->filter('tag_id', $tag_id)
						->all()
						->getDictionary('entry_id', 'entry_id');

		$tableData = array();

		if ( ! empty($tag_entries))
		{
			$entries = ee('Model')->get('ChannelEntry')
						/*->fields(
							'title',
							'entry_date',
							'entry_id'
						)*/
						->filter('site_id', ee()->config->item('site_id'))
						->filter('entry_id', 'IN', array_values($tag_entries));

			// -------------------------------------
			//	sort
			// -------------------------------------

			if (isset($col_map[ee()->input->get_post('sort_col')]))
			{
				$sort = (ee()->input->get_post('sort_dir') == 'asc') ? 'ASC' : 'DESC';

				$col = ee()->input->get_post('sort_col');

				$entries->order($col, $sort);
			}
			else
			{
				$entries->order('entry_date', 'desc');
			}

			$total_count = $entries->count();

			// -------------------------------------
			//	Pagination
			// -------------------------------------

			$this->cached_vars['paginate'] = '';

			$page = 0;

			if ($total_count > $this->row_limit)
			{
				$page			= $this->get_post_or_zero('page') ?: 1;

				$mcp_link_array = array(
					'method' => __FUNCTION__
				);

				if (ee()->input->get_post('sort_col') !== false)
				{
					$mcp_link_array['sort_col'] = ee()->input->get_post('sort_col', true);
				}

				if (ee()->input->get_post('sort_dir') !== false)
				{
					$mcp_link_array['sort_dir'] = ee()->input->get_post('sort_dir', true);
				}

				$this->cached_vars['pagination'] = ee('CP/Pagination', $total_count)
									->perPage($this->row_limit)
									->currentPage($page)
									->render($this->mcp_link($mcp_link_array, false));

				$entries->limit($this->row_limit)->offset(($page - 1) * $this->row_limit);
			}
			//END if ($total_count > $this->row_limit)

			foreach ($entries->all() as $row)
			{
				$tableData[] = array(
					$row->entry_id,
					array(
						'content' => $row->title,
						'href' => ee('CP/URL', 'publish/edit/entry/' . $row->entry_id)
					),
					$this->human_time($row->entry_date)
				);
			}
		}
		//END if ( ! empty($tag_entries))

		// -------------------------------------
		//	build table
		// -------------------------------------

		$table = ee('CP/Table', array(
			'sortable'	=> true,
			'search'	=> false,
			'sort_col'	=> 'entry_date',
			'sort_dir'	=> 'desc'
		));

		$table->setColumns(array(
			'entry_id',
			'entry_title',
			'entry_date'
		));

		$table->setData($tableData);

		$table->setNoResultsText('no_entries_found');

		$this->cached_vars['entries_table']		= $table->viewData(
			$this->mcp_link(array('method' => __FUNCTION__), false)
		);

		//--------------------------------------------
		//	other data
		//--------------------------------------------

		$this->cached_vars['cp_page_title'] = lang('channel_entries_by_tag') . ': ' . $tag_name;

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'channel_entries_by_tag',
			'highlight'	=> 'home',
			//'pkg_js'	=> array('auto_checkboxes'),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('channel_entries_by_tag'))
			)
		));
	}
	// END channel_entries_by_tag()


	// --------------------------------------------------------------------

	/**
	 *	Edit Tag Form
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function edit_tag_form()
	{
		$tag_id = $this->get_post_or_zero('tag_id');

		if ($tag_id < 1)
		{
			return ee()->functions->redirect($this->mcp_link());
		}

		//	----------------------------------------
		//	Query
		//	----------------------------------------

		$query = $this->fetch('TagTag')
						->filter('tag_id', $tag_id)
						->filter('site_id', ee()->config->item('site_id'))
						->first();

		if ( ! $query)
		{
			return ee()->functions->redirect($this->mcp_link());
		}

		$row = $query->asArray();


		$sections = array();

		$main_section = array();

		$main_section['edit_tag'] = array(
			'title'		=> lang('tag'),
			//'desc'		=> lang('add_bad_tags_instructions'),
			'fields'	=> array(
				'tag_id' => array(
					'value'		=> $tag_id,
					'type'		=> 'hidden',
					'required'	=> true
				),
				'tag_name' => array(
					'value'		=> $row['tag_name'],
					'type'		=> 'text',
					'required'	=> true
				)
			)
		);

		$sections[] = $main_section;

		$this->cached_vars['sections'] = $sections;

		$this->cached_vars['form_url'] = $this->mcp_link(array(
			'method' => 'edit_tag'
		));

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'				=> $this->mcp_link(array(
				'method' => 'edit_tag'
			)),
			'cp_page_title'			=> lang('edit_tag'),
			'save_btn_text'			=> 'edit_tag',
			'save_btn_text_working'	=> 'btn_saving'
		);

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'edit_tag_form',
			'highlight'	=> 'home',
			//'pkg_js'	=> array('auto_checkboxes'),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('edit_tag'))
			)
		));
	}
	// END edit tag form


	// --------------------------------------------------------------------

	/**
	 *	Edit Tag
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function edit_tag()
	{
		if ( ee()->input->get_post('tag_id') === false OR
			 ! is_numeric(ee()->input->get_post('tag_id')))
		{
			return false;
		}

		$this->tag_id = ee()->db->escape_str( ee()->input->get_post('tag_id'));

		$combine = true;

		//	----------------------------------------
		//	Validate
		//	----------------------------------------

		if ( ( $tag_name = ee()->input->get_post('tag_name') ) === false )
		{
			return $this->show_error(lang('tag_name_required'));
		}

		$query = ee()->db
					->select('tag_name')
					->where('tag_id', $this->tag_id)
					->limit(1)
					->get('tag_tags');

		if ($query->num_rows() == 0)
		{
			return ee()->functions->redirect($this->mcp_link());
		}

		$old_tag_name = $query->row('tag_name');

		unset($temp);

		//	----------------------------------------
		//	Clean tag
		//	----------------------------------------

		$tag_name	= $this->lib('Utils')->clean_str( $tag_name );

		//	----------------------------------------
		//	Check for duplicate
		//	----------------------------------------

		$sql	= "SELECT 	tag_id, tag_name
				   FROM 	exp_tag_tags
				   WHERE 	site_id = " . ee()->db->escape_str(ee()->config->item('site_id')) ."
				   AND 		tag_name = '" . ee()->db->escape_str( $tag_name ) . "'";

		if ( $this->tag_id != '' )
		{
			$sql .= " AND tag_id != '" . $this->tag_id . "'";
		}

		$sql	.= " LIMIT 1";

		$query	= ee()->db->query( $sql );

		//	----------------------------------------
		//	If we find no matching tags we can't possibly combine tags.
		//	----------------------------------------

		if ( $query->num_rows() == 0 )
		{
			$combine = false;
		}

		//	----------------------------------------
		//	Are we combining?
		//	----------------------------------------

		if ( $combine === true )
		{
			// --------------------------------------------
			//  Previously Tagged by New Tag
			// --------------------------------------------

			$extra_sql			= '';

			$previous = ee()->db->query(
				"SELECT entry_id
				 FROM 	exp_tag_entries
				 WHERE 	tag_id = '" . $query->row('tag_id') . "'"
			);

			if ($previous->num_rows() > 0)
			{
				$previous_entries	= array();

				foreach($previous->result_array() as $row)
				{
					$previous_entries[] = $row['entry_id'];
				}

				$extra_sql .= " AND entry_id NOT IN (".implode(',', $previous_entries).")";
			}

			// --------------------------------------------
			//  Update Tag Entries from Old to New, Except Where Already Tagged by New
			// --------------------------------------------

			ee()->db->query(
				"UPDATE exp_tag_entries
				 SET 	tag_id = '" . ee()->db->escape_str($query->row('tag_id')) . "'
				 WHERE 	tag_id = '" . ee()->db->escape_str($this->tag_id) . "'" .
				 $extra_sql
			);

			//	----------------------------------------
			//	Delete the old
			//	----------------------------------------

			ee()->db->query( "DELETE FROM exp_tag_entries 		WHERE tag_id = '" . $this->tag_id . "'" );
			ee()->db->query( "DELETE FROM exp_tag_tags 			WHERE tag_id = '" . $this->tag_id . "'" );

			//	----------------------------------------
			//	Recount stats
			//	----------------------------------------

			$this->lib('Utils')->recount_tags($query->row('tag_id'));

			$message	= str_replace(
				array( '%old_tag_name%', '%new_tag_name%' ),
				array( $old_tag_name, $tag_name ),
				lang('tags_combined')
			);
		}

		//	----------------------------------------
		//	 No Combining, Simply Updating
		//	----------------------------------------

		if ( $combine === false )
		{
			ee()->db->query(
				ee()->db->update_string(
					'exp_tag_tags',
					array(
						'tag_name' 	=> $tag_name,
						'tag_alpha' => $this->lib('Utils')->first_character($tag_name),
						'author_id' => ee()->session->userdata['member_id'],
						'edit_date' => ee()->localize->now
					),
					array(
						'tag_id' => $this->tag_id
					)
				)
			);

			$message	= lang('tag_updated');
		}

		ee()->functions->redirect($this->mcp_link(array(
			'method'	=> 'index',
			'msg'		=> urlencode($message)
		)));
	}
	// END edit tag


	// --------------------------------------------------------------------

	/**
	 *	Delete Tag
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function delete_tag()
	{
		$sql	= array();

		if ( ee()->input->post('toggle') === false OR
			 ! is_array(ee()->input->post('toggle')))
		{
			return ee()->functions->redirect($this->mcp_link());
		}

		$ids	= array();

		foreach($_POST['toggle'] as $key => $val)
		{
			$ids[] = $val;
		}

		$query = ee()->db->query(
			"SELECT tag_id
			 FROM 	exp_tag_tags
			 WHERE 	tag_id
			 IN 	('".implode("','", ee()->db->escape_str($ids))."')"
		);

		//	----------------------------------------
		//	Delete Tags
		//	----------------------------------------

		$ids = array();

		foreach ( $query->result_array() as $row )
		{
			$ids[] = $row['tag_id'];
		}

		ee()->db->query(
			"DELETE FROM 	exp_tag_tags
			 WHERE 			tag_id
			 IN 			('".implode("','", ee()->db->escape_str($ids))."')"
		);

		ee()->db->query(
			"DELETE FROM 	exp_tag_entries
			 WHERE 			tag_id
			 IN 			('".implode("','", ee()->db->escape_str($ids))."')"
		);

		foreach ( $sql as $q )
		{
			ee()->db->query($q);
		}

		$message = ($query->num_rows() == 1) ?
					str_replace(
						'%i%',
						$query->num_rows(),
						lang('tag_deleted')
					) :
					str_replace(
						'%i%',
						$query->num_rows(),
						lang('tags_deleted')
					);

		return ee()->functions->redirect($this->mcp_link(array(
			'method'	=> 'index',
			'msg'		=> $message
		)));
	}
	// END delete_tag()


	// --------------------------------------------------------------------

	/**
	 *	Bad Tag quick submit
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function bad_tag()
	{
		//	----------------------------------------
		//	Validate
		//	----------------------------------------

		if ( ( $tag_name_post = ee()->input->post('tag_name') ) === false AND
			( $tag_name_get = base64_decode(urldecode(ee()->input->get('tag_name'))) ) === false )
		{
			return $this->show_error(lang('tag_name_required'));
		}

		$tag_name = ($tag_name_post !== false) ? $tag_name_post : $tag_name_get;

		// --------------------------------------------
		//  The Past Messing with the Future
		// --------------------------------------------

		// What we have here is an old tag that was not
		// made lower cased when created.
		// Kelsey still wants the non-lowercased version entered,
		// so we do this whole fun process twice!

		if ($this->model('Data')->preference('convert_case') != 'n' AND
			$this->lib('Utils')->strtolower($tag_name) != $tag_name)
		{
			//	----------------------------------------
			//	Check for duplicate
			//	----------------------------------------

			$query	= ee()->db->query(
				"SELECT tag_name
				 FROM 	exp_tag_bad_tags
				 WHERE  site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'
				 AND 	BINARY tag_name = '".ee()->db->escape_str( $tag_name )."'"
			);

			if ( $query->num_rows() > 0 )
			{
				$tag_name = trim($tag_name, '"');

				return $this->show_error(
					str_replace(
						'%tag_name%',
						stripslashes( $tag_name ),
						lang('bad_tag_exists')
					)
				);
			}

			//	----------------------------------------
			//	Add
			//	----------------------------------------

			ee()->db->query(
				ee()->db->insert_string(
					'exp_tag_bad_tags',
					array(
						'tag_name' 	=> $tag_name,
						'site_id' 	=> ee()->config->item('site_id'),
						'author_id' => ee()->session->userdata['member_id'],
						'edit_date' => ee()->localize->now
					)
				)
			);
		}

		//	----------------------------------------
		//	Clean tag
		//	----------------------------------------

		$tag_name = $this->lib('Utils')->clean_str( $tag_name );

		//	----------------------------------------
		//	Check for duplicate
		//	----------------------------------------

		if ($this->model('Data')->preference('convert_case') != 'n')
		{
			$tag_name = strtolower($tag_name);
		}

		$query	= ee()->db->query(
			"SELECT tag_name
			 FROM 	exp_tag_bad_tags
			 WHERE  site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'
			 AND   	BINARY tag_name = '".ee()->db->escape_str( $tag_name )."'"
		);

		if ( $query->num_rows() > 0 )
		{
			$tag_name = trim($tag_name, '"');

			return $this->show_error(
				str_replace(
					'%tag_name%',
					stripslashes( $tag_name ),
					lang('bad_tag_exists')
				)
			);
		}

		//	----------------------------------------
		//	Add
		//	----------------------------------------

		ee()->db->query(
			ee()->db->insert_string(
				'exp_tag_bad_tags',
				array(
					'tag_name' 	=> $tag_name,
					'site_id' 	=> ee()->config->item('site_id'),
					'author_id' => ee()->session->userdata['member_id'],
					'edit_date' => ee()->localize->now
				)
			)
		);

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		ee()->functions->redirect($this->mcp_link(array(
			'method'	=> 'index',
			'msg'		=> 'bad_tags_added'
		)));
	}
	// END bad tag quick submit


	// --------------------------------------------------------------------

	/**
	 *	Manage Bad Tags
	 *
	 *	Manage Bad Tags in the CP and delete.
	 *
	 *	@access		public
	 * 	@param 		string 	message to send to user
	 *	@return		string
	 */

	public function manage_bad_tags($message = '')
	{
		$this->prep_message($message, true, true);

		//	----------------------------------------
		//	Query
		//	----------------------------------------

		$badTags = $this->fetch('BadTag')
					//->fields('tag_id', 'tag_name', 'edit_date')
					->filter('site_id', ee()->config->item('site_id'));

		// -------------------------------------
		//	sorting
		// -------------------------------------

		$total_count = $badTags->count();

		$tableData = array();

		if ($total_count > 0)
		{

			if (isset($col_map[ee()->input->get_post('sort_col')]))
			{
				$sort = (ee()->input->get_post('sort_dir') == 'asc') ? 'ASC' : 'DESC';

				$col = ee()->input->get_post('sort_col');

				$badTags->order($col, $sort);
			}
			else
			{
				$badTags->order('tag_name', 'asc');
			}

			// -------------------------------------
			//	Pagination
			// -------------------------------------

			$this->cached_vars['paginate'] = '';

			$page = 0;

			if ($total_count > $this->row_limit)
			{
				$page			= $this->get_post_or_zero('page') ?: 1;

				$mcp_link_array = array(
					'method' => __FUNCTION__
				);

				if (ee()->input->get_post('sort_col') !== false)
				{
					$mcp_link_array['sort_col'] = ee()->input->get_post('sort_col', true);
				}

				if (ee()->input->get_post('sort_dir') !== false)
				{
					$mcp_link_array['sort_dir'] = ee()->input->get_post('sort_dir', true);
				}

				$this->cached_vars['pagination'] = ee('CP/Pagination', $total_count)
									->perPage($this->row_limit)
									->currentPage($page)
									->render($this->mcp_link($mcp_link_array, false));

				$badTags->limit($this->row_limit)->offset(($page - 1) * $this->row_limit);
			}
			//END if ($total_count > $this->row_limit)

			foreach ($badTags->all() as $row)
			{
				$tableData[] = array(
					$row->tag_name,
					$this->human_time($row->edit_date),
					array(
						'name'	=> 'toggle[]',
						'value'	=> $row->tag_id,
						'data'	=> array(
							'confirm' => lang('bad_tag') . ': <b>' . htmlentities($row->tag_name, ENT_QUOTES) . '</b>'
						)
					)
				);
			}
		}
		//END if ( ! empty($tag_entries))

		// -------------------------------------
		//	build table
		// -------------------------------------

		$table = ee('CP/Table', array(
			'sortable'	=> true,
			'search'	=> false,
			'sort_col'	=> 'tag_name',
			'sort_dir'	=> 'asc'
		));

		$table->setColumns(array(
			'tag_name',
			'edit_date',
			array(
				'type' => Table::COL_CHECKBOX
			)
		));

		$table->setData($tableData);

		$table->setNoResultsText('no_bad_tags_found');

		$this->cached_vars['bad_tags_table'] = $table->viewData(
			$this->mcp_link(array('method' => __FUNCTION__), false)
		);

		//--------------------------------------------
		//	other data
		//--------------------------------------------

		$this->cached_vars['cp_page_title'] = lang('manage_bad_tags');


		// Final view variables we need to render the form
		$this->cached_vars['footer'] = array(
			'type'			=> 'bulk_action_form',
			'submit_lang'	=> lang('remove_bad_tags')
		);

		$this->mcp_modal_confirm(array(
			'form_url'		=> $this->mcp_link(array('method' => 'delete_bad_tag')),
			'name'			=> 'bad_tag',
			'kind'			=> lang('bad_tag')
		));

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'bad_tags',
			'highlight'	=> 'manage_bad_tags',
			//'pkg_js'	=> array('auto_checkboxes'),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('manage_bad_tags'))
			)
		));
	}
	// END manage_bad_tags()


	// --------------------------------------------------------------------

	/**
	 *	Delete Bad Tag
	 *
	 *	Removes Bad Tags from the database.
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function delete_bad_tag()
	{
		$sql	= array();

		if ( ee()->input->post('toggle') === false OR
			 ! is_array(ee()->input->post('toggle')))
		{
			return ee()->functions->redirect($this->mcp_link(array(
				'method'	=> 'manage_bad_tags'
			)));
		}

		$ids	= array();

		foreach ($_POST['toggle'] as $key => $val)
		{
			$ids[] = $val;
		}

		$query = ee()->db
					->select('tag_id')
					->where_in('tag_id', $ids)
					->get('tag_bad_tags');

		//	----------------------------------------
		//	Delete Bad Tags!
		//	----------------------------------------

		$ids = array();

		foreach ( $query->result_array() as $row )
		{
			$ids[] = $row['tag_id'];
		}

		$message = 'invalid_request';

		if ( ! empty($ids))
		{
			$query = ee()->db
						->where_in('tag_id', $ids)
						->delete('tag_bad_tags');
		}

		return ee()->functions->redirect($this->mcp_link(array(
			'method'	=> 'manage_bad_tags',
			'msg'		=> 'bad_tags_deleted'
		)));
	}
	// END delete_bad_tag()


	// --------------------------------------------------------------------

	/**
	 *	Edit bad tag form
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function add_bad_tags_form()
	{
		$sections = array();

		$main_section = array();

		$main_section['add_bad_tags'] = array(
			'title'		=> lang('add_bad_tags'),
			'desc'		=> lang('add_bad_tags_instructions'),
			'fields'	=> array(
				'tag_name' => array(
					'value'		=> '',
					'type'		=> 'textarea',
					'required'	=> true
				)
			)
		);

		$sections[] = $main_section;

		$this->cached_vars['sections'] = $sections;

		$this->cached_vars['form_url'] = $this->mcp_link(array(
			'method' => 'add_bad_tags'
		));

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'				=> $this->mcp_link(array(
				'method' => 'add_bad_tags'
			)),
			'cp_page_title'			=> lang('add_bad_tags'),
			'save_btn_text'			=> 'add_bad_tags',
			'save_btn_text_working'	=> 'btn_saving'
		);

		return $this->mcp_view(array(
			'file'		=> 'add_bad_tags_form',
			'highlight'	=> 'manage_bad_tags/add_bad_tags',
			//'pkg_js'	=> array('auto_checkboxes'),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('add_bad_tags'))
			)
		));
	}
	// END add_bad_tags_form()


	// --------------------------------------------------------------------

	/**
	 *	Edit bad tag
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function add_bad_tags()
	{
		//	----------------------------------------
		//	Validate
		//	----------------------------------------

		if ( ( $tag_name = ee()->input->get_post('tag_name') ) === false )
		{
			return $this->show_error(lang('tag_name_required'));
		}

		//	----------------------------------------
		//	Clean tag
		//	----------------------------------------

		$tag_name = $this->lib('Utils')->clean_str($tag_name);

		if ($tag_name == '')
		{
			return $this->show_error(lang('tag_name_required'));
		}

		$tag_array = array_unique(array_map('trim',
			preg_split( "/\n|\r/", $tag_name, -1, PREG_SPLIT_NO_EMPTY)
		));

		//	----------------------------------------
		//	Check for duplicate
		//	----------------------------------------

		$inserts = array();

		$existing = $this->fetch('BadTag')
						//->fields('tag_name')
						->filter('site_id', ee()->config->item('site_id'))
						->filter('tag_name', 'IN', $tag_array)
						->all()
						->getDictionary('tag_name', 'tag_name');

		$inserts = array_diff($tag_array, array_values($existing));

		//----------------------------------------
		//	 Add New Bad Tags to Database
		//----------------------------------------

		foreach($inserts as $tag_name)
		{
			$insert = $this->make('BadTag');
			$insert->tag_name	= $tag_name;
			$insert->site_id	= ee()->config->item('site_id');
			$insert->author_id	= ee()->session->userdata['member_id'];
			$insert->edit_date	= ee()->localize->now;
			$insert->save();
		}

		return ee()->functions->redirect($this->mcp_link(array(
			'method' => 'manage_bad_tags',
			'msg' => 'bad_tags_added'
		)));
	}
	// END add_bad_tags()


	// --------------------------------------------------------------------

	/**
	 *	Manage preferences
	 *
	 *	@access		public
	 * 	@param 		string 	message to send to user
	 *	@return		string
	 */

	public function preferences($message = '')
	{
		$this->prep_message($message, true, true);

		// --------------------------------------------
		//  Current Values
		// --------------------------------------------

		$prefModel = $this->make('Preference');

		$defaultPrefs = $prefModel->default_prefs;

		$prefs = $this->fetch('Preference')
					->filter('site_id', ee()->config->item('site_id'))
					->all()->getDictionary(
						'tag_preference_name',
						'tag_preference_value'
					);

		$sections = array();

		$main_section = array();

		foreach ($defaultPrefs as $short_name => $data)
		{
			//	While in our loop, once we reach the Multiple
			//	Tags Input section, break it out as a separate section.
			if ($short_name == 'explode_input_on_separator')
			{
				$sections[]		= $main_section;
				$main_section	= array();
			}

			$desc_name = 'tag_module_' . $short_name . '_desc';
			$desc = lang($desc_name);

			//if we don't have a description don't set it
			$desc = ($desc !== $desc_name) ? $desc : '';

			$main_section[$short_name] = array(
				'title'		=> lang('tag_module_' . $short_name),
				'desc'		=> $desc,
				'fields'	=> array(
					$short_name => array_merge($data, array(
						'value'		=> isset($prefs[$short_name]) ?
										$prefs[$short_name] :
										$data['default'],
						//we just require everything
						//its a settings form
						'required'	=> true
					))
				)
			);
		}

		$sections['explode_input_on_separator']	= $main_section;

		//$sections[] = $main_section;

		$this->cached_vars['sections'] = $sections;

		$this->cached_vars['form_url'] = $this->mcp_link(array(
			'method' => 'update_preferences'
		));

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'				=> $this->mcp_link(array(
				'method' => 'update_preferences'
			)),
			'cp_page_title'			=> lang('preferences'),
			'save_btn_text'			=> 'btn_save_settings',
			'save_btn_text_working'	=> 'btn_saving'
		);

		return $this->mcp_view(array(
			'file'		=> 'preferences_form',
			'highlight'	=> 'preferences',
			'pkg_js'	=> array('preferences_form'),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('preferences'))
			)
		));
	}
	// END preferences()


	// --------------------------------------------------------------------

	/**
	 *	Update Preferences
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function update_preferences()
	{

		$prefs = $this->make('Preference');

		$default_prefs = $prefs->default_prefs;

		$input_keys = $required = array_keys($default_prefs);

		$inputs = array();

		// -------------------------------------
		//	fetch only default prefs
		// -------------------------------------

		foreach ($input_keys as $input)
		{
			if (isset($_POST[$input]))
			{
				$inputs[$input] = ee()->input->post($input);
			}
		}

		// -------------------------------------
		//	validate (custom method)
		// -------------------------------------

		$result = $prefs->validateDefaultPrefs($inputs, $required);

		if ( ! $result->isValid())
		{
			$errors = array();

			foreach ($result->getAllErrors() as $name => $error_list)
			{
				foreach($error_list as $error_name => $error_msg)
				{
					$errors[] = lang('tag_module_' . $name) . ': ' . $error_msg;
				}
			}

			return $this->show_error($errors);
		}

		//	----------------------------------------
		//	Update Preferences
		//	----------------------------------------

		$site_id = ee()->config->item('site_id');

		$currentPrefs = $this->fetch('Preference')
							->filter('site_id', $site_id)
							->all()
							->indexBy('tag_preference_name');


		foreach($inputs as $name => $value)
		{
			//update
			if (isset($currentPrefs[$name]))
			{
				$currentPrefs[$name]->tag_preference_value = $value;
				$currentPrefs[$name]->save();
			}
			//insert
			else
			{
				$new = $this->make('Preference');
				$new->tag_preference_value = $value;
				$new->tag_preference_name = $name;
				$new->site_id = $site_id;
				$new->save();
			}
		}

		return ee()->functions->redirect($this->mcp_link(array(
			'method'	=> 'preferences',
			'msg'		=> 'preferences_updated'
		)));
	}
	// END update_preferences()


	// --------------------------------------------------------------------

	/**
	 *	Tag Field Sync
	 *
	 *	Used when the Publish Tab was used and prior to Tag 4.0.  Now, with the custom field
	 *	type the tags are put into that custom field so that they can be used with the Search
	 *	module.  This code merely sures that every entry has this done. -PB
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function tag_field_sync()
	{
		// --------------------------------------------
		//  Find All Tag Custom Fields for Each Channel
		// --------------------------------------------

		$query = ee()->db->query("	SELECT	c.channel_id, cf.field_id, cf.field_settings
									FROM	exp_channels AS c,
											exp_channel_fields as cf
									 WHERE 	cf.group_id = c.field_group
									 AND	cf.field_type = 'tag'");

		if ($query->num_rows() == 0)
		{
		}

		// --------------------------------------------
		//  Find All Entries without Tag Custom Field Data
		// --------------------------------------------

		$entries_json = array();

		foreach($query->result_array() AS $row)
		{
			$settings		= unserialize(base64_decode($row['field_settings']));
			$tag_group_id	= ( ! isset($settings['tag_group'])) ? 1 : $settings['tag_group'];

			$cquery = ee()->db->query("	SELECT	ct.entry_id, ct.title
										FROM	exp_channel_titles AS ct,
												exp_channel_data AS cd
										WHERE	ct.entry_id = cd.entry_id
										/*AND 	cd.field_id_{$row['field_id']} = ''*/
										AND		ct.entry_id IN
											(
												SELECT DISTINCT entry_id
												FROM exp_tag_entries
												WHERE channel_id = {$row['channel_id']}
												AND tag_group_id = {$tag_group_id}
											)");

			foreach($cquery->result_array() AS $crow)
			{
				$entries_json[] = array(
					'id'	=> $crow['entry_id'],
					'title'	=> $crow['title']
				);
			}
		}

		if (count($entries_json) == 0)
		{
			//ee()->functions->redirect($this->mcp_link(array('method' => 'utilities')) . AMP . 'msg=no_tag_fields_needed_updating');
		}

		// -------------------------------------
		//	Prep the View Vars
		// -------------------------------------

		$this->cached_vars['entries_json']			= json_encode($entries_json);
		$this->cached_vars['total_entries_count']	= count($entries_json);
		$this->cached_vars['return_uri']			= $this->mcp_link();

		$this->cached_vars['ajax_url'] = $this->mcp_link(array(
			'method' => 'sync_tag_fields'
		));

		$this->cached_vars['percent'] = 0;

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'tag_field_sync',
			'highlight'	=> 'utilities/tag_field_sync',
			'pkg_js'	=> array('utilities_progress_meter'),
			'pkg_css'	=> array('mcp_defaults', 'utilities_progress_meter'),
			'crumbs'	=> array(
				array(lang('sync_tags_fields'))
			)
		));
	}
	// END tag_field_sync()


	// --------------------------------------------------------------------

	/**
	 *	Sync Tag Fields
	 *
	 *	Takes the entry id and insures that the
	 *	tag custom field is filled in with all
	 *	of the tags for that entry
	 *
	 *	@access	public
	 *	@return	string 	JSON response if ajax request, otherwise text
	 */

	public function sync_tag_fields()
	{
		// -------------------------------------
		//	have we these tag IDs three?
		// -------------------------------------

		$entry_id = ee()->input->get_post('id');

		if ( ! $entry_id OR ! is_numeric($entry_id))
		{
			//this will be called most by ajax probably
			if ($this->is_ajax_request())
			{
				$this->send_ajax_response(array(
					'success' 	=> 'failure',
					'ids' 		=> $entry_id,
					'message'	=> lang('wrong_value')
				));
				exit();
			}
			else
			{
				exit(lang('wrong_value'));
			}
		}

		// --------------------------------------------
		//  Find Channel ID, Field ID, and Tag Group ID
		// --------------------------------------------

		$query = ee()->db->query("	SELECT ct.channel_id, cf.field_id, cf.field_settings
									FROM	exp_channels AS c,
											exp_channel_titles AS ct,
											exp_channel_fields AS cf
									WHERE 	ct.entry_id = '".ee()->db->escape_str($entry_id)."'
									AND		ct.channel_id = c.channel_id
									AND		c.field_group = cf.group_id
									AND		cf.field_type = 'tag'");

		if ($query->num_rows() == 0)
		{
			//this will be called most by ajax probably
			if ($this->is_ajax_request())
			{
				$this->send_ajax_response(array(
					'success' 	=> 'failure',
					'ids' 		=> $entry_id,
					'message'	=> lang('wrong_value')
				));
				exit();
			}
			else
			{
				exit(lang('wrong_value'));
			}
		}

		// --------------------------------------------
		//  Variables
		// --------------------------------------------

		foreach($query->result_array() AS $row)
		{
			$field_id	= $row['field_id'];
			$settings	= unserialize(base64_decode($row['field_settings']));
			$tag_group	= ( ! isset($settings['tag_group'])) ? 1 : $settings['tag_group'];

			$all_tags = $this->model('Data')->get_entry_tags_by_id(
				$entry_id,
				array('tag_group_id' => $tag_group)
			);

			$tags	= array();

			foreach ($all_tags as $row)
			{
				$tags[] = $row['tag_name'];
			}

			if ( ! empty($tags))
			{

				ee()->db->update(
					'exp_channel_data',
					array('field_id_'.$field_id	=> implode("\n", $tags)),
					array('entry_id'			=> $entry_id)
				);
			}
		}

		// -------------------------------------
		//	Success!
		// -------------------------------------

		//this will be called most by ajax probably
		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' 	=> 'success',
				'ids' 		=> $entry_id,
				'message'	=> lang('tag_field_updated')
			));
			exit();
		}
		else
		{
			exit(lang('tag_field_updates'));
		}
	}
	//END sync_tag_fields


	// --------------------------------------------------------------------

	/**
	 *	Update Tag Counts
	 *
	 *	uses ajax to update tag counts in a way that wont hog database resources
	 *
	 *	@access		public
	 * 	@param 		string 	message to send to user
	 *  @param 		bool 	show 4.1 update to user
	 *	@return		string
	 */

	public function update_tag_counts($message = '', $show_update_msg = false)
	{
		$this->prep_message($message);

		// -------------------------------------
		//	tag data
		// -------------------------------------

		$tag_json = array();

		$query = ee()->db->query(
			"SELECT 	tag_id, tag_name
			 FROM 		exp_tag_tags"
		);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$tag_json[] = array(
					'id'	=> $row['tag_id'],
					'title'	=> $row['tag_name']
				);
			}
		}

		// -------------------------------------
		//	Prep the View Vars
		// -------------------------------------

		$this->cached_vars['tag_json']			= json_encode($tag_json);
		$this->cached_vars['total_tags_count']	= count($tag_json);
		$this->cached_vars['show_update_msg']	= $show_update_msg;
		$this->cached_vars['return_uri']		= $this->mcp_link();
		$this->cached_vars['ajax_url']			= $this->mcp_link(array(
			'method' => 'update_tag_count'
		));
		$this->cached_vars['percent']			= 0;
		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'update_tag_counts',
			'highlight'	=> 'utilities/update_tag_counts',
			'pkg_js'	=> array('utilities_progress_meter'),
			'pkg_css'	=> array('mcp_defaults', 'utilities_progress_meter'),
			'crumbs'	=> array(
				array(lang('update_tag_counts'))
			)
		));
	}
	//END update_tag_counts


	// --------------------------------------------------------------------

	/**
	 * Update Tag Counts (Action for ajax request from MCP)
	 *
	 * @access	public
	 * @return	string 	JSON response if ajax request, otherwise text
	 */

	public function update_tag_count()
	{
		// -------------------------------------
		//	have we these tag IDs three?
		// -------------------------------------

		$tag_ids = ee()->input->get_post('id');

		if ( ! $tag_ids OR ( ! is_array($tag_ids) AND ! is_numeric($tag_ids)))
		{
			//this will be called most by ajax probably
			if ($this->is_ajax_request())
			{
				$this->send_ajax_response(array(
					'success' 	=> 'failure',
					'ids' 		=> $tag_ids,
					'message'	=> lang('wrong_value')
				));
				exit();
			}
			else
			{
				$this->show_error(lang('wrong_value'));
				exit();
			}
		}

		// -------------------------------------
		//	recount, yo!
		// -------------------------------------

		$this->lib('Utils')->recount_tags($tag_ids);

		//this will be called most by ajax probably
		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' 	=> 'success',
				'ids' 		=> $tag_ids,
				'message'	=> lang('tag_count_updated')
			));
			exit();
		}
		else
		{
			$this->show_error(lang('tag_count_updated'));
			exit();
		}
	}
	//END update_tag_count



	// --------------------------------------------------------------------

	/**
	 *	Manage harvest tags
	 *
	 *	@access		public
	 * 	@param 		string 	message to send to user
	 *	@return		string
	 */

	public function harvest($message = '')
	{
		$this->prep_message($message);

		//	----------------------------------------
		//	 Harvest from What Data Source?
		//	----------------------------------------

		$groups = array(
			'channel_categories'	=> lang('harvest_from_channel_categories'),
			'tag_fields'			=> lang('harvest_from_channel_tag_field')
		);

		foreach($groups as $group => $group_label)
		{
			$options[$group] = array();

			if ($group == 'channel_categories')
			{
				$channels_query = ee()->db->query(
					"SELECT 	channel_id AS channel_id,
								site_label,
								channel_title AS channel_title,
								field_group
					 FROM 		exp_channels, exp_sites
					 WHERE 		exp_sites.site_id = exp_channels.site_id
					 ORDER BY 	channel_title"
				);

				foreach($channels_query->result_array() as $row)
				{
					$options[$group][$row['channel_id']] = $row['site_label'] .
															' - ' . $row['channel_title'];
				}
			}
			else if ($group == 'tag_fields')
			{
				$query = ee()->db->query(
					"SELECT		p.tag_preference_name, f.field_label
					 FROM		exp_tag_preferences as p
					 LEFT JOIN	exp_channel_fields f
					 ON			f.field_id = p.tag_preference_value
					 WHERE		tag_preference_name
					 LIKE		'%_tag_field'
					 AND		tag_preference_value != '0'"
				);

				foreach($query->result_array() AS $q_row)
				{
					$x = explode('_', $q_row['tag_preference_name'], 2);

					foreach($channels_query->result_array() AS $row)
					{
						if ($row['channel_id'] == $x[0])
						{
							$options[$group][$row['channel_id']] = $row['site_label'] .
															' - ' . $row['channel_title'] .
															' > ' .$q_row['field_label'];
						}
					}
				}
			}
		}
		//END foreach($groups as $group => $group_label)

		// --------------------------------------------
		//  Build Harvest Location Field
		// --------------------------------------------

		$this->cached_vars['groups'] 					= $groups;
		$this->cached_vars['options'] 					= $options;

		//	----------------------------------------
		//	 Batch Size for Processing
		//	----------------------------------------

		$this->cached_vars['per_batch_options'] 		= array(1, 50, 100, 250, 500, 1000);

		$this->cached_vars['lang_harvest_description'] 	= lang('harvest_description');

		$this->cached_vars['form_url'] 					= $this->mcp_link(array('method' => 'process_harvest'));
		$this->cached_vars['tag_groups']				= $this->model('Data')->get_tag_groups();

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'harvest_form',
			'highlight'	=> 'utilities',
			//'pkg_js'	=> array('auto_checkboxes'),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('tag_harvest'))
			)
		));
	}
	// END harvest()


	// --------------------------------------------------------------------

	/**
	 *	Process Harvest Request or Refresh
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function process_harvest()
	{
		// --------------------------------------------
		//  Do Our Harvesting
		// --------------------------------------------

		$return = $this->_harvest();

		// --------------------------------------------
		//  Are We Finished?
		// --------------------------------------------

		if ( ! in_array(false, $return['done']))
		{
			return ee()->functions->redirect($this->mcp_link(array(
				'method'=>'harvest',
				'msg' => 'success_harvest_processing_is_complete'
			)));
		}

		$this->cached_vars['hidden_fields'] = array();

		// --------------------------------------------
		//  Harvest Sources for this Batch
		// --------------------------------------------

		foreach($return['done'] as $type => $finished)
		{
			if ($finished === true) continue;

			foreach($return['harvest_sources'] as $harvest_source)
			{
				if ( ! stristr($harvest_source, $type)) continue;

				$this->cached_vars['hidden_fields'][] = array('harvest_sources[]', $harvest_source);
			}
		}

		// --------------------------------------------
		//  Batch Number and Per Batch Amount
		// --------------------------------------------

		$return['batch']++; // Next Batch!

		$this->cached_vars['hidden_fields'][] = array('batch', $return['batch']);

		$this->cached_vars['hidden_fields'][] = array('per_batch', $return['per_batch']);

		$this->cached_vars['hidden_fields'][] = array('tag_group', $return['tag_group']);

		// --------------------------------------------
		//  Set All Return Variables to View Variables and Call Batch Page
		// --------------------------------------------

		$this->cached_vars = array_merge($this->cached_vars, $return);

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'harvest_batch_form',
			'highlight'	=> 'utilities',
			//'pkg_js'	=> array('auto_checkboxes'),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('tag_harvest'), $this->mcp_link(array('method' => 'tag_harvest'))),
				array(lang('tag_harvest_batch_process'))
			)
		));
	}
	// END process_harvest()


	// --------------------------------------------------------------------

	/**
	 *	The Harvest Processing Routine
	 *
	 *	The actual processing work is done here, and then we keep process_harvest() a bit cleaner
	 *	for displaying the page for more batches.
	 *
	 *	@access		private
	 *	@return		array
	 */

	private function _harvest( )
	{
		// --------------------------------------------
		//  Data Validation
		// --------------------------------------------

		if (ee()->input->get_post('harvest_sources') === false)
		{
			return ee()->functions->redirect($this->mcp_link(array('method' => 'harvest')));
		}

		if ( isset($_POST['harvest_sources']))
		{
			$harvest_sources = (! is_array($_POST['harvest_sources'])) ?
				array($_POST['harvest_sources']) : $_POST['harvest_sources'];
		}

		if ( isset($_GET['harvest_sources']))
		{
			$harvest_sources = explode('|', $_GET['harvest_sources']);
		}

		if ( count($harvest_sources) == 0)
		{
			return ee()->functions->redirect($this->mcp_link(array('method' => 'harvest')));
		}

		$per_batch	= ( ee()->input->get_post('per_batch') === false OR
						! is_numeric( ee()->input->get_post('per_batch'))) ?
							250 : ee()->input->get_post('per_batch');

		$batch		=  ( is_numeric(ee()->input->get_post('batch'))) ? ee()->input->get_post('batch') : 1;

		$done		= array();

		// --------------------------------------------
		//  Find Out What We're Parsing
		// --------------------------------------------

		$harvest_types = array();

		foreach($harvest_sources as $harvest_source)
		{
			foreach(array('channel_categories', 'tag_fields') as $type)
			{
				if (stristr($harvest_source, $type))
				{
					$harvest_types[$type][] = str_replace($type.'_', '', $harvest_source);
				}
			}
		}

		// --------------------------------------------
		//  Switch to the DB's Character Set from the Tag Character Set
		// --------------------------------------------



		// --------------------------------------------
		//  Let's Prepare for Some Parsing!
		// --------------------------------------------

		$data  = array();
		$total = 0;

		foreach($harvest_types as $harvest_type => $harvest_items)
		{
			$done[$harvest_type] = false;
			$data[$harvest_type] = array();

			//	----------------------------------------
			//	Query Channel Categories
			//	----------------------------------------

			if ( $harvest_type == 'channel_categories')
			{
				$sql	= "SELECT 		%sql
						   FROM 		exp_channel_titles AS wt
						   LEFT JOIN 	exp_category_posts cp
						   ON 			wt.entry_id = cp.entry_id
						   LEFT JOIN 	exp_categories c
						   ON 			c.cat_id = cp.cat_id
						   WHERE 		wt.channel_id
						   IN 			('".implode( "','", ee()->db->escape_str($harvest_items) )."')";

				//	----------------------------------------
				//	 Check Total
				//	----------------------------------------

				$query	= ee()->db->query( str_replace( "%sql", "COUNT(*) AS count", $sql ) );
				$query_row = $query->row_array();

				if ($query_row['count'] == 0)
				{
					$done[$harvest_type] = true;
					continue;
				}

				if ($query_row['count'] > $total)
				{
					$total = $query_row['count'];
				}

				//	----------------------------------------
				//	Get data
				//	----------------------------------------

				$sql	.= " ORDER BY entry_id ASC LIMIT " . ( ( $batch - 1 ) * $per_batch ).",".$per_batch;

				$query	= ee()->db->query(
					str_replace(
						"%sql",
						"DISTINCT wt.entry_id, wt.site_id, wt.channel_id, c.cat_name",
						$sql
					)
				);

				if ($query->num_rows() == 0)
				{
					$done[$harvest_type] = true;
					continue;
				}
				elseif($query->num_rows() < $per_batch OR $query_row['count'] == ( $batch * $per_batch ))
				{
					$done[$harvest_type] = true;
				}

				//	----------------------------------------
				//	Prep data
				//	----------------------------------------

				$entries	= array();

				foreach ( $query->result_array() as $row )
				{
					if ( trim($row['cat_name']) == '' ) continue;
					$entries[ $row['entry_id'] ][ 'channel_id' ]	= $row['channel_id'];
					$entries[ $row['entry_id'] ][ 'site_id' ]	= $row['site_id'];
					$entries[ $row['entry_id'] ][ 'str' ][]		= stripslashes($row['cat_name']);
				}

				$data[$harvest_type] = $entries;
			}
			elseif ( $harvest_type == 'tag_fields' )
			{
				//	----------------------------------------
				//	Discover Our Fields
				//	----------------------------------------

				$fields	= array();

				$query = ee()->db->query(
					"SELECT tag_preference_name, tag_preference_value
					 FROM 	exp_tag_preferences
					 WHERE 	tag_preference_name
					 LIKE 	'%_tag_field'
					 AND 	tag_preference_value != '0'"
				);


				foreach($query->result_array() AS $row)
				{
					foreach($harvest_items as $channel_id)
					{
						if ($row['tag_preference_name'] == $channel_id.'_tag_field')
						{
							$fields[$channel_id] = $row['tag_preference_value'];
						}
					}
				}

				// --------------------------------------------
				//  Validate Fields - They Might Have Deleted Since Saving Preferences?
				// --------------------------------------------

				$query = ee()->db->query(
					"SELECT COUNT(*) AS count
					 FROM 	exp_channel_fields
					 WHERE 	field_id
					 IN 	('" . implode("','", ee()->db->escape_str(array_unique($fields))) . "')"
				);

				if ($query->row('count') != count(array_unique(array_values($fields))))
				{
					return $this->show_error(lang('error_invalid_custom_fields_for_channels'));
				}

				//	----------------------------------------
				//	 Initial Query of Data Retrieval
				//	----------------------------------------

				$sql	= "SELECT 		%sql
						   FROM 		exp_channel_titles AS wt
						   LEFT JOIN 	exp_channel_data AS wd
						   ON 			wt.entry_id = wd.entry_id
						   WHERE		wt.channel_id
						   IN 			('".implode( "','", ee()->db->escape_str(array_keys($fields)) )."')";

				//	----------------------------------------
				//	 Check Total
				//	----------------------------------------

				$query	= ee()->db->query( str_replace( "%sql", "COUNT(*) AS count", $sql ) );
				$query_row = $query->row_array();

				if ($query_row['count'] == 0)
				{
					$done[$harvest_type] = true;
					continue;
				}

				if ($query_row['count'] > $total)
				{
					$total = $query_row['count'];
				}

				//	----------------------------------------
				//	Get data
				//	----------------------------------------

				$sql	.= " ORDER BY entry_id ASC LIMIT ".(( $batch - 1 ) * $per_batch).",".$per_batch;

				$query	= ee()->db->query(
					str_replace(
						"%sql",
						"wt.entry_id, wt.site_id, wt.channel_id, wd.field_id_" .
							implode( ", wd.field_id_", $fields ),
						$sql
					)
				);

				// There is nothing to harvest, so we are done!
				if ($query->num_rows() == 0)
				{
					$done[$harvest_type] = true;
					continue;
				}
				// The number left is less than or equal to the number per batch, so this is the last batch!
				elseif($query->num_rows() < $per_batch OR $query_row['count'] == ( $batch * $per_batch ))
				{
					$done[$harvest_type] = true;
				}

				//	----------------------------------------
				//	Prep data
				//	----------------------------------------

				$entries	= array();

				foreach ( $query->result_array() as $row )
				{
					if ( ! isset( $fields[ $row['channel_id'] ] ) ) continue;

					$id	= 'field_id_'.$fields[ $row['channel_id'] ];

					if ( $row[ $id ] == '' ) continue;

					$entries[ $row['entry_id'] ][ 'channel_id' ]	= $row['channel_id'];
					$entries[ $row['entry_id'] ][ 'site_id' ]					= $row['site_id'];
					$entries[ $row['entry_id'] ][ 'str' ]						= $row[$id];
				}

				$data[$harvest_type] = $entries;
			}
		}

		// --------------------------------------------
		//  Commence Parsing!
		// --------------------------------------------

		if ( ! class_exists('Tag') )
		{
			require $this->addon_path.'mod.tag.php';
		}

		foreach($data as $harvest_type => $entries)
		{
			if ( $harvest_type == 'channel_categories')
			{
				$Tag = new Tag();

				foreach ( $entries as $key => $val )
				{
					$Tag->remote		= false;
					$Tag->batch			= true;
					$Tag->entry_id		= $key;
					$Tag->site_id		= $val['site_id'];
					$Tag->channel_id	= $val['channel_id'];
					$Tag->str			= implode( "\n", $val['str'] );
					$Tag->tag_group_id  = ee()->input->post('tag_group');
					$Tag->parse();
				}
			}
			elseif ( $harvest_type == 'tag_fields' )
			{
				$Tag	= new Tag();

				foreach ( $entries as $key => $val )
				{
					$Tag->remote		= false;
					$Tag->batch			= true;
					$Tag->entry_id		= $key;
					$Tag->content_id	= $Tag->channel_id = $val['channel_id'];
					$Tag->site_id		= $val['site_id'];
					$Tag->str			= $val['str'];
					$Tag->tag_group_id  = ee()->input->post('tag_group');
					$Tag->parse();
				}
			}
		}

		//	----------------------------------------
		//	Return
		//	----------------------------------------

		return array(
			'done'				=> $done,
			'harvest_sources'	=> $harvest_sources,
			'batch'				=> $batch,
			'per_batch'			=> $per_batch,
			'total'				=> round($total/$per_batch),
			'tag_group'			=> ee()->input->post('tag_group')
		);
	}
	// END _harvest()


	//	----------------------------------------
	//	Recount Tag Statistics
	//	---------------------------------------

	public function recount( $return = true )
	{


		// --------------------------------------------
		//	Set num per batch and start
		// --------------------------------------------

		$num	= ( ee()->input->get_post('num') !== false AND is_numeric( ee()->input->get_post('num') ) === true ) ? ee()->input->get_post('num'): 1000;
		$start	= ( ee()->input->get_post('start') !== false AND is_numeric( ee()->input->get_post('start') ) === true ) ? ee()->input->get_post('start'): 0;

		//	----------------------------------------
		//	 Check Totals
		//	----------------------------------------

		$countq		= ee()->db->query( "SELECT COUNT(*) AS count FROM exp_tag_tags" );
		$remainingq	= ee()->db->query( "SELECT site_id FROM exp_tag_tags LIMIT ".ee()->db->escape_str( $start ).",".ee()->db->escape_str( $num ) );

		//	----------------------------------------
		//	Any tags at all?
		//	----------------------------------------

		if ( $countq->num_rows() == 0 OR $countq->row('count') == 0 )
		{
			ee()->functions->redirect($this->mcp_link(array(
				'method' => 'recount',
				'msg' => 'no_tags_to_recount'
			)));
			exit;
		}

		//	----------------------------------------
		//	Are we done?
		//	----------------------------------------

		if ( $remainingq->num_rows() == 0 )
		{
			ee()->functions->redirect($this->mcp_link(array(
				'method' => 'recount',
				'msg' => 'tags_successfully_recounted'
			)));
			exit;
		}

		// --------------------------------------------
		//	Is this our first pass through?
		// --------------------------------------------

		if ( $start == 0 )
		{
			// --------------------------------------------
			//  Old Entries Not Removed in Previous Versions
			// --------------------------------------------

			// Disabled because it was deleting Tags from entries submitted via SAEF from a Guest member.
			//ee()->db->query("DELETE FROM exp_tag_entries WHERE exp_tag_entries.author_id = 0");

			ee()->db->query("DELETE te
							FROM exp_tag_entries AS te
							LEFT JOIN exp_members AS m ON te.author_id = m.member_id
							WHERE te.author_id != 0
							AND m.member_id IS NULL");

			ee()->db->query("DELETE te FROM exp_tag_entries AS te
							LEFT JOIN exp_channel_titles AS wt ON te.entry_id = wt.entry_id
							WHERE te.type = 'channel'
							AND wt.entry_id IS NULL");

			//	----------------------------------------
			//	Remove Orphans
			//	----------------------------------------

			ee()->db->query("DELETE tt
							FROM exp_tag_tags AS tt
							LEFT JOIN exp_tag_entries AS te ON te.tag_id = tt.tag_id
							WHERE te.tag_id IS NULL");
		}

		//	----------------------------------------
		//	Recount stats for all existing tags
		//	----------------------------------------

		$query	= ee()->db->query( "SELECT tag_id FROM exp_tag_tags LIMIT ".ee()->db->escape_str( $start ).",".ee()->db->escape_str( $num ) );

		$tags	= array();

		foreach ( $query->result_array() as $row )
		{
			$tags[]	= $row['tag_id'];
		}

		$this->lib('Utils')->recount_tags($tags);

		//	----------------------------------------
		//	Loop and refresh page
		//	----------------------------------------

		$start	+=	$num;

		$url	= $this->base.'P='.'recount'.AMP.'num='.$num.AMP.'start='.$start;

		$data	= array(
						'title'		=> lang('recount'),
						'heading'	=> lang('recount'),
						'content'	=> str_replace( array( '%num', '%start', '%total' ), array( $num, $start, $countq->row('count') ), lang('tag_recount_running') ),
						'rate'		=> 2,
						'link'		=> array( $url, 'click here to get there' ),
						'redirect'	=> $url
						);

		ee()->output->show_message( $data );
	}
	// END recount()


	// -----------------------------------------------------------------

	/**
	 * Code pack page
	 *
	 * @access public
	 * @param	string	$message	lang line for update message
	 * @return	string				html output
	 */

	public function code_pack($message = '')
	{
		$this->prep_message($message, TRUE, TRUE);

		// --------------------------------------------
		//	Load vars from code pack lib
		// --------------------------------------------

		$codePack = $this->lib('CodePack');
		$cpl      =& $codePack;

		$cpl->autoSetLang = true;

		$cpt = $cpl->getTemplateDirectoryArray(
			$this->addon_path . 'code_pack/'
		);

		// --------------------------------------------
		//  Start sections
		// --------------------------------------------

		$sections = array();

		$main_section = array();

		// --------------------------------------------
		//  Prefix
		// --------------------------------------------

		$main_section['template_group_prefix'] = array(
			'title'		=> lang('template_group_prefix'),
			'desc'		=> lang('template_group_prefix_desc'),
			'fields'	=> array(
				'prefix' => array(
					'type'		=> 'text',
					'value'		=> $this->lower_name . '_',
				)
			)
		);

		// --------------------------------------------
		//  Templates
		// --------------------------------------------

		$main_section['templates'] = array(
			'title'		=> lang('groups_and_templates'),
			'desc'		=> lang('groups_and_templates_desc'),
			'fields'	=> array(
				'templates' => array(
					'type'		=> 'html',
					'content'	=> $this->view('code_pack_list', compact('cpt')),
				)
			)
		);

		// --------------------------------------------
		//  Compile
		// --------------------------------------------

		$this->cached_vars['sections'][] = $main_section;

		$this->cached_vars['form_url'] = $this->mcp_link(array(
			'method' => 'code_pack_install'
		));

		$this->cached_vars['box_class'] = 'code_pack_box';

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'				=> $this->mcp_link(array(
				'method' => 'code_pack_install'
			)),
			'cp_page_title'			=> lang('demo_templates') .
										'<br /><i>' . lang('demo_description') . '</i>' ,
			'save_btn_text'			=> 'install_demo_templates',
			'save_btn_text_working'	=> 'btn_saving'
		);

		ee('CP/Alert')->makeInline('shared-form')
		->asIssue()
		->addToBody(lang('prefix_error'))
		->cannotClose()
		->now();

		return $this->mcp_view(array(
			'file'		=> 'code_pack_form',
			'highlight'	=> 'demo_templates',
			'pkg_css'	=> array('mcp_defaults'),
			'pkg_js'	=> array('code_pack'),
			'crumbs'	=> array(
				array(lang('demo_templates'))
			)
		));
	}
	//END code_pack


	// --------------------------------------------------------------------

	/**
	 * Code Pack Install
	 *
	 * @access public
	 * @param	string	$message	lang line for update message
	 * @return	string				html output
	 */

	public function code_pack_install()
	{
		$prefix = trim((string) ee()->input->get_post('prefix'));

		if ($prefix === '')
		{
			return ee()->functions->redirect($this->mcp_link(array(
				'method' => 'code_pack'
			)));
		}

		// -------------------------------------
		//	load lib
		// -------------------------------------

		$codePack = $this->lib('CodePack');
		$cpl      =& $codePack;

		$cpl->autoSetLang = true;

		// -------------------------------------
		//	¡Las Variables en vivo! ¡Que divertido!
		// -------------------------------------

		$variables = array();

		$variables['code_pack_name']	= $this->lower_name . '_code_pack';
		$variables['code_pack_path']	= $this->addon_path . 'code_pack/';
		$variables['prefix']			= $prefix;

		// -------------------------------------
		//	install
		// -------------------------------------

		$return = $cpl->installCodePack($variables);

		//--------------------------------------------
		//	Table
		//--------------------------------------------

		$table = ee('CP/Table', array(
			'sortable'	=> false,
			'search'	=> false
		));

		$tableData = array();

		//--------------------------------------------
		//	Errors or regular
		//--------------------------------------------

		if (! empty($return['errors']))
		{
			foreach ($return['errors'] as $error)
			{
				$item = array();

				//	Error
				$item[]	= lang('error');

				//	Label
				$item[]	= $error['label'];

				//	Field type
				$item[]	= str_replace(
					array(
						'%conflicting_groups%',
						'%conflicting_data%',
						'%conflicting_global_vars%'
					),
					array(
						implode(", ", $return['conflicting_groups']),
						implode("<br />", $return['conflicting_global_vars'])
					),
					$error['description']
				);

				$tableData[] = $item;
			}
		}
		else
		{
			foreach ($return['success'] as $success)
			{
				$item = array();

				//	Error
				$item[]	= lang('success');

				//	Label
				$item[]	= $success['label'];

				//	Field type
				if (isset($success['link']))
				{
					$item[]	= array(
						'content'	=> $success['description'],
						'href'		=>$success['link']
					);
				}
				else
				{
					$item[]	= str_replace(
						array(
							'%template_count%',
							'%global_vars%',
							'%success_link%'
						),
						array(
							$return['template_count'],
							implode("<br />", $return['global_vars']),
							''
						),
						$success['description']
					);
				}

				$tableData[] = $item;
			}
		}

		$table->setColumns(array(
			'status',
			'description',
			'details',
		));

		$table->setData($tableData);

		$table->setNoResultsText('no_results');

		$this->cached_vars['table'] 	= $table->viewData();

		$this->cached_vars['form_url']	= '';

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'code_pack_install',
			'highlight'	=> 'demo_templates',
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('demo_templates'))
			)
		));
	}
	//END code_pack_install


	// --------------------------------------------------------------------

	/**
	 * Tag Browse	(ajax call)
	 *
	 * @access public
	 * @return	void
	 */

	public function tag_browse()
	{
		ee()->lang->loadfile( 'tag' );

		//	----------------------------------------
		//	Handle existing
		//	----------------------------------------

		$existing	= array();

		if ( ee()->input->get_post('existing') !== false )
		{
			$existing	= explode( "||", ee('Security/XSS')->clean(ee()->input->get_post('existing')) );
		}

		//	----------------------------------------
		//	Query and construct
		//	----------------------------------------



		$extra = '';

		if (ee()->input->get_post('msm_tag_search') !== 'y')
		{
			$extra = " AND site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
		}

		if (ee()->input->get_post('str') == '*')
		{
			$query	= ee()->db->query("SELECT DISTINCT tag_name AS name
									   FROM exp_tag_tags
									   WHERE tag_name NOT IN ('".implode( "','", ee()->db->escape_str( $existing ) )."')
									   {$extra}
									   ORDER BY tag_name" );
		}
		else
		{
			$str 	= $this->lib('Utils')->clean_str( ee()->input->get_post('str') );

			$query	= ee()->db->query("SELECT DISTINCT tag_name AS name
									   FROM exp_tag_tags
									   WHERE tag_alpha = '".ee()->db->escape_str( $this->lib('Utils')->first_character($str) )."'
									   AND tag_name LIKE '".ee()->db->escape_str( $str )."%'
									   AND tag_name NOT IN ('".implode( "','", ee()->db->escape_str( $existing ) )."')
									   {$extra}
									   ORDER BY tag_name" );
		}



		if ( $query->num_rows() == 0 )
		{
			$select = '<div class="message"><p>'.lang('no_matching_tags').'</p></div>';
		}
		else
		{
			$select	= '<ul>';

			foreach ( $query->result_array() as $row )
			{
				$select	.= '<li><a href="#">'.$row['name']."</a></li>";
			}

			$select	.= '</ul>';
		}

		@header("HTTP/1.0 200 OK");
		@header("HTTP/1.1 200 OK");

		exit($select);
	}
	// END AJAX browse


	// --------------------------------------------------------------------

	/**
	 * tag_autocomplete
	 *
	 * @access	public
	 * @return  null
	 */

	public function tag_autocomplete()
	{
		return $this->lib('Utils')->tag_autocomplete(array('tag_name'));
	}
	//END tag_autocomplete()


	// --------------------------------------------------------------------

	/**
	 * tag_suggest
	 *
	 * @access	public
	 * @param	bool	return json?
	 * @return	null
	 */

	public function tag_suggest($json = false)
	{
		//does a system exit
		return $this->lib('Utils')->tag_suggest($json);
	}
	// END _tag_suggest()
}
// END CLASS Tag_mcp
