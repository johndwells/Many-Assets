<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

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
	'pi_version'	=> '1.1.0',
	'pi_author'		=> 'John D Wells',
	'pi_author_url'	=> 'http://johndwells.com',
	'pi_description'=> 'Retrieve Assets from across multiple Entries',
	'pi_usage'		=> Many_assets::usage()
);


class Many_assets {

	public $return_data;
    
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
    
		// if successful, this will be filled with assets		
		$files = array();

    	/*
    	 * fetch params
    	 */
    	$entry_ids		= $this->EE->TMPL->fetch_param('entry_ids');
    	$field_names	= $this->EE->TMPL->fetch_param('field_names', FALSE);
    	$orderby		= $this->EE->TMPL->fetch_param('orderby', FALSE);
    	$sort			= $this->EE->TMPL->fetch_param('sort', '');
    	$limit			= $this->EE->TMPL->fetch_param('limit', 10);
    	$offset			= $this->EE->TMPL->fetch_param('offset', 0);

    	/*
    	 * standardise list of entry ids
    	 */
		$entry_ids = trim($entry_ids, ',|');
    	if(strpos($entry_ids, '|') !== FALSE)
    	{
    		$entry_ids = str_replace('|', ',', $entry_ids);
    	}

		/*
		 * Limit query to field(s)?
		 */
		$field_ids = array();
		$col_ids = array();
		if($field_names)
		{
			// standardise
			$field_names = trim($field_names, ',|');
	    	if(strpos($field_names, '|') !== FALSE)
	    	{
	    		$field_names = str_replace('|', ',', $field_names);
	    	}

    		// loop through each, because if a matrix col then we need extra steps
    		foreach(explode(',', $field_names) as $name)
    		{
    			if(strpos($field_names, ':') !== FALSE)
    			{
    				/* UNFINISHED.
    				 * NEED TO:
    				 * 	1. Check if in fact Matrix is installed
    				 *	2. Query to find the field_id & col_id, based on the field_name:col_name passed to us
    				 *	3. add field_id to $field_ids array
    				 *	4. add col_id to $col_ids array
    				 */
    				 list($field_name, $col_name) = explode(':', $field_names);
    				 $sql = 'SELECT mc.col_id, mc.field_id
    				 			FROM exp_matrix_cols mc JOIN exp_channel_fields cf ON mc.field_id = cf.field_id
    				 			WHERE cf.field_name = "' . $field_name . '"
    				 			AND mc.col_name = "' . $col_name . '"';
			    		$query = $this->EE->db->query($sql);
						if($query->num_rows())
						{
							$field_ids[] = $query->row()->field_id;
							$col_ids[] = $query->row()->col_id;
						}
    			}
    			else
    			{
		    		$sql = 'SELECT field_id FROM exp_channel_fields WHERE field_name = "' . $field_names . '"';
		    		$query = $this->EE->db->query($sql);
					if($query->num_rows())
					{
						$field_ids[] = $query->row()->field_id;
					}
    			}
    		}
		}
    	
    	if($orderby == 'random')
    	{
    		$orderby = 'RAND()';
    		
    		// no sense in sorting if we're random, right?
    		$sort = '';
    	}
    	
    	// make sure we have a value to use
		if($entry_ids)
		{
			/*
			 * build our query
			 */
			$sql = 'SELECT DISTINCT a.asset_id, a.*
				FROM exp_assets a
				JOIN exp_assets_entries ae ON a.asset_id = ae.asset_id
				WHERE ae.entry_id IN(' . $entry_ids . ')';
			
			if($field_ids)
			{
				$field_ids = implode(',', $field_ids);
				$sql .= ' AND ae.field_id IN(' . $field_ids . ')';
			}
			
			if($col_ids)
			{
				$col_ids = implode(',', $col_ids);
				$sql .= ' AND ae.col_id IN(' . $col_ids . ')';
			}
			
			if($orderby)
			{
				$sql .= ' ORDER BY ' . $orderby . ' ' . $sort;
			}
			
			if(intval($limit) > 0)
			{
				$sql .= ' LIMIT ' . intval($limit);
			}
			
			if(intval($offset) > 0)
			{
				$sql .= ' OFFSET ' . intval($limit);
			}
			
			// loop through our returned result
			if($rows = $this->EE->db->query($sql)->result_array())
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
				foreach ($rows as $row)
				{
					$file = $Assets_ft->helper->get_file($row['file_path']);
		
					if ($file->exists())
					{
						$file->set_row($row);
						
						$files[] = $file;
					}
				}
			}
		}
	
		// so, what did we find?
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