<?php

use Solspace\Addons\Tag\Library\AddonBuilder;

class Tag_upd extends AddonBuilder
{
	public $module_actions		= array();
	public $hooks				= array();

	// --------------------------------------------------------------------

	/**
	 * Contructor
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

		$this->module_actions = array(
			'insert_tags',
			'ajax',
			'tag_js'
		);

		$this->csrf_exempt_actions = array('ajax');

		// --------------------------------------------
		//  Extension Hooks
		// --------------------------------------------

		$this->hooks = array();
	}
	// END __construct


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
		if ($this->database_version() !== false)
		{
			return false;
		}

		// --------------------------------------------
		//  Our Default Install
		// --------------------------------------------

		if ($this->default_module_install() == false)
		{
			return false;
		}

		// --------------------------------------------
		//  Default Preferences - Per Site
		// --------------------------------------------

		$prefs = array(
			'parse'							=> 'linebreak',
			'convert_case'					=> 'y',
			'enable_tag_form'				=> 'y',
			'publish_entry_tag_limit'		=> 0,
			'allow_tag_creation_publish' 	=> 'y',
			'separator'						=> 'comma'
		);

		$squery = ee()->db->select("site_id")->get("exp_sites");

		foreach($squery->result_array() as $row)
		{
			foreach($prefs as $name => $value)
			{
				$data = array(
					'site_id'					=> $row['site_id'],
					'tag_preference_name'		=> $name,
					'tag_preference_value'		=> $value
				);

				ee()->db->insert('exp_tag_preferences', $data);
			}
		}

		$this->set_default_tag_group();

		// --------------------------------------------
		//  Module Install
		// --------------------------------------------

		$data = array(
			'module_name'			=> $this->class_name,
			'module_version'		=> $this->version,
			//this is for tabs
			'has_publish_fields'	=> 'n',
			'has_cp_backend'		=> 'y'
		);

		ee()->db->insert('exp_modules', $data);

		return true;
	}
	// END install()


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
		if ($this->database_version() === false)
		{
			return false;
		}

		// --------------------------------------------
		//  Default Module Uninstall
		// --------------------------------------------

		if ($this->default_module_uninstall() == false)
		{
			return false;
		}

		// --------------------------------------------
		//  Publish Page Tabs
		// --------------------------------------------

		$this->remove_tag_tabs();

		return true;
	}
	// END uninstall


	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * For the sake of sanity, we only start upgrading from version 2.0 or above.  Cleans out
	 * all of the really old upgrade code, which was making Paul really really crazily confused.
	 *
	 * @access	public
	 * @return	bool
	 */

	public function update($current = '')
	{
		if ($current == $this->version)
		{
			return false;
		}

		//need this for later
		$this->previous_version = $this->database_version();

		// -------------------------------------
		//	no more tag submit
		//	this is old but harmless
		// -------------------------------------

		ee()->db->update(
			'extensions',
			array('class' => $this->extension_name),
			array('class' => 'Tag_submit')
		);

		// --------------------------------------------
		//  Rename the 'parse' preference to a more descriptive name
		// --------------------------------------------

		if (version_compare($this->previous_version, '3.0.0', '<'))
		{
			ee()->db->update(
				'tag_preferences',
				array('tag_preference_name' => 'separator'),
				array('tag_preference_name' => 'parse')
			);

			ee()->db
				->where_in('tag_preference_value', array('semicolon', 'colon'))
				->where('tag_preference_name', 'parse')
				->update(
					'tag_preferences',
					array('tag_preference_value' => 'comma')
				);

			// --------------------------------------------
			//  - Put Publish Tab Labels in preferences table
			//	 - DROP exp_tag_prefs table
			// --------------------------------------------

			$query = ee()->db
						->select('settings')
						->where('class', $this->extension_name)
						->where('settings !=', '')
						->limit(1)
						->get('extensions');

			if ($query->num_rows() > 0)
			{
				ee()->load->helper('string');

				$settings = strip_slashes((unserialize($query->row('settings'))));

				$query = ee()->db
							->select('site_id, channel_id, channel_title')
							->get('channels');

				foreach($query->result_array() AS $row)
				{
					if ( ! empty($settings[$row['channel_id']]))
					{
						ee()->db->insert(
							'tag_preferences',
							array(
								'tag_preference_name'	=> $row['channel_id'].'_publish_tab_label',
								'tag_preference_value'	=> $settings[$row['channel_id']],
								'site_id'				=> $row['site_id']
							)
						);
					}
					//end if ( ! empty($settings[$row['channel_id']]))
				}
				//eND foreach($query->result_array()
			}
			//END if ($query->num_rows() > 0)

			ee()->db->query("DROP TABLE IF EXISTS exp_tag_prefs");

			// --------------------------------------------
			//  Change Tag DB Structure to have EE 2.x Naming
			// --------------------------------------------

			ee()->db->query("ALTER TABLE `exp_tag_tags` CHANGE `weblog_entries` `channel_entries` INT( 10 ) NOT NULL DEFAULT '0'");
			ee()->db->query("ALTER TABLE `exp_tag_entries` CHANGE `weblog_id` `channel_id` SMALLINT( 3 ) UNSIGNED NOT NULL");

			// --------------------------------------------
			//  Change 'weblog' to 'channel' in exp_tag_entries - No matter the version.
			// --------------------------------------------

			ee()->db->update(
				'tag_entries',
				array('type' => 'channel'),
				array('type' => 'weblog')
			);
		}
		//END if (version_compare($this->previous_version, '3.0.0', '<'))

		//--------------------------------------------
		//	Some tags were getting preceding/exceeding
		// 	white space, this cleans that up
		//	this looks insane, but it's better
		//	than losing customer's data - gf
		//--------------------------------------------

		if (version_compare($this->previous_version, '3.0.4', '<'))
		{
			//we are using binary for exact matching with tag merging
			//but when we trim items, we need to check if we need case or not
			//BEFORE merging,
			$strict_case	= $this->check_no($this->model('Data')->preference('convert_case'));

			//get all tags that start or end with spaces
			$ws_tags = ee()->db->query(
				"SELECT site_id, tag_name, tag_id
				 FROM 	exp_tag_tags
				 WHERE 	tag_name
				 REGEXP '^ | $'"
			);

			if ($ws_tags->num_rows() > 0)
			{
				$trimmed_list 	 = array();

				foreach ($ws_tags->result_array() as $row)
				{
					$row['trimmed_name'] = ($strict_case ? trim($row['tag_name']) : strtolower(trim($row['tag_name'])));
					//array of site_ids containing trimmed names which
					//are also arrays for easier merging
					$trimmed_list[$row['site_id']][$row['trimmed_name']][] = $row;
				}

				//--------------------------------------------
				// check for existing tags matching trimmed tags
				//--------------------------------------------

				$sql = "SELECT 	tag_name, tag_id, site_id
						FROM	exp_tag_tags
						WHERE	";

				foreach ($trimmed_list as $site_id => $names)
				{
						$sql .= "( site_id = '" . ee()->db->escape_str($site_id) . "'
								   AND BINARY tag_name
								   IN ('" . implode("','", ee()->db->escape_str(array_keys($names))) . "')
								 ) OR ";
				}

				//remove trailing ' OR '
				$sql 			= substr($sql, 0, -4) . " ORDER BY site_id, tag_id ASC";

				$current_tags 	= ee()->db->query($sql);

				if ($current_tags->num_rows() > 0)
				{
					$current_tags_by_site_id 	= array();

					//need to sort by site_id and name for matching the trimmed ones from above
					foreach ($current_tags->result_array() as $row)
					{
						$current_tags_by_site_id[$row['site_id']][$row['tag_name']]	= $row;
					}

					//for each match set, we need to check, merge, and remove
					foreach ($current_tags_by_site_id as $site_id => $tags)
					{
						foreach ($tags as $tag_name => $tag_data)
						{
							//this shouldnt be needed as we checked against this list
							//but saftey first
							if (isset($trimmed_list[$site_id][$tag_name]) AND
								! empty($trimmed_list[$site_id][$tag_name]))
							{
								//foreach trimmed name match we need to convert the ugly names
								for ($i = 0, $l = count($trimmed_list[$site_id][$tag_name]); $i < $l; $i++)
								{
									//merge untrimmed tags by trimmed name with matching good tags
									$this->lib('Utils')->merge_tags(
										$tag_name,
										//here 'tag_name' is the row data from the untrimmed name
										$trimmed_list[$site_id][$tag_name][$i]['tag_name'],
										$site_id
									);
								}

								//remove so we dont try to work with it again later
								unset($trimmed_list[$site_id][$tag_name]);
							}
							//END if (isset($trimmed_list
						}
						//END foreach ($tags

						//just a little cleanup incase everything had matching trimmed tags
						if (empty($trimmed_list[$site_id]))
						{
							unset($trimmed_list[$site_id]);
						}
					}
					//END foreach ($current_tags_by_site_id
				}
				//END if ($current_tags->num_rows() > 0)

				//--------------------------------------------
				//	convert all tags left to the trimmed
				// 	version of thier names
				//--------------------------------------------
				if ( ! empty($trimmed_list))
				{
					foreach ($trimmed_list as $site_id => $tag_names)
					{
						foreach ($tag_names as $trimmed_name => $matching_tags)
						{
							for ($i = 0, $l = count($matching_tags); $i < $l; $i++)
							{
								//for the first item with this trimmed name, (but no real tag match)
								//we update to the trimmed name
								if ($i == 0)
								{
									ee()->db->update(
										'tag_tags',
										array(
											'tag_name' => $trimmed_name
										),
										array(
											'site_id'	=> $site_id,
											'tag_id'	=> $matching_tags[$i]['tag_id']
										)
									);
								}
								//if we have more than one trimmed name of the same
								//we are going to merge this with the first one we updated
								else
								{
									$this->lib('Utils')->merge_tags(
										$trimmed_name,
										//here 'tag_name' is the row data from the untrimmed name
										$matching_tags[$i]['tag_name'],
										$site_id
									);
								}
								//eND if ($i == 0)
							}
							//END for ($i = 0,
						}
						//END foreach ($tag_names
					}
					//END foreach ($trimmed_list
				}
				//END if ( ! empty($trimmed_list))
			}
			//END if ($ws_tags->num_rows() > 0)
		}
		//END if (version_compare($this->previous_version, '3.0.4', '<'))

		//--------------------------------------------
		//	tag groups added in 4.0
		//--------------------------------------------

		if (version_compare($this->previous_version, '4.0.0', '<'))
		{

			if ( ! ee()->db->field_exists( 'tag_group_id', 'exp_tag_entries', false) )
			{
				ee()->db->query(
					"ALTER TABLE exp_tag_entries
					 ADD		tag_group_id int(10) unsigned NOT NULL default 1"
				);
			}
		}
		//END if (version_compare($this->database_version(), '4.0', '<'))

		if ( ! ee()->db->table_exists('exp_tag_groups'))
		{
			$module_install_sql = file_get_contents($this->addon_path . strtolower($this->lower_name) . '.sql');

			//gets JUST the tag groups table from the sql
			$groups_table = stristr(
				$module_install_sql,
				"CREATE TABLE IF NOT EXISTS `exp_tag_groups`"
			);

			$groups_table = substr($groups_table, 0, stripos($groups_table, ';;'));

			//install it
			ee()->db->query($groups_table);
		}

		$this->set_default_tag_group();

		// -------------------------------------
		//	add tag group counts for 4.1.0
		// -------------------------------------

		if (version_compare($this->previous_version, '4.1.0', '<'))
		{
			$tag_groups = $this->model('Data')->get_tag_groups(false);

			foreach ($tag_groups as $tag_group_id => $tag_group_name)
			{
				$new_col	= ee()->db->escape_str('total_entries_' . $tag_group_id);

				//um this should NEVER be true... but
				if ( ! ee()->db->field_exists($new_col, 'exp_tag_tags'))
				{
					ee()->db->query(
						"ALTER TABLE	`exp_tag_tags`
						 ADD COLUMN		{$new_col}		int(10) unsigned NOT NULL DEFAULT 0"
					);
				}
			}

			//after this, there is a custom redirect for 4.0.2 that does an ajax update of counts
		}

		// -------------------------------------
		//	drop subscriptions table
		// -------------------------------------

		if (version_compare($this->previous_version, '5.0.0', '<'))
		{
			//save this for the moment in case people want to keep
			//the data for a custom solution.

			if (ee()->db->table_exists('tag_subscriptions') &&
				ee()->db->count_all_results('tag_subscriptions') < 1)
			{
				ee()->load->dbforge();
				ee()->dbforge->drop_table('exp_tag_subscriptions');
			}
		}

		// -------------------------------------
		//	add id to tag entries table for primary
		//	index performance
		// -------------------------------------

		if ( ! ee()->db->field_exists('id', 'exp_tag_entries'))
		{
			ee()->db->query(
				"ALTER TABLE 	`exp_tag_entries`
				 ADD COLUMN 	`id` int(10) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT"
			);
		}

		// --------------------------------------------
		//  Default Module Update
		// --------------------------------------------

		$this->default_module_update();

		// --------------------------------------------
		//  Version Number Update - LAST!
		// --------------------------------------------


		ee()->db->update(
			'modules',
			array(
				'module_version' 		=> $this->version,
				//byebye tab
				'has_publish_fields'	=> 'n'
			),
			array(
				'module_name'			=> $this->class_name
			)
		);


		return true;
	}
	// END update()


	// --------------------------------------------------------------------

	/**
	 *	This will install itself when needed in the tabs section
	 *
	 *
	 *	@access		public
	 *	@return		array
	 */

	private function set_default_tag_group()
	{
		$query = ee()->db
					->select('tag_group_id')
					->where('tag_group_id', '1')
					->get('tag_groups');

		if ($query->num_rows() > 0)
		{
			return;
		}

		ee()->db->insert(
			"tag_groups",
			array(
				'tag_group_id'			=> 1,
				'tag_group_name'		=> "default",
				"tag_group_short_name"	=> "default"
			)
		);
	}
	// END tabs()


	// --------------------------------------------------------------------

	/**
	 *	remove all tabs, old and new, from layouts
	 *
	 *	@access		public
	 *	@return		null
	 */
	public function remove_tag_tabs()
	{
		$possible = array(
			'tag' => array(
				'tag__solspace_tag_submit' => array(
					'visible'		=> 'true',
					'collapse'		=> 'false',
					'htmlbuttons'	=> 'false',
					'width'			=> '100%'
				),
				'tag__solspace_tag_suggest' => array(
					'visible'		=> 'true',
					'collapse'		=> 'false',
					'htmlbuttons'	=> 'false',
					'width'			=> '100%'
				),
				'tag__solspace_tag_entry' => array(
					'visible'		=> 'true',
					'collapse'		=> 'false',
					'htmlbuttons'	=> 'false',
					'width'			=> '100%'
				),
			)
		);

		ee()->load->library('layout');
		ee()->layout->delete_layout_tabs($possible);
		ee()->layout->delete_layout_fields($possible);
	}
	//END remove_user_tabs()
}
/* END Tag_updater_base CLASS */
