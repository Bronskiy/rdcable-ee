<?php

// Changing this to true wont give you Freeform Pro for free, as the required files would still be missing.
// This is just a helper. You can purchase Freeform Pro at https://solspace.com/expressionengine/freeform


$freeform_pro	= false;




$info	= array(
	'author'			=> 'Solspace',
	'author_url'		=> 'https://solspace.com/expressionengine',
	'docs_url'			=> 'https://solspace.com/expressionengine/freeform/docs',
	'name'				=> 'Freeform' . (($freeform_pro) ? ' Pro' : ''),
	'description'		=> 'Advanced form creation and data collecting.',
	'version'			=> '5.1.2',
	'namespace'			=> 'Solspace\Addons\Freeform',
	'settings_exist'	=> true,
	'freeform_pro'		=> $freeform_pro,
	'models'			=> array(),
	//non-EE info
	'doc_links'		=> array(
		'custom_fields'		=> 'https://solspace.com/expressionengine/freeform/docs/fieldtype-development'
	),
);

return $info;
