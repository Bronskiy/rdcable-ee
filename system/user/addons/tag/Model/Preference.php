<?php

namespace Solspace\Addons\Tag\Model;
use EllisLab\ExpressionEngine\Service\Model\Model;

class Preference extends Model
{

	protected static $_primary_key	= 'tag_preference_id';
	protected static $_table_name	= 'tag_preferences';

	protected $tag_preference_id;
	protected $tag_preference_name;
	protected $tag_preference_value;
	protected $site_id;

	protected $_default_prefs = array(
		'separator'						=> array(
			'type'		=> 'select',
			'choices'	=> array(),
			'default'	=> 'comma',
			'validate'	=> '',
		),
		'convert_case'					=> array(
			'type'		=> 'yes_no',
			'default'	=> 'y',
			'validate'	=> 'enum[y,n]'
		),
		'allow_tag_creation_publish'	=> array(
			'type'		=> 'yes_no',
			'default'	=> 'y',
			'validate'	=> 'enum[y,n]',
		),
		'publish_entry_tag_limit'		=> array(
			'type'		=> 'text',
			'default'	=> '0',
			'validate'	=> 'isNatural',
		),
		'explode_input_on_separator'	=> array(
			'type'		=> 'yes_no',
			'default'	=> 'n',
			'validate'	=> 'enum[y,n]',
		),
		'enable_explode_controls'		=> array(
			'type'		=> 'yes_no',
			'default'	=> 'n',
			'validate'	=> 'enum[y,n]',
		),
	);

	protected $_delimiters = array(
		'colon'			=> ':',
		'comma'			=> ',',
		'doublepipe'	=> '||',
		'newline'		=> "\n",
		'pipe'			=> '|',
		'semicolon' 	=> ';',
		'space'			=> ' ',
		'tab'			=> "\t",
		'tilde'			=> '~',
	);

	// --------------------------------------------------------------------

	/**
	 * Validate Default Prefs
	 *
	 * Since we often use multiple prefs, lets validate default prefs
	 * and leave alone the ones not meant to be in the prefs page
	 *
	 * @access	public
	 * @param	array	$inputs		incoming inputs to validate
	 * @param	array	$required	array of names of required items
	 * @return	object				instance of validator result
	 */

	public function validateDefaultPrefs($inputs = array(), $required = array())
	{
		//not a typo, see get__default_prefs
		$prefsData = $this->default_prefs;

		$rules = array();

		foreach ($prefsData as $name => $data)
		{
			if (isset($data['validate']))
			{
				$r = (in_array($name, $required)) ? 'required|' : '';

				$rules[$name] = $r . $data['validate'];
			}
		}

		return ee('Validation')->make($rules)->validate($inputs);
	}
	//END validateDefaultPrefs


	// --------------------------------------------------------------------

	/**
	 * Getter: default_prefs
	 *
	 * loads items with lang lines and choices before sending off.
	 * (Requires ee('Model')->make() to access.)
	 *
	 * @access	public
	 * @return	array		key->value array of pref names and defaults
	 */

	public function get__default_prefs()
	{
		//just in case this gets removed in the future.
		if (isset(ee()->lang) && method_exists(ee()->lang, 'loadfile'))
		{
			ee()->lang->loadfile('tag');
		}

		$prefs = $this->_default_prefs;

		$prefs['separator']['choices'] = array();

		foreach ($this->delimiters as $delim_name => $delim)
		{
			$prefs['separator']['choices'][$delim_name] = lang($delim_name);
		}

		$prefs['separator']['validate'] = 'enum[' . implode(',', array_keys($this->delimiters)) . ']';

		return $prefs;
	}
	//END get__default_prefs


	// --------------------------------------------------------------------

	/**
	 * Getter:  delimiters
	 *
	 * For now just returns $this->_delimiters. We don't want to colide
	 * with columns for the moment.
	 *
	 * @access	public
	 * @return	array		key->value array of delimiter names and real values
	 */

	public function get__delimiters()
	{
		return $this->_delimiters;
	}
	//END get__delimiters
}
//END Preference
