<?php

if ( ! class_exists('Freeform_Model'))
{
	require_once 'freeform_model.php';
}

class Freeform_field_model extends Freeform_Model
{
	public $before_get = array('sort_get_on_name');

	public $_default_inputs = array(
		//hidden
		'field_id'					=> array(
			'label'		=> '',
			'desc'		=> '',
			'type'		=> 'hidden',
			'default'	=> '0',
			'validate'	=> '',
		),

		//non-hidden
		'field_type'				=> array(
			'label'		=> 'field_type',
			'desc'		=> '',
			'type'		=> 'select',
			//filled with getter
			'choices'	=> array(),
			'default'	=> 'text',
			'validate'	=> '',
		),
		'field_label'				=> array(
			'label'		=> 'field_label',
			'desc'		=> 'field_label_desc',
			'type'		=> 'text',
			'default'	=> '',
			'validate'	=> '',
			'required'	=> true
		),
		'field_name'					=> array(
			'label'		=> 'field_name',
			'desc'		=> 'field_name_desc',
			'type'		=> 'text',
			'default'	=> '',
			'validate'	=> '',
			'required'	=> true,
		),
		'field_description'			=> array(
			'label'		=> 'description',
			'desc'		=> 'field_description_desc',
			'type'		=> 'textarea',
			'default'	=> '',
			'validate'	=> '',
		),
		//'field_length'			=> array(
		//	'label'		=> 'field_length',
		//	'desc'		=> 'field_length_desc',
		//	'type'		=> 'text',
		//	'default'	=> '150',
		//	'validate'	=> '',
		//),
		'submissions_page'			=> array(
			'label'		=> 'submissions_page',
			'desc'		=> '',
			'type'		=> 'yes_no',
			'default'	=> 'y',
			'validate'	=> '',
		),
		'moderation_page'			=> array(
			'label'		=> 'moderation_page',
			'desc'		=> '',
			'type'		=> 'yes_no',
			'default'	=> 'y',
			'validate'	=> '',
		),
		'composer_use'			=> array(
			'label'		=> 'composer_use',
			'desc'		=> '',
			'type'		=> 'yes_no',
			'default'	=> 'y',
			'validate'	=> '',
		),
		'form_ids'			=> array(
			'label'		=> 'add_to_forms',
			'desc'		=> 'add_to_forms_desc',
			'type'		=> 'checkbox',
			'default'	=> '',
			'validate'	=> '',
		),
	);


// --------------------------------------------------------------------

	/**
	 * Input Defaults
	 *
	 * @access	public
	 * @return	array		array of input information
	 */

	public function input_defaults()
	{
		$out = $this->_default_inputs;

		$availableForms = $this->model('form')->key('form_id')->get();
		$formChoices    = array();

        if ($availableForms) {
            foreach ($availableForms as $form) {
                $formChoices[$form['form_id']] = $form['form_label'];
            }
        }

		$out['form_ids']['choices'] = $formChoices;

		//get field types

		$fieldtypes = $this->lib('Fields')->get_available_fieldtypes();

		foreach ($fieldtypes as $lower_name => $data)
		{
			$out['field_type']['choices'][$lower_name] = $data['name'];
		}

		return $out;
	}
	//end input_defaults


	// --------------------------------------------------------------------

	/**
	 * Sort fieldname output by field name
	 *
	 * @access public
	 * @param  array $where wheres for get()
	 * @return array        adjusted wheres
	 */

	public function sort_get_on_name ($where)
	{
		$stash = ($this->isolated) ? 'db_isolated_stash' : 'db_stash';

		$has_orderby = FALSE;

		foreach ($this->{$stash} as $call)
		{
			if ($call[0] == 'order_by')
			{
				$has_orderby = TRUE;
			}
		}

		if ( ! $has_orderby)
		{
			$this->order_by('field_name', 'asc');
		}

		return $where;
	}
	//END sort_get_on_name


	// --------------------------------------------------------------------

	/**
	 * get_column_name
	 *
	 * @access	public
	 * @param 	string 	the item to search
	 * @param 	string  search on id or name
	 * @return	string
	 */

	public function get_column_name ($search, $on = 'id')
	{
		$result	= FALSE;
		$row	= FALSE;

		//why check ID if we have ID?
		//need to know if it exists
		if ($on == 'id' AND $this->is_positive_intlike($search))
		{
			$row = $this->get_row($search);
		}
		else if ($on == 'name' AND is_string($search))
		{
			$row = $this->get_row(array('field_name' => $search));
		}

		if ($row and isset($row['field_id']))
		{
			$result = $this->form_field_prefix . $row['field_id'];
		}

		return $result;
	}
	//END get_column_name
}
//END Freeform_field_model
