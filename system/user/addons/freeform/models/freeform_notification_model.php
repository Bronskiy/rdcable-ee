<?php

if ( ! class_exists('Freeform_Model'))
{
	require_once 'freeform_model.php';
}

class Freeform_notification_model extends Freeform_Model
{
	//nonstandard name
	public $_table = 'freeform_notification_templates';

	public $default_prefs = array(
		'notification_id'	=> array(
			'type'		=> 'hidden',
			'default'	=> '',
		),
		'site_id'	=> array(
			'type'		=> 'hidden',
			'default'	=> '',
		),
		'notification_label'	=> array(
			'label'		=> 'notification_label',
			'desc'		=> 'notification_label_desc',
			'type'		=> 'text',
			'default'	=> '',
			'required'	=> true
		),
		'notification_name'	=> array(
			'label'		=> 'notification_name',
			'desc'		=> 'notification_name_desc',
			'type'		=> 'text',
			'default'	=> '',
			'validate'	=> 'required|alphaDash',
			'required'	=> true
		),
		'notification_description'	=> array(
			'label'		=> 'notification_description',
			'desc'		=> 'notification_description_desc',
			'type'		=> 'textarea',
			'default'	=> '',
			'validate'	=> '',
		),
		'from_name'	=> array(
			'label'		=> 'from_name',
			'desc'		=> 'from_name_desc',
			'type'		=> 'text',
			'default'	=> '%webmaster_name%',
			'validate'	=> 'required',
			'required'	=> true
		),
		'from_email'	=> array(
			'label'		=> 'from_email',
			'desc'		=> 'from_email_desc',
			'type'		=> 'text',
			'default'	=> '%webmaster_email%',
			'validate'	=> 'required',
			'required'	=> true
		),
		'reply_to_email'	=> array(
			'label'		=> 'reply_to_email',
			'desc'		=> 'reply_to_email_desc',
			'type'		=> 'text',
			'default'	=> '%webmaster_email%',
			'validate'	=> ''
		),
		'email_subject'	=> array(
			'label'		=> 'email_subject',
			'desc'		=> 'email_subject_desc',
			'type'		=> 'text',
			'default'	=> '',
			'validate'	=> 'required',
			'required'	=> true
		),
		'wordwrap'	=> array(
			'type'		=> 'yes_no',
			'default'	=> 'y',
			'validate'	=> 'enum[y,n]',
			'required'	=> true
		),
		'allow_html'	=> array(
			'type'		=> 'yes_no',
			'default'	=> 'n',
			'validate'	=> 'enum[y,n]',
			'required'	=> true
		),
		'include_attachments'	=> array(
			'label'		=> 'include_attachments',
			'desc'		=> 'include_attachments_desc',
			'type'		=> 'yes_no',
			'default'	=> 'n',
			'validate'	=> 'enum[y,n]',
			'required'	=> true
		),
		'template_data'	=> array(
			'label'		=> 'notification_data',
			'desc'		=> 'notification_data_desc',
			'type'		=> 'textarea',
			'default'	=> '',
			'validate'	=> 'required',
			'required'	=> true,
			'attrs'		=> ' style="min-height:300px"'
		),
	);


	// --------------------------------------------------------------------

	/**
	 * get_available
	 *
	 * @access	public
	 * @return	array types of notifications with id=>label pairs
	 */

	public function get_available()
	{
		if ( ! $this->model('preference')->show_all_sites())
		{
			$this->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		$notifications = $this->key('notification_id', 'notification_label')->get();

		$items = array('0' => lang('default'));

		if ($notifications)
		{
			foreach ($notifications as $key => $value)
			{
				$items[$key] = $value;
			}
		}

		return $items;
	}
	//get_available
}
//END Freeform_preference_model
