<?php

namespace Solspace\Addons\Tag\Model;
use Solspace\Addons\Tag\Library\AddonBuilder;

class Data extends AddonBuilder
{
	public $delimiters;

	public $pref_cache;


	public function __construct()
	{
		parent::__construct();

		$this->delimiters = $this->make('Preference')->delimiters;
	}

	// --------------------------------------------------------------------

	/**
	 * get_tag_separator
	 *
	 * @access	public
	 * @param	string	name of seperator
	 * @return	string	seperator
	 */

	public function get_tag_separator($name = '')
	{
		$delim = array_key_exists($name, $this->delimiters) ?
					$this->delimiters[$name] :
					end($this->delimiters);

		reset($this->delimiters);

		return $delim;
	}
	//END get_tag_separator


	// --------------------------------------------------------------------

	/**
	 * get_tag_separator
	 *
	 * @access	public
	 * @param	string	name of tag group
	 * @return	string	group id
	 */

	public function get_tag_group_id_by_name($name = '')
	{
		$return = false;

		$query = ee()->db
					->select('tag_group_id')
					->where('tag_group_short_name', $name)
					->or_where('tag_group_name', $name)
					->get('tag_groups');

		if ($query->num_rows() > 0)
		{
			$return = $query->row('tag_group_id');
		}

		return $return;
	}
	//END get_tag_group_id_by_name


	// --------------------------------------------------------------------

	/**
	 * Insert New Tag Group
	 *
	 * @access	public
	 * @param	string	$tag_group_name			Tag group name
	 * @param	string	$tag_group_short_name	short name of tag
	 * @return	int								id of new tag group
	 */

	public function insert_new_tag_group($tag_group_name, $tag_group_short_name = '')
	{

		if ($tag_group_short_name == '')
		{
			ee()->load->helper('url');

			$tag_group_short_name = url_title($tag_group_name);
		}

		$query = ee()->db
			->where('tag_group_short_name', $tag_group_short_name)
			->get('tag_groups');

		if ($query->num_rows() > 0)
		{
		    $resultArray = $query->result_array();
			return $resultArray[0]['tag_group_id'];
		}

		ee()->db->insert(
			'exp_tag_groups',
			array(
				"tag_group_name"		=> $tag_group_name,
				"tag_group_short_name"	=> $tag_group_short_name
			)
		);

		// -------------------------------------
		//	add column for tag group counts
		// -------------------------------------

		$insert_id 	= ee()->db->insert_id();
		$new_col	= ee()->db->escape_str('total_entries_' . $insert_id);

		//um this should NEVER be true... but
		if ( ! ee()->db->field_exists($new_col, 'exp_tag_tags'))
		{
			ee()->db->query(
				"ALTER TABLE 	`exp_tag_tags`
				 ADD COLUMN 	{$new_col} 		int(10) unsigned NOT NULL DEFAULT 0"
			);
		}

		return $insert_id;
	}
	//END insert_new_tag_group


	// --------------------------------------------------------------------

	/**
	 * Get Tag Groups
	 * returns an array of id => short_name tag groups
	 *
	 * @access	public
	 * @param	bool	use cache
	 * @return	array	id => short_name
	 */

	public function get_tag_groups($use_cache = true)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if ($use_cache AND isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$query = ee()->db
					->select('tag_group_id, tag_group_name')
					->get('tag_groups');

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$this->cached[$cache_name][$cache_hash][$row['tag_group_id']] = $row['tag_group_name'];
			}
		}

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	//eND get_tag_groups


	// --------------------------------------------------------------------

	/**
	 * Tag Total Entries SQL Insert
	 * returns a parse string for total entries
	 *
	 * @access	public
	 * @param	bool	use cache
	 * @return	string	inserts
	 */

	public function tag_total_entries_sql_insert ($prefix = '', $ending_comma = true, $use_cache = true)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if ($use_cache AND isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$prefix 	= ($prefix AND is_string($prefix)) ? trim($prefix) : '';

		$query = ee()->db
					->select('tag_group_id, tag_group_short_name')
					->get('tag_groups');

		$insert 	= '';

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$id 	= $row['tag_group_id'];
				$name 	= $row['tag_group_short_name'];

				$insert .= (($prefix !== '') ? $prefix . '.' : '') .
						'`total_entries_' . $id . "`,\n";
				//we want to also parse the total_entries_short_name
				$insert .= (($prefix !== '') ? $prefix . '.' : '') .
						'`total_entries_' . $id .
						'` as `total_entries_' . $name . "`,\n";
			}
		}

		if ( ! $ending_comma)
		{
			$insert = substr($insert, 0, -2);
		}

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		//tinfoil hat
		$this->cached[$cache_name][$cache_hash] = $insert;

		return $this->cached[$cache_name][$cache_hash];
	}
	//end tag_total_entries_sql_insert


	// --------------------------------------------------------------------

	/**
	 * get_entry_tags_by_tag_name
	 *
	 * @access	public
	 * @param	array	Array of tags
	 * @return	array
	 */

	public function get_entry_tags_by_tag_name($tags = array())
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if (isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		$query = ee()->db
					->select('tag_id, tag_name')
					->where_in('tag_name', $tags)
					->get('tag_tags');

		if ($query->num_rows() > 0)
		{
			$this->cached[$cache_name][$cache_hash] = $query->result_array();
		}

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	// END get_module_preferences()


	// --------------------------------------------------------------------

	/**
	 * get all tags from an entry
	 *
	 * @access	public
	 * @param	array	Array of Channel/Weblog IDs
	 * @return	array
	 */

	public function get_entry_tags_by_id($entry_id, $options = array(), $cache = true)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if ($cache AND isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = array();

		//--------------------------------------------
		//	options
		//--------------------------------------------

		$defaults 	= array(
			'tag_group_id'		=> 1,
			'entry_type'		=> 'channel',
		);

		$options = array_merge($defaults, $options);

		//lets just do this to prevent mistakes
		unset($defaults);

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		ee()->db->select('te.entry_id, t.tag_name, t.tag_id')
				->from('exp_tag_tags t')
				->join('exp_tag_entries te', 't.tag_id = te.tag_id', 'left')
				->where('te.entry_id', $entry_id)
				->where('te.type', $options['entry_type'])
				->group_by('tag_id')
				->order_by('t.tag_name');

		if ($options['tag_group_id'] !== '')
		{
			ee()->db->where_in(
				'te.tag_group_id',
				preg_split(
					'/\|/ims',
					$options['tag_group_id'],
					-1,
					PREG_SPLIT_NO_EMPTY
				)
			);
		}

		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			$this->cached[$cache_name][$cache_hash] = $query->result_array();
		}

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash];
	}
	// END get_module_preferences()


	// --------------------------------------------------------------------

	/**
	 * get tag ids from a certain group
	 *
	 * @access	public
	 * @param	int		group to filter to
	 * @param	bool	use the cache?
	 * @return	mixed	array if items found, false if none found in group
	 */

	public function get_tag_ids_by_group_id($group_id = 0, $cache = true)
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if ($cache AND isset($this->cached[$cache_name][$cache_hash]))
		{
			return $this->cached[$cache_name][$cache_hash];
		}

		$this->cached[$cache_name][$cache_hash] = false;

		$group_query = ee()->db
							->distinct()
							->select('tag_id')
							->where('tag_group_id', $group_id)
							->order_by('tag_id')
							->get('tag_entries');

		if ($group_query->num_rows() > 0)
		{
			$this->cached[$cache_name][$cache_hash] = array_keys(
				$this->prepare_keyed_result($group_query, 'tag_id')
			);
		}

		return $this->cached[$cache_name][$cache_hash];
	}
	//END get_tag_ids_by_group_id


	// --------------------------------------------------------------------

	/**
	 * Get the Preference for the Module for the Current Site
	 *
	 * @access	public
	 * @param	array	Array of Channel/Weblog IDs
	 * @return	array
	 */

	public function get_module_preferences()
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());
		$site_id	= ee()->config->item('site_id');

		if (isset($this->cached[$cache_name][$cache_hash][$site_id]))
		{
			return $this->cached[$cache_name][$cache_hash][$site_id];
		}

		$this->cached[$cache_name][$cache_hash][$site_id] = array();

		// --------------------------------------------
		//  Perform the Actual Work
		// --------------------------------------------

		//?????
		$possible_params = array('where', 'order_by', 'limit');

		$query = ee()->db
					->where('site_id', ee()->config->item('site_id'))
					->get('tag_preferences');

		foreach($query->result_array() as $row)
		{
			$this->cached[$cache_name][$cache_hash][
				$row['site_id']
			][$row['tag_preference_name']] = $row['tag_preference_value'];
		}

		// --------------------------------------------
		//  Return Data
		// --------------------------------------------

		return $this->cached[$cache_name][$cache_hash][$site_id];
	}
	// END get_module_preferences()


	// --------------------------------------------------------------------

	/**
	 * Get the ids of items that have names
	 *
	 * @access	public
	 * @param	bool	use cache or no (helpful when making changes in the middle of a document)
	 * @return	array 	Array of Channel/Weblog IDs
	 */

	public function get_tab_channel_ids( $use_cache = true )
	{
		// --------------------------------------------
		//  Prep Cache, Return if Set
		// --------------------------------------------

		$cache_name = __FUNCTION__;
		$cache_hash = $this->_imploder(func_get_args());

		if ($use_cache AND isset($this->cached[$cache_name][$cache_hash][ee()->config->item('site_id')]))
		{
			return $this->cached[$cache_name][$cache_hash][ee()->config->item('site_id')];
		}

		$ids_with_names = array();

		$prefs = $this->get_module_preferences($use_cache);

		//just find the prefs that are tab name related
		//we want an array of the channel ID numbers with the tab name as a value
		foreach($prefs as $key => $value)
		{
			if ( substr($key, -18) == "_publish_tab_label" AND ! in_array($value, array('', NULL), true))
			{
				$num 					= str_replace('_publish_tab_label', '', $key);
				$ids_with_names[$num] 	= $prefs[$key];
			}
		}

		//set false for if statements if empty
		$ids_with_names = (count($ids_with_names) > 0) ? $ids_with_names : false;

		//cache result (if $use_cache is false, this will still write the unchache result to cache)
		$this->cached[$cache_name][$cache_hash][ee()->config->item('site_id')] = $ids_with_names;

		return $this->cached[$cache_name][$cache_hash][ee()->config->item('site_id')];
	}
	//END get_tab_channel_ids


	// --------------------------------------------------------------------

	/**
	 * javascript autocomplete for tag field
	 *
	 * @access	public
	 * @return	string
	 */

	public function tag_field_autocomplete_js()
	{
		return '<script type="text/javascript" src="' .
					$this->theme_url .
					'js/jquery.tag_autocomplete.min.js"></script>';
	}
	//END tag_field_autocomplete_js


	// --------------------------------------------------------------------

	/**
	 * javascript for tag field
	 *
	 * @access	public
	 * @return	string
	 */

	public function tag_field_js()
	{
		return '<script type="text/javascript" src="' .
					$this->theme_url .
					'js/tag.js"></script>';
	}
	//END tag_field_js


	// --------------------------------------------------------------------

	/**
	 * css for tag field
	 *
	 * @access	public
	 * @return	string
	 */

	public function tag_field_css()
	{
		return '<link rel="stylesheet" type="text/css" ' .
						'media="screen" charset="utf-8" href="' .
						$this->theme_url .
						'css/tag.css" />';
	}
	//END tag_field_css


	// --------------------------------------------------------------------

	/**
	 * css for tag field
	 *
	 * @access	public
	 * @return	string
	 */

	public function tag_front_css()
	{
		return '<link rel="stylesheet" type="text/css" ' .
						'media="screen" charset="utf-8" href="' .
						$this->theme_url .
						'css/front_base.css" />';
	}
	//END tag_front_css


	// --------------------------------------------------------------------

	/**
	 * Find and return preference
	 *
	 * Any number of possible arguments, although
	 * typically I expect there will be only one or two
	 *
	 * @access	public
	 * @param	string			Preference to retrieve
	 * @return	null|string		If preference does not exist, NULL is returned, else the value
	 */

	public function preference()
	{
		$s = func_num_args();

		if ($s == 0)
		{
			return NULL;
		}

		//--------------------------------------------
		// Fetch Module Preferences
		//--------------------------------------------

		if (count($this->pref_cache) == 0)
		{
			$prefs = ee('Model')
				->get('tag:Preference')
				//->fields('tag_preference_name', 'tag_preference_value')
				->filter('site_id', ee()->config->item('site_id'))
				->all()
				->getDictionary('tag_preference_name', 'tag_preference_value');

			if ( ! $prefs)
			{
				return null;
			}

			$this->pref_cache = $prefs;
		}
		//--------------------------------------------
		// Find Our Value, If It Exists
		//--------------------------------------------

		$value = (isset($this->pref_cache[func_get_arg(0)])) ?
					$this->pref_cache[func_get_arg(0)] : NULL;

		return $value;
	}
	// END preference()

}
// END CLASS Tag_data
