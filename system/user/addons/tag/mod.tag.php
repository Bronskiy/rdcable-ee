<?php

use Solspace\Addons\Tag\Library\AddonBuilder;

class Tag extends AddonBuilder
{
	public $TYPE;

	public $remote					= false;
	public $batch					= false;

	public $author_id				= '';
	public $tag_id					= '';
	public $tag						= '';
	public $channel_id				= '';
	public $site_id					= '';
	public $entry_id				= '';
	public $old_entry_id			= '';
	public $tag_relevance			= array();
	public $max_relevance			= 0;
	public $str						= '';
	public $tagdata					= '';
	public $site_url				= '';
	public $cp_url					= '';
	public $type					= 'channel';
	//field type business
	public $from_ft					= false;
	public $tag_group_id			= 1;

	public $separator_override		= NULL;
	public $field_id				= 'default';

	public $existing				= array();
	public $new						= array();
	public $bad						= false;

	// Pagination variables
	public $paginate				= false;
	public $pagination_links		= '';
	public $page_next				= '';
	public $page_previous			= '';
	public $current_page			= 1;
	public $total_pages				= 1;
	public $total_rows				=  0;
	public $p_limit					= '';
	public $p_page					= '';
	public $basepath				= '';
	public $uristr					= '';


	/**
	 * contructor
	 *
	 * @access	public
	 * @param	int/string 	channel_id
	 * @param	int/string 	entry_id
	 * @param	string 	 	string of tags
	 * @return  object 	 	instance of itself of course
	 */

	public function __construct( $channel_id = '', $entry_id = '', $str = '' )
	{
		parent::__construct('module');

		$this->type					= 'channel';
		$this->channel_id			= $channel_id;
		$this->entry_id				= $entry_id;
		$this->site_id				= ee()->config->item('site_id');
		$this->str					= $str;

		if (ee()->config->item("use_category_name") == 'y' AND
			ee()->config->item("reserved_category_word") != '')
		{
			$this->use_category_names	= ee()->config->item("use_category_name");
			$this->reserved_cat_segment	= ee()->config->item("reserved_category_word");
		}

		//--------------------------------------------
		//	websafe seperator if any
		//--------------------------------------------

		$this->websafe_separator	= '+';

		if ( isset(ee()->TMPL)		AND
			 is_object(ee()->TMPL)	AND
			 ! in_array(ee()->TMPL->fetch_param('websafe_separator'), array(false, ''), true) )
		{
			$this->websafe_separator	= ee()->TMPL->fetch_param('websafe_separator');
		}

	}
	//	END constructor


	// --------------------------------------------------------------------

	/**
	 * Theme Folder URL
	 *
	 * Mainly used for codepack
	 *
	 * @access	public
	 * @return	string	theme folder url with ending slash
	 */

	public function theme_folder_url()
	{
		return $this->theme_url;
	}
	//END theme_folder_url



	// --------------------------------------------------------------------

	/**
	 * Tag Name
	 *
	 * Gets tag name from a number of possible sources and outputs
	 *
	 * @access	public
	 * @return	string		parsed tagdata
	 */

	public function tag_name()
	{
		if ( ee()->TMPL->tagdata == '' )
		{
			//	----------------------------------------
			//	Tag provided?
			//	----------------------------------------

			if ( ee()->TMPL->fetch_param('tag') !== false )
			{
				$this->tag	= ee()->TMPL->fetch_param('tag');
			}
			if ( ee()->TMPL->fetch_param('tag_id') !== false )
			{
				$this->tag_id = ee()->TMPL->fetch_param('tag_id');
			}
		}
		else
		{
			$this->tag	= ee()->TMPL->tagdata;
		}

		// --------------------------------------------
		//  Pull Tag from DB if Tag ID
		// --------------------------------------------

		if ($this->tag_id != '')
		{
			$query = ee()->db->query(
				"SELECT t.tag_name
				 FROM 	exp_tag_tags t
				 WHERE 	t.site_id
				 IN 	('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
				 AND 	t.tag_id = '".ee()->db->escape_str($this->tag_id)."'"
			);

			if ($query->num_rows() > 0)
			{
				$this->tag = $query->row('tag_name');
			}
		}

		//--------------------------------------------
		//	tag seperator
		//--------------------------------------------

		if ( ee()->TMPL->fetch_param('tag_separator') !== false AND
			 ee()->TMPL->fetch_param('tag_separator') != '' )
		{
			$this->tag = str_replace( ee()->TMPL->fetch_param('tag_separator'), ',', $this->tag);
		}

		//--------------------------------------------
		//	websafe separator
		//--------------------------------------------

		$websafe_separator	= ee()->TMPL->fetch_param('websafe_separator', '+');

		$this->tag = $this->lib('Utils')->clean_str(str_replace( $websafe_separator, ' ', $this->tag));

		if ( $this->tag == '' )
		{
			return '';
		}

		$tags		= explode( ",", stripslashes($this->tag));

		foreach ( $tags as $key => $tag )
		{
			switch(ee()->TMPL->fetch_param('case'))
			{
				case 'upper' :
					$tags[$key] = $this->lib('Utils')->strtoupper($tag);
				break;
				case 'lower' :
					$tags[$key] = $this->lib('Utils')->strtolower($tag);
				break;
				case 'sentence' :
					$tags[$key] = ucfirst($tag);
				break;
				case 'none' : break;
				default :
					$tags[$key] = ucwords($tag);
				break;
			}

		}

		if ( count( $tags ) > 1 )
		{
			return implode( ", ", $tags );
		}
		else
		{
			return $tags[0];
		}
	}
	//	END tag name


	// --------------------------------------------------------------------

	/**
	 * Tags
	 *
	 * @access	public
	 * @param	bool	$preview		preview mode?
	 * @param	string	$preview_tags	optional input strings for previews
	 * @return	string					parsed tagdata
	 */

	public function tags($preview = false, $preview_tags = '')
	{
		//	----------------------------------------
		//	Tag type
		//	----------------------------------------

		$type = 'channel';

		if ( ee()->TMPL->fetch_param('type') !== false AND
			ee()->TMPL->fetch_param('type') != '' )
		{
			$type = ee()->TMPL->fetch_param('type');
		}

		//	----------------------------------------
		//	Websafe separator
		//	----------------------------------------

		$websafe_separator	= '+';

		if ( ee()->TMPL->fetch_param('websafe_separator') !== false AND
			 ee()->TMPL->fetch_param('websafe_separator') != '' )
		{
			$websafe_separator	= ee()->TMPL->fetch_param('websafe_separator');
		}

		//	----------------------------------------
		//	Entry id
		//	----------------------------------------

		if ( ee()->TMPL->fetch_param('entry_id') )
		{
			$this->entry_id = ee()->TMPL->fetch_param('entry_id');
		}
		elseif ( $this->entry_id( $type ) === false )
		{
			return $this->no_results('tag');
		}

		// -------------------------------------
		//	tag groups?
		// -------------------------------------

		//pre-escaped
		$tag_group_sql_insert = $this->model('Data')->tag_total_entries_sql_insert('t');

		//	----------------------------------------
		//	Start SQL
		//	----------------------------------------

		// Allow searching on multiple entries
		$entry_ids = explode('|', $this->entry_id);

		$sql = "/* Solspace Tag - tags() */ SELECT SQL_CALC_FOUND_ROWS t.tag_name,
							t.tag_id,
							t.tag_name AS tag,
							t.channel_entries,
							t.total_entries,
							{$tag_group_sql_insert}
							t.clicks
				FROM 		exp_tag_tags t
				LEFT JOIN 	exp_tag_entries e
				ON 			t.tag_id = e.tag_id
				WHERE 		t.site_id
				IN 			('" .
					implode(
						"','",
						ee()->db->escape_str(ee()->TMPL->site_ids)
					) . "')
				AND 			e.entry_id
				IN 			('".implode("','", $entry_ids)."')";

		//	----------------------------------------
		//	Exclude?
		//	----------------------------------------

		if ( ee()->TMPL->fetch_param('exclude') !== false AND
			 ee()->TMPL->fetch_param('exclude') != '' )
		{
			$ids = $this->exclude(ee()->TMPL->fetch_param('exclude'));

			if (is_array($ids))
			{
				$sql	.= " AND t.tag_id NOT IN ('" .
					implode( "','", ee()->db->escape_str($ids) )."')";
			}
		}

		// --------------------------------------------
		//  Bad Tags
		// --------------------------------------------

		if (count($this->bad()) > 0)
		{
			$sql .= " AND t.tag_name NOT IN ('" .
				implode( "','", ee()->db->escape_str($this->bad()) )."')";
		}

		//	----------------------------------------
		//	Tag type
		//	----------------------------------------

		if ( $type != 'channel' )
		{
			$sql	.= " ".ee()->functions->sql_andor_string( $type, 'e.type' );
		}
		else
		{
			$sql	.= " AND e.type = 'channel'";
		}

		//--------------------------------------------
		//	tag group
		//--------------------------------------------

		if (ee()->TMPL->fetch_param('tag_group_id'))
		{
			$group_ids = preg_split(
				'/\|/',
				ee()->TMPL->fetch_param('tag_group_id'),
				-1,
				PREG_SPLIT_NO_EMPTY
			);
		}
		else if (ee()->TMPL->fetch_param('tag_group_name'))
		{
			$group_ids 		= array();

			$group_names 	= preg_split(
				'/\|/',
				ee()->TMPL->fetch_param('tag_group_name'),
				-1,
				PREG_SPLIT_NO_EMPTY
			);

			foreach ($group_names as $group_name)
			{
				$group_id = $this->model('Data')->get_tag_group_id_by_name($group_name);

				if (is_numeric($group_id))
				{
					$group_ids[] = $group_id;
				}
			}

			//if they pass bad names, return no results because
			//we want it to do the same thing that it will on bad tag_group_ids
			if (empty($group_ids))
			{
				return $this->no_results();
			}
		}

		if (isset($group_ids) AND $group_ids)
		{
			$sql	.= " AND e.tag_group_id IN (" .
				implode( ",", ee()->db->escape_str($group_ids) ).")";
		}

		//--------------------------------------------
		//	group by
		//--------------------------------------------

		$sql	.= " GROUP BY t.tag_id ";

		//	----------------------------------------
		//	Order
		//	----------------------------------------

		if ( in_array(
				ee()->TMPL->fetch_param('orderby'),
				array(
					'clicks',
					'edit_date',
					'entry_date',
					'total_entries',
					'channel_entries'
				)
			))
		{
			$sql	.= " ORDER BY t.".ee()->TMPL->fetch_param('orderby');
			$sql	.= ( stristr( 'asc', ee()->TMPL->fetch_param('sort') ) ) ? " ASC": " DESC";
		}
		else
		{
			$sql	.= " ORDER BY t.tag_name";
			$sql	.= ( stristr( 'desc', ee()->TMPL->fetch_param('sort') ) ) ? " DESC": " ASC";
		}


		//	----------------------------------------
		//	Current page/Query offset
		//	----------------------------------------

		if ( preg_match("/P(\d+)/s", ee()->uri->uri_string, $match) &&
			! $this->check_no(ee()->TMPL->fetch_param('dynamic')))
		{
			if ( $this->p_page == 0 AND is_numeric($match[1]) )
			{
				$this->p_page 	= $match[1];
			}
		}
		else
		{
			$this->p_page = 0;
		}

		//	----------------------------------------
		//	Limit
		//	----------------------------------------

		if ( ctype_digit( ee()->TMPL->fetch_param('limit') ) === true )
		{
			$sql	.= " LIMIT " . $this->p_page . ", " . ee()->TMPL->fetch_param('limit');
		}

		//	----------------------------------------
		//	Query
		//	----------------------------------------

		$query	= ee()->db->query( $sql );

		//	----------------------------------------
		//	Empty?
		//	----------------------------------------

		if ( $query->num_rows() == 0 )
		{
			return $this->no_results('tag');
		}

		//	----------------------------------------
		//	Get total without limit, for pagination
		//	----------------------------------------
		$total_query = ee()->db->query("/* Solspace Tag - tags() */ SELECT FOUND_ROWS() as total_rows");

		//	----------------------------------------
		//	Pagination Prep
		//	----------------------------------------

		$prefix = stristr(ee()->TMPL->tagdata, LD . 'tag_paginate' . RD);

		//get pagination info
		$pagination_data = $this->universal_pagination(array(
			'total_results'			=> $total_query->row('total_rows'),
			'tagdata'				=> ee()->TMPL->tagdata,
			'limit'					=> ee()->TMPL->fetch_param('limit'),
			'uri_string'			=> ee()->uri->uri_string,
			'current_page'			=> $this->p_page,
			'prefix'				=> 'tag',
			'auto_paginate'			=> true
		));

		if ($pagination_data['paginate'] === true)
		{
			ee()->TMPL->tagdata		= $pagination_data['tagdata'];
		}

		//	----------------------------------------
		//	Parse
		//	----------------------------------------

		$qs	= (ee()->config->item('force_query_string') == 'y') ? '' : '?';

		$r	= '';

		$total_results	 = count($query->result_array());

		$result_array = $query->result_array();

		$remove = array();

		// -------------------------------------
		//	Are we in preview mode?
		//	We need to mock some results for
		//	non-existing tags
		// -------------------------------------

		if ($preview)
		{
			$possible_previews = preg_split(
				"/\r\n|\n/ms",
				trim($preview_tags),
				-1,
				PREG_SPLIT_NO_EMPTY
			);

			$found_tags = array();

			foreach ($result_array as $row)
			{
				$found_tags[] = $row['tag'];
			}

			$add	= array_diff($possible_previews, $found_tags);
			$remove	= array_diff($found_tags, $possible_previews);

			// -------------------------------------
			//	prep row template
			// -------------------------------------

			$row_template = $result_array[0];

			//we have to have blanks for everything
			//that doesn't exist so any conditionals
			//don't fire false positives just yet
			foreach($row_template as $key => $value)
			{
				if (ctype_digit($value))
				{
					$row_template[$key] = 0;
				}
				else
				{
					$row_template[$key] = '';
				}
			}

			$row_template['site_id']	= ee()->config->item('site_id');
			$row_template['author_id']	= ee()->session->userdata('member_id');

			if ( ! empty($add))
			{
				foreach ($add as $add_tag)
				{
					$add_template = $row_template;
					$add_template['tag'] = $add_template['tag_name'] = $add_tag;
					$add_template['tag_alpha'] = substr($add_tag, 0, 1);

					$result_array[] = $add_template;
				}
			}
		}

		$count = 0;

		foreach ($result_array as $row )
		{
			if (in_array($row['tag'], $remove))
			{
				continue;
			}

			$tagdata	= ee()->TMPL->tagdata;

			$row['entry_id']			= $this->entry_id;
			$row['count']				= ++$count;
			$row['tag_count']			= $row['count'];
			$row['total_results']		= $total_results;
			$row['tag_total_results']	= $row['total_results'];
			$row['weblog_entries']		= $row['channel_entries'];

			//	----------------------------------------
			//	Add content
			//	----------------------------------------

			$row['websafe_tag']	= str_replace( " ", $websafe_separator, $row['tag'] );

			//	----------------------------------------
			//	Case
			//	----------------------------------------
			switch(ee()->TMPL->fetch_param('case'))
			{
				case 'upper' :
					$row['tag'] = $this->lib('Utils')->strtoupper($row['tag']);
				break;
				case 'lower' :
					$row['tag'] = $this->lib('Utils')->strtolower($row['tag']);
				break;
				case 'sentence' :
					$row['tag'] = ucfirst($row['tag']);
				break;
				case 'title' :
					$row['tag'] = ucwords($row['tag']);
				break;
				case 'none' :
					$row['tag'] = $row['tag'];
				break;
				default :
					$row['tag'] = $row['tag'];
				break;
			}

			//	----------------------------------------
			//	Parse conditionals
			//	----------------------------------------

			$cond		= $row;
			$tagdata	= ee()->functions->prep_conditionals( $tagdata, $cond );

			//	----------------------------------------
			//	Parse singles
			//	----------------------------------------

			foreach ( $row as $key => $val )
			{
				$tagdata	= ee()->TMPL->swap_var_single( $key, $val, $tagdata );
			}

			$r	.= $tagdata;
		}

		$backspace	= ( ctype_digit( ee()->TMPL->fetch_param('backspace') ) === true ) ? ee()->TMPL->fetch_param('backspace'): 0;

		$r			= ( $backspace > 0 ) ? substr( $r, 0, - $backspace ): $r;

		// --------------------------------------------
		//  Pagination?
		// --------------------------------------------

		//legacy support for non prefix
		if ($prefix)
		{
			$r = $this->parse_pagination(array(
				'prefix' 	=> 'tag',
				'tagdata' 	=> $r
			));
		}
		else
		{
			$r = $this->parse_pagination(array(
				'tagdata' 	=> $r
			));
		}

		return $r;
	}
	//	END tags


	// --------------------------------------------------------------------

	/**
	 * Entries
	 *
	 * Finds and displays channel entries with the given tags
	 *
	 * @access	public
	 * @return	string		parsed tagdata
	 */
	public function entries()
	{
		$dynamic	= $this->check_no(ee()->TMPL->fetch_param('dynamic', 'yes')) ? 'off': 'on';

		$qstring = (ee()->uri->page_query_string != '') ? ee()->uri->page_query_string : ee()->uri->query_string;
		$cat_id  = '';

		//	----------------------------------------
		//	Tag provided?
		// ----------------------------------------

		if ( ee()->TMPL->fetch_param('tag') !== false )
		{
			$this->tag = ee()->TMPL->fetch_param('tag');
		}
		elseif ( ee()->TMPL->fetch_param('tag_id') !== false )
		{
			$this->tag_id = ee()->TMPL->fetch_param('tag_id');
		}

		if ( $this->tag == '' &&
			 $this->tag_id == '' &&
			 ee()->TMPL->fetch_param('tag_group_id') === false &&
			 ee()->TMPL->fetch_param('tag_group_name') === false)
		{

			return $this->no_results('tag');
		}

		//	----------------------------------------
		//	Remove reserved characters
		//	----------------------------------------

		//--------------------------------------------
		//	tag seperator
		//--------------------------------------------

		$tag_separator = ee()->TMPL->fetch_param('tag_separator', ',');

		//--------------------------------------------
		//	websafe separator
		//--------------------------------------------

		$websafe_separator = ee()->TMPL->fetch_param('websafe_separator', '+');

		if ($this->tag_id == '')
		{
			$this->tag	= str_replace( $websafe_separator, " ", $this->tag );
			$this->tag	= str_replace( "%20", " ", $this->tag );
			$this->tag	= $this->lib('Utils')->clean_str( $this->tag );
		}

		//	----------------------------------------
		//	Are we ranking?
		//	----------------------------------------

		if ( in_array(
				ee()->TMPL->fetch_param( 'tag_rank' ),
				array( 'clicks', 'total_entries', 'channel_entries' ) )
		)
		{
			$tag_rank	= ee()->TMPL->fetch_param( 'tag_rank' );
		}

		//	----------------------------------------
		//	Inclusive tags?
		//	----------------------------------------


		if ( $this->check_yes(ee()->TMPL->fetch_param('inclusive')) === false)
		{
			$this->tag		= str_replace( $tag_separator, "|", $this->tag );

			$sql		= "SELECT DISTINCT	(e.entry_id)
						   FROM 			exp_tag_entries AS e
						   LEFT JOIN 		exp_tag_tags AS t
						   ON 				e.tag_id = t.tag_id ";

			//	----------------------------------------
			//	Are we checking for category?
			//	----------------------------------------

			if ( ee()->TMPL->fetch_param('category') !== false AND
				 ee()->TMPL->fetch_param('category') != '' )
			{
				//	----------------------------------------
				//	Get the id
				//	----------------------------------------

				if ( ctype_digit( str_replace( array("not ", "|"), "", ee()->TMPL->fetch_param('category') ) ) === true )
				{
					$cat_id	= ee()->TMPL->fetch_param('category');
				}
				elseif ( preg_match( "/C(\d+)/s", ee()->TMPL->fetch_param('category'), $match ) )
				{
					$cat_id	= $match['1'];
				}
				else
				{
					$cat_q	= ee()->db->query(
						"SELECT cat_id
						 FROM 	exp_categories
						 WHERE  site_id
						 IN 	('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
						 AND 	cat_url_title = '".
									ee()->db->escape_str( ee()->TMPL->fetch_param('category') )."'" );

					if ( $cat_q->num_rows() > 0 )
					{
						$cat_id	= '';

						foreach ( $cat_q->result_array() as $row )
						{
							$cat_id	.= $row['cat_id']."|";
						}
					}
				}
			}

			// Numeric version of the category?

			if (preg_match("#(^|\/)C(\d+)#", $qstring, $match) AND $dynamic == 'on')
			{
				$cat_id = $match['2'];
			}

			//	----------------------------------------
			//	Do we have a Category id?
			//	----------------------------------------
			//  We use LEFT JOIN when there is a 'not' so that we get
			//  entries that are not assigned to a category.
			// --------------------------------

			if ($cat_id != '')
			{
				if (substr($cat_id, 0, 3) == 'not' AND $this->check_no(ee()->TMPL->fetch_param('uncategorized_entries')) === false)
				{
					$sql .= "LEFT JOIN exp_category_posts AS cp ON e.entry_id = cp.entry_id ";
				}
				else
				{
					$sql .= "INNER JOIN exp_category_posts AS cp ON e.entry_id = cp.entry_id ";
				}
			}

			//	----------------------------------------
			//	 Search for Tag Names
			//	----------------------------------------

			$sql		.= " WHERE";

			$sql		.= " t.site_id IN ('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')";

			if ($this->tag_id != '')
			{
				$sql .= ee()->functions->sql_andor_string( $this->tag_id, ' t.tag_id');
			}
			elseif($this->tag != '')
			{
				if ($this->model('Data')->preference('convert_case') != 'n')
				{
					$this->tag = strtolower($this->tag);
				}

				if (substr($this->tag, 0, 4) == 'not ' AND
					$this->check_yes(ee()->TMPL->fetch_param('exclusive')))
				{
					$sql .= " AND 		e.entry_id
							  NOT IN 	(
								SELECT DISTINCT entry_id
								FROM 			exp_tag_entries AS e,
												exp_tag_tags AS t
								WHERE 			e.tag_id = t.tag_id ".
								ee()->functions->sql_andor_string(
									substr($this->tag, 4),
									'BINARY t.tag_name'
								) .
							")";
				}

				$sql		.= ee()->functions->sql_andor_string( $this->tag,' BINARY t.tag_name');
			}

			$sql		.= " AND e.type = 'channel'";

			// ----------------------------------------------
			//  Limit query by category
			// ----------------------------------------------

			if ($cat_id != '')
			{
				if (substr($cat_id, 0, 3) == 'not' AND
					$this->check_no(ee()->TMPL->fetch_param('uncategorized_entries')) === false)
				{
					$sql .= ee()->functions->sql_andor_string($cat_id, 'cp.cat_id', '', true)." ";
				}
				else
				{
					$sql .= ee()->functions->sql_andor_string($cat_id, 'cp.cat_id')." ";
				}
			}

			//--------------------------------------------
			//	tag group
			//--------------------------------------------

			if (ee()->TMPL->fetch_param('tag_group_id'))
			{
				$group_ids = preg_split('/\|/', ee()->TMPL->fetch_param('tag_group_id'), -1, PREG_SPLIT_NO_EMPTY);
			}
			else if (ee()->TMPL->fetch_param('tag_group_name'))
			{
				$group_ids 		= array();

				$group_names 	= preg_split('/\|/', ee()->TMPL->fetch_param('tag_group_name'), -1, PREG_SPLIT_NO_EMPTY);

				foreach ($group_names as $group_name)
				{
					$group_id = $this->model('Data')->get_tag_group_id_by_name($group_name);

					if (is_numeric($group_id))
					{
						$group_ids[] = $group_id;
					}
				}

				//if they pass bad names, return no results because
				//we want it to do the same thing that it will on bad tag_group_ids
				if (empty($group_ids))
				{
					return $this->no_results();
				}
			}

			if (isset($group_ids) AND $group_ids)
			{
				$sql	.= " AND e.tag_group_id IN (".implode( ",", ee()->db->escape_str($group_ids) ).")";
			}

			//	----------------------------------------
			//	Are we ranking?
			//	----------------------------------------

			if ( isset( $tag_rank ) )
			{
				$sql	.= " ORDER BY t.".$tag_rank." DESC";
			}

			//	----------------------------------------
			//	Run query
			//	----------------------------------------

			$query	= ee()->db->query( $sql );

			if ( $query->num_rows() == 0 )
			{

				return $this->no_results('tag');
			}

			//	----------------------------------------
			//	Assemble entry ids
			//	----------------------------------------

			$ids	= array();

			foreach ( $query->result_array() as $row )
			{
				$ids[] = $row['entry_id'];
			}

			$this->entry_id	= implode('|', $ids);
		}
		else
		{
			if ($this->tag_id == '')
			{

				$tags	= preg_split( '/[\|\\'.$tag_separator.']/', $this->tag );

				$tags	= array_unique( $tags );
			}

			$sql	= "SELECT DISTINCT	(e.entry_id), t.tag_id
					   FROM 			exp_tag_entries e
					   LEFT JOIN 		exp_tag_tags t
					   ON 				t.tag_id = e.tag_id ";

			//	----------------------------------------
			//	Are we checking for a category?
			//	----------------------------------------

			if ( ee()->TMPL->fetch_param('category') !== false AND
				 ee()->TMPL->fetch_param('category') != '' )
			{
				//	----------------------------------------
				//	Get the id
				//	----------------------------------------

				if ( ctype_digit( str_replace( array("not ", "|"), "", ee()->TMPL->fetch_param('category') ) ) === true )
				{
					$cat_id	= ee()->TMPL->fetch_param('category');
				}
				elseif ( preg_match( "/C(\d+)/s", ee()->TMPL->fetch_param('category'), $match ) )
				{
					$cat_id	= $match['1'];
				}
				else
				{
					$cat_q	= ee()->db->query(
						"SELECT cat_id
						 FROM 	exp_categories
						 WHERE 	site_id
						 IN 	('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
						 AND 	cat_url_title = '" . ee()->db->escape_str( ee()->TMPL->fetch_param('category') )."'"
					);

					if ( $cat_q->num_rows() > 0 )
					{
						$cat_id	= '';

						foreach ( $cat_q->result_array() as $row )
						{
							$cat_id	.= $row['cat_id']."|";
						}
					}
				}
			}

			// Numeric version of the category?

			if (preg_match("#(^|\/)C(\d+)#", $qstring, $match) AND $dynamic == 'on')
			{
				$cat_id = $match['2'];
			}

			//	----------------------------------------
			//	Do we have a Category id?
			//	----------------------------------------
			//  We use LEFT JOIN when there is a 'not' so that we get
			//  entries that are not assigned to a category.
			// --------------------------------

			if ($cat_id != '')
			{
				if (substr($cat_id, 0, 3) == 'not' AND
					$this->check_no(ee()->TMPL->fetch_param('uncategorized_entries')) === false)
				{
					$sql .= "LEFT JOIN exp_category_posts AS cp ON e.entry_id = cp.entry_id ";
				}
				else
				{
					$sql .= "INNER JOIN exp_category_posts AS cp ON e.entry_id = cp.entry_id ";
				}
			}

			$sql	.= " WHERE";

			$sql	.= " t.site_id IN ('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')";

			if ($this->tag_id != '')
			{
				$sql	.= " AND t.tag_id IN ('".implode( "','", ee()->db->escape_str(explode('|', $this->tag_id)))."')";
			}
			elseif($this->tag != '')
			{
				if ($this->model('Data')->preference('convert_case') != 'n')
				{
					array_walk($tags, create_function('$value', 'return strtolower($value);'));
				}

				if (count($tags) == 1)
				{
					$sql	.= " AND BINARY t.tag_name IN ('".implode( "','", ee()->db->escape_str($tags))."')";
				}
				else
				{
					$tsql = "SELECT 	te.entry_id, t.tag_name
							 FROM 		exp_tag_entries AS te
							 LEFT JOIN 	exp_tag_tags AS t
							 ON 		t.tag_id = te.tag_id
							 WHERE 		BINARY t.tag_name
							 IN 		('".implode( "','", ee()->db->escape_str($tags))."')
							 AND 		te.site_id
							 IN 		('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
							 AND 		te.type = 'channel'";

					//--------------------------------------------
					//	tag group
					//--------------------------------------------

					if (ee()->TMPL->fetch_param('tag_group_id'))
					{
						$group_ids = preg_split('/\|/', ee()->TMPL->fetch_param('tag_group_id'), -1, PREG_SPLIT_NO_EMPTY);
					}
					else if (ee()->TMPL->fetch_param('tag_group_name'))
					{
						$group_ids 		= array();

						$group_names 	= preg_split('/\|/', ee()->TMPL->fetch_param('tag_group_name'), -1, PREG_SPLIT_NO_EMPTY);

						foreach ($group_names as $group_name)
						{
							$group_id = $this->model('Data')->get_tag_group_id_by_name($group_name);

							if (is_numeric($group_id))
							{
								$group_ids[] = $group_id;
							}
						}

						//if they pass bad names, return no results because
						//we want it to do the same thing that it will on bad tag_group_ids
						if (empty($group_ids))
						{
							return $this->no_results();
						}
					}

					if (isset($group_ids) AND $group_ids)
					{
						$tsql	.= " AND te.tag_group_id IN (".implode( ",", ee()->db->escape_str($group_ids) ).")";
					}
					$tquery = ee()->db->query($tsql);

					if ($tquery->num_rows() == 0)
					{

						return $this->no_results('tag');
					}

					$entry_array = array();

					foreach($tquery->result_array() as $row)
					{
						$entry_array[$row['tag_name']][] = $row['entry_id'];
					}

					if (count($entry_array) != count($tags))
					{

						return $this->no_results('tag');
					}

					$chosen = call_user_func_array('array_intersect', $entry_array);

					if (count($chosen) == 0)
					{

						return $this->no_results('tag');
					}

					$sql .= "AND e.entry_id IN ('".implode("','", $chosen)."') ";
				}
			}

			$sql	.= " AND e.type = 'channel'";

			// ----------------------------------------------
			//  Limit query by category
			// ----------------------------------------------

			if ($cat_id != '')
			{
				if (substr($cat_id, 0, 3) == 'not' AND $this->check_no(ee()->TMPL->fetch_param('uncategorized_entries')) === false)
				{
					$sql .= ee()->functions->sql_andor_string($cat_id, 'cp.cat_id', '', true)." ";
				}
				else
				{
					$sql .= ee()->functions->sql_andor_string($cat_id, 'cp.cat_id')." ";
				}
			}

			//--------------------------------------------
			//	tag group
			//--------------------------------------------

			if (ee()->TMPL->fetch_param('tag_group_id'))
			{
				$group_ids = preg_split('/\|/', ee()->TMPL->fetch_param('tag_group_id'), -1, PREG_SPLIT_NO_EMPTY);
			}
			else if (ee()->TMPL->fetch_param('tag_group_name'))
			{
				$group_ids 		= array();

				$group_names 	= preg_split('/\|/', ee()->TMPL->fetch_param('tag_group_name'), -1, PREG_SPLIT_NO_EMPTY);

				foreach ($group_names as $group_name)
				{
					$group_id = $this->model('Data')->get_tag_group_id_by_name($group_name);

					if (is_numeric($group_id))
					{
						$group_ids[] = $group_id;
					}
				}

				//if they pass bad names, return no results because
				//we want it to do the same thing that it will on bad tag_group_ids
				if (empty($group_ids))
				{
					return $this->no_results();
				}
			}

			if (isset($group_ids) AND $group_ids)
			{
				$sql	.= " AND e.tag_group_id IN (".implode( ",", ee()->db->escape_str($group_ids) ).")";
			}

			/*else
			{
				$sql	.= " GROUP BY e.tag_id ";
			}*/

			//	----------------------------------------
			//	Are we ranking?
			//	----------------------------------------

			if ( isset( $tag_rank ) )
			{
				$sql	.= " ORDER BY t.".$tag_rank." DESC";
			}

			$query	= ee()->db->query( $sql );

			if ( $query->num_rows() == 0 )
			{

				return $this->no_results('tag');
			}

			$arr	= array();

			foreach ( $query->result_array() as $row )
			{
				$arr[ $row['tag_id'] ][]	= $row['entry_id'];
			}

			if ( count( $arr ) < 2 )
			{
				$chosen	= array_shift( $arr );
			}
			else
			{
				//we need a unique set of entry ids so we dont have repeat results
				$chosen = array_unique(call_user_func_array('array_merge', $arr));
			}

			if ( count( $chosen ) == 0 )
			{

				return $this->no_results('tag');
			}

			$this->entry_id	= implode( "|", $chosen );
		}

		// ----------------------------------------------
		//  Only Entries with Pages
		// ----------------------------------------------

		if ( ee()->TMPL->fetch_param('show_pages') !== false AND
			 in_array( ee()->TMPL->fetch_param('show_pages'), array('only', 'no') ) AND
			 ( $pages = ee()->config->item('site_pages') ) !== false)
		{
			//is this version 2?
			if (  ! array_key_exists('templates', $pages) AND
				  array_key_exists(ee()->config->item('site_id'), $pages) )
			{
				$pages = $pages[ee()->config->item('site_id')];
			}

			if ( ee()->TMPL->fetch_param('show_pages') == 'only' )
			{
				$this->entry_id	= implode( "|", array_intersect( explode( "|", $this->entry_id ), array_flip( $pages['templates'] ) ) );
			}
			else
			{
				$this->entry_id	= implode( "|", array_diff( explode( "|", $this->entry_id ), array_flip( $pages['templates'] ) ) );
			}
		}

		//	----------------------------------------
		//	Parse entries
		//	----------------------------------------

		if ( ! $tagdata = $this->_entries( array('dynamic' => 'off', 'show_pages' => 'yes') ) )
		{

			return $this->no_results('tag');
		}

		return $tagdata;
	}
	//	END entries


	// --------------------------------------------------------------------

	/**
	 *	The Parsing of Entries using Channel/Weblog module
	 *
	 *	@access		protected
	 *	@param		array - Additional parameters
	 *	@return		string
	 */

	protected function _entries ( $params = array() )
	{
		//	----------------------------------------
		//	Execute?
		//	----------------------------------------

		if ( $this->entry_id == '' ) return false;

		//	----------------------------------------
		//	Invoke Channel class
		//	----------------------------------------

		if ( ! class_exists('Channel') )
		{
			require PATH_MOD.'/channel/mod.channel.php';
		}

		$channel = new Channel;

		$channel_class_vars	= get_class_vars('Channel');
		$pager_sql_support	= isset($channel_class_vars['pager_sql']);

		// --------------------------------------------
		//  Invoke Pagination for EE 2.4 and Above
		// --------------------------------------------

		$channel = $this->add_pag_to_channel($channel);

		//	----------------------------------------
		//	Pass params
		//	----------------------------------------

		if (ee()->TMPL->fetch_param('channel'.'_entry_id') !== false AND
			ee()->TMPL->fetch_param('channel'.'_entry_id') != ''
			AND ctype_digit(str_replace(array("not ", "|"), '',
				ee()->TMPL->fetch_param('channel'.'_entry_id'))) === true
		   )
		{
			if (substr(ee()->TMPL->fetch_param('channel'.'_entry_id'), 0, 4) == 'not ')
			{
				// Only those Entry IDs not in the parameter.
				$this->entry_id = implode('|', array_diff(explode('|', $this->entry_id), explode('|', substr(ee()->TMPL->fetch_param('channel'.'_entry_id'), 4))));
			}
			else
			{

				$this->entry_id = implode('|', array_intersect(explode('|', $this->entry_id), explode('|', ee()->TMPL->fetch_param('channel'.'_entry_id'))));
			}
		}

		ee()->TMPL->tagparams['entry_id']	= $this->entry_id;

		ee()->TMPL->tagparams['url_title']	= '';

		ee()->TMPL->tagparams['inclusive']	= '';

		ee()->TMPL->tagparams['show_pages']	= 'all';

		if ( isset( $params['dynamic'] ) AND $params['dynamic'] == "off" )
		{
			ee()->TMPL->tagparams['dynamic']	= 'off';
		}

		//	----------------------------------------
		//	Execute needed methods
		//	----------------------------------------

		if ($channel->enable['custom_fields'] == true)
		{
			$channel->fetch_custom_channel_fields();
		}

		if ($channel->enable['member_data'] == true)
		{
			$channel->fetch_custom_member_fields();
		}

		// --------------------------------------------
		//  Pagination Tags Parsed Out
		// --------------------------------------------

		if ($channel->enable['pagination'] == true)
		{
			ee()->TMPL->tagdata = $this->pagination_prefix_replace(
				'tag',
				ee()->TMPL->tagdata
			);

			$channel = $this->fetch_pagination_data($channel);

			ee()->TMPL->tagdata = $this->pagination_prefix_replace(
				'tag',
				ee()->TMPL->tagdata,
				true
			);
		}

		//	----------------------------------------
		//	Grab entry data
		//	----------------------------------------

		// Since they no longer give us $this->pager_sql in EE 2.4, I will just
		// insure it is stored  and pull it right back out to use again.
		// It comes back in EE 2.5, so feature test here
		if ( ! $pager_sql_support)
		{
			ee()->db->save_queries = true;
		}

		$channel->build_sql_query();

		// Stop protecting our apostrophes
		ee()->uri->uri_string = str_replace(
			'_PROTECTED_APOSTROPHE_',
			"'",
			ee()->uri->uri_string
		);

		$_SERVER['REQUEST_URI'] = str_replace(
			'_PROTECTED_APOSTROPHE_',
			"'",
			$_SERVER['REQUEST_URI']
		);

		if ($channel->sql == '')
		{
			return $this->return_data = $this->no_results('tag');
		}

		// --------------------------------------------
		//  Transfer Pagination Variables Over to Channel object
		//	- Has to go after the building of the query
		//	as EE 2.4 does its Pagination work in there
		// --------------------------------------------

		$transfer = array(
			'paginate'		=> 'paginate',
			'total_pages' 	=> 'total_pages',
			'current_page'	=> 'current_page',
			'offset'		=> 'offset',
			'page_next'		=> 'page_next',
			'page_previous'	=> 'page_previous',
			'page_links'	=> 'pagination_links', // different!
			'total_rows'	=> 'total_rows',
			'per_page'		=> 'per_page',
			'per_page'		=> 'p_limit',
			'offset'		=> 'p_page'
		);

		foreach($transfer as $from => $to)
		{
			$channel->$to = $channel->pagination->$from;
		}

		 // --------------------------------------------
		//  Order By Relevance for the Related Entries Tag
		// --------------------------------------------

		if (ee()->TMPL->fetch_param('orderby') == 'relevance' AND
			isset(ee()->TMPL->tagparts[1]) AND
			ee()->TMPL->tagparts[1] == 'related_entries')
		{
			$offset = (
				! ee()->TMPL->fetch_param('offset') OR
				! is_numeric(ee()->TMPL->fetch_param('offset'))) ?
					'0' :
					ee()->TMPL->fetch_param('offset');

			if ($channel->paginate == true && ! empty($channel->pager_sql))
			{
				if (preg_match("/ORDER BY(.*?)(LIMIT|$)/s", $channel->sql, $matches) AND
					! stristr($channel->pager_sql, 'ORDER BY'))
				{
					$channel->pager_sql .= 'ORDER BY'.$matches[1];
				}

				// Create our ORDER BY clauses

				$orderby_clause = ' ORDER BY FIELD(t.entry_id, ' .
							 str_replace('|', ',', $this->entry_id). ') ';

				if (stristr($channel->pager_sql, 'ORDER BY'))
				{
					$channel->pager_sql = preg_replace(
						"/ORDER BY(.*?)(,|LIMIT|$)/s",
						$orderby_clause.',\1\2',
						$channel->pager_sql
					);
				}
				else
				{
					$channel->pager_sql .= $orderby_clause;
				}

				if ( ! stristr($channel->pager_sql, 'LIMIT'))
				{
					$offset = ( ! ee()->TMPL->fetch_param('offset') OR
								! is_numeric(ee()->TMPL->fetch_param('offset'))) ?
									'0' : ee()->TMPL->fetch_param('offset');

					$channel->pager_sql .= ($channel->p_page == '') ?
						" LIMIT " . $offset . ', ' . $channel->p_limit :
						" LIMIT " . $channel->p_page . ', ' . $channel->p_limit;
				}

				$pquery = ee()->db->query($channel->pager_sql);

				$entries = array();

				// Build ID numbers (checking for duplicates)
				foreach ($pquery->result_array() as $row)
				{
					$entries[] = $row['entry_id'];
				}

				$channel->sql = preg_replace(
					"/t\.entry_id\s+IN\s+\([^\)]+\)/is",
					"t.entry_id IN (".implode(',', $entries).")",
					$channel->sql
				);

				$channel->sql = preg_replace("/ORDER BY(.*?)(,|LIMIT|$)/s",
					$orderby_clause.',\1\2',
					$channel->sql
				);

				unset($pquery);
				unset($entries);
			}
			//END if ($channel->paginate == true
		}
		//END if (ee()->TMPL->fetch_param('orderby') == 'relevance'

		$channel->query = ee()->db->query($channel->sql);

		if ($channel->query->num_rows() == 0)
		{
			return false;
		}

		//	----------------------------------------
		//	Are we forcing the order?
		//	----------------------------------------

		if ( ee()->TMPL->fetch_param( 'tag_rank' ) !== false )
		{
			//	----------------------------------------
			//	Reorder
			//	----------------------------------------
			//	The channel class fetches entries and
			//	sorts them for us, but not according to
			//	our ranking order. So we need to
			//	reorder them.
			//	----------------------------------------

			$new	= array_flip(explode( "|", $this->entry_id ));

			foreach ( $channel->query->result_array() as $key => $row )
			{
				$new[$row['entry_id']] = $row;
			}

			foreach ( $new as $key => $val )
			{
				if ( is_array( $val ) !== true )
				{
					unset( $new[$key] );
				}
			}

			//	----------------------------------------
			//	Redeclare
			//	----------------------------------------
			//	We will reassign the $channel->query->result with our
			//	reordered array of values. Thank you PHP for being so fast with array loops.
			//	----------------------------------------

			$channel->query->result_array = array_values($new);

			//	Clear some memory
			unset( $new );
			unset( $entries );
		}

		// --------------------------------------------
		//  Typography
		// --------------------------------------------

		ee()->load->library('typography');
		ee()->typography->initialize();
		ee()->typography->convert_curly = false;

		if ($channel->enable['categories'] == true)
		{
			$channel->fetch_categories();
		}

		// --------------------------------------------
		//  Last Bit of Relevance Code
		// --------------------------------------------

		$sets = array();

		$related_entries = false;

		$hash = "0bdc060acf3d52a2aa1e6f266accd4da48a03951";

		if (ee()->TMPL->fetch_param('orderby') == 'relevance' AND
			isset(ee()->TMPL->tagparts[1]) AND
			ee()->TMPL->tagparts[1] == 'related_entries')
		{
			$related_entries = true;

			foreach ( $channel->query->result_array() as $key => $row )
			{
				$channel->query->result_array[$key]['max_relevance']			= $this->max_relevance;
				$channel->query->result_array[$key]['tag_relevance']			= $this->tag_relevance[$row['entry_id']];
				$channel->query->result_array[$key]['tag_relevance_percent']	= round(($this->tag_relevance[$row['entry_id']] / $this->max_relevance) * 100);

				$sets[$row['entry_id']] = array(
					'max_relevance'			=> $channel->query->result_array[$key]['max_relevance'],
					'tag_relevance'			=> $channel->query->result_array[$key]['tag_relevance'],
					'tag_relevance_percent'	=> $channel->query->result_array[$key]['tag_relevance_percent']
				);
			}

			ee()->TMPL->tagdata = $hash . '[{entry_id}]' . ee()->TMPL->tagdata . '/' . $hash . '[{entry_id}]';

			//this must be here or entry_id wont parse unless its already in the template
			ee()->TMPL->var_single['entry_id'] = 'entry_id';
		}

		// ----------------------------------------
		//	Parse and return entry data
		// ----------------------------------------

		$channel->parse_channel_entries();

		// -------------------------------------
		//	EE 3.x does not allow us to add
		//	our own data to the channel result array
		//	and have it parsed out so we have to
		//	go whacko and parse it ourselves
		// -------------------------------------

		if ($related_entries)
		{
			//find tag pairs between hashes
			preg_match_all(
				"/" . preg_quote($hash, '/') . '\[([\d]+)\](.*)' .
					preg_quote('/' . $hash, '/') . '\[\\1\]' . '/ims' ,
				$channel->return_data,
				$matches
			);

			if ( ! empty($matches[0]))
			{
				foreach ($matches[0] as $key => $value)
				{
					$channel->return_data = str_replace(
						//our entire tagdata set wrapped in hashes
						$matches[0][$key],
						ee()->TMPL->parse_variables(
							//just the innards of the hash wrap
							$matches[2][$key],
							//the vars we built earlier
							array($sets[$matches[1][$key]])
						),
						$channel->return_data
					);
				}
			}
		}
		//end if ($related_entries)

		// -------------------------------------
		//	pagination
		// -------------------------------------

		if ($channel->enable['pagination'] == true)
		{
			$channel = $this->add_pagination_data($channel);
		}

		//	----------------------------------------
		//	Count tag
		//	----------------------------------------

		$this->count_tag($channel->pagination->current_page);

		//	----------------------------------------
		//	Handle problem with pagination segments in the url
		//	----------------------------------------

		if ( preg_match("#(/?P\d+)#", ee()->uri->uri_string, $match) )
		{
			$channel->return_data	= str_replace( $match['1'], "", $channel->return_data );
		}

		$tagdata = str_replace('_PROTECTED_APOSTROPHE_', "'", $channel->return_data);

		return $tagdata;
	}
	//	END sub entries


	// --------------------------------------------------------------------

	/**
	 * Related entries
	 *
	 * @access	public
	 * @return	string		parsed html
	 */

	public function related_entries()
	{
		//	----------------------------------------
		//	Entry id?
		//	----------------------------------------

		if ( $this->entry_id() === false )
		{

			return $this->no_results('tag');
		}

		//--------------------------------------------
		//	related_entries hack for fake pagination
		//	if orderby relevance is used in ee1, it
		//	shows the items out of order unless
		//	you have some form of pagination
		//--------------------------------------------

		if (ee()->TMPL->fetch_param('orderby') == 'relevance' AND
			! stristr(ee()->TMPL->tagdata, LD . 'paginate' . RD) AND
			! stristr(ee()->TMPL->tagdata, LD . 'tag_paginate' . RD))
		{
			ee()->TMPL->tagdata .= '{paginate}{if entry_id == "999999999"}{pagination_links}{' .
									'/' . 'if}{' . '/' . 'paginate}';
		}

		//--------------------------------------------
		//	tag group
		//--------------------------------------------

		if (ee()->TMPL->fetch_param('tag_group_id'))
		{
			$group_ids = preg_split('/\|/', ee()->TMPL->fetch_param('tag_group_id'), -1, PREG_SPLIT_NO_EMPTY);
		}
		else if (ee()->TMPL->fetch_param('tag_group_name'))
		{
			$group_ids 		= array();

			$group_names 	= preg_split('/\|/', ee()->TMPL->fetch_param('tag_group_name'), -1, PREG_SPLIT_NO_EMPTY);

			foreach ($group_names as $group_name)
			{
				$group_id = $this->model('Data')->get_tag_group_id_by_name($group_name);

				if (is_numeric($group_id))
				{
					$group_ids[] = $group_id;
				}
			}

			//if they pass bad names, return no results because
			//we want it to do the same thing that it will on bad tag_group_ids
			if (empty($group_ids))
			{
				return $this->no_results();
			}
		}

		//	----------------------------------------
		//	Get tag ids for entry
		//	----------------------------------------

		$sql	= "SELECT DISTINCT te1.site_id, te1.entry_id, te1.tag_id";

		if (ee()->TMPL->fetch_param('orderby') == 'relevance')
		{
			$sql .= ", COUNT(DISTINCT te1.tag_id) AS tag_relevance";
		}

		if (count(ee()->TMPL->site_ids) == 1)
		{
			$sql .= " FROM 			exp_tag_entries AS te2
					  INNER JOIN 	exp_tag_entries te1
					  ON 			te1.tag_id = te2.tag_id
					  WHERE 		te1.type = 'channel'
					  AND 			te2.type = 'channel'
					  AND			te2.entry_id = '".ee()->db->escape_str($this->entry_id)."'
					  AND			te1.entry_id != '".ee()->db->escape_str($this->entry_id)."'
					  AND 			te1.site_id
					  IN 			('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
					  AND 			te2.site_id
					  IN 			('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')";
		}
		else
		{
			// So much work, just to get it to work across multiple Sites.

			$sql .= " FROM 			exp_tag_entries AS te2
					  INNER JOIN 	exp_tag_tags tt2
					  ON 			tt2.tag_id = te2.tag_id
					  INNER JOIN 	exp_tag_tags tt1
					  ON 			tt1.tag_name = tt2.tag_name
					  INNER JOIN 	exp_tag_entries te1
					  ON 			te1.tag_id = tt1.tag_id
					  WHERE 		te1.type = 'channel'
					  AND 			te2.type = 'channel'
					  AND 			te2.entry_id = '".ee()->db->escape_str($this->entry_id)."'
					  AND 			te1.entry_id != '".ee()->db->escape_str($this->entry_id)."'
					  AND 			te1.site_id
					  IN 			('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
					  AND 			te2.site_id
					  IN 			('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')";
		}

		//--------------------------------------------
		//	tag group
		//--------------------------------------------

		if (isset($group_ids) AND $group_ids)
		{
			$sql	.= " AND te1.tag_group_id IN (".implode( ",", ee()->db->escape_str($group_ids) ).")";
			$sql	.= " AND te2.tag_group_id IN (".implode( ",", ee()->db->escape_str($group_ids) ).")";
		}

		//	----------------------------------------
		//	Exclude?
		//	----------------------------------------

		if ( ee()->TMPL->fetch_param('exclude') !== false AND ee()->TMPL->fetch_param('exclude') != '' )
		{
			$ids	= $this->exclude( ee()->TMPL->fetch_param('exclude') );

			if ( is_array( $ids ) )
			{
				$sql	.= " AND te1.tag_id NOT IN ('".implode( "','", ee()->db->escape_str($ids) )."')";
			}
		}

		//----------------------------------------
		//	Rank limit
		//----------------------------------------
		//	We can pull entries by tag rank.
		//	Users can indicate their ranking method
		//	and pull by clicks, entries or both.
		//----------------------------------------

		if ( ctype_digit( ee()->TMPL->fetch_param('rank_limit') ) === true )
		{
			$rank		= array();

			if (count(ee()->TMPL->site_ids) == 1)
			{
				$sql_rank	= " SELECT 		tt1.tag_id, ( tt1.total_entries + tt1.clicks ) AS sum
								FROM 		exp_tag_entries AS te2
								INNER JOIN 	exp_tag_tags tt1
								ON 			tt1.tag_id = te2.tag_id
								WHERE 		tt1.site_id
								IN 			('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
								AND 		te2.type = 'channel'
								AND 		te2.entry_id != '".ee()->db->escape_str($this->entry_id)."'";
			}
			else
			{
				$sql_rank	= " SELECT 		tt1.tag_id, ( tt1.total_entries + tt1.clicks ) AS sum
								FROM 		exp_tag_entries AS te2
								INNER JOIN 	exp_tag_tags tt2
								ON 			tt2.tag_id = te2.tag_id
								INNER JOIN 	exp_tag_tags tt1
								ON 			tt1.tag_name = tt2.tag_name
								WHERE 		tt1.site_id
								IN 			('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
								AND 		te2.type = 'channel'
								AND 		te2.entry_id != '".ee()->db->escape_str($this->entry_id)."'";
			}

			//--------------------------------------------
			//	tag group
			//--------------------------------------------

			if (isset($group_ids) AND $group_ids)
			{
				$sql_rank	.= " AND te2.tag_group_id IN (".implode( ",", ee()->db->escape_str($group_ids) ).")";
			}

			//	----------------------------------------
			//	Filter to our tags only
			//	----------------------------------------

			if (ee()->TMPL->fetch_param('orderby') == 'relevance')
			{
				$query	= ee()->db->query( $sql." GROUP BY te1.entry_id ORDER BY tag_relevance");
			}
			else
			{
				$query	= ee()->db->query( $sql );
			}

			if ( $query->num_rows() == 0 )
			{

				return $this->no_results('tag');
			}

			if ($query->num_rows() > 0)
			{
				$data = array();

				foreach ( $query->result_array() as $row )
				{
					$data[] = $row['tag_id'];
				}

				$sql_rank .= "AND tt1.tag_id IN (".implode(',', $data).")";
			}

			//	----------------------------------------
			//	Group
			//	----------------------------------------

			$sql_rank	.= " GROUP BY tt1.tag_id";

			$rank_method	= ( ee()->TMPL->fetch_param('rank_method') ) ? ee()->TMPL->fetch_param('rank_method'): '';

			$allowed_ranks	= array( 'total_entries', 'clicks' );

			//	----------------------------------------
			//	Rank by both entries and clicks?
			//	----------------------------------------

			if ( $rank_method == '' OR ( stristr( $rank_method, 'total_entries' ) AND stristr( $rank_method, 'clicks' ) ) )
			{
				$sql_rank	.= " ORDER BY sum";
			}

			//	----------------------------------------
			//	Rank by one vector?
			//	----------------------------------------

			elseif ( in_array( $rank_method, $allowed_ranks ) )
			{
				$sql_rank	.= " ORDER BY tt1.".ee()->db->escape_str( $rank_method );
			}
			else
			{
				$sql_rank	.= " ORDER BY tt1.total_entries";
			}

			$sql_rank	.= " DESC LIMIT ".ee()->TMPL->fetch_param('rank_limit');

			ee()->TMPL->log_item("Tag sql_rank:".$sql_rank);

			$r			= ee()->db->query( $sql_rank );

			foreach ( $r->result_array() as $row )
			{
				$rank[]	= ee()->db->escape_str( $row['tag_id'] );
			}

			unset($r);

			$sql	.= " AND te1.tag_id IN ('".implode( "','", ee()->db->escape_str($rank) )."')";
		}

		if (ee()->TMPL->fetch_param('orderby') == 'relevance')
		{
			$sql .= " GROUP BY te1.entry_id ORDER BY tag_relevance";

			$sort = ee()->TMPL->fetch_param('sort');

			switch ($sort)
			{
				case 'asc'	: $sql .= " asc";
					break;
				case 'desc'	: $sql .= " desc";
					break;
				default		: $sql .= " desc";
					break;
			}
		}

		//	----------------------------------------
		//	Run query
		//	----------------------------------------

		$query	= ee()->db->query( $sql );

		ee()->TMPL->log_item("Tag sql:".$sql);

		if ( $query->num_rows() == 0 )
		{

			return $this->no_results('tag');
		}

		//	----------------------------------------
		//	 Count of Original Entry's Tags for Max Relevance
		//	----------------------------------------

		if (ee()->TMPL->fetch_param('orderby') == 'relevance')
		{

			$msql =	"SELECT COUNT(DISTINCT tag_id) AS count
					 FROM 	exp_tag_entries
					 WHERE 	type 		= 'channel'
					 AND 	entry_id 	= " . ee()->db->escape_str($this->entry_id);

			if (isset($group_ids) AND $group_ids)
			{
				$msql	.= " AND tag_group_id IN (".implode( ",", ee()->db->escape_str($group_ids) ).")";
			}


			$mquery = ee()->db->query($msql);

			$this->max_relevance = $mquery->row('count');
		}

		//	----------------------------------------
		//	Assemble entry ids
		//	----------------------------------------

		$this->old_entry_id = $this->entry_id;

		$ids	= array();

		foreach ( $query->result_array() as $row )
		{
			if (isset($row['tag_relevance']))
			{
				$this->tag_relevance[$row['entry_id']] = $row['tag_relevance'];
			}

			$ids[] = $row['entry_id'];
		}

		$this->entry_id	= implode('|', $ids);

		//	----------------------------------------
		//	Parse entries
		//	----------------------------------------

		if ( ! $tagdata = $this->_entries( array( 'dynamic' => 'off' ) ) )
		{

			return $this->no_results('tag');
		}

		return $tagdata;
	}
	//	END related entries


	//	----------------------------------------
	//	Cloud
	//	----------------------------------------

	// --------------------------------------------------------------------

	/**
	 * Cloud
	 *
	 * @access	public
	 * @return	string		parsed html
	 */

	public function cloud()
	{
		$max 					= 1;  // Must be 1, cannot divide by zero!

		$rank_by				= (ee()->TMPL->fetch_param('rank_by') == 'clicks') ? 'clicks' : 'entries';

		$groups					= ( ctype_digit( ee()->TMPL->fetch_param('groups') ) === true ) ?
									ee()->TMPL->fetch_param('groups') : 5;

		$start					= ( ctype_digit( ee()->TMPL->fetch_param('start') ) === true ) ?
									ee()->TMPL->fetch_param('start') : 10;

		$step					= ( ctype_digit( ee()->TMPL->fetch_param('step') ) === true ) ?
									ee()->TMPL->fetch_param('step') : 2;

		$username				= ee()->TMPL->fetch_param('username', '');
		$author_id				= ee()->TMPL->fetch_param('author_id', '');
		$show_expired			= ee()->TMPL->fetch_param('show_expired', 'no');
		$show_future_entries	= ee()->TMPL->fetch_param('show_future_entries', 'no');
		$start_on				= ee()->TMPL->fetch_param('start_on', '');
		$status					= ee()->TMPL->fetch_param('status', '');
		$stop_before			= ee()->TMPL->fetch_param('stop_before', '');
		$day_limit				= ee()->TMPL->fetch_param('day_limit', '');
		$websafe_separator		= ee()->TMPL->fetch_param('websafe_separator', '+');


		// --------------------------------------------
		//  Fixed Order - Override of tag_id="" parameter
		// --------------------------------------------

		// fixed entry id ordering
		if (($fixed_order = ee()->TMPL->fetch_param('fixed_order')) === false OR
			 preg_match('/[^0-9\|]/', $fixed_order))
		{
			$fixed_order = false;
		}
		else
		{
			// Override Tag ID parameter to get exactly these entries
			// Other parameters will still affect results. I blame the user for using them if it
			// does not work they way they want.
			ee()->TMPL->tagparams['tag_id'] = $fixed_order;

			$fixed_order = preg_split('/\|/', $fixed_order, -1, PREG_SPLIT_NO_EMPTY);

			// A quick and easy way to reverse the order of these entries.  People might like this.
			if (ee()->TMPL->fetch_param('sort') == 'desc')
			{
				$fixed_order = array_reverse($fixed_order);
			}
		}

		// -------------------------------------
		//	tag groups?
		// -------------------------------------

		//pre-escaped
		$tag_group_sql_insert = $this->model('Data')->tag_total_entries_sql_insert('t');

		$entries_prefix	= "wt";

		//	----------------------------------------
		//	Begin SQL
		//	----------------------------------------

		$sql = "SELECT 		t.tag_id,
							t.clicks,
							t.tag_name,
							t.total_entries,
							{$tag_group_sql_insert}
							t.channel_entries,
							w.channel_id,
							w.channel_url,
							w.comment_url,
							COUNT(e.tag_id) AS count
				FROM 		exp_tag_tags AS t
				LEFT JOIN 	exp_tag_entries e
				ON 			t.tag_id = e.tag_id
				LEFT JOIN 	exp_channels AS w
				ON 			w.channel_id = e.channel_id";

		//	----------------------------------------
		//	Handle date stuff
		//	----------------------------------------

		if ( 	$start_on != '' OR
				$stop_before != '' OR
				$day_limit != '' OR
				$status != '' OR
				$show_expired != '' OR
				$show_future_entries != '' )
		{
			$sql	.= " LEFT JOIN exp_channel_titles AS wt " .
						"ON wt.entry_id = e.entry_id";
		}

		//	----------------------------------------
		//	Are we checking category?
		//	----------------------------------------

		if ( ee()->TMPL->fetch_param('category') !== false AND
			 ee()->TMPL->fetch_param('category') != '' )
		{
			//	----------------------------------------
			//	Get the id
			//	----------------------------------------

			if ( ctype_digit( str_replace( array("not ", "|"), '',
						ee()->TMPL->fetch_param('category') ) ) === true )
			{
				$cat_id	= ee()->TMPL->fetch_param('category');
			}
			elseif ( preg_match( "/C(\d+)/s", ee()->TMPL->fetch_param('category'), $match ) )
			{
				$cat_id	= $match['1'];
			}
			else
			{
				$cat_q	= ee()->db->query(
					"SELECT cat_id
					 FROM 	exp_categories
					 WHERE 	site_id
					 IN 	('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
					 AND 	cat_url_title = '".ee()->db->escape_str( ee()->TMPL->fetch_param('category') )."'"
				);

				if ( $cat_q->num_rows() > 0 )
				{
					$cat_id	= '';

					foreach ( $cat_q->result_array() as $row )
					{
						$cat_id	.= $row['cat_id']."|";
					}
				}
			}

			//	----------------------------------------
			//	Do we have an id?
			//	----------------------------------------

			if ( isset( $cat_id ) )
			{
				$sql .= " LEFT JOIN exp_category_posts AS cp ON e.entry_id = cp.entry_id";
			}
		}

		$sql .= " WHERE t.site_id IN ('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
				  AND t.tag_id != '' AND e.type = 'channel'";

		//	----------------------------------------
		//	No bad tags
		//	----------------------------------------

		if (count($this->bad()) > 0)
		{
			$sql	.= " AND t.tag_name NOT IN ('".implode( "','", ee()->db->escape_str($this->bad()) )."')";
		}

		//--------------------------------------------
		//	tag group
		//--------------------------------------------

		if (ee()->TMPL->fetch_param('tag_group_id'))
		{
			$group_ids = preg_split('/\|/', ee()->TMPL->fetch_param('tag_group_id'), -1, PREG_SPLIT_NO_EMPTY);
		}
		else if (ee()->TMPL->fetch_param('tag_group_name'))
		{
			$group_ids 		= array();

			$group_names 	= preg_split('/\|/', ee()->TMPL->fetch_param('tag_group_name'), -1, PREG_SPLIT_NO_EMPTY);

			foreach ($group_names as $group_name)
			{
				$group_id = $this->model('Data')->get_tag_group_id_by_name($group_name);

				if (is_numeric($group_id))
				{
					$group_ids[] = $group_id;
				}
			}

			//if they pass bad names, return no results because
			//we want it to do the same thing that it will on bad tag_group_ids
			if (empty($group_ids))
			{
				return $this->no_results();
			}
		}

		if (isset($group_ids) AND $group_ids)
		{
			$sql	.= " AND e.tag_group_id IN (".implode( ",", ee()->db->escape_str($group_ids) ).")";
		}

		//	----------------------------------------
		//	 Narrow Tags via Tag Name
		//	----------------------------------------

		if ( ee()->TMPL->fetch_param('tag_name') !== false AND ee()->TMPL->fetch_param('tag_name') != '' )
		{
			if (substr( ee()->TMPL->fetch_param('tag_name'), 0, 4) == 'not ')
			{
				$ids	= $this->exclude( substr(ee()->TMPL->fetch_param('tag_name'), 4));

				if ( is_array( $ids ) )
				{
					$sql	.= " AND t.tag_id NOT IN ('".implode( "','", ee()->db->escape_str($ids) )."')";
				}
			}
			else
			{
				$ids	= $this->exclude( ee()->TMPL->fetch_param('tag_name') );

				if ( is_array( $ids ) )
				{
					$sql	.= " AND t.tag_id IN ('".implode( "','", ee()->db->escape_str($ids) )."')";
				}
			}
		}

		//	----------------------------------------
		//	 Narrow Tags via Tag ID
		//	----------------------------------------

		if ( ee()->TMPL->fetch_param('tag_id') !== false AND ee()->TMPL->fetch_param('tag_id') != '' )
		{
			$sql .= ee()->functions->sql_andor_string( ee()->TMPL->fetch_param('tag_id'), "t.tag_id" );
		}

		//	----------------------------------------
		//	Exclude?
		//	----------------------------------------

		if ( ee()->TMPL->fetch_param('exclude') !== false AND ee()->TMPL->fetch_param('exclude') != '' )
		{
			$ids	= $this->exclude( ee()->TMPL->fetch_param('exclude') );

			if ( is_array( $ids ) )
			{
				$sql	.= " AND t.tag_id NOT IN ('".implode( "','", ee()->db->escape_str($ids) )."')";
			}
		}

		//	----------------------------------------
		//	Are we checking category?
		//	----------------------------------------

		if ( isset( $cat_id ) )
		{
			$sql .= " ".ee()->functions->sql_andor_string( $cat_id, "cp.cat_id" );
		}

		//	----------------------------------------
		//	Limit to/exclude specific channels
		//	----------------------------------------

		if ($channel = ee()->TMPL->fetch_param('channel'))
		{
			$xql = "SELECT channel_id FROM exp_channels
					WHERE site_id IN ('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')";

			$xql .= ee()->functions->sql_andor_string($channel, 'channel_name');

			$query = ee()->db->query($xql);

			if ($query->num_rows() == 0)
			{

				return $this->no_results('tag');
			}
			else
			{
				$zchannels = array();

				foreach ($query->result_array() as $row)
				{
					$zchannels[] = $row['channel_id'];
				}

				$sql .= " AND e.channel_id IN ('".implode("','", ee()->db->escape_str($zchannels))."')";
			}
		}

		// ----------------------------------------------
		//  We only select entries that have not expired
		// ----------------------------------------------

		$timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

		if ( $show_future_entries != 'yes')
		{
			$sql .= " AND wt.entry_date < ".$timestamp." ";
		}

		if ( $show_expired != 'yes')
		{
			$sql .= " AND (wt.expiration_date = 0 || wt.expiration_date > ".$timestamp.") ";
		}

		//	-----------------------------------------
		//	Limit by status
		//	----------------------------------------

		if ( $status != '' )
		{
			$sql	.= " ".ee()->functions->sql_andor_string( $status, $entries_prefix.".status" );
		}

		//	-----------------------------------------
		//	Limit by author
		//	----------------------------------------

		if ( ctype_digit( $author_id ) === true )
		{
			$sql .= " AND e.author_id = '".ee()->db->escape_str( $author_id )."'";
		}
		elseif ( $username == 'CURRENT_USER' )
		{
			$sql .= " AND e.author_id = '".ee()->db->escape_str( ee()->session->userdata('member_id') )."'";
		}
		elseif ( $username != '' )
		{
			$m_id = ee()->db->query(
				"SELECT member_id
				 FROM 	exp_members
				 WHERE 	username='".ee()->db->escape_str( $username )."'"
			);

			if ( $m_id->num_rows() > 0 )
			{
				$sql .= " AND e.author_id = '".$m_id->row('member_id')."'";
			}
		}

		//	----------------------------------------
		//	Limit query by number of days
		//	----------------------------------------

		if ( $day_limit != '' )
		{
			$time = ee()->localize->now - ( $day_limit * 60 * 60 * 24);

			$sql .= " AND ".$entries_prefix.".entry_date >= '".$time."'";
		}
		else // OR
		{
			//	----------------------------------------
			//	Limit query by date range given in tag parameters
			//	----------------------------------------

			if ( $start_on != '' )
				$sql .= " AND ".$entries_prefix.".entry_date >= '".ee()->localize->convert_human_date_to_gmt($start_on)."'";

			if ( $stop_before != '' )
				$sql .= " AND ".$entries_prefix.".entry_date < '".ee()->localize->convert_human_date_to_gmt($stop_before)."'";
		}

		// --------------------------------------
		//  Most Popular Tags, by #
		// --------------------------------------

		if (ee()->TMPL->fetch_param('most_popular') !== false AND
			is_numeric(ee()->TMPL->fetch_param('most_popular')))
		{
			if ($rank_by == 'clicks')
			{
				$query = ee()->db->query(preg_replace("/SELECT(.*?)\s+FROM\s+/is", 'SELECT DISTINCT t.tag_id FROM ', $sql)." ORDER BY t.clicks DESC LIMIT 0, ".ceil(ee()->TMPL->fetch_param('most_popular')));
			}
			else
			{
				$query = ee()->db->query(preg_replace("/SELECT(.*?)\s+FROM\s+/is", 'SELECT DISTINCT t.tag_id FROM ', $sql)." ORDER BY t.total_entries DESC LIMIT 0, ".ceil(ee()->TMPL->fetch_param('most_popular')));
			}

			if ($query->num_rows() == 0)
			{

				return $this->return_data = $this->no_results('tag');
			}

			$tag_ids = array();

			foreach($query->result_array() as $row)
			{
				$tag_ids[] = $row['tag_id'];
			}

			$sql .= " AND t.tag_id IN (".implode(',', $tag_ids).")";
		}


		// --------------------------------------
		//  Pagination checkeroo! - Do Before GROUP BY!
		// --------------------------------------

		$query = ee()->db->query(preg_replace(
			"/SELECT(.*?)\s+FROM\s+/is",
			'SELECT COUNT(DISTINCT e.tag_id) AS count FROM ',
			$sql
		));

		if ($query->row('count') == 0 AND
			 (strpos( ee()->TMPL->tagdata, 'paginate' ) !== false AND
			  strpos( ee()->TMPL->tagdata, 'tag_paginate' ) !== false))
		{

			return $this->return_data = $this->no_results('tag');
		}

		$this->p_limit  	= ( ! ee()->TMPL->fetch_param('limit'))  ? 20 : ee()->TMPL->fetch_param('limit');
		$this->total_rows 	= $query->row('count');
		$this->p_page 		= ($this->p_page == '' || ($this->p_limit > 1 AND $this->p_page == 1)) ? 0 : $this->p_page;

		if ($this->p_page > $this->total_rows)
		{
			$this->p_page = 0;
		}

		$prefix = stristr(ee()->TMPL->tagdata, LD . 'tag_paginate' . RD);

		//get pagination info
		$pagination_data = $this->universal_pagination(array(
			'sql'					=> preg_replace(
				"/SELECT(.*?)\s+FROM\s+/is",
				'SELECT COUNT(DISTINCT e.tag_id) AS count FROM ',
				$sql
			),
			'total_results'			=> $this->total_rows,
			'tagdata'				=> ee()->TMPL->tagdata,
			'limit'					=> $this->p_limit,
			'uri_string'			=> ee()->uri->uri_string,
			'current_page'			=> $this->p_page,
			'prefix'				=> 'tag',
			'auto_paginate'			=> true
		));

		//if we paginated, sort the data
		if ($pagination_data['paginate'] === true)
		{
			ee()->TMPL->tagdata		= $pagination_data['tagdata'];
		}

		//	----------------------------------------
		//	Set group by
		//	----------------------------------------

		$sql .= " GROUP BY e.tag_id";

		//	----------------------------------------
		//	Find Max for All Pages
		//	----------------------------------------

		if ($this->paginate === true)
		{
			if ($rank_by == 'clicks')
			{
				$query = ee()->db->query($sql." ORDER BY clicks DESC LIMIT 0, 1");
			}
			else
			{
				$query = ee()->db->query($sql." ORDER BY count DESC LIMIT 0, 1");
			}

			if ($query->num_rows() > 0)
			{
				$max = ($rank_by == 'clicks') ? $query->row('clicks') : $query->row('count');
			}
		}

		//	----------------------------------------
		//	Set order by
		//	----------------------------------------

		$ord	= " ORDER BY t.tag_name";

		if ($fixed_order !== false)
		{
			$ord = ' ORDER BY FIELD(e.tag_id, '.implode(',', $fixed_order).') ';
		}
		elseif ( ee()->TMPL->fetch_param('orderby') !== false AND
				 ee()->TMPL->fetch_param('orderby') != '' )
		{
			foreach ( array(
					'random' 			=> "rand()",
					'clicks'			=> "t.clicks",
					'count' 			=> 'count',
					'total_entries' 	=> 't.total_entries',
					'channel_entries' 	=> 't.channel_entries',
					'tag_name' 			=> 't.tag_name'
				) as $key => $val )
			{
				if ( $key == ee()->TMPL->fetch_param('orderby') )
				{
					$ord	= " ORDER BY ".$val;
				}
			}
		}

		$sql .= $ord;

		//	----------------------------------------
		//	Set sort
		//	----------------------------------------

		if (ee()->TMPL->fetch_param('orderby') !== 'random' AND
			$fixed_order === false)
		{
			if ( (ee()->TMPL->fetch_param('sort') !== false AND
				 ee()->TMPL->fetch_param('sort') == 'asc')
				 OR stristr($ord, 'tag_name') )
			{
				$sql	.= " ASC";
			}
			else
			{
				$sql	.= " DESC";
			}
		}

		//	----------------------------------------
		//	Set numerical limit
		//	----------------------------------------

		if ($this->paginate === true AND $this->total_rows > $this->p_limit)
		{
			$sql .= " LIMIT ".$this->p_page.', '.$this->p_limit;
		}
		else
		{
			$sql .= ( ctype_digit( ee()->TMPL->fetch_param('limit') ) === true ) ?
					' LIMIT '.ee()->TMPL->fetch_param('limit') : ' LIMIT 20';
		}

		//	----------------------------------------
		//	Query
		//	----------------------------------------

		$query	= ee()->db->query( $sql );

		//	----------------------------------------
		//	Empty?
		//	----------------------------------------

		if ( $query->num_rows() == 0 )
		{

			return $this->no_results('tag');
		}

		//	----------------------------------------
		//	What's the max?
		//	----------------------------------------

		// If we have Pagination, we find the MAX value up above.
		// If not, we find it based on the current results.

		if ($this->paginate !== true)
		{
			foreach ( $query->result_array() as $row )
			{
				if ($rank_by == 'clicks')
				{
					$max	= ( $row['clicks'] > $max ) ? $row['clicks']: $max;
				}
				else
				{
					$max	= ( $row['count'] > $max ) ? $row['count']: $max;
				}
			}
		}

		//	----------------------------------------
		//	Order alpha
		//	----------------------------------------

		$tags	= array();

		foreach ( $query->result_array() as $row )
		{
			$tags[$row['tag_name']]['tag_id']			= $row['tag_id'];
			$tags[$row['tag_name']]['count']			= $row['count'];
			$tags[$row['tag_name']]['clicks']			= $row['clicks'];
			$tags[$row['tag_name']]['total_entries']	= $row['total_entries'];
			$tags[$row['tag_name']]['channel_entries']	= $row['channel_entries'];
			$tags[$row['tag_name']]['weblog_entries']	= $row['channel_entries'];

			// -------------------------------------
			//	tag group total entries?
			// -------------------------------------

			$tag_groups = $this->model('Data')->get_tag_groups();

			foreach ($tag_groups as $id => $short_name)
			{
				$tags[$row['tag_name']]['total_entries_' . $id]			= $row['total_entries_' . $id];
				$tags[$row['tag_name']]['total_entries_' . $short_name]	= $row['total_entries_' . $id];
			}

			$tags[$row['tag_name']]['channel_id']	= (
				isset( $row['channel_id'] ) === true
			) ? $row['channel_id']: '';

			$tags[$row['tag_name']]['channel_url']	= (
				isset( $row['channel_url'] ) === true
			) ? rtrim( $row['channel_url'], "\/" )."/": '';

			$tags[$row['tag_name']]['comment_url']		= (
				isset( $row['channel_url']) === true
			) ?	rtrim( $row['comment_url'], "\/" ) ."/" : '';

			$tags[$row['tag_name']]['size']				= ceil( (($rank_by == 'clicks') ?
															$row['clicks'] :
															$row['count']) / ( $max / $groups ) );

			$tags[$row['tag_name']]['step']				= $tags[$row['tag_name']]['size'] * $step + $start;
		}

		if ( $ord == 'count' )
		{
			ksort( $tags );
		}

		//	----------------------------------------
		//	Parse
		//	----------------------------------------

		$r			= '';
		$position	= 0;

		$qs	= (ee()->config->item('force_query_string') == 'y') ? '' : '?';

		$total_results = count($tags);

		foreach ( $tags as $key => $row )
		{
			$tagdata	= ee()->TMPL->tagdata;

			$row['total_results'] = $total_results;

			$position++;

			//	----------------------------------------
			//	Conditionals
			//	----------------------------------------

			$cond					= $row;
			$cond['position']		= $position;
			$cond['tag_name']		= $key;
			$cond['websafe_tag']	= str_replace( " ", $websafe_separator, $key );
			$tagdata				= ee()->functions->prep_conditionals( $tagdata, $cond );

			//	----------------------------------------
			//	Parse Switch
			//	----------------------------------------

			if ( preg_match( "/".LD."(switch\s*=.+?)".RD."/is", $tagdata, $match ) > 0 )
			{
				$sparam = ee()->functions->assign_parameters($match['1']);

				$sw = '';

				if ( isset( $sparam['switch'] ) !== false )
				{
					$sopt = explode("|", $sparam['switch']);

					$sw = $sopt[($position + count($sopt)) % count($sopt)];
				}

				$tagdata = ee()->TMPL->swap_var_single($match['1'], $sw, $tagdata);
			}

			//	----------------------------------------
			//	Parse singles
			//	----------------------------------------

			$tagdata = str_replace( LD.'tag'.RD, $key, $tagdata );
			$tagdata = str_replace( LD.'tag_name'.RD, $key, $tagdata );
			$tagdata = str_replace( LD.'tag_id'.RD, $row['tag_id'], $tagdata );
			$tagdata = str_replace( LD.'websafe_tag'.RD, str_replace( " ", $websafe_separator, $key ), $tagdata );
			$tagdata = str_replace( LD.'count'.RD, $row['count'], $tagdata );
			$tagdata = str_replace( LD.'clicks'.RD, $row['clicks'], $tagdata );
			$tagdata = str_replace( LD.'total_entries'.RD, $row['total_entries'], $tagdata );

			// -------------------------------------
			//	tag group total entries?
			// -------------------------------------

			$tag_groups = $this->model('Data')->get_tag_groups();

			foreach ($tag_groups as $id => $short_name)
			{
				$tagdata = str_replace(
					 LD.'total_entries_' . $id.RD,
					 $row['total_entries_' . $id],
					 $tagdata
				);

				$tagdata = str_replace(
					LD.'total_entries_' . $short_name.RD,
					$row['total_entries_' . $id],
					$tagdata
				);
			}


			$tagdata = str_replace( LD.'channel_entries'.RD, $row['channel_entries'], $tagdata );
			$tagdata = str_replace( LD.'size'.RD, $row['size'], $tagdata );
			$tagdata = str_replace( LD.'step'.RD, $row['step'], $tagdata );
			$tagdata = str_replace( LD.'position'.RD, $position, $tagdata );
			$tagdata = str_replace( LD.'channel'.'_id'.RD, $row['channel_id'], $tagdata );
			$tagdata = str_replace( LD.'channel_id'.RD, $row['channel_id'], $tagdata );
			$tagdata = str_replace( LD.'channel_url'.RD, $row['channel_url'], $tagdata );
			$tagdata = str_replace( LD.'comment_url'.RD, $row['comment_url'], $tagdata );
			$tagdata = str_replace( LD.'total_results'.RD, $row['total_results'], $tagdata );

			//	----------------------------------------
			//	Concat
			//	----------------------------------------

			$r	.= $tagdata;
		}

		//	----------------------------------------
		//	Backspace
		//	----------------------------------------

		$backspace			= ( ctype_digit( ee()->TMPL->fetch_param('backspace') ) === true ) ? ee()->TMPL->fetch_param('backspace'): 0;

		$this->return_data	= ( $backspace > 0 ) ? substr( $r, 0, - $backspace ): $r;

		// --------------------------------------------
		//  Pagination?
		// --------------------------------------------

		//legacy support for non prefix
		if ($prefix)
		{
			$this->return_data = $this->parse_pagination(array(
				'prefix' 	=> 'tag',
				'tagdata' 	=> $this->return_data
			));
		}
		else
		{
			$this->return_data = $this->parse_pagination(array(
				'tagdata' 	=> $this->return_data
			));
		}



		return $this->return_data;
	}
	//	END cloud


	// --------------------------------------------------------------------

	/**
	 * Parse
	 *
	 * Parses a found string of tags into tags and inserts them
	 *
	 * @access	public
	 * @return	boolean			success
	 */

	public function parse()
	{
		if ( $this->entry_id == '' ) return false;

		$str				= '';

		$arr				= array();
		$data				= array();
		$existing_entries	= array();

		//--------------------------------------------
		//	separator override?
		//--------------------------------------------

		//incomming tag_sperator_override
		if (ee()->input->post('tag_separator_override') AND
			array_key_exists(
				ee()->input->post('tag_separator_override'),
				$this->model('Data')->delimiters
			))
		{
			$this->separator_override = ee()->input->post('tag_separator_override');
		}

		// -------------------------------------
		//	set the tag_group_id to $this->tag_group_id
		// -------------------------------------

		$this->get_tag_group_id();

		//	----------------------------------------
		//	Clean the str
		//	----------------------------------------

		$this->str	= $this->lib('Utils')->clean_str($this->str);

		//	----------------------------------------
		//	Delete tag entries
		//	----------------------------------------
		// 	When submitting locally, we overwrite
		// 	the existing tags for this entry with
		// 	the new ones submitted, so let's delete the current tags.
		//	----------------------------------------

		if ( $this->remote === false AND
			 $this->batch === false )
		{
			//--------------------------------------------
			//	Temporary note: removing this check ( remote != 'y' )
			// 	for now so that we can delete remotely entered tags
			//	in the CP if we don't like them.
			//--------------------------------------------

			ee()->db->delete(
				'tag_entries',
				array(
					'type'			=> $this->type,
					'entry_id'		=> $this->entry_id,
					'tag_group_id'	=> $this->tag_group_id,
				)
			);
		}

		//	----------------------------------------
		// 	In local mode, if we have no tags.
		//	Clean orphans and get out.
		//	----------------------------------------

		if ( $this->str == '' AND $this->remote === false )
		{
			$this->clean_dead_tags();

			return true;
		}

		// -------------------------------------
		//	site id
		// -------------------------------------

		if (empty($this->site_id))
		{
			$this->site_id = ee()->config->item('site_id');
		}

		//	----------------------------------------
		//	Grab tag entries for this entry
		//	----------------------------------------

		$tag_ids	= array();

		$query=	ee()->db
					->select('tag_id, remote')
					->where(array(
						'type'			=> $this->type,
						'entry_id'		=> $this->entry_id,
						'tag_group_id'	=> $this->tag_group_id,
					))
					->get('tag_entries');

		if ( $query->num_rows() > 0 )
		{
			foreach ( $query->result_array() as $row )
			{
				$existing_entries[$row['tag_id']]	= $row['remote'];
				$tag_ids[]							= $row['tag_id'];
			}
		}

		//	----------------------------------------
		//	Get Channel Id
		//	----------------------------------------

		if ( $this->channel_id == '' )
		{
			$query = ee()->db
						->select('channel_id, site_id')
						->where(array(
							'site_id'	=> $this->site_id,
							'entry_id'	=> $this->entry_id
						))
						->get('channel_titles');

			if ( $query->num_rows() > 0 )
			{
				$this->channel_id	= $query->row('channel_id');
				//this makes no sense
				//removing
				//$this->site_id		= $query->row('site_id');
			}
		}

		//	----------------------------------------
		//	Update existing tags
		//	----------------------------------------
		// 	We want tags that match the submitted set.
		//	We will update their edit dates.
		//	----------------------------------------

		$str_array = $this->str_arr();

		//@deprecated
		//This should already have been done by _clean_str
		if ($this->model('Data')->preference('convert_case') != 'n')
		{
			array_walk(
				$str_array,
				function($value)
				{
					return strtolower($value);
				}
			);
		}

		$str = implode("','", ee()->db->escape_str($str_array));

		$sql	= "SELECT 	t.tag_id, t.tag_name
				   FROM 	exp_tag_tags AS t
				   WHERE 	t.site_id = '".ee()->db->escape_str($this->site_id)."'
				   AND 		BINARY t.tag_name
				   IN 		('".$str."')";

		$query	= ee()->db->query( $sql );

		//	----------------------------------------
		//	For each existing tag found in str...
		//	----------------------------------------

		foreach ( $query->result_array() as $row )
		{
			//	----------------------------------------
			//	Record existing tags found in str
			//	----------------------------------------

			$this->existing[$row['tag_id']]	= $row['tag_name'];

			$tag_ids[]	= $row['tag_id'];

			//	----------------------------------------
			//	Update the existing tag edit date
			//	----------------------------------------

			ee()->db->update(
				'exp_tag_tags',
				array('edit_date'	=> ee()->localize->now ),
				array('tag_id'		=> $row['tag_id'] )
			);

			//	----------------------------------------
			//	Prep data for exp_tag_entries insert
			//	----------------------------------------

			$data	= array(
				'tag_id'		=> $row['tag_id'],
				'channel_id'	=> $this->channel_id,
				'site_id'		=> $this->site_id,
				'entry_id'		=> $this->entry_id,
				'author_id'		=> ( $this->author_id == '' ) ?
									ee()->session->userdata['member_id'] :
									$this->author_id,
				'ip_address'	=> ee()->input->ip_address(),
				'remote'		=> ( $this->remote ) ? 'y': 'n',
				'type'			=> $this->type,
				'tag_group_id'	=> $this->tag_group_id
			);

			//	----------------------------------------
			// 	Are we in local mode? Meaning are we NOT
			//	using the tag form to let users submit tags?
			//	----------------------------------------

			if ( $this->remote === false )
			{
				//	----------------------------------------
				//	Claim ownership of a remotely entered tag
				//	----------------------------------------
				// 	We're in the context of tags from our str
				//	that already exist. If we're in
				// 	local mode and this entry already has a
				//	reference to this tag, but the tag was
				// 	previously entered remotely, we'll change
				//	the ownership to the person
				// 	currently editing.
				//	----------------------------------------

				if ( isset( $existing_entries[$row['tag_id']] ) AND
					 $existing_entries[$row['tag_id']] == 'y' )
				{
					ee()->db->update(
						'exp_tag_entries',
						$data,
						array(
							'entry_id' 		=> $this->entry_id,
							'tag_id' 		=> $row['tag_id'],
							'tag_group_id' 	=> $this->tag_group_id
						)
					);
				}

				//	----------------------------------------
				// 	Otherwise, if the entry does not have a
				//	reference to the tag, make it so.
				//	----------------------------------------

				elseif ( isset( $existing_entries[$row['tag_id']] ) === false )
				{
					ee()->db->insert('exp_tag_entries', $data);
				}
			}

			// ----------------------------------------
			// If remote mode and no entry exists
			// ----------------------------------------

			elseif ( isset( $existing_entries[$row['tag_id']] ) === false AND
					 in_array( $row['tag_name'], $this->bad() ) === false )
			{

				ee()->db->insert(
					'exp_tag_entries',
					$data
				);
			}
		}

		//	----------------------------------------
		//	Add new tags
		//	----------------------------------------
		//	1.	We turn the submitted string of tags into an array.
		//	2.	We remove from that array tags that already exist and tags that are not allowed.
		//	3.	Then we remove duplicate tags within the string.
		//	4.	Then we add the tags.
		//	5.	Then we associate those tags with the entry.
		//	6.	Then we clean-up the DB of orphaned tags.
		//	----------------------------------------

		$new	= array_unique(
			array_diff(
				$this->str_arr( true ),
				$this->existing,
				$this->bad()
			)
		);

		foreach ( $new as $n )
		{
			if ($this->model('Data')->preference('allow_tag_creation_publish') != 'y'
				AND REQ == 'CP'
			)
			{
				continue;
			}

			if ( $n != '' )
			{
				$n	= ( $this->model('Data')->preference('convert_case') != 'n' ) ?
						$this->lib('Utils')->strtolower( $n ) : $n;


				ee()->db->insert(
					'exp_tag_tags',
					array(
						'tag_alpha'		=> $this->lib('Utils')->first_character($n),
						'tag_name'		=> $n,
						'entry_date' 	=> ee()->localize->now,
						'site_id'		=> ee()->config->item('site_id'),
						'author_id'		=> ee()->session->userdata['member_id']
					)
				);

				$data	= array(
					'tag_id'		=> ee()->db->insert_id(),
					'site_id'		=> $this->site_id,
					'channel_id'	=> $this->channel_id,
					'entry_id'		=> $this->entry_id,
					'author_id'		=> ( $this->author_id == '' ) ?
										ee()->session->userdata['member_id'] :
										$this->author_id,
					'ip_address'	=> ee()->input->ip_address(),
					'remote'		=> ( $this->remote ) ? 'y': 'n',
					'type'			=> $this->type,
					'tag_group_id'	=> $this->tag_group_id
				);

				$tag_ids[]	= ee()->db->insert_id();

				ee()->db->query( ee()->db->insert_string( 'exp_tag_entries', $data ) );
			}
		}

		//--------------------------------------------
		//	fix field data if applicable
		//--------------------------------------------

		if ($this->from_ft == true AND $this->field_id !== 'default')
		{
			$final_tags 		= $this->model('Data')->get_entry_tags_by_id(
				$this->entry_id,
				array(
					'tag_group_id' => $this->tag_group_id
				)
			);

			$final_tag_names	= array();

			foreach ($final_tags as $row)
			{
				$final_tag_names[] = $row['tag_name'];
			}

			//if we have any, concat with new line
			if ( ! empty($final_tag_names))
			{
				ee()->db->query(
					ee()->db->update_string(
						'exp_channel_data',
						array(
							'field_id_' . $this->field_id 	=> implode("\n", $final_tag_names)
						),
						array(
							'entry_id'						=> $this->entry_id
						)
					)
				);
			}
		}

		//	----------------------------------------
		//	Clean-up dead tags
		//	----------------------------------------

		$this->clean_dead_tags();

		//	----------------------------------------
		//	Recount
		//	----------------------------------------

		$this->lib('Utils')->recount_tags($tag_ids);

		//	----------------------------------------
		//	Return
		//	----------------------------------------


		return true;
	}
	//	END parse


	// --------------------------------------------------------------------

	/**
	 * Delete Tags
	 *
	 * @access	public
	 * @param	mixed	$entry_ids	array or single int/string id
	 * @param	string	$type		what type of item to delete
	 * @return	void
	 */

	public function delete( $entry_ids, $type = 'channel')
	{
		if ( ! is_array($entry_ids) OR count( $entry_ids ) == 0 ) return;

		//	----------------------------------------
		//	Query
		//	----------------------------------------

		$sql = "SELECT DISTINCT entry_id FROM exp_tag_entries
				WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'
				AND type = '".ee()->db->escape_str( $type )."' AND
				entry_id IN ('".implode("','", ee()->db->escape_str( $entry_ids ))."')";

		$query = ee()->db->query($sql);

		//	----------------------------------------
		//	Delete entries
		//	----------------------------------------

		if ( $query->num_rows() == 0 ) return;

		$ids = array();

		foreach( $query->result_array() as $row )
		{
			$ids[] = $row['entry_id'];
		}

		ee()->db->query("DELETE FROM exp_tag_entries WHERE entry_id IN ('".implode("','", ee()->db->escape_str( $ids ))."')");

		//	----------------------------------------
		//	Clean-up dead tags
		//	----------------------------------------

		$this->clean_dead_tags();

		//	----------------------------------------
		//	Return
		//	----------------------------------------

		return;
	}
	//	END delete


	// --------------------------------------------------------------------

	/**
	 * Clean Dead Tags
	 *
	 * @access	protected
	 * @return void
	 */

	protected function clean_dead_tags()
	{
		//	----------------------------------------
		//	Remove tags with no entries
		//	----------------------------------------

		$query = ee()->db
					->select('e.tag_id, COUNT(e.tag_id) AS count')
					->from('exp_tag_tags t')
					->join('exp_tag_entries e', 'e.tag_id = t.tag_id', 'left')
					->group_by('e.tag_id', 'DESC')
					->get();

		foreach ( $query->result_array() as $row )
		{
			if ( $row['count'] == '0' )
			{
				ee()->db->where('tag_id', $row['tag_id'])->delete('tag_tags');
			}
		}
	}
	//	END clean up


	// --------------------------------------------------------------------

	/**
	 * Count Tags to update clicks
	 *
	 * @access	protected
	 * @param	integer	$page	current... page?
	 * @return	boolean			success
	 */

	protected function count_tag ( $page = 1 )
	{
		if ( $this->tag == '' OR $page > 1 ) return false;

		//	----------------------------------------
		//	Get array of tags
		//	----------------------------------------

		$tags	= explode( "|", ee()->db->escape_str( $this->tag ) );

		//	----------------------------------------
		//	Get tags
		//	----------------------------------------

		$sql	= "UPDATE exp_tag_tags SET clicks = (clicks + 1) WHERE";

		if ($this->model('Data')->preference('convert_case') != 'n')
		{
			array_walk($tags, create_function('$value', 'return strtolower($value);'));
		}

		$sql	.= " BINARY tag_name IN ('".implode( "','", ee()->db->escape_str($tags) )."')";

		$query	= ee()->db->query( $sql );

		return true;
	}
	//	END count tag


	// --------------------------------------------------------------------

	/**
	 * Exclude Tag
	 *
	 * @access	protected
	 * @param	string	$str	incoming string to remove of ids
	 * @return	mixed			array of ids with items removed
	 */

	protected function exclude( $str = '' )
	{
		//	----------------------------------------
		//	Parse string
		//	----------------------------------------

		if ( $str == '' ) return false;

		$ids	= array();
		$like	= array();
		$excludes	= preg_split( "/,|\|/", $str );

		// --------------------------------------------
		//	Begin query
		// --------------------------------------------

		$sql = "SELECT tag_id FROM exp_tag_tags
				WHERE site_id IN ('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."') ";

		// --------------------------------------------
		//	Check for token so we know what kind of
		// search to do. % = token
		// --------------------------------------------

		foreach ($excludes as $key => $value)
		{
			if ( strpos( $value, '%' ) !== false )
			{
				$like[] = "tag_name LIKE '".ee()->db->escape_str( $value )."'";
				unset($excludes[$key]);
			}
		}

		// --------------------------------------------
		//	Check for plain Jane tags
		// --------------------------------------------

		if ( count($excludes) > 0 )
		{
			$like[] = "tag_name IN ('".implode( "','", ee()->db->escape_str( $excludes ) )."')";
		}

		// --------------------------------------------
		//	Tack on LIKE searches
		// --------------------------------------------

		if ( count($like) > 0 )
		{
			$sql .= "AND (".implode(' OR ', $like).")";
		}

		// --------------------------------------------
		//	Run the query
		// --------------------------------------------

		$query = ee()->db->query($sql);

		foreach ( $query->result_array() as $row )
		{
			$ids[]	= $row['tag_id'];
		}

		return ( count($ids) > 0 ) ? $ids : false;
	}
	//	END exclude


	// --------------------------------------------------------------------

	/**
	 * Get Bad Tags
	 *
	 * @access	protected
	 * @return	array		array of bad tags
	 */

	protected function bad()
	{
		//	----------------------------------------
		//	Have we already done this?
		//	----------------------------------------

		if ( $this->bad !== false )
		{
			return $this->bad;
		}

		$this->bad = array();

		//	----------------------------------------
		//	Do it
		//	----------------------------------------

		$sql	= "SELECT tag_name FROM exp_tag_bad_tags";

		if ( isset( $TMPL ) )
		{
			$sql	.= " WHERE site_id IN ('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')";
		}
		else
		{
			$sql	.= " WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";
		}

		$query	= ee()->db->query( $sql );

		//	----------------------------------------
		//	Adding an empty tag prevents the module from checking the database for every
		//	single tag in the tag cloud when there are no bad tags registered for the site.
		//	----------------------------------------

		foreach ( $query->result_array() as $row )
		{
			$this->bad[] = $row['tag_name'];
		}

		return $this->bad;
	}
	//	END get bad tags


	// --------------------------------------------------------------------

	/**
	 * String to array
	 *
	 * @access	protected
	 * @param	boolean	$remove_slashes		remove slashes from string
	 * @return	array						string split on separator
	 */

	protected function str_arr($remove_slashes = false)
	{
		return $this->lib('Utils')->str_arr(
			$this->str,
			$this->separator_override,
			$remove_slashes
		);;
	}
	//	END string to array


	// --------------------------------------------------------------------

	/**
	 * Entry ID
	 *
	 * @access	protected
	 * @param	string	$type	type of id
	 * @return	boolean			entry_id found and $this->entry_id set
	 */

	protected function entry_id( $type = 'channel' )
	{
		ee()->load->helper('string');

		//	----------------------------------------
		//	Prep type
		//	----------------------------------------

		$types = array( 'channel'	=> 'exp_channel_titles');

		$type = (isset($types[$type])) ? $types[$type] : 'exp_channel_titles';

		//	----------------------------------------
		//	Cat segment
		//	----------------------------------------

		$cat_segment	= ee()->config->item("reserved_category_word");

		//	----------------------------------------
		//	Begin matching
		//	----------------------------------------

		$psql	= "SELECT entry_id FROM `".$type."` WHERE entry_id = '%eid'";

		if ( ctype_digit( ee()->TMPL->fetch_param('entry_id') ) === true )
		{
			$sql	= str_replace( "%eid", ee()->db->escape_str( ee()->TMPL->fetch_param('entry_id') ), $psql );

			$query	= ee()->db->query( $sql );

			if ( $query->num_rows() > 0 )
			{
				$this->entry_id	= $query->row('entry_id');

				return true;
			}
		}
		elseif (ee()->TMPL->fetch_param('url_title') != "")
		{
			$query = ee()->db
						->select('entry_id')
						->where('url_title', ee()->TMPL->fetch_param('url_title'))
						->get($type);

			if ( $query->num_rows() > 0 )
			{
				$this->entry_id	= $query->row('entry_id');

				return true;
			}
		}
		elseif ( ee()->uri->query_string != '' OR ( isset( ee()->uri->page_query_string ) === true AND ee()->uri->page_query_string != '' ) )
		{
			$qstring = ( ee()->uri->page_query_string != '' ) ? ee()->uri->page_query_string : ee()->uri->query_string;

			//	----------------------------------------
			//	Do we have a pure ID number?
			//	----------------------------------------

			if ( ctype_digit( $qstring ) === true )
			{
				$sql	= str_replace( "%eid", ee()->db->escape_str( $qstring ), $psql );

				$query	= ee()->db->query( $sql );

				if ( $query->num_rows() > 0 )
				{
					$this->entry_id	= $query->row('entry_id');

					return true;
				}
			}
			else
			{
				//	----------------------------------------
				//	Parse day
				//	----------------------------------------

				if (preg_match("#\d{4}/\d{2}/(\d{2})#", $qstring, $match))
				{
					$partial	= substr($match['0'], 0, -3);

					$qstring	= trim_slashes(str_replace($match['0'], $partial, $qstring));
				}

				//	----------------------------------------
				//	Parse /year/month/
				//	----------------------------------------

				if (preg_match("#(\d{4}/\d{2})#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match['1'], '', $qstring));
				}

				//	----------------------------------------
				//	Parse page number
				//	----------------------------------------

				if (preg_match("#^P(\d+)|/P(\d+)#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match['0'], '', $qstring));
				}

				//	----------------------------------------
				//	Parse category indicator
				//	----------------------------------------

				// Text version of the category

				if (preg_match("#^".$cat_segment."/#", $qstring, $match) AND ee()->TMPL->fetch_param('channel'))
				{
					$qstring	= str_replace($cat_segment.'/', '', $qstring);

					$sql		= "SELECT DISTINCT cat_group FROM exp_channels
								   WHERE site_id IN ('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."') ";

					$sql	.= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('channel'), 'channel_name');

					$query	= ee()->db->query($sql);

					if ($query->num_rows() == 1)
					{
						$result	= ee()->db->query("SELECT cat_id
											  FROM exp_categories
											  WHERE site_id IN ('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
											  AND cat_name='".ee()->db->escape_str($qstring)."' AND group_id='".ee()->db->escape_str($query->row('cat_group'))."'");

						if ($result->num_rows() == 1)
						{
							$qstring	= 'C'.$result->row('cat_id');
						}
					}
				}

				//	----------------------------------------
				//	Numeric version of the category
				//	----------------------------------------

				if (preg_match("#^C(\d+)#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match['0'], '', $qstring));
				}

				//	----------------------------------------
				//	Remove "N"
				//	----------------------------------------

				// The recent comments feature uses "N" as the URL indicator
				// It needs to be removed if presenst

				if (preg_match("#^N(\d+)|/N(\d+)#", $qstring, $match))
				{
					$qstring	= trim_slashes(str_replace($match['0'], '', $qstring));
				}

				//	----------------------------------------
				//	Parse URL title
				//	----------------------------------------

				if (strstr($qstring, '/'))
				{
					$xe			= explode('/', $qstring);
					$qstring	= current($xe);
				}

				$sql	= "SELECT wt.entry_id
							FROM exp_channel_titles AS wt, exp_channels AS w
							WHERE wt.channel_id = w.channel_id
							AND wt.site_id IN ('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
							AND wt.url_title = '".ee()->db->escape_str($qstring)."'";


				$query	= ee()->db->query($sql);

				if ( $query->num_rows() > 0 )
				{
					$this->entry_id = $query->row('entry_id');

					return true;
				}

				// --------------------------------------------
				//  Entry ID Only?
				// --------------------------------------------

				if ( ctype_digit($qstring))
				{
					$this->entry_id = $qstring;
					return true;
				}
			}
		}

		return false;
	}
	//END entry id


	// --------------------------------------------------------------------

	/**
	 * Tag Stats
	 *
	 * @access	public
	 * @return	{string}		tagdata output for stats
	 */

	public function stats()
	{
		$t_entries = 0;
		$p_entries = 0;
		$gt_entries = 0;
		$pg_entries = 0;
		$ranked = array();

		$this->return_data = ee()->TMPL->tagdata;

		//	----------------------------------------
		//	Query
		//	----------------------------------------

		$tags = ee()->db->query(
			"SELECT COUNT(*) AS count
			 FROM 	exp_tag_tags
			 WHERE 	site_id
			 IN 	('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')"
		);

		if (stristr ( ee()->TMPL->tagdata, 'channel'.'_entries_tagged'.RD ) !== false)
		{
			$t_entries	= ee()->db->query(
				"SELECT 	COUNT(DISTINCT tag_id) AS count
				 FROM 		exp_tag_entries
				 WHERE 		type = 'channel'
				 AND 		site_id
				 IN 		('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
				 GROUP BY 	entry_id"
			);

			$t_entries	= ( $t_entries->num_rows() > 0 ) ? $t_entries->num_rows(): 0;

			$entries	= ee()->db->query(
				"SELECT COUNT(*) AS count
				 FROM 	exp_channel_titles
				 WHERE 	site_id
				 IN 	('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')"
			);

			$p_entries	= ( $entries->row('count') != 0 ) ? round( $t_entries / $entries->row('count') * 100, 2): 0;
		}

		//	----------------------------------------
		//	Check gallery?
		//	----------------------------------------

		$gt_entries	= 0;
		$pg_entries	= 0;

		if (preg_match_all("/".preg_quote(LD)."top_([0-9]+)_tags".preg_quote(RD)."/", ee()->TMPL->tagdata, $matches) !== false)
		{
			foreach($matches[1] as $number)
			{
				$top5 = ee()->db->query(
					"SELECT t.tag_name
					 FROM 	exp_tag_tags t
					 WHERE 	site_id
					 IN 	('".implode("','", ee()->db->escape_str(ee()->TMPL->site_ids))."')
					 ORDER 	BY t.total_entries DESC LIMIT ".ceil($number)
				);

				$ranked = array();

				foreach ( $top5->result_array() as $row )
				{
					$ranked[] = $row['tag_name'];
				}

				$this->return_data = str_replace(LD.'top_'.ceil($number).'_tags'.RD, implode(', ', $ranked), $this->return_data);
			}
		}

		//	----------------------------------------
		//	Data
		//	----------------------------------------

		$data = array(
			LD.'total_tags'.RD						=> $tags->row('count'),
			LD.'total_channel_entries_tagged'.RD	=> $t_entries,
			LD.'percent_channel_entries_tagged'.RD	=> $p_entries,
			LD.'total_weblog_entries_tagged'.RD		=> $t_entries,
			LD.'percent_weblog_entries_tagged'.RD	=> $p_entries,
		);

		return str_replace(array_keys($data), array_values($data), $this->return_data);
	}
	//	END stats



	// --------------------------------------------------------------------

	/**
	 *	tag js for the front end (should be 2.x only)
	 *
	 *	@access		public
	 *	@return		string	tag js for the front end
	 */

	public function field_js()
	{
		if ( isset( ee()->sessions->cache['solspace']['scripts']['tag']['field'] ) )
		{
			return '';
		}

		ee()->sessions->cache['solspace']['scripts']['tag']['field']	= true;

		return $this->model('Data')->tag_field_js();
	}
	//END field_js


	// --------------------------------------------------------------------

	/**
	 *	tag autocomplete js for the front end (should be 2.x only)
	 *
	 *	@access		public
	 *	@return		string	tag auto completejs for the front end
	 */

	public function field_autocomplete_js()
	{
		if ( isset( ee()->sessions->cache['solspace']['scripts']['jquery']['tag_autocomplete'] ) )
		{
			return '';
		}

		ee()->sessions->cache['solspace']['scripts']['jquery']['tag_autocomplete']	= true;

		return $this->model('Data')->tag_field_autocomplete_js();
	}
	//END field_js


	// --------------------------------------------------------------------

	/**
	 *	tag css for the front end (should be 2.x only)
	 *
	 *	@access		public
	 *	@return		string	tag css for the front end
	 */

	public function field_css()
	{
		if ( isset( ee()->sessions->cache['solspace']['css']['tag']['field'] ) )
		{
			return '';
		}

		ee()->sessions->cache['solspace']['css']['tag']['field']	= true;

		return $this->model('Data')->tag_field_css() .
				((REQ == 'PAGE') ? "\n" . $this->model('Data')->tag_front_css() : '');
	}
	//END field_css


	// --------------------------------------------------------------------

	/**
	 *	parses and returns form widget
	 *
	 *	@access		public
	 * 	@param 		array 	data for item inputs from the field type or elsewhere
	 *	@return		string
	 */

	public function field_type_widget($data)
	{
		//--------------------------------------------
		//	default data
		//--------------------------------------------

		$defaults = array(
			'entry_id'			=> ee()->input->get_post('entry_id'),
			'channel_id'		=> ee()->input->get_post('channel_id'),
			'field_data' 		=> '',
			'field_name'		=> 'default',
			'field_id'			=> 'solspace_tag_entry',
			'tag_group_id'		=> 1,
			'all_open'			=> 'no',
			'suggest_from'		=> 'group',
			'top_tag_limit'		=> 5,
			'suggest_fields' 	=> '',
			'input_only'		=> false,
			'explode_separator'	=> $this->check_yes($this->model('Data')->preference(
				'explode_input_on_separator'
			)),
			'tag_separator'		=> "\n",
			'tag_separator_name'=> "newline",
			'enable_explode_controls' => $this->check_yes($this->model('Data')->preference(
				'enable_explode_controls'
			))
		);

		if (array_key_exists(
				$this->model('Data')->preference('separator'),
				$this->model('Data')->delimiters
			))
		{
			$defaults['tag_separator'] = $this->model('Data')->delimiters[
				$this->model('Data')->preference('separator')
			];
			$defaults['tag_separator_name'] = $this->model('Data')->preference('separator');
		}

		$data = array_merge($defaults, $data);

		//no shenanigans
		unset($defaults);

		//--------------------------------------------
		//	MORE default data.. :/
		//--------------------------------------------

		$this->cached_vars['entry_id'] 		= $entry_id 	= (
			is_numeric($data['entry_id']) AND $data['entry_id'] > 0
		) ? $data['entry_id'] : 0;

		$this->cached_vars['channel_id'] 	= $channel_id	= (
			is_numeric($data['channel_id']) AND $data['channel_id'] > 0
		) ? $data['channel_id']	: 0;

		$this->cached_vars['field_id'] 		= $field_id = $data['field_id'];
		//removed this check because we need to allow multiple field ids
		//in situations where they need to be definable
		/*		= (
			is_numeric($data['field_id']) AND $data['field_id'] > 0
		) ? $data['field_id'] : 'solspace_tag_entry';*/

		$this->cached_vars['field_name'] 	= $field_name	= (
			trim($data['field_name']) !== ''
		) ? trim($data['field_name']) : 'default';

		$autosave		= (ee()->input->get_post('use_autosave') === 'y');

		$this->cached_vars['suggest_fields'] = preg_split(
			'/' . preg_quote('|', '/') . '/',
			$data['suggest_fields'],
			-1,
			PREG_SPLIT_NO_EMPTY
		);

		//--------------------------------------------
		//	current tags and autosave stuff
		//--------------------------------------------

		$current_tag_names 	= trim($data['field_data']);
		$tags				= array();

		//unless this is autosave, or new, we need to get data
		//from the tag_entries table because its the most correct
		//we strive to always be congruent, but this is just
		//another failsafe
		if ( ! $data['input_only'] AND
			$entry_id > 0 AND
			! $autosave)
		{
			$tags = $this->model('Data')->get_entry_tags_by_id(
				$entry_id,
				array(
					'tag_group_id'		=> $data['tag_group_id'],
					'entry_type'		=> 'channel',
				)
			);

			//no reset unless we get results
			if ( ! empty($tags))
			{
				$tag_names = array();

				foreach ($tags as $tag)
				{
					$tag_names[] = $tag['tag_name'];
				}

				$current_tag_names = implode("\n", $tag_names);
			}
		}

		//should not get here very often. mostly with autosave
		if ( ! $data['input_only'] AND
			empty($tags) AND
			$entry_id > 0 AND
			$current_tag_names != ''
		 )
		{
			$tags = $this->model('Data')->get_entry_tags_by_tag_name(
				explode("\n", $current_tag_names)
			);
		}

		//if we have no tags after this, lets remove any erroneous data
		//again, this should not happen, but another failsafe
		if ($data['input_only'] OR empty($tags))
		{
			$current_tag_names = '';
		}

		$this->cached_vars['hidden_tag_data'] 		= $current_tag_names;

		$this->cached_vars['current_tags'] 			= array();
		$this->cached_vars['current_tags_escaped'] 	= array();

		foreach ($tags as $tag)
		{
			$this->cached_vars['current_tags'][]			= $tag['tag_name'];
			$this->cached_vars['current_tags_escaped'][]	= str_replace("'", '&#039;', $tag['tag_name']);
		}

		// --------------------------------------------
		//  Top 5 Tags
		// --------------------------------------------

		$this->cached_vars['top_tags'] 			= array();
		$this->cached_vars['top_tags_escaped'] 	= array();

		if ( ! $data['input_only'])
		{
			$top_sql = " SELECT 	t.tag_name, t.total_entries
						 FROM 		exp_tag_tags t
						 WHERE 		site_id = " .
							ee()->db->escape_str(ee()->config->item('site_id'));


			$top_orderby = "t.total_entries";

			// -------------------------------------
			//	tag groups?
			// -------------------------------------

			if ($data['suggest_from'] === 'group')
			{
				//need to change the top #'s to the group count
				$tgid_clean = ee()->db->escape_str($data['tag_group_id']);
				$top_orderby = "t.total_entries_" . $tgid_clean;

				$top_sql = str_replace(
					't.total_entries',
					$top_orderby . ' as total_entries',
					$top_sql
				);

				$top_sql .= " AND tag_id IN (
								SELECT DISTINCT tag_id
								FROM	exp_tag_entries
								WHERE	tag_group_id = " .
									$tgid_clean . ")";
			}


			$top_sql .= " ORDER BY 	{$top_orderby} DESC
						 LIMIT 		" . ee()->db->escape_str($data['top_tag_limit']);

			$top	= ee()->db->query($top_sql);

			foreach ( $top->result_array() as $row )
			{
				$this->cached_vars['top_tags'][$row['tag_name']] 	= $row['total_entries'];
				$this->cached_vars['top_tags_escaped'][]			= str_replace("'", '&#039;', $row['tag_name']);
			}
		}

		//--------------------------------------
		//  lang
		//--------------------------------------

		$lvars = array(
			'suggest_tags',
			'top_tags',
			'current_tags',
			'add_tags',
			'error'
		);

		foreach($lvars as $var)
		{
			//replacing all spaces with non-breaking just to prevent BS
			$this->cached_vars['lang_' . $var] = str_replace(' ', NBS, lang($var));
		}

		$this->cached_vars['lang_tag_limit_reached'] = lang('tag_limit_reached');

		$lang_input_separator_note = str_replace(
			'%sep%',
			strtolower(lang('separator_' . $data['tag_separator_name'])),
			lang('explode_input_on_separator_note')
		);

		// -------------------------------------
		//	delimiter lang
		// -------------------------------------

		$delimiter_lang = array();

		foreach ($this->model('Data')->delimiters as $key => $value)
		{
			$delimiter_lang[$key] = lang('separator_' . $key);
		}

		// -------------------------------------
		//	view vars
		// -------------------------------------

		$this->cached_vars = array_merge($this->cached_vars, array(
			//prefs
			'all_open'					=> $data['all_open'],
			'input_only'				=> $data['input_only'],
			'tag_limit'					=> $this->model('Data')->preference(
				'publish_entry_tag_limit'
			),
			'explode_separator'			=> $data['explode_separator'],
			'enable_explode_controls'	=> $data['enable_explode_controls'],
			'tag_separator'				=> $data['tag_separator'],
			'tag_separator_name'		=> $data['tag_separator_name'],

			'delimiter_lang'			=> $delimiter_lang,

			//delimiters for optional splitting
			'delimiter_json'			=> json_encode(
				$this->model('Data')->delimiters
			),

			//name... so... long.. :(
			'lang_explode_input_on_separator_note' =>
				$lang_input_separator_note,

			//tab name for publish tabs
			'tab_name'					=> $this->either_or(
				$this->model('Data')->preference($data['channel_id'].'_publish_tab_label'),
				''
			),
		));


		//--------------------------------------------
		//	urls
		//--------------------------------------------

		$act_base = $this->get_action_url('ajax');

		$this->cached_vars['suggest_tags_url'] 	= $act_base .
													'&method=tag_suggest' .
													'&tag_separator=doublepipe';

		if ($data['suggest_from'] === 'group')
		{
			$this->cached_vars['suggest_tags_url'] .= '&tag_group_id=' .
														$data['tag_group_id'];
		}

		$suggest_from = $data['suggest_from'] === 'group' ?
							'&tag_group_id=' . $data['tag_group_id'] :
							'';

		$this->cached_vars['autocomplete_url'] 	= $act_base . '&method=tag_autocomplete&tag_separator=doublepipe' . $suggest_from;

		//--------------------------------------------
		//	parse tags
		//--------------------------------------------

        if (REQ === "CP") {
            $this->cached_vars['suggest_tags_url'] = ee('CP/URL', 'addons/settings/tag/tag_suggest');
            $this->cached_vars['autocomplete_url'] = ee('CP/URL', 'addons/settings/tag/tag_autocomplete');
        }

		return $this->view('field_type', NULL, true);
	}
	//	END field_type_widget


	// --------------------------------------------------------------------

	/**
	 *	Ajax
	 *	Mixed methods for requesting items via ajax
	 *
	 *	@access		public
	 *	@return		mixed
	 */

	public function ajax()
	{
		$method = ee()->input->get_post('method');

		if ($method == 'tag_autocomplete')
		{
			//exits with headers
			return $this->lib('Utils')->tag_autocomplete(array('tag_name'));
		}

		if ($method == 'tag_suggest')
		{
			//does a system exit
			return $this->lib('Utils')->tag_suggest(true);
		}
	}
	//ENd ajax


	// --------------------------------------------------------------------

	/**
	 *	get_tag_group_id
	 *	gets the tag group id from a number of places and sets it to the
	 *	instance default param
	 *
	 *	@access		protected
	 *	@return		int
	 */

	protected function get_tag_group_id()
	{
		$tag_group_id = false;

		//preference for params and ids over names and get_post
		if (isset(ee()->TMPL) AND
			is_object(ee()->TMPL) AND
			ee()->TMPL->fetch_param('tag_group_id'))
		{
			$tag_group_id = ee()->TMPL->fetch_param('tag_group_id');
		}
		else if (isset(ee()->TMPL) AND
				is_object(ee()->TMPL) AND
				ee()->TMPL->fetch_param('tag_group_name'))
		{
			$tag_group_id = $this->model('Data')->get_tag_group_id_by_name(
				ee()->TMPL->fetch_param('tag_group_name')
			);
		}
		else if (ee()->input->get_post('tag_group_id'))
		{
			$tag_group_id = ee()->input->get_post('tag_group_id');
		}
		else if (ee()->input->get_post('tag_group_name'))
		{
			$tag_group_id = $this->model('Data')->get_tag_group_id_by_name(
				ee()->input->get_post('tag_group_name')
			);
		}

		//is it legit, dawg?
		if ( $tag_group_id !== false AND
			(is_numeric($tag_group_id) AND $tag_group_id > 1) OR
			is_numeric(str_replace(array('|', '&'), '', $tag_group_id))
		)
		{
			$this->tag_group_id = $tag_group_id;
		}

		//returns default if nothing nice and new
		return $this->tag_group_id;
	}
	//END get_tag_group_id


	// --------------------------------------------------------------------

	/**
	 *	Outputs Tag Separator Label for Current Site
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function separator()
	{
		return lang($this->model('Data')->preference('separator'));
	}
	// END separator()
}
// END CLASS Tag
