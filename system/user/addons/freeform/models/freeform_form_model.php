<?php

if ( ! class_exists('Freeform_Model'))
{
	require_once 'freeform_model.php';
}

class Freeform_form_model extends Freeform_Model
{
	public $custom_field_info 			= array(
		'type'		=> 'TEXT',
		'null'		=> TRUE
	);

	public $default_form_table_columns = array(
		'entry_id'			=> array(
			'type'				=> 'INT',
			'constraint'		=> 10,
			'unsigned'			=> TRUE,
			'auto_increment'	=> TRUE
		),
		'site_id'			=> array(
			'type'				=> 'INT',
			'constraint'		=> 10,
			'unsigned'			=> TRUE,
			'null'				=> FALSE,
			'default'			=> 1
		),
		'author_id'			=> array(
			'type'				=> 'INT',
			'constraint'		=> 10,
			'unsigned'			=> TRUE,
			'null'				=> FALSE,
			'default'			=> 0
		),
		'complete'			=> array(
			'type'				=> 'VARCHAR',
			'constraint'		=> 1,
			'null'				=> FALSE,
			'default'			=> 'y'
		),
		'ip_address'		=> array(
			'type'				=> 'VARCHAR',
			'constraint'		=> 40,
			'null'				=> FALSE,
			'default'			=> 0
		),
		'entry_date'		=> array(
			'type'				=> 'INT',
			'constraint'		=> 10,
			'unsigned'			=> TRUE,
			'null'				=> FALSE,
			'default'			=> 0
		),
		'edit_date'			=> array(
			'type'				=> 'INT',
			'constraint'		=> 10,
			'unsigned'			=> TRUE,
			'null'				=> FALSE,
			'default'			=> 0
		),
		'status'			=> array(
			'type'				=> 'VARCHAR',
			'constraint'		=> 50
		),
	);

	public $_default_inputs = array(
		//hidden
		'form_id'					=> array(
			'label'		=> '',
			'desc'		=> '',
			'type'		=> 'hidden',
			'default'	=> '0',
			'validate'	=> '',
		),
		'duplicate_id'				=> array(
			'label'		=> '',
			'desc'		=> '',
			'type'		=> 'hidden',
			'default'	=> '0',
			'validate'	=> '',
		),
		'template_id'				=> array(
			'label'		=> '',
			'desc'		=> '',
			'type'		=> 'hidden',
			'default'	=> '0',
			'validate'	=> '',
		),
		'composer_id'				=> array(
			'label'		=> '',
			'desc'		=> '',
			'type'		=> 'hidden',
			'default'	=> '0',
			'validate'	=> '',
		),
		'field_ids'					=> array(
			'label'		=> '',
			'desc'		=> '',
			'type'		=> 'hidden',
			'default'	=> '',
			'validate'	=> '',
		),
		'field_order'				=> array(
			'label'		=> '',
			'desc'		=> '',
			'type'		=> 'hidden',
			'default'	=> '',
			'validate'	=> '',
		),
		'ret'				=> array(
			'label'		=> '',
			'desc'		=> '',
			'type'		=> 'hidden',
			'default'	=> 'forms',
			'validate'	=> '',
		),


		'form_type'					=> array(
			'label'		=> 'form_type',
			'desc'		=> '',
			'type'		=> 'select',
			'choices'	=> array(),
			'default'	=> 'template',
			'validate'	=> '',
		),
		'form_label'				=> array(
			'label'		=> 'form_label',
			'desc'		=> 'form_label_desc',
			'type'		=> 'text',
			'default'	=> '',
			'validate'	=> '',
			'required'	=> true
		),
		'form_name'					=> array(
			'label'		=> 'form_name',
			'desc'		=> 'form_name_desc',
			'type'		=> 'text',
			'default'	=> '',
			'validate'	=> '',
			'required'	=> true,
		),
		'form_description'			=> array(
			'label'		=> 'description',
			'desc'		=> 'form_description_desc',
			'type'		=> 'textarea',
			'default'	=> '',
			'validate'	=> '',
		),
		'default_status'			=> array(
			'label'		=> 'default_status',
			'desc'		=> 'default_status_desc',
			'type'		=> 'select',
			'choices'	=> array(),
			'default'	=> '',
			'validate'	=> '',
		),
		'notify_user'				=> array(
			'label'		=> 'notify_user',
			'desc'		=> 'notify_user_desc',
			'type'		=> 'yes_no',
			'default'	=> 'n',
			'validate'	=> '',
		),
		'user_email_field'			=> array(
			'label'		=> 'user_email_field',
			'desc'		=> 'user_email_field_desc',
			'type'		=> 'hidden',
			'default'	=> '',
			'validate'	=> '',
		),
		'user_notification_id'		=> array(
			'label'		=> 'user_notification',
			'desc'		=> 'user_notification_desc',
			'type'		=> 'select',
			'choices'	=> array(),
			'default'	=> '',
			'validate'	=> '',
		),
		'notify_admin'				=> array(
			'label'		=> 'notify_admin',
			'desc'		=> 'notify_admin_desc',
			'type'		=> 'yes_no',
			'default'	=> 'n',
			'validate'	=> '',
		),
		'admin_notification_id'		=> array(
			'label'		=> 'admin_notification',
			'desc'		=> 'admin_notification_desc',
			'type'		=> 'select',
			'choices'	=> array(),
			'default'	=> '',
			'validate'	=> '',
		),
		'admin_notification_email'	=> array(
			'label'		=> 'admin_notification_email',
			'desc'		=> 'admin_notification_email_desc',
			'type'		=> 'text',
			'default'	=> '',
			'validate'	=> '',
		),
		'form_fields'				=> array(
			'label'		=> 'form_fields',
			'desc'		=> 'form_fields_desc',
			'type'		=> 'html',
			'content'	=> '',
			'default'	=> '',
			'validate'	=> '',
		),
        'entry_url'	=> array(
            'label'		=> 'entry_url',
            'desc'		=> 'entry_url_desc',
            'type'		=> 'text',
            'default'	=> '',
            'validate'	=> '',
        ),
	);


	public $default_form_status	= 'pending';


	//crud listeners
	public $after_get			= array('parse_form_strings');

	public $before_insert		= array('before_insert_add');
	public $after_insert		= array('create_form_table');

	public $before_update		= array('before_update_add');
	public $after_update		= array('create_form_table');

	public $after_delete		= array('remove_entry_tables');


	// --------------------------------------------------------------------

	/**
	 * Input Defaults
	 *
	 * @access	public
	 * @return	array		array of input information
	 */

	public function input_defaults()
	{
		$out = $this->_default_inputs;

		$out['default_status']['default']			= $this->default_form_status;
		$out['default_status']['choices']			= $this->model('preference')->get_form_statuses();
		$out['admin_notification_email']['default']	= ee()->config->item('webmaster_email');

		// -------------------------------------
		//	form type choices
		// -------------------------------------

		if ($this->addon_info['freeform_pro'])
		{
			$out['form_type']['choices']			= array(
				'template'	=> lang('template'),
				'composer'	=> lang('composer')
			);
			$out['form_type']['default']			= 'composer';
			$out['form_type']['validate']			= 'enum[template,composer]';
		}
		else
		{
			$out['form_type']['type']				= 'hidden';
		}

		// -------------------------------------
		//	user email fields
		// -------------------------------------

		$f_rows =	$this->model('field')
						->select('field_id, field_label, settings')
						->get(array('field_type' => 'text'));

		//we only want fields that are being validated as email
		if ($f_rows)
		{
			$user_email_fields = array('' => lang('choose_user_email_field'));

			foreach ($f_rows as $row)
			{
				$row_settings = json_decode($row['settings'], TRUE);
				$row_settings = (is_array($row_settings)) ? $row_settings : array();

				if (isset($row_settings['field_content_type']) AND
					$row_settings['field_content_type'] == 'email')
				{
					$user_email_fields[$row['field_id']] = $row['field_label'];
				}
			}

			$out['user_email_field']['type']		= 'select';
			$out['user_email_field']['choices']		= $user_email_fields;
			$out['user_email_field']['validate']	= 'enum[' . implode(',', array_keys($user_email_fields)) . ']';
		}
		else
		{
			$out['user_email_field']['type']		= 'html';
			$out['user_email_field']['content']		= '';
		}

		// -------------------------------------
		//	notifications
		// -------------------------------------

		$notification_templates = $this->model('notification')->get_available();
		$notification_validate	= 'enum[' . implode(',', array_keys($notification_templates)) . ']';

		$out['admin_notification_id']['choices']	= $notification_templates;
		$out['user_notification_id']['choices']		= $notification_templates;
		$out['admin_notification_id']['validate']	= $notification_validate;
		$out['user_notification_id']['validate']	= $notification_validate;

		return $out;
	}
	//end input_defaults


	// --------------------------------------------------------------------

	/**
	 * valid_id
	 *
	 * @access	public
	 * @param	int 	id of form to check
	 * @return	bool 	is valid id
	 */

	public function valid_id($form_id = 0)
	{
		return ($this->isolate()->count($form_id) > 0);
	}
	// END valid_id()


	// --------------------------------------------------------------------

	/**
	 * get_form_info
	 *
	 * @access	public
	 * @param	number 	id of form that info is needed for
	 * @return	array 	data from form
	 */

	public function get_info($form_id = 0)
	{
		$data = $this->get_row($form_id);

		if (empty($data))
		{
			return FALSE;
		}

		//get all associated fields and add in
		if ( ! empty($data['field_ids']) )
		{
			$field_data = $this->model('field')->get(array(
				'field_id' => $data['field_ids']
			));

			//get fields
			if ( ! empty($field_data))
			{
				foreach ($field_data as $row)
				{
					$data['fields'][$row['field_id']] = $row;
				}
			}
			else
			{
				$data['fields'] = array();
			}
		}
		else
		{
			$data['fields'] = array();
		}

		//get all associated composer fields and add in
		$data['composer_field_ids']	= ''; $data['composer_fields']	= array();

		if ( ! empty($data['composer_id']) )
		{

			$cdata = $this->model('composer')->get_row($data['composer_id']);

			if ( ! empty( $cdata['composer_data'] ) )
			{
				$fields	= (array) json_decode( $cdata['composer_data'] );

				if ( ! empty( $fields['fields'] ) )
				{
					$data['composer_field_ids']	= $fields['fields'];

					sort($data['composer_field_ids']);

					$find = array_diff($data['composer_field_ids'], array_keys($data['fields']));

					$c_fields = array();

					if ( ! empty($find))
					{
						$c_field_data = $this->model('field')->get(array(
							'field_id' => $find
						));

						if ($c_field_data !== FALSE)
						{
							foreach ($c_field_data as $row)
							{
								$c_fields[$row['field_id']] = $row;
							}
						}
					}

					//set fields
					foreach ($data['composer_field_ids'] as $field_id)
					{
						if (isset($data['fields'][$field_id]))
						{
							$data['composer_fields'][$field_id] =& $data['fields'][$field_id];
						}
						else if (isset($c_fields[$field_id]))
						{
							$data['composer_fields'][$field_id] = $c_fields[$field_id];
						}
					}

					unset($c_fields);
				}
			}
		}

		return $data;
	}
	// END get_form_info()


	// --------------------------------------------------------------------

	/**
	 * Parse Form Strings
	 *
	 * decodes settings and unpipes field ids
	 *
	 * @access protected
	 * @param  $data 	array  array of data rows
	 * @param  $all 	boolean all?
	 * @return array       affected data rows
	 */

	protected function parse_form_strings ($data, $all)
	{
		foreach ($data as $k => $row)
		{
			if (isset($row['settings']) AND $row['settings'] !== NULL)
			{
				$settings = json_decode($row['settings'], TRUE);

				$data[$k]['settings'] = (is_array($settings)) ? $settings : array();
			}

			if (isset($data[$k]['field_ids']) AND $data[$k]['field_ids'] !== NULL)
			{
				$data[$k]['field_ids'] 	= $this->pipe_split($row['field_ids']);
			}
		}

		return $data;
	}
	//END parse_form_strings


	// --------------------------------------------------------------------

	/**
	 * Before insert add
	 *
	 * adds site id and entry date to insert data
	 *
	 * @access protected
	 * @param  array $data array of insert data
	 * @return array       affected data
	 */

	protected function before_insert_add ($data)
	{
		if ( ! isset($data['entry_date']))
		{
			$data['entry_date'] = ee()->localize->now;
		}

		if ( ! isset($data['site_id']))
		{
			$data['site_id'] = ee()->config->item('site_id');
		}

		return $data;
	}
	//END before_insert_add


	// --------------------------------------------------------------------

	/**
	 * Before update add
	 *
	 * adds site id and edit date to update data
	 *
	 * @access protected
	 * @param  array $data array of update data
	 * @return array       affected data
	 */

	protected function before_update_add ($data)
	{
		if ( ! isset($data['edit_date']))
		{
			$data['edit_date'] = ee()->localize->now;
		}

		return $data;
	}
	//END before_update_add


	// --------------------------------------------------------------------

	/**
	 * Remove Entry Tables
	 *
	 * Removes a form and its corosponding entries table
	 *
	 * @access	protected
	 * @param 	array 	$form_ids ids of form entry tables to delete
	 * @return  null
	 */

	protected function remove_entry_tables ($form_ids)
	{
		ee()->load->dbforge();

		if ( ! is_array($form_ids))
		{
			if ( ! $this->is_positive_intlike($form_ids)){ return; }

			$form_ids = array($form_ids);
		}

		foreach ($form_ids as $form_id)
		{
			$table_name = $this->table_name($form_id);

			//need to check else this fails on error :|
			if ($this->db->table_exists($table_name))
			{
				ee()->dbforge->drop_table($table_name);
			}
		}
	}
	//end remove_entry_tables


	// --------------------------------------------------------------------

	/**
	 * table_name
	 *
	 * returns the entris table name of the from id passed
	 *
	 * @access	public
	 * @param 	int 	form id
	 * @return	string  table name with form iD replaced
	 */

	public function table_name ($form_id)
	{
		return str_replace(
			'%NUM%',
			$form_id,
			$this->form_table_nomenclature
		);
	}
	//END table_name


	// --------------------------------------------------------------------

	/**
	 * create_form_table
	 *
	 * @access	public
	 * @param 	int 	form_id number
	 * @return	bool 	success
	 */

	public function create_form_table($form_id)
	{
		if (is_array($form_id))
		{
			$form_id = array_pop($form_id);
		}

		if ( ! $this->is_positive_intlike($form_id)){ return FALSE; }

		ee()->load->dbforge();

		$table_name = $this->table_name($form_id);

		//if the table doesn't exist, lets create it with all of the default fields
		if ( ! $this->db->table_exists($table_name))
		{
			//adds all defaults and sets primary key
			ee()->dbforge->add_field($this->default_form_table_columns);
			ee()->dbforge->add_key('entry_id', TRUE);

			ee()->dbforge->create_table($table_name, TRUE);

			return TRUE;
		}
		//oops!
		//TODO: check for major fields or error differently?
		else
		{
			return FALSE;
		}
	}
	//END create_form_table


	// --------------------------------------------------------------------

	/**
	 * forms_with_field_id
	 *
	 * @access	public
	 * @param	number 		$field_id 	id of field that info is needed for
	 * @param   boolean  	$use_cache	use cache?
	 * @return	array 					data of forms by id
	 */

	public function forms_with_field_id ($field_id, $use_cache = TRUE)
	{
		if ( ! $this->is_positive_intlike($field_id)){ return FALSE; }

		//cache?
		$cache = $this->cacher(func_get_args(), __FUNCTION__);
		if ($use_cache AND $cache->is_set()){ return $cache->get(); }

		// --------------------------------------------
		//  get form info
		// --------------------------------------------

		$field_id 	= $this->db->escape_str($field_id);

		$sql = "SELECT	*
				FROM	exp_freeform_forms
				WHERE	field_ids
				REGEXP	('^$field_id$|^$field_id\\\\||\\\\|$field_id\\\\||\\\\|$field_id$')";

		//e.g. finds '1' in (1, 1|2|3, 3|2|1, 2|1|3) but not in (10|11|12, 10, etc..)
		$query		= $this->db->query($sql);

		$cache->set(FALSE);

		//get form info
		if ($query->num_rows() > 0)
		{
			$cache->set($this->prepare_keyed_result($query->result_array(), 'form_id'));
		}

		return $cache->get();
	}
	//END forms_with_field_id
}
//END Freeform_form_model
