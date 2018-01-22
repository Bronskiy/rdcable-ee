<?php

return array(
	'author'			=> 'Solspace',
	'author_url'		=> 'https://solspace.com/expressionengine',
	'docs_url'			=> 'https://solspace.com/expressionengine/tag/docs',
	'name'				=> 'Tag',
	'description'		=> 'Tag your content with keywords and intuitively display relationships between entries.',
	'version'			=> '5.0.6',
	'namespace'			=> 'Solspace\Addons\Tag',
	'settings_exist'	=> true,
	'models' => array(
		'BadTag'		=> 'Model\BadTag',
		'Entry'			=> 'Model\Entry',
		'Group'			=> 'Model\Group',
		'Preference'	=> 'Model\Preference',
		'TagTag'		=> 'Model\TagTag',
	)
);
