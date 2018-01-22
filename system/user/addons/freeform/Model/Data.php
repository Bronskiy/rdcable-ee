<?php

namespace Solspace\Addons\Freeform\Model;

use Solspace\Addons\Freeform\Library\AddonBuilder;
use Solspace\Addons\Freeform\Library\Cacher;

class Data extends AddonBuilder
{
	public $cached						= array();

	//various default values for backend
	public $defaults					= array(
		//MCP
		'mcp_row_limit' 			=> 50,
		//fields
		'field_length'				=> 150,
		//notifications
		'wordwrap'					=> 'y',
		'allow_html'				=> 'n',
		'default_fields'			=> array(
			
			"file_upload",
			"text",
			"textarea"
		)
	);

	//cannot be used in form or field names
	public $prohibited_names			= array(
		'all_form_fields',
		'attachment_count',
		'attachments',
		'author',
		'author_id',
		'channel_id',
		'complete',
		'edit_date',
		'entry_date',
		'entry_id',
		'form_label',
		'form_name',
		'form_id',
		'freeform_entry_id',
		'group_id',
		'hash_stored_data',
		'ip_address',
		'status',
		'template',
	);

	//default pref values with type of input
	public $default_preferences;
	//default pref values with type of input
	public $default_global_preferences;
	public $admin_only_prefs;
	public $msm_only_prefs;


	public $standard_notification_tags	= array(
		'all_form_fields'			=> "{all_form_fields}\n\t{field_label}\n\t{field_data}\n{/all_form_fields}",
		'all_form_fields_string'	=> '{all_form_fields_string}',
		'freeform_entry_id'			=> '{freeform_entry_id}',
		'entry_date'				=> '{entry_date format=&quot;%Y-%m-%d - %H:%i&quot;}',
		'form_name'					=> '{form_name}',
		'form_label'				=> '{form_label}',
		'form_id'					=> '{form_id}',
		'attachments'				=> "{attachments}\n\t{fileurl}\n\t{filename}\n{/attachments}",
		'attachment_count'			=> '{attachment_count}'
	);

	public $msm_enabled					= FALSE;

	public $allowed_html_tags			= array(
		'p','br','a','strong','b','i','em',
		'dl','dd','dt','ul','ol','li', 'a',
		'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
		'address'
	);

	public $doc_links;


	// --------------------------------------------------------------------

	/**
	 * __construct
	 *
	 * @access public
	 */

	public function __construct()
	{
		parent::__construct();

		//moved these
		$this->admin_only_prefs				= $this->model('preference')->admin_only_prefs;
		$this->msm_only_prefs				= $this->model('preference')->msm_only_prefs;
		$this->doc_links					= $this->addon_info['doc_links'];
		$this->msm_enabled					= $this->model('preference')->msm_enabled;
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * get_form_submissions_count
	 *
	 * returns the total submissions for the entry table
	 *
	 * @access	public
	 * @param 	int 	form id
	 * @return	int  	total table count
	 */

	public function get_form_submissions_count ($form_id)
	{
		if ( ! $this->model('preference')->show_all_sites())
		{
			$this->model('entry')->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		//set cache and return
		return $this->model('entry')->id($form_id)->count();
	}
	//END get_form_submissions_count


	// --------------------------------------------------------------------

	/**
	 * get_form_needs_moderation_count
	 *
	 * returns the total items needing moderation
	 *
	 * @access	public
	 * @param 	int 	form id
	 * @return	int  	total moderation count
	 */

	public function get_form_needs_moderation_count ($form_id)
	{
		if ( ! $this->model('preference')->show_all_sites())
		{
			$this->model('entry')->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		return $this->model('entry')->id($form_id)->count(
			array('status' => 'pending')
		);
	}
	//END get_form_needs_moderation_count


	// --------------------------------------------------------------------

	/**
	 * is_valid_entry_id
	 *
	 * @access	public
	 * @param	int 	id of field to check
	 * @param	int 	id of form to check
	 * @return	bool 	is valid id
	 */

	public function is_valid_entry_id ( $entry_id = 0, $form_id = 0)
	{
		//proper INTs?
		if ( ! $this->is_positive_intlike($entry_id) OR
			 ! $this->model('form')->valid_id($form_id))
		{
			return FALSE;
		}

		return ($this->model('entry')->id($form_id)->count($entry_id) > 0);
	}
	// END is_valid_entry_id()


	// --------------------------------------------------------------------

	/**
	 * get_form_id_by_name
	 *
	 * @access	public
	 * @param	string 	name of field the id is desired for
	 * @param	bool 	use cache
	 * @return	mixed 	name of form or FALSE
	 */

	public function get_form_id_by_name ( $form_name = '')
	{
		if ( trim($form_name) == ''){ return FALSE;	}



		$row = $this->model('form')->select('form_id')
										->get_row(array('form_name' => $form_name));

		return ($row ? $row['form_id'] : FALSE);
	}
	// END get_form_id_by_name()


	// --------------------------------------------------------------------

	/**
	 * get entry data by id
	 *
	 * @access	public
	 * @param	int  	entry id is desired for
	 * @param 	int 	form_id desired
	 * @param	bool 	use cache
	 * @return	mixed 	name of form or FALSE
	 */

	public function get_entry_data_by_id ( $entry_id = 0, $form_id = 0, $use_cache = TRUE )
	{
		//valid INTs?
		if ( ! $this->is_positive_intlike($entry_id) OR
			 ! $this->model('form')->valid_id($form_id))
		{
			return FALSE;
		}



		return $this->model('entry')->id($form_id)->get_row($entry_id);
	}
	// END get_entry_data_by_id


	// --------------------------------------------------------------------

	/**
	 * get_form_info_by_notification_id
	 *
	 * @access	public
	 * @param	number 	id of field that info is needed for
	 * @return	array 	data of forms by id
	 */

	public function get_form_info_by_notification_id ( $notification_id = 0)
	{
		if ( ! $this->is_positive_intlike($notification_id)){ return FALSE; }

		// -------------------------------------
		//  get form info
		// -------------------------------------



		$query =	$this->model('form')
						->key('form_id')
						->where('user_notification_id', $notification_id)
						->or_where('admin_notification_id', $notification_id)
						->get();

		return $query;
	}
	// END get_form_info_by_notification_id()
}
// END CLASS
