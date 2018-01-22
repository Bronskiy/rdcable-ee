<?php

namespace Solspace\Addons\Tag\Model;

// Yes this classname is stupid but the mod.tag.php class is already
// named tag and I dont want to break convention on singular filenames
// for models.
class TagTag extends BaseModel
{

	protected static $_primary_key	= 'tag_id';
	protected static $_table_name	= 'tag_tags';

	protected $tag_id;
	protected $tag_alpha;
	protected $tag_name;
	protected $site_id;
	protected $author_id;
	protected $entry_date;
	protected $edit_date;
	protected $clicks;
	protected $total_entries;
	protected $channel_entries;

	protected $_field_list_cache;

	// --------------------------------------------------------------------

	/**
	 * We have a variable number of columns so we have to
	 * get a list of fields every first time this is called.
	 *
	 * @access	public
	 * @return	array	array of columns for this table
	 */

	public function getFields()
	{
		if ( ! isset($this->_field_list_cache))
		{
			$all = ee('Database')
				->newQuery()
				->list_fields($this->getTableName());

			$known = static::getClassFields();

			$this->_field_list_cache = array_unique(array_merge($known, $all));
		}

		return $this->_field_list_cache;
	}
	//END getFieldList
}
//END Preference
