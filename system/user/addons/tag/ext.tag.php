<?php

use Solspace\Addons\Tag\Library\AddonBuilder;

class Tag_ext extends AddonBuilder
{
	public $name			= "Tag";
	public $version			= "";
	public $description		= "";
	public $settings_exist	= "n";
	public $docs_url		= "https://solspace.com/expressionengine/tag/docs";
	public $required_by		= array('module');

	/**
	 * Shim for removed extension calls
	 * that will get hit before upgrade
	 *
	 * @var	array
	 * @see	__call
	 */
	protected $removed_functions = array(
		'entry_submission_end',
		'delete_entries_start',
		'sessions_end',
		'cp_js_end'
	);

	// --------------------------------------------------------------------

	/**
	 *	Constructor
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		null
	 */

	public function __construct($settings = '')
	{
		// --------------------------------------------
		//  Load Parent Constructor
		// --------------------------------------------

		parent::__construct('extension');

		// --------------------------------------------
		//  Settings!
		// --------------------------------------------

		$this->settings = $settings;
	}
	//END constructor

	// --------------------------------------------------------------------
	// Note:
	//
	// Even if you remove all functions from this file,
	// people who are updating from older versions will receive
	// errors if the hooks fire before you can run the update script.
	// In such a case we would have to leave at least a __call function
	// to handle those calls until the update could be run to
	// remove the extension hooks.
	//
	//
	// This is especially true for sessions_end and cp_js_end hooks
	// that will always run in the CP.
	// --------------------------------------------------------------------

	// --------------------------------------------------------------------

	/**
	 * Magic Call Method
	 *
	 * Used here to shim out removed hook calls so no errors show
	 * when we are needing to upgrade to a new version that doesn't
	 * have the removed function.
	 *
	 * @access	public
	 * @param	string	$method	desired method
	 * @param	array	$args	method ards
	 * @return	mixed			last call, or FALSE, or null if method not removed
	 */

	public function __call($method = '', $args = array())
	{
		if (in_array($method, $this->removed_functions))
		{
			return $this->get_last_call(
				( ! empty($args)) ? array_shift($args) : FALSE
			);
		}
	}
	//END __call
}
//END class
