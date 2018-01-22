<?php

class Textarea_freeform_ft extends Freeform_base_ft
{
	public 	$info 	= array(
		'name' 			=> 'Textarea',
		'version' 		=> '',
		'description' 	=> 'A field for multi-line text input.'
	);

	public $default_rows = 6;


	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct()
	{
		parent::__construct();

		$this->info['name'] 		= lang('default_textarea_name');
		$this->info['description'] 	= lang('default_textarea_desc');
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Display Entry in the CP
	 *
	 * formats data for cp entry
	 *
	 * @access	public
	 * @param 	string 	data from table for email output
	 * @return	string 	output data
	 */

	public function display_entry_cp ($data)
	{
		if (isset($this->settings['disallow_html_rendering']) AND
			$this->settings['disallow_html_rendering'] == 'n')
		{
			return ee()->functions->encode_ee_tags($data, TRUE);
		}
		else
		{
			return $this->form_prep_encode_ee($data);
		}
	}
	//END display_entry_cp


	// --------------------------------------------------------------------

	/**
	 * Replace Tag
	 *
	 * @access	public
	 * @param	string 	data
	 * @param 	array 	params from tag
 	 * @param 	string 	tagdata if there is a tag pair
	 * @return	string
	 */

	public function replace_tag ($data, $params = array(), $tagdata = FALSE)
	{
		if (isset($this->settings['disallow_html_rendering']) AND
			$this->settings['disallow_html_rendering'] == 'n')
		{
			return ee()->functions->encode_ee_tags($data, TRUE);
		}
		else
		{
			return $this->form_prep_encode_ee($data);
		}
	}
	//END replace_tag

	// --------------------------------------------------------------------

	/**
	 * Display Field
	 *
	 * @access	public
	 * @param	string 	saved data input
	 * @param  	array 	input params from tag
	 * @param 	array 	attribute params from tag
	 * @return	string 	display output
	 */

	public function display_field ($data = '', $params = array(), $attr = array())
	{
		return form_textarea(array_merge(array(
			'name'	=> $this->field_name,
			'id'	=> 'freeform_' . $this->field_name,
			'value'	=> ee()->functions->encode_ee_tags($data, TRUE),
			'rows'	=> isset($this->settings['field_ta_rows']) ?
						$this->settings['field_ta_rows'] :
						$this->default_rows,
			'cols'	=> '50'
		), $attr));
	}
	//END display_field


	// --------------------------------------------------------------------

	/**
	 * Display Field Settings
	 *
	 * @access	public
	 * @param	array
	 * @return	string
	 */

	public function display_settings ($data = array())
	{
		$sub_settings = array();

		$field_rows	= ( ! isset($data['field_ta_rows']) OR
						$data['field_ta_rows'] == '') ?
							$this->default_rows :
							$data['field_ta_rows'];

		$sub_settings['field_ta_rows'] = array(
			'title'		=> 'textarea_rows',
			'desc'		=> 'textarea_rows_desc',
			'attrs'		=> array('id' => 'field_ta_rows'),
			'fields'	=> array(
				'field_ta_rows'	=> array(
					'type'		=> 'text',
					'value'		=> $field_rows,
					'required'	=> true
				)
			)
		);

		$disallow_html_rendering	= ( ! isset($data['disallow_html_rendering']) OR
						$data['disallow_html_rendering'] == '') ?
							'y' :
							$data['disallow_html_rendering'];

		$sub_settings['disallow_html_rendering'] = array(
			'title'		=> 'disallow_html_rendering',
			'desc'		=> 'disallow_html_rendering_desc',
			'attrs'		=> array('id' => 'disallow_html_rendering'),
			'fields'	=> array(
				'disallow_html_rendering'	=> array(
					'type'		=> 'yes_no',
					'value'		=> $disallow_html_rendering,
					'required'	=> true
				)
			)
		);

		$settings = array(
			$this->field_name => array(
				'label'		=> $this->info['name'],
				'group'		=> $this->field_name,
				'settings'	=> $sub_settings
			)
		);

		return $settings;
	}
	//END display_settings


	// --------------------------------------------------------------------

	/**
	 * Save Field Settings
	 *
	 * @access	public
	 * @return	string
	 */

	public function save_settings($data = array())
	{
		$field_rows 	= ee()->input->get_post('field_ta_rows');

		$field_rows 	= (
			is_numeric($field_rows) AND
			$field_rows > 0
		) ?	$field_rows : $this->default_rows;

		return array(
			'field_ta_rows'				=> $field_rows,
			'disallow_html_rendering'	=> (
				ee()->input->get_post('disallow_html_rendering') == 'n' ? 'n' : 'y'
			)
		);
	}
	//END save_settings
}
//END class Textarea_freeform_ft
