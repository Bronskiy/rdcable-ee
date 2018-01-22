<?php

namespace Solspace\Addons\Tag\Model;
use EllisLab\ExpressionEngine\Service\Model\Model;

//extend model and add some helpful methods
class BaseModel extends Model
{
	private $addonName;


	// --------------------------------------------------------------------

	/**
	 * Make a new verison of this model
	 *
	 * @access	protected
	 * @return	object		instance of this object via EE make
	 */

	protected function make(array $data = array())
	{
		return $this->getModelFacade()->make($this->getName(), $data);
	}
	//END make


	// --------------------------------------------------------------------

	/**
	 * Fetch for this object
	 *
	 * @access	protected
	 * @return	object		instance of the query biulder for this object via EE get
	 */

	protected function fetch($default_ids = null)
	{
		return $this->getModelFacade()->get($this->getName(), $default_ids);
	}
	//END fetch


	// --------------------------------------------------------------------

	/**
	 * To Array
	 *
	 * @access	public
	 * @return	array	key->value array of not null object var values
	 */

	public function asArray()
	{
		$fields = $this->getFields();

		$result = array();

		foreach ($fields as $fieldName)
		{
			if (substr($fieldName, 0, 1) !== '_' &&
				isset($this->$fieldName) &&
				$this->$fieldName !== null)
			{
				$result[$fieldName] = $this->$fieldName;
			}
		}
		return $result;
	}
	//END asArray


	// --------------------------------------------------------------------

	/**
	 * Get Table Name (not sure why this isn't defined)
	 *
	 * @access	public
	 * @return	string		table name
	 */

	public function getTableName()
	{
		return static::$_table_name;
	}
	//END getTableName
}
//END BaseModel
