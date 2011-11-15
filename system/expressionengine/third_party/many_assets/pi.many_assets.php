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
	'pi_version'	=> '1.0',
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
    	$entry_ids	= $this->EE->TMPL->fetch_param('entry_ids');
    	$orderby	= $this->EE->TMPL->fetch_param('orderby', FALSE);
    	$sort		= $this->EE->TMPL->fetch_param('sort', '');
    	$limit		= $this->EE->TMPL->fetch_param('limit', 10);
    	$offset		= $this->EE->TMPL->fetch_param('offset', 0);

    	/*
    	 * reformat params
    	 */
		$entry_ids = trim($entry_ids, ',|');
    	if(strpos($entry_ids, '|') !== FALSE)
    	{
    		$entry_ids = str_replace('|', ',', $entry_ids);
    	}
    	
    	if($orderby == 'random')
    	{
    		$orderby = 'RAND()';
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