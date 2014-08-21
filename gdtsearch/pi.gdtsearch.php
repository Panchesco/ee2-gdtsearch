<?php
/**
* Gdtsearch Class
*
* @package ExpressionEngine
* @author Richard Whitmer/Godat Design, Inc.
* @copyright (c) 2014, Godat Design, Inc.
* @license
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*
* @link http://godatdesign.com
* @since Version 2.9
*/
 
 // ------------------------------------------------------------------------

/**
 * Good at Search Plugin
 *
 * @package			ExpressionEngine
 * @subpackage		third_party
 * @category		Plugin
 * @author			Richard Whitmer/Godat Design, Inc.
 * @copyright		Copyright (c) 2014, Godat Design, Inc.
 * @link			http://godatdesign.com
 */
  
 // ------------------------------------------------------------------------

	$plugin_info = array(
	    'pi_name'         => 'Good at Search',
	    'pi_version'      => '1.0',
	    'pi_author'       => 'Richard Whitmer/Godat Design, Inc.',
	    'pi_author_url'   => 'http://godatdesign.com/',
	    'pi_description'  => '
	    Customizable handling of search results to extend native EE search result URLs.
	    ',
	    'pi_usage'        => Gdtsearch::usage()
	);
	

	class  Gdtsearch {
			
			public	$site_id			= 1;
			public	$return_data		= '';
			public	$entry_id			= FALSE;
			public	$status_array		= array('open');
			public	$title				= NULL;
			public	$pages_array		= array();
			public	$auto_path			= FALSE;

		
			public function __construct()
			{
			
				// Since we'll be using the URL helper, load it.
				ee()->load->helper('url');

				// Fetch the entry_id from the template.
				if(ee()->TMPL->fetch_param('entry_id'))
				{
				    $this->entry_id	= ee()->TMPL->fetch_param('entry_id');
				}
				
				// Fetch the auto_path param from the template.
				if(ee()->TMPL->fetch_param('auto_path'))
				{
				    $this->auto_path	= ee()->TMPL->fetch_param('auto_path');
				}
				
				// Get info from the pages module about page uris
				$this->site_id	= ee()->config->item('site_id');
				$this->pages_array	= ee()->config->item('site_pages')[$this->site_id]['uris'];
			}
			
			// ------------------------------------------------------------------------
			
			
			/**
			 *	Return url of search result.
			 *	@return string.
			 */
			 public function url()
			 {
			 
			 	// If the entry_id exists in the pages array, return the pages uri.
			 	if(isset($this->pages_array[$this->entry_id]))
			 	{

				 	return site_url($this->pages_array[$this->entry_id]);
			 	
				 	} else {
				 	
					// Get data about the title.
					$this->title = $this->entry_title($this->entry_id);

			 	}


			 	// If no title was found, just return the site url.
			 	if($this->title === NULL)
			 	{
				 	return site_url();
			 	}
			 	
			 	
			 	############################################################################
			 	## 	CUSTOMIZATION: 
			 	##	Do your own thing by editing the switch statement below ################
			 	############################################################################
			 	
			 	switch($this->title->channel_name)
			 	{

				 	// Probably best to leave the default branch alone.
					default:
					 	if($this->auto_path !== FALSE)
					 	{ 
					 		return $this->auto_path;
					 		} else {
						 	return site_url();
					 	}
				 	break;
			 	}
			 	
			 	############################################################################
			 	############################################################################
			 	############################################################################
			 }


			 

			/**
			 *	Return plugin usage documentation.
			 *	@return string
			 */
			public function usage()
			{
				
					ob_start();  ?>
					
					
					ABOUT:
					----------------------------------------------------------------------------
					Without customization, this plugin gives you the same URL path result as EE's 
					native {auto_path} or {page_url} tags. 
					
					But if you need more control over the URLs seach results point to, 
					customize the switch statement in the plugin's url() method and use the 
					exp:gdtcat:url tag to do whatever acrobatics you want to point searchers where
					you want them to go.
					 
					
					TAGS:
					----------------------------------------------------------------------------
					{exp:gdtsearch:url entry_id="{entry_id}" auto_path="{auto_path}"}
					
					
					
					PARAMETERS: 
					----------------------------------------------------------------------------
					entry_id	-	required
					auto_path	- 	optional: The auto_path string provided by EE's native search.
									If present, plugin will use this as a fallback instead of 
									the site url if no customization option is found.
					

					<?php
					 $buffer = ob_get_contents();
					 ob_end_clean();
					
					return $buffer;
					
			}
			
			
			/**
			 *	Get entry title and category data for the entry id.
			 *	@param $entry_id integer
			 *	@return mixed object or NULL 
			 */
			 private function entry_title($entry_id)
			 {
				//	Get joined channel title and data rows as one row.
				
					$sel	=	array(
									'channel_titles.entry_id',
									'channel_titles.title',
									'channel_titles.url_title',
									'channel_titles.status',
									'channel_titles.site_id',
									'channel_titles.channel_id',
									'channels.channel_name',
									'channels.channel_title',
									'channels.channel_url'
									);
					
					
					$query			= ee()->db
										->select($sel)
										->join('channels','channels.channel_id = channel_titles.channel_id')
										->where('entry_id',$this->entry_id)
										->limit(1)
										->get('channel_titles');
					
					
					if($query->num_rows()==1)
					{	
						$entry_title				= $query->row();					
						$entry_title->categories	= $this->categories($entry_title->entry_id);
						
						return $entry_title;
					
					} else {
						
						return NULL;
					}

				
			 }
			
			
			/**
			 *	Get the array of categories an entry_id is assigned to.
			 *	@param $entry_id integer
			 *	@return array  associatvie cat_id=>cat_url_title
			 */
			 private function categories($entry_id)
			 {
				 
				 $data	= array();
				 $sel	= array('category_posts.entry_id','category_posts.cat_id','categories.cat_url_title');
				 
				 $query	= ee()->db
				 			->select($sel)
				 			->join('categories','categories.cat_id = category_posts.cat_id')
				 			->where('category_posts.entry_id',$entry_id)
				 			->get('category_posts');
				 			
				foreach($query->result() as $key => $row)
				{
					
					$data[$row->cat_id]	= $row->cat_url_title;
					
				}
				
				return $data;
				 
			 }
		
		
	}
/* End of file pi.gdtsearch.php */
/* Location: ./system/expressionengine/third_party/gdtcat/pi.gdtsearch.php */
