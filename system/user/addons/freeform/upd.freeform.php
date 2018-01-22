<?php

use Solspace\Addons\Freeform\Library\AddonBuilder;

class Freeform_upd extends AddonBuilder
{
	public $module_actions		= array();
	public $hooks				= array();
	public $default_settings	= array();
	private $info				= array();

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
		//  Module Actions
		// --------------------------------------------

		$this->module_actions	= array(
			'save_form'
		);

		// --------------------------------------------
		//  Extension Hooks
		// --------------------------------------------

		$this->hooks	= array();
	}
	// END Freeform_updater_base()


	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */

	public function install()
	{
		// Already installed, let's not install again.
		if ($this->database_version() !== FALSE)
		{
			return FALSE;
		}

		// --------------------------------------------
		//  Our Default Install
		// --------------------------------------------

		if ($this->default_module_install() == FALSE)
		{
			return FALSE;
		}

		// --------------------------------------------
		//  Load default field types
		// --------------------------------------------

		$this->lib('Fields')->install_default_freeform_fields();

		// --------------------------------------------
		//  Install sample fields and form
		// --------------------------------------------

		$this->install_sample_fields_and_form();

		// --------------------------------------------
		//  Module Install
		// --------------------------------------------

		ee()->db->insert(
			'exp_modules',
			array(
				'module_name'			=> $this->class_name,
				'module_version'		=> $this->version,
				'has_publish_fields'	=> 'n',
				'has_cp_backend'		=> 'y'
			)
		);


		$this->model('preference')->insert(array(
			'preference_name' 	=> 'ffp',
			//Changing this won't give you freeform pro for
			//free, it just helps the update routine ;).
			'preference_value'	=> ($this->addon_info['freeform_pro'] === TRUE) ? 'y' : 'n',
			'site_id'			=> '0'
		));

		return TRUE;
	}
	// END install()


	// --------------------------------------------------------------------

	/**
	 * Install sample fields and form
	 *
	 * @access	public
	 * @return	bool
	 */

	public function install_sample_fields_and_form()
	{
		// --------------------------------------------
		//  Get default site id
		// --------------------------------------------

		$query		= ee()->db->select('site_id')
							  ->order_by('site_id')
							  ->get('sites', '1');

		$site_id	= ($query->num_rows() == 0) ? 1: $query->row('site_id');

		// --------------------------------------------
		//  Get default author id
		// --------------------------------------------

		$query		= ee()->db->select('member_id, email')
							  ->where('group_id', '1')
							  ->order_by('member_id')
							  ->get('members', 1);

		$author_id		= ($query->num_rows() == 0) ? 1: $query->row('member_id');
		$author_email	= ($query->num_rows() == 0) ? '': $query->row('email');

		// --------------------------------------------
		//  Field data
		// --------------------------------------------

		$fields	= array(
			'first_name'	=> array(
				//'field_id'		=> '',
				'site_id'		=> $site_id,
				'field_name'	=> 'first_name',
				'field_label'	=> 'First Name',
				'field_type'	=> 'text',
				'settings'		=> array(
					'field_length'			=> 150,
					'field_content_type'	=> 'any'
				),
				'author_id'		=> $author_id,
				'entry_date'	=> ee()->localize->now,
				'required'		=> 'n',
				'submissions_page'	=> 'y',
				'moderation_page'	=> 'y',
				'composer_use'		=> 'y',
				'field_description'	=> 'This field contains the user\'s first name.'
			),
			'last_name'	=> array(
				//'field_id'		=> '',
				'site_id'		=> $site_id,
				'field_name'	=> 'last_name',
				'field_label'	=> 'Last Name',
				'field_type'	=> 'text',
				'settings'		=> array(
					'field_length'			=> 150,
					'field_content_type'	=> 'any'
				),
				'author_id'		=> $author_id,
				'entry_date'	=> ee()->localize->now,
				'required'		=> 'n',
				'submissions_page'	=> 'y',
				'moderation_page'	=> 'y',
				'composer_use'		=> 'y',
				'field_description'	=> 'This field contains the user\'s last name.'
			),
			'email'	=> array(
				//'field_id'		=> '',
				'site_id'		=> $site_id,
				'field_name'	=> 'email',
				'field_label'	=> 'Email',
				'field_type'	=> 'text',
				'settings'		=> array(
					'field_length'			=> 150,
					'field_content_type'	=> 'email'
				),
				'author_id'		=> $author_id,
				'entry_date'	=> ee()->localize->now,
				'required'		=> 'n',
				'submissions_page'	=> 'y',
				'moderation_page'	=> 'y',
				'composer_use'		=> 'y',
				'field_description'	=> 'A basic email field for collecting stuff like an email address.'
			),
			'user_message'	=> array(
				//'field_id'		=> '',
				'site_id'		=> $site_id,
				'field_name'	=> 'user_message',
				'field_label'	=> 'Message',
				'field_type'	=> 'textarea',
				'settings'		=> array(
					'field_ta_rows'			=> 6
				),
				'author_id'		=> $author_id,
				'entry_date'	=> ee()->localize->now,
				'required'		=> 'n',
				'submissions_page'	=> 'y',
				'moderation_page'	=> 'y',
				'composer_use'		=> 'y',
				'field_description'	=> 'This field contains the user\'s message.'
			)
		);

		// --------------------------------------------
		//  Loop and create fields
		// --------------------------------------------



		foreach ($fields as $key => $data)
		{
			$data['settings']	= json_encode($data['settings']);

			$field_ids[$key] = $this->model('field')->insert($data);
		}

		// --------------------------------------------
		//  Form data
		// --------------------------------------------

		$forms	= array(
			'contact'	=>  array(
				'site_id'			=> $site_id,
				'form_name'			=> 'contact',
				'form_label'		=> 'Contact',
				'default_status'	=> 'pending',
				'notify_admin'		=> 'y',
				'admin_notification_id'		=> $author_id,
				'admin_notification_email'	=> $author_email,
				'form_description'	=> 'This is a basic contact form.',
				'field_ids'			=> implode('|', $field_ids),
				'author_id'			=> $author_id,
				'entry_date'		=> ee()->localize->now
			)
		);

		// --------------------------------------------
		//  Create form
		// --------------------------------------------



		$prefix 	= $this->model('form')->form_field_prefix;
		$form_id 	= $this->lib('Forms')->create_form($forms['contact']);

		// --------------------------------------------
		//  Sample entry
		// --------------------------------------------

		$entries	= array(
				'hi'			=> array(
				'site_id'		=> $site_id,
				'author_id'		=> 0,
				'complete'		=> 'y',
				'ip_address'	=> '127.0.0.1',
				'entry_date'	=> ee()->localize->now,
				'status'		=> 'pending',
				$prefix . $field_ids['first_name']		=> 'Jake',
				$prefix . $field_ids['last_name']		=> 'Solspace',
				$prefix . $field_ids['email']			=> 'support@solspace.com',
				$prefix . $field_ids['user_message']	=> 'Welcome to Solspace Freeform! We hope you enjoy using this add-on!'
			)
		);

		ee()->db->insert_batch(
			$this->model('form')->table_name($form_id),
			$entries
		);
	}

	//	End install sample fields and form


	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */

	public function uninstall()
	{
		// Cannot uninstall what does not exist, right?
		if ($this->database_version() === FALSE)
		{
			return FALSE;
		}

		// -------------------------------------
		//  uninstall routine for fieldtypes
		// -------------------------------------



		$installed_fieldtypes = $this->model('fieldtype')->installed_fieldtypes();

		if ($installed_fieldtypes !== FALSE)
		{
			foreach ($installed_fieldtypes as $name => $data)
			{
				$this->lib('Fields')->uninstall_fieldtype($name);
			}
		}

		// -------------------------------------
		//  delete all extra form tables
		// -------------------------------------



		$query = $this->model('form')->select('form_id')->get();

		if ($query)
		{
			foreach ($query as $row)
			{
				$this->model('form')->delete($row['form_id']);
			}
		}

		// -------------------------------------
		//  Delete legacy tables if a migration was done from FF3 to FF4
		// -------------------------------------

		$this->lib('Migration')->uninstall();

		// --------------------------------------------
		//  Default Module Uninstall
		// --------------------------------------------

		if ($this->default_module_uninstall() == FALSE)
		{
			return FALSE;
		}

		return TRUE;
	}

	// END uninstall()


	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */

	public function update()
	{
		$pro_update = (
			$this->model('preference')->global_preference('ffp') !== FALSE AND
			($this->check_yes($this->model('preference')->global_preference('ffp')) !== $this->addon_info['freeform_pro'])
		);

		// --------------------------------------------
		//  Default Module Update
		// --------------------------------------------

		$this->default_module_update();

		// --------------------------------------------
		// rename legacy (<=3.x) tables before we install new sql
		// --------------------------------------------

		if (version_compare($this->database_version(), '4.0.0', '<'))
		{
			$this->lib('Migration')->upgrade_rename_tables();
		}

		// --------------------------------------------
		//  Default Module Install
		// --------------------------------------------

		//all db tables should have create if not exists, so this is safe
		$this->install_module_sql();

		// -------------------------------------
		//  insert / update default fields
		// -------------------------------------

		//if we are coming from 3.x we need to install some defaults
		if (version_compare($this->database_version(), '4.0.0', '<'))
		{
			$this->lib('Fields')->install_default_freeform_fields();

			$this->model('preference')->insert(array(
				'preference_name' 	=> 'ffp',
				'preference_value'	=> ($this->addon_info['freeform_pro'] === TRUE) ? 'y' : 'n',
				'site_id' 			=> '0'
			));
		}
		else
		{
			$this->lib('Fields')->update_default_freeform_fields($pro_update);
		}

		// --------------------------------------------
		//  Rename legacy tables
		// --------------------------------------------

		if (version_compare($this->database_version(), '4.0.0', '<'))
		{
			$this->lib('Migration')->upgrade_migrate_preferences();
			$this->lib('Migration')->upgrade_notification_templates();
		}

		// -------------------------------------
		//	edit_date => entry_date *sigh*
		// -------------------------------------

		if (version_compare($this->database_version(), '4.0.4', '<') AND
			$this->column_exists('edit_date', 'exp_freeform_user_email'))
		{
			ee()->load->dbforge();
			ee()->dbforge->modify_column(
				'freeform_user_email',
				array(
					'edit_date' => array(
						'name'	=> 'entry_date',
						//we aren't changing this but stupid mysql
						//requires you send a type when changing
						//a column name
						'type'	=> 'INT'
					)
				)
			);
		}

		// -------------------------------------
		//	add index to entry date
		// -------------------------------------

		if (version_compare($this->database_version(), '4.2.0.1', '<'))
		{
			$key_result = ee()->db->query(
				"SHOW INDEX
				 FROM		exp_freeform_params
				 WHERE		Column_name = 'entry_date'"
			);

			if ($key_result->num_rows() == 0)
			{
				ee()->db->query(
					"ALTER TABLE	exp_freeform_params
					 ADD INDEX		`entry_date` (`entry_date`)"
				);
			}
		}

		// -------------------------------------
		//	Add email log table
		// -------------------------------------

		if (! ee()->db->table_exists('exp_freeform_email_logs'))
		{
			$newest_prefs = TRUE;

			$module_install_sql = file_get_contents(
				$this->addon_path . 'db.' . strtolower($this->lower_name) . '.sql'
			);

			//gets JUST the tag prefs table from the sql

			$prefs_table = stristr(
				$module_install_sql,
				"CREATE TABLE IF NOT EXISTS `exp_freeform_email_logs`"
			);

			$prefs_table = substr($prefs_table, 0, stripos($prefs_table, ';;'));

			//install it
			ee()->db->query($prefs_table);
		}

		// -------------------------------------
		//	Remove mailing list fieldtype
		//	as it's been removed from EE completely
		// -------------------------------------

		if (version_compare($this->database_version(), '5.0.0', '<'))
		{
			//remove from type availability
			$this->model('fieldtype')->delete(array(
				'fieldtype_name' => 'mailinglist'
			));

			$fields = $this->model('field')
						->where('field_type', 'mailinglist')
						->key('field_type', 'field_type')
						->get();

			//remove field from all forms and field list
			if ( ! empty($fields))
			{
				$this->lib('Fields')->delete_field(array_values($fields));
			}

			ee()->db->delete('freeform_fields', array('field_type' => 'mailinglist'));

			$fieldsQuery = ee()->db->get('freeform_fields');
			foreach ($fieldsQuery->result() as $field) {
				$settings = json_decode($field->settings);
				if (isset($settings->list_type)) {
					$settings->{$field->field_type . '_list_type'} = $settings->list_type;
					unset($settings->list_type);

				}

				if (isset($settings->field_list_items)) {
					$settings->{$field->field_type . '_field_list_items'} = $settings->field_list_items;
					unset($settings->field_list_items);
				}

				ee()->db->update(
					'freeform_fields',
					array('settings' => json_encode($settings)),
					array('field_id' => $field->field_id)
				);
			}
		}


		// ----------------------------------------
        //  Add the entry_url field to forms table
        // ----------------------------------------
        if (version_compare($this->database_version(), '5.1.0', '<')) {
            $entryUrlQuery = "
            ALTER TABLE exp_freeform_forms
            ADD entry_url VARCHAR(255) NULL DEFAULT NULL
            ";
            ee()->db->query($entryUrlQuery);
        }


            // -------------------------------------
		//	pro version vs free version?
		// -------------------------------------

		//up to pro
		if ($this->addon_info['freeform_pro'] === TRUE)
		{
			$this->install_ffp_channel_field();
		}
		//down to free
		else
		{
			$this->model('field')
				->where_not_in('field_type', $this->model('Data')->defaults['default_fields'])
				->update(array('field_type' => 'text'));

			$this->uninstall_ffp_channel_field();
		}

		if ($this->model('preference')->count(array(
				'site_id'			=> 0,
				'preference_name'	=> 'ffp'
			)) > 0
		)
		{
			$this->model('preference')->update(
				array(
					'preference_value'	=> ($this->addon_info['freeform_pro'] === TRUE) ? 'y' : 'n',
				),
				array(
					'site_id'			=> '0',
					'preference_name'	=> 'ffp'
				)
			);
		}
		else
		{
			$this->model('preference')->insert(array(
				'preference_name' 	=> 'ffp',
				'preference_value'	=> ($this->addon_info['freeform_pro'] === TRUE) ? 'y' : 'n',
				'site_id' 			=> '0'
			));
		}

		// --------------------------------------------
		//  Version Number Update - LAST!
		// --------------------------------------------

		$data = array(
			'module_version'		=> $this->version,
			'has_publish_fields'	=> 'n'
		);

		ee()->db->update(
			'modules',
			$data,
			array(
				'module_name'		=> $this->class_name
			)
		);

		return TRUE;
	}

	// END update()


	// --------------------------------------------------------------------

	/**
	 * Uninstall FFP Channel Fieldtype
	 *
	 * @access	public
	 * @return	null
	 */

	public function uninstall_ffp_channel_field ()
	{
		ee()->load->library('addons/addons_installer');
		ee()->load->model('addons_model');

		if (ee()->addons_model->fieldtype_installed($this->lower_name))
		{
			ee()->addons_installer->uninstall($this->lower_name, 'fieldtype', FALSE);
		}
	}
	//END uninstall_ffp_channel_field


	// --------------------------------------------------------------------

	/**
	 * Install FFP Channel Fieldtype
	 *
	 * @access	public
	 * @return	null
	 */

	public function install_ffp_channel_field ()
	{
		ee()->load->library('addons/addons_installer');
		ee()->load->model('addons_model');

		if (! ee()->addons_model->fieldtype_installed($this->lower_name))
		{
			ee()->addons_installer->install($this->lower_name, 'fieldtype', FALSE);
		}
	}
	//END install_ffp_channel_field
}
// END Class Freeform_updater_base
