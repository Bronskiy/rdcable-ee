<?php

use Solspace\Addons\Freeform\Library\AddonBuilder;

class Freeform extends AddonBuilder
{
	/**
	 * return data for when the constructor os only called
	 * unused thusfar in this addon.
	 * @var string
	 */
	public $return_data		= '';

	/**
	 * Multipart form?
	 * @var boolean
	 * @see form
	 */
	public $multipart		= FALSE;

	/**
	 * Params array storage for param
	 * @var array
	 * @see param
	 */
	public $params			= array();

	/**
	 * params id for param fetch
	 * @var integer
	 * @see form
	 * @see insert_params
	 */
	public $params_id		= 0;

	/**
	 * Form ID storage
	 * @var integer
	 * @see form_id
	 */
	public $form_id			= 0;

	/**
	 * Test Mode?
	 *
	 * @var boolean
	 * @see do_exist
	 */
	public $test_mode		= FALSE;

	/**
	 * Default Multipage Marker
	 *
	 * @var string
	 * @see form
	 * @see set_form_params
	 */
	public $default_mp_page_marker = 'page';

	/**
	 * Multipage Page Array
	 *
	 * @var array
	 * @see get_mp_page_array
	 */
	public $mp_page_array;

	/**
	 * Prevents rerunning of set_form_params
	 * @var boolean
	 * @see set_form_params
	 */
	public $params_ran = FALSE;

	/**
	 * Form Data
	 * @var array
	 */
	public $form_data;

	/**
	 * Params With Defaults
	 * @var array
	 * @see get_default_params
	 */
	public $params_with_defaults;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct ()
	{
		parent::__construct();

		// -------------------------------------
		//  Module Installed and Up to Date?
		// -------------------------------------

		ee()->load->helper(array('text', 'form', 'url', 'string'));

		//avoids AR collisions
		$this->model('preference')->prefs_with_defaults();
		$this->model('preference')->global_prefs_with_defaults();
		$this->model('preference')->show_all_sites();
	}
	// END __construct()


	// --------------------------------------------------------------------

	/**
	 * Theme Folder URL
	 *
	 * Mainly used for codepack
	 *
	 * @access	public
	 * @return	string	theme folder url with ending slash
	 */

	public function theme_folder_url()
	{
		return $this->theme_url;
	}
	//END theme_folder_url


	// --------------------------------------------------------------------

	/**
	 * Form Info
	 *
	 * @access	public
	 * @return	string parsed tagdata
	 */

	public function form_info()
	{
		$form_ids = $this->form_id(TRUE, FALSE);

		if ($form_ids)
		{
			$this->model('form')->where_in('form_id', $form_ids);
		}

		// -------------------------------------
		//	site ids
		// -------------------------------------

		//if its star, allow all
		if (ee()->TMPL->fetch_param('site_id') !== '*')
		{
			$site_id = $this->parse_numeric_array_param('site_id');

			//if this isn't false, its single or an array
			if ($site_id !== FALSE)
			{
				//no ids? exit
				if (empty($site_id['ids']))
				{
					$this->model('form')->reset();
					return $this->no_results_error();
				}
				//e.g. site_id="not 1"
				else if ($site_id['not'])
				{
					$this->model('form')->where_not_in(
						'site_id',
						$site_id['ids']
					);
				}
				else
				{
					$this->model('form')->where_in(
						'site_id',
						$site_id['ids']
					);
				}
			}
			//default
			else
			{
				$this->model('form')->where(
					'site_id',
					ee()->config->item('site_id')
				);
			}
		}

		$orderByMap = array(
			'name' => 'form_name',
			'label' => 'form_label',
			'id' => 'form_id',
		);

		$orderByParam = ee()->TMPL->fetch_param('orderby', 'label');
		if (!isset($orderByMap[$orderByParam])) {
			$orderBy = 'form_label';
		} else {
			$orderBy = $orderByMap[$orderByParam];
		}

		$sort = strtolower(ee()->TMPL->fetch_param('sort', 'asc'));
		if ($sort !== 'desc') {
			$sort = 'asc';
		}

		$limit = (int)ee()->TMPL->fetch_param('limit', 20);

		// -------------------------------------
		//	form data
		// -------------------------------------

		$form_data =	$this->model('form')
							->select(implode(', ', array(
								'form_id',
								'site_id',
								'form_name',
								'form_label',
								'form_description',
								'author_id',
								'entry_date',
								'edit_date',
							)))
							->order_by($orderBy, $sort)
							->limit($limit)
							->get();

		if ( ! $form_data)
		{
			return	$this->no_results_error(($form_ids) ?
						'invalid_form_id' :
						NULL
					);
		}

		// -------------------------------------
		//	author data
		// -------------------------------------

		$author_ids		= array();
		$author_data	= array();

		foreach ($form_data as $row)
		{
			$author_ids[] = $row['author_id'];
		}

		$a_query = ee()->db->select('member_id, username, screen_name')
							->from('members')
							->where_in('member_id', array_unique($author_ids))
							->get();

		if ($a_query->num_rows() > 0)
		{
			$author_data = $this->prepare_keyed_result(
				$a_query,
				'member_id'
			);
		}

		// -------------------------------------
		//	output
		// -------------------------------------

		$variables = array();



		foreach ($form_data as $row)
		{
			$new_row = array();

			//prefix everything
			foreach ($row as $key => $value)
			{
				$new_row['freeform:' . $key] = $value;
			}

			//we are only counting completed entries
			$new_row['freeform:total_entries']	=	$this->model('entry')
														->id($row['form_id'])
														->where('complete', 'y')
														->count();

			$new_row['freeform:author']			=	(
				isset($author_data[$row['author_id']]) ?
					(
						isset($author_data[$row['author_id']]['screen_name']) ?
							$author_data[$row['author_id']]['screen_name'] :
							$author_data[$row['author_id']]['username']
					) :
					lang('n_a')
			);

			$variables[] = $new_row;
		}

		if ($orderByParam == "total_entries") {
			usort($variables, function($a,$b) use ($sort) {
				$aTotal = (int)$a['freeform:total_entries'];
				$bTotal = (int)$b['freeform:total_entries'];

				if ($aTotal > $bTotal) {
					return $sort == 'asc' ? 1 : -1;
				} else if ($aTotal < $bTotal) {
					return $sort == 'asc' ? -1 : 1;
				}

				return 0;
			});
		}

		$prefixed_tags	= array(
			'count',
			'switch',
			'total_results'
		);

		$tagdata = ee()->TMPL->tagdata;

		$tagdata = $this->tag_prefix_replace(
			'freeform:',
			$prefixed_tags,
			$tagdata
		);

		//this should handle backspacing as well
		$tagdata = ee()->TMPL->parse_variables($tagdata, $variables);

		$tagdata = $this->tag_prefix_replace(
			'freeform:',
			$prefixed_tags,
			$tagdata,
			TRUE
		);

		return $tagdata;
	}
	//END form_info


	// --------------------------------------------------------------------

	/**
	 * Freeform:Entries
	 * {exp:freeform:entries}
	 *
	 * @access	public
	 * @return	string 	tagdata
	 */

	public function entries()
	{
		// -------------------------------------
		//	form id
		// -------------------------------------

		$form_ids = $this->form_id(TRUE, FALSE);

		if ( ! $form_ids)
		{
			return $this->no_results_error('invalid_form_id');
		}

		if ( ! is_array($form_ids))
		{
			$form_ids = array($form_ids);
		}

		// -------------------------------------
		//	start cache for count and result
		// -------------------------------------

		$forms_data	=	$this->model('form')
							->key('form_id')
							->get(array('form_id' => $form_ids));

		$statuses	= array_keys($this->model('preference')->get_form_statuses());

		// -------------------------------------
		//	field order ids
		// -------------------------------------

		$all_field_ids	= array();
		$all_order_ids	= array();

		foreach ($forms_data as $form_data)
		{
			//this should always be true, but NEVER TRUST AN ELF
			if (isset($form_data['field_ids']) AND
				is_array($form_data['field_ids']))
			{
				$all_field_ids = array_merge(
					$all_field_ids,
					$form_data['field_ids']
				);

				$all_order_ids = array_merge(
					$all_order_ids,
					$this->lib('Utils')->pipe_split($form_data['field_order'])
				);
			}
		}

		$all_field_ids = array_unique($all_field_ids);
		$all_order_ids = array_unique($all_order_ids);

		sort($all_field_ids);

		// -------------------------------------
		//	get field data
		// -------------------------------------

		$all_field_data = FALSE;

		if ( ! empty($all_field_ids))
		{
			$all_field_data = $this->model('field')
									->key('field_id')
									->where_in('field_id', $all_field_ids)
									->get();
		}

		$field_data = array();

		if ($all_field_data)
		{
			foreach ($all_field_data as $row)
			{
				$field_data[$row['field_id']] = $row;
			}
		}

		// -------------------------------------
		//	set tables
		// -------------------------------------

		$this->model('entry')->id($form_ids);

		// -------------------------------------
		//	replace CURRENT_USER before we get
		//	started because the minute we don't
		//	someone is going to figure out
		//	a way to need it in site_id=""
		// -------------------------------------

		$this->replace_current_user();

		// -------------------------------------
		//	site ids
		// -------------------------------------

		//if its star, allow all
		if (ee()->TMPL->fetch_param('site_id') !== '*')
		{
			$site_id = $this->parse_numeric_array_param('site_id');

			//if this isn't false, its single or an array
			if ($site_id !== FALSE)
			{
				if (empty($site_id['ids']))
				{
					$this->model('entry')->reset();
					return $this->no_results_error();
				}
				else if ($site_id['not'])
				{
					$this->model('entry')->where_not_in(
						'site_id',
						$site_id['ids']
					);
				}
				else
				{
					$this->model('entry')->where_in(
						'site_id',
						$site_id['ids']
					);
				}
			}
			//default
			else
			{
				$this->model('entry')->where(
					'site_id',
					ee()->config->item('site_id')
				);
			}
		}

		// -------------------------------------
		//	entry ids
		// -------------------------------------

		$entry_id = $this->parse_numeric_array_param('entry_id');

		if ($entry_id !== FALSE)
		{
			if (empty($entry_id['ids']))
			{
				$this->model('entry')->reset();
				return $this->no_results_error();
			}
			else if ($entry_id['not'])
			{
				$this->model('entry')->where_not_in(
					'entry_id',
					$entry_id['ids']
				);
			}
			else
			{
				$this->model('entry')->where_in(
					'entry_id',
					$entry_id['ids']
				);
			}
		}

		// -------------------------------------
		//	author ids
		// -------------------------------------

		$author_id = $this->parse_numeric_array_param('author_id');

		if ($author_id !== FALSE)
		{
			if (empty($author_id['ids']))
			{
				$this->model('entry')->reset();
				return $this->no_results_error();
			}
			else if ($author_id['not'])
			{
				$this->model('entry')->where_not_in(
					'author_id',
					$author_id['ids']
				);
			}
			else
			{
				$this->model('entry')->where_in(
					'author_id',
					$author_id['ids']
				);
			}
		}

		// -------------------------------------
		//	{freeform:all_form_fields}
		// -------------------------------------

		$tagdata = $this->replace_all_form_fields(
			ee()->TMPL->tagdata,
			$field_data,
			$all_order_ids
		);

		// -------------------------------------
		//	get standard columns and labels
		// -------------------------------------

		$standard_columns 	= array_keys(
			$this->model('form')->default_form_table_columns
		);

		$standard_columns[] = 'author';

		$column_labels		= array();

		//keyed labels for the front end
		foreach ($standard_columns as $column_name)
		{
			$column_labels[$column_name] = lang($column_name);
		}

		// -------------------------------------
		//	available fields
		// -------------------------------------

		//this makes the keys and values the same
		$available_fields	= array_combine(
			$standard_columns,
			$standard_columns
		);

		$custom_fields		= array();
		$field_descriptions	= array();

		foreach ($field_data as $field_id => $f_data)
		{
			$fid = $this->model('form')->form_field_prefix . $field_id;

			//field_name => field_id_1, etc
			$available_fields[$f_data['field_name']]	= $fid;
			//field_id_1 => field_id_1, etc
			$available_fields[$fid]						= $fid;
			$custom_fields[] = $f_data['field_name'];

			//labels
			$column_labels[$f_data['field_name']]		= $f_data['field_label'];
			$column_labels[$fid]						= $f_data['field_label'];

			$field_descriptions[
				'freeform:description:' . $f_data['field_name']
			]	= $f_data['field_description'];
		}

		// -------------------------------------
		//	search:field_name="kittens"
		// -------------------------------------

		foreach (ee()->TMPL->tagparams as $key => $value)
		{
			if (substr($key, 0, 7) == 'search:')
			{
				$search_key = substr($key, 7);

				if (isset($available_fields[$search_key]))
				{
					$this->model('entry')->add_search(
						$available_fields[$search_key],
						$value
					);
				}
			}
		}

		// -------------------------------------
		//	date range
		// -------------------------------------

		$date_range			= ee()->TMPL->fetch_param('date_range');
		$date_range_start	= ee()->TMPL->fetch_param('date_range_start');
		$date_range_end		= ee()->TMPL->fetch_param('date_range_end');

		$this->model('entry')->date_where(
			$date_range,
			$date_range_start,
			$date_range_end
		);

		// -------------------------------------
		//	complete
		// -------------------------------------

		$show_incomplete = ee()->TMPL->fetch_param('show_incomplete');

		if ($show_incomplete === 'only')
		{
			$this->model('entry')->where('complete', 'n');
		}
		//default unless show_incomplete="y"
		else if ( ! $this->check_yes($show_incomplete))
		{
			$this->model('entry')->where('complete', 'y');
		}

		// -------------------------------------
		//	status
		// -------------------------------------

		$status = ee()->TMPL->fetch_param('status', 'open');

		if ($status !== 'all')
		{
			//make it an array either way
			$status = array_map('trim', $this->lib('Utils')->pipe_split($status));

			$approved = array_map('strtolower', $statuses);

			$search_status = array();

			//only keep legit ones
			foreach($status as $potential_status)
			{
				if (in_array(strtolower($potential_status), $approved))
				{
					$search_status[] = $potential_status;
				}
			}

			if ( ! empty($search_status))
			{
				$this->model('entry')->where_in(
					'status',
					$search_status
				);
			}
		}

		// -------------------------------------
		//	orderby/sort
		// -------------------------------------

		$sort 		= ee()->TMPL->fetch_param('sort');
		$orderby 	= ee()->TMPL->fetch_param('orderby');

		if ($orderby !== FALSE AND trim($orderby) !== '')
		{
			$orderby = $this->lib('Utils')->pipe_split(strtolower(trim($orderby)));

			array_walk($orderby, 'trim');

			// -------------------------------------
			//	sort
			// -------------------------------------

			if ($sort !== FALSE AND trim($sort) !== '')
			{
				$sort = $this->lib('Utils')->pipe_split(strtolower(trim($sort)));

				array_walk($sort, 'trim');

				//correct sorts
				foreach ($sort as $key => $value)
				{
					if ( ! in_array($value, array('asc', 'desc')))
					{
						$sort[$key] = 'asc';
					}
				}
			}
			else
			{
				$sort = array('asc');
			}

			// -------------------------------------
			//	add sorts and orderbys
			// -------------------------------------

			foreach ($orderby as $key => $value)
			{
				if ($value == 'random')
				{
					$this->model('entry')->order_by('', 'random');
					continue;
				}

				if (isset($available_fields[$value]))
				{
					//if the sort is not set, just use the first
					//really this should teach people to be more specific :p
					$temp_sort = isset($sort[$key]) ? $sort[$key] : $sort[0];

					$this->model('entry')->order_by(
						$available_fields[$value],
						$temp_sort
					);
				}
			}
		}

		//--------------------------------------
		//  pagination start vars
		//--------------------------------------

		$limit				= ee()->TMPL->fetch_param('limit', 50);
		$offset				= ee()->TMPL->fetch_param('offset', 0);
		$row_count			= 0;
		$total_entries		= $this->model('entry')->count(array(), FALSE);
		$current_page		= 0;

		if ($total_entries == 0)
		{
			$this->model('entry')->reset();
			return $this->no_results_error();
		}

		// -------------------------------------
		//	pagination?
		// -------------------------------------

		$prefix = stristr($tagdata, LD . 'freeform:paginate' . RD);

		if ($limit > 0 AND ($total_entries - $offset) > $limit)
		{
			//get pagination info
			$pagination_data = $this->universal_pagination(array(
				'total_results'			=> $total_entries,
				'tagdata'				=> $tagdata,
				'limit'					=> $limit,
				'offset'				=> $offset,
				'uri_string'			=> ee()->uri->uri_string,
				'prefix'				=> 'freeform:',
				'auto_paginate'			=> TRUE
			));

			//if we paginated, sort the data
			if ($pagination_data['paginate'] === TRUE)
			{
				$tagdata		= $pagination_data['tagdata'];
				$current_page 	= $pagination_data['pagination_page'];
			}
		}
		else
		{
			$this->paginate = FALSE;
		}

		$this->model('entry')->limit($limit, $current_page + $offset);

		// -------------------------------------
		//	get data
		// -------------------------------------

		$result_array = $this->model('entry')->get();

		if (empty($result_array))
		{
			$this->model('entry')->reset();
			return $this->no_results_error();
		}

		$output_labels = array();

		//column labels for output
		foreach ($column_labels as $key => $value)
		{
			$output_labels['freeform:label:' . $key] = $value;
		}

		$count				= $row_count;

		$variable_rows		= array();

		$replace_tagdata	= '';

		// -------------------------------------
		//	allow pre_process
		// -------------------------------------

		$entry_ids = array();

		foreach ($result_array as $row)
		{
			if ( ! isset($entry_ids[$row['form_id']]))
			{
				$entry_ids[$row['form_id']] = array();
			}

			$entry_ids[$row['form_id']][] = $row['entry_id'];
		}

		// -------------------------------------
		//	preprocess items
		// -------------------------------------
		//	These are separated by form id so this
		//	is not iterating over each entry id
		//	but rather grouped by form.
		// -------------------------------------

		foreach ($entry_ids as $f_form_id => $f_entry_ids)
		{
			$this->lib('Fields')->apply_field_method(array(
				'method'		=> 'pre_process_entries',
				'form_id'		=> $f_form_id,
				'form_data'		=> $forms_data,
				'entry_id'		=> $f_entry_ids,
				'field_data'	=> $field_data
			));
		}

		// -------------------------------------
		//	output
		// -------------------------------------

		$to_prefix = array(
			'absolute_count',
			'absolute_results',
			'attachment_count',
			'author_id',
			'author',
			'complete',
			'edit_date',
			'entry_date',
			'entry_id',
			'form_id',
			'form_name',
			'ip_address',
			'reverse_count'
		);

		$absolute_count = $current_page + $offset;
		$total_results	= count($result_array);
		$count			= 0;

		// -------------------------------------
		//	get file attachment count for entries
		// -------------------------------------

		$att_results = $this->model('file_upload')
						->select('form_id, entry_id, COUNT(*) as file_count')
						->where_in('form_id', $form_ids)
						->group_by('form_id, entry_id')
						->get();


		$attached_counts = array();

		if ( ! empty($att_results))
		{
			foreach ($att_results as $att_row)
			{
				if ( ! isset($attached_counts[$att_row['form_id']]))
				{
					$attached_counts[$att_row['form_id']] = array();
				}

				$attached_counts[$att_row['form_id']][$att_row['entry_id']] = $att_row['file_count'];
			}
		}

		// -------------------------------------
		//	build results
		// -------------------------------------

		foreach ($result_array as $row)
		{
			//apply replace tag to our field data
			$field_parse = $this->lib('Fields')->apply_field_method(array(
				'method'			=> 'replace_tag',
				'form_id'			=> $row['form_id'],
				'entry_id'			=> $row['entry_id'],
				'form_data'			=> $forms_data,
				'field_data'		=> $field_data,
				'field_input_data'	=> $row,
				'tagdata'			=> $tagdata
			));

			$row = array_merge(
				$output_labels,
				$field_descriptions,
				$row,
				$field_parse['variables']
			);

			$row['attachment_count'] = isset($attached_counts[$row['form_id']][$row['entry_id']]) ?
										$attached_counts[$row['form_id']][$row['entry_id']] :
										0;

			if ($replace_tagdata == '')
			{
				$replace_tagdata = $field_parse['tagdata'];
			}

			$row['freeform:form_name']			= $forms_data[$row['form_id']]['form_name'];
			$row['freeform:form_label']			= $forms_data[$row['form_id']]['form_label'];

			//prefix
			foreach ($row as $key => $value)
			{
				if ( ! preg_match('/^freeform:/', $key))
				{
					if (in_array($key, $custom_fields) AND
						! isset($row['freeform:field:' . $key]))
					{
						$row['freeform:field:' . $key] = $value;
					}
					else if ( ! isset($row['freeform:' . $key]))
					{
						$row['freeform:' . $key] = $value;
					}

					unset($row[$key]);
				}
			}

			// -------------------------------------
			//	other counts
			// -------------------------------------
			$row['freeform:reverse_count']		= $total_results - $count++;
			$row['freeform:absolute_count']		= ++$absolute_count;
			$row['freeform:absolute_results']	= $total_entries;

			$variable_rows[] = $row;
		}

		$tagdata = $replace_tagdata;

		$prefixed_tags	= array(
			'count',
			'switch',
			'total_results'
		);

		$tagdata = $this->tag_prefix_replace('freeform:', $prefixed_tags, $tagdata);

		//this should handle backspacing as well
		$tagdata = ee()->TMPL->parse_variables($tagdata, $variable_rows);

		$tagdata = $this->tag_prefix_replace('freeform:', $prefixed_tags, $tagdata, TRUE);

		// -------------------------------------
		//	add pagination
		// -------------------------------------

		//prefix or no prefix?
		if ($prefix)
		{
			$tagdata = $this->parse_pagination(array(
				'prefix' 	=> 'freeform:',
				'tagdata' 	=> $tagdata
			));
		}
		else
		{
			$tagdata = $this->parse_pagination(array(
				'tagdata' 	=> $tagdata
			));
		}

		return $tagdata;
	}
	//END entries


	

	// --------------------------------------------------------------------

	/**
	 * Freeform:Form
	 * {exp:freeform:form}
	 *
	 * @access	public
	 * @param 	bool 	$edit			edit mode? external for security
	 * @param	bool	$preview		preview mode?
	 * @param	mixed	$preview_fields	extra preview fields?
	 * @return	string 	tagdata
	 */

	public function form($edit = FALSE, $preview = FALSE, $preview_fields = FALSE)
	{
		if ($this->check_yes(ee()->TMPL->fetch_param('require_logged_in')) AND
			ee()->session->userdata['member_id'] == '0')
		{
			return $this->no_results_error('not_logged_in');
		}

		// -------------------------------------
		//	form id
		// -------------------------------------

		$form_id = $this->form_id(FALSE, FALSE);

		if ( ! $form_id)
		{
			return $this->no_results_error('invalid_form_id');
		}

		// -------------------------------------
		//	libs, helpers, etc
		// -------------------------------------



		ee()->load->helper('form');

		// -------------------------------------
		//	build query
		// -------------------------------------

		$this->form_data = $form_data = $this->model('form')->get_info($form_id);

		// -------------------------------------
		//	preview fields? (composer preview)
		// -------------------------------------

		if ( ! empty($preview_fields))
		{


			$valid_preview_fields = $this->model('field')
										->where_in('field_id', $preview_fields)
										->key('field_id')
										->get();

			if ($valid_preview_fields)
			{
				foreach ($valid_preview_fields as $p_field_id => $p_field_data)
				{
					$p_field_data['preview']			= TRUE;
					$form_data['fields'][$p_field_id]	= $p_field_data;
				}
			}
		}

		// -------------------------------------
		//	form data
		// -------------------------------------

		$this->params['form_id'] = $form_id;

		// -------------------------------------
		//	edit?
		// -------------------------------------

		$entry_id	= 0;

		$edit_data	= array();

		

		$this->params['edit']				= $edit;
		$this->params['entry_id']			= $entry_id;

		// -------------------------------------
		//	replace CURRENT_USER everywhere
		// -------------------------------------

		$this->replace_current_user();

		// -------------------------------------
		//	default params
		// -------------------------------------

		$this->default_mp_page_marker = 'page';

		$this->set_form_params(TRUE);

		//	----------------------------------------
		//	Check for duplicate
		//	----------------------------------------

		$duplicate = FALSE;

		//we can only prevent dupes on entry like this
		if ( ! $edit AND $this->params['prevent_duplicate_on'])
		{
			if ( in_array(
					$this->params['prevent_duplicate_on'],
					array('member_id', 'ip_address'),
					TRUE
				))
			{
				$duplicate = $this->lib('Forms')->check_duplicate(
					$form_id,
					$this->params['prevent_duplicate_on'],
					'',
					$this->params['prevent_duplicate_per_site']
				);
			}
		}

		//	----------------------------------------
		//	duplicate?
		//	----------------------------------------

		if ($duplicate)
		{
			if ($this->params['duplicate_redirect'] !== '')
			{
				ee()->functions->redirect(
					$this->prep_url(
						$this->params['duplicate_redirect'],
						$this->params['secure_duplicate_redirect']
					)
				);

				return $this->do_exit();
			}
			else if ($this->params['error_on_duplicate'])
			{
				return $this->no_results_error('no_duplicates');
			}
			/*else if (preg_match(
				'/' . LD . 'if freeform_duplicate' . RD . '(*?)' '/',
				ee()->TMPL->tagdata, ))
			{

			}*/
		}

		// -------------------------------------
		//	check user email field
		// 	if this is from form prefs, its an ID
		// -------------------------------------

		$valid_user_email_field = FALSE;

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			if ($this->params['user_email_field'] == $field_data['field_name'] OR
				$this->params['user_email_field'] == $field_id)
			{
				$valid_user_email_field = TRUE;

				//in case the setting is an id
				$this->params['user_email_field'] = $field_data['field_name'];
				break;
			}
		}

		//  if it doesn't exist in the form, lets blank it
		$this->params['user_email_field'] = (
			$valid_user_email_field ?
				$this->params['user_email_field'] :
				''
		);

		

		$this->edit	= $edit;

		//	----------------------------------------
		//	'freeform_module_form_begin' hook.
		//	 - This allows developers to change data before form processing.
		//	----------------------------------------

		if (ee()->extensions->active_hook('freeform_module_form_begin') === TRUE)
		{
			ee()->extensions->call(
				'freeform_module_form_begin',
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;
		}
		//	----------------------------------------

		// -------------------------------------
		//	start form
		// -------------------------------------

		$tagdata				= ee()->TMPL->tagdata;
		$return					= '';
		$hidden_fields			= array();
		$outer_template_vars	= array();
		$variables				= array();
		$page_total				= 1;
		$current_page			= 0;
		$last_page				= TRUE;
		$multipage				= $this->params['multipage'];

		// -------------------------------------
		//	check if this is multi-page
		// -------------------------------------

		
			$current_page = 1;
			

		// -------------------------------------
		//	set for hooks
		// -------------------------------------

		$this->multipage = $multipage;
		$this->last_page = $last_page;

		// -------------------------------------
		//	check again for captcha now that
		//	tagdata has been adjusted
		// -------------------------------------

		if ($this->params['require_captcha'])
		{
			$this->params['require_captcha'] = (
				$this->require_captcha() &&
				stristr($tagdata, LD . 'freeform:captcha' . RD) != FALSE
			);
		}

		// -------------------------------------
		//	submit
		// -------------------------------------

		//standard submits
		$variables['freeform:submit'] = form_submit('submit', lang('submit'));

		//replace submit buttons that have args
		$tagdata = $this->replace_submit(array(
			'tag'		=> 'freeform:submit',
			'pre_args'	=> array(
				'name' => 'submit',
				'value' => lang('submit')
			),
			'tagdata'	=> $tagdata
		));

		// -------------------------------------
		//	other random vars
		// -------------------------------------

		$variables['freeform:submit_previous']	= '';
		$variables['freeform:duplicate']		= $duplicate;
		$variables['freeform:not_duplicate']	= ! $duplicate;
		$variables['freeform:form_label']		= $form_data['form_label'];
		$variables['freeform:form_description']	= $form_data['form_description'];
		$variables['freeform:last_page']		= $last_page;
		$variables['freeform:current_page']		= $current_page;

		

		// -------------------------------------
		//	display fields
		// -------------------------------------

		$field_error_data	= array();
		$general_error_data	= array();
		$field_input_data	= array();

		// -------------------------------------
		//	inline errors?
		// -------------------------------------

		if ($this->params['inline_errors'] AND
			$this->is_positive_intlike(
				ee()->session->flashdata('freeform_errors')
			)
		)
		{


			$error_query = $this->model('param')->get_row(
				ee()->session->flashdata('freeform_errors')
			);

			if ($error_query !== FALSE)
			{
				$potential_error_data = json_decode($error_query['data'], TRUE);

				//specific field errors
				if (isset($potential_error_data['field_errors']))
				{
					$field_error_data = $potential_error_data['field_errors'];
				}

				//errors that aren't field based
				if (isset($potential_error_data['general_errors']))
				{
					$general_error_data = $potential_error_data['general_errors'];
				}

				//gets inputs for repopulation
				if (isset($potential_error_data['inputs']))
				{
					$field_input_data = $potential_error_data['inputs'];
				}

				//restore recipient_emails
				if (! empty($potential_error_data['stored_data']['recipient_emails']))
				{
					$previous_inputs['hash_stored_data']['recipient_emails'] =
						$potential_error_data['stored_data']['recipient_emails'];
				}

				//restore user_recipient_emails
				if (! empty($potential_error_data['stored_data']['user_recipient_emails']))
				{
					$previous_inputs['hash_stored_data']['user_recipient_emails'] =
						$potential_error_data['stored_data']['user_recipient_emails'];
				}
			}
		}
		//END if ($this->params['inline_errors']

		// -------------------------------------
		//	build field variables
		// -------------------------------------

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			// -------------------------------------
			//	label?
			// -------------------------------------

			$error = '';

			if (isset($field_error_data[$field_data['field_name']]))
			{
				$error = is_array($field_error_data[$field_data['field_name']]) ?
							implode(', ', $field_error_data[$field_data['field_name']]) :
							$field_error_data[$field_data['field_name']];
			}

			// -------------------------------------
			//	variables for later parsing
			// -------------------------------------

			$variables['freeform:error:' . $field_data['field_name']] = $error;

			$variables['freeform:label:' . $field_data['field_name']] = $field_data['field_label'];
			$variables['freeform:description:' . $field_data['field_name']] = $field_data['field_description'];

			// -------------------------------------
			//	values?
			// -------------------------------------

			$col_name = $this->model('form')->form_field_prefix . $field_id;

			// -------------------------------------
			//	multipage previous inputs?
			// -------------------------------------

			$possible = (
				isset($previous_inputs[$col_name]) ?
					$previous_inputs[$col_name] :
					(
						isset($previous_inputs[$field_data['field_name']]) ?
							$previous_inputs[$field_data['field_name']] :
							''
					)
			);

			$possible = $this->prep_multi_item_data($possible, $field_data['field_type']);

			$variables['freeform:mp_data:' . $field_data['field_name']] = $possible;

			

		}
		//END foreach ($form_data['fields'] as $field_id => $field_data)

		// -------------------------------------
		//	This is done after edit data in
		//	cause they edited data, but had an error
		//	in their edits and we are now in
		//	inline error mode
		// -------------------------------------

		if ( ! empty($edit_data))
		{
			$field_input_data = array_merge($edit_data, $field_input_data);
		}
		else if ( ! empty($previous_inputs))
		{
			$field_input_data = array_merge($previous_inputs, $field_input_data);
		}

		// -------------------------------------
		//	recipient emails from multipage?
		// -------------------------------------

		$variables['freeform:mp_data:user_recipient_emails'] = '';

		if (isset($previous_inputs['hash_stored_data']['user_recipient_emails']) AND
			is_array($previous_inputs['hash_stored_data']['user_recipient_emails']))
		{
			$variables['freeform:mp_data:user_recipient_emails'] = implode(
				', ',
				$previous_inputs['hash_stored_data']['user_recipient_emails']
			);
		}

		// -------------------------------------
		//	freeform:all_form_fields
		// -------------------------------------

		$tagdata = $this->replace_all_form_fields(
			$tagdata,
			$form_data['fields'],
			$form_data['field_order'],
			$field_input_data
		);

		// -------------------------------------
		//	general errors
		// -------------------------------------

		if ( ! empty($general_error_data))
		{
			//the error array might have sub arrays
			//so we need to flatten
			$_general_error_data = array();

			foreach ($general_error_data as $error_set => $error_data)
			{
				if (is_array($error_data))
				{
					foreach ($error_data as $sub_key => $sub_error)
					{
						$_general_error_data[] = array(
							'freeform:error_message' => $sub_error
						);
					}
				}
				else
				{
					$_general_error_data[] = array(
						'freeform:error_message' => $error_data
					);
				}
			}

			$general_error_data = $_general_error_data;
		}

		$variables['freeform:general_errors']	= $general_error_data;
		$variables['freeform:field_errors']		= ! empty($field_error_data);

		//have to do this so the conditional will work,
		//seems that parse variables doesn't think a non-empty array = YES
		$tagdata = ee()->functions->prep_conditionals(
			$tagdata,
			array(
				'freeform:general_errors'	=> ! empty($general_error_data),
				'freeform:field_errors'		=> ! empty($field_error_data)
			)
		);

		// -------------------------------------
		//	apply replace tag to our field data
		// -------------------------------------

		$field_parse = $this->lib('Fields')->apply_field_method(array(
			'method'			=> 'display_field',
			'form_id'			=> $form_id,
			'entry_id'			=> $entry_id,
			'form_data'			=> $form_data,
			'field_input_data'	=> $field_input_data,
			'tagdata'			=> $tagdata
		));

		$this->multipart 	= $field_parse['multipart'];
		$variables 			= array_merge($variables, $field_parse['variables']);
		$tagdata 			= $field_parse['tagdata'];

		// -------------------------------------
		//	dynamic recipient list
		// -------------------------------------

		$this->params['recipients']		= (
			! in_array(ee()->TMPL->fetch_param('recipients'), array(FALSE, ''))
		);

		//preload list with usable info if so
		$this->params['recipients_list'] = array();

		if ( $this->params['recipients'] )
		{
			$i				= 1;
			$while_limit	= 1000;
			$counter		= 0;

			while ( ! in_array(ee()->TMPL->fetch_param('recipient' . $i), array(FALSE, '')) )
			{
				$recipient = explode('|', ee()->TMPL->fetch_param('recipient' . $i));

				//has a name?
				if ( count($recipient) > 1)
				{
					$recipient_name		= trim($recipient[0]);
					$recipient_email	= trim($recipient[1]);
				}
				//no name, we assume its just an email
				//(though, this makes little sense, it needs a name to be useful)
				else
				{
					$recipient_name		= '';
					$recipient_email	= trim($recipient[0]);
				}

				$recipient_selected = FALSE;

				if (isset($previous_inputs['hash_stored_data']['recipient_emails']) AND
					is_array($previous_inputs['hash_stored_data']['recipient_emails']))
				{
					$recipient_selected = in_array(
						$recipient_email,
						$previous_inputs['hash_stored_data']['recipient_emails']
					);
				}

				//add to list
				$this->params['recipients_list'][$i] = array(
					'name'		=> $recipient_name,
					'email'		=> $recipient_email,
									//because this wasn't being unique enough
									//on stupid windows servers *sigh*
					'key'		=> uniqid('', true),
					'selected'	=> $recipient_selected
				);

				$i++;

				//In case fetch_param ever defaults to something
				//thats not falsy.
				if (++$counter >= $while_limit)
				{
					break;
				}
			}

			//if we end up with nothing, then lets not attempt later
			if (empty($this->params['recipients_list']))
			{
				$this->params['recipients'] = FALSE;
			}
		}

		//	----------------------------------------
		//	parse {freeform:captcha}
		//	----------------------------------------

		$variables['freeform:captcha'] = FALSE;

		if ($this->params['require_captcha'])
		{
			$variables['freeform:captcha'] = ee('Captcha')->create();

			// -------------------------------------
			//	IF there is no captcha present
			//	in this tagdata, we don't want
			//	to require people to input it.
			//	Thats asking for errors.
			//	Usually this occurs when someone is
			//	trying to force captcha but the
			//	member is logged in and EE wont
			//	output captcha for members unless
			//	captcha_require_members is enabled.
			// -------------------------------------

			if (stristr($tagdata, LD . 'freeform:captcha' . RD) == FALSE OR
				empty($variables['freeform:captcha']))
			{
				$this->params['require_captcha'] = FALSE;
			}
		}

		// -------------------------------------
		//	dynamic recipient tagdata
		// -------------------------------------

		if ( $this->params['recipients'] AND
			count($this->params['recipients_list']) > 0)
		{
			$variables['freeform_recipients'] = array();

			$recipient_list 	= $this->params['recipients_list'];

			//dynamic above starts with 1, so does this
			for ( $i = 1, $l = count($recipient_list); $i <= $l; $i++ )
			{
				$variables['freeform:recipient_name' . $i] = $recipient_list[$i]['name'];
				$variables['freeform:recipient_value' . $i] = $recipient_list[$i]['key'];
				$variables['freeform:recipient_selected' . $i] = $recipient_list[$i]['selected'];

				$variables['freeform:recipients'][] = array(
					'freeform:recipient_name' 		=> $recipient_list[$i]['name'],
					'freeform:recipient_value'		=> $recipient_list[$i]['key'],
					'freeform:recipient_count'		=> $i,
					//selected from hash data from multipages
					'freeform:recipient_selected' 	=> $recipient_list[$i]['selected']
				);
			}
		}

		// -------------------------------------
		//	status pairs
		// -------------------------------------

		$tagdata = $this->parse_status_tags($tagdata);

		//	----------------------------------------
		//	'freeform_module_pre_form_parse' hook.
		//	 - This allows developers to change data before tagdata processing.
		//	----------------------------------------

		$this->variables = $variables;

		if (ee()->extensions->active_hook('freeform_module_pre_form_parse') === TRUE)
		{
			$backup_tagdata = $tagdata;

			$tagdata = ee()->extensions->call(
				'freeform_module_pre_form_parse',
				$tagdata,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;

						//valid data?
			if ( (! is_string($tagdata) OR empty($tagdata)) AND
				 $this->check_yes($this->model('preference')->preference('hook_data_protection')))
			{
				$tagdata = $backup_tagdata;
			}
		}
		//	----------------------------------------

		//extra precaution in case someone hoses this
		if (isset($this->variables) AND is_array($this->variables))
		{
			$variables = $this->variables;
		}

		// -------------------------------------
		//	parse external vars
		// -------------------------------------

		$outer_template_vars['freeform:form_id']			= $form_id;
		$outer_template_vars['freeform:form_page']			= $current_page;
		$outer_template_vars['freeform:form_page_total']	= $page_total;
		$outer_template_vars['freeform:form_name']			= $form_data['form_name'];
		$outer_template_vars['freeform:form_label']			= $form_data['form_label'];

		ee()->TMPL->template = ee()->functions->prep_conditionals(
			ee()->TMPL->template,
			$outer_template_vars
		);

		ee()->TMPL->template = ee()->functions->var_swap(
			ee()->TMPL->template,
			$outer_template_vars
		);

		// -------------------------------------
		//	parse all vars
		// -------------------------------------

		$tagdata = ee()->TMPL->parse_variables(
			$tagdata,
			array(array_merge($outer_template_vars,$variables))
		);

		// -------------------------------------
		//	This doesn't force an ajax request
		//	but instead forces it _not_ to be
		//	if the ajax param = 'no'.
		//	$this->params['ajax'] defaults to
		//	boolean true, so this will only
		//	happen if someone adds ajax="no".
		// -------------------------------------

		if ( ! $this->params['ajax'])
		{
			$hidden_fields['ajax_request'] = 'no';
		}

		//-------------------------------------
		//	build form
		//-------------------------------------

		$return .= $this->build_form(array(
			'action'			=> $this->get_action_url('save_form'),
			'method'			=> 'post',
			'hidden_fields'		=> array_merge($hidden_fields, array(
				// 	no more params can be set after this
				'params_id' => $this->insert_params(),
			)),
			'tagdata'			=> $tagdata
		));

		//	----------------------------------------
		//	'freeform_module_form_end' hook.
		//	 - This allows developers to change the form before output.
		//	----------------------------------------

		if (ee()->extensions->active_hook('freeform_module_form_end') === TRUE)
		{
			$backup_return = $return;

			$return = ee()->extensions->call(
				'freeform_module_form_end',
				$return,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;

			//valid data?
			if ( (! is_string($return) OR empty($return)) AND
				 $this->check_yes($this->model('preference')->preference('hook_data_protection')))
			{
				$return = $backup_return;
			}
		}
		//	----------------------------------------

		return $return;
	}
	//END form


	// -------------------------------------
	//	action requests
	// -------------------------------------


	// --------------------------------------------------------------------

	/**
	 * ajax_validate
	 *
	 * does a save form that stops after validation
	 *
	 * @access	public
	 * @return	mixed 	ajax request
	 */

	public function ajax_validate_form()
	{
		return $this->save_form(TRUE);
	}
	//END ajax_validate


	// --------------------------------------------------------------------

	/**
	 * save_form
	 *
	 * form save from front_end/action request
	 *
	 * @access	public
	 * @param 	bool validate only
	 * @return	null
	 */

	public function save_form($validate_only = FALSE)
	{
		if ( ! $validate_only AND REQ !== 'ACTION' AND ! $this->test_mode)
		{
			return;
		}



		if (ee()->input->get_post('params_id') === FALSE)
		{
			return $this->pre_validation_error(
				lang('missing_post_data') . ' - params_id'
			);
		}

		// -------------------------------------
		//	require logged in?
		// -------------------------------------

		if ($this->param('require_logged_in') AND
			ee()->session->userdata['member_id'] == '0')
		{
			return $this->pre_validation_error(
				lang('not_authorized') . ' - ' .
				lang('not_logged_in')
			);
		}

		// -------------------------------------
		//	blacklist, banned
		// -------------------------------------

		if (ee()->session->userdata['is_banned'] OR (
				$this->check_yes(ee()->blacklist->blacklisted) AND
				$this->check_no(ee()->blacklist->whitelisted)
			)
		)
		{
			return $this->pre_validation_error(
				lang('not_authorized') . ' - ' .
				lang('reason_banned')
			);
		}

		// -------------------------------------
		//	require ip? (except admin)
		// -------------------------------------

		if ($this->param('require_ip'))
		{
			if (ee()->input->ip_address() == '0.0.0.0')
			{
				return $this->pre_validation_error(
					lang('not_authorized') . ' - ' .
					lang('reason_ip_required')
				);
			}
		}

		// -------------------------------------
		//	Is the nation of the user banned?
		// -------------------------------------

		if ($this->nation_ban_check(FALSE))
		{
			return $this->pre_validation_error(
				lang('not_authorized') . ' - ' .
				ee()->config->item('ban_message')
			);
		}

		

		// -------------------------------------
		//	valid form id
		// -------------------------------------

		$form_id = $this->form_id(FALSE, FALSE);

		if ( ! $form_id)
		{
			return $this->pre_validation_error(lang('invalid_form_id'));
		}

		// -------------------------------------
		//	is this an edit? entry_id
		// -------------------------------------

		$entry_id 		= $this->entry_id();

		$edit			= $this->is_positive_intlike($entry_id);

		// -------------------------------------
		//	for multipage check later
		// -------------------------------------

		$multipage			= $this->param('multipage');
		$current_page		= $this->param('current_page');
		$last_page			= $this->param('last_page');
		$previous_inputs	= array();

		

		// -------------------------------------
		//	form data
		// -------------------------------------

		$this->form_data = $form_data = $this->model('form')->get_info($form_id);
		$field_labels		= array();
		$valid_fields		= array();
		$column_names		= array();

		foreach ( $form_data['fields'] as $row)
		{
			$field_labels[$row['field_name']] 	= $row['field_label'];
			$valid_fields[] 					= $row['field_name'];
			//fill previous inputs names correctly

			$column_name = 'form_field_' . $row['field_id'];

			if (isset($previous_inputs[$column_name]))
			{
				$previous_inputs[$row['field_name']] = $previous_inputs[$column_name];
			}

			$column_names[$row['field_name']] = $column_name;
		}

		// -------------------------------------
		//	for hooks
		// -------------------------------------

		$this->edit			= $edit;
		$this->multipage	= $multipage;
		$this->last_page	= $last_page;

		// -------------------------------------
		//	user email max/spam count
		// -------------------------------------

		if ($last_page AND ($this->param('recipient_user_input') OR
			 $this->param('recipients')) AND
			 $this->lib('Notifications')->check_spam_interval($form_id)
		)
		{
			return $this->pre_validation_error(
				lang('not_authorized') . ' - ' .
				lang('email_limit_exceeded')
			);
		}

		// -------------------------------------
		//	Check for duplicate
		// -------------------------------------

		$duplicate = FALSE;

		if ($this->param('prevent_duplicate_on'))
		{
			$duplicate = $this->lib('Forms')->check_duplicate(
				$form_id,
				$this->param('prevent_duplicate_on'),
				ee()->input->get_post(
					$this->param('prevent_duplicate_on'),
					TRUE
				),
				$this->param('prevent_duplicate_per_site')
			);
		}

		if ($duplicate)
		{
			return $this->pre_validation_error(lang('no_duplicates'));
		}

		// -------------------------------------
		//	pre-validate hook
		// -------------------------------------

		$errors				= array();
		//have to do this weird for backward compat
		$this->field_errors = array();

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
		//	require fields
		// -------------------------------------

		if ($this->param('required'))
		{
			$required = $this->lib('Utils')->pipe_split($this->param('required'));

			foreach ($required as $required_field)
			{
				//require need to work for recipients and recipient email user
				$valid_require = array_merge(
					$valid_fields,
					array('recipient_email_user', 'recipient_email')
				);

				$require_labels = $field_labels;
				$require_labels['recipient_email_user']	= lang('user_recipients');
				$require_labels['recipient_email']		= lang('dynamic_recipients');

				//just in case someone misspelled a require
				//or removes a field after making the require list
				if ( ! in_array($required_field, $valid_require))
				{
					continue;
				}

				$gp_value = ee()->input->get_post($required_field);

				if ( (
						//empty array
						(is_array($gp_value) AND count($gp_value) < 1) OR
						//empty string or false
						( ! is_array($gp_value) AND trim((string) $gp_value) === '') OR
						//recipient email <select> default is '0'
						(
							$required_field === 'recipient_email' AND
							$gp_value === '0'
						)
					)
					//required field could be a file
					//check to see if something uploaded for that name
					AND ! $this->lib('Utils')->file_upload_present(
						$required_field,
						$previous_inputs
					)
				)
				{
					$this->field_errors[
						$required_field
					] = lang('required_field_missing');


					//only want the postfixing of errors
					//if we are sending to general errors screen
					//or an error page
					//the second conditional is for people requesting
					//the custom error page via ajax
					if ( ! $this->param('inline_errors') AND
						 ! ($this->is_ajax_request() AND
							! trim($this->param('error_page'))))
					{
						$this->field_errors[$required_field] .= ': '.
										$require_labels[$required_field];
					}
				}
			}
		}

		// -------------------------------------
		//	matching fields
		// -------------------------------------

		if ($this->param('matching_fields'))
		{
			$matching_fields = $this->lib('Utils')->pipe_split($this->param('matching_fields'));

			foreach ($matching_fields as $match_field)
			{

				//just in case someone misspelled a require
				//or removes a field after making the require list
				if ( ! in_array($match_field, $valid_fields))
				{
					continue;
				}

				//array comparison is correct in PHP and this should work
				//no matter what.
				//normal validation will fix other issues
				if ( ee()->input->get_post($match_field) == FALSE OR
					 ee()->input->get_post($match_field . '_confirm') == FALSE OR
					 ee()->input->get_post($match_field) !==
						ee()->input->get_post($match_field . '_confirm')
				)
				{
					$this->field_errors[$match_field] = lang('fields_do_not_match') .
										$field_labels[$match_field] .
										' | ' .
										$field_labels[$match_field] .
										' ' .
										lang('confirm');
				}
			}
		}

		// -------------------------------------
		//	validate dynamic recipients
		// 	no actual validation errors
		// 	will throw here, but in case we do
		// 	in the future
		// -------------------------------------

		$recipient_emails = array();

		if ($this->param('recipients'))
		{
			$recipient_email_input = ee()->input->get_post('recipient_email');

			if ( ! in_array($recipient_email_input, array(FALSE, ''), TRUE))
			{
				if ( ! is_array($recipient_email_input))
				{
					$recipient_email_input = array($recipient_email_input);
				}

				// recipients are encoded, so lets check for keys
				// since dynamic recipients are dev inputted
				// we aren't going to error on invalid ones
				// but rather just accept if present, and move on if not

				$recipients_list	= $this->param('recipients_list');
				$field_out			= '';

				foreach($recipients_list as $i => $r_data)
				{
					if (in_array($r_data['key'], $recipient_email_input))
					{
						$recipient_emails[] = $r_data['email'];
						$field_out .= $r_data['name'] . ' <' . $r_data['email'] . '>' . "\n";
					}
				}

				//THE ENGLISH ARE TOO MANY!
				if (count($recipient_emails) > $this->param('recipients_limit'))
				{
					$errors['recipient_email'] = lang('over_recipient_limit');
				}

				//does the user have a recipient_email custom field?
				else if (in_array('recipient_email', $valid_fields))
				{
					$_POST['recipient_email'] = trim($field_out);
				}
			}

			//if there is previous recipient emails
			if (empty($recipient_emails) AND
				! empty($previous_inputs['hash_stored_data']['recipient_emails']))
			{
				$recipient_emails = $previous_inputs['hash_stored_data']['recipient_emails'];
			}
		}

		// -------------------------------------
		//	validate user inputted emails
		// -------------------------------------

		$user_recipient_emails = array();

		if ($this->param('recipient_user_input'))
		{
			$user_recipient_email_input = ee()->input->get_post('recipient_email_user');

			if ( ! in_array($user_recipient_email_input, array(FALSE, ''), TRUE))
			{
				$user_recipient_emails = $this->validate_emails($user_recipient_email_input);

				$user_recipient_emails = $user_recipient_emails['good'];

				//if we are here that means we submitted at least something
				//but nothing passed
				if (empty($user_recipient_emails))
				{
					$errors['recipient_user_input'] = lang('no_valid_recipient_emails');
				}
				else if (count($user_recipient_emails) > $this->param('recipient_user_limit'))
				{
					$errors['recipient_email_user'] = lang('over_recipient_user_limit');
				}
			}

			//if there is previous user recipient emails
			if (empty($user_recipient_emails) AND
				! empty($previous_inputs['hash_stored_data']['user_recipient_emails']))
			{
				$user_recipient_emails = $previous_inputs['hash_stored_data']['user_recipient_emails'];
			}
		}

		// -------------------------------------
		//	validate status
		// -------------------------------------

		//on edit we want this blank.
		$status				= ($edit) ? '' : $form_data['default_status'];
		$input_status		= ee()->input->post('status', TRUE);
		$param_status		= $this->param('status');
		$available_statuses	= $this->model('preference')->get_form_statuses();

		//user status input
		if ($this->param('allow_status_edit') AND
			$input_status !== FALSE AND
			array_key_exists($input_status, $available_statuses))
		{
			$status = $input_status;
		}
		//status param
		else if ($param_status !== $status AND
				//this will take care of any blanks
				array_key_exists($param_status, $available_statuses))
		{
			$status = $param_status;
		}

		// -------------------------------------
		//	validate
		// -------------------------------------

		$field_input_data	= array();

		$field_list			= array();

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			$field_list[$field_data['field_name']] = $field_data['field_label'];

			$field_post = ee()->input->post($field_data['field_name'], TRUE);

			//if it's not even in $_POST or $_GET, lets skip input
			//unless its an uploaded file, then we'll send false anyway
			//because its field type will handle the rest of that work
			if ($field_post !== FALSE OR
				isset($_FILES[$field_data['field_name']]))
			{
				$field_input_data[$field_data['field_name']] = $field_post;
			}
		}

		//form fields do their own validation,
		//so lets just get results! (sexy results?)
		$this->field_errors = array_merge(
			$this->field_errors,
			$this->lib('Fields')->validate(
				$form_id,
				$field_input_data,
				! ($this->is_ajax_request() OR $this->param('inline_errors'))
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

		// -------------------------------------
		//	captcha
		// -------------------------------------


		if ( ! $validate_only AND
			ee()->input->get_post('validate_only') === FALSE AND
			$last_page AND
			$this->param('require_captcha'))
		{
			if ( trim(ee()->input->post('captcha')) == '')
			{
				$errors[] = lang('captcha_required');
			}
			else
			{
				ee()->db->where('word', ee()->input->post('captcha', TRUE));
				ee()->db->where('ip_address', ee()->input->ip_address());
				ee()->db->where('date > ', '(UNIX_TIMESTAMP()-7200)', FALSE);

				if ( ! ee()->db->count_all_results('captcha'))
				{
					$errors[] = lang('captcha_required');
				}

				ee()->db->where('word', ee()->input->post('captcha', TRUE));
				ee()->db->where('ip_address', ee()->input->ip_address());
				ee()->db->where('date < ', '(UNIX_TIMESTAMP()-7200)', FALSE);

				ee()->db->delete('captcha');
			}
		}

		$all_errors = array_merge($errors, $this->field_errors);

		// -------------------------------------
		//	halt on errors
		// -------------------------------------

		if (count($all_errors) > 0)
		{
			// -------------------------------------
			//	inline errors
			// -------------------------------------

			if ($this->param('inline_errors'))
			{


				$error_param_id = $this->model('param')->insert_params(
					array(
						'general_errors'	=> $errors,
						'field_errors'		=> $this->field_errors,
						'inputs'			=> $field_input_data,
						'stored_data'		=> array(
							'recipient_emails'		=> $recipient_emails,
							'user_recipient_emails'	=> $user_recipient_emails
						)
					)
				);

				ee()->session->set_flashdata('freeform_errors', $error_param_id);

				ee()->functions->redirect(
					$this->prep_url(
						$this->param('inline_error_return'),
						$this->param('secure_return')
					)
				);
				return $this->do_exit();
			}

			

			// -------------------------------------
			//	ajax or standard user error
			// -------------------------------------

			$this->lib('Utils')->full_stop($all_errors);
		}

		//send ajax response exists
		//but this is in case someone is using a replacer
		//that uses
		if ($validate_only OR ee()->input->get_post('validate_only') !== FALSE)
		{
			if ($this->is_ajax_request())
			{
				$this->send_ajax_response(array(
					'success'	=> TRUE,
					'errors'	=> array()
				));
			}

			return $this->do_exit();
		}

		// -------------------------------------
		//	status
		// -------------------------------------

		//if we have no status (edit or some other weirdness)
		//let the forms lib handle it.
		if ( ! $edit || trim($status) !== '')
		{
			$field_input_data['status'] = $status;
		}

		// -------------------------------------
		//	entry insert begin hook
		// -------------------------------------

		if (ee()->extensions->active_hook('freeform_module_insert_begin') === TRUE)
		{
			$backup_data = $field_input_data;

			$field_input_data = ee()->extensions->call(
				'freeform_module_insert_begin',
				$field_input_data,
				$entry_id,
				$form_id,
				$this
			);

			if (ee()->extensions->end_script === TRUE)
			{
				return;
			}

			// -------------------------------------
			//	if some butthead doesn't return
			//	the array from their extension
			//	we need a backup here or everything
			//	busts.
			// -------------------------------------

			if ( ! is_array($field_input_data) AND
				 $this->check_yes($this->model('preference')->preference('hook_data_protection')))
			{
				$field_input_data = $backup_data;
			}
		}

		// -------------------------------------
		//	insert/update data into db
		// -------------------------------------


			$entry_id = $this->lib('Forms')->insert_new_entry(
				$form_id,
				$field_input_data
			);

		// -------------------------------------
		//	entry insert end hook
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

			if (ee()->extensions->end_script === TRUE)
			{
				return;
			}
		}

		// -------------------------------------
		//	delete xid and captcha
		// -------------------------------------
		//	wait this late because we dont
		//	want to remove before a custom field
		// 	has a chance to throw an error
		// 	on one of its actions, like file
		//	upload
		// -------------------------------------

		if ($last_page AND $this->param('require_captcha'))
		{
			ee()->db->where(array(
				'word'			=> ee()->input->post('captcha'),
				'ip_address'	=> ee()->input->ip_address()
			));
			ee()->db->or_where('date <', ee()->localize->now - 7200);
			ee()->db->delete('captcha');
		}

		// -------------------------------------
		//	if we are multipaging and going
		//	to the previous page, bail early
		// -------------------------------------

		if ($multipage &&
			$submit_to_previous &&
			$this->param('multipage_previous_page'))
		{
			ee()->functions->redirect(
				$this->prep_url(
					$this->param('multipage_previous_page'),
					$this->param('secure_return')
				)
			);

			return $this->do_exit();
		}

		// -------------------------------------
		//	if we are multi-paging, move on
		// -------------------------------------

		if ($multipage AND ! $last_page)
		{
			ee()->functions->redirect(
				$this->prep_url(
					$this->param('multipage_next_page'),
					$this->param('secure_return')
				)
			);
			return $this->do_exit();
		}

		// -------------------------------------
		//	edit data for notifications?
		// -------------------------------------

		if ($edit)
		{
			$edit_data = $this->model('Data')->get_entry_data_by_id($entry_id, $form_id);

			if (is_array($previous_inputs))
			{
				$previous_inputs = array_merge($edit_data, $previous_inputs);
			}
			else
			{
				$previous_inputs = $edit_data;
			}
		}

		// -------------------------------------
		//	previous inputs need their real names
		// -------------------------------------

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			if (is_array($previous_inputs))
			{
				$fid = $this->model('form')->form_field_prefix . $field_id;

				//id name? field_id_1, etc
				if (isset($previous_inputs[$fid]))
				{
					$previous_inputs[$field_data['field_name']] = $previous_inputs[$fid];
				}
			}
		}

		$field_input_data = array_merge(
			(is_array($previous_inputs) ? $previous_inputs : array()),
			array('entry_id' => $entry_id),
			$field_input_data
		);

		// -------------------------------------
		//	do notifications
		// -------------------------------------

		if ( ! $edit OR $this->param('notify_on_edit'))
		{
			if ($this->param('notify_admin'))
			{
				$this->lib('Notifications')->send_notification(array(
					'form_id'			=> $form_id,
					'entry_id'			=> $entry_id,
					'notification_type'	=> 'admin',
					'recipients'		=> $this->param('admin_notify'),
					'form_input_data'	=> $field_input_data,
					'cc_recipients'		=> $this->param('admin_cc_notify'),
					'bcc_recipients'	=> $this->param('admin_bcc_notify'),
					'template'			=> $this->param('admin_notification_template')
				));
			}

			//this is a custom field named by the user
			//notifications does its own validation
			//so if someone puts a non-validated input field
			//then notifications will just silently fail
			//because it wont be a user input problem
			//but rather a dev implementation problem
			if ($this->param('notify_user') AND
				$this->param('user_email_field') AND
				isset($field_input_data[$this->param('user_email_field')]))
			{
				$this->lib('Notifications')->send_notification(array(
					'form_id'			=> $form_id,
					'entry_id'			=> $entry_id,
					'notification_type'	=> 'user',
					'recipients'		=> $field_input_data[$this->param('user_email_field')],
					'form_input_data'	=> $field_input_data,
					'template'			=> $this->param('user_notification_template'),
					'enable_spam_log'	=> FALSE
				));
			}

			//recipients
			if ( ! empty($recipient_emails))
			{
				$this->lib('Notifications')->send_notification(array(
					'form_id'			=> $form_id,
					'entry_id'			=> $entry_id,
					'notification_type'	=> 'user_recipient',
					'recipients'		=> $recipient_emails,
					'form_input_data'	=> $field_input_data,
					'template'			=> $this->param('recipient_template')
				));
			}

			//user inputted recipients
			if ( ! empty($user_recipient_emails))
			{
				$this->lib('Notifications')->send_notification(array(
					'form_id'			=> $form_id,
					'entry_id'			=> $entry_id,
					'notification_type'	=> 'user_recipient',
					'recipients'		=> $user_recipient_emails,
					'form_input_data'	=> $field_input_data,
					'template'			=> $this->param('recipient_user_template')
				));
			}
		}

		// -------------------------------------
		//	return
		// -------------------------------------

		$return_url = $this->param('return');

		if (ee()->input->post('return') !== FALSE)
		{
			$return_url = ee()->input->post('return');
		}

		$return = str_replace(
			//because. Shut up.
			array(
				'%%form_entry_id%%',
				'%%entry_id%%',
				'%form_entry_id%',
				'%entry_id%'
			),
			$entry_id,
			$this->prep_url(
				$return_url,
				$this->param('secure_return')
			)
		);

		//detergent?
		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success'		=> TRUE,
				'entry_id'		=> $entry_id,
				'form_id'		=> $form_id,
				'return'		=> $return,
				'return_url'	=> $return
			));
		}
		else
		{
			ee()->functions->redirect($return);
		}
	}
	//END save_form


	// --------------------------------------------------------------------
	//	private! No looky!
	// --------------------------------------------------------------------


	// --------------------------------------------------------------------

	/**
	 * Pre-Validation Errors that are deal breakers
	 *
	 * @access	protected
	 * @param	mixed		$errors	error string or array of errors
	 * @return	null		exits
	 */

	protected function pre_validation_error($errors)
	{
		// -------------------------------------
		//	NOTE: These are pre-validation errors
		//	that are only supposed to be things
		//	that are fatal to the submitting
		//	of the form.
		// -------------------------------------

		if ($this->param('inline_errors'))
		{


			$error_param_id = $this->model('param')->insert_params(
				array(
					'general_errors'	=> is_array($errors) ? $errors : array($errors),
					'field_errors'		=>	array(),
					'inputs'			=> array()
				)
			);

			ee()->session->set_flashdata('freeform_errors', $error_param_id);

			ee()->functions->redirect(
				$this->prep_url(
					$this->param('inline_error_return'),
					$this->param('secure_return')
				)
			);
			return $this->do_exit();
		}

		

		return $this->lib('Utils')->full_stop($errors);
	}
	//END pre_validation_error


	// --------------------------------------------------------------------

	/**
	 * build_form
	 *
	 * builds a form based on passed data
	 *
	 * @access	private
	 * @
	 * @return 	mixed  	boolean false if not found else id
	 */

	private function build_form($data)
	{
		// -------------------------------------
		//	prep input data
		// -------------------------------------

		//set defaults for optional items
		$input_defaults	= array(
			'action' 			=> '/',
			'hidden_fields' 	=> array(),
			'tagdata'			=> ee()->TMPL->tagdata,
		);

		//array2 overwrites any duplicate key from array1
		$data 			= array_merge($input_defaults, $data);

		//OK, so form_open is supposed to be doing this,
		//but guess what: It only works if CI sees that
		//config->item('csrf_protection') === true, and uh
		//sometimes it's false eventhough secure_forms == 'y'
		//
		if ( $this->check_yes(ee()->config->item('secure_forms')) )
		{
			$data['hidden_fields']['csrf_token'] = CSRF_TOKEN;
		}

		// --------------------------------------------
		//  HTTPS URLs?
		// --------------------------------------------

		$data['action'] = $this->prep_url(
			$data['action'],
			(
				isset($this->params['secure_action']) AND
				$this->params['secure_action']
			)
		);


		foreach(array('return', 'RET') as $return_field)
		{
			if (isset($data['hidden_fields'][$return_field]))
			{
				$data['hidden_fields'][$return_field] = $this->prep_url(
					$data['hidden_fields'][$return_field],
					(
						isset($this->params['secure_return']) AND
						$this->params['secure_return']
					)
				);
			}
		}

		// --------------------------------------------
		//  Override Form Attributes with form:xxx="" parameters
		// --------------------------------------------

		$form_attributes = array();

		if (is_object(ee()->TMPL) AND ! empty(ee()->TMPL->tagparams))
		{
			foreach(ee()->TMPL->tagparams as $key => $value)
			{
				if (strncmp($key, 'form:', 5) == 0)
				{
					//allow action override.
					if (substr($key, 5) == 'action')
					{
						$data['action'] = $value;
					}
					else
					{
						$form_attributes[substr($key, 5)] = $value;
					}
				}
			}
		}

		// --------------------------------------------
		//  Create and Return Form
		// --------------------------------------------

		//have to have this for file uploads
		if ($this->multipart)
		{
			$form_attributes['enctype'] = 'multipart/form-data';
		}

		$form_attributes['method'] = $data['method'];

		$return		= form_open(
			$data['action'],
			$form_attributes,
			$data['hidden_fields']
		);

		//Rremoved stripslashes as it was affecting html5 regex
		//validation and no one can really remember why it was
		//here in the first place (since FF 1.x). Might be that
		//it was a legacy thing with EE 1.x.
		//$return		.= stripslashes($data['tagdata']);

		$return		.= $data['tagdata'];

		$return		.= "</form>";

		return $return;
	}
	//END build_form


	// --------------------------------------------------------------------

	/**
	 * form_id - finds form id the best it can
	 *
	 * @access	private
	 * @param   bool 	$allow_multiple allow multiple input?
	 * @return 	mixed  	boolean false if not found else id
	 */

	private function form_id($allow_multiple = FALSE, $use_cache = TRUE)
	{
		if ($use_cache AND $this->form_id)
		{
			return $this->form_id;
		}

		$form_id		= FALSE;
		$possible_name	= FALSE;
		$possible_label	= FALSE;
		$possible_id	= FALSE;
		$tmpl_available	= (isset(ee()->TMPL) AND is_object(ee()->TMPL));
		// -------------------------------------
		//	by direct param first
		// -------------------------------------

		if ($tmpl_available)
		{
			$possible_id = ee()->TMPL->fetch_param('form_id');
		}

		// -------------------------------------
		//	by name param
		// -------------------------------------

		if ( ! $possible_id AND $tmpl_available)
		{
			$possible_name = ee()->TMPL->fetch_param('form_name');
		}

		// -------------------------------------
		//	by label (with legacy for collection)
		// -------------------------------------

		if ($tmpl_available AND ! $possible_id AND ! $possible_name)
		{
			$possible_label = ee()->TMPL->fetch_param('form_label');

			if ( ! $possible_label)
			{
				$possible_label = ee()->TMPL->fetch_param('collection');
			}
		}

		// -------------------------------------
		//	params id
		// -------------------------------------

		if ( ! $possible_id AND
			 ! $possible_name AND
			 ! $possible_label AND
			 $this->param('form_id'))
		{
			$possible_id = $this->param('form_id');
		}

		// -------------------------------------
		//	params name
		// -------------------------------------

		if ( ! $possible_id AND
			 ! $possible_name AND
			 ! $possible_label AND
			 $this->param('form_name'))
		{
			$possible_name = $this->param('form_name');
		}

		// -------------------------------------
		//	get post id
		// -------------------------------------

		if ( ! $possible_id AND
			 ! $possible_name AND
			 ! $possible_label)
		{
			$possible_id = ee()->input->get_post('form_id');
		}

		// -------------------------------------
		//	get post name
		// -------------------------------------

		if ( ! $possible_id AND
			 ! $possible_name AND
			 ! $possible_label)
		{
			$possible_name = ee()->input->get_post('form_name');
		}

		// -------------------------------------
		//	check possibles
		// -------------------------------------

		if ($possible_id)
		{
			//if multiple and match pattern...
			if ($allow_multiple AND preg_match('/^[\d\|]+$/', $possible_id))
			{
				$ids = $this->lib('Utils')->pipe_split($possible_id);



				$result = $this->model('form')->select('form_id')
												   ->get(array('form_id' => $ids));
				//we only want results, not everything
				if ($result !== FALSE)
				{
					$form_id = array();

					foreach ($result as $row)
					{
						$form_id[] = $row['form_id'];
					}
				}
			}
			else if ($this->is_positive_intlike($possible_id) AND
					 $this->model('form')->valid_id($possible_id))
			{
				$form_id = $possible_id;
			}
		}

		if ( ! $form_id AND $possible_name)
		{
			//if multiple and pipe
			if ($allow_multiple AND stristr($possible_name, '|'))
			{
				$names = $this->lib('Utils')->pipe_split($possible_name);



				$result = $this->model('form')->select('form_id')
												   ->get(array('form_name' => $names));

				//we only want results, not everything
				if ($result !== FALSE)
				{
					$form_id = array();

					foreach ($result as $row)
					{
						$form_id[] = $row['form_id'];
					}
				}
			}
			else
			{
				$possible_id = $this->model('Data')->get_form_id_by_name($possible_name);

				if ($possible_id !== FALSE AND $possible_id > 0)
				{
					$form_id = $possible_id;
				}
			}
		}

		if ( ! $form_id AND $possible_label)
		{


			//if multiple and pipe
			if ($allow_multiple AND stristr($possible_label, '|'))
			{
				$names = $this->lib('Utils')->pipe_split($possible_label);

				$result =	$this->model('form')
								->select('form_id')
								->get(array('form_label' => $names));

				//we only want results, not everything
				if ($result !== FALSE)
				{
					$form_id = array();

					foreach ($result as $row)
					{
						$form_id[] = $row['form_id'];
					}
				}
			}
			else
			{
				$possible_id =	$this->model('form')
									->select('form_id')
									->get_row(array('form_label' => $possible_label));

				if ($possible_id !== FALSE)
				{
					$form_id = $possible_id['form_id'];
				}
			}
		}

		// -------------------------------------
		//	store if good
		// -------------------------------------

		if ($form_id AND $form_id > 0)
		{
			$this->form_id = $form_id;
		}

		return $form_id;
	}
	//END form_id


	// --------------------------------------------------------------------

	/**
	 * entry_id - finds entry id the best it can
	 *
	 * @access	private
	 * @return 	mixed	boolean false if not found else id
	 */

	private function entry_id()
	{
		$form_id = $this->form_id();

		if ( ! $form_id)
		{
			return FALSE;
		}

		if (isset($this->entry_id) AND $this->entry_id)
		{
			return $this->entry_id;
		}

		$entry_id = FALSE;

		// -------------------------------------
		//	by direct param first
		// -------------------------------------

		if (isset(ee()->TMPL) AND is_object(ee()->TMPL))
		{
			$entry_id_param = ee()->TMPL->fetch_param('entry_id');

			if ( $this->is_positive_intlike($entry_id_param) AND
				 $this->model('Data')->is_valid_entry_id($entry_id_param, $form_id))
			{
				$entry_id = $entry_id_param;
			}
		}

		// -------------------------------------
		//	params id
		// -------------------------------------

		if ( ! $entry_id AND $this->param('entry_id'))
		{
			$entry_id_param = $this->param('entry_id');

			if ( $this->is_positive_intlike($entry_id_param) AND
				 $this->model('Data')->is_valid_entry_id($entry_id_param, $form_id))
			{
				$entry_id = $entry_id_param;
			}
		}

		// -------------------------------------
		//	get post id
		// -------------------------------------

		if ( ! $entry_id AND ee()->input->get_post('entry_id'))
		{
			$entry_id_param = ee()->input->get_post('entry_id');

			if ( $this->is_positive_intlike($entry_id_param) AND
				  $this->model('Data')->is_valid_entry_id($entry_id_param, $form_id))
			{
				$entry_id = $entry_id_param;
			}
		}

		// -------------------------------------
		//	store if good
		// -------------------------------------

		if ($entry_id AND $entry_id > 0)
		{
			$this->entry_id = $entry_id;
		}

		return $entry_id;
	}
	//END entry_id


	// --------------------------------------------------------------------

	/**
	 * Set Form Params
	 *
	 * @access	public
	 * @param	array		incoming form data for defaults
	 * @param	bool		rerun param finding?
	 * @return	array		array of params found
	 */

	public function set_form_params($rerun = FALSE)
	{
		if ( ! $rerun && $this->params_ran)
		{
			return $this->params;
		}

		$params_with_defaults = $this->get_default_params();

		foreach ($params_with_defaults as $name => $default)
		{
			$this->set_form_param($name);
		}

		//we use the bool here because
		//the params array can have items set to it
		//outside of the defaults running here
		$this->params_ran = true;

		return $this->params;
	}
	//set_form_params


	// --------------------------------------------------------------------

	/**
	 * Set Form Param
	 *
	 * @access	public
	 * @param	string	$name			name of param
	 * @param	mixed	$value			optional value to set param to
	 * @param	boolean $set_tagparam [description]
	 */

	public function set_form_param($name, $value = null, $set_tagparam = false)
	{
		$params_with_defaults = $this->get_default_params();

		//not default? lets just set it.
		if ( ! isset($params_with_defaults[$name]))
		{
			if ($value === null)
			{
				$value = ee()->TMPL->fetch_param($name);
			}

			$this->params[$name] = $value;
		}
		else
		{
			$default = $params_with_defaults[$name];

			//if the default is a boolean value we only want a boolean
			//output
			if (is_bool($default))
			{
				if ($value === null)
				{
					$value = ee()->TMPL->fetch_param($name);
				}

				//and if there is a string version of the param
				if (is_string($value))
				{
					//and if the default is boolean true
					if ($default === TRUE)
					{
						//and if the template param uses an indicator of the
						//'false' variety, we want to override the default
						//of TRUE and set FALSE.
						$this->params[$name] = ! $this->check_no($value);
					}
					//but if the default is boolean false
					else
					{
						//and the template param is trying to turn the feature
						//on through a 'y', 'yes', or 'on' value, then we want
						//to convert the FALSE to a TRUE
						$this->params[$name] = $this->check_yes($value);
					}
				}
				//there is no template param version of
				//this default so the default stands
				else
				{
					//for setting tagparam, however rare
					$value = $default;
					$this->params[$name] = $default;
				}
			}
			//other wise check for the param or fallback on default
			else
			{
				if ($value === null)
				{
					$value = trim(
						ee()->TMPL->fetch_param($name, $default)
					);
				}

				$this->params[$name] = $value;
			}

		}

		if ($set_tagparam && $value !== null)
		{
			ee()->TMPL->tagparams[$name] = $value;
		}
	}
	//END set_form_param


	// --------------------------------------------------------------------


	/**
	 * Get Default Params
	 *
	 * Gets default Params and sets them to class var if not alraedy set
	 * @access	public
	 * @return	array	key value array of default params
	 */

	public function get_default_params()
	{
		if (isset($this->params_with_defaults))
		{
			return $this->params_with_defaults;
		}

		if ( ! isset($this->form_data))
		{
			$this->form_data = $this->model('form')->get_info($this->form_id());
		}

		$this->params_with_defaults	= array(
			//security
			'secure_action'					=> FALSE,
			'secure_return'					=> FALSE,
			'require_captcha'				=> $this->require_captcha(),
			'require_ip'					=> ! $this->check_no(
				ee()->config->item("require_ip_for_posting")
			),
			'return'						=> ee()->uri->uri_string,
			'inline_error_return'			=> ee()->uri->uri_string,
			'error_page'					=> '',
			'ajax'							=> TRUE,
			'restrict_edit_to_author'		=> TRUE,

			'inline_errors'					=> FALSE,

			//dupe prevention
			'prevent_duplicate_on'			=> '',
			'prevent_duplicate_per_site'	=> FALSE,
			'secure_duplicate_redirect'		=> FALSE,
			'duplicate_redirect'			=> '',
			'error_on_duplicate'			=> FALSE,

			//required or matching fields
			'required'						=> '',
			'matching_fields'				=> '',

			//multipage
			'last_page'						=> TRUE,
			'multipage'						=> FALSE,
			'redirect_on_timeout'			=> TRUE,
			'redirect_on_timeout_to'		=> '',
			'page_marker'					=> $this->default_mp_page_marker,
			'multipage_page'				=> '',
			'paging_url'					=> '',
			'multipage_page_names'			=> '',

			//notifications
			'admin_notify'					=> $this->form_data['admin_notification_email'],
			'admin_cc_notify'				=> '',
			'admin_bcc_notify'				=> '',
			'notify_user'					=> $this->check_yes($this->form_data['notify_user']),
			'notify_admin'					=> $this->check_yes($this->form_data['notify_admin']),
			'notify_on_edit'				=> FALSE,
			'user_email_field'				=> $this->form_data['user_email_field'],

			//dynamic_recipients
			'recipients'					=> FALSE,
			'recipients_limit'				=> '3',

			//user inputted recipients
			'recipient_user_input'			=> FALSE,
			'recipient_user_limit'			=> '3',

			//templates
			'recipient_template'			=> "",
			'recipient_user_template'		=> "",
			'admin_notification_template'	=> $this->form_data['admin_notification_id'],
			'user_notification_template'	=> $this->form_data['user_notification_id'],

												//we already set default status in like 5 places
			'status'						=> '',//$this->form_data['default_status'],
			'allow_status_edit'				=> FALSE,
		);

		return $this->params_with_defaults;
	}
	//END get_default_params

	// --------------------------------------------------------------------

	/**
	 * Require Captcha?
	 *
	 * @access	public
	 * @return	boolean		user isn't logged in or member require captcha
	 */

	public function require_captcha()
	{
		return (
			ee()->session->userdata('member_id') == 0 OR
			$this->check_yes(ee()->config->item('captcha_require_members'))
		);
	}
	//END require_captcha


	// --------------------------------------------------------------------

	/**
	 * Set Page Positions
	 *
	 * @access	public
	 * @return	array	array of page positions for current page
	 */

	public function set_page_positions()
	{
		if (isset($this->page_positions))
		{
			//removed this cache because
			//its causing issues with multipage forms
			//in Ee 2.10.x. No clue whats causing it
			//but this isn't saving us a whole lot of
			//CPU cycles anyway.
			//return $this->page_positions;
		}

		// -------------------------------------
		//	need form params
		// -------------------------------------

		$this->set_form_params();

		// -------------------------------------
		//	defaults
		// -------------------------------------

		$current_page	= 0;
		$next_page		= 0;
		$previous_page	= 0;
		$page_type		= 'int';
		$page_total		= 1;

		// -------------------------------------
		//	page array?
		// -------------------------------------

		$mp_page_array = $this->get_mp_page_array();

		// -------------------------------------
		//	parse
		// -------------------------------------

		if ( ! empty($mp_page_array))
		{
			$page_type = 'text';

			$position		= array_search(
				$this->params['multipage_page'],
				$mp_page_array
			);

			if (in_array($position, array(-1, FALSE), TRUE))
			{
				$position = 0;
			}

			//this will be our human readable
			$current_page		= $position + 1;

			$page_total			= count($mp_page_array);

			$next_page			= ($position + 1 < $page_total) ?
									$mp_page_array[$position + 1] : 0;
			$previous_page		= ($position - 1 >= 0) ?
									$mp_page_array[$position - 1] : 0;

			// -------------------------------------
			//	set paging url
			// -------------------------------------

			if ($this->params['paging_url'] == '')
			{
				$matches = array();

				if ($this->params['multipage_page'])
				{
					//we want to match all and get the last
					preg_match_all(
						'/(?:\/|^)(' .
							preg_quote($this->params['multipage_page'], '/') .
						')(?:\/|$)/',
						ee()->uri->uri_string,
						$matches,
						PREG_SET_ORDER
					);
				}

				$match = array();

				if ($matches)
				{
					$match = array_pop($matches);
				}

				if (isset($match[1]))
				{
					$segs	= explode('/', ee()->uri->uri_string);

					$i		= count($segs);

					while ($i--)
					{
						if ($segs[$i] == $match[1])
						{
							$segs[$i] = '%page%';
							break;
						}
					}

					$this->params['paging_url'] = implode($segs, '/');

					//if they didn't set a redirect on timeout
					//we can assume its this same setup, but with
					//page1 instead of page whatever else.
					if ($this->params['redirect_on_timeout_to'] == '')
					{
						//swap the number from the match, then swap the match
						$this->params['redirect_on_timeout_to'] = str_replace(
							'%page%',
							$mp_page_array[0],
							$this->params['paging_url']
						);
					}
				}
				//END if (isset($match[1]))
			}
			//END if ($this->params['paging_url'] == '')
		}
		//else to if ( ! empty($mp_page_array))
		//manually passed integer?
		else if ($this->params['multipage_page'] != '')
		{
			//remove the marker in case someone sends us a segment
			//with the marker in it like "page2"
			$multipage_page = trim(
				str_replace(
					$this->params['page_marker'],
					'',
					$this->params['multipage_page']
				)
			);

			if ($multipage_page AND
				$this->is_positive_intlike($multipage_page))
			{
				$current_page = $multipage_page;

				$next_page		= ($current_page < $page_total) ?
									$current_page + 1 : 0;

				$previous_page	= ($current_page > 1) ?
									$current_page - 1 : 0;
			}
		}
		//END if ( ! empty($mp_page_array))
		//else if ($this->params['multipage_page'] != '')

		// -------------------------------------
		//	find dynamically?
		// -------------------------------------

		if ($current_page == 0)
		{
			$pm = $this->params['page_marker'];

			//find the page number in /ee/template/page1/
			preg_match(
				'/(?:\/|^)' . preg_quote($pm, '/') . '([0-9]+)(?:\/|$)/',
				ee()->uri->uri_string,
				$matches
			);

			if (isset($matches[1]))
			{
				$current_page = $matches[1];

				//swap the number from the match, then swap the match
				$this->params['paging_url'] = str_replace(
					$pm . $matches[1],
					'%page%',
					ee()->uri->uri_string
				);

				//if they didn't set a redirect on timeout
				//we can assume its this same setup, but with
				//page1 instead of page whatever else.
				if ($this->params['redirect_on_timeout_to'] == '')
				{
					//swap the number from the match, then swap the match
					$this->params['redirect_on_timeout_to'] = str_replace(
						$matches[0],
						str_replace(
							$matches[1],
							'1',
							$matches[0]
						),
						ee()->uri->uri_string
					);
				}
			}
		}

		//if none of that crap worked, page 1
		if ($current_page == 0)
		{
			$current_page = 1;

			$next_page		= ($current_page < $page_total) ?
				$current_page + 1 : 0;

			$previous_page	= ($current_page > 1) ?
				$current_page - 1 : 0;
		}

		$this->page_positions = array(
			'page_type'			=> $page_type,
			'current_page'		=> $current_page,
			'page_total'		=> $page_total,
			'next_page'			=> $next_page,
			'previous_page'		=> $previous_page,
		);

		return $this->page_positions;
	}
	//END set_page_positions


	// --------------------------------------------------------------------

	/**
	 * Get Multipage page array
	 *
	 * @access	public
	 * @return	array	array of multipage pages or empty array
	 */

	public function get_mp_page_array()
	{
		if (isset($this->mp_page_array))
		{
			return $this->mp_page_array;
		}

		$this->set_form_params();

		if ($this->params['multipage_page_names'] != '')
		{
			$mp_page_array = $this->lib('Utils')->pipe_split(
				$this->params['multipage_page_names']
			);

			array_walk($mp_page_array, 'trim');

			$this->mp_page_array = $mp_page_array;

			// -------------------------------------
			//	multipage page missing?
			// -------------------------------------

			if ( ! $this->params['multipage_page'])
			{
				foreach ($mp_page_array as $key => $page)
				{
					$mp_page_array[$key] = preg_quote($page, '/');
				}

				$match_string = '/(?:\/|^)(' .
									implode('|', $mp_page_array) .
								')(?:\/|$)/';

				//we want to match all and get the last
				preg_match_all(
					$match_string,
					ee()->uri->uri_string,
					$matches,
					PREG_SET_ORDER
				);

				if ($matches)
				{
					$match = array_pop($matches);

					if ( ! $this->params['multipage_page'])
					{
						$this->params['multipage_page'] = $match[1];
					}
				}
			}
		}
		else
		{
			$this->mp_page_array = array();
		}

		return $this->mp_page_array;
	}
	//END get_mp_page_array


	// --------------------------------------------------------------------

	/**
	 * param - gets stored paramaters
	 *
	 * @access	private
	 * @param	string  $which	which param needed
	 * @param	string  $type	type of param
	 * @return	bool 			$which was empty
	 */

	private function param($which = '', $type = 'all')
	{
		//	----------------------------------------
		//	Params set?
		//	----------------------------------------

		if ( count( $this->params ) == 0 )
		{


			//	----------------------------------------
			//	Empty id?
			//	----------------------------------------

			$params_id = ee()->input->get_post('params_id', TRUE);

			if ( ! $this->is_positive_intlike($params_id) )
			{
				return FALSE;
			}

			$this->params_id = $params_id;

			// -------------------------------------
			//	pre-clean so cache can keep
			// -------------------------------------

			$this->model('param')->cleanup();

			//	----------------------------------------
			//	Select from DB
			//	----------------------------------------

			$data = $this->model('param')->select('data')
											  ->get_row($this->params_id);

			//	----------------------------------------
			//	Empty?
			//	----------------------------------------

			if ( ! $data )
			{
				return FALSE;
			}

			//	----------------------------------------
			//	Unserialize
			//	----------------------------------------

			$this->params				= json_decode( $data['data'], TRUE );
			$this->params				= is_array($this->params) ? $this->params : array();
			$this->params['set']		= TRUE;
		}
		//END if ( count( $this->params ) == 0 )


		//	----------------------------------------
		//	Fetch from params array
		//	----------------------------------------

		if ( isset( $this->params[$which] ) )
		{
			$return	= str_replace( "&#47;", "/", $this->params[$which] );

			return $return;
		}

		//	----------------------------------------
		//	Fetch TMPL
		//	----------------------------------------

		if ( isset( ee()->TMPL ) AND
			 is_object(ee()->TMPL) AND
			 ee()->TMPL->fetch_param($which) )
		{
			return ee()->TMPL->fetch_param($which);
		}

		//	----------------------------------------
		//	Return (if which is blank, we are just getting data)
		//	else if we are looking for something that doesn't exist...
		//	----------------------------------------

		return ($which === '');
	}
	//End param


	// --------------------------------------------------------------------

	/**
	 * insert_params - adds multiple params to stored params
	 *
	 * @access	private
	 * @param	array	$param	sassociative array of params to send
	 * @return	mixed			insert id or false
	 */

	private function insert_params( $params = array() )
	{


		if (empty($params) AND isset($this->params))
		{
			$params = $this->params;
		}

		return $this->model('param')->insert_params($params);
	}
	//	End insert params


	// --------------------------------------------------------------------

	/**
	 * prep_url
	 *
	 * checks a url for {path} or url creation needs with https replacement
	 *
	 * @access	private
	 * @param 	string 	url to be prepped
	 * @param 	bool 	replace http with https?
	 * @return	string 	url prepped with https or not
	 */

	private function prep_url($url, $https = FALSE)
	{
		$return = trim($url);

		$return = ($return !== '') ? $return : ee()->config->item('site_url');

		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) > 0 )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}
		else if ( ! preg_match('/^http[s]?:\/\/|^\/\//', $return) )
		{
			$return	= ee()->functions->create_url( $return );
		}

		if ($https)
		{
			$return = preg_replace('/^http:\/\/|^\/\//', 'https://', $return);
		}

		return $return;
	}
	//end prep_url


	// --------------------------------------------------------------------

	/**
	 * nation ban check
	 *
	 * sessions built in nation ban check doesn't properly
	 * return a bool if show errors are off
	 * and we want ajax responses with this
	 *
	 * @access	private
	 * @param 	bool 	show fatal errors instead of returning bool true
	 * @return	bool 	is banned or now
	 */

	private function nation_ban_check($show_error = TRUE)
	{
		if ( ! $this->check_yes(ee()->config->item('require_ip_for_posting')) OR
			 ! $this->check_yes(ee()->config->item('ip2nation')) OR
			 ! ee()->db->table_exists('exp_ip2nation'))
		{
			return FALSE;
		}

		//2.5.2 has a different table and ipv6 support
		if (version_compare($this->ee_version, '2.5.2', '<='))
		{
			ee()->db->select("country");
			ee()->db->where('ip <', ip2long(ee()->input->ip_address()));
			ee()->db->order_by('ip', 'desc');
			$query = ee()->db->get('ip2nation', 1);
		}
		else
		{
			// all IPv4 go to IPv6 mapped
			$addr = ee()->input->ip_address();

			if (strpos($addr, ':') === FALSE &&
				strpos($addr, '.') !== FALSE)
			{
				$addr = '::'.$addr;
			}

			$addr = inet_pton($addr);
			$addr = ee()->db->escape_str($addr);

			$query = ee()->db
				->select('country')
				->where("ip_range_low <= '{$addr}'", '', FALSE)
				->where("ip_range_high >= '{$addr}'", '', FALSE)
				->order_by('ip_range_low', 'desc')
				->limit(1, 0)
				->get('ip2nation');
		}

		if ($query->num_rows() == 1)
		{
			$ip2_query = ee()->db->get_where(
				'ip2nation_countries',
				array(
					'code'		=> $query->row('country'),
					'banned'	=> 'y'
				)
			);

			if ($ip2_query->num_rows() > 0)
			{
				if ($show_error == TRUE)
				{
					return ee()->output->fatal_error(
						ee()->config->item('ban_message'),
						0
					);
				}
				else
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}
	//END nation_ban_check


	// --------------------------------------------------------------------

	/**
	 * Parse Numeric Array Param
	 *
	 * checks template param for item like 'not 1|2|3'
	 *
	 * @access	private
	 * @param 	string 	name of param to parse
	 * @return	mixed	false if not set, array if set
	 */

	private function parse_numeric_array_param($name = '')
	{
		$return = array();

		if (trim($name) == '')
		{
			return FALSE;
		}

		$name_id = ee()->TMPL->fetch_param($name);

		if ($name_id == FALSE)
		{
			return FALSE;
		}

		$name_id 	= trim(strtolower($name_id));
		$not 		= FALSE;

		if (substr($name_id, 0, 3) == 'not')
		{
			$not 		= TRUE;
			$name_id 	= preg_replace('/^not[\s]*/', '', $name_id);
		}

		$clean_ids = array();

		if ($name_id !== '')
		{
			$name_id = str_replace(
				'CURRENT_USER',
				ee()->session->userdata('member_id'),
				$name_id
			);

			if (stristr($name_id, '|'))
			{
				$name_id = $this->lib('Utils')->pipe_split($name_id);
			}

			if ( ! is_array($name_id))
			{
				$name_id = array($name_id);
			}

			foreach ($name_id as $value)
			{
				$value = trim($value);

				if ($this->is_positive_intlike($value))
				{
					$clean_ids[] = $value;
				}
			}
		}

		return array(
			'not' 	=> $not,
			'ids'	=> $clean_ids
		);
	}
	//END parse_numeric_array_param


	// --------------------------------------------------------------------

	/**
	 * Tag Prefix replace
	 *
	 * Takes a set of tags and removes unprefixed tags then removes the
	 * prefixes from the prefixed ones. Sending reverse true re-instates the
	 * unprefixed items
	 *
	 * @param  string  $prefix  prefix for tags
	 * @param  array   $tags    array of tags to look for prefixes with
	 * @param  string  $tagdata incoming tagdata to replace on
	 * @param  boolean $reverse reverse the replacements?
	 * @return string  tagdata with replacements
	 */

	public function tag_prefix_replace($prefix = '', $tags = array(),
										$tagdata = '', $reverse = FALSE)
	{
		if ($prefix == '' OR ! is_array($tags) OR empty($tags))
		{
			return $tagdata;
		}

		//allowing ':' in a prefix
		if (substr($prefix, -1, 1) !== ':')
		{
			$prefix = rtrim($prefix, '_') . '_';
		}


		$hash 	= '02be645684a54f45f08d0b1dbadf78e1a3a9f2ee';

		$find 			= array();
		$hash_replace 	= array();
		$prefix_replace = array();

		$length = count($tags);

		foreach ($tags as $key => $item)
		{
			$nkey = $key + $length;

			//if there is nothing prefixed, we don't want to do anything datastardly
			if ( ! $reverse AND
				strpos($tagdata, LD . $prefix . $item) === FALSE)
			{
				continue;
			}

			//this is terse, but it ensures that we
			//find any an all tag pairs if they occur
			$find[$key] 			= $item;
			$find[$nkey] 			= '/' .  $item;
			$hash_replace[$key] 	= $hash . $item;
			$hash_replace[$nkey] 	= '/' .  $hash . $item;
			$prefix_replace[$key] 	= $prefix . $item;
			$prefix_replace[$nkey] 	= '/' .  $prefix . $item;
		}

		//prefix standard and replace prefixes
		if ( ! $reverse)
		{
			foreach ($find as $key => $value)
			{
				$tagdata = preg_replace(
					'/(?<![:_])\b(' . preg_quote($value, '/') . ')\b(?![:_])/ms',
					$hash_replace[$key],
					$tagdata
				);
			}

			foreach ($prefix_replace as $key => $value)
			{
				$tagdata = preg_replace(
					'/(?<![:_])\b(' . preg_quote($value, '/') . ')\b(?![:_])/ms',
					$find[$key],
					$tagdata
				);
			}

			//$tagdata = str_replace($find, $hash_replace, $tagdata);
			//$tagdata = str_replace($prefix_replace, $find, $tagdata);
		}
		//we are on the return, fix the hashed ones
		else
		{
			//$tagdata = str_replace($hash_replace, $find, $tagdata);
			foreach ($hash_replace as $key => $value)
			{
				$tagdata = preg_replace(
					'/(?<![:_])\b(' . preg_quote($value, '/') . ')\b(?![:_])/ms',
					$find[$key],
					$tagdata
				);
			}
		}

		return $tagdata;
	}
	//END tag_prefix_replace


	// --------------------------------------------------------------------

	/**
	 * Checks first for an error block if present
	 *
	 * @access protected
	 * @param  string	$line	error line
	 * @return string			parsed html tagdata
	 */

	protected function no_results_error($line = '')
	{
		$opener		= LD . 'if ';
		$closer		= LD . '/if' . RD;
		$q_closer	= preg_quote($closer, '/');
		$tagdata	= ee()->TMPL->tagdata;

		if ($line != '' AND
			preg_match(
				"/". $opener . preg_quote($this->lower_name) . ":error" .
					RD."(.*?)". $q_closer ."/s",
				$tagdata,
				$match
			)
		)
		{
			//This can have some nested if statements so we need to
			//make sure that we have balanced {if} blocks
			$td			= $match[1];
			$openers	= substr_count($td, $opener);
			$closers	= substr_count($td, $closer);

			//nested IFs?
			if ($openers && $openers > $closers)
			{
				$loop = 0;

				while ($openers != $closers)
				{
					//protection
					if ($loop++ > 500)
					{
						break;
						return ee()->TMPL->no_results();
					}

					$sub_td = substr($tagdata, strpos($tagdata, $td) + strlen($td));
					$td = $td . substr(
						$sub_td,
						strpos($sub_td, $closer),
						strpos($sub_td, $closer) + strlen($closer)
					);

					$openers	= substr_count($td, $opener);
					$closers	= substr_count($td, $closer);
				}
			}

			$error_tag = $this->lower_name . ":error";

			return ee()->TMPL->parse_variables(
				$td,
				array(array(
					$error_tag		=> $line,
					'error_message' => lang($line)
				))
			);
		}
		else if ( preg_match(
				"/". $opener .preg_quote($this->lower_name).":no_results" .
					RD."(.*?)". $q_closer ."/s",
				$tagdata,
				$match
			)
		)
		{
			return $match[1];
		}
		else
		{
			return ee()->TMPL->no_results();
		}
	}
	//END no_results_error


	// --------------------------------------------------------------------

	/**
	 * Replaces CURRENT_USER in tag params
	 *
	 * @access	protected
	 * @return	null
	 */

	protected function replace_current_user()
	{
		if (isset(ee()->TMPL) AND is_object(ee()->TMPL))
		{
			foreach (ee()->TMPL->tagparams as $key => $value)
			{
				if (stristr($value, 'CURRENT_USER'))
				{
					ee()->TMPL->tagparams[$key] = preg_replace(
						'/(?<![:_])\b(CURRENT_USER)\b(?![:_])/ms',
						ee()->session->userdata('member_id'),
						$value
					);
				}
			}
		}
	}
	//END replace_current_user


	


	// --------------------------------------------------------------------

	/**
	 * Replace all form fields tags in the {freeform:all_form_fields} loop
	 *
	 * @access	protected
	 * @param	string	$tagdata			incoming tagdata
	 * @param	array	$fields				field_id => field_data array
	 * @param	array	$field_order		order of field by field id (optional)
	 * @param	array	$field_input_data	field input data (optional)
	 * @return	string						transformed output data
	 */

	protected function replace_all_form_fields($tagdata,
												$fields,
												$field_order = array(),
												$field_input_data = array())
	{
		// -------------------------------------
		//	all form fields loop
		// -------------------------------------
		//	this can be used to build normal output
		//	or custom output for edit.
		// -------------------------------------

		if (preg_match_all(
			'/' . LD . 'freeform:all_form_fields.*?' . RD .
				'(.*?)' .
			LD . '\/freeform:all_form_fields' . RD . '/ms',
			$tagdata,
			$matches,
			PREG_SET_ORDER
		))
		{
			$all_field_replace_data = array();

			$field_loop_ids = array_keys($fields);

			// -------------------------------------
			//	order ids?
			// -------------------------------------

			if ( ! is_array($field_order) AND is_string($field_order))
			{
				$field_order = $this->lib('Utils')->pipe_split($field_order);
			}

			$order_ids = array();

			if (is_array($field_order))
			{
				$order_ids = array_filter($field_order, array($this, 'is_positive_intlike'));
			}

			if ( ! empty($order_ids))
			{
				//this makes sure that any fields in 'fields' are in the
				//order set as well. Will add missing at the end like this
				$field_loop_ids = array_merge(
					$order_ids,
					array_diff($field_loop_ids, $order_ids)
				);
			}

			//build variables



			foreach ($field_loop_ids as $field_id)
			{

				if ( ! isset($fields[$field_id]))
				{
					continue;
				}

				$field_data = $fields[$field_id];

				// -------------------------------------
				//	get previous data
				// -------------------------------------

				$col_name = $this->model('form')->form_field_prefix . $field_id;

				$display_field_data = '';

				if (isset($field_input_data[$field_data['field_name']]))
				{
					$display_field_data = $field_input_data[$field_data['field_name']];
				}
				else if (isset($field_input_data[$col_name]))
				{
					$display_field_data = $field_input_data[$col_name];
				}

				// -------------------------------------
				//	load field data
				// -------------------------------------

				$all_field_replace_data[] = array(
					'freeform:field_id'		=> $field_id,
					'freeform:field_data'	=> $display_field_data,
					'freeform:field_name'	=> $field_data['field_name'],
					'freeform:field_type'	=> $field_data['field_type'],
					'freeform:field_label'	=> LD . 'freeform:label:' .
												$field_data['field_name'] . RD,
					'freeform:field_output'	=> LD . 'freeform:field:' .
												$field_data['field_name'] . RD,
					'freeform:field_description'	=> LD . 'freeform:description:' .
												$field_data['field_name'] . RD
				);
			}

			foreach ($matches as $match)
			{
				$tagdata_replace = ee()->TMPL->parse_variables(
					$match[1],
					$all_field_replace_data
				);

				$tagdata = str_replace($match[0], $tagdata_replace, $tagdata);
			}
		}

		return $tagdata;
	}
	//END replace_all_form_fields


	// --------------------------------------------------------------------

	/**
	 * Parse Status Tags
	 *
	 * Parses:
	 * 	 	{freeform:statuses status="not closed|open"}
	 *			{status_name} {status_value}
	 *		{/freeform:statuses}
	 *
	 * @access	protected
	 * @param	string $tagdata	tagdata to be parsed
	 * @return	string			adjusted tagdata with status pairs parsed
	 */

	protected function parse_status_tags ($tagdata)
	{
		$matches 	= array();
		$tag		= 'freeform:statuses';
		$statuses	= $this->model('preference')->get_form_statuses();

		preg_match_all(
			'/' . LD . $tag . '.*?' . RD .
				'(.*?)' .
			LD . '\/' . $tag . RD . '/ms',
			$tagdata,
			$matches,
			PREG_SET_ORDER
		);

		if ($matches AND
			isset($matches[0]) AND
			! empty($matches[0]))
		{
			foreach ($matches as $key => $value)
			{
				$replace_with	= '';
				$tdata			= $value[1];

				//no need for an if. if we are here, this matched before
				preg_match(
					'/' . LD . $tag . '.*?' . RD . '/',
					$value[0],
					$sub_matches
				);

				// Checking for variables/tags embedded within tags
				// {exp:channel:entries channel="{master_channel_name}"}
				if (stristr(substr($sub_matches[0], 1), LD) !== FALSE)
				{
					$sub_matches[0] = ee()->functions->full_tag(
						$sub_matches[0],
						$value[0]
					);

					// -------------------------------------
					//	fix local tagdata
					// -------------------------------------

					preg_match(
						'/' . preg_quote($sub_matches[0]) .
							'(.*?)' .
						LD . '\/' . $tag . RD . '/ms',
						$value[0],
						$tdata_matches
					);

					if (isset($tdata_matches[1]))
					{
						$tdata = $tdata_matches[1];
					}
				}

				$tag_params = ee()->functions->assign_parameters(
					$sub_matches[0]
				);

				$out_status = $statuses;

				if (isset($tag_params['status']))
				{
					$names	= strtolower($tag_params['status']);
					$not	= FALSE;

					if (preg_match("/^not\s+/s", $names))
					{
						$names	= preg_replace('/^not\s+/s', '', $names);
						$not	= TRUE;
					}

					$names = preg_split(
						'/\|/s',
						trim($names),
						-1,
						PREG_SPLIT_NO_EMPTY
					);

					foreach ($out_status as $status_name => $status_value)
					{
						if (in_array(strtolower($status_name), $names) == $not)
						{
							unset($out_status[$status_name]);
						}
					}
				}

				foreach ($out_status as $out_name => $out_value)
				{
					$replace_with .= str_replace(
						array(LD . 'status_name' . RD, LD . 'status_label' . RD),
						array($out_name, $out_value),
						$tdata
					);
				}

				//remove
				$tagdata = str_replace($value[0], $replace_with, $tagdata);
			}
		}

		return $tagdata;
	}
	//END parse_status_tags


	// --------------------------------------------------------------------

	/**
	 * Runs each fields 'prep_multi_item_data' so that things like 'cat|~|dog'
	 * dont show publicly
	 *
	 * @access	public
	 * @param	string	$data		incoming data to parse
	 * @param	string	$field_type	fieldtype data is from
	 * @return	string				result of prepping multiitem data
	 */

	public function prep_multi_item_data($data = '', $field_type = 'text')
	{
		//nothing to do if its empty, yo
		if ( ! $data)
		{
			return $data;
		}

		$instance =& $this->lib('Fields')->get_fieldtype_instance(
			$field_type
		);

		$result = $instance->prep_multi_item_data($data);

		//eh, bad dog
		if ( ! $result)
		{
			return $data;
		}

		//array?
		if ( ! is_string($result))
		{
			return implode("\n", (array) $result);
		}

		return $result;
	}
	//END prep_multi_item_data


	// --------------------------------------------------------------------

	/**
	 * Replace Submit buttons with args
	 *
	 * @access public
	 * @param	array  $options	input options
	 * @return	string			parsed tagdata
	 */

	public function replace_submit($options = array())
	{
		$defaults = array(
			'pre_args'	=> array(),
			'post_args'	=> array(),
			'tagdata'	=> '',
			'tag'		=> '',
			'remove'	=> false
		);

		//set vars
		foreach($defaults as $key => $value)
		{
			$$key = (isset($options[$key])) ? $options[$key] : $value;
		}

		if (preg_match_all(
				"/" . LD . preg_quote($tag, '/') . "\b(.*?)" . RD . "/ims",
				$tagdata,
				$matches
			)
		)
		{
			$total_matches 	= count($matches[0]);

			for ($i = 0; $i < $total_matches; $i++)
			{

				// Checking for variables/tags embedded within tags
				// {exp:channel:entries channel="{master_channel_name}"}
				if (stristr(substr($matches[0][$i], 1), LD) !== FALSE)
				{
					$matches[0][$i] = ee()->functions->full_tag($matches[0][$i], $tagdata);
				}

				//if we aren't dealing with just random space
				if (trim($matches[1][$i]) != '')
				{
					$args = ee()->functions->assign_parameters($matches[1][$i]);

					$attr = array();

					if ($args)
					{
						foreach ($args as $key => $value)
						{
							if (substr($key, 0, 5) == 'attr:')
							{
								$attr[substr($key, 5)] = $value;
							}
						}
					}

					//wont work without the name being correct
					$args = array_merge(
						$pre_args,
						$attr,
						$post_args
					);

					if ($remove)
					{
						$tagdata = str_replace(
							$matches[0][$i],
							'',
							$tagdata
						);
					}
					else
					{
						$tagdata = str_replace(
							$matches[0][$i],
							form_submit($args),
							$tagdata
						);
					}
				}
				//END if trim
			}
			//END for
		}
		//END preg_match_all

		return $tagdata;
	}
	//END replace_submit


	// --------------------------------------------------------------------

	/**
	 * Do Exit
	 *
	 * Helper for unit tests so PHP exit isn't fired in the middle of it
	 *
	 * @access protected
	 * @return void
	 */

	protected function do_exit()
	{
		if ($this->test_mode)
		{
			return;
		}
		else
		{
			exit();
		}
	}
	//END do_exit
}
// END CLASS Freeform
