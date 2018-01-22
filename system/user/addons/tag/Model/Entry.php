<?php

namespace Solspace\Addons\Tag\Model;

class Entry extends BaseModel
{

	protected static $_primary_key	= 'id';
	protected static $_table_name	= 'tag_entries';

	protected $id;
	protected $entry_id;
	protected $tag_id;
	protected $channel_id;
	protected $site_id;
	protected $author_id;
	protected $ip_address;
	protected $type;
	protected $tag_group_id;
	protected $remote;
}
//END Preference
