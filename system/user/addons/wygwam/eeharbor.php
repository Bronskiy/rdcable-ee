<?php
	namespace wygwam;

	/**
	 * EEHarbor foundation
	 *
	 * Bridges the functionality gaps between EE versions.
	 * This file namespaces, and dynamically loads the correct version of the EE helper
	 *
	 * @package         eeharbor_helper
	 * @version         1.1.2
	 * @author          Tom Jaeger <Tom@EEHarbor.com>
	 * @link            https://eeharbor.com
	 * @copyright       Copyright (c) 2016, Tom Jaeger/EEHarbor
	 */

	if(defined('APP_VER')) $app_ver = APP_VER;
	else $app_ver = ee()->config->item('app_version');

	include_once PATH_THIRD.'wygwam/helpers/eeharbor_ee' . substr($app_ver, 0, 1) . '_helper.php';

	class EEHarbor extends \wygwam\EEHelper {
		function __construct()
		{
			$params = array("module" => "wygwam", "module_name" => "Wygwam");

			parent::__construct($params);
		}
	}
