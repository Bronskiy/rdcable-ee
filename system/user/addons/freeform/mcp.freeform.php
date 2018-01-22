<?php

use EllisLab\ExpressionEngine\Library\CP\Table;
use Solspace\Addons\Freeform\Library\AddonBuilder;

class Freeform_mcp extends AddonBuilder
{
	private $row_limit				= 50;
	private $migration_batch_limit	= 100;
	private $pro_update				= FALSE;
	private $info					= array();
	private $menu					= array();

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	bool		Enable calling of methods based on URI string
	 * @return	string
	 */

	public function __construct()
	{
		parent::__construct('module');

		// --------------------------------------------
		//  Prep menu
		// --------------------------------------------

		$this->menu = $menu	= array(
			'forms' => array(
				'link'  => $this->base,
				'title' => lang('forms'),
				'nav_button'	=> array(
					'title'	=> lang('new'),
					'link'  => $this->mcp_link(array(
						'method' => 'edit_form'
					)),
				),
			),
			'fields' => array(
				'link'  => $this->mcp_link(array(
					'method' => 'fields'
				)),
				'title' => lang('fields'),
				'nav_button'	=> array(
					'title'	=> lang('new'),
					'link'  => $this->mcp_link(array(
						'method' => 'edit_field'
					)),
				),
			),
			'fieldtypes' => array(
				'link'  => $this->mcp_link(array(
					'method' => 'fieldtypes'
				)),
				'title' => lang('fieldtypes'),
			),
			'notifications' => array(
				'link'  => $this->mcp_link(array(
					'method' => 'notifications'
				)),
				'title' => lang('notifications'),
				'nav_button'	=> array(
					'title'	=> lang('new'),
					'link'  => $this->mcp_link(array(
						'method' => 'edit_notification'
					)),
				),
			),
			
			/*'utilities' => array(
				'link'  => $this->mcp_link(array(
					'method' => 'utilities'
				)),
				'title' => lang('utilities'),
			),*/
			
			'email_logs' => array(
				'link'  => $this->mcp_link(array(
					'method' => 'email_logs'
				)),
				'title' => lang('email_logs'),
			),
			'preferences' => array(
				'link'  => $this->mcp_link(array(
					'method' => 'preferences'
				)),
				'title' => lang('preferences'),
			),
			'demo_templates' => array(
				'link'  => $this->mcp_link(array(
					'method' => 'code_pack'
				)),
				'title' => lang('demo_templates'),
			),
			'resources' => array(
				'title' => lang('freeform_resources'),
				'sub_list' => array(
					'product_info' => array(
						'link' => 'https://solspace.com/expressionengine/freeform',
						'title' => lang('freeform_product_info'),
						'external' => true,
					),
					'documentation' => array(
						'link' => $this->docs_url,
						'title' => lang('freeform_documentation'),
						'external' => true,
					),
					'support' => array(
						'link' => 'https://solspace.com/expressionengine/support',
						'title' => lang('freeform_official_support'),
						'external' => true,
					),
				),
			),
		);

		

		$this->set_nav($menu);

		// --------------------------------------------
		//  Various
		// --------------------------------------------

		$this->cached_vars['module_version']		= $this->version;
		$this->cached_vars['inner_nav_links']		= array();

		//avoids AR collisions
		$this->model('preference')->prefs_with_defaults();
		$this->model('preference')->global_prefs_with_defaults();

		// -------------------------------------
		//	run upgrade or downgrade scripts
		// -------------------------------------

		$ffp_pref = $this->model('preference')->global_preference('ffp');

		if ($this->addon_info['freeform_pro'] AND $ffp_pref === 'n' OR
			! $this->addon_info['freeform_pro'] AND $ffp_pref === 'y')
		{
			//update to pro
			$this->updater()->update();
		}

		ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . URL_THIRD_THEMES . 'freeform/css/solspace-fa.css">');
	}
	// END __construct


	//---------------------------------------------------------------------
	// begin views
	//---------------------------------------------------------------------


	// --------------------------------------------------------------------

	/**
	 * index()
	 *
	 * @access	public
	 * @param	string
	 * @return	null
	 */

	public function index($message = '')
	{
		return $this->forms($message);
	}
	// END index


	// --------------------------------------------------------------------

	/**
	 * My Forms
	 *
	 * @access	public
	 * @param	string $message incoming message for flash data
	 * @return	string html output
	 */

	public function forms($message = '')
	{
		$this->prep_message($message);

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		$this->cached_vars['form_right_links']	= array(
			array(
				'link' => $this->mcp_link(array('method' => 'edit_form')),
				'title' => lang('new_form'),
			)
		);

		//--------------------------------------
		//  start vars
		//--------------------------------------

		$paginate			= '';
		$row_count			= 0;

		// -------------------------------------
		//	pagination?
		// -------------------------------------

		if ( ! $this->model('preference')->show_all_sites())
		{
			$this->model('form')->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		$total_results = $this->model('form')->count(array(), FALSE);

		//	----------------------------------------
		//	Pagination
		//	----------------------------------------

		$page	= 0;

		if ($total_results > $this->row_limit)
		{
			$page	= $this->get_post_or_zero('page') ?: 1;

			$mcp_link_array = array(
				'method' => __FUNCTION__
			);

			$this->cached_vars['pagination'] = ee('CP/Pagination', $total_results)
								->perPage($this->row_limit)
								->currentPage($page)
								->render($this->mcp_link($mcp_link_array, false));

			$this->model('form')->limit(
				$this->row_limit,
				($page - 1) * $this->row_limit
			);
		}

		$this->model('form')->order_by('form_label');

		// -------------------------------------
		//	Did they upgrade from FF3?
		// -------------------------------------
		/*
		$this->cached_vars['legacy']		= FALSE;
		$this->cached_vars['migrate_link']	= '';

		if ( $this->lib('Migration')->legacy() === TRUE )
		{
			$this->cached_vars['legacy']		= TRUE;
			$this->cached_vars['migrate_link']	= $this->mcp_link(array('method' => 'utilities'));
		}
		*/

		// -------------------------------------
		//	data
		// -------------------------------------

		$tableData	= array();

		$rows = $this->model('form')->get();

		if ($rows !== FALSE)
		{
			// -------------------------------------
			//	check for composer for each form
			// -------------------------------------

			$form_ids = array();

			$potential_composer_ids = array();

			foreach ($rows as $row)
			{
				$form_ids[] = $row['form_id'];

				if ($this->is_positive_intlike($row['composer_id']))
				{
					$potential_composer_ids[$row['form_id']] = $row['composer_id'];
				}
			}

			$has_composer = array();

			if ( ! empty($potential_composer_ids))
			{
				$composer_ids = $this->model('composer')
									->key('composer_id', 'composer_id')
									->where('preview !=', 'y')
									->where_in(
										'composer_id',
										array_values($potential_composer_ids)
									)
									->get();

				if ( ! empty($composer_ids))
				{
					foreach ($potential_composer_ids as $form_id => $composer_id)
					{
						if (in_array($composer_id, $composer_ids))
						{
							$has_composer[$form_id] = $composer_id;
						}
					}
				}
			}
			//END if ( ! empty($potential_composer_ids))

			// -------------------------------------
			//	rows
			// -------------------------------------

			foreach ($rows as $row)
			{
				$row['submissions_count']		= (
					$this->model('Data')->get_form_submissions_count($row['form_id'])
				);

				$row['moderate_count']			= (
					$this->model('Data')->get_form_needs_moderation_count($row['form_id'])
				);

				$row['has_composer']			= isset(
					$has_composer[$row['form_id']]
				);

				// -------------------------------------
				//	piles o' links
				// -------------------------------------

				$row['form_submissions_link']	= $this->mcp_link(array(
					'method'		=> 'entries',
					'form_id'		=> $row['form_id']
				));

				$row['form_moderate_link']		= $this->mcp_link(array(
					'method'		=> 'moderate_entries',
					'form_id'		=> $row['form_id'],
					'search_status'	=> 'pending'
				));

				$row['form_edit_composer_link'] = $this->mcp_link(array(
					'method'		=> 'form_composer',
					'form_id'		=> $row['form_id']
				));

				$row['form_settings_link']		= $this->mcp_link(array(
					'method'		=> 'edit_form',
					'form_id'		=> $row['form_id']
				));

				$row['form_duplicate_link']		= $this->mcp_link(array(
					'method'		=> 'edit_form',
					'duplicate_id'	=> $row['form_id']
				));

				$tableData[] = array(
					$row['form_id'],
					'label'	=> array(
						'type'		=> 'html',
						'content'	=> $this->view('forms/_row', array('col_type' => 'label', 'row' => $row))
					),
					'submissions'	=> array(
						'type'		=> 'html',
						'content'	=> $this->view('forms/_row', array('col_type' => 'submissions', 'row' => $row))
					),
					'moderate'	=> array(
						'type'		=> 'html',
						'content'	=> $this->view('forms/_row', array('col_type' => 'moderate', 'row' => $row))
					),
					'manage'	=> array(
						'type'		=> 'html',
						'content'	=> $this->view('forms/_row', array('col_type' => 'manage', 'row' => $row))
					),
					array(
						'name'		=> 'selections[]',
						'value'	=> $row['form_id'],
						'data'		=> array(
							'confirm' => lang('form') . ': <b>' . htmlentities($row['form_label'], ENT_QUOTES) . '</b>'
						)
					)
				);
				//END $tableData[] = array(
			}
			//END foreach ($rows as $row)
		}
		//END if ($rows !== FALSE)

		// -------------------------------------
		//	Build table
		// -------------------------------------

		$table = ee('CP/Table', array(
			'sortable'	=> false,
			'search'	=> false,
		));

		$table->setColumns(
			array(
				'id' => array(
					'type'	=> Table::COL_ID
				),
				'form'			=> array(
					'type'	=> 'html'
				),
				'submissions'	=> array(
					'type'	=> 'html'
				),
				'moderate'		=> array(
					'type'	=> 'html'
				),
				'manage'		=> array(
					'type'	=> 'html'
				),
				array(
					'type'	=> Table::COL_CHECKBOX,
					'name'	=> 'selection'
				)
			)
		);

		$table->setData($tableData);

		$table->setNoResultsText('no_forms');

		$this->cached_vars['table'] = $table->viewData(
			$this->mcp_link(array('method' => __FUNCTION__), false)
		);

		// -------------------------------------
		//	Modal for delete confirmation
		// -------------------------------------

		$this->cached_vars['footer'] = array(
			'type'			=> 'bulk_action_form',
			'submit_lang'	=> lang('forms_remove')
		);

		$this->mcp_modal_confirm(array(
			'form_url'	=> $this->mcp_link(array('method' => 'forms_delete')),
			'name'		=> 'forms',
			'kind'		=> lang('forms'),
		));

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'		=> $this->mcp_link(array(
				'method' => 'edit_form'
			)),
			'cp_page_title'	=> lang('forms'),
			'table_head'	=> lang('forms')
		);

		return $this->mcp_view(array(
			'file'			=> 'list',
			'highlight'		=> 'forms',
			'show_message'	=> false,
			'pkg_js'		=> array(),
			'pkg_css'		=> array('mcp_defaults'),
			'crumbs'		=> array(
				array(lang('forms'))
			)
		));
	}
	//END forms


	// --------------------------------------------------------------------

	/**
	 * fields_delete()
	 *
	 * @access	public
	 * @return	string
	 */

	public function fields_delete()
	{
		$message = 'delete_field_success';

		//if everything is all nice and array like, DELORT
		//but one last check on each item to make sure its a number
		if (is_array($_POST['selections']))
		{
			foreach ($_POST['selections'] as $field_id)
			{
				if ($this->is_positive_intlike($field_id))
				{
					$this->lib('Fields')->delete_field($field_id);
				}
			}
		}

		ee()->functions->redirect($this->mcp_link(array(
			'method'	=> 'fields',
			'msg'		=> $message
		)));
	}
	//END fields_delete


	// --------------------------------------------------------------------

	/**
	 * forms_delete()
	 *
	 * @access	public
	 * @return	string
	 */

	public function forms_delete()
	{
		$message = 'delete_form_success';

		//if everything is all nice and array like, DELORT
		//but one last check on each item to make sure its a number
		if (is_array($_POST['selections']))
		{
			foreach ($_POST['selections'] as $form_id)
			{
				if ($this->is_positive_intlike($form_id))
				{
					$this->lib('Forms')->delete_form($form_id);
				}
			}
		}

		//the voyage home
		ee()->functions->redirect($this->mcp_link(array(
			'method'	=> 'index',
			'msg'		=> $message
		)));
	}
	//END forms_delete


	// --------------------------------------------------------------------

	/**
	 * Edit Form
	 *
	 * @access	public
	 * @return	string html output
	 */

	public function edit_form()
	{
		// -------------------------------------
		//	form ID? we must be editing
		// -------------------------------------

		$form_id	= $this->get_post_or_zero('form_id');

		$update	= $this->cached_vars['update'] = ($form_id != 0);

		$title		= lang('new_form');

		// -------------------------------------
		//	default data
		// -------------------------------------

		$inputs = array();

		$input_defaults = $this->model('form')->input_defaults();

		foreach ($input_defaults as $name => $info)
		{
			$inputs[$name] = $info['default'];
		}

		// -------------------------------------
		//	updating?
		// -------------------------------------

		if ($update)
		{
			$form_data = $this->model('form')->get_info($form_id);

			if ($form_data)
			{
				$title = lang('update_form') . ': ' . $form_data['form_label'];

				foreach ($form_data as $key => $value)
				{
					if ($key == 'admin_notification_email')
					{
						$value = str_replace('|', ', ', $value);
					}

					if ($key == 'field_ids')
					{
						$value = ( ! empty($value)) ? implode('|', $value) : '';
					}

					$inputs[$key] = form_prep($value);
				}
			}
			else
			{
				$this->lib('Utils')->full_stop(lang('invalid_form_id'));
			}
		}

		// -------------------------------------
		//	duplicating?
		// -------------------------------------

		$duplicate_id = $this->get_post_or_zero('duplicate_id');

		$inputs['duplicate_id']	= $duplicate_id;
		$inputs['duplicated']	= FALSE;

		if ( ! $update AND $duplicate_id > 0)
		{
			$form_data = $this->model('form')->get_info($duplicate_id);

			if ($form_data)
			{
				foreach ($form_data as $key => $value)
				{
					if (in_array($key, array('form_id', 'form_label', 'form_name')))
					{
						continue;
					}

					if ($key == 'field_ids')
					{
						$value =  ( ! empty($value)) ? implode('|', $value) : '';
					}

					if ($key == 'admin_notification_email')
					{
						$value = str_replace('|', ', ', $value);
					}

					$inputs[$key] = form_prep($value);
				}

				$inputs['duplicated']		= TRUE;
				$inputs['duplicated_from']	= $form_data['form_label'];
			}
		}

		$chosen_fields = array();

		if (isset($form_data['field_ids']) AND
			! empty($form_data['field_ids']) AND
			isset($form_data['field_order']) AND
			! empty($form_data['field_order']))
		{
			$field_ids = $form_data['field_ids'];

			if ( ! is_array($field_ids))
			{
				$field_ids = $this->lib('Utils')->pipe_split($field_ids);
			}

			$field_order = $form_data['field_order'];

			if ( ! is_array($field_order))
			{
				$field_order = $this->lib('Utils')->pipe_split($field_order);
			}

			$missing_ids = array_diff($field_ids, $field_order);

			$inputs['field_order'] = implode('|', array_merge($field_order, $missing_ids));
		}

		if (empty($inputs['field_order']))
		{
			$inputs['field_order'] = $inputs['field_ids'];
		}

		if ($this->addon_info['freeform_pro'])
		{
			$inputs['form_type'] = (
				! isset($form_data['composer_id']) ||
				$form_data['composer_id'] == 0
			) ?
				'template' :
				'composer';
		}
		else
		{
			$inputs['form_type'] = 'template';
		}

		$this->cached_vars['field_order'] = $inputs['field_order'];

		// -------------------------------------
		//	available fields for field choice
		// -------------------------------------

		$available_fields	= $this->model('field')->get();

		$available_fields	= ($available_fields !== FALSE) ?
													$available_fields :
													array();

		$input_defaults['form_fields']['content'] = $this->setup_select_fields(
			$this->model('field')->key('field_id', 'field_label')->get(),
			$this->lib('Utils')->pipe_split($inputs['field_order'])
		);

		//$input_defaults['form_fields']['choices'] = $this->model('field')->key('field_id', 'field_label')->get();
		//$inputs['form_fields'] = $this->lib('Utils')->pipe_split($inputs['field_order']);

		// -------------------------------------
		//	build form sections
		// -------------------------------------

		$sections = array();

		$main_section = array();

		// -------------------------------------
		//	Hidden fields
		// -------------------------------------

		$hidden = array();

		// -------------------------------------
		//	Shown fields
		// -------------------------------------

		foreach ($input_defaults as $short_name => $data)
		{
			if ($data['type'] == 'hidden')
			{
				$hidden[$short_name] = isset($inputs[$short_name]) ?
									$inputs[$short_name] :
									$data['default'];

				continue;
			}

			$main_section[$short_name] = array(
				'title'		=> lang($data['label']),
				'desc'		=> lang($data['desc'] ?: ''),
				'fields'	=> array(
					$short_name => array_merge($data, array(
						'value'		=> isset($inputs[$short_name]) ?
										$inputs[$short_name] :
										$data['default'],
						'required'	=> isset($data['required']) ?
											$data['required'] :
											false
					))
				)
			);
		}
		//END foreach ($input_defaults as $short_name => $data)

		if ( ! empty($hidden))
		{
			$this->cached_vars['form_hidden'] = $hidden;
		}

		$sections[] = $main_section;

		//	----------------------------------------
		//	Load vars
		//	----------------------------------------

		$this->cached_vars['sections'] = $sections;

		$this->cached_vars['form_url'] = $this->mcp_link(array(
			'method' => 'save_form'
		));

		$this->cached_vars['addon_info'] = $this->addon_info;

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'				=> $this->mcp_link(array(
				'method' => 'save_form'
			)),
			'cp_page_title'			=> $title,
			'save_btn_text'			=> 'btn_save',
			'save_btn_text_working'	=> 'btn_saving'
		);

		// -------------------------------------
		//	js libs
		// -------------------------------------

		$this->load_fancybox();

		ee()->cp->add_js_script(array(
			'ui'	=> array('draggable', 'droppable', 'sortable')
		));

		// -------------------------------------
		//	view
		// -------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'edit_form',
			'highlight'	=> 'forms',
			'show_message' => false,
			'pkg_js'	=> array(
				'standard_cp.min',
				'edit_form',
				'jquery.smooth-scroll.min'
			),
			'pkg_css'	=> array('mcp_defaults', 'edit_form'),
			'crumbs'	=> array(
				array($title)
			)
		));
	}
	//END edit_form


	// --------------------------------------------------------------------

	/**
	 * Setup Select Fields
	 *
	 * Builds a field selection snootching the view and JS from
	 * the relationship fieldtype
	 *
	 * @access	protected
	 * @param	array	$available_fields	available choices (value => label)
	 * @param	array 	$order				choices in order of appearance (value)
	 * @return	string						html view for field in form
	 */

	protected function setup_select_fields($available_fields, $order = array())
	{
		//---------------------------------------------
		//  Dependencies
		//---------------------------------------------

		$multiple	= true;
		$channels	= array();
		$field_name = 'form_fields';
		$settings   = '';
		$selected	= $order;
		//make order array keys with blank entries
		//because the related field is weird like that
		$related	= count($order) ?
				array_combine($order, array_fill(0, count($order), '')) :
				array();
		$entries	= array();

		sort($selected);

		ee()->cp->add_js_script(array(
			'plugin'	=> 'ee_interact.event',
			'file'		=> 'fields/relationship/cp',
			'ui'		=> 'sortable'
		));

		// -------------------------------------
		//	fields ('entries')
		// -------------------------------------

		if ( ! empty($available_fields))
		{
			foreach ($available_fields as $field_id => $field_label)
			{
				$new					= new stdClass();
				$channel				= new stdClass();
				$channel->channel_id	= 0;
				$channel->channel_title	= '';
				$new->Channel			= $channel;
				$new->title				= $field_label;
				$new->entry_id			= $field_id;

				if (isset($related[$field_id]))
				{
					$related[$field_id] = $new;
				}

				$entries[]	= $new;
			}
		}

		//---------------------------------------------
		//  Field view
		//---------------------------------------------

		$field_view	= ee('View')->make('relationship:publish')
						->render(compact(
							'field_name',
							'entries',
							'selected',
                            'settings',
							'related',
							'multiple',
							'channels'
						));

		//---------------------------------------------
		//  Change references to 'items' to 'authors'
		//---------------------------------------------
		//	We change references to be 'author' oriented
		//	and we also hide the reorder handles on the
		//	related authors so that they cannot be drag &
		//	drop reordered.
		//---------------------------------------------

		$field_view	= str_replace(
			array(
				lang('item_to_relate_with'),
				lang('items_to_relate_with'),
				lang('items_related_to'),
				lang('no_entry_related'),
				lang('search_avilable_entries'),
				lang('search_available_entries'),
				lang('search_related_entries'),
				lang('no_entries_found'),
				lang('no_entries_related'),
				lang('items_related_to'),
				'<div class="filters">',
				'class="relate-actions"',
				//last because its generic and can affect
				//other items
				lang('items'),
			),
			array(
				lang('available_fields'),
				lang('available_fields'),
				lang('selected_fields'),
				'',
				'',
				'',
				'',
				lang('no_fields'),
				lang('no_fields_chosen'),
				'',
				'<div class="filters" style="display:none">',
				'class="relate-actions" style="display:none"',
				lang('fields'),
			),
			$field_view
		);

		//---------------------------------------------
		//  Return
		//---------------------------------------------

		//this has to be wrapped so the JS works
		return '<div class="publish">' . $field_view . '</div>';
	}
	//END setup_select_fields


	// --------------------------------------------------------------------

	/**
	 * form composer
	 *
	 * ajax form and field builder
	 *
	 * @access	public
	 * @param	string message lang line
	 * @return	string html output
	 */

	public function form_composer($message = '')
	{

		// -------------------------------------
		//	form_id
		// -------------------------------------

		$form_id	= ee()->input->get_post('form_id', TRUE);
		$form_data	= $this->model('form')->get_info($form_id);

		if ( ! $form_data)
		{
			return $this->lib('Utils')->full_stop(lang('invalid_form_id'));
		}

		$update = $form_data['composer_id'] != 0;

		// -------------------------------------
		//	data
		// -------------------------------------

		$this->cached_vars['form_data']		= $form_data;

		// -------------------------------------
		//	fields for composer
		// -------------------------------------



		if ( ! $this->model('preference')->show_all_sites())
		{
			$this->model('field')
				->where('site_id', ee()->config->item('site_id'));
		}

		$available_fields = $this->model('field')
								->where('composer_use', 'y')
								->order_by('field_label')
								->key('field_name')
								->get();

		$available_fields = ($available_fields !== FALSE) ?
								$available_fields :
								array();

		// -------------------------------------
		//	templates
		// -------------------------------------



		if ( ! $this->model('preference')->show_all_sites())
		{
			$this->model('template')
				->where('site_id', ee()->config->item('site_id'));
		}

		$available_templates =  $this->model('template')
									->where('enable_template', 'y')
									->order_by('template_label')
									->key('template_id', 'template_label')
									->get();

		$available_templates = ($available_templates !== FALSE) ?
									$available_templates :
									array();

		// -------------------------------------
		//	get field output for composer
		// -------------------------------------

		$field_composer_output	= array();
		$field_id_list			= array();

		foreach ($available_fields as $field_name => $field_data)
		{
			$field_id_list[$field_data['field_id']] = $field_name;

			//encode to keep JS from running
			//camel case because its exposed in JS
			$field_composer_output[$field_name] = $this->composer_field_data(
				$field_data['field_id'],
				$field_data,
				TRUE
			);
		}

		$this->cached_vars['field_id_list']					= json_encode($field_id_list);
		$this->cached_vars['field_composer_output_json']	= json_encode($field_composer_output);
		$this->cached_vars['available_fields']				= $available_fields;
		$this->cached_vars['available_templates']			= $available_templates;
		$this->cached_vars['prohibited_field_names']		= $this->model('Data')->prohibited_names;
		$this->cached_vars['notifications']					= $this->model('notification')->get_available();
		$this->cached_vars['disable_missing_submit_warning'] = $this->check_yes(
			$this->model('preference')->preference('disable_missing_submit_warning')
		);

		// -------------------------------------
		//	previous composer data?
		// -------------------------------------

		$composer_data = '{}';

		if ($form_data['composer_id'] > 0)
		{


			$composer = $this->model('composer')
							->select('composer_data')
							->where('composer_id', $form_data['composer_id'])
							->get_row();

			if ($composer !== FALSE)
			{
				$composer_data_test = json_decode($composer['composer_data']);

				if ($composer_data_test)
				{
					$composer_data = $composer['composer_data'];
				}
			}
		}

		$this->cached_vars['composer_layout_data'] = $composer_data;

		// ----------------------------------------
		//	Load vars
		// ----------------------------------------

		$this->cached_vars['lang_allowed_html_tags'] = (
			lang('allowed_html_tags') .
			"&lt;" . implode("&gt;, &lt;", $this->model('Data')->allowed_html_tags) . "&gt;"
		);

		$this->cached_vars['captcha_dummy_url'] = $this->theme_url .
													'images/captcha.png';

		$this->cached_vars['new_field_url'] = $this->mcp_link(array(
			'method'	=> 'edit_field',
			//this builds a URL, so yes this is intentionally a string
			'modal'	=> 'true'
		), TRUE);

		$this->cached_vars['field_data_url'] = $this->mcp_link(array(
			'method'	=> 'composer_field_data'
		), TRUE);

		$this->cached_vars['composer_preview_url'] = $this->mcp_link(array(
			'method'	=> 'composer_preview',
			'form_id'	=> $form_id
		), TRUE);

		$this->cached_vars['composer_ajax_save_url'] = $this->mcp_link(array(
			'method'	=> 'save_composer_data',
			'form_id'	=> $form_id
		), TRUE);

		//
		$this->cached_vars['composer_save_url'] = $this->mcp_link(array(
			'method'	=> 'save_composer_data',
			'form_id'	=> $form_id
		));

		$this->cached_vars['allowed_html_tags'] = "'" .
			implode("','", $this->model('Data')->allowed_html_tags) . "'";

		// -------------------------------------
		//	js libs
		// -------------------------------------

		$this->load_fancybox();

		ee()->cp->add_js_script(array(
			'ui'		=> array('sortable', 'draggable', 'droppable'),
			'file'		=> array('underscore')
		));

		//we dont have any messages we want to deal with here yet
		//$this->prep_message($message);

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'composer',
			'highlight'	=> 'forms',
			'pkg_js'	=> array(
				'standard_cp.min',
				'edit_field_cp.min',
				'composer_cp.min',
				'security.min'
			),
			'pkg_css'	=> array(
				'mcp_defaults',
				'composer_interface'
			),
			'crumbs'	=> array(
				array(lang('composer'))
			)
		));
	}
	//END form_composer


	// --------------------------------------------------------------------

	/**
	 * Composer preview
	 *
	 * @access	public
	 * @return	mixed	ajax return if detected or else html without cp
	 */

	public function composer_preview()
	{
		$form_id		= $this->get_post_or_zero('form_id');
		$template_id	= $this->get_post_or_zero('template_id');
		$composer_id	= $this->get_post_or_zero('composer_id');
		$preview_id		= $this->get_post_or_zero('preview_id');
		$subpreview		= (ee()->input->get_post('subpreview') !== FALSE);
		$composer_page	= $this->get_post_or_zero('composer_page');

		if ( ! $this->model('form')->valid_id($form_id))
		{
			$this->lib('Utils')->full_stop(lang('invalid_form_id'));
		}

		// -------------------------------------
		//	is this a preview?
		// -------------------------------------

		if ($preview_id > 0)
		{
			$preview_mode	= TRUE;
			$composer_id	= $preview_id;
		}

		$page_count = 1;

		// -------------------------------------
		//	main output or sub output?
		// -------------------------------------

		if ( ! $subpreview)
		{
			// -------------------------------------
			//	get composer data and build page count
			// -------------------------------------

			if ($composer_id > 0)
			{


				$composer = $this->model('composer')
								->select('composer_data')
								->where('composer_id', $composer_id)
								->get_row();

				if ($composer !== FALSE)
				{
					$composer_data = json_decode(
						$composer['composer_data'],
						TRUE
					);

					if ($composer_data AND
						 isset($composer_data['rows']) AND
						 ! empty($composer_data['rows']))
					{
						foreach ($composer_data['rows'] as $row)
						{
							if ($row == 'page_break')
							{
								$page_count++;
							}
						}
					}
				}
			}

			$page_url = array();

			for ($i = 1, $l = $page_count; $i <= $l; $i++)
			{
				$page_url[] = $this->mcp_link(array(
					'method'		=> __FUNCTION__,
					'form_id'		=> $form_id,
					'template_id'	=> $template_id,
					'preview_id'	=> $preview_id,
					'subpreview'	=> 'true',
					'composer_page'	=> $i
				));
			}

			$this->cached_vars['page_url']				= $page_url;
			$this->cached_vars['default_preview_css']	= file_get_contents($this->addon_path . 'css/composer_preview.css');
			$this->cached_vars['jquery_src']			= URL_THEMES . 'asset/javascript/' . PATH_JS . '/jquery/jquery.js';

			$html = $this->view('composer_preview');
		}
		else
		{

			$subhtml = "{exp:freeform:composer form_id='$form_id'";
			$subhtml .= ($composer_page > 1) ? " multipage_page='" . $composer_page . "'" : '';
			$subhtml .= ($template_id > 0) ? " composer_template_id='" . $template_id . "'" : '';
			$subhtml .= ($preview_id > 0) ? " preview_id='" . $preview_id . "'" : '';
			$subhtml .= "}";
			$html = $this->lib('Utils')->template()->process_string_as_template($subhtml);
		}

		if ($this->is_ajax_request())
		{

			exit($this->send_ajax_response(array(
				'success'	=> TRUE,
				'html'		=> $html
			)));
		}
		else
		{
			exit($html);
		}
	}
	//end composer preview


	// --------------------------------------------------------------------

	/**
	 * entries
	 *
	 * @access	public
	 * @param	string	$message	message lang line
	 * @param	bool	$moderate	are we moderating?
	 * @param   bool 	$export	export?
	 * @return	string				html output
	 */

	public function entries($message = '' , $moderate = FALSE, $export = FALSE)
	{
		// -------------------------------------
		//  Messages
		// -------------------------------------

		$this->prep_message($message);

		// -------------------------------------
		//	moderate
		// -------------------------------------

		$search_status = ee()->input->get_post('search_status');

		$moderate = (
			$moderate AND
			($search_status == 'pending' OR
				$search_status === FALSE
			)
		);

		//if moderate and search status was not submitted, fake into pending
		if ($moderate AND $search_status === FALSE)
		{
			$_POST['search_status'] = 'pending';
		}

		$this->cached_vars['moderate']	= $moderate;
		$this->cached_vars['method']	= $method = (
			$moderate ? 'moderate_entries' : 'entries'
		);

		// -------------------------------------
		//	form data? legit? GTFO?
		// -------------------------------------

		$form_id = ee()->input->get_post('form_id');

		//form data does all of the proper id validity checks for us
		$form_data = $this->model('form')->get_info($form_id);

		if ( ! $form_data)
		{
			$this->lib('Utils')->full_stop(lang('invalid_form_id'));
			exit();
		}

		$this->cached_vars['form_id']		= $form_id;
		$this->cached_vars['form_label']	= $form_data['form_label'];

		// -------------------------------------
		//	status prefs
		// -------------------------------------

		$form_statuses = $this->model('preference')->get_form_statuses();

		$this->cached_vars['form_statuses'] = $form_statuses;

		// -------------------------------------
		//	rest of models
		// -------------------------------------

		$this->model('entry')->id($form_id);

		// -------------------------------------
		//	custom field labels
		// -------------------------------------

		$standard_columns	= $this->get_standard_column_names();

		//we want author instead of author id until we get data
		$possible_columns	= $standard_columns;
		//key = value
		$all_columns		= array_combine($standard_columns, $standard_columns);
		$column_labels		= array();
		$field_column_names = array();

		//field prefix
		$f_prefix			= $this->model('form')->form_field_prefix;

		//keyed labels for the front end
		foreach ($standard_columns as $column_name)
		{
			$column_labels[$column_name] = lang($column_name);
		}

		// -------------------------------------
		//	check for fields with custom views for entry tables
		// -------------------------------------

		$right_links = array();

		//fields in this form
		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			// -------------------------------------
			//	add custom column names and labels
			// -------------------------------------

			//outputs form_field_1, form_field_2, etc for ->select()
			$field_id_name = $f_prefix . $field_id;

			$field_column_names[$field_id_name]			= $field_data['field_name'];
			$all_columns[$field_id_name]				= $field_data['field_name'];

			$column_labels[$field_data['field_name']]	= $field_data['field_label'];
			$column_labels[$field_id_name]				= $field_data['field_label'];


			// -------------------------------------
			//	get field instance
			// -------------------------------------

			$fieldsInstance = $this->lib('Fields')->get_field_instance(
				array(
					'field_id'   => $field_id,
					'field_data' => $field_data
				)
			);
			$instance       =& $fieldsInstance;

            $showInSubmissions = $field_data["submissions_page"] == "y";
            $showInModeration  = $field_data["moderation_page"] == "y";
            if (($moderate && $showInModeration) || (!$moderate && $showInSubmissions)) {
                $possible_columns[] = $field_id;
            }

			// -------------------------------------
			//	do any fields have custom views
			//	to add?
			// -------------------------------------

			if ( ! empty($instance->entry_views))
			{
				foreach ($instance->entry_views as $e_lang => $e_method)
				{
					$right_links[] = array(
						'link'	=> $this->mcp_link(array(
							'method'		=> 'field_method',
							'field_id'		=> $field_id,
							'field_method'	=> $e_method,
							'form_id'		=> $form_id
						)),
						'title'	=> $e_lang
					);
				}
			}
		}

		// -------------------------------------
		//	visible columns
		// -------------------------------------

		$visible_columns = $this->visible_columns($standard_columns, $possible_columns);

		$this->cached_vars['visible_columns']	= $visible_columns;
		$this->cached_vars['column_labels']		= $column_labels;
		$this->cached_vars['possible_columns']	= $possible_columns;
		$this->cached_vars['all_columns']		= $all_columns;

		// -------------------------------------
		//	prep unused from from possible
		// -------------------------------------

		//so so used
		$un_used = array();

		foreach ($possible_columns as $pcid)
		{
			$check = ($this->is_positive_intlike($pcid)) ?
						$f_prefix . $pcid :
						$pcid;

			if ( ! in_array($check, $visible_columns))
			{
				$un_used[] = $check;
			}
		}

		$this->cached_vars['unused_columns'] = $un_used;

		// -------------------------------------
		//	build query
		// -------------------------------------

		//base url for pagination
		$pag_url		= array(
			'method'	=> $method,
			'form_id'	=> $form_id
		);

		//cleans out blank keys from unset
		$find_columns	= array_merge(array(), $visible_columns);
		$must_haves		= array('entry_id');

		// -------------------------------------
		//	search criteria
		//	building query
		// -------------------------------------

		$has_search = FALSE;

		$search_vars = array(
			'search_keywords',
			'search_status',
			'search_date_range',
			'search_date_range_start',
			'search_date_range_end',
			'search_on_field'
		);

		foreach ($search_vars as $search_var)
		{
			$$search_var = ee()->input->get_post($search_var, TRUE);

			$$search_var = urldecode($$search_var);

			//set for output
			$this->cached_vars[$search_var] = (
				($$search_var) ? trim($$search_var) : ''
			);
		}

		// -------------------------------------
		//	search keywords
		// -------------------------------------

		if ($search_keywords AND
			trim($search_keywords) !== '' AND
			$search_on_field AND
			in_array($search_on_field, $visible_columns))
		{
			$this->model('entry')->like(
				$search_on_field,
				$search_keywords
			);

			//pagination
			$pag_url['search_keywords'] = $search_keywords;
			$pag_url['search_on_field'] = $search_on_field;

			$has_search = TRUE;
		}
		//no search on field? guess we had better search it all *gulp*
		else if ($search_keywords AND trim($search_keywords) !== '')
		{
			$first = TRUE;

			$this->model('entry')->group_like(
				$search_keywords,
				array_values($visible_columns)
			);

			$pag_url['search_keywords'] = $search_keywords;

			$has_search = TRUE;
		}

		//status search?
		if ($moderate)
		{
			$this->model('entry')->where('status', 'pending');
		}
		else if ($search_status AND in_array($search_status, array_flip( $form_statuses)))
		{
			$this->model('entry')->where('status', $search_status);

			//pagination
			$pag_url['search_status'] = $search_status;

			$has_search = TRUE;
		}

		// -------------------------------------
		//	date range?
		// -------------------------------------

		//pagination
		if ($search_date_range == 'date_range')
		{


			if ($search_date_range_start !== FALSE)
			{
				$pag_url['search_date_range_start'] = $search_date_range_start;
			}

			if ($search_date_range_end !== FALSE)
			{
				$pag_url['search_date_range_end'] = $search_date_range_end;
			}

			//add timestamps so dates encompass from the beginning
			//to the end of said dates and not midnight am to midnight am
			$search_date_range_start .= ' 00:00';
			$search_date_range_end .= ' 23:59';

			//pagination
			if ($search_date_range_start OR $search_date_range_end)
			{
				$pag_url['search_date_range'] = 'date_range';
				$has_search = TRUE;
			}
		}
		else if ($search_date_range !== FALSE)
		{
			$pag_url['search_date_range'] = $search_date_range;
			$has_search = TRUE;
		}

		$this->model('entry')->date_where(
			$search_date_range,
			$search_date_range_start,
			$search_date_range_end
		);

		// -------------------------------------
		//	any searches?
		// -------------------------------------

		$this->cached_vars['has_search'] = $has_search;

		// -------------------------------------
		//	data from all sites?
		// -------------------------------------

		if ( ! $this->model('preference')->show_all_sites())
		{
			$this->model('entry')->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		//we need the counts for exports and end results
		$total_entries		= $this->model('entry')->count(array(), FALSE);

		// -------------------------------------
		//	orderby
		// -------------------------------------

		$order_by	= 'entry_date';

		$p_order_by = ee()->input->get_post('sort_col');

		if ($p_order_by !== FALSE AND in_array($p_order_by, $all_columns))
		{
			$order_by				= $p_order_by;
			$pag_url['order_by']	= $order_by;
		}

		// -------------------------------------
		//	sort
		// -------------------------------------

		$sort		= ($order_by == 'entry_date') ? 'desc' : 'asc';

		$p_sort		= ee()->input->get_post('sort_dir');

		if ($p_sort !== FALSE AND
			in_array(strtolower($p_sort), array('asc', 'desc')))
		{
			$sort					= strtolower($p_sort);
			$pag_url['sort_dir']	= $sort;
		}

		$this->model('entry')->order_by($order_by, $sort);

		// -------------------------------------
		//	export button
		// -------------------------------------

		if ($total_entries > 0)
		{
			$right_links[] = array(
				'title'	=> lang('export_entries'),
				'link'	=> '#export_entries'
			);
		}

		// -------------------------------------
		//	export url
		// -------------------------------------

		$export_url							= $pag_url;
		$export_url['moderate']				= $moderate ? 'true' : 'false';
		$export_url['method']				= 'export_entries';

		$this->cached_vars['export_url']	= $this->mcp_link($export_url);

		// -------------------------------------
		//	export?
		// -------------------------------------

		if ($export)
		{
			$export_fields = ee()->input->get_post('export_fields');
			$export_labels = $column_labels;

			// -------------------------------------
			//	build possible select alls
			// -------------------------------------

			$select = array();

			//are we sending just the selected fields?
			if ($export_fields != 'all')
			{
				$select = array_unique(array_merge($must_haves, $find_columns));

				foreach ($export_labels as $key => $value)
				{
					//clean export labels for json
					if ( ! in_array($key, $select))
					{
						unset($export_labels[$key]);
					}
				}

				//get real names
				foreach ($select as $key => $value)
				{
					if (isset($field_column_names[$value]))
					{
						$select[$key] = $field_column_names[$value];
					}
				}
			}
			//sending all fields means we need to still clean some labels
			else
			{
				foreach ($all_columns as $field_id_name => $field_name)
				{
					//clean export labels for json
					if ($field_id_name != $field_name)
					{
						unset($export_labels[$field_id_name]);
					}

					$select[] = $field_name;
				}
			}

			foreach ($export_labels as $key => $value)
			{
				//fix entities
				$value = html_entity_decode($value, ENT_COMPAT, 'UTF-8');

				$export_labels[$key] = $value;

				if (isset($field_column_names[$key]))
				{
					$export_labels[$field_column_names[$key]] = $value;
				}
			}

			$this->model('entry')->select(implode(', ', $select));

			// -------------------------------------
			//	check for chunking, etc
			// -------------------------------------

			$this->lib('Export')->format_dates = (ee()->input->get_post('format_dates') == 'y');

			$this->lib('Export')->export(array(
				'method'			=> ee()->input->get_post('export_method'),
				'form_id'			=> $form_id,
				'form_name'		=> $form_data['form_name'],
				'output'			=> 'download',
				'model'			=> $this->model('entry'),
				'remove_entry_id'	=> ($export_fields != 'all' AND ! in_array('entry_id', $visible_columns)),
				'header_labels'	=> $export_labels,
				'total_entries'	=> $total_entries
			));
		}
		//END if ($export)

		// -------------------------------------
		//	selects
		// -------------------------------------

		$needed_selects = array_unique(array_merge($must_haves, $find_columns));

		$this->model('entry')->select(implode(', ', $needed_selects));

		//--------------------------------------
		//  pagination start vars
		//--------------------------------------

		$pag_url			= $this->mcp_link($pag_url);
		$paginate			= '';
		$row_count			= 0;
		//moved above exports
		//$total_entries		= $this->model('entry')->count(array(), FALSE);
		$current_page		= 0;

		// -------------------------------------
		//	pagination?
		// -------------------------------------

		// do we need pagination?
		if ($total_entries > $this->row_limit)
		{
			$current_page				= $this->get_post_or_zero('page') ?: 1;

			$mcp_link_array = array(
				'method' => __FUNCTION__,
			    'form_id' => $form_id,
			);

			if (ee()->input->get_post('sort_col') !== false)
			{
				$mcp_link_array['sort_col'] = ee()->input->get_post('sort_col', true);
			}

			if (ee()->input->get_post('sort_dir') !== false)
			{
				$mcp_link_array['sort_dir'] = ee()->input->get_post('sort_dir', true);
			}

			$this->cached_vars['pagination'] = ee('CP/Pagination', $total_entries)
							->perPage($this->row_limit)
							->currentPage($current_page)
							->render($this->mcp_link($mcp_link_array, false));

			$this->model('entry')->limit(
				$this->row_limit,
				($current_page - 1) * $this->row_limit
			);
		}

		// -------------------------------------
		//	get data
		// -------------------------------------

		$result_array	= $this->model('entry')->get();

		$count			= $row_count;

		$entries		= array();

		if ( ! $result_array)
		{
			$result_array = array();
		}

		$entry_ids = array();

		foreach ($result_array as $row)
		{
			$entry_ids[] = $row['entry_id'];
		}

		// -------------------------------------
		//	allow pre_process
		// -------------------------------------

		$this->lib('Fields')->apply_field_method(array(
			'method'		=> 'pre_process_entries',
			'form_id'		=> $form_id,
			'entry_id'		=> $entry_ids,
			'form_data'		=> $form_data,
			'field_data'	=> $form_data['fields']
		));

		$tableData = array();

		$fields_by_name = array_flip($field_column_names);

		foreach ($result_array as $row)
		{
			//apply display_entry_cp to our field data
			$field_parse = $this->lib('Fields')->apply_field_method(array(
				'method'			=> 'display_entry_cp',
				'form_id'			=> $form_id,
				'entry_id'			=> $row['entry_id'],
				'form_data'			=> $form_data,
				'field_data'		=> $form_data['fields'],
				'field_input_data'	=> $row
			));

			$row = array_merge($row, $field_parse['variables']);

			// -------------------------------------
			//	remove entry_id and author_id if we
			//	arent showing them
			// -------------------------------------

			$data = array($row['entry_id']);

			if ( ! in_array('entry_id', $visible_columns))
			{
				$data = array();
			}

			// -------------------------------------
			//	dates
			// -------------------------------------

			//if (isset($row['entry_date'])) {
			//	$data['entry_date'] = $this->format_cp_date($row['entry_date']);
			//}
            //
			//if (isset($row['edit_date'])) {
			//	$data['edit_date'] = ($row['edit_date'] == 0) ? '' : $this->format_cp_date($row['edit_date']);
			//}

            $toolbar_items = array();
            if (isset($form_data["entry_url"]) && !empty($form_data["entry_url"])) {
                $entryUrl = $form_data["entry_url"];
                $entryUrl = str_replace("{form_id}", $form_data["form_id"], $entryUrl);
                $entryUrl = str_replace("{form_name}", $form_data["form_name"], $entryUrl);
                $entryUrl = str_replace("{entry_id}", $row['entry_id'], $entryUrl);

                $toolbar_items["view"] = array(
                    "href" => $entryUrl,
                    "title" => lang("entry_url"),
                );
            }

			$toolbar_items['edit'] = array(
                'href'	=> $this->mcp_link(array(
                    'method'		=> 'edit_entry',
                    'form_id'		=> $form_id,
                    'entry_id'		=> $row['entry_id']
                )),
                'title'	=> lang('edit_entry'),
            );

			//only show approve if status is pending
			if (isset($row['status']) && $row['status'] == 'pending')
			{
				$toolbar_items['approve']	= array(
					'href'	=> $this->mcp_link(array(
						'method'		=> 'approve_entries',
						'form_id'		=> $form_id,
						'entry_ids'		=> $row['entry_id']
					)),
					'title'	=> lang('approve'),
				);
			}

			// -------------------------------------
			//	fill in only the data from visible
			//	columns
			// -------------------------------------

			foreach ($visible_columns as $column_name)
			{
				//we do this already
				if ($column_name == 'entry_id')
				{
					continue;
				}

				$n = $all_columns[$column_name];

				if (isset($fields_by_name[$n]) && isset($row[$fields_by_name[$n]])) {
					$data[] = $row[$fields_by_name[$n]];
				} else {
                    if (in_array($column_name, array('edit_date', 'entry_date'))) {
                        $row[$n] = ($row[$n] == 0) ? '' : $this->format_cp_date($row[$n]);
                    }

					$data[] = $row[$n];
				}
			}

			$data += array(
				'toolbar_items' => array(
					'toolbar_items'	=> $toolbar_items
				),
				'actions' => array(
					'name'	=> 'entry_ids[]',
					'value'	=> $row['entry_id'],
					'data'	=> array(
						'confirm' => '#' . $row['entry_id'],
					)
				),
			);

			$tableData[] = $data;
		}


		// -------------------------------------
		//	ajax request?
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'tableData'			=> $tableData,
				'paginate'			=> $paginate,
				'visibleColumns'	=> $visible_columns,
				'allColumns'		=> $all_columns,
				'columnLabels'		=> $column_labels,
				'success'			=> TRUE
			));
			exit();
		}

		// -------------------------------------
		//	moderation count?
		// -------------------------------------

		//lets not waste the query if we are already moderating
		$moderation_count	= (
			( ! $moderate) ?
				$this->model('Data')->get_form_needs_moderation_count($form_id) :
				0
		);

		if ($moderation_count > 0)
		{
			$this->cached_vars['lang_num_items_awaiting_moderation'] = str_replace(
				array('%num%', '%form_label%'),
				array($moderation_count, $form_data['form_label']),
				lang('num_items_awaiting_moderation')
			);
		}

		$this->cached_vars['moderation_count']	= $moderation_count;
		$this->cached_vars['moderation_link']	= $this->mcp_link(array(
			'method'		=> 'moderate_entries',
			'form_id'		=> $form_id,
			'search_status'	=> 'pending'
		));

		// -------------------------------------
		//	is admin?
		// -------------------------------------

		$this->cached_vars['is_admin'] = $is_admin = (
			ee()->session->userdata('group_id') == 1
		);


		// -------------------------------------
		// member groups
		// -------------------------------------

		$member_groups = array();

		if ($is_admin)
		{
			ee()->db->select('group_id, group_title');

			$member_groups = $this->prepare_keyed_result(
				ee()->db->get('member_groups'),
				'group_id',
				'group_title'
			);
		}

		$this->cached_vars['member_groups']	= $member_groups;


		// -------------------------------------
		//	moderation lang
		// -------------------------------------

		$this->cached_vars['lang_viewing_moderation'] = str_replace(
			'%form_label%',
			$form_data['form_label'],
			lang('viewing_moderation')
		);

		// -------------------------------------
		//	other vars
		// -------------------------------------

		$this->cached_vars['entries_filter_uri']	= $this->mcp_link(array(
			'method'		=> __FUNCTION__,
		));

		$this->cached_vars['form_uri']				= $this->mcp_link(array(
			'method'		=> 'entries_action',
			'return_method' => (($moderate) ? 'moderate_' :	'' ) . 'entries',
			'form_id'		=> $form_id
		));

		$this->cached_vars['save_layout_url']		= $this->mcp_link(array(
			'method'		=> 'save_field_layout'
		));

		// -------------------------------------
		//	Build table
		// -------------------------------------

        $this->cached_vars['base_url'] = ee('CP/URL', 'addons/settings/freeform/entries&form_id=' . $form_id);
		$this->cached_vars['filters'] = $this->view('entries/entries_filters');

		$table = ee('CP/Table', array(
			'sortable'	=> true,
			'search'	=> true,
		));

		// -------------------------------------
		//	build custom columns
		// -------------------------------------

		$column_array = array('id' => array('type' => Table::COL_ID));

		if ( ! in_array('entry_id', $visible_columns))
		{
			$column_array = array();
		}

		foreach ($visible_columns as $column_name)
		{
			//we do this already
			if ($column_name == 'entry_id')
			{
				continue;
			}

			if ($column_name == 'status')
			{
				$column_array['status'] = array(
					'type'	=> Table::COL_STATUS
				);

				continue;
			}

			$column = array(
				'label' => $all_columns[$column_name],
				'encode' => false,
			);

			$column_array[] = $column;
		}

		$column_array += array(
			'action'		=> array(
				'type'	=> Table::COL_TOOLBAR,
				'sort'	=> false
			),
			'toggle'		=> array(
				'type'	=> Table::COL_CHECKBOX,
				'sort'	=> false,
				'name'  => 'selection',
			),
		);

		// -------------------------------------
		//	Add custom field names to EE->lang
		//	since they are defined in the db
		//	and not in a lang file, but we need
		//	them to work with Ee's custom mess.
		//
		//	(If we just send the end name to
		//	the columns it gets the name incorrect
		//	when it comes time to do the automatic
		//	sorting url.)
		// -------------------------------------

		foreach ($column_labels as $name => $label)
		{
			ee()->lang->language[$name] = $label;
		}

		// -------------------------------------
		//	set columns and table
		// -------------------------------------

		$table->setColumns($column_array);

		$table->setData($tableData);

		// -------------------------------------
		//	no results lang
		// -------------------------------------

		$no_results = (
			($has_search) ?
				'no_results_for_search' :
				(
					($moderate) ?
						'no_entries_awaiting_approval' :
						'no_entries_for_form'
				)
		);

		$table->setNoResultsText($no_results);

		$this->cached_vars['table'] = $table->viewData(
			$this->mcp_link(array(
				'method' => __FUNCTION__,
				'form_id' => $form_id
			), false)
		);




		$this->cached_vars['footer'] = array(
			'submit_lang' => lang('submit'),
			'type'        => 'bulk_action_form',
		);

		$modalProperties = array(
			'name'      => 'modal-confirm-remove',
			'form_url'  => ee('CP/URL', 'addons/settings/freeform/delete_entries&form_id=' . $form_id),
			'checklist' => array(
				array(
					'kind' => lang('submissions'),
				),
			),
		);

		ee()->javascript->set_global(
			'lang.remove_confirm',
			lang('remove') . ': <b>### ' . lang('remove_plural') . '</b>'
		);

		ee('CP/Modal')->addModal(
			'modal-confirm-remove',
			ee('View')->make('_shared/modal_confirm_remove')->render($modalProperties)
		);

		ee()->cp->add_js_script(array('file' => array('cp/confirm_remove')));










		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'		=> $this->mcp_link(array(
				'method'		=> 'entries',
				'form_id'		=> $form_id
			)),
			'cp_page_title'	=> lang('entries'),
			'table_head'	=> lang('entries')
		);

		// -------------------------------------
		//	export modal
		// -------------------------------------

		$modal = ee('View')
					->make('ee:_shared/modal')
					->render(array(
						'name'		=> 'export-entries',
						'contents'	=> $this->view('entries/export_dialog')
					));

		ee('CP/Modal')->addModal('export-entries', $modal);

		// -------------------------------------
		//	field_layout modal
		// -------------------------------------

		$field_layout_columns = array();

		foreach ($possible_columns as $pcid)
		{
			$check = ($this->is_positive_intlike($pcid)) ?
						$f_prefix . $pcid :
						$pcid;

			$field_layout_columns[$check] = $column_labels[$check];
		}

		$this->cached_vars['entries_layout_select'] = $this->setup_select_fields(
			$field_layout_columns,
			$visible_columns
		);

		$modal = ee('View')
					->make('ee:_shared/modal')
					->render(array(
						'name'		=> 'field-layout',
						'contents'	=> $this->view('entries/entries_layout')
					));

		ee('CP/Modal')->addModal('field-layout', $modal);

		//---------------------------------------------
		//  right link buttons
		//---------------------------------------------

		$this->cached_vars['form_right_links']	= array_merge(
			$right_links,
			array(
				array(
					'link' => '#edit_field_layout',
					'title' => lang('edit_field_layout'),
				),
				array(
					'link' => $this->mcp_link(array(
						'method'	=> 'edit_entry',
						'form_id'	=> $form_id
					)),
					'title' => lang('new_entry'),
				)
			)
		);

		// -------------------------------------
		//	js libs
		// -------------------------------------

		//this could mess with our own calendar
		//so not sure how this will work
		ee()->lang->loadfile('calendar');

		ee()->javascript->set_global(
			'date.date_format',
			ee()->config->item('date_format')
		);

		ee()->javascript->set_global('lang.date.months.full', array(
			lang('cal_january'),
			lang('cal_february'),
			lang('cal_march'),
			lang('cal_april'),
			lang('cal_may'),
			lang('cal_june'),
			lang('cal_july'),
			lang('cal_august'),
			lang('cal_september'),
			lang('cal_october'),
			lang('cal_november'),
			lang('cal_december')
		));

		ee()->javascript->set_global('lang.date.months.abbreviated', array(
			lang('cal_jan'),
			lang('cal_feb'),
			lang('cal_mar'),
			lang('cal_apr'),
			lang('cal_may'),
			lang('cal_june'),
			lang('cal_july'),
			lang('cal_aug'),
			lang('cal_sep'),
			lang('cal_oct'),
			lang('cal_nov'),
			lang('cal_dec')
		));

		ee()->javascript->set_global('lang.date.days', array(
			lang('cal_su'),
			lang('cal_mo'),
			lang('cal_tu'),
			lang('cal_we'),
			lang('cal_th'),
			lang('cal_fr'),
			lang('cal_sa'),
		));

		ee()->cp->add_js_script(array(
			'file' => array('cp/date_picker', 'underscore')
		));

		$this->load_fancybox();

		return $this->mcp_view(array(
			'file'		=> 'list',
			'highlight'	=> 'forms',
			'show_message'	=> false,
			'pkg_js'	=> array('standard_cp.min', 'entries_cp.min', 'custom_filters.min'),
			'pkg_css'	=> array('mcp_defaults', 'entry_filter_cp', 'custom_filters'),
			'crumbs'	=> array(
				array(
					lang('forms'),
					$this->mcp_link(array('method' => 'forms'))
				),
				array(
					$form_data['form_label'] . ': ' .
					lang($moderate ? 'moderate' : 'entries')
				)
			)
		));
	}
	//END entries


	// --------------------------------------------------------------------

	/**
	 * Fire a field method as a page view
	 *
	 * @access	public
	 * @param	string $message		lang line to load for a message
	 * @return	string				html output
	 */
	public function field_method($message = '')
	{
		$this->prep_message($message);

		$form_id		= $this->get_post_or_zero('form_id');
		$field_id		= $this->get_post_or_zero('field_id');
		$field_method	= ee()->input->get_post('field_method');
		$instance		= FALSE;

		if ( $field_method == FALSE OR
			! $this->model('form')->valid_id($form_id) OR
			$field_id == 0)
		{
			ee()->functions->redirect($this->mcp_link(array('method' => 'forms')));
		}

		$fieldsInstance = $this->lib('Fields')->get_field_instance(
			array(
				'form_id'  => $form_id,
				'field_id' => $field_id
			)
		);
		$instance       =& $fieldsInstance;

		//legit?
		if ( ! is_object($instance) OR
			 empty($instance->entry_views) OR
			 //removed so you can post to this
			 //! in_array($field_method, $instance->entry_views) OR
			 ! is_callable(array($instance, $field_method)))
		{
			ee()->functions->redirect($this->mcp_link(array('method' => 'forms')));
		}

		$method_lang = lang('field_entry_view');

		foreach ($instance->entry_views as $e_lang => $e_method)
		{
			if ($field_method == $e_method)
			{
				$method_lang = $e_lang;
			}
		}

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['field_view_output'] = $instance->$field_method();

		// -------------------------------------
		//	loading these after the instance
		//	incase the instance exits
		// -------------------------------------

		$this->load_fancybox();

		return $this->mcp_view(array(
			'file'			=> 'field_view_wrapper',
			'highlight'		=> 'forms',
			'show_message'	=> false,
			'pkg_js'		=> array(),
			'pkg_css'		=> array('mcp_defaults'),
			'crumbs'		=> array(
				array(
					lang('forms'),
					$this->mcp_link(array(
						'method'	=> 'forms'
					))
				),
				array(
					lang('entries'),
					$this->mcp_link(array(
						'method'	=> 'entries',
						'form_id'	=> $form_id
					))
				),
				array($method_lang)
			)
		));
	}
	//END field_method


	// --------------------------------------------------------------------

	/**
	 * migrate collections
	 *
	 * @access	public
	 * @return	string
	 */

	public function migrate_collections($message = '')
	{
		$this->prep_message($message);

		//--------------------------------------
		//  Variables
		//--------------------------------------

		$migrate_empty_fields	= 'n';
		$migrate_attachments	= 'n';

		$this->cached_vars['total']			= 0;
		$this->cached_vars['collections']	= '';

		$collections = ee()->input->post('collections');

		if ( $collections !== FALSE  )
		{
			$this->cached_vars['total']			= $this->lib('Migration')
														->get_migration_count(
															$collections
														);

			$this->cached_vars['collections']	= implode('|', $collections);

			if ($this->check_yes( ee()->input->post('migrate_empty_fields') ) )
			{
				$migrate_empty_fields	= 'y';
			}

			if ($this->check_yes( ee()->input->post('migrate_attachments') ) )
			{
				$migrate_attachments	= 'y';
			}
		}

		//--------------------------------------
		//  Migration ajax url
		//--------------------------------------

		$this->cached_vars['ajax_url']		= $this->base .
			'&method=migrate_collections_ajax' .
			'&migrate_empty_fields=' . $migrate_empty_fields .
			'&migrate_attachments=' . $migrate_attachments .
			'&collections=' .
			urlencode( $this->cached_vars['collections'] ) .
			'&total=' .
			$this->cached_vars['total'] .
			'&batch=0';

		//--------------------------------------
		//  images
		//--------------------------------------

		//Success image
		$this->cached_vars['success_png_url']	= $this->theme_url . 'images/success.png';

		//  Error image
		$this->cached_vars['error_png_url']		= $this->theme_url . 'images/exclamation.png';

		//--------------------------------------
		//  Javascript
		//--------------------------------------

		$this->cached_vars['cp_javascript'][] = 'migrate';

		ee()->cp->add_js_script(
			array('ui' => array('core', 'progressbar'))
		);

		//--------------------------------------
		//  Load page
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'migrate.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//	End migrate collections


	// --------------------------------------------------------------------

	/**
	 * migrate collections ajax
	 *
	 * @access	public
	 * @return	string
	 */

	public function migrate_collections_ajax()
	{
		$upper_limit	= 9999;

		//--------------------------------------
		//  Base output
		//--------------------------------------

		$out	= array(
			'done'	=> FALSE,
			'batch'	=> ee()->input->get('batch'),
			'total'	=> ee()->input->get('total')
		);

		//--------------------------------------
		//  Validate
		//--------------------------------------
		$collections = ee()->input->get('collections');

		if ( empty( $collections ) OR
			 ee()->input->get('batch') === FALSE )
		{
			$out['error']	= TRUE;
			$out['errors']	= array( 'no_collections' => lang('no_collections') );
			$this->send_ajax_response($out);
			exit();
		}

		//--------------------------------------
		//  Done?
		//--------------------------------------

		if ( ee()->input->get('batch') !== FALSE AND
			 ee()->input->get('total') !== FALSE AND
			 ee()->input->get('batch') >= ee()->input->get('total') )
		{
			$out['done']	= TRUE;
			$this->send_ajax_response($out);
			exit();
		}

		//--------------------------------------
		//  Anything?
		//--------------------------------------

		$collections	= $this->lib('Utils')->pipe_split(
			urldecode(
				ee()->input->get('collections')
			)
		);

		$counts			= $this->lib('Migration')->get_collection_counts($collections);

		if (empty($counts))
		{
			$out['error']	= TRUE;
			$out['errors']	= array( 'no_collections' => lang('no_collections') );
			$this->send_ajax_response($out);
			exit();
		}

		//--------------------------------------
		//  Do any of the submitted collections have unmigrated entries?
		//--------------------------------------

		$migrate	= FALSE;

		foreach ( $counts as $form_name => $val )
		{
			if ( ! empty( $val['unmigrated'] ) )
			{
				$migrate = TRUE;
			}
		}

		if ( empty( $migrate ) )
		{
			$out['done']	= TRUE;
			$this->send_ajax_response($out);
			exit();
		}

		//--------------------------------------
		//  Master arrays
		//--------------------------------------

		$forms			= array();
		$form_fields	= array();

		//--------------------------------------
		//  Loop and process
		//--------------------------------------

		foreach ( $counts as $form_name => $val )
		{
			//--------------------------------------
			//  For each collection, create a form
			//--------------------------------------

			$form_data = $this->lib('Migration')->create_form($form_name);

			if ( $form_data !== FALSE )
			{
				$forms[ $form_data['form_name'] ]['form_id']	= $form_data['form_id'];
				$forms[ $form_data['form_name'] ]['form_label']	= $form_data['form_label'];
			}
			else
			{
				$errors = $this->lib('Migration')->get_errors();

				if ( $errors !== FALSE )
				{
					$out['error']	= TRUE;
					$out['errors']	= $errors;
					$this->send_ajax_response($out);
					exit();
				}
			}

			//--------------------------------------
			//  For each collection, determine fields
			//--------------------------------------

			$migrate_empty_fields	= (
				$this->check_yes(ee()->input->get('migrate_empty_fields'))
			) ? 'y': 'n';

			$fields = $this->lib('Migration')->get_fields_for_collection(
				$form_name,
				$migrate_empty_fields
			);

			if ($fields !== FALSE)
			{
				$form_fields[ $form_name ]['fields']	= $fields;
			}
			else
			{
				$errors = $this->lib('Migration')->get_errors();

				if ($errors !== FALSE)
				{
					$out['error']	= TRUE;
					$out['errors']	= $errors;
					$this->send_ajax_response($out);
					exit();
				}
			}

			//--------------------------------------
			//  For each collection, create necessary fields if they don't yet exist.
			//--------------------------------------

			$created_field_ids	= array();

			if ( ! empty( $form_fields[$form_name]['fields'] ) )
			{
				foreach ( $form_fields[$form_name]['fields'] as $name => $attr )
				{
					$field_id = $this->lib('Migration')->create_field(
						$forms[$form_name]['form_id'],
						$name,
						$attr
					);

					if ($field_id !== FALSE )
					{
						$created_field_ids[]	= $field_id;
					}
					else
					{
						$errors = $this->lib('Migration')->get_errors();

						if ( $errors !== FALSE )
						{
							$out['error']	= TRUE;
							$out['errors']	= $errors;
							$this->send_ajax_response($out);
							exit();
						}
					}
				}
			}

			//--------------------------------------
			//  For each collection, create upload fields if needed.
			//--------------------------------------

			$attachment_profiles = $this->lib('Migration')->get_attachment_profiles( $form_name );

			if ($this->check_yes( ee()->input->get('migrate_attachments') ) AND
				$attachment_profiles !== FALSE )
			{
				foreach ( $attachment_profiles as $row )
				{
					$field_id = $this->lib('Migration')->create_upload_field(
						$forms[ $form_name ]['form_id'],
						$row['name'],
						$row
					);

					if ($field_id !== FALSE)
					{
						$created_field_ids[]					= $field_id;
						$upload_pref_id_map[ $row['pref_id'] ]	= array(
							'field_id'		=> $field_id,
							'field_name'	=> $row['name']
						);
					}
					else
					{
						$errors = $this->lib('Migration')->get_errors();

						if ( $errors !== FALSE )
						{
							$out['error']	= TRUE;
							$out['errors']	= $errors;
							$this->send_ajax_response($out);
							exit();
						}
					}
				}

				if ( ! empty( $upload_pref_id_map ) )
				{
					$this->lib('Migration')->set_property(
						'upload_pref_id_map',
						$upload_pref_id_map
					);
				}
			}

			//--------------------------------------
			//  Assign the fields to our form.
			//--------------------------------------

			$this->lib('Migration')->assign_fields_to_form(
				$forms[ $form_data['form_name'] ]['form_id'],
				$created_field_ids
			);

			//--------------------------------------
			//	Safeguard?
			//--------------------------------------

			if ( ee()->input->get('batch') == $upper_limit )
			{
				$this->send_ajax_response(array('done' => TRUE ));
				exit();
			}

			//--------------------------------------
			//  Pass attachments pref
			//--------------------------------------

			if ($this->check_yes( ee()->input->get('migrate_attachments') ) )
			{
				$this->lib('Migration')->set_property('migrate_attachments', TRUE);
			}

			//--------------------------------------
			//  Grab entries
			//--------------------------------------

			$this->lib('Migration')->set_property(
				'batch_limit',
				$this->migration_batch_limit
			);

			$entries = $this->lib('Migration')->get_legacy_entries($form_name);

			if ( $entries !== FALSE )
			{
				foreach ( $entries as $entry )
				{
					//--------------------------------------
					//  Insert
					//--------------------------------------

					$entry_id = $this->lib('Migration')->set_legacy_entry(
						$forms[ $form_name ]['form_id'],
						$entry
					);

					if ( $entry_id === FALSE )
					{
						$errors = $this->lib('Migration')->get_errors();

						if ( $errors !== FALSE )
						{
							$out['error']	= TRUE;
							$out['errors']	= $errors;
							$this->send_ajax_response($out);
							exit();
						}
					}
					else
					{
						$out['batch']	= $out['batch'] + 1;
					}
				}
			}
		}

		//--------------------------------------
		//  Are we done?
		//--------------------------------------

		$this->send_ajax_response($out);
		exit();
	}
	//	End migrate collections ajax


	// --------------------------------------------------------------------

	/**
	 * moderate entries
	 *
	 * almost the same as entries but with some small modifications
	 *
	 * @access	public
	 * @param	string message lang line
	 * @return	string html output
	 */

	public function moderate_entries($message = '')
	{
		return $this->entries($message, TRUE);
	}
	//END moderate_entries


	// --------------------------------------------------------------------

	/**
	 * Action from submitted entries links
	 *
	 * @access public
	 * @return string	html output
	 */

	public function entries_action()
	{
		$action = ee()->input->get_post('submit_action');

		if ($action == 'approve')
		{
			return $this->approve_entries();
		}

		else if ($action == 'delete')
		{
			return $this->delete_confirm_entries();
		}

		else
		{
			show_error('No correct action recognized for: ' . __FUNCTION__);
		}
	}
	//END entries_action


	// --------------------------------------------------------------------

	/**
	 * Edit Entry
	 *
	 * @access	public
	 * @return	string parsed html
	 */

	public function edit_entry($message = '')
	{
		$this->prep_message($message);

		// -------------------------------------
		//	edit?
		// -------------------------------------

		$form_id	= $this->get_post_or_zero('form_id');
		$entry_id	= $this->get_post_or_zero('entry_id');

		$form_data = $this->model('form')->get_info($form_id);

		if ( ! $form_data)
		{
			return $this->lib('Utils')->full_stop(lang('invalid_form_id'));
		}

		$entry_data	= array();
		$edit		= FALSE;

		if ($entry_id > 0)
		{
			$entry_data = $this->model('entry')
							  ->id($form_id)
							  ->where('entry_id', $entry_id)
							  ->get_row();

			if ($entry_data == FALSE)
			{
				return $this->lib('Utils')->full_stop(lang('invalid_entry_id'));
			}

			$edit = TRUE;
		}

		// -------------------------------------
		//	build form sections
		// -------------------------------------

		$sections = array();

		$main_section = array();

		// -------------------------------------
		//	load the template library in case
		//	people get upset or something
		// -------------------------------------

		if ( ! isset(ee()->TMPL) OR ! is_object(ee()->TMPL))
		{
			ee()->load->library('template', null, 'TMPL');
		}

		// -------------------------------------
		//	get fields
		// -------------------------------------

		$field_output_data = array();

		$field_loop_ids = array_keys($form_data['fields']);

		if ($form_data['field_order'] !== '')
		{
			$order_ids = $this->lib('Utils')->pipe_split($form_data['field_order']);

			if ( ! empty($order_ids))
			{
				//this makes sure that any fields in 'fields' are in the
				//order set as well. Will add missing at the end like this
				$field_loop_ids = array_merge(
					$order_ids,
					array_diff($field_loop_ids, $order_ids)
				);
			}
		}

		// -------------------------------------
		//	build entry data
		// -------------------------------------

		$entry_data['screen_name'] = '';
		$entry_data['group_title'] = '';

		if ( !empty($entry_data['author_id']))
		{
			$member_data =  ee()->db
								->select('group_title, screen_name')
								->from('members')
								->join('member_groups', 'members.group_id = member_groups.group_id', 'left')
								->where('member_id', $entry_data['author_id'])
								->limit(1)
								->get();

			if ($member_data->num_rows() > 0)
			{
				$entry_data['screen_name'] = $member_data->row('screen_name');
				$entry_data['group_title'] = $member_data->row('group_title');
			}
		}

		if ($entry_data['screen_name'] == '')
		{
			$entry_data['screen_name'] = lang('guest');
			$entry_data['group_title'] = lang('guest');

			$guest_data =	ee()->db
								->select('group_title')
								->from('member_groups')
								->where('group_id', 3)
								->limit(1)
								->get();

			if ($guest_data->num_rows() > 0)
			{
				$entry_data['group_title'] = $guest_data->row('group_title');
			}
		}

		$entry_data['entry_date']	= ( ! empty( $entry_data['entry_date'] ) ) ?
										$entry_data['entry_date'] :
										ee()->localize->now;
		$entry_data['entry_date']	= $this->format_cp_date($entry_data['entry_date']);
		$entry_data['edit_date']	= ( !empty($entry_data['edit_date'])) ?
										$this->format_cp_date($entry_data['edit_date']) :
										lang('n_a');

		$this->cached_vars['form_uri']			= $this->mcp_link(array(
			'method'	=> 'save_entry'
		));
		$statuses			= $this->model('preference')->get_form_statuses();

		// -------------------------------------
		//	build data
		// -------------------------------------

		//hidden
		$main_section[] = array(
			'title'		=> '',
			'desc'		=> '',
			'fields'	=> array(
				'form_id' => array(
					'type'		=> 'hidden',
					'value'		=> $form_id
				),
				'entry_id' => array(
					'type'		=> 'hidden',
					'value'		=> $entry_id
				)
			)
		);

		if ($edit)
		{
			$statics = array(
				'entry_id',
				'group_title',
				'screen_name',
				'ip_address',
				'entry_date',
				'edit_date'
			);

			foreach ($statics as $static)
			{
				$main_section[$static] = array(
					'title'		=> lang($static),
					'desc'		=> '',
					'fields'	=> array(
						$static => array(
							'type'			=> 'html',
							'content'		=> isset($entry_data[$static]) ?
												$entry_data[$static] :
												lang('n_a'),
							'required'		=> false
						)
					)
				);
			}
		}

		// -------------------------------------
		//	status
		// -------------------------------------

		$main_section['status'] = array(
			'title'		=> lang('status'),
			'desc'		=> '',
			'fields'	=> array(
				'status' => array(
					'type'		=> 'select',
					'choices'	=> $statuses,
					'value'		=> isset($entry_data['status']) ?
									$entry_data['status'] :
									$form_data['default_status']
				)
			)
		);

		// -------------------------------------
		//	field output
		// -------------------------------------

        $hasFileUpload = false;
		foreach ($field_loop_ids as $field_id)
		{
			//just in case a rogue field got into the mix
			if ( ! isset($form_data['fields'][$field_id]))
			{
				continue;
			}

			$f_data = $form_data['fields'][$field_id];

			$fieldsInstance = $this->lib('Fields')->get_field_instance(
				array(
					'field_id'       => $field_id,
					'field_data'     => $f_data,
					'form_id'        => $form_id,
					'entry_id'       => $entry_id,
					'edit'           => $edit,
					'extra_settings' => array(
						'entry_id' => $entry_id
					)
				)
			);
			$instance       =& $fieldsInstance;

            if ($instance instanceof File_upload_freeform_ft) {
                $hasFileUpload = true;
            }

			$column_name = $this->model('entry')->form_field_prefix . $field_id;

			$display_field_data = '';

			if ($edit)
			{
				if (isset($entry_data[$column_name]))
				{
					$display_field_data = $entry_data[$column_name];
				}
				else if (isset($entry_data[$f_data['field_name']]))
				{
					$display_field_data = $entry_data[$f_data['field_name']];
				}
			}

			$field_output_data[$field_id] = array(
				'field_display'		=> $instance->display_edit_cp($display_field_data),
				'field_label'		=> $f_data['field_label'],
				'field_description' => $f_data['field_description']
			);


			$main_section[$column_name] = array(
				'title'		=> $f_data['field_label'],
				'desc'		=> $f_data['field_description'],
				'fields'	=> array(
					$column_name => array(
						'type'			=> 'html',
						'content'		=> $instance->display_edit_cp($display_field_data),
						'required'		=> false
					)
				)
			);
		}

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$sections[] = $main_section;

		//	----------------------------------------
		//	Load vars
		//	----------------------------------------

		$this->cached_vars['sections'] = $sections;

		$this->cached_vars['form_url'] = $this->mcp_link(array(
			'method' => 'save_form'
		));

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		$title = lang($edit ? 'edit_entry' : 'new_entry')  . ': ' .
					$form_data['form_label'];

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'				=> $this->mcp_link(array(
				'method' => 'save_entry'
			)),
			'has_file_input'        => $hasFileUpload,
			'cp_page_title'			=> $title,
			'save_btn_text'			=> 'submit',
			'save_btn_text_working'	=> 'btn_saving'
		);

		// -------------------------------------
		//	view
		// -------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'form',
			'highlight'	=> 'forms',
			'show_message' => false,
			'pkg_js'	=> array('standard_cp.min'),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(
					lang('forms'),
					$this->mcp_link(array('method' => 'forms'), false)
				),
				array($title)
			)
		));
	}
	//END edit_entry


	// --------------------------------------------------------------------

	/**
	 * fields
	 *
	 * @access	public
	 * @param	string message
	 * @return	string
	 */

	public function fields($message = '')
	{
		$this->prep_message($message);

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		$this->cached_vars['form_right_links']	= array(
			array(
				'link' => $this->mcp_link(array('method' => 'edit_field')),
				'title' => lang('new_field'),
			)
		);

		//--------------------------------------
		//  start vars
		//--------------------------------------

		$paginate			= '';
		$row_count			= 0;

		// -------------------------------------
		//	pagination?
		// -------------------------------------


		if ( ! $this->model('preference')->show_all_sites())
		{
			$this->model('field')->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		$total_results = $this->model('field')->count(array(), FALSE);

		//	----------------------------------------
		//	Pagination
		//	----------------------------------------

		$page	= 0;

		if ($total_results > $this->row_limit)
		{
			$page	= $this->get_post_or_zero('page') ?: 1;

			$mcp_link_array = array(
				'method' => __FUNCTION__
			);

			$this->cached_vars['pagination'] = ee('CP/Pagination', $total_results)
								->perPage($this->row_limit)
								->currentPage($page)
								->render($this->mcp_link($mcp_link_array, false));

			$this->model('field')->limit(
				$this->row_limit,
				($page - 1) * $this->row_limit
			);
		}

		$this->model('field')->order_by('field_label');

		$fieldtypes	= $this->lib('Fields')->get_installable_fieldtypes();

		// -------------------------------------
		//	data
		// -------------------------------------

		$tableData	= array();

		$rows = $this->model('field')->get();

		if ($rows !== FALSE)
		{
			// -------------------------------------
			//	rows
			// -------------------------------------

			foreach ($rows as $row)
			{
				// -------------------------------------
				//	piles o' links
				// -------------------------------------

				$row['field_settings_link']		= $this->mcp_link(array(
					'method'		=> 'edit_field',
					'field_id'		=> $row['field_id']
				));

				$row['field_duplicate_link']		= $this->mcp_link(array(
					'method'		=> 'edit_field',
					'duplicate_id'	=> $row['field_id']
				));

				$tableData[] = array(
					$row['field_id'],
					'label'	=> array(
						'type'		=> 'html',
						'content'	=> $this->view(
							'fields/_row',
							array(
								'col_type' => 'label',
								'row' => $row
							)
						)
					),
					'type'	=> array(
						'type'		=> 'html',
						'content'	=> $this->view(
							'fields/_row',
							array(
								'col_type' => 'type',
								'row' => $row,
								'fieldtype_name' => $fieldtypes[$row['field_type']]['name']
							)
						)
					),
					'description'	=> array(
						'type'		=> 'html',
						'content'	=> $this->view(
							'fields/_row',
							array(
								'col_type' => 'description',
								'row' => $row
							)
						)
					),
					'manage'	=> array(
						'type'		=> 'html',
						'content'	=> $this->view(
							'fields/_row',
							array(
								'col_type' => 'manage',
								'row' => $row
							)
						)
					),
					array(
						'name'		=> 'selections[]',
						'value'	=> $row['field_id'],
						'data'		=> array(
							'confirm' => lang('field') . ': <b>' . htmlentities($row['field_label'], ENT_QUOTES) . '</b>'
						)
					)
				);
			}
		}

		// -------------------------------------
		//	Build table
		// -------------------------------------

		$table = ee('CP/Table', array(
			'sortable'	=> false,
			'search'	=> false,
		));

		$table->setColumns(
			array(
				'id' => array(
					'type'	=> Table::COL_ID
				),
				'field'	=> array(
					'type'	=> 'html'
				),
				'type'	=> array(
					'type'	=> 'html'
				),
				'description'	=> array(
					'type'	=> 'html'
				),
				'manage'	=> array(
					'type'	=> 'html'
				),
				array(
					'type'	=> Table::COL_CHECKBOX,
					'name'	=> 'selection'
				)
			)
		);

		$table->setData($tableData);

		$table->setNoResultsText('no_fields');

		$this->cached_vars['table'] = $table->viewData(
			$this->mcp_link(array('method' => __FUNCTION__), false)
		);

		// -------------------------------------
		//	Modal for delete confirmation
		// -------------------------------------

		$this->cached_vars['footer'] = array(
			'type'			=> 'bulk_action_form',
			'submit_lang'	=> lang('fields_remove')
		);

		$this->mcp_modal_confirm(array(
			'form_url'	=> $this->mcp_link(array('method' => 'fields_delete')),
			'name'		=> 'fields',
			'kind'		=> lang('fields'),
		));

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'		=> $this->mcp_link(array(
				'method' => 'edit_field'
			)),
			'cp_page_title'	=> lang('fields'),
			'table_head'	=> lang('fields')
		);

		return $this->mcp_view(array(
			'file'		=> 'list',
			'highlight'	=> 'fields',
			'show_message' => false,
			'pkg_js'	=> array('tooltip', 'dataTables'),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('fields'))
			)
		));
	}
	//END fields


	// --------------------------------------------------------------------

	/**
	 * Edit Field
	 *
	 * @access	public
	 * @param   bool 	$modal	is this a modal version?
	 * @return	string
	 */

	public function edit_field($modal = FALSE)
	{
		// -------------------------------------
		//	field ID? we must be editing
		// -------------------------------------

		$field_id		= $this->get_post_or_zero('field_id');

		$update			= ($field_id != 0);

		$modal			= (
			! $modal AND $this->check_yes(ee()->input->get_post('modal'))
		) ? TRUE : $modal;

		$this->cached_vars['modal'] = $modal;

		//--------------------------------------------
		//	Crumbs and tab highlight
		//--------------------------------------------

		$this->add_crumb(lang('fields'), $this->base . '/fields');
		$this->add_crumb(lang(($update ? 'update_field' : 'new_field')) );

		// -------------------------------------
		//	default values
		// -------------------------------------

		$input_defaults = $this->model('field')->input_defaults();

		$inputs = array();

		foreach ($input_defaults as $key => $data)
		{
			$inputs[$key] = $data['default'];
		}

		// -------------------------------------
		//	defaults
		// -------------------------------------

		$edit_warning = FALSE;

		$field_in_forms = array();
		$incoming_settings = FALSE;


		if ($update)
		{
			$field_data = $this->model('field')->get_row($field_id);

			if (empty($field_data))
			{
				$this->lib('Utils')->full_stop(lang('invalid_field_id'));
			}

			foreach ($field_data as $key => $value)
			{
				$inputs[$key] = $value;
			}

			// -------------------------------------
			//	is this change going to affect any
			//	forms that use this field?
			// -------------------------------------

			$form_info = $this->model('form')->forms_with_field_id($field_id);

			if ($form_info AND ! empty($form_info))
			{
				$edit_warning = TRUE;

				$form_names = array();

				foreach ($form_info as $row)
				{
					$field_in_forms[]	= $row['form_id'];
					$form_names[]		= $row['form_label'];
				}

				$this->cached_vars['lang_field_edit_warning'] = lang('field_edit_warning');

				$this->cached_vars['lang_field_edit_warning_desc'] = str_replace(
					'%form_names%',
					implode(', ', $form_names),
					lang('field_edit_warning_desc')
				);
			}
		}

		$inputs['form_ids'] = $field_in_forms;

		// -------------------------------------
		//	duplicating?
		// -------------------------------------

		$duplicate_id	= $this->get_post_or_zero('duplicate_id');
		$duplicate		= FALSE;

		$duplicated = FALSE;

		if ( ! $update AND $duplicate_id > 0)
		{
			$field_data = $this->model('field')->get_row($duplicate_id);

			if ($field_data)
			{
				$duplicate = TRUE;

				foreach ($field_data as $key => $value)
				{
					if (in_array($key, array('field_id', 'field_label', 'field_name')))
					{
						continue;
					}

					$inputs[$key] = $value;
				}

				$duplicated		= TRUE;
				$this->cached_vars['duplicated_from']	= $field_data['field_label'];
			}
		}

		// -------------------------------------
		//	get available field types
		// -------------------------------------

		$fieldtypes = $this->lib('Fields')->get_available_fieldtypes();

		// -------------------------------------
		//	get available forms to add this to
		// -------------------------------------

		if ( ! $this->model('preference')->show_all_sites())
		{
			ee()->db->where('site_id', ee()->config->item('site_id'));
		}

		$this->cached_vars['available_forms'] = $this->model('form')->key('form_id')->get();

		// -------------------------------------
		//	add desc and lang for field types
		// -------------------------------------


		$field_options = array();

		foreach($fieldtypes as $fieldtype => $field_data)
		{
			//settings?
			$settings = (
				($update OR $duplicate ) AND
				$inputs['field_type'] == $fieldtype AND
				isset($inputs['settings'])
			) ? json_decode($inputs['settings'], TRUE) : array();

			//get array of fields (for EE )
			$opts = $fieldtypes[$fieldtype]['settings_output'] =
				$this->lib('Fields')->fieldtype_display_settings_output(
					$fieldtype,
					$settings
				);

			if (is_array($opts))
			{
				$field_options = array_merge($field_options, $opts);
			}
		}

		if (isset($inputs['field_label']))
		{
			$inputs['field_label'] = form_prep($inputs['field_label']);
		}

		if (isset($inputs['field_description']))
		{
			$inputs['field_description'] = form_prep($inputs['field_description']);
		}

		// -------------------------------------
		//	load inputs
		// -------------------------------------

		foreach ($inputs as $key => $value)
		{
			$this->cached_vars[$key] = $value;
		}

		$this->cached_vars['form_uri'] = $this->mcp_link(array(
			'method'		=> 'save_field'
		));

		//----------------------------------------
		//	Load vars
		//----------------------------------------

		$this->cached_vars['prohibited_field_names']	= json_encode($this->model('Data')->prohibited_names);

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->load_fancybox();

		// -------------------------------------
		//	build form sections
		// -------------------------------------

		$sections = array();

		$main_section = array();

		// -------------------------------------
		//	Hidden fields
		// -------------------------------------

		$hidden = array();

		foreach ($input_defaults as $short_name => $data)
		{
			if ($data['type'] == 'hidden')
			{
				$hidden[$short_name] = isset($inputs[$short_name]) ?
									$inputs[$short_name] :
									$data['default'];
			}
		}

		if ( ! empty($hidden))
		{
			$this->cached_vars['form_hidden'] = $hidden;
		}

		// -------------------------------------
		//	Shown fields
		// -------------------------------------

		foreach ($input_defaults as $short_name => $data)
		{
			if ($data['type'] == 'hidden')
			{
				continue;
			}

			$main_section[$short_name] = array(
				'title'		=> lang($data['label']),
				'desc'		=> lang($data['desc'] ?: ''),
				'fields'	=> array(
					$short_name => array_merge($data, array(
						'value'		=> isset($inputs[$short_name]) ?
										$inputs[$short_name] :
										$data['default'],
						'required'	=> isset($data['required']) ?
											$data['required'] :
											false
					))
				)
			);
		}
		//END foreach ($input_defaults as $short_name => $data)

		$sections[] = $main_section;

		if ( ! empty($field_options))
		{
			$sections += $field_options;
		}

		$this->cached_vars['sections'] = $sections;

		$this->cached_vars['form_url'] = $this->mcp_link(array(
			'method' => 'save_field'
		));

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'				=> $this->mcp_link(array(
				'method' => 'save_field'
			)),
			'cp_page_title'			=> lang(( $update ? 'update_field' : 'new_field')),
			'save_btn_text'			=> ($update ? 'update_field' : 'new_field'),
			'save_btn_text_working'	=> 'btn_saving',
			'form_class'			=> 'edit_field'
		);

		if ($modal)
		{
			exit($this->view('edit_field'));
		}

		return $this->mcp_view(array(
			'file'		=> 'edit_field',
			'highlight'	=> 'fields',
			'show_message' => false,
			'pkg_css'	=> array('mcp_defaults', 'standard_cp'),
			'pkg_js'	=> array('standard_cp.min', 'edit_field_cp.min'),
			'crumbs'	=> array(
				array(lang(($update ? 'update_field' : 'new_field')))
			)
		));
	}
	//END edit_field


	// --------------------------------------------------------------------

	/**
	 * Field Types
	 *
	 * @access	public
	 * @param	message
	 * @return	string
	 */

	public function fieldtypes($message = '')
	{
		$this->prep_message($message);

		//--------------------------------------
		//  start vars
		//--------------------------------------

		$installed_fieldtypes	= $this->model('fieldtype')->installed_fieldtypes() ?: array();
		$fieldtypes			= $this->lib('Fields')->get_installable_fieldtypes() ?: array();

		// -------------------------------------
		//	missing fieldtype folders?
		// -------------------------------------

		$missing_fieldtypes		= array();

		foreach ($installed_fieldtypes as $name => $version)
		{
			if ( ! array_key_exists($name, $fieldtypes))
			{
				$missing_fieldtypes[] = $name;
			}
		}

		// -------------------------------------
		//	Table Rows
		// -------------------------------------

		$tableData	= array();

		// -------------------------------------
		//	add urls and crap
		// -------------------------------------

		$action_url = $this->mcp_link(array(
			'method' => 'freeform_fieldtype_action'
		));

		foreach ($fieldtypes as $name => $data)
		{
			$fieldtypes[$name]['installed_lang'] = lang(
				$data['installed'] ? 'installed' : 'not_installed'
			);

			$action = ($data['installed'] ? 'uninstall' : 'install');

			$action_url = array(
				'toolbar_items'	=> array(
					$action	=> array(
						'href'	=> $this->mcp_link(array(
							'method'		=> 'freeform_fieldtype_action',
							'name'			=> $name,
							'action'		=> $action,
						)),
						'title'	=> lang($action),
					)
				)
			);

			//cannot uninstall text
			if ($name == 'text')
			{
				$action_url = array(
					'toolbar_items'	=> array(
						'hide-toolbar-item'	=> array(
							'href'	=> '#'
						)
					)
				);
			}

			$install_stat = $data['installed'] ? 'installed' : 'not_installed';

			$tableData[] = array(
				'freeform_fieldtype_name'	=> $data['name'],
				'description'				=> $data['description'],
				'version'					=> $data['version'],
				'status'					=> array(
					'content'	=> str_replace(' ', '&nbsp;', lang($install_stat)),
					'class'		=> $data['installed'] ? 'open' : 'closed'
				),
				'action'					=> $action_url
			);
			//END $tableData[] = array(
		}

		$this->cached_vars['freeform_ft_docs_url'] = $this->model('Data')
														->doc_links['custom_fields'];

		// -------------------------------------
		//	Build table
		// -------------------------------------

		$table = ee('CP/Table', array(
			'sortable'	=> false,
			'search'	=> false,
		));

		$table->setColumns(
			array(
				'freeform_fieldtype_name',
				'description',
				'version',
				'status'		=> array(
					'type'	=> Table::COL_STATUS
				),
				'action'		=> array(
					'type'	=> Table::COL_TOOLBAR,
					'sort'	=> false
				)
			)
		);

		$table->setData($tableData);

		$table->setNoResultsText('no_fieldtypes');

		$this->cached_vars['table'] = $table->viewData(
			$this->mcp_link(array('method' => __FUNCTION__), false)
		);

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'		=> $this->mcp_link(array(
				'method' => 'fieldtypes'
			)),
			'cp_page_title'	=> lang('fieldtypes'),
			'table_head'	=> lang('fieldtypes')
		);

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'list',
			'highlight'	=> 'fieldtypes',
			'show_message'	=> false,
			'pkg_js'	=> array(),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('fieldtypes'))
			)
		));
	}
	//END field_types


	// --------------------------------------------------------------------

	/**
	 * notifications
	 *
	 * @access	public
	 * @param	string	message to output
	 * @return	string	outputted template
	 */

	public function notifications($message = '')
	{
		$this->prep_message($message);

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		$this->cached_vars['form_right_links']	= array(
			array(
				'link' => $this->mcp_link(array('method' => 'edit_notification')),
				'title' => lang('new_notification'),
			)
		);

		//--------------------------------------
		//  start vars
		//--------------------------------------

		$paginate			= '';
		$row_count			= 0;

		// -------------------------------------
		//	pagination?
		// -------------------------------------


		if ( ! $this->model('preference')->show_all_sites())
		{
			$this->model('notification')->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		$total_results = $this->model('notification')->count(array(), FALSE);

		//	----------------------------------------
		//	Pagination
		//	----------------------------------------

		$page	= 0;

		if ($total_results > $this->row_limit)
		{
			$page	= $this->get_post_or_zero('page') ?: 1;

			$mcp_link_array = array(
				'method' => __FUNCTION__
			);

			$this->cached_vars['pagination'] = ee('CP/Pagination', $total_results)
								->perPage($this->row_limit)
								->currentPage($page)
								->render($this->mcp_link($mcp_link_array, false));

			$this->model('notification')->limit(
				$this->row_limit,
				($page - 1) * $this->row_limit
			);
		}

		$this->model('notification')->order_by('notification_label');

		// -------------------------------------
		//	data
		// -------------------------------------

		$tableData	= array();

		$rows = $this->model('notification')->get();

		if ($rows !== FALSE)
		{
			// -------------------------------------
			//	rows
			// -------------------------------------

			foreach ($rows as $row)
			{
				// -------------------------------------
				//	piles o' links
				// -------------------------------------

				$row['notification_settings_link']	= $this->mcp_link(array(
					'method'			=> 'edit_notification',
					'notification_id'	=> $row['notification_id']
				));

				$row['notification_duplicate_link']	= $this->mcp_link(array(
					'method'		=> 'edit_notification',
					'duplicate_id'	=> $row['notification_id']
				));

				$tableData[] = array(
					$row['notification_id'],
					'label'	=> array(
						'type'		=> 'html',
						'content'	=> $this->view('notifications/_row', array('col_type' => 'label', 'row' => $row))
					),
					'subject'	=> array(
						'type'		=> 'html',
						'content'	=> $this->view('notifications/_row', array('col_type' => 'subject', 'row' => $row))
					),
					'manage'	=> array(
						'type'		=> 'html',
						'content'	=> $this->view('notifications/_row', array('col_type' => 'manage', 'row' => $row))
					),
					array(
						'name'		=> 'selections[]',
						'value'	=> $row['notification_id'],
						'data'		=> array(
							'confirm' => lang('notification') . ': <b>' . htmlentities($row['notification_label'], ENT_QUOTES) . '</b>'
						)
					)
				);
			}
		}

		// -------------------------------------
		//	Build table
		// -------------------------------------

		$table = ee('CP/Table', array(
			'sortable'	=> false,
			'search'	=> false,
		));

		$table->setColumns(
			array(
				'id' => array(
					'type'	=> Table::COL_ID
				),
				'label'	=> array(
					'type'	=> 'html'
				),
				'subject'	=> array(
					'type'	=> 'html'
				),
				'manage'	=> array(
					'type'	=> 'html'
				),
				array(
					'type'	=> Table::COL_CHECKBOX,
					'name'	=> 'selection'
				)
			)
		);

		$table->setData($tableData);

		$table->setNoResultsText('no_notifications');

		$this->cached_vars['table'] = $table->viewData(
			$this->mcp_link(array('method' => __FUNCTION__), false)
		);

		// -------------------------------------
		//	Modal for delete confirmation
		// -------------------------------------

		$this->cached_vars['footer'] = array(
			'type'			=> 'bulk_action_form',
			'submit_lang'	=> lang('notifications_remove')
		);

		$this->mcp_modal_confirm(array(
			'form_url'	=> $this->mcp_link(array('method' => 'notifications_delete')),
			'name'		=> 'notifications',
			'kind'		=> lang('notifications'),
		));

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'		=> $this->mcp_link(array(
				'method' => 'edit_notification'
			)),
			'cp_page_title'	=> lang('notifications'),
			'table_head'	=> lang('notifications')
		);

		return $this->mcp_view(array(
			'file'		=> 'list',
			'highlight'	=> 'notifications',
			'show_message' => false,
			'pkg_js'	=> array(),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('notifications'))
			)
		));
	}
	//END notifications


	// --------------------------------------------------------------------

	/**
	 * notifications_delete()
	 *
	 * @access	public
	 * @return	string
	 */

	public function notifications_delete()
	{
		$message = 'delete_notification_success';

		//if everything is all nice and array like, DELORT
		//but one last check on each item to make sure its a number
		if (is_array($_POST['selections']))
		{
			foreach ($_POST['selections'] as $id)
			{
				if ($this->is_positive_intlike($id))
				{
					$this->lib('Notifications')->delete_notification($id);
				}
			}
		}

		//the voyage home
		ee()->functions->redirect($this->mcp_link(array(
			'method'	=> 'notifications',
			'msg'		=> $message
		)));
	}

	//END notifications_delete()


	// --------------------------------------------------------------------

	/**
	 * edit_notification
	 *
	 * @access	public
	 * @return	string
	 */

	public function edit_notification()
	{
		$this->prep_message();

		// --------------------------------------------
		//  Start sections
		// --------------------------------------------

		$sections		= array();
		$main_section	= array();

		// --------------------------------------------
		//  Defaults
		// --------------------------------------------


		$defaultPrefs = $this->model('notification')->default_prefs;

		// -------------------------------------
		//	Updating?
		// -------------------------------------

		if (ee()->input->get('notification_id'))
		{
			$notification_data = $this->model('notification')->get_row(ee()->input->get('notification_id'));

			foreach ($notification_data as $key => $value)
			{
				$defaultPrefs[$key]['default'] = form_prep($value);
			}
		}

		// -------------------------------------
		//	Duplicating?
		// -------------------------------------

		$duplicate_id = $this->get_post_or_zero('duplicate_id');

		if ($duplicate_id > 0)
		{
			$notification_data = $this->model('notification')->get_row($duplicate_id);

			if ($notification_data)
			{
				foreach ($notification_data as $key => $value)
				{
					//TODO: remove other items that dont need to be duped?

					if (in_array($key, array(
						'notification_id',
						'notification_label',
						'notification_name'
					)))
					{
						continue;
					}

					$defaultPrefs[$key]['default'] = form_prep($value);
				}
			}
		}

		// -------------------------------------
		//	get available fields
		// -------------------------------------

		$this->cached_vars['available_fields']	= array();

		if (($fields = $this->model('field')->get()) !== FALSE)
		{
			$this->cached_vars['available_fields']	= $fields;
		}

		$this->cached_vars['standard_tags']		= $this->model('Data')->standard_notification_tags;

		if (isset($defaultPrefs['template_data']) && isset($defaultPrefs['template_data']['default'])) {
			$defaultPrefs['template_data']['default'] = html_entity_decode($defaultPrefs['template_data']['default'], ENT_QUOTES);
		}

		// -------------------------------------
		//	Loop and load
		// -------------------------------------

		$hidden = array();

		foreach ($defaultPrefs as $short_name => $data)
		{
			if ($data['type'] == 'hidden')
			{
				$hidden[$short_name] = isset($data['value']) ? $data['value']: $data['default'];
				continue;
			}

			if (isset($data['desc'])) {
				$desc = $data['desc'];
			} else {
				$desc_name = $short_name . '_subtext';
				$desc      = lang($desc_name);

				//if we don't have a description don't set it
				$desc = ($desc !== $desc_name) ? $desc : '';
			}

			$data['default']	= str_replace(array('%webmaster_name%', '%webmaster_email%'), array(ee()->config->item('webmaster_name'), ee()->config->item('webmaster_email')), $data['default']);

			$required	= FALSE;
			$disabled	= '';
			$attrs		= (empty($data['attrs'])) ? $disabled: $data['attrs'] . $disabled;

			$fields		= array(
				$short_name => array_merge($data, array(
					'value'		=> isset($data['value']) ? $data['value']: $data['default'],
					'attrs'		=> $attrs,
					'required'	=> isset($data['required']) ? $data['required'] : false
				))
			);

			// --------------------------------------------
			//  Set the row now
			// --------------------------------------------

			$main_section[$short_name] = array(
				'wide'		=> isset($wide),
				'hide'		=> (isset($data['type']) AND $data['type'] == 'hidden') ? TRUE: FALSE,
				'title'		=> lang($short_name),
				'desc'		=> $desc,
				'fields'	=> $fields
			);
		}

		if ( ! empty($hidden))
		{
			$this->cached_vars['form_hidden'] = $hidden;
		}

		$sections[]	= $main_section;

		$this->cached_vars['sections'] = $sections;

		$this->cached_vars['form_url'] = $this->mcp_link(array(
			'method' => 'save_notification'
		));

		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		$new_update	= (ee()->input->get('notification_id')) ? 'notification_update': 'notification_add';

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'				=> $this->mcp_link(array(
				'method' => 'save_notification'
			)),
			'cp_page_title'			=> lang($new_update),
			'save_btn_text'			=> 'btn_save_notification',
			'save_btn_text_working'	=> 'btn_saving_notification'
		);

		return $this->mcp_view(array(
			'file'		=> 'form',
			'highlight'	=> 'notifications',
			'show_message' => false,
			'pkg_js'	=> array('standard_cp.min', 'notifications_cp.min'),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(
					lang('notifications'),
					$this->mcp_link(array('method' => 'notifications'), false)
				),
				array(lang($new_update))
			)
		));
	}
	//END edit_notification

	


	// --------------------------------------------------------------------

	/**
	 * Email Logs
	 *
	 * @access	public
	 * @param	string $message		incoming success message if any
	 * @return	string				html output
	 */

	public function email_logs($message = '')
	{
		$this->prep_message($message);

		//--------------------------------------
		//  start vars
		//--------------------------------------

		$paginate			= '';
		$row_count			= 0;

		// -------------------------------------
		//	pagination?
		// -------------------------------------

		if ( ! $this->model('preference')->show_all_sites())
		{
			$this->model('email_log')->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		$total_results = $this->model('email_log')->count(array(), FALSE);

		// do we need pagination?
		if ( $total_results > $this->row_limit)
		{
			$page	= $this->get_post_or_zero('page') ?: 1;

			$mcp_link_array = array(
				'method' => __FUNCTION__
			);

			$this->cached_vars['pagination'] = ee('CP/Pagination', $total_results)
								->perPage($this->row_limit)
								->currentPage($page)
								->render($this->mcp_link($mcp_link_array, false));

			$this->model('email_log')->limit(
				$this->row_limit,
				($page - 1) * $this->row_limit
			);
		}

		//newest first
		$this->model('email_log')->order_by('id', 'desc');

		$this->cached_vars['paginate'] = $paginate;

		// -------------------------------------
		//	data
		// -------------------------------------

		$email_logs = array();

		$email_logs = $this->model('email_log')->get();

		$sites = $this->model('Data')->get_sites();

		$tableData = array();

		if ( ! empty($email_logs))
		{
			foreach($email_logs as $key => $log)
			{
				$tableData[] = array(
					'site'				=> $sites[$log['site_id']],
					'date'				=> (
						$log['date'] > 0
					) ?
						$this->format_cp_date($log['date']) :
						lang('n_a'),
					'type'				=> ucfirst($log['type']),
					'success_reported'	=> (
						$log['success'] == 'y'
					) ? lang('yes') : lang('no'),
					'from'				=> $log['from'],
					'from_name'			=> $log['from_name'],
					'to'				=> $log['to'],
					'subject'			=> $log['subject'],
					'message'			=> str_replace(
						"\n",
						"<br/>",
						htmlentities($log['message'])
					),
					'debug_info'		=> $log['debug_info'],
				);
			}
		}

		// -------------------------------------
		//	Build table
		// -------------------------------------

		$table = ee('CP/Table', array(
			'sortable'	=> false,
			'search'	=> false,
		));

		$table->setColumns(
			array(
				'site',
				'date',
				'type',
				'success_reported',
				'from',
				'from_name',
				'to',
				'subject',
				'message',
				'debug_info',
			)
		);

		$table->setData($tableData);

		$table->setNoResultsText('no_emails_logged');

		$this->cached_vars['table'] = $table->viewData(
			$this->mcp_link(array('method' => __FUNCTION__), false)
		);
		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		$this->cached_vars['form_right_links']	= array(
			array(
				'link' => $this->mcp_link(array(
					'method' => 'clear_email_logs'
				)),
				'title' => lang('clear_email_log'),
			)
		);

		$logging_enabled = $this->check_yes(
			$this->model('preference')->preference('email_logging')
		);

		if ($logging_enabled)
		{
			$this->cached_vars['header_note'] = str_replace(
				'%preferences%',
				'<a href="' . $this->mcp_link(array('method' => 'preferences')) .
					'">' . lang('preferences') . '</a>',
				lang('email_log_enabled')
			);
		}
		else
		{
			$this->cached_vars['header_note'] = str_replace(
				'%preferences%',
				'<a href="' . $this->mcp_link(array('method' => 'preferences')) .
					'">' . lang('preferences') . '</a>',
				lang('email_log_not_enabled')
			);
		}

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'		=> $this->mcp_link(array(
				'method' => 'email_logs'
			)),
			'cp_page_title'	=> lang('email_logs'),
			'table_head'	=> lang('email_logs')
		);

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'list',
			'highlight'	=> 'email_logs',
			'show_message'	=> false,
			'pkg_js'	=> array(),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('email_logs'))
			)
		));
	}
	//END email_logs


	// --------------------------------------------------------------------

	/**
	 * Clear Email Logs
	 *
	 * @access	public
	 * @return	void (redirect)
	 */
	public function clear_email_logs()
	{
		if (ee()->db->table_exists('exp_freeform_email_logs'))
		{
			ee()->db->truncate('exp_freeform_email_logs');
		}

		ee()->functions->redirect(
			$this->mcp_link(array(
				'method'	=> 'email_logs',
				'msg'		=> 'email_log_cleared'
			))
		);
	}
	//END clear_email_logs


	// --------------------------------------------------------------------

	/**
	 * preferences
	 *
	 * @access	public
	 * @return	string
	 */

	public function preferences($message = '')
	{
		$this->prep_message($message, TRUE, TRUE);

		// -------------------------------------
		//	global prefs
		// -------------------------------------

		$msm_enabled			= $this->model('preference')->msm_enabled;
		$is_admin				= (ee()->session->userdata('group_id') == 1);
		$show_global_prefs		= ($is_admin AND $msm_enabled);
		$global_pref_data		= array();
		$global_prefs			= $this->model('preference')->global_prefs_with_defaults();
		$default_global_prefs	= $this->model('preference')->default_global_preferences;

		$global_section = array();

		//dynamically get prefs and lang so we can just add them to defaults
		foreach($global_prefs as $short_name => $value)
		{
			//this skips things that don't need to be shown on this page
			//so we can use the prefs table for other areas of the addon
			if ( ! isset($default_global_prefs[$short_name]))
			{
				continue;
			}

			// --------------------------------------------
			//  Set the row now
			// --------------------------------------------

			$short_name_desc = $short_name . '_desc';
			$type = $default_global_prefs[$short_name]['type'];

			$global_section[$short_name] = array(
				'hide'		=> ($type == 'hidden'),
				'title'		=> lang($short_name),
				'desc'		=> (
					lang($short_name_desc) == $short_name_desc
				) ? '' : lang($short_name_desc),
				'fields'	=> array(
					$short_name => array_merge(
						$default_global_prefs[$short_name],
						array(
							'value'		=> $value
						)
					)
				)
			);
		}

		if ($show_global_prefs)
		{
			$sections['global_prefs']	= $global_section;
		}

		// -------------------------------------
		//	these two will only be used if MSM
		//	is enabled, but setting them
		//	anyway to avoid potential PHP errors
		// -------------------------------------

		$prefs_all_sites	=  ! $this->check_no(
			$this->model('preference')->global_preference('prefs_all_sites')
		);

		$lang_site_prefs_for_site = (
			lang('site_prefs_for') . ' ' . (
				($prefs_all_sites) ?
					lang('all_sites') :
					ee()->config->item('site_label')
			)
		);

		//--------------------------------------
		//  per site prefs
		//--------------------------------------

		$pref_data		= array();
		$main_section	= array();

		$prefs			= $this->model('preference')->prefs_with_defaults();
		$default_prefs	= $this->model('preference')->default_preferences;

		//load custom html
        if (isset($default_prefs['form_statuses'])) {
            $default_prefs['form_statuses']['content'] = $this->view(
                'list_input',
                array(
                    'list' => $prefs['form_statuses'],
                    'name' => 'form_statuses'
                )
            );
        }


		foreach($prefs as $short_name => $value)
		{
			if (
				//this skips things that don't need to be shown on this page
				//so we can use the prefs table for other areas of the addon
				! isset($default_prefs[$short_name])
				OR
				//admin only pref?
				(
					! $is_admin AND
					in_array($short_name, $this->model('preference')->admin_only_prefs)
				)
				OR
				//MSM pref and no MSM?
				(
					! $msm_enabled AND
					in_array($short_name, $this->model('preference')->msm_only_prefs)
				)
			)
			{
				continue;
			}

			// --------------------------------------------
			//  Set the row now
			// --------------------------------------------

			$short_name_desc = $short_name . '_desc';
			$type = $default_prefs[$short_name]['type'];

			$main_section[$short_name] = array(
				'hide'		=> ($type == 'hidden'),
				'title'		=> lang($short_name),
				'desc'		=> (
					lang($short_name_desc) == $short_name_desc
				) ? '' : lang($short_name_desc),
				'fields'	=> array(
					$short_name => array_merge(
						$default_prefs[$short_name],
						array(
							'value'		=> $value
						)
					)
				)
			);
		}


		//---------------------------------------------
		//  Load Page and set view vars
		//---------------------------------------------

		$sections['preferences']	= $main_section;

		$this->cached_vars['sections'] = $sections;

		$this->cached_vars['form_url'] = $this->mcp_link(array(
			'method' => 'save_preferences'
		));

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'				=> $this->mcp_link(array(
				'method' => 'save_preferences'
			)),
			'cp_page_title'			=> lang('preferences'),
			'save_btn_text'			=> 'save_preferences',
			'save_btn_text_working'	=> 'btn_saving'
		);

		return $this->mcp_view(array(
			'file'		=> 'form',
			'highlight'	=> 'preferences',
			'show_message' => false,
			'pkg_js'	=> array('standard_cp.min', 'preferences_cp.min'),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('preferences'))
			)
		));
	}

	//END preferences


	// --------------------------------------------------------------------

	/**
	 * delete confirm page abstractor
	 *
	 * @access	private
	 * @param	string	method you want to post data to after confirm
	 * @param	array	all of the values you want to carry over to post
	 * @param	string	the lang line of the warning message to display
	 * @param	string	the lang line of the submit button for confirm
	 * @param   bool    $message_use_lang use the lang wrapper for the message?
	 * @return	string
	 */

	private function delete_confirm($method,		$hidden_values,
									 $message_line, $submit_line = 'delete',
									 $message_use_lang = TRUE)
	{
		$this->cached_vars = array_merge($this->cached_vars, array(
			'hidden_values' => $hidden_values,
			'lang_message'	=> ($message_use_lang ? lang($message_line) : $message_line),
			'lang_submit'	=> lang($submit_line),
			'form_url'		=> $this->mcp_link(array('method' => $method))
		));

		$this->cached_vars['current_page']	= $this->view(
			'delete_confirm.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END delete_confirm


	//--------------------------------------------------------------------
	//	end views
	//--------------------------------------------------------------------


	// --------------------------------------------------------------------

	/**
	 * Clean up inputted paragraph html
	 *
	 * @access	public
	 * @param	string $string	string to clean
	 * @return	mixed			string if html or json if not
	 */

	public function clean_paragraph_html($string = '', $allow_ajax = TRUE)
	{
		if ( ! $string OR trim($string == ''))
		{
			$string = ee()->input->get_post('clean_string');

			if ( $string === FALSE OR trim($string) === '')
			{
				$string = '';
			}
		}

		$allowed_tags	= '<' . implode('><', $this->model('Data')->allowed_html_tags) . '>';

		$string			= strip_tags($string, $allowed_tags);

		$string			= ee('Security/XSS')->clean($string);

		return $string;
	}
	//END clean_paragraph_html


	// --------------------------------------------------------------------

	/**
	 * Fieldtype Actions
	 *
	 * this installs or uninstalles depending on the sent action
	 * this will redirect no matter what
	 *
	 * @access	public
	 * @return	null
	 */

	public function freeform_fieldtype_action()
	{
		$return_url = array('method' => 'fieldtypes');
		$name		= ee()->input->get_post('name', TRUE);
		$action	= ee()->input->get_post('action');

		if ($name AND $action)
		{
			if ($action === 'install')
			{
				if ($this->lib('Fields')->install_fieldtype($name))
				{
					$return_url['msg'] = 'fieldtype_installed';
				}
				else
				{
					return $this->lib('Utils')->full_stop(lang('fieldtype_install_error'));
				}
			}
			else if ($action === 'uninstall')
			{
				$uninstall = $this->uninstall_confirm_fieldtype($name);

				//if its not a boolean true, its a confirmation
				if ($uninstall !== TRUE)
				{
					return $uninstall;
				}
				else
				{
					$return_url['msg'] = 'fieldtype_uninstalled';
				}
			}
		}

		if ($this->is_ajax_request())
		{
			return $this->send_ajax_response(array(
				'success'	=> TRUE,
				'message'	=> lang('fieldtype_uninstalled')
			));
		}
		else
		{
			ee()->functions->redirect($this->mcp_link($return_url));
		}
	}
	//END freeform_fieldtype_action


	// --------------------------------------------------------------------

	/**
	 * save field layout
	 *
	 * ajax called method for saving field layout in the entries screen
	 *
	 * @access	public
	 * @return	string
	 */

	public function save_field_layout()
	{
		$save_for		= ee()->input->get_post('save_for', TRUE);
		$shown_fields	= ee()->input->get_post('shown_fields', TRUE);
		$form_id		= $this->get_post_or_zero('form_id');
		$form_data		= $this->model('form')->get_info($form_id);

		// -------------------------------------
		//	valid
		// -------------------------------------

		if (
			! $this->is_ajax_request() OR
			! is_array($shown_fields) OR
			! $form_data
		)
		{
			return $this->send_ajax_response(array(
				'success'	=> FALSE,
				'message'	=> lang('invalid_input')
			));
		}

		// -------------------------------------
		//	permissions?
		// -------------------------------------

		//if (ee()->session->userdata('group_id') != 1 AND
		//	! $this->check_yes($this->model('preference')->preference('allow_user_field_layout'))
		//)
		//{
		//	return $this->send_ajax_response(array(
		//		'success'	=> FALSE,
		//		'message'	=> lang('invalid_permissions')
		//	));
		//}

		// -------------------------------------
		//	save
		// -------------------------------------

		$field_layout_prefs = $this->model('preference')->preference('field_layout_prefs');

		$original_prefs	= (
			is_array($field_layout_prefs) ?
				$field_layout_prefs :
				array()
		);


		// -------------------------------------
		//	who is it for?
		// -------------------------------------

		$for				= array();

		foreach ($save_for as $item)
		{
			//if this is for everyone, we can stop
			if (in_array($item, array('just_me', 'everyone')))
			{
				$for = $item;
				break;
			}

			if ($this->is_positive_intlike($item))
			{
				$for[] = $item;
			}
		}

		// -------------------------------------
		//	what do they want to see?
		// -------------------------------------

		$standard_columns = $this->get_standard_column_names();
		$possible_columns = $standard_columns;

		//build possible columns
		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			$possible_columns[] = $field_id;
		}

		$data			 = array();

		$prefix		 = $this->model('form')->form_field_prefix;

		//check for field validity, no funny business
		foreach ($shown_fields as $field_name)
		{
			$field_id = str_replace($prefix, '', $field_name);

			if (in_array($field_name, $standard_columns))
			{
				$data[] = $field_name;

				unset(
					$possible_columns[
						array_search(
							$field_name,
							$possible_columns
						)
					]
				);
			}
			else if (in_array($field_id , array_keys($form_data['fields'])))
			{
				$data[] = $field_id;

				unset(
					$possible_columns[
						array_search(
							$field_id,
							$possible_columns
						)
					]
				);
			}
		}

		//removes holes
		sort($possible_columns);

		// -------------------------------------
		//	insert the data per group or all
		// -------------------------------------

		$settings = array(
			'visible'	=> $data,
			'hidden'	=> $possible_columns
		);

		if ($for == 'just_me')
		{
			$id = ee()->session->userdata('member_id');

			$original_prefs['entry_layout_prefs']['member'][$id] = $settings;
		}
		else if ($for == 'everyone')
		{
			$original_prefs['entry_layout_prefs']['all']['visible'] = $settings;
		}
		else
		{
			foreach ($for as $who)
			{
				$original_prefs['entry_layout_prefs']['group'][$who]['visible'] = $settings;
			}
		}

		// -------------------------------------
		//	save
		// -------------------------------------

		$this->model('preference')->set_preferences(array(
			'field_layout_prefs' => json_encode($original_prefs)
		));

		// -------------------------------------
		//	success!
		// -------------------------------------

		//TODO test for ajax request or redirect back
		//don't want JS erorrs preventing this from
		//working
		$this->send_ajax_response(array(
			'success'		=> TRUE,
			'message'		=> lang('layout_saved'),
			'update_fields' => array()
		));

		//prevent EE CP default spit out
		exit();
	}
	//END save_field_layout


	// --------------------------------------------------------------------

	/**
	 * save_form
	 *
	 * @access	public
	 * @return	null
	 */

	public function save_form()
	{
		// -------------------------------------
		//	form ID? we must be editing
		// -------------------------------------

		$form_id		= $this->get_post_or_zero('form_id');

		$update			= ($form_id != 0);

		// -------------------------------------
		//	default status
		// -------------------------------------

		$default_status	= ee()->input->get_post('default_status', TRUE);

		$default_status	= ($default_status AND trim($default_status) != '') ?
							$default_status :
							$this->model('Data')->defaults['default_form_status'];

		// -------------------------------------
		//	composer return?
		// -------------------------------------

		$ffp = $this->addon_info['freeform_pro'];
		$ret = ee()->input->get_post('ret');

		$do_composer			= ($ffp AND $ret == 'composer');
		$composer_save_finish	= ($ffp AND $ret == 'composer_save_finish');

		if ($composer_save_finish)
		{
			$do_composer = TRUE;
		}

		// -------------------------------------
		//	error on empty items or bad data
		//	(doing this via ajax in the form as well)
		// -------------------------------------

		$errors			= array();

		// -------------------------------------
		//	field name
		// -------------------------------------

		$form_name		= ee()->input->get_post('form_name', TRUE);

		//if the field label is blank, make one for them
		//we really dont want to do this, but here we are
		if ( ! $form_name OR ! trim($form_name))
		{
			$errors['form_name'] = lang('form_name_required');
		}
		else
		{
			$form_name = strtolower(trim($form_name));

			if ( in_array($form_name, $this->model('Data')->prohibited_names ) )
			{
				$errors['form_name'] = str_replace(
					'%name%',
					$form_name,
					lang('reserved_form_name')
				);
			}

			//if the form_name they submitted isn't like how a URL title may be
			//also, cannot be numeric
			if (preg_match('/[^a-z0-9\_\-]/i', $form_name) OR
				is_numeric($form_name))
			{
				$errors['form_name'] = lang('form_name_can_only_contain');
			}



			//get dupe from field names
			$dupe_data = $this->model('form')->get_row(array(
				'form_name' => $form_name
			));

			//if we are updating, we don't want to error on the same field id
			if ( ! empty($dupe_data) AND
				! ($update AND $dupe_data['form_id'] == $form_id))
			{
				$errors['form_name'] = str_replace(
					'%name%',
					$form_name,
					lang('form_name_exists')
				);
			}
		}

		// -------------------------------------
		//	form label
		// -------------------------------------

		$form_label = ee()->input->get_post('form_label', TRUE);

		if ( ! $form_label OR ! trim($form_label) )
		{
			$errors['form_label'] = lang('form_label_required');
		}

		// -------------------------------------
		//	admin notification email
		// -------------------------------------

		$admin_notification_email = ee()->input->get_post('admin_notification_email', TRUE);

		if ($admin_notification_email AND
			trim($admin_notification_email) != '')
		{
			ee()->load->helper('email');

			$emails = preg_split(
				'/(\s+)?\,(\s+)?/',
				$admin_notification_email,
				-1,
				PREG_SPLIT_NO_EMPTY
			);

			$errors['admin_notification_email'] = array();

			foreach ($emails as $key => $email)
			{
				$emails[$key] = trim($email);

				if ( ! valid_email($email))
				{
					$errors['admin_notification_email'][] = str_replace('%email%', $email, lang('non_valid_email'));
				}
			}

			if (empty($errors['admin_notification_email']))
			{
				unset($errors['admin_notification_email']);
			}

			$admin_notification_email = implode('|', $emails);
		}
		else
		{
			$admin_notification_email = '';
		}

		// -------------------------------------
		//	user email field
		// -------------------------------------

		$user_email_field = ee()->input->get_post('user_email_field');



		$field_ids = $this->model('field')->key('field_id', 'field_id')->get();

		if ($user_email_field AND
			$user_email_field !== '--' AND
			trim($user_email_field) !== '' AND
			! in_array($user_email_field, $field_ids ))
		{
			$errors['user_email_field'] = lang('invalid_user_email_field');
		}

		// -------------------------------------
		//	errors? For shame :(
		// -------------------------------------

		if ( ! empty($errors))
		{
			return $this->lib('Utils')->full_stop($errors);
		}

		//send ajax response exists
		//but this is in case someone is using a replacer
		//that uses
		if ($this->check_yes(ee()->input->get_post('validate_only')))
		{
			if ($this->is_ajax_request())
			{
				$this->send_ajax_response(array(
					'success'	=> TRUE,
					'errors'	=> array()
				));
			}

			exit();
		}

		// -------------------------------------
		//	field ids
		// -------------------------------------

		$field_ids = array_filter(
			$this->lib('Utils')->pipe_split(
				ee()->input->get_post('field_order', TRUE)
			),
			array($this, 'is_positive_intlike')
		);

		//$field_ids = ee()->input->get_post('form_fields', TRUE);
		//if (!$field_ids) {
		//	$field_ids = array();
		//}

		$sorted_field_ids = $field_ids;

		sort($sorted_field_ids);

		// -------------------------------------
		//	insert data
		// -------------------------------------

		$data = array(
			'form_name'					=> strip_tags($form_name),
			'form_label'				=> strip_tags($form_label),
			'default_status'			=> $default_status,
			'user_notification_id'		=> $this->get_post_or_zero('user_notification_id'),
			'admin_notification_id'		=> $this->get_post_or_zero('admin_notification_id'),
			'admin_notification_email'	=> $admin_notification_email,
			'form_description'			=> strip_tags(ee()->input->get_post('form_description', TRUE)),
			'author_id'				=> ee()->session->userdata('member_id'),
			'field_ids'					=> implode('|', $sorted_field_ids),
			'field_order'				=> implode('|', $field_ids),
			'notify_admin'				=> (ee()->input->get_post('notify_admin') == 'y') ? 'y' : 'n',
			'notify_user'				=> (ee()->input->get_post('notify_user') == 'y') ? 'y' : 'n',
			'user_email_field'			=> $user_email_field,
            'entry_url'                 => ee()->input->get_post("entry_url") ?: null,
		);

		if ($do_composer)
		{
			unset($data['field_ids']);
			unset($data['field_order']);
		}

		if ($update)
		{
			unset($data['author_id']);

			if ( ! $do_composer)
			{
				$data['composer_id'] = 0;
			}

			$this->lib('Forms')->update_form($form_id, $data);
		}
		else
		{
			//we don't want this running on update, will only happen for dupes
			$composer_id = $this->get_post_or_zero('composer_id');

			//this is a dupe and they want composer to dupe too?
			if ($do_composer AND $composer_id > 0)
			{


				$composer_data = $this->model('composer')
									 ->select('composer_data')
									 ->where('composer_id', $composer_id)
									 ->get_row();

				if ($composer_data !== FALSE)
				{
					$data['composer_id'] = $this->model('composer')->insert(
						array(
							'composer_data' => $composer_data['composer_data'],
							'site_id'		=> ee()->config->item('site_id')
						)
					);
				}
			}

			$form_id = $this->lib('Forms')->create_form($data);
		}

		// -------------------------------------
		//	return
		// -------------------------------------

		if ( ! $composer_save_finish AND $do_composer)
		{
			ee()->functions->redirect($this->mcp_link(array(
				'method'	=> 'form_composer',
				'form_id'	=> $form_id,
				'msg'		=> 'edit_form_success'
			)));
		}
		//'save and finish, default'
		else
		{
			ee()->functions->redirect($this->mcp_link(array(
				'method'	=> 'forms',
				'msg'		=> 'edit_form_success'
			)));
		}
	}
	//END save_form


	// --------------------------------------------------------------------

	/**
	 * Save Entry
	 *
	 * @access	public
	 * @return	null	redirect
	 */

	public function save_entry()
	{
		// -------------------------------------
		//	edit?
		// -------------------------------------

		$form_id	= $this->get_post_or_zero('form_id');
		$entry_id	= $this->get_post_or_zero('entry_id');




		$form_data = $this->model('form')->get_info($form_id);

		// -------------------------------------
		//	valid form id
		// -------------------------------------

		if ( ! $form_data)
		{
			return $this->lib('Utils')->full_stop(lang('invalid_form_id'));
		}

		$previous_inputs	= array();

		if ( $entry_id > 0)
		{
			$entry_data = $this->model('entry')
							  ->id($form_id)
							  ->where('entry_id', $entry_id)
							  ->get_row();

			if ( ! $entry_data)
			{
				return $this->lib('Utils')->full_stop(lang('invalid_entry_id'));
			}

			$previous_inputs = $entry_data;
		}

		// -------------------------------------
		//	form data
		// -------------------------------------

		$field_labels	= array();
		$valid_fields	= array();

		foreach ( $form_data['fields'] as $row)
		{
			$field_labels[$row['field_name']]	= $row['field_label'];
			$valid_fields[]					= $row['field_name'];
		}

		// -------------------------------------
		//	is this an edit? entry_id
		// -------------------------------------

		$edit			= ($entry_id AND $entry_id != 0);

		// -------------------------------------
		//	for hooks
		// -------------------------------------

		$this->edit			= $edit;
		$this->multipage	= FALSE;
		$this->last_page	= TRUE;

		// -------------------------------------
		//	prevalidate hook
		// -------------------------------------

		$errors				= array();
		//to assist backward compat
		$this->field_errors	= array();

		if (ee()->extensions->active_hook('freeform_module_validate_begin') === TRUE)
		{
			$backup_errors = $errors;

			$errors = ee()->extensions->call(
				'freeform_module_validate_begin',
				$errors,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;

			//valid data?
			if ( ! is_array($errors) AND
				 $this->check_yes($this->model('preference')->preference('hook_data_protection')))
			{
				$errors = $backup_errors;
			}
		}

		// -------------------------------------
		//	validate
		// -------------------------------------

		$field_input_data	= array();

		$field_list			= array();

		// -------------------------------------
		//	status?
		// -------------------------------------

		$available_statuses	= $this->model('preference')->get_form_statuses();

		$status = ee()->input->get_post('status');

		if ( ! array_key_exists($status, $available_statuses))
		{
			$field_input_data['status'] = $this->model('Data')->defaults['default_form_status'];
		}
		else
		{
			$field_input_data['status'] = $status;
		}

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			$field_list[$field_data['field_name']] = $field_data['field_label'];

			$field_post = ee()->input->get_post($field_data['field_name']);

			//if it's not even in $_POST or $_GET, lets skip input
			//unless its an uploaded file, then we'll send false anyway
			//because its fieldtype will handle the rest of that work
			if ($field_post !== FALSE OR
				isset($_FILES[$field_data['field_name']]))
			{
				$field_input_data[$field_data['field_name']] = $field_post;
			}
		}

		//form fields do thier own validation,
		//so lets just get results! (sexy results?)
		$this->field_errors = array_merge(
			$this->field_errors,
			$this->lib('Fields')->validate(
				$form_id,
				$field_input_data
			)
		);

		// -------------------------------------
		//	post validate hook
		// -------------------------------------

		if (ee()->extensions->active_hook('freeform_module_validate_end') === TRUE)
		{
			$backup_errors = $errors;

			$errors = ee()->extensions->call(
				'freeform_module_validate_end',
				$errors,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;

			//valid data?
			if ( ! is_array($errors) AND
				 $this->check_yes($this->model('preference')->preference('hook_data_protection')))
			{
				$errors = $backup_errors;
			}
		}

		$errors = array_merge($errors, $this->field_errors);

		// -------------------------------------
		//	halt on errors
		// -------------------------------------

		if (count($errors) > 0)
		{
			$this->lib('Utils')->full_stop($errors);
		}

		//send ajax response exists
		//but this is in case someone is using a replacer
		//that uses
		if ($this->check_yes(ee()->input->get_post('validate_only')))
		{
			if ($this->is_ajax_request())
			{
				$this->send_ajax_response(array(
					'success'	=> TRUE,
					'errors'	=> array()
				));
			}

			exit();
		}

		// -------------------------------------
		//	entry insert begin hook
		// -------------------------------------

		if (ee()->extensions->active_hook('freeform_module_insert_begin') === TRUE)
		{
			$backup_field_input_data = $field_input_data;

			$field_input_data = ee()->extensions->call(
				'freeform_module_insert_begin',
				$field_input_data,
				$entry_id,
				$form_id,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;

			//valid data?
			if ( ! is_array($field_input_data) AND
				 $this->check_yes($this->model('preference')->preference('hook_data_protection')))
			{
				$field_input_data = $backup_field_input_data;
			}
		}

		// -------------------------------------
		//	insert/update data into db
		// -------------------------------------

		if ($edit)
		{
			$this->lib('Forms')->update_entry(
				$form_id,
				$entry_id,
				$field_input_data
			);
		}
		else
		{
			$entry_id = $this->lib('Forms')->insert_new_entry(
				$form_id,
				$field_input_data
			);
		}

		// -------------------------------------
		//	entry insert begin hook
		// -------------------------------------

		if (ee()->extensions->active_hook('freeform_module_insert_end') === TRUE)
		{
			ee()->extensions->call(
				'freeform_module_insert_end',
				$field_input_data,
				$entry_id,
				$form_id,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;
		}

		// -------------------------------------
		//	return
		// -------------------------------------

		$success_line = ($edit) ? 'edit_entry_success' : 'new_entry_success';

		if ($this->is_ajax_request())
		{
			return $this->send_ajax_response(array(
				'form_id'	=> $form_id,
				'entry_id'	=> $entry_id,
				'message'	=> lang($success_line),
				'success'	=> TRUE
			));
		}
		//'save and finish, default'
		else
		{
			ee()->functions->redirect($this->mcp_link(array(
				'method'	=> 'entries',
				'form_id'	=> $form_id,
				'msg'		=> $success_line
			)));
		}
	}
	//END edit_entry

	

	// --------------------------------------------------------------------

	/**
	 * approve entries
	 *
	 * accepts ajax call to approve entries
	 * or can be called via another view function on post
	 *
	 * @access	public
	 * @param	int	form id
	 * @param	mixed	array or int of entry ids to approve
	 * @return	string
	 */

	public function approve_entries($form_id = 0, $entry_ids = array())
	{
		// -------------------------------------
		//	valid form id?
		// -------------------------------------

		if ( ! $form_id OR $form_id <= 0)
		{
			$form_id = $this->get_post_form_id();
		}

		if ( ! $form_id)
		{
			$this->lib('Utils')->full_stop(lang('invalid_form_id'));
		}

		// -------------------------------------
		//	entry ids?
		// -------------------------------------

		if ( ! $entry_ids OR empty($entry_ids) )
		{
			$entry_ids = $this->get_post_entry_ids();
		}

		//check
		if ( ! $entry_ids)
		{
			$this->lib('Utils')->full_stop(lang('invalid_entry_id'));
		}

		// -------------------------------------
		//	approve!
		// -------------------------------------

		$updates = array();

		foreach($entry_ids as $entry_id)
		{
			$updates[] = array(
				'entry_id'	=> $entry_id,
				'status'	=> 'open'
			);
		}



		ee()->db->update_batch(
			$this->model('form')->table_name($form_id),
			$updates,
			'entry_id'
		);

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' => TRUE
			));
			exit();
		}
		else
		{
			$method = ee()->input->get_post('return_method');

			$method = ($method AND is_callable(array($this, $method))) ?
							$method :
							'moderate_entries';

			ee()->functions->redirect($this->mcp_link(array(
				'method'	=> $method,
				'form_id'	=> $form_id,
				'msg'		=> 'entries_approved'
			)));
		}
	}
	//END approve_entry


	// --------------------------------------------------------------------

	/**
	 * get/post form_id
	 *
	 * gets and validates the current form_id possibly passed
	 *
	 * @access	private
	 * @param	bool	validate form_id
	 * @return	mixed 	integer of passed in form_id or bool false
	 */

	private function get_post_form_id($validate = TRUE)
	{
		$form_id = $this->get_post_or_zero('form_id');

		if ($form_id == 0 OR
			($validate AND
			 ! $this->model('form')->valid_id($form_id))
		)
		{
			return FALSE;
		}

		return $form_id;
	}
	//ENd get_post_form_id


	// --------------------------------------------------------------------

	/**
	 * get/post entry_ids
	 *
	 * gets and validates the current entry possibly passed
	 *
	 * @access	private
	 * @return	mixed 	array of passed in entry_ids or bool false
	 */

	private function get_post_entry_ids()
	{
		$entry_ids = ee()->input->get_post('entry_ids');

		if ( ! is_array($entry_ids) AND
			 ! $this->is_positive_intlike($entry_ids))
		{
			return FALSE;
		}

		if ( ! is_array($entry_ids))
		{
			$entry_ids = array($entry_ids);
		}

		//clean and validate each as int
		$entry_ids = array_filter($entry_ids, array($this, 'is_positive_intlike'));

		if (empty($entry_ids))
		{
			return FALSE;
		}

		return $entry_ids;
	}
	//END get_post_entry_ids


	// --------------------------------------------------------------------

	/**
	 * delete_confirm_entries
	 *
	 * accepts ajax call to delete entry
	 * or can be called via another view function on post
	 *
	 * @access	public
	 * @param	int	form id
	 * @param	mixed	array or int of entry ids to delete
	 * @return	string
	 */

	public function delete_confirm_entries($form_id = 0, $entry_ids = array())
	{
		// -------------------------------------
		//	ajax requests should be doing front
		//  end delete confirm. This also handles
		//	the ajax errors properly
		// -------------------------------------

		if ( $this->is_ajax_request())
		{
			return $this->delete_entries();
		}

		// -------------------------------------
		//	form id?
		// -------------------------------------

		if ( ! $form_id OR $form_id <= 0)
		{
			$form_id = $this->get_post_form_id();
		}

		if ( ! $form_id)
		{
			$this->show_error(lang('invalid_form_id'));
		}

		// -------------------------------------
		//	entry ids?
		// -------------------------------------

		if ( ! $entry_ids OR empty($entry_ids) )
		{
			$entry_ids = $this->get_post_entry_ids();
		}

		//check
		if ( ! $entry_ids)
		{
			$this->show_error(lang('invalid_entry_id'));
		}

		// -------------------------------------
		//	return method?
		// -------------------------------------

		$return_method = ee()->input->get_post('return_method');

		$return_method = ($return_method AND
						  is_callable(array($this, $return_method))) ?
							$return_method :
							'entries';

		// -------------------------------------
		//	confirmation page
		// -------------------------------------

		return $this->delete_confirm(
			'delete_entries',
			array(
				'form_id'		=> $form_id,
				'entry_ids'	=> $entry_ids,
				'return_method' => $return_method
			),
			'confirm_delete_entries'
		);
	}
	//END delete_confirm_entries


	// --------------------------------------------------------------------

	/**
	 * delete entries
	 *
	 * accepts ajax call to delete entry
	 * or can be called via another view function on post
	 *
	 * @access	public
	 * @param	int	form id
	 * @param	mixed	array or int of entry ids to delete
	 * @return	string
	 */

	public function delete_entries($form_id = 0, $entry_ids = array())
	{
		// -------------------------------------
		//	valid form id?
		// -------------------------------------

		if ( ! $this->is_positive_intlike($form_id))
		{
			$form_id = $this->get_post_form_id();
		}

		if ( ! $form_id)
		{
			$this->lib('Utils')->full_stop(lang('invalid_form_id'));
		}

		// -------------------------------------
		//	entry ids?
		// -------------------------------------

		if ( ! $entry_ids OR empty($entry_ids) )
		{
			$entry_ids = $this->get_post_entry_ids();
		}

		//check
		if ( ! $entry_ids)
		{
			$this->lib('Utils')->full_stop(lang('invalid_entry_id'));
		}

		$success = $this->lib('Forms')->delete_entries($form_id, $entry_ids);

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' => $success
			));
		}
		else
		{
			$method = ee()->input->get_post('return_method');

			$method = ($method AND is_callable(array($this, $method))) ?
							$method : 'entries';

			ee()->functions->redirect($this->mcp_link(array(
				'method'	=> $method,
				'form_id'	=> $form_id,
				'msg'		=> 'entries_deleted'
			)));
		}
	}
	//END delete_entries


	// --------------------------------------------------------------------

	/**
	 * Confirm Delete Fields
	 *
	 * @access public
	 * @return html
	 */

	public function delete_confirm_fields()
	{
		//the following fields will be deleted
		//the following forms will be affected
		//they contain the forms..

		$field_ids = ee()->input->get_post('field_id', TRUE);

		if ( ! is_array($field_ids) AND
			 ! $this->is_positive_intlike($field_ids) )
		{
			$this->lib('Utils')->full_stop(lang('no_field_ids_submitted'));
		}

		//already checked for numeric :p
		if ( ! is_array($field_ids))
		{
			$field_ids = array($field_ids);
		}

		$delete_field_confirmation = '';

		$clean_field_ids = array();

		foreach ($field_ids as $field_id)
		{
			if ($this->is_positive_intlike($field_id))
			{
				$clean_field_ids[] = $field_id;
			}
		}

		if (empty($clean_field_ids))
		{
			$this->lib('Utils')->full_stop(lang('no_field_ids_submitted'));
		}

		// -------------------------------------
		//	build a list of forms affected by fields
		// -------------------------------------

		ee()->db->where_in('field_id', $clean_field_ids);

		$all_field_data			= ee()->db->get('freeform_fields');

		$delete_field_confirmation	= lang('delete_field_confirmation');

		$extra_form_data			= '';

		foreach ($all_field_data->result_array() as $row)
		{
			//this doesn't get field data, so we had to above;
			$field_form_data = $this->model('form')->forms_with_field_id($row['field_id']);

			// -------------------------------------
			//	get each form affected by each field listed
			//	and show the user what forms will be affected
			// -------------------------------------

			if ( $field_form_data !== FALSE )
			{
				$freeform_affected = array();

				foreach ($field_form_data as $form_id => $form_data)
				{
					$freeform_affected[] = $form_data['form_label'];
				}

				$extra_form_data .= $this->view(
					'error_list.html',
					array(
						'field_label'		=> $row['field_label'],
						'freeform_affected'	=> $freeform_affected
					),
					true
				);
			}
		}

		//if we have anything, add some extra warnings
		if ($extra_form_data != '')
		{
			$delete_field_confirmation .=	'<p>' .
												lang('freeform_will_lose_data') .
											'</p>' .
											$extra_form_data;
		}

		return $this->delete_confirm(
			'delete_fields',
			array('field_id' => $clean_field_ids),
			$delete_field_confirmation,
			'delete',
			FALSE
		);
	}
	//END delete_confirm_fields


	// --------------------------------------------------------------------

	/**
	 * utilities
	 *
	 * @access	public
	 * @return	string
	 */

	public function utilities($message = '')
	{
		$this->prep_message($message);

		//--------------------------------------
		//  Counts
		//--------------------------------------

		$this->cached_vars['counts']	= $this->lib('Migration')
											->get_collection_counts();

		//--------------------------------------
		//  File upload field installed?
		//--------------------------------------

		$file_upload_installed			= $this->lib('Migration')
											->get_field_type_installed('file_upload');

		// -------------------------------------
		//	build form fields
		// -------------------------------------

		$sections		= array();
		$main_section	= array();

		foreach ($menu_items as $menu_item)
		{
			$member_choices = array();
			$group_yes = array();

			foreach ($member_groups as $group_id => $group_title)
			{
				$member_choices[$menu_item . '_' . $group_id] = $group_title;

				if (isset($permissions[$menu_item]['groups'][$group_id]) &&
					$permissions[$menu_item]['groups'][$group_id] == 'y')
				{
					$group_yes[] = $group_id;
				}
			}

			$fields		= array(
				$menu_item . '_allow_type' => array(
					'type'		=> 'select',
					'choices'	=> array(
						'allow_all' => lang('allow_all'),
						'deny_all'	=> lang('deny_all'),
						'by_group'	=> lang('by_group')
					),
					'value'		=> '',
					'attrs'		=> '',
					'required'	=> false
				),
			);

			// --------------------------------------------
			//  Set the row now
			// --------------------------------------------

			$main_section[$menu_item] = array(
				'wide'		=> false,
				'title'		=> lang($menu_item),
				'fields'	=> $fields
			);
		}

		$sections[]	= $main_section;

		//--------------------------------------
		//  Load page
		//--------------------------------------

		$this->cached_vars['sections']	= $sections;

		// -------------------------------------
		//	other vars
		// -------------------------------------

		$this->cached_vars['form_uri']	= $this->mcp_link(array(
			'method'	=> 'migrate_collections'
		));

		// Final view variables we need to render the form
		$this->cached_vars += array(
			'base_url'				=> $this->mcp_link(array(
				'method' => 'migrate_collections'
			)),
			'cp_page_title'			=> lang('utilities'),
			'table_head'			=> lang('utilities'),
			'save_btn_text'			=> 'save_permissions',
			'save_btn_text_working'	=> 'saving_permissions',
			'header_note'			=> lang('permissions_description'),
		);

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		return $this->mcp_view(array(
			'file'		=> 'form',
			'highlight'	=> 'utilities',
			'show_message'	=> false,
			'pkg_js'	=> array('utilities_cp.min'),
			'pkg_css'	=> array('mcp_defaults'),
			'crumbs'	=> array(
				array(lang('permissions'))
			)
		));
	}
	//	End utilities


	// --------------------------------------------------------------------

	/**
	 * Confirm Uninstall Fieldtypes
	 *
	 * @access public
	 * @return html
	 */

	public function uninstall_confirm_fieldtype($name = '')
	{
		$name = trim($name);

		if ($name == '')
		{
			ee()->functions->redirect($this->base);
		}

		$items = $this->model('field')
						->key('field_label', 'field_label')
						->get(array('field_type' => $name));

        return $this->uninstall_fieldtype($name);


        //if ($items == FALSE)
		//{
		//	return $this->uninstall_fieldtype($name);
		//}
		//else
		//{
		//	$confirmation = '<p>' . lang('following_fields_converted') . ': <strong>';
        //
		//	$confirmation .= implode(', ', $items) . '</strong></p>';
        //
		//	return $this->delete_confirm(
		//		'uninstall_fieldtype',
		//		array('fieldtype' => $name),
		//		$confirmation,
		//		'uninstall',
		//		FALSE
		//	);
		//}
	}
	//END uninstall_confirm_fieldtype


	// --------------------------------------------------------------------

	/**
	 * Uninstalls fieldtypes
	 *
	 * @access public
	 * @param  string $name fieldtype name to remove
	 * @return void
	 */

	public function uninstall_fieldtype($name = '', $redirect = TRUE)
	{
		if ($name == '')
		{
			$name = ee()->input->get_post('fieldtype', TRUE);
		}

		if ( ! $name)
		{
			$this->lib('Utils')->full_stop(lang('no_fieldtypes_submitted'));
		}

		$success = $this->lib('Fields')->uninstall_fieldtype($name);

		if ( ! $redirect)
		{
			return $success;
		}

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success'	=> $success,
				'message'	=> lang('fieldtype_uninstalled'),
			));
		}
		else
		{
			ee()->functions->redirect($this->mcp_link(array(
				'method'	=> 'fieldtypes',
				'msg'		=> 'fieldtype_uninstalled'
			)));
		}
	}
	//END uninstall_fieldtype


	// --------------------------------------------------------------------

	/**
	 * Delete Fields
	 *
	 * @access public
	 * @return void
	 */

	public function delete_fields()
	{
		// -------------------------------------
		//	safety goggles
		// -------------------------------------
		//
		$field_ids = ee()->input->get_post('field_id', TRUE);

		if ( ! is_array($field_ids) AND
			 ! $this->is_positive_intlike($field_ids) )
		{
			$this->lib('Utils')->full_stop(lang('no_field_ids_submitted'));
		}

		//already checked for numeric :p
		if ( ! is_array($field_ids))
		{
			$field_ids = array($field_ids);
		}

		// -------------------------------------
		//	delete fields
		// -------------------------------------

		$this->lib('Fields')->delete_field($field_ids);

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' => TRUE
			));
		}
		else
		{
			ee()->functions->redirect($this->mcp_link(array(
				'method'	=> 'fields',
				'msg'		=> 'fields_deleted'
			)));
		}
	}
	//END delete_fields


	// --------------------------------------------------------------------

	/**
	 * Confirm deletion of notifications
	 *
	 * accepts ajax call to delete notification
	 *
	 * @access	public
	 * @param	int	form id
	 * @param	mixed	array or int of notification ids to delete
	 * @return	string
	 */

	public function delete_confirm_notification($notification_id = 0)
	{
		// -------------------------------------
		//	ajax requests should be doing front
		//  end delete confirm. This also handles
		//	the ajax errors properly
		// -------------------------------------

		if ( $this->is_ajax_request())
		{
			return $this->delete_notification();
		}

		// -------------------------------------
		//	entry ids?
		// -------------------------------------

		if ( ! is_array($notification_id) AND
			 ! $this->is_positive_intlike($notification_id))
		{
			$notification_id = ee()->input->get_post('notification_id');
		}

		if ( ! is_array($notification_id) AND
			 ! $this->is_positive_intlike($notification_id))
		{
			$this->lib('Utils')->full_stop(lang('invalid_notification_id'));
		}

		if ( is_array($notification_id))
		{
			$notification_id = array_filter(
				$notification_id,
				array($this, 'is_positive_intlike')
			);
		}
		else
		{
			$notification_id = array($notification_id);
		}

		// -------------------------------------
		//	confirmation page
		// -------------------------------------

		return $this->delete_confirm(
			'delete_notification',
			array(
				'notification_id'	=> $notification_id,
				'return_method'		=> 'notifications'
			),
			'confirm_delete_notification'
		);
	}
	//END delete_confirm_notification


	// --------------------------------------------------------------------

	/**
	 * Delete Notifications
	 *
	 * @access	public
	 * @param	integer	$notification_id	notification
	 * @return	null
	 */

	public function delete_notification($notification_id = 0)
	{
		// -------------------------------------
		//	entry ids?
		// -------------------------------------

		if ( ! is_array($notification_id) AND
			 ! $this->is_positive_intlike($notification_id))
		{
			$notification_id = ee()->input->get_post('notification_id');
		}

		if ( ! is_array($notification_id) AND
			 ! $this->is_positive_intlike($notification_id))
		{
			$this->lib('Utils')->full_stop(lang('invalid_notification_id'));
		}

		if ( is_array($notification_id))
		{
			$notification_id = array_filter(
				$notification_id,
				array($this, 'is_positive_intlike')
			);
		}
		else
		{
			$notification_id = array($notification_id);
		}



		$success = $this->model('notification')
						->where_in('notification_id', $notification_id)
						->delete();

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' => $success
			));
		}
		else
		{
			$method = ee()->input->get_post('return_method');

			$method = ($method AND is_callable(array($this, $method))) ?
							$method : 'notifications';

			ee()->functions->redirect($this->mcp_link(array(
				'method'	=> $method,
				'msg'		=> 'delete_notification_success'
			)));
		}

	}
	//END delete_notification

	

	// --------------------------------------------------------------------

	/**
	 * save_field
	 *
	 * @access	public
	 * @return	void (redirect)
	 */

	public function save_field()
	{
		// -------------------------------------
		//	field ID? we must be editing
		// -------------------------------------

		$field_id		= $this->get_post_or_zero('field_id');

		$update		= ($field_id != 0);

		// -------------------------------------
		//	yes or no items (all default yes)
		// -------------------------------------

		$y_or_n = array('submissions_page', 'moderation_page', 'composer_use');

		foreach ($y_or_n as $item)
		{
			//set as local var
			$$item = $this->check_no(ee()->input->get_post($item)) ? 'n' : 'y';
		}

		// -------------------------------------
		//	field instance
		// -------------------------------------

		$field_type = ee()->input->get_post('field_type', TRUE);



		$available_fieldtypes = $this->lib('Fields')->get_available_fieldtypes();

		//get the update with previous settings if this is an edit
		if ($update)
		{
			$field = $this->model('field')
					->where('field_id', $field_id)
					->where('field_type', $field_type)
					->count();

			//make sure that we have the correct type just in case they
			//are changing type like hooligans
			if ($field)
			{
				$fieldsInstance = $this->lib('Fields')->get_field_instance($field_id);
				$field_instance =& $fieldsInstance;
			}
			else
			{
				$fieldsInstance = $this->lib('Fields')->get_fieldtype_instance($field_type);
				$field_instance =& $fieldsInstance;
			}
		}
		else
		{
			$fieldsInstance = $this->lib('Fields')->get_fieldtype_instance($field_type);
			$field_instance =& $fieldsInstance;
		}

		// -------------------------------------
		//	error on empty items or bad data
		//	(doing this via ajax in the form as well)
		// -------------------------------------

		$errors = array();

		// -------------------------------------
		//	field name
		// -------------------------------------

		$field_name = ee()->input->get_post('field_name', TRUE);

		//if the field label is blank, make one for them
		//we really dont want to do this, but here we are
		if ( ! $field_name OR ! trim($field_name))
		{
			$errors['field_name'] = lang('field_name_required');
		}
		else
		{
			$field_name = strtolower(trim($field_name));

			if ( in_array($field_name, $this->model('Data')->prohibited_names ) )
			{
				$errors['field_name'] = str_replace(
					'%name%',
					$field_name,
					lang('freeform_reserved_field_name')
				);
			}

			//if the field_name they submitted isn't like how a URL title may be
			//also, cannot be numeric
			if (preg_match('/[^a-z0-9\_\-]/i', $field_name) OR
				is_numeric($field_name))
			{
				$errors['field_name'] = lang('field_name_can_only_contain');
			}

			//get dupe from field names
			$f_query = ee()->db->select('field_name, field_id')->get_where(
				'freeform_fields',
				 array('field_name' => $field_name)
			);

			//if we are updating, we don't want to error on the same field id
			if ($f_query->num_rows() > 0 AND
				! ($update AND $f_query->row('field_id') == $field_id))
			{
				$errors['field_name'] = str_replace(
					'%name%',
					$field_name,
					lang('field_name_exists')
				);
			}
		}

		// -------------------------------------
		//	field label
		// -------------------------------------

		$field_label = ee()->input->get_post('field_label', TRUE);

		if ( ! $field_label OR ! trim($field_label) )
		{
			$errors['field_label'] = lang('field_label_required');
		}

		// -------------------------------------
		//	field type
		// -------------------------------------

		if ( ! $field_type OR ! array_key_exists($field_type, $available_fieldtypes))
		{
			$errors['field_type'] = lang('invalid_fieldtype');
		}

		// -------------------------------------
		//	field settings errors?
		// -------------------------------------

		$field_settings_validate = $field_instance->validate_settings();

		if ( $field_settings_validate !== TRUE)
		{
			if (is_array($field_settings_validate))
			{
				$errors['field_settings'] = $field_settings_validate;
			}
			else if ( ! empty($field_instance->errors))
			{
				$errors['field_settings'] = $field_instance->errors;
			}
			else
			{
				$errors['field_settings'] = lang('field_settings_error');
			}
		}

		// -------------------------------------
		//	errors? For shame :(
		// -------------------------------------

		if ( ! empty($errors))
		{
			return $this->lib('Utils')->full_stop($errors);
		}

		if ($this->check_yes(ee()->input->get_post('validate_only')) AND
			$this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success'	=> TRUE
			));
		}

		// -------------------------------------
		//	insert data
		// -------------------------------------

		$data		= array(
			'field_name'		=> strip_tags($field_name),
			'field_label'		=> strip_tags($field_label),
			'field_type'		=> $field_type,
			'edit_date'		=> '0', //overridden if update
			'field_description' => strip_tags(ee()->input->get_post('field_description', TRUE)),
			'submissions_page'	=> $submissions_page,
			'moderation_page'	=> $moderation_page,
			'composer_use'		=> $composer_use,
			'settings'			=> json_encode($field_instance->save_settings())
		);

		if ($update)
		{
			$this->model('field')->update(
				array_merge(
					$data,
					array(
						'edit_date' => ee()->localize->now
					)
				),
				array('field_id' => $field_id)
			);
		}
		else
		{
			$field_id = $this->model('field')->insert(
				array_merge(
					$data,
					array(
						'author_id'		=> ee()->session->userdata('member_id'),
						'entry_date'	=> ee()->localize->now,
						'site_id'		=> ee()->config->item('site_id')
					)
				)
			);
		}

		$field_instance->field_id = $field_id;

		$field_instance->post_save_settings();

		$field_in_forms = array();

		if ($update)
		{
			$field_in_forms = $this->model('form')->forms_with_field_id($field_id);

			if ($field_in_forms)
			{
				$field_in_forms = array_keys($field_in_forms);
			}
			else
			{
				$field_in_forms = array();
			}
		}

		$form_ids = ee()->input->get_post('form_ids');

		if (!$form_ids) {
			$form_ids = array();
		}

		if ( ! (empty($form_ids) AND empty($field_in_forms)))
		{
			$remove = array_unique(array_diff($field_in_forms, $form_ids));
			$add	= array_unique(array_diff($form_ids, $field_in_forms));

			foreach ($add as $add_id)
			{
				$this->lib('Forms')->add_field_to_form($add_id, $field_id);
			}

			foreach ($remove as $remove_id)
			{
				$this->lib('Forms')->remove_field_from_form($remove_id, $field_id);
			}
		}

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$return = array(
				'success'	=> TRUE,
				'field_id'	=> $field_id,
			);

			if ($this->check_yes(ee()->input->get_post('include_field_data')))
			{
				$return['composerFieldData'] = $this->composer_field_data($field_id, NULL, TRUE);
			}

			$this->send_ajax_response($return);
		}
		else
		{
			//redirect back to fields on success
			ee()->functions->redirect($this->mcp_link(array(
				'method'	=> 'fields',
				'msg'		=> 'edit_field_success'
			)));
		}
	}
	//END save_field

	

	// --------------------------------------------------------------------

	/**
	 * save_notification
	 *
	 * @access	public
	 * @return	null (redirect)
	 */

	public function save_notification()
	{
		// -------------------------------------
		//	notification ID? we must be editing
		// -------------------------------------

		$notification_id		= $this->get_post_or_zero('notification_id');

		$update				= ($notification_id != 0);

		// -------------------------------------
		//	yes or no items (default yes)
		// -------------------------------------

		$y_or_n = array('wordwrap');

		foreach ($y_or_n as $item)
		{
			//set as local var
			$$item = $this->check_no(ee()->input->get_post($item)) ? 'n' : 'y';
		}

		// -------------------------------------
		//	yes or no items (default no)
		// -------------------------------------

		$n_or_y = array('allow_html', 'include_attachments');

		foreach ($n_or_y as $item)
		{
			//set as local var
			$$item = $this->check_yes(ee()->input->get_post($item)) ? 'y' : 'n';
		}

		// -------------------------------------
		//	error on empty items or bad data
		//	(doing this via ajax in the form as well)
		// -------------------------------------

		$errors = array();

		// -------------------------------------
		//	notification name
		// -------------------------------------

		$notification_name = ee()->input->get_post('notification_name', TRUE);

		//if the field label is blank, make one for them
		//we really dont want to do this, but here we are
		if ( ! $notification_name OR ! trim($notification_name))
		{
			$errors['notification_name'] = lang('notification_name_required');
		}
		else
		{
			$notification_name = strtolower(trim($notification_name));

			if ( in_array($notification_name, $this->model('Data')->prohibited_names ) )
			{
				$errors['notification_name'] = str_replace(
					'%name%',
					$notification_name,
					lang('reserved_notification_name')
				);
			}

			//if the field_name they submitted isn't like how a URL title may be
			//also, cannot be numeric
			if (preg_match('/[^a-z0-9\_\-]/i', $notification_name) OR
				is_numeric($notification_name))
			{
				$errors['notification_name'] = lang('notification_name_can_only_contain');
			}

			//get dupe from field names
			ee()->db->select('notification_name, notification_id');

			$f_query = ee()->db->get_where(
				'freeform_notification_templates',
				array(
					'notification_name' => $notification_name
				)
			);

			//if we are updating, we don't want to error on the same field id
			if ($f_query->num_rows() > 0 AND
				! ($update AND $f_query->row('notification_id') == $notification_id))
			{
				$errors['notification_name'] = str_replace(
					'%name%',
					$notification_name,
					lang('notification_name_exists')
				);
			}
		}

		// -------------------------------------
		//	notification label
		// -------------------------------------

		$notification_label = ee()->input->get_post('notification_label', TRUE);

		if ( ! $notification_label OR ! trim($notification_label) )
		{
			$errors['notification_label'] = lang('notification_label_required');
		}

		ee()->load->helper('email');

		// -------------------------------------
		//	notification email
		// -------------------------------------

		$from_email = ee()->input->get_post('from_email', TRUE);

		if ($from_email AND trim($from_email) != '')
		{
			$from_email = trim($from_email);

			//allow tags
			if ( ! preg_match('/' . LD . '([a-zA-Z0-9\_]+)' . RD . '/is', $from_email))
			{
				if ( ! valid_email($from_email))
				{
					$errors['from_email'] = str_replace(
						'%email%',
						$from_email,
						lang('non_valid_email')
					);
				}
			}
		}

		// -------------------------------------
		//	from name
		// -------------------------------------

		$from_name = ee()->input->get_post('from_name', TRUE);

		if ( ! $from_name OR ! trim($from_name) )
		{
			//$errors['from_name'] = lang('from_name_required');
		}

		// -------------------------------------
		//	reply to email
		// -------------------------------------

		$reply_to_email = ee()->input->get_post('reply_to_email', TRUE);

		if ($reply_to_email AND trim($reply_to_email) != '')
		{
			$reply_to_email = trim($reply_to_email);

			//allow tags
			if ( ! preg_match('/' . LD . '([a-zA-Z0-9\_]+)' . RD . '/is', $reply_to_email))
			{
				if ( ! valid_email($reply_to_email))
				{
					$errors['reply_to_email'] = str_replace(
						'%email%',
						$reply_to_email,
						lang('non_valid_email')
					);
				}
			}
		}
		else
		{
			$reply_to_email = '';
		}

		// -------------------------------------
		//	email subject
		// -------------------------------------

		$email_subject = ee()->input->get_post('email_subject', TRUE);

		if ( ! $email_subject OR ! trim($email_subject) )
		{
			$errors['email_subject'] = lang('email_subject_required');
		}

		// -------------------------------------
		//	errors? For shame :(
		// -------------------------------------

		if ( ! empty($errors))
		{
			return $this->lib('Utils')->full_stop($errors);
		}
		//ajax checking?
		else if ($this->check_yes(ee()->input->get_post('validate_only')))
		{
			return $this->send_ajax_response(array(
				'success'				=> TRUE
			));
		}

		// -------------------------------------
		//	insert data
		// -------------------------------------

		$data		= array(
			'notification_name'			=> strip_tags($notification_name),
			'notification_label'		=> strip_tags($notification_label),
			'notification_description'	=> strip_tags(ee()->input->get_post('notification_description', TRUE)),
			'wordwrap'					=> $wordwrap,
			'allow_html'				=> $allow_html,
			'from_name'					=> $from_name,
			'from_email'				=> $from_email,
			'reply_to_email'			=> $reply_to_email,
			'email_subject'				=> strip_tags($email_subject),
			'template_data'				=> ee()->input->get_post('template_data'),
			'include_attachments'		=> $include_attachments
		);



		if ($update)
		{
			$this->model('notification')->update(
				$data,
				array('notification_id' => $notification_id)
			);
		}
		else
		{
			$notification_id = $this->model('notification')->insert(
				array_merge(
					$data,
					array(
						'site_id' => ee()->config->item('site_id')
					)
				)
			);
		}

		// -------------------------------------
		//	ajax?
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success'				=> TRUE,
				'notification_id'		=> $notification_id
			));
		}
		else
		{
			//redirect back to fields on success
			ee()->functions->redirect($this->mcp_link(array(
				'method'	=> 'notifications',
				'msg'		=> 'edit_notification_success'
			)));
		}
	}

	//END save_notification

	


	// --------------------------------------------------------------------

	/**
	 * Sets the menu highlight and assists with permissions (Freeform Pro)
	 *
	 * @access	protected
	 * @param	string		$menu_item	The menu item to highlight
	 */

	protected function set_highlight($menu_item = 'module_forms')
	{
		
		$this->cached_vars['module_menu_highlight'] = $menu_item;
	}
	//END set_highlight


	// --------------------------------------------------------------------

	/**
	 * save_preferences
	 *
	 * @access	public
	 * @return	null (redirect)
	 */

	public function save_preferences()
	{
		//defaults are in data.freeform.php
		$prefs = array();

		$all_prefs = array_merge(
			$this->model('preference')->default_preferences,
			$this->model('preference')->default_global_preferences
		);

		//check post input for all existing prefs and default if not present
		foreach($all_prefs as $pref_name => $data)
		{
			$input					= ee()->input->get_post($pref_name, TRUE);
			//default
			$output					= $data['value'];

			//int
			if ($data['validate'] == 'number' AND
				$this->is_positive_intlike($input, -1))
			{
				$output = $input;
			}
			//yes or no
			elseif ($data['type'] == 'yes_no' AND
					in_array(trim($input), array('y', 'n'), TRUE))
			{
				$output = trim($input);
			}
			//list of items
			//this seems nutty, but this serializes the list of items
			elseif ($data['validate'] == 'list')
			{
				//lotses?
				if (is_array($input))
				{
					$temp_input = array();

					foreach ($input as $key => $value)
					{
						if (trim($value) !== '')
						{
							$temp_input[] = trim($value);
						}
					}

					$output = json_encode($temp_input);
				}
				//just one :/
				else if (trim($input) !== '')
				{
					$output = json_encode(array(trim($input)));
				}
			}
			//text areas
			elseif ($data['type'] == 'text' OR
					$data['type'] == 'textarea' )
			{
				$output = trim($input);
			}


			$prefs[$pref_name]	= $output;
		}

		//send all prefs to DB
		$this->model('preference')->set_preferences($prefs);

		// ----------------------------------
		//  Redirect to Homepage with Message
		// ----------------------------------

		ee('CP/Alert')
			->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('preferences_updated'))
			->defer();

		ee()->functions->redirect($this->mcp_link('preferences'));
	}
	//END save_preferences


	// --------------------------------------------------------------------

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
	 * Export Entries
	 *
	 * Calls entries with proper flags to cue export
	 *
	 * @access public
	 * @return mixed	forces a download of the exported items or error
	 */

	public function export_entries()
	{
		$moderate = (ee()->input->get_post('moderate') == 'true');

		return $this->entries(NULL, $moderate, TRUE);
	}
	//END export_entries


	// --------------------------------------------------------------------

	/**
	 * get_standard_column_names
	 *
	 * gets the standard column names and replaces author_id with author
	 *
	 * @access	private
	 * @return	null
	 */

	private function get_standard_column_names()
	{
		$standard_columns	= array_keys(
			$this->model('form')->default_form_table_columns
		);

		array_splice(
			$standard_columns,
			array_search('author_id', $standard_columns),
			1,
			'author'
		);

		return $standard_columns;
	}
	//END get_standard_column_names


	// --------------------------------------------------------------------

	/**
	 * load_fancybox
	 *
	 * loads fancybox jquery UI plugin and its needed css
	 *
	 * @access	protected
	 * @return	null
	 */

	protected function load_fancybox()
	{
		$e = new Exception;

		//so currently the fancybox setup inlucded in EE doesn't get built
		//automaticly and requires relying on the current CP theme.
		//Dislike. Inlcuding our own version instead.
		//Seems fancy box has also been removed from some later versions
		//of EE, so instinct here was correct.
		$css_link = $this->theme_url . 'fancybox/jquery.fancybox-1.3.4.css';
		$js_link = $this->theme_url . 'fancybox/jquery.fancybox-1.3.4.pack.js';

		ee()->cp->add_to_head('<link href="' . $css_link . '" type="text/css" rel="stylesheet" media="screen" />');
		ee()->cp->add_to_foot('<script src="' . $js_link . '" type="text/javascript"></script>');
	}
	//END load_fancybox


	// --------------------------------------------------------------------

	/**
	 * Visible Columns
	 *
	 * @access	protected
	 * @param   $possible_columns possible columns
	 * @return	array array of visible columns
	 */

	protected function visible_columns($standard_columns = array(),
										$possible_columns = array())
	{
		// -------------------------------------
		//	get column settings
		// -------------------------------------

		$column_settings	= array();



		$field_layout_prefs = $this->model('preference')->preference('field_layout_prefs');
		$member_id			= ee()->session->userdata('member_id');
		$group_id			= ee()->session->userdata('group_id');
		$f_prefix			= $this->model('form')->form_field_prefix;

		//¿existe? Member? Group? all?
		if ($field_layout_prefs)
		{
			//$field_layout_prefs = json_decode($field_layout_prefs, TRUE);

			$entry_layout_prefs = (
				isset($field_layout_prefs['entry_layout_prefs']) ?
					$field_layout_prefs['entry_layout_prefs'] :
					FALSE
			);

			if ($entry_layout_prefs)
			{
				if (isset($entry_layout_prefs['member'][$member_id]))
				{
					$column_settings = $entry_layout_prefs['member'][$member_id];
				}
				else if (isset($entry_layout_prefs['all']))
				{
					$column_settings = $entry_layout_prefs['all'];
				}
				else if (isset($entry_layout_prefs['group'][$group_id]))
				{
					$column_settings = $entry_layout_prefs['group'][$group_id];
				}
			}
		}

		//if a column is missing, we don't want to error
		//and if its newer than the settings, show it by default
		//settings are also in order of appearence here.

		//we also store the field ids without the prefix
		//in case someone changed it. That would probably
		//hose everything, but who knows? ;)
		if ( ! empty($column_settings))
		{
			$to_sort = array();

			//we are going over possible instead of settings in case something
			//is new or an old column is missing
			foreach ($possible_columns as $cid)
			{
				//if these are new, put them at the end
				if ( ! in_array($cid, $column_settings['visible']) AND
					! in_array($cid, $column_settings['hidden'])
				)
				{
					$to_sort[$cid] = $cid;
				}
			}

			//now we want columns from the settings order to go first
			//this way stuff thats not been removed gets to keep settings
			foreach ($column_settings['visible'] as $ecid)
			{
				if (in_array($ecid, $possible_columns))
				{
					//since we are getting our real results now
					//we can add the prefixes
					if ( ! in_array($ecid, $standard_columns) )
					{
						$ecid = $f_prefix . $ecid;
					}

					$visible_columns[] = $ecid;
				}
			}

			//and if we have anything left over (new fields probably)
			//its at the end
			if ( ! empty($to_sort))
			{
				foreach ($to_sort as $tsid)
				{
					//since we are getting our real results now
					//we can add the prefixes
					if ( ! in_array($tsid, $standard_columns) )
					{
						$tsid = $f_prefix . $tsid;
					}

					$visible_columns[] = $tsid;
				}
			}
		}
		//if we don't have any settings, just toss it all in in order
		else
		{
			foreach ($possible_columns as $pcid)
			{
				if ( ! in_array($pcid, $standard_columns) )
				{
					$pcid = $f_prefix . $pcid;
				}

				$visible_columns[] = $pcid;
			}

			//in theory it should always be there if prefs are empty ...

			$default_hide = array('site_id', 'entry_id', 'complete');

			foreach ($default_hide as $hide_me_seymour)
			{
				if (in_array($hide_me_seymour, $visible_columns))
				{
					unset(
						$visible_columns[
							array_search(
								$hide_me_seymour,
								$visible_columns
							)
						]
					);
				}
			}

			//fix keys, but preserve order
			$visible_columns = array_merge(array(), $visible_columns);
		}

		return $visible_columns;
	}
	//END visible_columns


	// --------------------------------------------------------------------

	/**
	 * Format CP date
	 *
	 * @access	public
	 * @param	mixed	$date	unix time
	 * @return	string			unit time formatted to cp date formatting pref
	 */

	public function format_cp_date($date)
	{
		return $this->lib('Utils')->format_cp_date($date);
	}
	//END format_cp_date


	// --------------------------------------------------------------------

	/**
	 * Send AJAX response
	 *
	 * Outputs and exit either an HTML string or a
	 * JSON array with the Profile disabled and correct
	 * headers sent.
	 *
	 * @access	public
	 * @param	string|array	String is sent as HTML, Array is sent as JSON
	 * @param	bool			Is this an error message?
	 * @param	bool			bust cache for JSON?
	 * @return	void
	 */

	public function send_ajax_response($msg, $error = FALSE, $cache_bust = TRUE)
	{
		parent::send_ajax_response($msg, $error, $cache_bust);
	}
	//END send_ajax_response


		// --------------------------------------------------------------------

	/**
	 * MCP view with options
	 *
	 * @access	protected
	 * @param	array  $options input options for view
	 * @return	string			html output
	 */

	protected function mcp_view($options = array())
	{
		$return = parent::mcp_view($options);

		$return['body'] = str_replace(
			'<form ',
			'<form autocomplete="off" ',
			$return['body']
		);

		$modal = ee('View')
					->make('ee:_shared/modal')
					->render(array(
						'name'		=> 'freeform-error-modal',
						'contents'	=> $this->view('shared_errors')
					));

		ee('CP/Modal')->addModal('freeform-error-modal', $modal);

		return $return;
	}
	//END mcp_view
}
// END CLASS Freeform
