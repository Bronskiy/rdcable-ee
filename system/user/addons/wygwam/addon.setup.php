<?php
return array(
	'author'         => 'https://eeharbor.com/wygwam',
	'author_url'     => 'https://eeeharbor.com/',
	'name'           => 'Wygwam',
	'description'    => 'Wysiwyg editor powered by CKEditor',
	'version'        => '4.0.6',
	'namespace'      => 'PT\Wygwam',
	'settings_exist' => true,
	'docs_url'       => 'https://eeharbor.com/wygwam/documentation',
	'services'       => array (),
	'models'         => array (
		'Config' => 'Model\Config'
	),
	'fieldtypes'     => array(
		'wygwam' => array(
			'compatibility' => 'text'
		)
	)
);
