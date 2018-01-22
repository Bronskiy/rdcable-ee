<?php

namespace PT\Wygwam;
if( file_exists(PATH_THIRD.'assets/helper.php'))
	include_once(PATH_THIRD .'assets/helper.php');

/**
 * Wygwam Helper Class
 *
 * @package   Wygwam
 * @author    EEHarbor <help@eeharbor.com>
 * @copyright Copyright (c) Copyright (c) 2016 EEHarbor
 */

class Helper
{
	public static $entrySiteId;

	private static $_globalSettings;
	private static $_themeUrl;
	private static $_includedFieldResources = FALSE;
	private static $_includedConfigs;
	private static $_fileTags;
	private static $_pageTags;
	private static $_sitePages;
	private static $_pageData;

	private static $_toolbarButtonGroups;
	private static $_toolbarButtonCombos;
	private static $_toolbarButtonLabelOverrides;


	/**
	 * Return Info model.
	 *
	 * @return \EllisLab\ExpressionEngine\Core\Provider $providerObject
	 */
	public static function getInfo()
	{
		return ee('App')->get('wygwam');
	}

	/*
	 * Return a generated URL to a Wygwam action
	 *
	 * @param string $action
	 * @param array  $params
	 *
	 * @return string $url
	 */
	public static function getMcpUrl($action = 'index', $params = array())
	{
		return ee('CP/URL')->make('addons/settings/wygwam/'.$action, $params)->compile();
	}

	/**
	 * Gets Wygwam's global settings.
	 *
	 * @return array $globalSettings
	 */
	public static function getGlobalSettings()
	{
		if (! isset(static::$_globalSettings))
		{
			$model = static::getFieldtypeModel();

			if (!$model)
			{
				return array();
			}

			$defaults = array(
				'license_key' => '',
				'file_browser' => 'ee'
			);

			static::$_globalSettings = array_merge($defaults, $model->settings);
		}

		return static::$_globalSettings;
	}

	/**
	 * Sets Wygwam's global settings.
	 *
	 * @param array $globalSettings
	 */
	public static function setGlobalSettings($globalSettings)
	{
		static::$_globalSettings = $globalSettings;
	}

	/**
	 * Get the Wygwam fieldtype model.
	 *
	 * @return \EllisLab\ExpressionEngine\Model\Addon\Fieldtype|null $fieldType
	 */
	public static function getFieldtypeModel()
	{
		return ee('Model')->get('Fieldtype')->filter('name', '==', 'wygwam')->first();
	}

	// --------------------------------------------------------------------

	/**
	 * Returns toolbar button groupings, based on CKEditor's default "Full" toolbar.
	 *
	 * @return array $groups
	 */
	public static function toolbarButtonGroups()
	{
		if (!isset(static::$_toolbarButtonGroups))
		{
			static::$_toolbarButtonGroups = array(
				array('Source'),
				array('Templates'),
				array('Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord'),
				array('Undo', 'Redo'),
				array('Scayt'),
				array('Bold', 'Italic', 'Underline', 'Strike'),
				array('Subscript', 'Superscript'),
				array('RemoveFormat'),
				array('NumberedList', 'BulletedList'),
				array('Outdent', 'Indent'),
				array('JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'),
				array('Blockquote', 'CreateDiv'),
				array('Link', 'Unlink', 'Anchor'),
				array('Image', 'Table', 'HorizontalRule', 'SpecialChar', 'MediaEmbed'),
				array('ReadMore'),
				array('Styles'),
				array('Format'),
				array('TextColor', 'BGColor'),
				array('Maximize', 'ShowBlocks'),
			);

			// -------------------------------------------
			//  'wygwam_tb_groups' hook
			//   - Allow extensions to modify the available toolbar groups
			//
			if (ee()->extensions->active_hook('wygwam_tb_groups'))
			{
				static::$_toolbarButtonGroups = ee()->extensions->call('wygwam_tb_groups', static::$_toolbarButtonGroups);
			}
			//
			// -------------------------------------------
		}

		return static::$_toolbarButtonGroups;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns which toolbar items are combos.
	 *
	 * @return array $combos
	 */
	public static function toolbarButtonCombos()
	{
		if (!isset(static::$_toolbarButtonCombos))
		{
			static::$_toolbarButtonCombos = array('Styles', 'Format');

			// -------------------------------------------
			//  'wygwam_tb_combos' hook
			//   - Allow extensions to modify which toolbar items should be considered selects.
			//
			if (ee()->extensions->active_hook('wygwam_tb_combos'))
			{
				static::$_toolbarButtonCombos = ee()->extensions->call('wygwam_tb_combos', static::$_toolbarButtonCombos);
			}
			//
			// -------------------------------------------
		}

		return static::$_toolbarButtonCombos;
	}

	/**
	 * Returns the real toolbar button names.
	 *
	 * @return array $overrides
	 */
	public static function toolbarLabelOverrides()
	{
		if (!isset(static::$_toolbarButtonLabelOverrides))
		{
			static::$_toolbarButtonLabelOverrides = array(
				'PasteText'      => 'Paste As Plain Text',
				'PasteFromWord'  => 'Paste from Word',
				'Scayt'          => 'Spell Check As You Type',
				'RemoveFormat'   => 'Remove Format',
				'Strike'         => 'Strike Through',
				'NumberedList'   => 'Insert/Remove Numbered List',
				'BulletedList'   => 'Insert/Remove Bulleted List',
				'Outdent'        => 'Decrease Indent',
				'Indent'         => 'Increase Indent',
				'CreateDiv'      => 'Create Div Container',
				'HorizontalRule' => 'Insert Horizontal Line',
				'About'          => 'About CKEditor',
				'MediaEmbed'     => 'Embed Media',
				'ReadMore'       => 'Read More',
				'ShowBlocks'     => 'Show Blocks',
			);

			// -------------------------------------------
			//  'wygwam_tb_label_overrides' hook
			//   - Allow extensions to modify which toolbar items should be considered selects.
			//
			if (ee()->extensions->active_hook('wygwam_tb_label_overrides'))
			{
				static::$_toolbarButtonLabelOverrides = ee()->extensions->call('wygwam_tb_label_overrides', static::$_toolbarButtonLabelOverrides);
			}
			//
			// -------------------------------------------
		}

		return static::$_toolbarButtonLabelOverrides;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns a map of common EE language folder names to CKEditor language codes.
	 *
	 * @return array $languageMap
	 */
	public static function languageMap()
	{
		return array(
			'arabic'              => 'ar',
			'arabic-utf8'         => 'ar',
			'arabic-windows-1256' => 'ar',
			'czech'               => 'cs',
			'cesky'               => 'cs',
			'danish'              => 'da',
			'german'              => 'de',
			'deutsch'             => 'de',
			'english'             => 'en',
			'spanish'             => 'es',
			'spanish_ee201pb'     => 'es',
			'finnish'             => 'fi',
			'french'              => 'fr',
			'hungarian'           => 'hu',
			'croatian'            => 'hr',
			'italian'             => 'it',
			'japanese'            => 'ja',
			'korean'              => 'ko',
			'dutch'               => 'nl',
			'norwegian'           => 'no',
			'polish'              => 'pl',
			'brazilian'           => 'pt',
			'portuguese'          => 'pt',
			'brasileiro'          => 'pt',
			'brasileiro_160'      => 'pt',
			'russian'             => 'ru',
			'russian_utf8'        => 'ru',
			'russian_win1251'     => 'ru',
			'slovak'              => 'sk',
			'swedish'             => 'sv',
			'swedish_ee20pb'      => 'sv',
			'turkish'             => 'tr',
			'ukrainian'           => 'uk',
			'chinese'             => 'zh',
			'chinese_traditional' => 'zh',
			'chinese_simplified'  => 'zh'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Returns the default config settings.
	 *
	 * @return array $configSettings
	 */
	public static function defaultConfigSettings()
	{
		$toolbars = static::defaultToolbars();

		return array(
			'toolbar'        => $toolbars['Basic'],
			'height'         => '200',
			'resize_enabled' => 'y',
			'contentsCss'    => array(),
			'parse_css'      => FALSE,
			'restrict_html'  => 'y',
			'upload_dir'     => ''
		);
	}

	/**
	 * Returns the default toolbars.
	 *
	 * @return array $toolbars
	 */
	public static function defaultToolbars()
	{
		return array(
			'Basic' => array('Bold','Italic','Underline','NumberedList','BulletedList','Link','Unlink','Anchor'),
			'Full'  => array('Source','Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord','Undo', 'Redo','Scayt','Bold', 'Italic', 'Strike','RemoveFormat','NumberedList', 'BulletedList','Outdent', 'Indent','Blockquote','Link', 'Unlink', 'Anchor','Image', 'Table', 'HorizontalRule', 'SpecialChar','ReadMore','Styles','Format','Maximize')
		);
	}

	/**
	 * Returns the default Wygwam field settings.
	 *
	 * @return array $settings
	 */
	public static function defaultSettings()
	{
		return array(
			'config_id' => '',
			'defer'     => 'n'
		);
	}

	/**
	 * Returns the base CKEditor config.
	 *
	 * @return array $config
	 */
	public static function baseConfig()
	{
		return array_merge(array(
			'skin'                          => 'wygwam',
			'toolbarCanCollapse'            => 'n',
			'dialog_backgroundCoverOpacity' => 0,
			'entities_processNumerical'     => 'y',
			'forcePasteAsPlainText'         => 'y'
		), static::defaultConfigSettings());
	}

	// --------------------------------------------------------------------

	/**
	 * Converts flat array of buttons into multi-dimensional
	 * array of tool groups and their buttons.
	 *
	 * @param array $buttons
	 * @param bool  $includeMissing should missing buttons be included
	 *
	 * @return array $result
	 */
	public static function createToolbar($buttons, $includeMissing = FALSE)
	{
		$toolbar = array();

		// EmbedMedia => MediaEmbed
		$key = array_search('EmbedMedia', $buttons);

		if ($key !== FALSE)
		{
			$buttons[$key] = 'MediaEmbed';
		}

		// group buttons by toolgroup
		$toolbarButtonGroups = static::toolbarButtonGroups();

		foreach($toolbarButtonGroups as $groupIndex => &$group)
		{
			$groupSelectionIndex = NULL;
			$missing = array();

			foreach($group as $buttonIndex => &$button)
			{
				// selected?
				if (($buttonSelectionIndex = array_search($button, $buttons)) !== FALSE)
				{
					if ($groupSelectionIndex === NULL)
					{
						$groupSelectionIndex = $buttonSelectionIndex;
					}

					if ( ! isset($toolbar[$groupSelectionIndex]))
					{
						$toolbar[$groupSelectionIndex] = array();
					}

					$toolbar[$groupSelectionIndex]['b'.$buttonIndex] = $button;
				}
				else if ($includeMissing)
				{
					$missing['b'.$buttonIndex] = '!'.$button;
				}
			}

			if ($groupSelectionIndex !== NULL)
			{
				if ($includeMissing)
				{
					$toolbar[$groupSelectionIndex] = array_merge($missing, $toolbar[$groupSelectionIndex]);
				}

				ksort($toolbar[$groupSelectionIndex]);
				$toolbar[$groupSelectionIndex] = array_values($toolbar[$groupSelectionIndex]);
			}
		}

		// sort by keys and remove them
		ksort($toolbar);
		$result = array();

		foreach ($toolbar as $toolGroup)
		{
			array_push($result, $toolGroup);
		}

		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns the boolean config settings.
	 *
	 * @return array $list
	 */
	public static function configBooleans()
	{
		return array(
			'autoGrow_onStartup',
			'autoParagraph',
			'colorButton_enableMore',
			'dialog_noConfirmCancel',
			'disableNativeSpellChecker',
			'disableObjectResizing',
			'disableReadonlyStyling',
			'editingBlock',
			'entities',
			'entities_greek',
			'entities_latin',
			'entities_processNumerical',
			'fillEmptyBlocks',
			'forceEnterMode',
			'forcePasteAsPlainText',
			'forceSimpleAmpersand',
			'fullPage',
			'htmlEncodeOutput',
			'ignoreEmptyParagraph',
			'image_removeLinkByEmptyURL',
			'pasteFromWordNumberedHeadingToList',
			'pasteFromWordPromptCleanup',
			'pasteFromWordRemoveFontStyles',
			'pasteFromWordRemoveStyles',
			'readOnly',
			'resize_enabled',
			'scayt_autoStartup',
			'startupFocus',
			'startupOutlineBlocks',
			'templates_replaceContent',
			'toolbarCanCollapse',
			'toolbarGroupCycling',
			'toolbarStartupExpanded'
		);
	}

	/**
	 * Returns the config settings that are lists.
	 *
	 * @return array $list
	 */
	public static function configLists()
	{
		return array(
			'contentsCss',
			'templates_files'
		);
	}

	/**
	 * Returns the config settings that are literals.
	 *
	 * @return array $list
	 */
	public static function configLiterals()
	{
		return array(
			'enterMode',
			'on',
			'stylesheetParser_skipSelectors',
			'stylesheetParser_validSelectors',
			'filebrowserBrowseFunc',
			'filebrowserLinkBrowseFunc',
			'filebrowserImageBrowseFunc',
			'filebrowserFlashBrowseFunc',
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Returns Wygwam's themes folder URL.
	 *
	 * @return string $url
	 */
	public static function themeUrl()
	{
		if (! isset(static::$_themeUrl))
		{
			$themeFolderUrl = defined('URL_THIRD_THEMES') ? URL_THIRD_THEMES : ee()->config->slash_item('theme_folder_url').'third_party/';
			static::$_themeUrl = $themeFolderUrl.'wygwam/';
		}

		return static::$_themeUrl;
	}

	/**
	 * Includes a CSS file in the page head.
	 *
	 * @param string $file
	 */
	public static function includeThemeCss($file)
	{
		ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.static::themeUrl().$file.'" />');
	}

	/**
	 * Includes a JS file in the page foot.
	 *
	 * @param string $file
	 */
	public static function includeThemeJs($file)
	{
		ee()->cp->add_to_foot('<script type="text/javascript" src="'.static::themeUrl().$file.'"></script>');
	}

	/**
	 * Insert CSS in the page head.
	 *
	 * @param string $css
	 */
	public static function insertCss($css)
	{
		ee()->cp->add_to_head('<style type="text/css">'.$css.'</style>');
	}

	/**
	 * Insert JS in the page foot.
	 *
	 * @param string $js
	 */
	public static function insertJs($js)
	{
		ee()->cp->add_to_foot('<script type="text/javascript">'.$js.'</script>');
	}

	/**
	 * Includes the necessary CSS and JS files to get Wygwam fields working.
	 */
	public static function includeFieldResources()
	{
		if (! static::$_includedFieldResources)
		{
			static::includeThemeJs('lib/ckeditor/ckeditor.js');
			static::includeThemeJs('lib/ckeditor/config.js');
			static::includeThemeJs('scripts/wygwam.js');
			static::includeThemeCss('styles/wygwam.css');
			static::insertCss('.content_elements_icon_wygwam { background: url('.static::themeUrl().'images/ce_icon.png); background-size: 16px; }');

			$js = 'Wygwam.themeUrl = "'.static::themeUrl().'";'
				. 'Wygwam.ee2plus = '.(version_compare(APP_VER, '2.2', '>=') ? 'true' : 'false').';';

			$filedirs = static::getUploadDestinations(1);

			if ($filedirs)
			{
				$filedir_urls = array();
				foreach ($filedirs as $filedir)
				{
					$filedir_urls[$filedir['id']] = $filedir['url'];
				}

				$js .= 'Wygwam.filedirUrls = '.json_encode($filedir_urls).';';
			}

			static::insertJs($js);

			static::$_includedFieldResources = TRUE;
		}
	}

	/**
	 * Inserts the Wygwam config JS in the page foot by config ID.
	 *
	 * @param $configId
	 *
	 * @return $configHandle The handle for config used by Wygwam JS
	 */
	public static function insertConfigJsById($configId)
	{
		$globalSettings = static::getGlobalSettings();

		// starting point
		$baseConfig = static::baseConfig();

		// -------------------------------------------
		//  Editor Config
		// -------------------------------------------


		if (ee()->db->table_exists('wygwam_configs')
			&& is_numeric($configId)
			&& $config = ee('Model')->get('wygwam:Config')->filter('config_id', '==', $configId)->first()
		)
		{
			/**
			 * @var $config \PT\Wygwam\Model\Config
			 */
			// merge custom settings into config
			$customSettings = $config->settings;
			$configHandle = preg_replace('/[^a-z0-9]/i', '_', $config->config_name).$configId;
			$config = array_merge($baseConfig, $customSettings);

		}
		else
		{
			$customSettings = array();
			$config = $baseConfig;
			$configHandle = 'default0';
		}

		// skip if already included
		if (isset(static::$_includedConfigs) && in_array($configHandle, static::$_includedConfigs))
		{
			return $configHandle;
		}

		// language
		if (! isset($config['language']) || ! $config['language'])
		{
			$langMap = static::languageMap();
			$language = ee()->session->userdata('language');
			$config['language'] = isset($langMap[$language]) ? $langMap[$language] : 'en';
		}

		// toolbar
		if (is_array($config['toolbar']))
		{
			$config['toolbar'] = static::createToolbar($config['toolbar']);
		}

		// css
		if (! $config['contentsCss'])
		{
			unset($config['contentsCss']);
		}

		// set the autoGrow_minHeight to the height
		$config['autoGrow_minHeight'] = $config['height'];

		// allowedContent
		if ($config['restrict_html'] == 'n')
		{
			$config['allowedContent'] = true;
		}

		unset($config['restrict_html']);

		// extraPlugins
		if (!empty($config['extraPlugins']))
		{
			$extraPlugins = array_map('trim', explode(',', $config['extraPlugins']));
		}
		else
		{
			$extraPlugins = array();
		}

		$extraPlugins[] = 'wygwam';
		$extraPlugins[] = 'readmore';

		if ($config['parse_css'])
		{
			if (!in_array('stylesheetparser', $extraPlugins))
			{
				$extraPlugins[] = 'stylesheetparser';
			}

			unset($config['parse_css']);
		}

		$config['extraPlugins'] = implode(',', $extraPlugins);

		// -------------------------------------------
		//  File Browser Config
		// -------------------------------------------

		$userGroup = ee()->session->userdata('group_id');
		$uploadDir = isset($config['upload_dir']) ? $config['upload_dir'] : NULL;
		$uploadDestination = static::getUploadDestinations($userGroup, $uploadDir);

		$fileBrowser = isset($globalSettings['file_browser']) ? $globalSettings['file_browser'] : 'ee';

		switch ($fileBrowser)
		{
			case 'assets':

				// make sure Assets is actually installed
				// (otherwise, just use the EE File Manager)
				if (static::isAssetsInstalled())
				{
					// include sheet resources
					\Assets_helper::include_sheet_resources();

					// if no upload directory was set, just default to "all"
					if (! $uploadDir)
					{
						$uploadDir = '"all"';
					}

					// If this has a source type passed in as well, wrap it in quotes.
					if (strpos($uploadDir, ":"))
					{
						$uploadDir = '"'.$uploadDir.'"';
					}

					$config['filebrowserBrowseFunc']      = 'function(params) { Wygwam.loadAssetsSheet(params, '.$uploadDir.', "any"); }';
					$config['filebrowserImageBrowseFunc'] = 'function(params) { Wygwam.loadAssetsSheet(params, '.$uploadDir.', "image"); }';
					$config['filebrowserFlashBrowseFunc'] = 'function(params) { Wygwam.loadAssetsSheet(params, '.$uploadDir.', "flash"); }';

					break;
				}

			default:

				if (! $uploadDestination)
				{
					break;
				}

				// load the file browser
				// pass in the uploadDir to limit the directory to the one choosen
				static::insertJs(NL."\t"."Wygwam.fpUrl = '" . ee('CP/FilePicker')->make($uploadDir)->getUrl() ."';".NL);

				// if no upload directory was set, just default to "all"
				if (! $uploadDir) $uploadDir = '"all"';

				$config['filebrowserBrowseFunc']      = 'function(params) { Wygwam.loadEEFileBrowser(params, '.$uploadDir.', "any"); }';
				$config['filebrowserImageBrowseFunc'] = 'function(params) { Wygwam.loadEEFileBrowser(params, '.$uploadDir.', "image"); }';
		}

		// add any site page data to wygwam config
		if ($pages = static::getAllPageData())
		{
			ee()->lang->loadfile('wygwam');
			$sitePageString = lang('wygwam_site_page');

			foreach ($pages as $page)
			{
				$config['link_types'][$sitePageString][] = array(
					'label' => $page[2],
					'url'   => $page[4]
				);
			}
		}

		// -------------------------------------------
		//  'wygwam_config' hook
		//   - Override any of the config settings
		//
		if (ee()->extensions->active_hook('wygwam_config'))
		{
			$config = ee()->extensions->call('wygwam_config', $config, $customSettings);
		}
		//
		// -------------------------------------------

		unset($config['upload_dir']);

		// -------------------------------------------
		//  JSONify Config and Return
		// -------------------------------------------

		$configLiterals = static::configLiterals();
		$configBooleans = static::configBooleans();

		$js = '';

		foreach ($config as $setting => $value)
		{
			if (! in_array($setting, $configLiterals))
			{
				if (in_array($setting, $configBooleans))
				{
					$value = ($value == 'y' ? TRUE : FALSE);
				}

				$value = json_encode($value);

				// Firefox gets an "Unterminated string literal" error if this line gets too long,
				// so let's put each new value on its own line
				if ($setting == 'link_types')
				{
					$value = str_replace('","', "\",\n\t\t\t\"", $value);
				}
			}

			$js .= ($js ? ','.NL : '')
				. "\t\t".'"'.$setting.'": '.$value;
		}

		// Strip out any non-space whitespace chars
		$js = str_replace(array(chr(10), chr(11), chr(12), chr(13)), ' ', $js);

		static::insertJs(NL."\t".'Wygwam.configs["'.$configHandle.'"] = {'.NL.$js.NL."\t".'};'.NL);
		static::$_includedConfigs[] = $configHandle;

		return $configHandle;
	}

	// --------------------------------------------------------------------

	/**
	 * Gets the system upload preferences
	 *
	 * @param  int $groupId Member group ID specified when returning allowed upload directories only for that member group
	 * @param  int $id      Specific ID of upload destination to return
	 *
	 * @return array $destinations Result array of DB object, merged with custom file upload settings (if on EE 2.4+)
	 */
	public static function getUploadDestinations ($groupId = NULL, $id = NULL)
	{
		ee()->load->model('file_upload_preferences_model');
		return ee()->file_upload_preferences_model->get_file_upload_preferences($groupId, $id);
	}

	// --------------------------------------------------------------------

	/**
	 * Gets all the possible {filedir_X} tags and their replacement URLs.
	 *
	 * @param bool $sort
	 *
	 * @return array $list
	 */
	private static function _getFileTags($sort = FALSE)
	{
		if (! isset(static::$_fileTags))
		{
			$tags = array();
			$urls = array();

			ee()->load->model('file_upload_preferences_model');

			if ($filePaths = ee()->file_upload_preferences_model->get_paths())
			{
				if ($sort)
				{
					uasort($filePaths, function ($a, $b) { return -(strcmp(strlen($a), strlen($b))); });
				}

				foreach ($filePaths as $id => $url)
				{
					// ignore "/" URLs
					if ($url == '/') continue;

					$tags[] = LD.'filedir_'.$id.RD;
					$urls[] = $url;
				}
			}

			static::$_fileTags = array($tags, $urls);
		}

		return static::$_fileTags;
	}

	/**
	 * Replaces {filedir_X} tags with their URLs.
	 *
	 * @param string &$data
	 */
	public static function replaceFileTags(&$data)
	{
		$tags = static::_getFileTags();
		$data = str_replace($tags[0], $tags[1], $data);
	}

	/**
	 * Replaces File URLs with {filedir_X} tags.
	 *
	 * @param string &$data
	 */
	public static function replaceFileUrls(&$data)
	{
		$tags = static::_getFileTags();
		$data = str_replace($tags[1], $tags[0], $data);
	}

	/**
	 * Replaces Asset URLs with {assets_X} tags.
	 *
	 * @param $data
	 * @param $assetIds
	 * @param $assetUrls
	 */
	public static function replaceAssetUrls(&$data, $assetIds, $assetUrls)
	{
		foreach ($assetUrls as $key => $assetUrl)
		{
			$replace = '{assets_'.$assetIds[$key].':'.$assetUrl.'}';
			$search = str_replace('/', '\/', preg_quote(rtrim($assetUrl, '/')));
			$search = '/(?!\")('.$search.')\/?(?=\")/uU';

			$data = preg_replace($search, $replace, $data);
		}
	}

	/**
	 * Replaces Asset URLs with {assets_X} tags.
	 *
	 * @param $data
	 *
	 * @return array $list
	 */
	public static function replaceAssetTags(&$data)
	{
		preg_match_all("/\\{assets_(\\d*):((.*)(\\}))/uU", $data, $matches);

		if ($matches && !empty($matches[0]))
		{
			$assetIds = $matches[1];
			$assetUrls = $matches[3];

			if (static::isAssetsInstalled())
			{
				$EE = ee();
				$EE->load->add_package_path(PATH_THIRD.'assets/');
				$EE->load->library('assets_lib');
				$files = $EE->assets_lib->get_file_by_id($assetIds);
			}

			for ($counter = 0; $counter < count($matches[1]); $counter++)
			{
				$fileId = $matches[1][$counter];

				// The file has been deleted or Assets is not installed.
				if ((isset($files[$fileId]) && $files[$fileId] === false) || !isset($files[$fileId]))
				{
					$replace = $matches[3][$counter];
				}
				else
				{
					$replace = $files[$fileId]->url();
				}

				$data = str_replace('{assets_'.$fileId.':'.$matches[3][$counter].'}', $replace, $data);
			}

			return array('ids' => $assetIds, 'urls' => $assetUrls);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Gets all the possible {page_X} tags and their replacement URLs
	 *
	 * @param bool $sort
	 *
	 * @return array $list
	 */
	private static function _getPageTags($sort = FALSE)
	{
		if (! isset(static::$_pageTags))
		{
			$tags = array();
			$urls = array();

			$pageData = static::getAllPageData(FALSE);

			if ($sort)
			{
				usort($pageData, function ($a, $b) { return -(strcmp(strlen($a[4]), strlen($b[4]))); });
			}

			foreach ($pageData as $page)
			{
				$tags[] = LD.'page_'.$page[0].RD;
				$urls[] = $page[4];
			}

			static::$_pageTags = array($tags, $urls);
		}

		return static::$_pageTags;
	}

	/**
	 * Replaces {page_X} tags with the page URLs.
	 *
	 * @param string &$data
	 */
	public static function replacePageTags(&$data)
	{
		if (strpos($data, LD.'page_') !== FALSE)
		{
			$tags = static::_getPageTags();
			foreach ($tags[0] as $key => $pageTag)
			{
				$pattern = '/(?!&quot;|\")('.preg_quote($pageTag).')(&quot;|\"|\/)?/u';
				preg_match_all($pattern, $data, $matches);

				if ($matches && count($matches[0]) > 0)
				{
					// $matches[2] should either be &quot;, ", / or empty
					foreach ($matches[2] as $innerKey => $match)
					{
						$search = '/('.preg_quote($matches[1][$innerKey]).')/uU';
						$replace = $tags[1][$key];

						// If there is not a trailing quote or slash, we're going to add one.
						if (empty($match))
						{
							$replace .= '/';
						}

						$data = preg_replace($search, $replace, $data);
					}
				}
			}
		}
	}

	/**
	 * Replace page URLs with {page_X} tags.
	 *
	 * @param string &$data
	 */
	public static function replacePageUrls(&$data)
	{
		$tags = static::_getPageTags(TRUE);

		foreach ($tags[1] as $key => $pageUrl)
		{
			$pageUrl = str_replace('/', '\/', preg_quote(rtrim($pageUrl, '/')));
			$search = '/(?!\")('.$pageUrl.')\/?(?=\")/uU';
			$data = preg_replace($search, $tags[0][$key], $data);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Returns whether the Pages module is installed.
	 *
	 * @return bool $isInstalled
	 */
	private static function _isPagesModuleInstalled()
	{
		return array_key_exists('pages', ee('Addon')->installed());
	}

	// --------------------------------------------------------------------

	/**
	 * Gets the current site's pages.
	 *
	 * @return array $sitePages
	 */
	private static function _getSitePages()
	{
		$siteId = ee()->config->item('site_id');

		// Is this entry from a different site?
		$differentSite = (static::$entrySiteId && $siteId != static::$entrySiteId);
		$entrySiteId = $differentSite ? static::$entrySiteId : $siteId;

		if (! isset(static::$_sitePages[$entrySiteId]))
		{
			// Temporarily swap the site config over to the entry's site
			if ($differentSite)
			{
				ee()->config->site_prefs('', $entrySiteId);
			}

			$pages = ee()->config->item('site_pages');

			if (is_array($pages) && !empty($pages[$entrySiteId]['uris']))
			{
				// grab a copy of this site's pages
				$site_pages = array_merge($pages[$entrySiteId]);

				// sort by uris
				natcasesort($site_pages['uris']);

				static::$_sitePages[$entrySiteId] = $site_pages;
			}
			else
			{
				static::$_sitePages[$entrySiteId] = array();
			}

			// Return the config to the actual site
			if ($differentSite)
			{
				ee()->config->site_prefs('', $siteId);
			}
		}

		return static::$_sitePages[$entrySiteId];
	}

	/**
	 * Gets the Pages module data.
	 *
	 * @return array $pagesModule
	 */
	private static function _getPagesModuleData()
	{
		if (! isset(static::$_pageData))
		{
			static::$_pageData = array();

			if (($pages = static::_getSitePages()) && ($pageIds = array_filter(array_keys($pages['uris']))))
			{
				/**
				 * @var \EllisLab\ExpressionEngine\Model\Channel\ChannelEntry|null $entries
				 */
				$entries = ee('Model')->get('ChannelEntry')
						->filter('entry_id', 'IN', $pageIds)
						->order('entry_id', 'ASC')->all();

				// index entries by entry_id
				$entryData = array();

				foreach ($entries as $entry)
				{
					$entryData[$entry->entry_id] = $entry;
				}

				foreach ($pages['uris'] as $entryId => $uri)
				{
					if (! isset($entryData[$entryId]))
					{
						continue;
					}

					$entry = $entryData[$entryId];

					$url = ee()->functions->create_page_url($pages['url'], $uri);

					if (!$url || $url == '/')
					{
						continue;
					}

					static::$_pageData[] = array(
						$entryId,
						$entry->channel_id,
						$entry->title,
						'0',
						$url
					);
				}
			}

			// sort by entry title
			if(count(static::$_pageData) > 0)
			{
				static::$_pageData = static::_subvalSort(static::$_pageData, 2);
			}
		}

		return static::$_pageData;
	}

	/**
	 * Sorts a multidimensional array on an internal array's key.
	 *
	 * @param array $initialArray
	 * @param string $subKey
	 *
	 * @return array $sortedArray
	 */
	private static function _subvalSort($initialArray, $subKey)
	{
		$sortedArray = array();

		foreach ($initialArray as $key => $value)
		{
			$tempArray[$key] = strtolower($value[$subKey]);
		}

		asort($tempArray);

		foreach ($tempArray as $key => $value)
		{
			$sortedArray[] = $initialArray[$key];
		}

		return $sortedArray;
	}

	// --------------------------------------------------------------------

	/**
	 * Gets all site page data from the pages module.
	 *
	 * @param bool $installCheck
	 *
	 * @return array $pageData
	 */
	public static function getAllPageData ($installCheck = TRUE)
	{
		$pageData = array();

		if ($installCheck)
		{
			if (static::_isPagesModuleInstalled())
			{
				$pageData = static::_getPagesModuleData();
			}
		}
		else
		{
			$pageData = static::_getPagesModuleData();
		}

		return $pageData;
	}

	/**
	 * Returns whether Assets is installed or not.
	 *
	 * @return bool $isInstalled
	 */
	public static function isAssetsInstalled()
	{
		return array_key_exists('assets', ee('Addon')->installed());
	}
}
