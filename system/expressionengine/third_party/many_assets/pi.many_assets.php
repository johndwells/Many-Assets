<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Many Assets Plugin
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Plugin
 * @author		John D Wells
 * @link		http://johndwells.com
 */

$plugin_info = array(
	'pi_name'		=> 'Many Assets',
	'pi_version'	=> '1.2.2',
	'pi_author'		=> 'John D Wells',
	'pi_author_url'	=> 'http://johndwells.com',
	'pi_description'=> 'Retrieve P&T Assets from across many Entries, and/or across many custom fields.',
	'pi_usage'		=> Many_assets::usage()
);

class Many_assets {

	public $return_data;

	/**
	 * Holds any query results
	 */
    protected $_result = array();
    
    /**
     * Cache key
     */
    protected $_ckey;



	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Obviously
		$this->EE =& get_instance();


		// -------------------------------------------
		// Fetch/call/cache our query
		// -------------------------------------------

		$this->_ckey = md5($this->EE->TMPL->tagproper);
		if ( ! ($this->_result = $this->EE->session->cache('many_assets', $this->_ckey)))
		{

	    	// -------------------------------------------
	    	// fetch required param(s) - return immediately if any are not provided
			// -------------------------------------------

			if (($entry_ids	= $this->EE->TMPL->fetch_param('entry_ids', FALSE)) == FALSE) return;
	
	
	
	    	// -------------------------------------------
	    	// fetch optional params
			// -------------------------------------------

	    	$include		= $this->EE->TMPL->fetch_param('include', FALSE);
	    	$exclude		= $this->EE->TMPL->fetch_param('exclude', FALSE);
	    	$orderby		= $this->EE->TMPL->fetch_param('orderby', 'asset_order');
	    	$sort			= $this->EE->TMPL->fetch_param('sort', 'asc');
	    	$limit			= $this->EE->TMPL->fetch_param('limit', 0);
	    	$offset			= $this->EE->TMPL->fetch_param('offset', 0);



			// -------------------------------------------
			// Format/standardise params
			// -------------------------------------------

			// $entry_ids may be pipe or string delimited
			$entry_ids = $this->_delimitered($entry_ids);

			// $orderby & $sort may be pipe or string delimited
			$orderby = $this->_delimitered(strtolower($orderby));
			$sort = $this->_delimitered(strtolower($sort));

			// $limit & $offset should be integers			
	    	$limit = intval($limit);
	    	$offset = intval($offset);
	
	
	
			// -------------------------------------------
			// Assemble our query
			// -------------------------------------------

			$sql = 'SELECT DISTINCT a.asset_id, a.*
				FROM exp_assets a
				JOIN exp_assets_entries ae ON a.asset_id = ae.asset_id
				WHERE ae.entry_id IN(' . $entry_ids . ')';
			
			// limit to certain fields?
			if($sql_include = $this->_include($include))
			{
				$sql .= ' AND (' . implode(' OR ', $sql_include) . ')';
			}

			// exclude certain fields?
			if($sql_exclude = $this->_exclude($exclude))
			{
				// beware of NULL... http://forums.mysql.com/read.php?10,275066,275197#msg-275197 
				$sql .= ' AND (ae.col_id IS NULL OR ' . implode(' AND ', $sql_exclude) . ')';
			}

			// format sort order based on $orderby & $sort
			$sql .= $this->_sql_sort_order($orderby, $sort);
			
			if($limit > 0)
			{
				$sql .= ' LIMIT ' . $limit;
			}
			
			if($offset > 0)
			{
				$sql .= ' OFFSET ' . $limit;
			}
			


			// -------------------------------------------
			// Run our query, and save to cache
			// -------------------------------------------

			$query = $this->EE->db->query($sql);
			$this->_result = $query->result_array();
			$query->free_result();
			$this->EE->session->set_cache('many_assets', $this->_ckey, $this->_result);
		}



		// -------------------------------------------
		// process whatever we've found
		// -------------------------------------------

		$files = array();
		if($this->_result)
		{
		
			// Include dependency classes
			if ( ! class_exists('EE_Fieldtype'))
			{
				include_once (APPPATH . 'fieldtypes/EE_Fieldtype' . EXT);
			}

			if ( ! class_exists('Assets_ft'))
			{
				include_once PATH_THIRD . 'assets/ft.assets.php';
			}

			// heavy lifting
			$Assets_ft = new Assets_ft();
			foreach ($this->_result as $row)
			{
				$file = $Assets_ft->helper->get_file($row['file_path']);
	
				if ($file->exists())
				{
					$file->set_row($row);
					
					$files[] = $file;
				}
			}
		}



		// -------------------------------------------
		// What to return?
		// -------------------------------------------

		if(count($files) > 0)
		{
			$this->return_data = $Assets_ft->replace_tag($files, $this->EE->TMPL->tagparams, $this->EE->TMPL->tagdata);
		} else {
			// Nothing - show No Results
			$this->return_data = $this->EE->TMPL->no_results();
		}
		
		// release the hounds!
		return $this->return_data;
    }



	/**
	 * Utility method for cleaning up a delimiter-ed list
	 */
	protected function _delimitered($string)
	{
		$string = trim($string, ',|');
    	if(strpos($string, '|') !== FALSE)
    	{
    		$string = str_replace('|', ',', $string);
    	}
    	
    	return $string;
	}



	/**
	 * Convenience method for Many_assets::include_exclude()
	 */
	protected function _include($include = array())
	{
		return $this->_include_exclude($include, 'i');
	}
	
	
	
	/**
	 * Utility method to format the portion of the sql query that specifies sort order
	 */
	protected function _sql_sort_order($orderby, $sort)
	{
		$sql = ' ORDER BY ';
		
		// random trumps all others
		if(strpos($orderby, 'random') !== FALSE)
		{
			$sql .= ' RAND() ';
		}
		else
		{
			// need same amount of params
			if(($orderby_count = substr_count($orderby, ',')) != ($sort_count = substr_count($sort, ',')))
			{
				for($i = ($orderby_count - $sort_count); $i > 0; $i--)
				{
					$sort .= ',' . 'asc';
				} 
			}

			// combine orderby & sort into a single array
			$sort_order = array_combine(explode(',', $orderby), explode(',', $sort));
			
			// for sanity's sake, let's be sure we're trying to order on columns that exist
			$fields = array_merge($this->EE->db->list_fields('exp_assets'), $this->EE->db->list_fields('exp_assets_entries'));
			
			foreach($sort_order as $key => $val)
			{
				if(in_array($key, $fields))
				{
					// if sorting by asset_id, need to specify which table, since it appears in both
					if($key == 'asset_id')
					{
						$sql .= ' a.' . $key . ' ' . $val . ',';
					}
					else{
						$sql .= ' ' . $key . ' ' . $val . ',';
					}
				}
			}

			// remove trailings			
			$sql = trim($sql, ',');
		}
		
		return $sql;
	}



	/**
	 * Convenience method for Many_assets::include_exclude()
	 */
	protected function _exclude($exclude = array())
	{
		return $this->_include_exclude($exclude, 'e');
	}


	/**
	 * Utility method to include or exclude certain fields
	 */
	protected function _include_exclude($incexc = '', $mode = 'i')
	{

		// Format/standardise params
		$incexc = $this->_delimitered($incexc);

		// arrays full of col_ids and field_ids
		$col_ids = $field_ids = array();

		foreach(explode(',', $incexc) as $names)
		{
			switch(substr_count($names, ':'))
			{
				// 2 = matrix column, in a custom field, in a channel
				case(2) :
    				// Only run query if our matrix table exists
    				if($this->EE->db->table_exists('exp_matrix_cols'))
    				{
	    				list($channel_name, $field_name, $col_name) = explode(':', $names);
	    				$sql = 'SELECT mc.col_id
	    							FROM exp_matrix_cols mc JOIN exp_channel_fields cf ON mc.field_id = cf.field_id
	    							WHERE cf.field_name = "' . $field_name . '"
	    							AND group_id IN (SELECT field_group FROM exp_channels WHERE channel_name = "'. $channel_name . '")
	    							AND mc.col_name = "' . $col_name . '" LIMIT 1';
			    		$query = $this->EE->db->query($sql);
						if($query->num_rows())
						{
							$row = $query->row();
							$key = 'c' . $row->col_id;
							if( ! array_key_exists($key, $col_ids))
							{
								$col_ids[$key] = $row->col_id;
							}
						}

						// waste not, want not
						$query->free_result();
					}
				break;

				// 1 = custom field in a channel
				case(1) :
    				list($channel_name, $field_name) = explode(':', $names);
		    		$sql = 'SELECT field_id FROM exp_channel_fields WHERE field_name = "' . $field_name . '" AND group_id IN (SELECT field_group FROM exp_channels WHERE channel_name = "'. $channel_name . '")';
		    		$query = $this->EE->db->query($sql);
					if($query->num_rows())
					{
						$row = $query->row();
						$key = 'f' . $row->field_id;
						if( ! array_key_exists($key, $field_ids))
						{
							$field_ids[$key] = $row->field_id;
						}
					}

					// waste not, want not
					$query->free_result();
				break;
			}
		}
		// combine all we found above into succinct NOT|IN()s
		$return = array();
		$condition = ($mode == 'i') ? 'IN' : 'NOT IN';
		
		if($col_ids) {
			$return[] = ' ae.col_id ' . $condition . '(' . implode(',', $col_ids) . ') ';
		}
		if($field_ids) {
			$return[] = ' ae.field_id ' . $condition . '(' . implode(',', $field_ids) . ') ';
		}

		// we return one of our two options
		return $return;
	}

	/**
	 * Plugin Usage
	 */
	public static function usage()
	{
		ob_start();
?>

See README.md for details, or visit https://github.com/johndwells/Many-Assets.
<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}


/* End of file pi.many_assets.php */
/* Location: /system/expressionengine/third_party/many_assets/pi.many_assets.php */