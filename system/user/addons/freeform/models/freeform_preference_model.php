<?php

if ( ! class_exists('Freeform_Model'))
{
	require_once 'freeform_model.php';
}

class Freeform_preference_model extends Freeform_Model
{
	public $after_get					= array('json_decode_stored');

	public $pref_cache					= array();
	public $global_pref_cache			= array();

	//statuses for forms with more added through prefs?
	public $form_statuses				= array(
		'pending',
		'open',
		'closed'
	);

	//default pref values with type of input
	public $default_preferences			= array(
		'default_show_all_site_data'	=> array(
			'type'		=> 'yes_no',
			'value'		=> 'n',
			'validate'	=> 'enum[y,n]'
		),

		'max_user_recipients'			=> array(
			'type'		=> 'text',
			'value'		=> 10,
			'validate'	=> 'number'
		),
		'enable_spam_prevention'		=> array(
			'type'		=> 'yes_no',
			'value'		=> 'y',
			'validate'	=> 'enum[y,n]'
		),
		'spam_count'					=> array(
			'type'		=> 'text',
			'value'		=> 30,
			'validate'	=> 'number'
		),
		'spam_interval'					=> array(
			'type'		=> 'text',
			'value'		=> 60,
			'validate'	=> 'number'
		),

		'cp_date_formatting'			=> array(
			'type'		=> 'text',
			'value'		=> 'Y-m-d - H:i',
			'validate'	=> ''
		),
		'email_logging'					=> array(
			'type'		=> 'yes_no',
			'value'		=> 'n',
			'validate'	=> 'enum[y,n]'
		),
		'hook_data_protection'			=> array(
			'type'		=> 'yes_no',
			'value'		=> 'y',
			'validate'	=> 'enum[y,n]'
		),
		'disable_missing_submit_warning'=> array(
			'type'		=> 'yes_no',
			'value'		=> 'n',
			'validate'	=> 'enum[y,n]'
		),
	);

	//default pref values with type of input
	public $default_global_preferences	= array(
		'prefs_all_sites'				=> array(
			'type'		=> 'yes_no',
			'value'		=> 'y',
			'validate'	=> 'enum[y,n]'
		)
	);

	public $admin_only_prefs			= array(
		'allow_user_field_layout'
	);

	public $msm_only_prefs				= array(
		'default_show_all_site_data'
	);

	public $msm_enabled					= false;

	public $show_all_sites;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 */

	public function __construct()
	{
		parent::__construct();

		$this->msm_enabled = $this->check_yes(
			ee()->config->item('multiple_sites_enabled')
		);
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * preference
	 *
	 * Loads preferences and return
	 *
	 * @access	public
	 * @param	string	$which	which pref to find
	 * @return	mixed			return string value of pref from DB or from
	 *							defaults or bool false if not found
	 */

	public function preference($which)
	{
		$prefs = $this->prefs_with_defaults();

		return isset($prefs[$which]) ? $prefs[$which] : false;
	}
	//END preference


	// --------------------------------------------------------------------

	/**
	 * global preference
	 *
	 * Loads global preferences and return
	 *
	 * @access	public
	 * @param	string	$which	which pref to find
	 * @return	mixed			return string value of pref from DB or from
	 *							defaults or bool false if not found
	 */

	public function global_preference($which)
	{
		if (empty($this->global_pref_cache))
		{
			$this->global_pref_cache = $this->isolate()
										->where('site_id', 0)
										->key('preference_name', 'preference_value')
										->get();
		}

		return isset($this->global_pref_cache[$which]) ?
				$this->global_pref_cache[$which] :
				(
					isset($this->default_global_preferences[$which]['value']) ?
						$this->default_global_preferences[$which]['value'] :
						false
				);
	}
	//END global_preference


	// --------------------------------------------------------------------

	/**
	 * Preferences With Defaults
	 *
	 * @access	public
	 * @return	array	preferneces set with defaults included if no pref set
	 */

	public function prefs_with_defaults()
	{
		if ( ! empty($this->pref_cache))
		{
			return $this->pref_cache;
		}

		// -------------------------------------
		//	get prefs
		// -------------------------------------

		$prefs = array();

		//start with defaults
		foreach ($this->default_preferences as $key => $data)
		{
			$prefs[$key] = $data['value'];
		}

		//installed?
		if ($this->installed())
		{
			//$site_id = (
			//	! $this->check_no($this->global_preference('prefs_all_sites')) ?
			//		1 :
			//		ee()->config->item('site_id')
			//);

			// Overriding this - since now each site should have its own permissions
			$site_id = ee()->config->item('site_id');

			$query =	$this->isolate()
							->key('preference_name', 'preference_value')
							->where('site_id', $site_id)
							->get();

			//override any of not all defaults
			if ($query !== FALSE)
			{
				$prefs = array_merge($prefs, $query);
			}
		}

		$this->pref_cache = $prefs;

		return $this->pref_cache;
	}
	//END prefs_with_defaults


	// --------------------------------------------------------------------

	/**
	 * Preferences With Defaults
	 *
	 * @access	public
	 * @return	array	preferneces set with defaults included if no pref set
	 */

	public function global_prefs_with_defaults()
	{
		// -------------------------------------
		//	get prefs
		// -------------------------------------

		$prefs = array();

		//start with defaults
		foreach ($this->default_global_preferences as $key => $data)
		{
			$prefs[$key] = $data['value'];
		}

		//installed?
		if ($this->installed())
		{
			$query =	$this->isolate()
							->key('preference_name', 'preference_value')
							->where('site_id', 0)
							->get();

			//override any of not all defaults
			if ($query !== FALSE)
			{
				$prefs = array_merge($prefs, $query);
			}
		}

		return $prefs;
	}
	//END global_prefs_with_defaults


	// --------------------------------------------------------------------

	/**
	 * Show all sites
	 *
	 * do we want to show data from all sites?
	 *
	 * @access	public
	 * @return	bool
	 */

	public function show_all_sites()
	{
		if ( ! isset($this->show_all_sites))
		{
			$this->show_all_sites = (
				! $this->msm_enabled OR
				! $this->check_no(
					$this->preference('default_show_all_site_data')
				)
			);
		}

		return $this->show_all_sites;
	}
	//END show_all_sites

	// --------------------------------------------------------------------

	/**
	 * Get Form Statuses
	 *
	 * returns an array of form statuses
	 *
	 * @access	public
	 * @return	array
	 */

	public function get_form_statuses()
	{
		$statuses = array();

		foreach ($this->form_statuses as $status)
		{
			$statuses[$status] = lang($status);
		}



		return $statuses;
	}
	//END get_form_statuses


	// --------------------------------------------------------------------

	/**
	 * set_module_preferences
	 *
	 * @access	public
	 * @param	array  associative array of prefs and values
	 * @return	null
	 */

	public function set_preferences($new_prefs = array())
	{
		//no shenanigans
		if (empty($new_prefs))
		{
			return;
		}

		// -------------------------------------
		//	separate global prefs
		// -------------------------------------

		$global_prefs = array();

		foreach ($new_prefs as $key => $value)
		{
			if (array_key_exists($key, $this->default_global_preferences))
			{
				$global_prefs[$key] = $value;
				unset($new_prefs[$key]);
			}
		}

		// -------------------------------------
		//	site id?
		// -------------------------------------

		$prefs_all_sites = FALSE;

		if (isset($global_prefs['prefs_all_sites']))
		{
			$prefs_all_sites = (
				! $this->check_no($global_prefs['prefs_all_sites'])
			);
		}
		else
		{
			$prefs_all_sites = (
				! $this->check_no(
					$this->global_preference('prefs_all_sites')
				)
			);
		}

		$site_id = $prefs_all_sites ? 1 : ee()->config->item('site_id');

		// -------------------------------------
		//	get old prefs
		// -------------------------------------

		$prefs   = array();

		$p_query = $this->get();

		if ($p_query !== FALSE)
		{
			//either we've changed to prefs all sites,
			//or its been that way
			//either way, we need to set some prelims
			if ($prefs_all_sites)
			{
				foreach ($p_query as $row)
				{
					// if we are swaping to one set of prefs,
					// or we are swtiching to one set of prefs
					// then we just want old prefs from site_id 1
					// this will then preserve things like layout prefs
					// from site_id 1
					if ($prefs_all_sites && !in_array($row['site_id'], array(0, 1, '0', '1'), TRUE)) {
						continue;
					}

					if ( ! isset($prefs[$row['site_id']]))
					{
						$prefs[$row['site_id']] = array();
					}

					$prefs[$row['site_id']][$row['preference_name']] = $row['preference_value'];
				}
			}

			foreach ($p_query as $row) {
				if ($row['preference_name'] == "permissions") {
					$prefs[$row['site_id']][$row['preference_name']] = $row['preference_value'];
				}
			}
		}

		//kill all entries so we don't have to mix updates
		$this->clear_table();

		//keep old prefs and only overwrite updated/ add new
		//manual merge because we need the numeric keys intact
		if (array_key_exists($site_id, $prefs))
		{
			//if the old pref
			foreach ($prefs as $pref_site_id => $sub_prefs)
			{
				if ($pref_site_id == $site_id)
				{
					foreach ($new_prefs as $key => $value)
					{
						$prefs[$pref_site_id][$key] = $value;
					}
				}
			}
		}
		else
		{
			$prefs[$site_id] = $new_prefs;
		}

		//globals
		$prefs[0] = (isset($prefs[0])) ?
						array_merge($prefs[0], $global_prefs) :
						$global_prefs;

		// -------------------------------------
		//	prepare to insert new prefs
		// -------------------------------------

		foreach($prefs as $site_id => $sub_prefs)
		{
			foreach ($sub_prefs as $key => $value)
			{
				if (is_array($value))
				{
					$value = json_encode($value);
				}

				$this->insert(array(
					'preference_name' 	=> $key,
					'preference_value'	=> $value,
					'site_id' 			=> $site_id
				));
			}
		}
	}
	// END set_preferences


	// --------------------------------------------------------------------

	/**
	 * json_decode stored items
	 *
	 * @access	public
	 * @param	array	$data	incoming data rows from observer
	 * @param	bool	$all	returning all rows or a single?
	 * @return	array			affected data
	 */

	public function json_decode_stored ($data, $all)
	{
		if ($all)
		{
			foreach ($data as $key => $row)
			{
				if (isset($row['preference_value']))
				{
					if (preg_match('/^(\[|\{)/', $row['preference_value']))
					{
						$usi = json_decode($row['preference_value'], TRUE);

						if (is_array($usi))
						{
							$data[$key]['preference_value'] = $usi;
						}
					}
				}
			}
		}
		else if (isset($data['preference_value']))
		{
			//json data?
			if (preg_match('/^(\[|\{)/', $data['preference_value']))
			{
				$usi = json_decode($data['preference_value']);
				if (is_array($usi))
				{
					$data['preference_value'] = $usi;
				}
			}
		}

		return $data;
	}
	//json_decode_stored
}
//END Freeform_preference_model
