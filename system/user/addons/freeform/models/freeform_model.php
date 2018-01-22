<?php

use Solspace\Addons\Freeform\Library\Cacher;
use Solspace\Addons\Freeform\Library\AddonBuilder;

class Freeform_Model extends AddonBuilder
{
	//added these here because multiple items need the form_name
	//we don't add exp_ here because DBForge adds it either way? ok :|
	public $form_table_nomenclature		= 'freeform_form_entries_%NUM%';
	public $form_field_prefix			= 'form_field_';
	public $cache_enabled				= TRUE;

	protected $class 					= __CLASS__;
	protected $isolated 				= FALSE;
	protected $keyed 					= FALSE;

	public $_table;
	public $id;
	public $root_name 					= '';
	public $dbprefix 					= 'exp_';
	public $swap_pre 					= '';

	// -------------------------------------
	//	redoing redundant driver data?
	//	Yes, because EE's version of CI
	//	makes them private with no way to retrive
	//	or use the functions that utilize them
	// -------------------------------------

	//default to mysql, but if another drive is found
	//we will reset in the constructor
	public $random_keyword 				= ' RAND()';
	public $_random_keyword 			= ' RAND()';
	public $_escape_char 				= '`';
	public $_count_string 				= 'SELECT COUNT(*) AS ';
	public $_like_escape_str			= '';
	public $_like_escape_chr			= '';
	public $_protect_identifiers	= TRUE;
	public $_reserved_identifiers	= array('*');

	// -------------------------------------
	//	observer groups
	// -------------------------------------

	public $before_count				= array();

	public $before_get					= array();
	public $after_get					= array();

	public $before_insert				= array();
	public $after_insert				= array();

	public $before_update				= array();
	public $after_update				= array();

	public $before_delete				= array();
	public $after_delete				= array();

	// -------------------------------------
	//	stashes
	// -------------------------------------

	public $db_stash					= array();
	public $db_isolated_stash			= array();


	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access public
	 * @return object $this
	 */

	public function __construct ()
	{
		parent::__construct('module');

		$this->class 		= get_class($this);

		$this->root_name 	= strtolower(
			preg_replace("/^Freeform_(.*?)_model/is", '$1', $this->class)
		);

		if ( ! $this->id)
		{
			$this->id = $this->root_name . '_id';
		}

		if ( ! $this->_table)
		{
			ee()->load->helper('inflector');
			$this->_table = 'freeform_' . trim(plural($this->root_name), '_');
		}

		$this->dbprefix = ee()->db->dbprefix;
		$this->random_keyword = $this->_random_keyword;

		$this->db =& ee()->db;
	}
	//END __construct


	// --------------------------------------------------------------------
	//	CRUD
	// --------------------------------------------------------------------


	// --------------------------------------------------------------------

	/**
	 * count
	 *
	 * @access public
	 * @param  mixed  	$where 		mixed. if single, id =>, if array, where =>
	 * @param  boolean 	$cleanup   	cleanup and deisolate?
	 * @return int         			count of all results
	 */

	public function count($where = array(), $cleanup = TRUE)
	{
		// -------------------------------------
		//	validate
		// -------------------------------------

		if ( ! is_array($where))
		{
			//if its not an array and not an INT we cannot use it :p
			if ( ! $this->is_positive_intlike($where)){	return 0; }

			$where = array($this->id => $where);
		}

		// -------------------------------------
		//	wheres
		// -------------------------------------

		$where = $this->observe('before_count', $where);

		// -------------------------------------
		//	cache
		// -------------------------------------

		$stash = ($this->isolated) ? 'db_isolated_stash' : 'db_stash';

		$cache = $this->cacher(array(func_get_args(), $this->{$stash}, $where), __FUNCTION__);

		if ($this->cache_enabled AND $cache->is_set())
		{
			if ($cleanup){ $this->reset()->deisolate();	}

			return $cache->get();
		}

		// -------------------------------------
		//	get
		// -------------------------------------

		$this->wheres($where);

		$this->run_stash();

		$count = $this->db->count_all_results($this->_table);

		if ($cleanup)
		{
			//cleanup
			$this->reset()->deisolate();
		}

		return $cache->set($count);
	}
	//END count


	// --------------------------------------------------------------------

	/**
	 * Count all results
	 *
	 * @access public
	 * @return int  	total count of all results in table
	 */

	public function count_all($cleanup = TRUE)
	{
		//	cache
		$cache = $this->cacher(func_get_args(), __FUNCTION__);
		if ($this->cache_enabled AND $cache->is_set()){return $cache->get();}

		$count = $this->db->count_all($this->_table);

		if ($cleanup)
		{
			//cleanup
			$this->reset()->deisolate();
		}

		return $cache->set($count);
	}
	//END count_all


	// --------------------------------------------------------------------

	/**
	 * Get
	 *
	 * @access public
	 * @param  mixed  	$where 		mixed. if single, id =>, if array, where =>
	 * @param  boolean 	$cleanup   	cleanup and deisolate?
	 * @param  boolean 	$all 		return all results? (mainly used for)
	 * @return array    			array of data or array of rows if $all
	 */

	public function get($where = array(), $cleanup = TRUE, $all = TRUE)
	{
		// -------------------------------------
		//	validate
		// -------------------------------------

		if ( ! is_array($where))
		{
			//if its not an array and not an INT we cannot use it :p
			if ( ! $this->is_positive_intlike($where)){	return FALSE; }

			$where = array($this->id => $where);
		}

		$this->wheres($where);

		// -------------------------------------
		//	wheres
		// -------------------------------------

		$where = $this->observe('before_get', $where, $all);

		// -------------------------------------
		//	cache
		// -------------------------------------

		$stash = ($this->isolated) ? 'db_isolated_stash' : 'db_stash';

		//where is reset here because its not the same as arg where
		$cache = $this->cacher(
			array(func_get_args(), $this->{$stash}, $all, $this->keyed),
			__FUNCTION__
		);

		if ($this->cache_enabled AND $cache->is_set())
		{
			if ($cleanup){ $this->reset()->deisolate();	}

			return $cache->get();
		}

		// -------------------------------------
		//	get
		// -------------------------------------

		if ( ! $all)
		{
			$this->limit(1);
		}

		$this->run_stash();

		$query = $this->db->get($this->_table);

		// -------------------------------------
		//	keyed?
		// -------------------------------------

		$keyed = $this->keyed;

		// -------------------------------------
		//	clean
		// -------------------------------------

		if ($cleanup)
		{
			$this->reset()->deisolate();
		}

		// -------------------------------------
		//	cache and go
		// -------------------------------------

		if ($query->num_rows() > 0 )
		{
			$rows = $this->observe('after_get', $query->result_array(), $all);

			//keyed result?
			if ($all AND $keyed)
			{
				$args = $keyed;
				array_unshift($args, $rows);
				$rows = call_user_func_array(array($this, 'prepare_keyed_result'), $args);
			}

			return $cache->set(( ! $all) ? $rows[0] : $rows);
		}
		else
		{
			return $cache->set(FALSE);
		}
	}
	//END get


	// --------------------------------------------------------------------

	/**
	 * Get Row
	 *
	 * Same as get but returns just the first row
	 *
	 * @access	public
	 * @param 	mixed 	$where 		the id of the form or an array of wheres
	 * @param   boolean $cleanup   	cleanup and deisolate?
	 * @return	array 				data from the form id or an empty array
	 */

	public function get_row($where = array(), $cleanup = TRUE)
	{
		return $this->get($where, $cleanup, FALSE);
	}
	//get_all


	// --------------------------------------------------------------------

	/**
	 * Insert Data
	 *
	 * @access public
	 * @param  array 	$data 	array of data to insert
	 * @return mixed       		id if success, boolean false if not
	 */

	public function insert($data)
	{
		$data = $this->observe('before_insert', $data);

		$success = $this->db->insert($this->_table, $data);

		if ($success)
		{
			Cacher::clear($this->class);

			$id = $this->db->insert_id();

			$this->observe('after_insert', $id);

			$return = $id;
		}
		else
		{
			$return = FALSE;
		}

		//just in case
		$this->reset()->deisolate();

		return $return;
	}
	//END insert


	// --------------------------------------------------------------------

	/**
	 * Update
	 *
	 * @access 	public
	 * @param  	mixed  	$where 	mixed. if single, id =>, if array, where =>
	 * @param  	array 	$data 	array of data to update
	 * @return 	mixed       	id if success, boolean false if not
	 */

	public function update($data, $where = array())
	{
		// -------------------------------------
		//	validate
		// -------------------------------------

		if ( ! is_array($where))
		{
			//if its not an array and not an INT we cannot use it :p
			if ( ! $this->is_positive_intlike($where)){	return FALSE; }

			$where = array($this->id => $where);
		}

		// -------------------------------------
		//	update
		// -------------------------------------

		//run listener
		$data = $this->observe('before_update', $data);

		$ids = $this->get_ids($where);

		$this->wheres($where);

		$this->run_stash();

		$success = $this->db->update($this->_table, $data);

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($success)
		{
			Cacher::clear($this->class);

			$this->observe('after_update', $ids);

			$return = TRUE;
		}
		else
		{
			$return = FALSE;
		}

		//just in case
		$this->reset()->deisolate();

		return $return;
	}
	//END update


	// --------------------------------------------------------------------

	/**
	 * delete
	 *
	 * @access 	public
	 * @param  	mixed  	$where 	mixed. if single, id =>, if array, where =>
	 * @return 	mixed       	id if success, boolean false if not
	 */

	public function delete($where = array())
	{
		// -------------------------------------
		//	validate
		// -------------------------------------

		if ( ! is_array($where))
		{
			//if its not an array and not an INT we cannot use it :p
			if ( ! $this->is_positive_intlike($where)){	return array(); }

			$where = array($this->id => $where);
		}

		// -------------------------------------
		//	make sure the return function has Ids
		//	to work with done first in case a query
		//	or some such gets added
		// -------------------------------------

		$ids = $this->get_ids($where);

		// -------------------------------------
		//	delete
		// -------------------------------------

		$this->wheres($where);

		$this->run_stash();

		$success = $this->db->delete($this->_table);

		//just in case
		$this->reset()->deisolate();

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($success)
		{
			Cacher::clear($this->class);

			$this->observe('after_delete', $ids);

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	//END delete


	// --------------------------------------------------------------------
	//	utilities
	// --------------------------------------------------------------------
	// 	Some functions here are repeated from elsehwere in the Freeform
	// 	code but this is intention so models can be more standalone
	// --------------------------------------------------------------------


	// --------------------------------------------------------------------

	/**
	 * Reset selections, etc
	 *
	 * @access public
	 * @return object $this returns self for chaining
	 */

	public function reset()
	{
		$stash = ($this->isolated) ? 'db_isolated_stash' : 'db_stash';

		$this->{$stash} = array();
		$this->swap_pre = '';
		$this->keyed 	= FALSE;

		return $this;
	}
	//END reset


	// --------------------------------------------------------------------

	/**
	 * Set results to be keys
	 *
	 * @access public
	 * @param  string $key key to sort on
	 * @param  string $val return a value instead?
	 * @return object      $this for chaining
	 */

	public function key($key = '', $val = '')
	{
		$args = func_get_args();

		if ($val !== '')
		{
			$this->select(implode(', ', $args));
		}

		$this->keyed = $args;

		return $this;
	}
	//END key


	// --------------------------------------------------------------------

	/**
	 * Cacher
	 * This is abstracted because some models might need to make different
	 * use of it
	 *
	 * Table is mixed in by default because some models might change the table
	 *
	 * @access protected
	 * @param  array 	$args 	arguments from func_get_args();
	 * @param  string 	$func 	function name from __FUNCTION__
	 * @return object       	new cacher instance
	 */

	protected function cacher($args, $func)
	{
		return new Cacher(array($args, $this->_table), $func, $this->class);
	}
	//END cacher


	// --------------------------------------------------------------------

	/**
	 * Clear class cache
	 *
	 * @access public
	 * @param  string $func function to clear on, else clears entire class
	 * @return object       returns $this for chaining
	 */

	public function clear_cache($func = '')
	{
		if (trim($func) !== '')
		{
			Cacher::clear($this->class, $func);
		}
		else
		{
			Cacher::clear($this->class);
		}

		return $this;
	}
	//end clear_cache


	// --------------------------------------------------------------------

	/**
	 * Any direct calls that are AR like db calls we stash
	 * and will call when we call the get/insert/etc.
	 *
	 * This will also allow interupt and override.
	 *
	 * @access public
	 * @param  string $method    method name
	 * @param  array  $arguments args
	 * @return object            if its db callable, return self for chain
	 */

	public function __call($method, $arguments)
	{
		if (is_callable(array($this->db, $method)))
		{
			$stash = ($this->isolated) ? 'db_isolated_stash' : 'db_stash';

			$this->{$stash}[] = array($method, $arguments);

			return $this;
		}
		else
		{
			throw new \Exception(str_replace(
				array('%class%', '%method%'),
				array($this->class, $method),
				lang('call_to_undefined_method')
			));
		}
	}
	//END __call


	// --------------------------------------------------------------------

	/**
	 * Run stashed db calls
	 *
	 * @access protected
	 * @return object		$this for chaining
	 */

	protected function run_stash($reset_after_call = FALSE)
	{
		$stash = ($this->isolated) ? 'db_isolated_stash' : 'db_stash';

		foreach ($this->{$stash} as $call)
		{
			$method 	= $call[0];
			$arguments 	= $call[1];

			//db method?
			if (is_callable(array($this->db, $method)))
			{
				call_user_func_array(array($this->db, $method), $arguments);
			}
			//local method?
			else if (is_callable(array($this, $method)))
			{
				call_user_func_array(array($this, $method), $arguments);
			}
		}

		if ($reset_after_call)
		{
			$this->reset();
		}

		return $this;
	}
	//END run_stash


	// --------------------------------------------------------------------

	/**
	 * Runs over the $where array and sends it to where_in if its an array
	 *
	 * @access 	protected
	 * @param  	array  $where array of where clauses
	 * @return 	object        $this for chaining
	 */

	protected function wheres($where = array())
	{
		if ( ! empty($where))
		{
			foreach ($where as $find => $criteria)
			{
				if (is_array($criteria))
				{
					$this->where_in($find, $criteria);
				}
				else
				{
					$this->where($find, $criteria);
				}
			}
		}

		return $this;
	}
	//END wheres


	// --------------------------------------------------------------------

	/**
	 * Get the ids either from the current where
	 * Isolated in case a function needs to override
	 *
	 * @access protected
	 * @param  array $where  wheres from parent
	 * @return array         array of ids
	 */

	protected function get_ids($where)
	{
		$ids = array();

		//if ID is not part of the query, we need it
		if (array_key_exists($this->id, $where))
		{
			$ids = $where[$this->id];

			if ( ! is_array($ids))
			{
				$ids = array($ids);
			}
		}

		return $ids;
	}
	//END get_ids


	// --------------------------------------------------------------------

	/**
	 * Sets $this->db to its own instance to avoid AR collision
	 *
	 * @access public
	 * @return object $this for chaining
	 */

	public function isolate()
	{
		if ( ! $this->isolated)
		{
			$this->isolated = TRUE;

			$this->db		= DB(array(
				'hostname' => ee()->db->hostname,
				'database' => ee()->db->database,
				'username' => ee()->db->username,
				'password' => ee()->db->password,
				'dbprefix' => ee()->db->dbprefix,
			));
		}

		return $this;
	}
	//END isolate


	// --------------------------------------------------------------------

	/**
	 * UnSets $this->db from its own instance and parent __get falls back to
	 * $CI->db
	 *
	 * @access public
	 * @return object $this for chaining
	 */

	public function deisolate()
	{
		if ($this->isolated)
		{
			$this->isolated = FALSE;
			$this->db->close();
			unset($this->db);
			$this->db =& ee()->db;
		}

		return $this;
	}
	//END deisolate


	// --------------------------------------------------------------------

	/**
	 * Run observer hooks on data
	 *
	 * @access protected
	 * @param  string 	$listener 	name of listener array to run
	 * @param  mixed 	$data     	data to iterate over
	 * @return mixed           		affected data
	 */

	protected function observe($listener, $data)
	{
		//no funny stuff
		if ( ! isset($this->$listener) OR ! is_array($this->$listener))
		{
			return $data;
		}

		//everything after the first arg
		$args = func_get_args();
		array_shift($args);

		foreach ($this->$listener as $method)
		{
			if (is_callable(array($this, $method)))
			{
				$data = call_user_func_array(array($this, $method), $args);
			}
		}

		return $data;
	}
	//END observe


	// --------------------------------------------------------------------

	/**
	 * Split a string by pipes with no empty items
	 * Because I got really tired of typing this.
	 *
	 * @access public
	 * @param  string $str pipe delimited string to split
	 * @return array      array of results
	 */

	public function pipe_split($str)
	{
		return $this->lib('Utils')->pipe_split($str);
	}
	//END pipe_split


	// --------------------------------------------------------------------

	/**
	 * Clear Table
	 *
	 * Truncates table
	 *
	 * @access	public
	 * @return	object this for chaning
	 */
	public function clear_table()
	{
		$this->db->truncate($this->_table);
		return $this;
	}
	//END clear_table


	// --------------------------------------------------------------------

	/**
	 * List fields
	 *
	 * @access	public
	 * @return	array of field names
	 */

	public function list_table_fields($use_cache = TRUE)
	{

		//where is reset here because its not the same as arg where
		$cache = $this->cacher(array(), __FUNCTION__);

		if ($this->cache_enabled AND $use_cache AND $cache->is_set())
		{
			return $cache->get();
		}

		$p_table_name = (substr($this->_table, 0, strlen($this->dbprefix)) !== $this->dbprefix) ?
									$this->dbprefix . $this->_table :
									$table_name;

		$fields = array();

		$field_q = $this->db->query(
			"SHOW COLUMNS FROM " . $this->db->escape_str($p_table_name)
		);

		if ($field_q->num_rows() > 0)
		{
			foreach ($field_q->result_array() as $row)
			{
				if (isset($row['Field']))
				{
					$fields[] = $row['Field'];
				}
			}
		}

		return $cache->set($fields);
	}
	//END list_table_fields


	// --------------------------------------------------------------------

	/**
	 * Installed
	 *
	 * @access	public
	 * @return	boolean		model's table is installed
	 */

	public function installed()
	{
		//using table here fixes the issue using this with
		//the entry model
		$cache = $this->cacher(array($this->_table), __FUNCTION__);

		if ($this->cache_enabled AND $cache->is_set())
		{
			return $cache->get();
		}

		return $cache->set(ee()->db->table_exists($this->_table));
	}
	//END installed


	// --------------------------------------------------------------------

	/**
	 * Prepare keyed result
	 *
	 * This one is a little different than AOB so we need this override
	 *
	 * Take a query object and return an associative array. If $val is empty,
	 * the entire row per record will become the value attached to the indicated key.
	 *
	 * For example, if you do a query on exp_channel_titles and exp_channel_data
	 * you can use this to quickly create an associative array of channel entry
	 * data keyed to entry id.
	 *
	 * @access	public
	 * @param 	array 	$rows data rows to sort
	 * @param   string $key
	 * @return	mixed
	 */

	public function prepare_keyed_result ( $rows, $key = '', $val = '' )
	{
		if ( ! is_array( $rows )  OR $key == '' ) return FALSE;

		// --------------------------------------------
		//  Loop through query
		// --------------------------------------------

		$data	= array();

		foreach ( $rows as $row )
		{
			if ( isset( $row[$key] ) === FALSE ) continue;

			$data[ $row[$key] ]	= ( $val != '' AND isset( $row[$val] ) ) ? $row[$val]: $row;
		}

		return ( empty( $data ) ) ? FALSE : $data;
	}
	// END prepare_keyed_result
}
//END Freeform_model
