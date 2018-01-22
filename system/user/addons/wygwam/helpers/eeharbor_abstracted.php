<?php
namespace wygwam;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * EEHarbor helper class
 *
 * Bridges the functionality gaps between EE versions.
 *
 * @package         EEHarbor_abstracted
 * @version         1.1.2
 * @author          Tom Jaeger <Tom@EEHarbor.com>
 * @link            https://eeharbor.com
 * @copyright       Copyright (c) 2016, Tom Jaeger/EEHarbor
 */

abstract class EEHarbor_abstracted
{
	private $_ee_major_version;

    public function getEEVersion($major=true){
    	$this->_ee_major_version = substr(APP_VER, 0, 1);

    	if($major == true) return $this->_ee_major_version;
		else return APP_VER;
    }

    // Force Extending class to define these methods
    abstract public function instantiate($which);
	abstract public function getBaseURL($method='', $extra='');
	abstract public function getNav($nav_items=array());
	abstract public function cpURL($path, $mode='', $variables=array());
	abstract public function moduleURL($method='index', $variables=array());
	abstract public function view($view, $vars=array(), $return=false);
	abstract public function getCurrentPage($options=array());
	abstract public function getStartNum($options);
	abstract public function pagination($options=array());
	abstract public function getSettings($asArray=false);
	abstract public function getConfig($item);
	abstract public function setConfig($item, $value);
	abstract public function cache($mode, $key=false, $data=false);
	abstract public function flashData($type='message_success', $title='', $body='', $extra_parameters=array());
	abstract public function getAddonThemesDir();
	abstract public function xss_clean($input);
	abstract public function getCurrentUrlInfo($options = null);
	abstract public function getCachePath();
	abstract public function is_ee2();
	abstract public function is_ee3();
	abstract public function reduce_double_slashes($string);

}