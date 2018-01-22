<?php

namespace Solspace\Addons\Tag\Model;

class BadTag extends BaseModel
{

	protected static $_primary_key	= 'tag_id';
	protected static $_table_name	= 'tag_bad_tags';

	protected $tag_id;
	protected $tag_name;
	protected $site_id;
	protected $author_id;
	protected $edit_date;
}
//END Preference
