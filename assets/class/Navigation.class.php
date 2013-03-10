<?php
class Navigation { 
	
	function __construct($db){
		
		//connect to db
		$this->db = $db;
		
		//assure active session
		assureSession();
		
		//slug pages array
		$this->pages_by_slug = array();
		
		//pages to parent
		$this->pages_by_parent = array();

		//array by ids
		$this->paths_by_id = array();
		
		//featured pages array
		$this->featured_pages = array();

		//need navigation stack
		if(!isset($_SESSION['navigation_stack'])) $_SESSION['navigation_stack'] = array();

		//get clean request string
		$this->request_no_get = self::getRequestUriClean();
		
		//get slug array
		$this->page_slug_array = self::buildPageSlugArray();
		
		//get last page slug
		$this->last_page_slug = self::getLastPageSlug();
			
		//simple pages array
		$this->pages = self::buildNavigationArray();
		
		//stack page array
		$this->pages_stack = self::stackPageArray();
		
		//get menus
		$this->menus = self::buildMenuArray();
		
		//build menu's stack
		$this->menus_stack = self::buildMenuStack();
		
	}

	/***************************************************************************/
	/****************** FIX DOUBLE SLASH PATH **********************************/
	/***************************************************************************/
	protected function fixDoubleSlash($path){
		return preg_replace('/[\/]+/', '/', $path);
	}

	/***************************************************************************/
	/****************** BUILD MENU STACK ***************************************/
	/***************************************************************************/	
	protected function buildMenuStack(){
		if(empty($this->menus)){ return null; }
		$return = array(); 	
		foreach($this->menus as $menu_id => $menu_array){ 
			if(!empty($menu_array)){ 
				foreach($menu_array as $page_id => $page_array){ 
					$return[$menu_id][$page_id]['page'] = $this->pages[$page_id]; 
					$return[$menu_id][$page_id]['active'] = (isset($this->page_slug_array[0]) and $this->page_slug_array[0] == $this->pages[$page_id]['dzpro_page_slug'] or $this->request_no_get == $this->pages[$page_id]['dzpro_page_slug']) ? 1 : 0;
					$return[$menu_id][$page_id]['path'] = self::fixDoubleSlash('/' . $this->pages[$page_id]['path'] . '/'); 
					$return[$menu_id][$page_id]['current'] = ($this->pages[$page_id]['dzpro_page_slug'] == $this->last_page_slug) ? 1 : 0; 
					$return[$menu_id][$page_id]['subpages'] = self::returnPagesForParent($page_id, 1, self::fixDoubleSlash($return[$menu_id][$page_id]['path']));	
				} 
			} 
		} 
		return $return;
	}

	/***************************************************************************/
	/****************** BUILD MENU ARRAY ***************************************/
	/***************************************************************************/
	protected function buildMenuArray(){
		$result = @mysql_query(" SELECT * FROM dzpro_menus LEFT JOIN dzpro_page_to_menu USING ( dzpro_menu_id ) LEFT JOIN dzpro_pages USING ( dzpro_page_id ) WHERE dzpro_pages.dzpro_page_id IS NOT NULL AND dzpro_pages.dzpro_page_on_navigation = 1 ORDER BY dzpro_page_orderfield ASC ") or die(mysql_error()); if(mysql_num_rows($result) > 0){ $return = array(); while($row = mysql_fetch_assoc($result)){ $return[$row['dzpro_menu_id']][$row['dzpro_page_id']] = $row; } mysql_free_result($result); return $return; } return array();
	}

	/***************************************************************************/
	/****************** GET MENU ARRAY *****************************************/
	/***************************************************************************/	
	public function getMenu($menu_identity = null){
		if(empty($menu_identity)){ return null; }
		return isset($this->menus_stack[$menu_identity]) ? $this->menus_stack[$menu_identity] : null;
	}

	/***************************************************************************/
	/****************** GET CLEAN REQUEST URI **********************************/
	/***************************************************************************/
	protected function getRequestUriClean(){
		$uri = (isset($_SERVER['REDIRECT_URL']) and !empty($_SERVER['REDIRECT_URL'])) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'];
		return str_ireplace(array($_SERVER['QUERY_STRING'], '?'), array('', ''), $uri);
	}

	/***************************************************************************/
	/****************** GET LAST PAGE SLUG *************************************/
	/***************************************************************************/	
	protected function buildPageSlugArray(){
		if(empty($this->request_no_get)){ return null; }
		$array = explode('/', $this->request_no_get); $return = array(); foreach($array as $slug){ if(!empty($slug)){ $return[] = $slug; } }
		return $return;
	}
	
	/***************************************************************************/
	/****************** GET LAST PAGE SLUG *************************************/
	/***************************************************************************/	
	protected function getLastPageSlug(){
		if(empty($this->page_slug_array)){ return '/'; }
		return end($this->page_slug_array);
	}

	/***************************************************************************/
	/****************** RECURSIVE RETURN PAGES FOR PARENT **********************/
	/***************************************************************************/	
	protected function returnPagesForParent($parent_id, $depth, $path){
		$return = array(); 
		foreach($this->pages as $page_id => $page){ 
			if($page['dzpro_page_parent_id'] == $parent_id){ 
				if(!isset($this->pages[$page_id]['path'])){ 
					$return[$page_id]['path'] = self::fixDoubleSlash($path . $page['dzpro_page_slug'] . '/'); 
				}else{ 
					$return[$page_id]['path'] = $this->pages[$page_id]['path']; 
				} 
				$return[$page_id]['page'] = $page; 
				$return[$page_id]['active'] = (isset($this->page_slug_array[$depth]) and $this->page_slug_array[$depth] == $page['dzpro_page_slug']) ? 1 : 0; 
				$return[$page_id]['current'] = ($page['dzpro_page_slug'] == $this->last_page_slug) ? 1 : 0; 
				$this->paths_by_id[$page_id] = self::fixDoubleSlash($path . $page['dzpro_page_slug'] . '/'); 
				if(!isset($this->pages[$page_id]['path'])){ 
					$this->pages[$page_id]['path'] = self::fixDoubleSlash($path . $page['dzpro_page_slug'] . '/'); 
				} 
				$return[$page_id]['subpages'] = self::returnPagesForParent($page_id, $depth + 1, self::fixDoubleSlash($return[$page_id]['path']));
			} 
		} 
		return $return;
	}

	/***************************************************************************/
	/****************** STACK PAGE ARRAY ***************************************/
	/***************************************************************************/
	protected function stackPageArray(){
		if(empty($this->pages)){ return null; } return self::returnPagesForParent(0, 0, '/', true);
	}
	
	/***************************************************************************/
	/****************** GET NAVIGATION ARRAY ***********************************/
	/***************************************************************************/
	protected function buildNavigationArray(){
		$result = @mysql_query("SELECT * FROM dzpro_pages WHERE dzpro_page_on_navigation = 1 ORDER BY dzpro_page_orderfield ASC"); if(mysql_num_rows($result) > 0){ $return = array(); while($row = mysql_fetch_assoc($result)){ $return[$row['dzpro_page_id']] = $row; $this->pages_by_slug[$row['dzpro_page_slug']] = $row; if(have($row['dzpro_page_featured']) and $row['dzpro_page_featured'] == 1){ $this->featured_pages[$row['dzpro_page_id']] = $row; } } mysql_free_result($result); return $return; } return array();
	}	
	
	/***************************************************************************/
	/****************** ADD PAGE TO STACK **************************************/
	/***************************************************************************/
	public function addToStack($url = null){
		if($url){ $_SESSION['navigation_stack'][time()] = $url; }
	}
	
	/***************************************************************************/
	/****************** RETURN TO LAST PAGE ************************************/
	/***************************************************************************/
	public function returnToLast(){
		$last_page = (isset($_SESSION['navigation_stack']) and !empty($_SESSION['navigation_stack'])) ? end($_SESSION['navigation_stack']) : '/'; header("Location: " . $last_page); exit(0);
	}

	/***************************************************************************/
	/****************** RETURN LAST PAGE STRING ********************************/
	/***************************************************************************/
	public function returnLastPageUrl(){
		$last_page = (isset($_SESSION['navigation_stack']) and !empty($_SESSION['navigation_stack'])) ? end($_SESSION['navigation_stack']) : '/'; return $last_page;
	}
	
	/***************************************************************************/
	/****************** REMOVE LAST ITEM FROM STACK ****************************/
	/***************************************************************************/
	public function goBack(){
		if(isset($_SESSION['navigation_stack'])){ array_pop($_SESSION['navigation_stack']); }
	}

	/***************************************************************************/
	/****************** GET BREAD CRUMBS ***************************************/
	/***************************************************************************/	
	public function getBreadCrumbs(){
		if(!isset($this->page_slug_array) or empty($this->page_slug_array)){ return array(); } if(!isset($this->pages_by_slug) or empty($this->pages_by_slug)){ return array(); }
		$return = array(); foreach($this->page_slug_array as $key => $slug){ if(isset($this->pages_by_slug[$slug]) and !empty($this->pages_by_slug[$slug])){ $return[$key] = $this->pages_by_slug[$slug]; $return[$key]['path'] = $this->paths_by_id[$this->pages_by_slug[$slug]['dzpro_page_id']]; } }
		return $return;
	}
	
}
?>