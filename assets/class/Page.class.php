<?php
class Page extends Navigation {

	/***************************************/
	/*********** PAGE CONSTRUCTOR **********/
	/***************************************/
	function __construct($db, $extra_vars = null){
		
		//Global vars
		$this->extra_vars = $extra_vars;
		
		//construct parent
		parent::__construct($db);
				
		//get the current page
		$this->current_page = self::getPageForSlug();

		//the tags
		$this->tags = array(); $this->relevant_tags = array(); self::getTheTags();
				
		//define page constants
		self::definePageConstants();
		
	}

	/***************************************************************************/
	/****************** GET THE TAGS *******************************************/
	/***************************************************************************/
	public function getTheTags(){
		$result = @mysql_query(" SELECT * FROM dzpro_tags LEFT JOIN dzpro_tag_to_page USING ( dzpro_tag_id ) ORDER BY LENGTH( dzpro_tag_name ) DESC "); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $this->tags[$row['dzpro_tag_id']] = $row; if((int)$row['dzpro_page_id'] == (int)$this->current_page['dzpro_page_id']){ $this->relevant_tags[] = $row; } } mysql_free_result($result); return true; }
		return false;
	}
	
	/***************************************************************************/
	/****************** SET ALTERNATIVE DATABASE CONNECTION ********************/
	/***************************************************************************/	
	public function setAlternativeDatabaseConnectionLink($link){
		if(!is_resource($link)){ return false; }
		$this->db_alt = $link; return true;
	}

	/***************************************************************************/
	/****************** DEFINE PAGE CONSTANTS **********************************/
	/***************************************************************************/
	private function definePageConstants(){
		$result = @mysql_query("SELECT * FROM dzpro_page_constants WHERE dzpro_page_id = " . (int)$this->current_page['dzpro_page_id']); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ if(!defined($row['dzpro_page_constant_name'])){ define($row['dzpro_page_constant_name'], $row['dzpro_page_constant_value']); } } }
	}
	
	/***************************************************************************/
	/****************** GET ACTIVE PAGE SLUG ***********************************/
	/***************************************************************************/
	private function getPageForSlug(){
		if(empty($this->last_page_slug)){ return array(); }
		$return = array(); $result = @mysql_query("SELECT * FROM dzpro_pages WHERE dzpro_page_slug = '" . mysql_real_escape_string($this->last_page_slug) . "'"); if(mysql_num_rows($result)){ while($row = mysql_fetch_assoc($result)){ $return = $row; } mysql_free_result($result); } return $return;
	}

	/***************************************************************************/
	/****************** INCLUDE IS EXISTS - INSULATES INCLUDED SCOPE ***********/
	/***************************************************************************/
	protected function includeFileIfExists($path = null){
		if(is_file($path)){ include $path; }
	}
	
	/***************************************************************************/
	/****************** INCLUDE PAGE TEMPLATE **********************************/
	/***************************************************************************/	
	public function includePageTemplate(){
		if(empty($this->current_page) or !is_file(DOCUMENT_ROOT . $this->current_page['dzpro_page_template'])){ include DOCUMENT_ROOT . FILE_404; }else{ include DOCUMENT_ROOT . $this->current_page['dzpro_page_template']; }
	}

	/***************************************************************************/
	/****************** INCLUDE PAGE ELEMENTS **********************************/
	/***************************************************************************/	
	public function loadPageElements($area_name = null){
		if(empty($area_name)){ return null; }
		$result = @mysql_query("SELECT * FROM dzpro_page_elements LEFT JOIN dzpro_page_element_to_page USING ( dzpro_page_element_id ) WHERE dzpro_page_id = " . (int)$this->current_page['dzpro_page_id'] . " AND dzpro_page_element_map_area = '" . mysql_real_escape_string($area_name) . "' ORDER BY dzpro_page_element_map_orderfield"); if(mysql_num_rows($result) > 0){ while($row = mysql_fetch_assoc($result)){ $this->theElement = $row; if(have($row['dzpro_page_variant_element_template']) and is_file(DOCUMENT_ROOT . ELEMENTS_PATH . $row['dzpro_page_variant_element_template']) and (getVisitorId()%2) == 1){ self::includeFileIfExists(DOCUMENT_ROOT . ELEMENTS_PATH . $row['dzpro_page_variant_element_template']); }else{ self::includeFileIfExists(DOCUMENT_ROOT . ELEMENTS_PATH . $row['dzpro_page_element_template']); } } mysql_free_result($result); }
	}

	/***************************************************************************/
	/****************** GET PAGE CONTENTS **************************************/
	/***************************************************************************/	
	public function loadPageContent($area_name = null){
		if(empty($area_name)){ return null; }
		$result = @mysql_query("SELECT dzpro_page_content_html FROM dzpro_page_contents WHERE dzpro_page_id = " . (int)$this->current_page['dzpro_page_id'] . " AND dzpro_page_content_name = '" . mysql_real_escape_string($area_name) . "'"); if(mysql_num_rows($result) > 0){ $return = ''; while($row = mysql_fetch_assoc($result)){ $return = $row['dzpro_page_content_html']; } mysql_free_result($result); return self::showTagsBlock() . self::placeTagLinks(stripslashes($return)); } return null; 
	}
	
	/***************************************************************************/
	/****************** SHOW TAGS BLOCK ****************************************/
	/***************************************************************************/	
	protected function showTagsBlock(){
	
		return null; //disabled
	
		if(isset($this->current_page['dzpro_page_show_tags']) and $this->current_page['dzpro_page_show_tags'] == 0){ return null; }
		if(have($this->relevant_tags)){ $return = '<ul class="tags">'; $count = 0; foreach($this->relevant_tags as $tag){ if($count < LIMIT_PAGE_TAG_LIST){ $return .= '<li><a href="/tag/' . prepareStringForUrl($tag['dzpro_tag_name']) . '/" title="' . prepareTag($tag['dzpro_tag_description']) . '">' . prepareStringHtml($tag['dzpro_tag_title']) . '</a></li>'; } $count++; } $return .= '</ul>'; return $return; }
		return null;
	}
	
	/***************************************************************************/
	/****************** INSERT TAG LINKS ***************************************/
	/***************************************************************************/	
	protected function placeTagLinks($string = null){
		if(have($this->tags)){ $string = placeTagLinks($string, $this->tags); } return $string;
	}

	/***************************************************************************/
	/****************** GET FEATURED PAGE **************************************/
	/***************************************************************************/
	public function getFeaturedPages(){
		if(!have($this->featured_pages)){ return false; }
		foreach($this->featured_pages as $page_id => $page_array){ $this->featured_pages[$page_id]['path'] = $this->paths_by_id[$page_id]; }
		return $this->featured_pages;
	}
	
	/***************************************************************************/
	/****************** GET PAGE FRIENDS ***************************************/
	/***************************************************************************/	
	public function getPageFriends(){
		if(empty($this->current_page)){ return null; }
		return mysql_query_on_key(" SELECT * FROM dzpro_friend_to_page LEFT JOIN dzpro_friends USING ( dzpro_friend_id ) WHERE dzpro_page_id = " . (int)$this->current_page['dzpro_page_id'] . " ORDER BY dzpro_friend_orderfield ASC ", 'dzpro_friend_id');		
	}
	
	/***************************************************************************/
	/****************** GET PAGE FROM ID ***************************************/
	/***************************************************************************/
	public function getPagePathFromId($page_id = null){
		if(have($this->paths_by_id[$page_id])){ return $this->paths_by_id[$page_id]; }
		return null;
	}

	/***************************************************************************/
	/****************** GET PAGE SlIDES ****************************************/
	/***************************************************************************/	
	public function getPageSlides(){
		if(empty($this->current_page)){ return null; }
		return mysql_query_on_key(" SELECT * FROM dzpro_page_slide_to_page LEFT JOIN dzpro_page_slides USING ( dzpro_page_slide_id ) WHERE dzpro_page_id = " . (int)$this->current_page['dzpro_page_id'] . " ORDER BY dzpro_page_slides.dzpro_page_slide_orderfield ASC ", 'dzpro_page_slide_id');
	}

	/***************************************************************************/
	/****************** GET PAGE ALBUMS ****************************************/
	/***************************************************************************/
	public function getPageAlbums(){
		if(empty($this->current_page)){ return null; }
		return mysql_query_on_key(" SELECT * FROM dzpro_albums LEFT JOIN dzpro_album_to_page USING ( dzpro_album_id ) WHERE dzpro_page_id = " . (int)$this->current_page['dzpro_page_id'] . " ORDER BY dzpro_album_orderfield ", 'dzpro_album_id');
	}

	/***************************************************************************/
	/****************** GET PAGE IMAGES ****************************************/
	/***************************************************************************/	
	public function getPageImages($limit = 12){
		if(empty($this->current_page)){ return null; }
		return mysql_query_on_key(" SELECT * FROM dzpro_albums LEFT JOIN dzpro_album_items USING ( dzpro_album_id ) LEFT JOIN dzpro_album_to_page USING ( dzpro_album_id ) WHERE dzpro_album_item_id IS NOT NULL AND dzpro_page_id = " . (int)$this->current_page['dzpro_page_id'] . " GROUP BY dzpro_album_item_id ORDER BY dzpro_album_item_orderfield ASC LIMIT 0, " . (int)$limit, 'dzpro_album_item_id');
	}

	/***************************************************************************/
	/****************** GET PAGE IMAGES ****************************************/
	/***************************************************************************/	
	public function getPageForms($limit = 12){
		if(empty($this->current_page)){ return null; }
		return mysql_query_on_key(" SELECT * FROM dzpro_forms LEFT JOIN dzpro_form_fields USING ( dzpro_form_id ) LEFT JOIN dzpro_form_to_page USING ( dzpro_form_id ) WHERE dzpro_page_id = " . (int)$this->current_page['dzpro_page_id'] . " GROUP BY dzpro_form_field_id ORDER BY dzpro_form_field_orderfield ASC LIMIT 0, " . (int)$limit, 'dzpro_form_field_id');
	}

}
?>