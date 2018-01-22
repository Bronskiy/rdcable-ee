<?php

if ( ! class_exists('Freeform_Model'))
{
	require_once 'freeform_model.php';
}

class Freeform_param_model extends Freeform_Model
{
	public 	$id = 'params_id';

	// --------------------------------------------------------------------

	/**
	 * insert_params - adds multiple params to stored params
	 *
	 * @access	public
	 * @param	(array)  associative array of params to send
	 * @return	insert id or false
	 */

	public function insert_params ( $params = array() )
	{
		//	----------------------------------------
		//	Empty?
		//	----------------------------------------
		if ( ! is_array($params))
		{
			return FALSE;
		}

		//	----------------------------------------
		//	Serialize
		//	----------------------------------------

		$params	= json_encode( $params );

		$this->cleanup();

		//----------------------------------------
		//	Insert
		//----------------------------------------

		$this->insert(array(
			'entry_date'	=> ee()->localize->now,
			'data'			=> $params
		));

		//----------------------------------------
		//	Return
		//----------------------------------------

		return $this->db->insert_id();
	}
	//	End insert params


	// --------------------------------------------------------------------

	/**
	 * Cleans up any old param
	 *
	 * @access public
	 * @return object this for chaining
	 */

	public function cleanup()
	{
		//	----------------------------------------
		//	Delete excess when older than 2 hours
		//	----------------------------------------

		$this->delete(array(
			'entry_date <' => ee()->localize->now - 7200
		));

		return $this;
	}
}
//END Freeform_param_model
