<?php
namespace wygwam;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once PATH_THIRD.'wygwam/helpers/eeharbor_abstracted.php';

/**
 * EEHarbor helper class
 *
 * Bridges the functionality gaps between EE versions.
 *
 * @package         eeharbor_helper
 * @version         1.1.2
 * @author          Tom Jaeger <Tom@EEHarbor.com>
 * @link            https://eeharbor.com
 * @copyright       Copyright (c) 2016, Tom Jaeger/EEHarbor
 */

// --------------------------------------------------------------------

class EEHelper extends \wygwam\EEHarbor_abstracted {

	private $_module;
	private $_module_name;
	private $_ee_major_version;
	private $app_settings;

	public function __construct($info)
	{
		$this->_module = $info['module'];
		$this->_module_name = $info['module_name'];

		// This sets the $app_settings variable with settings from the addon.setup.php file, and the database
		$this->getSettings();

		if(!function_exists('ee')) {
			function ee() {
				return get_instance();
			}
		}
	}

	public function instantiate($which) {
		ee()->api->instantiate($which);
	}

	public function getBaseURL($method='', $extra='')
	{
		if($method == '/') $method = '';
		elseif($method) $method = AMP.'method='.$method;

		$url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->_module.$method.$extra;

		if (version_compare(APP_VER, '2.6.0', '>=') && version_compare(APP_VER, '2.7.3', '=<')) {
			// Yay, workaround for EE 2.6.0 session bug
			$config_type = 'admin_session_type';
		} else {
			$config_type = 'cp_session_type';
		}

		$s = 0;
		switch (ee()->config->item($config_type)){
			case 's':
				$s = ee()->session->userdata('session_id', 0);
				break;
			case 'cs':
				$s = ee()->session->userdata('fingerprint', 0);
				break;
		}

		// Test if our URL already has the session and directive.
		parse_str(parse_url(str_replace('&amp;', '&', $url), PHP_URL_QUERY), $url_test);

		if(!empty($s) && (!isset($url_test['S']) || empty($url_test['S']))) $url .= AMP.'S='.$s;
		if(!isset($url_test['D']) || empty($url_test['D'])) $url .= AMP.'D=cp';

		return $url;
	}

	public function getNav($nav_items=array())
	{
		foreach($nav_items as $title => $method) {
			if(strpos($method, 'http') === false) $method = $this->getBaseURL($method);

			$nav_items[$title] = $method;
		}

		ee()->cp->set_right_nav($nav_items);
	}

	public function cpURL($path, $mode='', $variables=array())
	{
		switch($path) {
			case 'listing':
				$path = 'content_edit';
				$mode = '';
				if(isset($variables['filter_by_channel'])) {
					$variables['channel_id'] = $variables['filter_by_channel'];
					unset($variables['filter_by_channel']);
				}
				break;

			case 'publish':
				$path = 'content_publish';
				if($mode == 'create' || $mode == 'edit') $mode = 'entry_form';
				break;

			case 'members':
				if($mode == 'groups') $mode = 'member_group_manager';
				break;

			case 'channels':
				$path = 'admin_content';
				if($mode == 'create') $mode = 'channel_add';
				break;

			// case 'addons':
			// 	$path = 'addon_modules';
			// 	break;
		}

		$url = BASE.AMP.'D=cp'.AMP.'C='.$path;

		if($mode) $url .= AMP.'M='.$mode;

		foreach ($variables as $variable => $value) {
			$url .= AMP . $variable . '=' . $value;
		}

		return $url;
	}

	public function moduleURL($method='index', $variables=array())
	{
		$url = $this->getBaseURL() . AMP . 'method=' . $method;

		foreach ($variables as $variable => $value) {
			$url .= AMP . $variable . '=' . $value;
		}

		return $url;
	}

	public function view($view, $vars = array(), $return = FALSE)
	{
		return ee()->load->view($view, $vars, $return);
	}

	public function getCurrentPage($options = array())
	{
		// If we have the per_page query variable, it's an offset.
		if(ee()->input->get('per_page', 1)) {
			$offset = (int) ee()->input->get('per_page', 1);
			return ($offset / $options['per_page']) + 1;
		} elseif(ee()->input->get('page', 1)) {
			return (int) ee()->input->get('page', 1);
		} else {
			return 1;
		}
	}

	public function getStartNum($options)
	{
		return ($options['current_page'] * $options['per_page']) - $options['per_page'];
	}

	public function pagination($options = array())
	{
		// Remap from normal logic to EE2 logic.
		if(isset($options['current_page'])) $options['cur_page'] = $options['current_page'];

		ee()->load->library('pagination');
		ee()->pagination->initialize($options);
		return ee()->pagination->create_links();
	}

	public function getSettings($asArray = false)
	{
		// EE caches the list of DB tables, so unset the table_names var if it's set
		// otherwise table_exists could return a false negative if it was just created.
		if(isset(ee()->db->data_cache['table_names'])) unset(ee()->db->data_cache['table_names']);

		if(ee()->db->table_exists($this->_module.'_settings')) {
			$dbSettings = ee()->db->get_where($this->_module.'_settings', array('site_id'=>ee()->config->item('site_id')))->row_array();
		} else {
			$dbSettings = array();
		}

		$addonSettings = require PATH_THIRD.'wygwam/addon.setup.php';

		$this->app_settings = (object) array_merge($dbSettings, $addonSettings);

		if($asArray) return array_merge($dbSettings, $addonSettings);

		return $this->app_settings;
	}

	public function getConfig($item)
	{
		return $this->app_settings->{$item};
	}

	public function setConfig($item, $value)
	{
		// EE caches the list of DB tables, so unset the table_names var if it's set
		// otherwise table_exists could return a false negative if it was just created.
		if(isset(ee()->db->data_cache['table_names'])) unset(ee()->db->data_cache['table_names']);

		// Make sure the settings table exists.
		if(ee()->db->table_exists($this->_module.'_settings')) {
			// Find out if the settings exist, if not, insert them.
			ee()->db->where('site_id', ee()->config->item('site_id'));
			$exists = ee()->db->count_all_results($this->_module.'_settings');

			$data['site_id'] = ee()->config->item('site_id');
			$data[$item] = $value;

			if($exists) {
				ee()->db->where('site_id', ee()->config->item('site_id'));
				ee()->db->update($this->_module.'_settings', $data);
			} else {
				ee()->db->insert($this->_module.'_settings', $data);
			}
		}
	}

	public function cache($mode, $key = false, $data = false) {
		if (! isset(ee()->session->cache[$this->_module]))
		{
		 	ee()->session->cache[$this->_module] = array();
		}

		// Returns EE's native cache function for EE2.
		switch($mode) {
			case 'get':
				if(isset(ee()->session->cache[$this->_module][$key])) return ee()->session->cache[$this->_module][$key];
				else return false;
				break;

			case 'set':
				return ee()->session->cache[$this->_module][$key] = $data;
				break;

			case 'delete':
			case 'clear':
				if($key) unset(ee()->session->cache[$this->_module][$key]);
				else unset(ee()->session->cache[$this->_module]);
				break;

			default:
				return false;
		}
	}

	/**
	 * Flash a message to the screen
	 * @param  string $type             Type of message to display. [message_success, message_notice, message_error, message_failure]
	 * @param  string $title            Title of flash message (Concatenated with body when EE2)
	 * @param  string $body             Title of flash message (Concatenated with title when EE2)
	 * @param  array  $extra_parameters Name of EE3 alert functions to call in addition to the default ones. (does nothing in EE2) ex. ['cannotClose']
	 */
	public function flashData($type='message_success', $title='', $body='', $extra_parameters=array()) {
		ee()->session->set_flashdata($type, $title . " " . $body);
	}

	/**
	 * Gets the directory for the addon's theme files
	 * @return [string] [path of directory]
	 */
	public function getAddonThemesDir() {
		return "/themes/third_party/" . $this->_module . '/';
	}

	/**
	 * Overwrite any native EE Classes.
	 * EE2 uses direct assignment.
	 *
	 * @param object $class    The EE class object you want to overwrite
	 * @param object $data     The optional data used to overwrite.
	 **/
	public function overwriteEEClass($class, $data='') {
		ee()->{$class} = $data;
	}

	/**
	 * Remove any native EE Classes.
	 * EE2 uses direct assignment.
	 *
	 * @param object $class    The EE class object you want to overwrite
	 **/
	public function removeEEClass($class) {
		ee()->{$class} = '';
	}

	/**
	 * XSS protection for user input
	 * @param  String or Array $input xss_clean accepts a string or array as input.
	 * @return Sanitized string or array
	 */
	public function xss_clean($input)
	{
		return ee()->security->xss_clean($input);
	}

	/**
	 * Get information about the current page (in the CP)
	 * @param  [string] $options option to only get a portion of the information rather than an array
	 * @return [string or array]         full path info in array, or single element
	 */
	public function getCurrentUrlInfo($options = null)
	{
		$url = @trim($_SERVER['QUERY_STRING'], "/");
		$segments = @explode( "/", $url);
		$url_info['full'] = $url;
		$url_info['cp'] = (@$segments[0] === "cp");
		$url_info['segments'] = $segments;
		$url_info['module'] = @$_GET["module"];
		$url_info['method'] = array_key_exists("method", $_GET) ? @$_GET["method"] : "index";

		if($options && array_key_exists($options, $url_info))
			return $url_info[$options];

		return $url_info;
	}

	/**
	 * Returns the system cache path
	 * @return string - path to cache
	 */
	public function getCachePath() {
		$cache_path = ee()->config->item('cache_path');

		if (empty($cache_path))
			$cache_path = APPPATH.'cache/';

		return $cache_path;
	}

	/**
	 * Provides a quick boolean for checking ee version
	 * @return boolean is_ee2
	 */
	public function is_ee2() {
		return true;
	}

	/**
	 * Provides a quick boolean for checking ee version
	 * @return boolean is_ee3
	 */
	public function is_ee3() {
		return false;
	}

	/**
	 * Call the EE method for removing double slashes. Is specific to the EE version.
	 * @return string result
	 */
	public function reduce_double_slashes($string) {
		if(version_compare(APP_VER, '2.6.0', '<')) {
			return ee()->functions->remove_double_slashes($string);
		} else {
			ee()->load->helper('string');
			return reduce_double_slashes($string);
		}
	}
}