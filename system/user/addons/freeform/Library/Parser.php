<?php

/**
 * Addon Builder - Abstracted Template Parser
 *
 * @package		Solspace:Addon Builder
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2016, Solspace, Inc.
 * @link		https://solspace.com/expressionengine/freeform
 * @license		https://solspace.com/software/license-agreement
 * @filesource	freeform/Library/Parser.php
 */

namespace Solspace\Addons\Freeform\Library;

class Parser
{
	protected $old_get = '';

	/**
	 * Addon builder instance for helping
	 *
	 * @var object
	 */
	protected $aob;

	public static $global_cache;

	public $global_vars = array();

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct()
	{
		$this->aob = new AddonBuilder();

		// -------------------------------------
		//	global cache
		// -------------------------------------

		if ( ! isset(static::$global_cache))
		{
			if ( ! isset(ee()->session->cache['modules']['morsel']['template']))
			{
				ee()->session->cache['modules']['morsel']['template'] = array();
			}

			static::$global_cache =& ee()->session->cache['modules']['morsel']['template'];
		}

		// --------------------------------------------
		//  ExpressionEngine only loads snippets
		//  on PAGE and ACTION requests
		// --------------------------------------------

		$this->load_snippets();

		if ( ! isset(ee()->TMPL))
		{
			ee()->load->library('template', NULL, 'TMPL');
		}
	}
	// END constructor()


	// --------------------------------------------------------------------

	/**
	 * Load Snippets for CP as ExpressionEngine only loads snippets
	 * on PAGE and ACTION requests
	 *
	 * @access	public
	 * @return	void
	 */

	public function load_snippets()
	{
		//this is done automatically for action and page requests
		if (REQ != 'CP' || (
				isset(static::$global_cache['snippets_loaded']) &&
				static::$global_cache['snippets_loaded']
			)
		)
		{
			return;
		}

		// load up any Snippets
		ee()->db->select('snippet_name, snippet_contents');
		ee()->db->where_in('site_id', array(0, ee()->config->item('site_id')));

		$fresh = ee()->db->get('snippets');

		if ($fresh->num_rows() > 0)
		{
			$snippets = array();

			foreach ($fresh->result() as $var)
			{
				$snippets[$var->snippet_name] = $var->snippet_contents;
			}

			$var_keys = array();

			foreach (ee()->config->_global_vars as $k => $v)
			{
				$var_keys[] = LD.$k.RD;
			}

			foreach ($snippets as $name => $content)
			{
				$snippets[$name] = str_replace(
					$var_keys,
					array_values(ee()->config->_global_vars),
					$content
				);
			}

			ee()->config->_global_vars = array_merge(
				ee()->config->_global_vars,
				$snippets
			);

			unset($var_keys);
		}

		unset($snippets);
		unset($fresh);

		static::$global_cache['snippets_loaded'] = true;
	}
	//END load_snippets


	// --------------------------------------------------------------------

	/**
	 * Set Parse PHP
	 *
	 * sets flag on template to use parse_php or not
	 *
	 * @access	public
	 * @param	boolean		$value	on or off via true/false
	 * @return	object				$this for chaining
	 */

	public function setParsePhp($value)
	{
		ee()->TMPL->parse_php = (bool) $value;

		return $this;
	}
	//END setParsePhp


	// --------------------------------------------------------------------

	/**
	 * Set PHP Parse Location
	 *
	 * @access	public
	 * @param	string		$phpParseLocation	'i' or 'o' for input/output
	 * @return  object							$this for chaining
	 */

	public function setPhpParseLocation($phpParseLocation)
	{
		$phpParseLocation = strtolower(trim($phpParseLocation));

		if ($phpParseLocation == "i")
		{
			$phpParseLocation = "input";
		}
		else if ($phpParseLocation == "o")
		{
			$phpParseLocation = "output";
		}

		ee()->TMPL->php_parse_location = $phpParseLocation;

		return $this;
	}
	//END setPhpParseLocation


	// --------------------------------------------------------------------

	/**
	 * Set Encode Email
	 *
	 * @access	public
	 * @param	string	$value		email encoding
	 * @return	object				$this for chaining
	 */

	public function setEncodeEmail($value)
	{
		ee()->TMPL->encode_email = (bool) $value;

		return $this;
	}
	//END setEncodeEmail


	// --------------------------------------------------------------------

	/**
	 * Process Template
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @param	string|integer
	 * @return	null
	 */

	public function process_string_as_template($str)
	{
		// --------------------------------------------
		//  Solves the problem of redirect links (?URL=)
		//  being added by Typography in a CP request
		// --------------------------------------------

		if (REQ == 'CP')
		{
			$this->old_get = (isset($_GET['M'])) ? $_GET['M'] : '';
			$_GET['M'] = 'send_email';
		}

		// standardize newlines
		$str	= preg_replace("/(\015\012)|(\015)|(\012)/", "\n", $str);

		ee()->load->helper('text');

		// convert high ascii
		$str	= (
			ee()->config->item('auto_convert_high_ascii') == 'y'
		) ? ascii_to_entities($str): $str;

		// -------------------------------------
		//  Prepare for Processing
		// -------------------------------------

		//need to make sure this isn't run as static or cached
		ee()->TMPL->template_type	= 'webpage';
		ee()->TMPL->cache_status		= 'NO_CACHE';

		//restore_xml_declaration gets calls in parse_globals
		$str = ee()->TMPL->convert_xml_declaration(
			ee()->TMPL->remove_ee_comments($str)
		);

		ee()->TMPL->log_item("Template Type: ".ee()->TMPL->template_type);

		// -------------------------------------`
		//	add our globals to global vars
		// -------------------------------------

		ee()->TMPL->log_item(
			"Solspace globals added (Keys): " .
			implode('|', array_keys(ee()->TMPL->global_vars))
		);
		ee()->TMPL->log_item(
			"Solspace globals added (Values): " .
			trim(implode('|', ee()->TMPL->global_vars))
		);

		ee()->config->_global_vars = array_merge(
			ee()->config->_global_vars,
			ee()->TMPL->global_vars,
			$this->global_vars
		);

		ee()->TMPL->parse($str, false, ee()->config->item('site_id'));


		if (REQ == 'CP')
		{
			$_GET['M'] = $this->old_get;
		}

		// -------------------------------------------
		// 'template_post_parse' hook.
		//  - Modify template after tag parsing
		//
		if (ee()->extensions->active_hook('template_post_parse') === TRUE)
		{
			ee()->TMPL->final_template = ee()->extensions->call(
				'template_post_parse',
				ee()->TMPL->final_template,
				false, // $is_partial
				ee()->config->item('site_id')
			);
		}
		//
		// -------------------------------------------

		// --------------------------------------------
		//  Finish with Global Vars and Return!
		// --------------------------------------------
		$end = ee()->TMPL->parse_globals(ee()->TMPL->final_template);

		return $end;
	 }
	// END process_string_as_template


	// --------------------------------------------------------------------

	/**
	 *	Fetch Add-Ons for Instllation
	 *	This caches parent lists
	 *
	 *	@access		public
	 *	@return		null
	 */

	public function fetch_addons()
	{
		//no res
		if (count(ee()->TMPL->modules) > 0 && count(ee()->TMPL->plugins) > 0)
		{
			return;
		}

		if ( isset(static::$global_cache['fetch_modules']) &&
			isset(static::$global_cache['fetch_plugins']))
		{
			ee()->TMPL->modules = static::$global_cache['fetch_modules'];
			ee()->TMPL->plugins = static::$global_cache['fetch_plugins'];
			return;
		}

		ee()->TMPL->fetch_addons();

		static::$global_cache['fetch_modules'] = array_unique(ee()->TMPL->modules);
		static::$global_cache['fetch_plugins'] = array_unique(ee()->TMPL->plugins);

	}
	// END fetch_addons


	// --------------------------------------------------------------------

	/**
	 * Magic Call
	 *
	 * Forwarding calls to ee()->TMPL so we can use this as if it
	 * were the parser itself
	 *
	 * @access	public
	 * @param	string	$name	method name
	 * @param	[type]	$args	method args
	 * @return	void
	 */

	public function __call($name, $args)
	{
		if (is_callable(array(ee()->TMPL, $name)))
		{
			return call_user_func_array(array(ee()->TMPL, $name), $args);
		}

		throw new \Exception('Method ' . get_class($this) . '::' . $name . ' does not exist.');
	}
	//__call
}
// END parser
