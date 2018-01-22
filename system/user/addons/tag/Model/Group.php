<?php

namespace Solspace\Addons\Tag\Model;

class Group extends BaseModel
{

	protected static $_primary_key	= 'tag_group_id';
	protected static $_table_name	= 'tag_groups';

	protected $tag_group_id;
	protected $tag_group_name;
	protected $tag_group_short_name;
}
//END Preference
