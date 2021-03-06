<?php
/******************************************************************************************************************
 
Plugin Name: Site Locations
Plugin URI: http://glocal.coop
Description: Plug-in to assign locations to sites. Based on Site Locations by Deanna Schneider (http://deannaschneider.wordpress.com)
Author: Glocal Coop
Version: 1.0
Author URI: http://glocal.coop
Text Domain: bloglocations

Copyleft:

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

            
******************************************************************************************************************
*/

/* ******************************************************************************
 * Create a class to hold all the general functions. We'll create public functions below.
 * ******************************************************************************
 */

class glo_blog_locations 
{
	// empty variables for setting up tables
    var $table_location;
	var $table_relationship;
	
	

	
	
/* *******************************************************************************
 * Default constructor
 * *******************************************************************************
 */
	function glo_blog_locations() 
	{	
		global $table_prefix, $wpdb;
		
		// Update variables for 1 table to hold the locations, 1 table to hold relationships, and 1 table to hold photo references
		$this->table_location  = $wpdb->blogs . "_glo_location";
		$this->table_relationship = $wpdb->blogs . "_glo_location_relationship";
		
		// get the version
		$version = get_site_option( 'glo_bloglocations_setup' );
		// call set up if there's not option set yet, and we're not uninstalling
		if ($_GET['page'] == 'glo_bl_management_page' && ! isset($_GET['uninstalling'])){
			if(  $version == null ) {
				$this->setup();
			}
			if ( $version == 1 ){
				$this->upgrade(1);			
			}

			
		}
		
		if ($_GET['page'] == 'glo_bl_management_page' && $_GET['uninstalling'] == true){
			$this->unInstall();
			}
		
	} //end glo_blog_locations()
	
/* *******************************************************************************
 * Setup - only runs when site option glo_bloglocations_setup is not set
 * *******************************************************************************
 */
   
    function setup() 
    {
	    global $wpdb;
	    
	    //check if table was already created
	    if($wpdb->get_var("show tables like '$this->table_location'") != $this->table_location) {
			// if not create the location table
	    	$table_location_query = "CREATE TABLE $this->table_location (
						      id int(11) unsigned NOT NULL auto_increment,
						      location_name VARCHAR(55) NOT NULL default '',
							  active int(1) unsigned NOT NULL default 1,
							  slug VARCHAR(55) NOT NULL default '',
							  description VARCHAR(4000) NOT NULL default '',
							  thumbnail VARCHAR(200) NOT NULL default '',
							  banner VARCHAR(200) NOT NULL default '',
							  featured Integer default 0,
						      UNIQUE KEY id (id)
						      )";	  
 			
 			$results = $wpdb->query($table_location_query);
			
			//create the relationships table
			$table_relationship_query = "CREATE TABLE $this->table_relationship (
						  blog_id int(11) unsigned NOT NULL,
						  location_id int(11) unsigned NOT NULL, 
						  UNIQUE KEY id (blog_id, location_id)
						  )";
			$results = $wpdb->query($table_relationship_query);
			
			//insert locations & slugs into database - if you want more default locations, add them here before running the plugin
		    $locations = array(
				array(location_name => 'General',
					  slug => 'general')	  
					  );
		    
		   
		    foreach ( $locations as $location ) {
				$insert = $wpdb->prepare("INSERT INTO $this->table_location (location_name, slug) VALUES (%s, %s)", $location['location_name'], $location['slug']);
		    	$results = $wpdb->query( $insert );	
		    }
		    
		    
		    //add blog_location to every blog that's not deleted, spam, or the main blog
			$blog_list = $wpdb->get_results( "SELECT blog_id FROM $wpdb->blogs WHERE blog_id > 1 AND deleted = '0' and spam = '0' and archived = '0';");
			 
			foreach ( $blog_list as $blog ) {
				$this->set_blog_location($blog->blog_id,'1');
			}
		    
	    }
		
		// Add a site option so that we'll know set up ran
		add_site_option( 'glo_bloglocations_setup', 3 );
		
		// Add a site option to handle excluded blogs
		add_site_option('glo_bloglocations_excluded_blogs', '0');
	    		
    } // end setup()
 
 /* *********************************************************************************************
  * Upgrade - currently only from 1 to 2, but written to handle other versions in the future
  ************************************************************************************************/   
    function upgrade($version = 1) {
    	global $wpdb;
    	//if we're upgrading from version 1 (which was really version .3.2 - but we're fixing that little issue for the next round of upgrades)
		if ($version == 1){
			$alter_name = "Alter table " . $this->table_location . "s rename to " .  $this->table_location;
			$results = $wpdb->query($alter_name);
			
			$alter_rel = "Alter table " . $wpdb->blogs . "_glo_locations_relationships  rename to " .  $this->table_relationship;
			$results = $wpdb->query($alter_rel);
			
			$alter_query = "Alter table $this->table_location 
			ADD column slug VARCHAR(55) NOT NULL default '',
			ADD column description VARCHAR(4000) NOT NULL default '',
			ADD column thumbnail VARCHAR(200) NOT NULL default '',
			ADD column banner VARCHAR(200) NOT NULL default '',
			ADD column featured INTEGER default 0 ";
			
			$results = $wpdb->query($alter_query);
			
		// Add a site option so that we'll know set up ran
		update_site_option( 'glo_bloglocations_setup', 3 );
		// Add a site option to handle excluded blogs
		add_site_option('glo_bloglocations_excluded_blogs', '0');
		}
    }
	
/* *****************************************************************************************
 * Uninstall this plugin - deletes all tables and un-sets glo_bloglocations_setup site option (not really used yet)
 * *****************************************************************************************
 */
	
	function unInstall(){
		global $wpdb;
		$wpdb->query("DROP TABLE $this->table_location");
		$wpdb->query("DROP TABLE $this->table_relationship");
		$wpdb->query($wpdb->prepare("delete FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d", 'glo_bloglocations_excluded_blogs', $wpdb->siteid) );
		$wpdb->query($wpdb->prepare("delete FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d", 'glo_bloglocations_setup', $wpdb->siteid) );

		
		
		
	} // end unInstall()
	
	
/* **********************************************************************************************************
 * General Helper Functions
 * **********************************************************************************************************
 */	
 
 	function get_location_id_from_slug($slug) {
		global $wpdb;	
		$statement = $wpdb->prepare("SELECT id FROM $this->table_location WHERE slug = %s", $slug);
		$result = $wpdb->get_var($statement);
		return $result;
	}
    
	function get_location_id_from_blog_id($blog_id) {
		global $wpdb;
		// validate inputs
		if (!$blog_id =  (int) $blog_id) $blog_id = 0;
		$result = $wpdb->get_var($wpdb->prepare("SELECT location_id FROM $this->table_relationship WHERE blog_id = %d", $blog_id));
		return $result;
	}
    
    function get_blog_location($blog_id) {
    	global $wpdb;
    	return $wpdb->get_var($wpdb->prepare("select location_id from  $this->table_relationship  where blog_id = %d", $blog_id));
    }
	
	function get_blog_location_name($blog_id) {
    	global $wpdb;
    	$result = $wpdb->get_var($wpdb->prepare("select location_name from $this->table_location  c INNER JOIN  $this->table_relationship r ON c.id = r.location_id where r.blog_id =  %d", $blog_id));
		return ($result);
    }

	function get_blog_location_slug($blog_id) {
    	global $wpdb;
    	$result = $wpdb->get_var($wpdb->prepare("select slug from $this->table_location  c INNER JOIN  $this->table_relationship r ON c.id = r.location_id where r.blog_id =  %d", $blog_id));
		return ($result);
    }
     
	function get_location_name($location_id) {
    	global $wpdb;
    	return $wpdb->get_var($wpdb->prepare("SELECT location_name FROM $this->table_location
						WHERE id = %s AND active = 1;", $location_id));
    }
    
	function get_locations() {
    	global $wpdb;
		return $wpdb->get_results("SELECT id, location_name, slug, description, thumbnail, banner, '' as total FROM $this->table_location WHERE active = 1 ORDER BY location_name;");
    }
	
	function get_used_locations() {
    	global $wpdb;
    	// get excluded blogs
		$excluded = get_site_option('glo_bloglocations_excluded_blogs');
		
		// if the excluded string is nothing, the site option hasn't been set up yet - so do that
		if (strlen($excluded) == 0){
			add_site_option('glo_bloglocations_excluded_blogs', '0');
			$excluded = 0;
		}
		
		// don't include the main blog, deleted or excluded blogs
		return $wpdb->get_results("SELECT distinct id, location_name, slug, description, thumbnail, banner, count(r.blog_id) AS total FROM $this->table_location c INNER JOIN $this->table_relationship r ON c.id = r.location_id INNER JOIN $wpdb->blogs b ON r.blog_id = b.blog_id WHERE c.active = 1  and b.archived = '0' and b.spam = '0' and b.deleted = '0' and b.blog_id !=1 AND b.blog_id not in ($excluded) GROUP BY id, location_name ORDER BY location_name;");
    }
	
	// Returns an object of all the info about a single location
	function get_location($location_id) {
		global $wpdb;
    	return $wpdb->get_row($wpdb->prepare("SELECT location_name, id, slug, description, thumbnail, banner FROM $this->table_location
						WHERE id = %s AND active = 1;", $location_id));
	}
	
	// delete a blog from the relationships table
	function update_relationships($blog_id) {
		global $wpdb;
		$results = $wpdb->query( $wpdb->prepare("DELETE FROM $this->table_relationships WHERE blog_id = %d", $blog_id) );
	}

	function checkForSpace($str, $pos) {
		if (substr($str, $pos, 1) == ' '){
			return $pos;
		}
		else {
			if ($pos < strlen($str)){
				return $this->checkForSpace($str, $pos + 1);
			}
			else {
				return $pos;
			}
			
		}
	}

	
/* *******************************************************************************
 * Get all Sites that are part of a specific location id
 * *******************************************************************************
 */

    function get_blogs_from_location_id($location_id, $max_rows = 0, $blog_id = 0, $orderby = 'last_updated') // max_rows limits the query, $blog_id drops out the blog on which the widget is
    {
    	global $wpdb, $wpmuBaseTablePrefix;
    	
		// validate inputs
		if (!$location_id = (int) $location_id) $location_id = 0;
		if (!$max_rows = (int) $max_rows) $max_rows = 0;
		if (!$blog_id =  (int) $blog_id) $blog_id = 0;
		$orders = array('last_updated' => 'b.last_updated desc', 'alpha' => 'b.path', 'added' => 'b.registered desc' );
		if (!array_key_exists($orderby, $orders)){
			$orderby = 'last_updated';
			
		}
		// get excluded blogs
		$excluded = get_site_option('glo_bloglocations_excluded_blogs');
		
		// if the excluded string is nothing, the site option hasn't been set up yet - so do that
		if (strlen($excluded) == 0){
			add_site_option('glo_bloglocations_excluded_blogs', '0');
			$excluded = 0;
		}
		
		$statement = "SELECT distinct b.blog_id FROM $wpdb->blogs b 
			INNER JOIN $this->table_relationship r ON b.blog_id = r.blog_id  
			INNER JOIN $this->table_location c ON r.location_id = c.id AND c.active = 1 
			WHERE r.location_id = $location_id and b.archived = '0' and b.spam = '0' and b.deleted = '0' and b.blog_id !=1 AND b.blog_id not in ($excluded)";
		if ($blog_id != 0 && $blog_id == (int) $blog_id) {
			$statement .=  " AND b.blog_id != $blog_id ";
		}
		// check for Donncha's site wide tags blog - we don't want to include that
		$potential_tags_blog = get_site_option( 'tags_blog_id');
		if ($potential_tags_blog == (int) $potential_tags_blog && $potential_tags_blog) {
			$statement .= " AND b.blog_id !=  " . $potential_tags_blog . " ";
		}
		
		$statement = $statement . " ORDER BY " . $orders[$orderby];
		if ($max_rows > 0) {
			$statement = $statement . " LIMIT $max_rows "; 
		}
		$statement = $statement . ";";
		
		
		$blog_list = $wpdb->get_results($statement);
		 
   		$blogs_from_location = array();
		foreach ( $blog_list as $blog ) {
				array_push($blogs_from_location, $blog);
		}
		
		return $blogs_from_location;
    	
    } // end get_blogs_from_location_id()
	
	
/* **********************************************************************************************************
 * Get the recent posts from a location id
 * **********************************************************************************************************
 */	
	function get_recent_posts_from_location_id($location_id, $max_rows=0, $blog_id) {
		global $wpdb;
		if ($max_rows == 0 OR $max_rows != (int) $max_rows) {
			$max_rows = 10; // limit this to 10 total posts instead of defaulting to as many as there are
		}
		
		$blogs = $this->get_blogs_from_location_id($location_id, $max_rows, $blog_id);
		// loop through the blogs and create a big union statement (this isn't the most effecient method....)
		$i = 0;
		$sqlstring = "";
		$prefix = substr($wpdb->prefix, 0, stripos($wpdb->prefix, "_")+1);
		foreach($blogs as $blog) {
			if($i>0) $sqlstring .= " UNION ";
			
			$sqlstring .= "SELECT id, post_title, post_date, post_content, " . $blog->blog_id . " AS blogid,";
			$sqlstring .= "(SELECT option_value FROM " . $prefix . $blog->blog_id  ."_options WHERE option_name = 'blogname') AS blogname, ";
			$sqlstring .= "(SELECT option_value FROM " . $prefix . $blog->blog_id  ."_options WHERE option_name = 'siteurl') AS siteurl ";
			$sqlstring .= "FROM " . $prefix . $blog->blog_id . "_posts WHERE post_type = 'post' and post_status = 'publish' and post_title != 'Hello World!' ";
			$i = 1;
		}
		$sqlstring .=  " order by post_date desc limit " . $max_rows;
		// run the big union statement to get a list of posts to display
		return  $wpdb->get_results($sqlstring);
	
	}

/* **********************************************************************************************************
 * Get the recent posts from a location id, formatted as a list of items
 * **********************************************************************************************************
 */			
	function get_recent_posts_from_location_id_html($location_id, $max_rows=0, $blog_id=0) {
		global $wpdb;
			
		$result = $this->get_recent_posts_from_location_id($location_id, $max_rows, $blog_id);
		
			if( sizeof($result) > 0 ) {
				foreach ($result as $post) {
				
				$link = get_blog_permalink( $post->blogid, $post->id );
				$post->post_date = date_i18n(get_option('date_format') ,strtotime($post->post_date));  // Format the date
				echo "<li class='location'><h3 class='location-title'><a href='" .$link . "'>" . $post->post_title . "</a></h3>";
				echo "<div class='meta'><span class='post-date'>" . $post->post_date . "</span></div>";
				if (strlen(strip_shortcodes(strip_tags($post->post_content))) > 0){
					echo  "<div class='location-excerpt'>" . wp_html_excerpt(strip_shortcodes($post->post_content), 30) . "</div>";
				}
				echo "<h4 class='sitename'>From: <a href='" . $post->siteurl . "'>" . $post->blogname . "</a></h4>";
				echo "</li>";
				
				}
			
			}
			
			else {
				echo ("<li>");
				echo ("No recent posts in this location.");
				echo ("</li>");
				$location = $this->get_location($location_id);
				echo ("<li>");
				echo ("<a href='/sites/" . strtolower($location->slug) . "'>See all " . $location->location_name . " sites.</a>");
				echo ("</li>");
				}		
	}
	
/* **********************************************************************************************************
 * Get all the blogs from a location id, formatted as a list of items
 * **********************************************************************************************************
 */		
	function get_blogs_from_location_id_html($location_id, $max_rows = 0, $blog_id = 0, $orderby = 'last_updated', $class='')
    {
    	
		$result = $this->get_blogs_from_location_id($location_id, $max_rows, $blog_id, $orderby);		
		if(sizeOf($result) > 0) {
			if(strlen($class > 0)){
				echo("<ul class='" . $class ."'>");
			}
			else {
				echo("<ul>");
			}
			
	    	foreach ($result  as $blog )  {	    		
				$details = get_blog_details($blog->blog_id);
				echo "<li><a href='http://" . $details->domain . $details->path . "'>" . $details->blogname . "</a></li>";
	    	}
			echo("</ul>");
		}
    }
	

		
/* ********************************************************************************
 * Gets the blog details (name, link) for all blogs in a location - returns an array
 * ********************************************************************************
 */
	
	function get_blog_details_from_location_id($location_id, $max_rows = 0, $blog_id = 0, $orderby = 'last_updated')
    {
		$result = $this->get_blogs_from_location_id($location_id, $max_rows, $blog_id, $orderby);
		if(sizeof($result) > 0) {
		$blogs = array();
		
		
    	foreach ($result  as $blog )  {
   		
    		$details = get_blog_details($blog->blog_id);
			$thisblog = array(id=>$blog->blog_id,
			domain=>$details->domain,
			path=>$details->path,
			blogname=>$details->blogname
			);
			
			array_push($blogs, $thisblog);
    	}
		
		
    	// if the sort was in alpha order, we need to sort the array before returning it
		if ($orderby == 'alpha'){
			foreach ($blogs as $key => $row) {
    		$domain[$key]  = $row['domain'];
    		$path[$key] = $row['path'];
			$blogname[$key] = $row['blogname'];
			$id[$key] = $row['id'];
		}
		
		array_multisort($blogname, SORT_ASC, $blogs);
			
		}
		
		
			return $blogs;
		}
    }
    
    
 /* ********************************************************************************
 * Gets the list of locations, formatted as a list of items
 * ********************************************************************************
 */   
	
    
	function get_locations_html($used = true, $show_count = false, $send_to_root = false, $use_slugs = false)
    {	
		if ($used == true) {
			$cats = $this->get_used_locations();
		}
		else {
			$cats = $this->get_locations();
		}
		
    	foreach ( $cats as $location )  {
    		$text = "<li><a href='";
			if ($send_to_root == false) $text .= get_settings('siteurl');
			$text .= "/location/" . strtolower($location->slug) . "'>";
			if ($use_slugs == true) $text .= $location->slug;
			else $text .= $location->location_name;
			$text  .= "</a>";
			if ($show_count == true && $location->total > 0){
				$text .= " ($location->total)";				
			}
			$text .=  " </li>";
			echo $text;
    	}
		
    }
	
	
 
 /* ********************************************************************************
 * Gets a select box of all locations formatted for Site Locations and Site Edit pages
 * ********************************************************************************
 */   
	function get_locations_select($id = 0)
    {
    	global $wpdb;
    	if ($id == 0) {
    		$id = $wpdb->blogid;
			
    	}
		
    	$blog_location = $this->get_blog_location($id);
    	echo "<th>Site Location</th> <td><select name='blog_location_id' id='blog_location_id'>";
		
    	foreach ( $this->get_locations() as $location )  {
    		if ($blog_location && $blog_location == $location->id) {
    			$selected = "selected='selected'";
    		}
			echo "<option value='$location->id' ". $selected .">" . $location->location_name ."</option>";
			$selected = '';
    	}
    	echo "</select> </td>";
    }
    
/* ********************************************************************************
 * Gets a select box of all locations formatted for Site Locations and Site Edit pages
 * ********************************************************************************
 */   
	function get_locations_select_featured($id)
    {
    	global $wpdb;
    	
    	echo "<th>Site Location</th> <td><select name='blog_location_id' id='blog_location_id'>";
		
    	foreach ( $this->get_locations() as $location )  {
    		if ($id == $location->id) {
    			$selected = "selected='selected'";
    		}
			echo "<option value='$location->id' ". $selected .">" . $location->location_name ."</option>";
			$selected = '';
    	}
    	echo "</select> </td>";
    }   

 /* ********************************************************************************
 * Gets a select box of all locations formatted for wp-signup page
 * ********************************************************************************
 */   
	function get_locations_select_signup($id = 0)
    {
    	
    	echo "<label for='blog_location_id'>Site Location:</label><select name='blog_location_id' id='blog_location_id'>";
		
    	foreach ( $this->get_locations() as $location )  {
			echo "<option value='$location->id' " .">" . $location->location_name ."</option>";
			$selected = '';
    	}
    	echo "</select>";
    }

 
 
	

	
/* *****************************************************************************************************************
 * Administrative Functions
 * *****************************************************************************************************************
 */
 
	//Sets the location of a brand new blog
	function set_new_blog_location($blog_id)
    {
    	global $wpdb;
		
    	//include the functions file

    	

    	include_once dirname(__FILE__) . '/glo_blog_locations/functions.php';
		
		/* get the blog name*/
		
		$blogname = get_blog_option($blog_id,'blogname');
			
		$optionname = 'signup_location_' . $blogname;
		
		$location_id = get_site_option($optionname);
				
		if (!$location_id) {
			$location_id = $_POST['blog_location_id'];
		}
		//else { //shouldn't we always remove this now?
			// remove the site option, since we don't need it anymore
			$wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->sitemeta WHERE meta_key = %s", $optionname) );		
		//}	
		

    	$this->set_blog_location($blog_id,$location_id);
		
		
		
		

    	// Set the notice to default to "on" and the sharing to off

    	add_blog_option($blog_id, 'glo_notification', 1);

    	add_blog_option($blog_id, 'glo_locationexclude', 1);

    	

    	//New blogs should always be set not to aggregate (since they have no content to begin with)

    	glo_bl_toggle_blog_exclusion($blog_id, 'e');

    	

    	

		

		

		

		

    }
 
 	// Saves the location when they first sign up - this is needed because of the way signups process after confirmation for new users
  	function save_signup_blog_location(){
		// set a site option with the key for the new unactivated blog
		//echo("Saving signup blog option");
		add_site_option( 'signup_location_' . $_POST['blog_title'], $_POST[blog_location_id] );
		
	}
	
   // Adds or updates the blog location, whichever is necessary
    function set_blog_location($blog_id, $location_id = '1') 
    {
    	global $wpdb, $wpmuBaseTablePrefix;
    	$total = $wpdb->get_var($wpdb->prepare("select count(*) from $this->table_relationship WHERE blog_id = %d", $blog_id));
		if ($total == '1') {
			$results = $wpdb->query( $wpdb->prepare("UPDATE $this->table_relationship SET location_id = %d  WHERE blog_id = %d", $location_id, $blog_id ) );	
    	} else {
			$results = $wpdb->query( $wpdb->prepare("INSERT INTO  $this->table_relationship (blog_id, location_id) VALUES (%d, %d)", $blog_id, $location_id));
		}
    }
	
	// Updates the location meta info
	function update_location($location_id, $location, $slug, $description){
			global $wpdb;
			$results = $wpdb->query( $wpdb->prepare("UPDATE $this->table_location SET location_name =  %s, slug = %s, description = %s WHERE id =  %d", $location, $slug, $description, $location_id ) );
	}
	
	// Adds a new location
	function add_location( $location, $slug, $description){
			global $wpdb;
			$results = $wpdb->query( $wpdb->prepare("INSERT INTO $this->table_location (location_name, slug, description) VALUES (%s, %s, %s)", $location, $slug, $description) );
	}
	
	// Sets the featured location
	function set_featured_location($location_id){
		global $wpdb;
		// set all the featured locations to 0, then set featured location to 1
		$results = $wpdb->query( $wpdb->prepare("UPDATE $this->table_location SET featured =  0 WHERE id !=  %d",  $location_id ) );
		$results = $wpdb->query( $wpdb->prepare("UPDATE $this->table_location SET featured =  1 WHERE id =  %d",  $location_id ) );
		
	}
	
	// Gets the featured location
	function get_featured_location(){
		global $wpdb;
		$results = $wpdb->get_var( "Select id from $this->table_location WHERE featured = 1" );
		return $results;
		
	}
	
	// Gets the featured location_name
	function get_featured_location_name(){
		global $wpdb;	
		$results = $wpdb->get_var( "Select location_name from $this->table_location WHERE featured = 1" );
		return $results;
		
	}
	
	// Deletes a location after changing any blogs associated with it to the default location
	function delete_location( $location_id){
			global $wpdb;
			// update all the blogs that have that ID to the #1 required ID.
			$blog_list = $this->get_blogs_from_location_id($location_id);
			foreach ( $blog_list as $blog ) {
				$result = $this->set_blog_location($blog->blog_id, 1);
			}
			
			// delete the location
			$results = $wpdb->query( $wpdb->prepare("DELETE FROM $this->table_location WHERE id = %d", $location_id) );
	}
    
	//Gets the table of all locations - used on the site admin - blog locations page
	 function get_locations_table()
    {
    	global $wpdb;
    	
    	echo "<table class='wp-list-table widefat'><thead><tr><th>ID</th><th>Name</th><th>Slug</th><th colspan='2'>Description</th></tr></thead>";
    	foreach ( $this->get_locations() as $location )  {
			
    		echo "<tbody><tr><form name='catupdate' method='post'><td align='center'>" . $location->id . "</td>";
			echo("<td><input type='text' maxlength='140' name='location' value='" . $location->location_name . "' required><br />140 chars max");
			echo("<td><input type='text' maxlength='140' name='slug' value='" . $location->slug . "' required><br />140 chars max");
			echo("<td><textarea name='description' cols='40' rows='5'>" . $location->description . "</textarea><br />8000 chars max" );

			echo("<input type='hidden' name='location_id' value='" . $location->id . "'>");
			echo("<td><input type='hidden' name='action' value='edit'> <input type='submit' class='button-primary save' name='edit' value='Update'> </td>");
			echo("</form><td>");
			if ($location->id != 1) {
				echo "<form name='deletecat' method='post' onsubmit='return confirm(\"Are you sure you want to delete this location?\");'><input type='hidden' name='action' value='delete'><input type='hidden' name='locationid' value='" . $location->id . "'><input type='submit' class='button alert'  value='Delete'></form>";
			}
			else {
				echo "Location 1 can not be deleted.";
			}
			
			echo "</td></tr>";
    	}
    	echo "</tbody></table>";
    	echo "<h2>Add New</h2>";
    	echo "<table class='wp-list-table widefat'><thead><tr><th>Name</th><th>Slug</th><th colspan='2'>Description</th></tr></thead>";
    	echo("<tr><form name='catadd' method='post'><td><input type='text' maxlength='140' name='location' value='' required><br />140 chars max<input type='hidden' name='action' value='add'></td>");
		echo("<td><input type='text' maxlength='140' name='slug' value='' required><br />140 chars max</td>");
		echo("<td><textarea name='description' cols='40' rows='5'></textarea><br />8000 chars max</td>");
		echo("<td><input type='submit' class='button-primary save' value='Add'></form></td></tr></tbody></table>");
    }
	
	
	
	
	// Adds the submenu to the blog settings screen
    function add_submenu()
    {
    	add_options_page('Site Location', 'Site Location', 10, 'glo_blog_location', array(&$this,'config_page'));
    }
    
	// Creates the configuration page for an individual blog (blog's settings screen sub menu)
    function config_page() 
    {
    	global $wpdb, $blog_id;
		$sitename = get_site_option('site_name');
    	if ($_POST['action'] == 'update') {
			$this->set_blog_location($wpdb->blogid,$_POST['blog_location_id']);		
			$updated = true;
    	}
		
    	
		
    	if ($updated) { ?>
        <div id="message" class="updated fade"><p><?php _e('Options saved.') ?></p></div>
        <?php	} ?>
        <div class="wrap">
        <h2>Site Location</h2>
        <p>Site Locations are how <?php echo($sitename); ?> includes your posts into the location areas on the main <?php echo($sitename); ?> site. These locations are for the convenience of the public using the main <?php echo($sitename); ?> site and do not affect your individual blog site in any way.<p> 
        <form name="bloglocationform" action="" method="post">
            <table class="form-table">	
            <tr>
			<?php
                    $this->get_locations_select();
            ?>

            </tr>
            </table>
            <p class="submit">
            <input type="hidden" name="action" value="update" /> 
            <input type="submit" name="Submit" class="button-primary" value="<?php _e('Update Options') ?>" /> 
            </p>
        </form>
        </div>
        <?php
            }
	
	
	//Add the site-wide administrator menu (site admin) 
	function add_siteadmin_page(){
      if (is_site_admin()) {

      	if (function_exists('is_network_admin')) {

      		add_submenu_page('settings.php', 'Site Locations', 'Site Locations', 10, 'glo_bl_management_page', array(&$this, 'glo_bl_management_page'));

     

      	}

      	else {

      		add_submenu_page('ms-admin.php', 'Site Locations', 'Site Locations', 10, 'glo_bl_management_page', array(&$this, 'glo_bl_management_page'));

      	}

      }
	 }
	 
	 
	 
	 // Creates the submenu page for the site admins
	 function glo_bl_management_page(){
	 	// Display a list of all the locations to potential edit/add/delete;
	 
	 	global $wpdb;
    	
    	if ($_POST['action'] == 'edit') {
			$this->update_location($_POST['location_id'], $_POST['location'], $_POST['slug'], $_POST['description']);
			$updated = true;
    	}
		if ($_POST['action'] == 'add') {
			$this->add_location($_POST['location'], $_POST['slug'], $_POST['description']);
			$updated = true;
    	}
		
		if ($_POST['action'] == 'delete') {
			$this->delete_location($_POST['locationid']);
			$updated = true;
		}
		if ($_POST['action'] == 'featured'){
			
			$this->set_featured_location($_POST['blog_location_id']);
			$updated = true;
			
		}
		
    	
		// only display this stuff if we're not running the uninstall
		if (!isset($_GET['uninstalling'])) {
			
		?>
        <div class="wrap">
        <h2>Manage Site Locations</h2>
          
            <?php
			if ($updated){
				?>
				<div id="message" class="updated fade"><p><?php _e('Options saved.') ?></p></div>
				<?php
			}
                    $this->get_locations_table();
            ?>
			<div>
				<h2>Featured Location</h2>
				<form name="catupdate" method="post">
				<p>Theme code may want to feature a specific location. Use this option to set the featured location.</p>
				<p><?php
				$featured = $this->get_featured_location();
				$this->get_locations_select_featured($featured); ?> <input type="submit" class="button" value="Set Featured Location">
				<input type="hidden" name="action" value="featured">
				</p>
				</form>
			</div>
           
           
           
        </div>
		
		<?php 
		} // end of if to display if we're not unistalling
		?>
		
		<div class="wrap">
		<h2>Uninstall</h2>
		<?php if (!isset($_GET['uninstalling'])){
			
		?>
		<p>Uninstalling this plugin will delete all database tables and sitewide options related to this plugin. You will not be able to undo this uninstall. Proceed with caution.</p>
		
		<p>Once the data is deleted, you will still need to manually delete the files associated with this plugin. </p>
		<p><a href="settings.php?page=glo_bl_management_page&uninstalling=true">Yes, uninstall this plugin.</a>
		
		<?php
		}
		else{
			echo ("<p>Your plugin data has been uninstalled. You may safely delete your plugin files.</p>");
			
		}
		?>
		</div>
		<?php
		
	 }

// Notify site admins if their site is not being aggregated.
	function site_admin_notice() {

		global $wp_db_version, $blog_id;

		$noticeon = get_option('glo_notification');

		// if a blog has never set the notice status, default it to turned on

		if (strlen($noticeon) != 1){

			$noticeon = 1;

			update_option('glo_notification', 1);

		}

		

			

		// don't show this notice on the page where you change the setting or if the notice has been turned off

		if ($_SERVER['SCRIPT_NAME'] == '/wp-admin/options-privacy.php' || $noticeon == 0){

			return;

		}

		

		$excludelist = get_site_option('glo_bloglocations_excluded_blogs');

		$excluded = glo_bl_listfind($excludelist, $blog_id, ",");

		

		if ( current_user_can('switch_themes') && $excluded == 1 ){

			

			echo "<div class='update-nag'>" . sprintf( __( 'Your site is development mode and hidden from the %s homepage. <br /> To include it on the %s homepage, click <a href="%s">here</a>.' ), get_blog_option(1, 'blogname'), get_blog_option(1, 'blogname'),
 esc_url( admin_url( 'options-privacy.php' ) ) ) . "</div>";

		}

	}



/* ********************************************************************************

 * The following section adds our content sharing options to the privacy page and hooks into the updating of these options to run our custom functions

 * ********************************************************************************

 */

	

	function add_privacy_options_init() {

		add_settings_section('glo_content_sharing_options', 'Content Sharing Options', array('glo_blog_locations', 'add_content_sharing_section'), 'privacy');

		

		add_settings_field('glo_locationexclude', 'Share Your Content', array('glo_blog_locations', 'add_glo_locationexclude'), 'privacy', 'glo_content_sharing_options');

		add_settings_field('glo_notification', 'Show/Hide Notification', array('glo_blog_locations', 'add_glo_notification'), 'privacy', 'glo_content_sharing_options');

		

		register_setting('privacy','glo_locationexclude');

		register_setting('privacy', 'glo_notification');

		

	

	}

	

	function add_content_sharing_section(){

		

		echo("<p>Get your site listed and findable by sharing it on the ". get_blog_option(1, 'blogname') ." homepage. Your most recent posts will automatically display in their Location area news and your site will be listed in the Sites list for <a href='". esc_url( admin_url( 'options-general.php?page=glo_blog_location' ) ) ."'>your site's location</a>.</p>");

	}

	

	function add_glo_locationexclude() {	

		// Check to see if this option has been set yet and if not, set it to exclude the blog

		global $blog_id;

		$excludelist = get_site_option('glo_bloglocations_excluded_blogs');

		$excluded = glo_bl_listfind($excludelist, $blog_id, ",");

		$shared = get_option('glo_locationexclude');

		if (strlen($shared) != 1){ 

			add_option('glo_locationexclude', $excluded);

		}

		

		echo('<label for="glo_locationexclude_no"><input id="glo_locationexclude_no" type="radio" name="glo_locationexclude" value="0"' . checked( 0, get_option('glo_locationexclude'), false ) . '/> Share it!</label>');

		echo('<br/>');

	  	echo('<label for="glo_locationexclude_yes"><input id="glo_locationexclude_yes" type="radio" name="glo_locationexclude" value="1"' . checked( 1, get_option('glo_locationexclude'), false ) . '/> Do not share it!</label>');

			

		

	}

	

	function add_glo_notification(){

			echo('<label for="glo_notification_off"><input id="glo_notification_off" type="radio" name="glo_notification" value="0" ' . checked( 0, get_option('glo_notification'), false ) .' /> <b>Turn the notification off.</b> I will remember when it is time to share my blog.</label>');

           

            echo('<br />');

            echo('<label for="glo_notification_on"><input id="glo_notification_on" type="radio" name="glo_notification" value="1" ' . checked( 1, get_option('glo_notification'), false ) .' /> <b>Turn the notification on.</b> I need a reminder to share my blog when it is ready to go live.</label>');

			

            

		

	}

	

	

	function update_option_glo_locationexclude($oldvalue, $_newvalue){

		global $blog_id;

			if ($_newvalue == 1) {

					// exclude this blog

					glo_bl_toggle_blog_exclusion($blog_id, 'e');

					

				}

			else {

					// include this blog

					glo_bl_toggle_blog_exclusion($blog_id, 'i');

					

				}

	}

	

	

	/* *******************************************************************************************

	 * Create a stylesheet to hide the privacy settings on the sign in page

	 * *******************************************************************************************

	 */

	

	function hide_privacy_stylesheet() {

		?>

		<style type="text/css">

			#setupform div#privacy { display: none; }

		</style>

		<?php

	}
	
}; // end of class



// Create the class
$glo_wpmsbl = new glo_blog_locations();



	
// Add the actions and filters we need to make all this run	
add_action('signup_blogform', array(&$glo_wpmsbl, 'get_locations_select_signup'));
add_filter('wpmu_new_blog', array(&$glo_wpmsbl, 'set_new_blog_location'), 101);
add_action('signup_finished', array(&$glo_wpmsbl, 'save_signup_blog_location'));

add_action( 'admin_notices', array(&$glo_wpmsbl, 'site_admin_notice') );



// hook into options-privacy.php and the updates of those options

add_action('admin_init', array(&$glo_wpmsbl, 'add_privacy_options_init'));

add_action('update_option_glo_locationexclude', array(&$glo_wpmsbl, 'update_option_glo_locationexclude'), 10, 2);



add_action( 'wp_head', array(&$glo_wpmsbl, 'hide_privacy_stylesheet' ));



add_action('admin_menu', array(&$glo_wpmsbl, 'add_submenu'));



if (function_exists('is_network_admin')) {

	add_action('network_admin_menu', array(&$glo_wpmsbl, 'add_siteadmin_page'));

}

else {

	

add_action('admin_menu', array(&$glo_wpmsbl, 'add_siteadmin_page'));

}



add_action('delete_blog', array(&$glo_wpmsbl, 'update_relationships'));



/* *********************************************************************************
 * Make public functions for the "private" functions in the class
 */
function glo_get_blogs_from_location_id_html($location_id = '1', $max_rows = 0, $blog_id = 0, $orderby = 'last_updated') {
    global $glo_wpmsbl;
	return $glo_wpmsbl->get_blogs_from_location_id_html($location_id, $max_rows, $blog_id, $orderby);
}

function glo_get_location_name($location_id = '1') {
    global $glo_wpmsbl;
	return $glo_wpmsbl->get_location_name($location_id);
}

function glo_get_locations_html($used = true, $show_count = true, $send_to_root = false, $use_slugs = false) {
    global $glo_wpmsbl;
	return $glo_wpmsbl->get_locations_html($used, $show_count, $send_to_root, $use_slugs);
}

function glo_get_blog_location_name($blog_id) {
	global $glo_wpmsbl;
	return $glo_wpmsbl->get_blog_location_name($blog_id);
}

function glo_get_blog_location_slug($blog_id) {
	global $glo_wpmsbl;
	return $glo_wpmsbl->get_blog_location_slug($blog_id);
}

function glo_get_location_id_from_blog_id($blog_id) {
	global $glo_wpmsbl;
	return $glo_wpmsbl->get_location_id_from_blog_id($blog_id);
}

function glo_get_recent_posts_from_location_id_html($location_id, $max_rows=0, $blog_id) {
	global $glo_wpmsbl;
	return $glo_wpmsbl->get_recent_posts_from_location_id_html($location_id, $max_rows, $blog_id);
}

function glo_get_blog_details_from_location_id($location_id, $max_rows = 0, $blog_id = 0, $orderby = 'last_updated') {
	global $glo_wpmsbl;
	return $glo_wpmsbl->get_blog_details_from_location_id($location_id, $max_rows = 0, $blog_id = 0, $orderby);
}	

function glo_get_recent_posts_from_location_id($location_id, $max_rows=0, $blog_id) {
	global $glo_wpmsbl;
	return $glo_wpmsbl->get_recent_posts_from_location_id($location_id, $max_rows=0, $blog_id);
}

function glo_get_location_id_from_slug($slug) {
	global $glo_wpmsbl;
	return $glo_wpmsbl->get_location_id_from_slug($slug);	
}

function glo_get_location($location_id) {
	global $glo_wpmsbl;
	return $glo_wpmsbl->get_location($location_id);	
}
function glo_get_used_locations() {
	global $glo_wpmsbl;
	return $glo_wpmsbl->get_used_locations();	
}
function glo_get_featured_location() {
	global $glo_wpmsbl;
	return $glo_wpmsbl->get_featured_location();	
}
function glo_get_featured_location_name() {
	global $glo_wpmsbl;
	return $glo_wpmsbl->get_featured_location_name();	
}


/* ********************************************************************************************************
 * Include the privacy options section
 * ********************************************************************************************************
 */
 if ( strpos($_SERVER['REQUEST_URI'], 'wp-admin') == true && file_exists(dirname(__FILE__) . '/glo_blog_locations/miscactions.php')) {
	include_once dirname(__FILE__) . '/glo_blog_locations/miscactions.php';
	}


?>