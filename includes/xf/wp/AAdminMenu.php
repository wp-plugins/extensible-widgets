<?php

require_once('AAdminPage.php');

/**
 * This is the admin menu manager for the WordPress admin.
 *
 * @package xf 
 * @subpackage wp
 */
abstract class xf_wp_AAdminMenu extends xf_wp_AAdminPage {
	
	// STATIC MEMBERS
	
	/**
	 * Adds a page of the menu manager instance to the admin menu.
	 * This is a wrapper around the WordPress add_menu_page() function.
	 * This function takes a page object and sends that data to the WordPress function.
	 *
	 * @param xf_wp_AdminMenu $menu  The menu manager instance that is adding the page
	 * @return bool
	 */
	protected static function addMenu( xf_wp_AAdminMenu &$menu ) {
		// the pageName is important, because it is what is checked later to see if this page has been added to a menu
		// this is also the name of the page in the WordPress file admin.php. so it is really "admin.php?page=$page->pageName"
		if( isset( $menu->pageName ) ) return false;
		$menu->pageName = $menu->shortName;
		$added = add_menu_page( $menu->title, $menu->menuTitle, $menu->capability, $menu->pageName, $menu->menuCallback );
		return $added;
	}
	
	/**
	 * Adds a page child of the menu manager instance to an admin menu page as a sub page.
	 * This is a wrapper around the WordPress add_submenu_page() function.
	 * This function takes a page object and sends that data to the WordPress function.
	 *
	 * @param xf_wp_AdminMenu $menu The menu manager instance that is adding the page
	 * @param xf_wp_AAdminPage $parentPage The page of the menu manager that is the parent page of the page that will be added.
	 * @param xf_wp_AAdminPage $page The page of the menu manager that will be added
	 * @param bool $addChild Whether or not to automatically add the child to the parent's children
	 * @return bool
	 */
	protected static function addToMenu( xf_wp_AAdminMenu &$menu, xf_wp_AAdminPage &$page, $addChild = true ) {
		// the pageName is important, because it is what is checked later to see if this page has been added to a menu
		// this is also the name of the page in the WordPress file admin.php. so it is really "admin.php?page=$page->pageName"
		if( isset( $page->pageName ) ) return false;
		if( $page->isDefault ) {
			$page->pageName = $menu->pageName;
			if( $addChild ) $menu->addChildByName( 'default', $page );
		} else {
			$page->pageName = $page->shortName;
			if( $addChild ) $menu->addChild( $page );
		}
		$added = add_submenu_page( $menu->pageName, $page->title, $page->menuTitle, $page->capability, $page->pageName, $page->menuCallback );
		return $added;
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * Actually builds the menu adding this instance's and the children's callbacks to WordPress's admin menu system.
	 * Before calling this method this menu remains not connected to WordPress, and will not output.
	 *
	 * @return void
	 */
	final public function build() {
		$this->doLocalAction( 'buildStart' );
		// First add this
		self::addMenu( $this );
		
		// Now if there are children, add them (must be done with internal iderator loop)
		if( !$this->hasChildren ) return;
		reset($this->_pages);
		if( !$this->hasDefaultChild ) {
			$first =& current($this->_pages);
			$first->isDefault = true;;
		}
		do {
			$child =& current($this->_pages);
			self::addToMenu( $this, $child, false );
			if( $this->currentPageName == $child->pageName ) $pn = key($this->_pages);
		} while( next($this->_pages) !== false );
		$this->doLocalAction( 'buildComplete' );
		
		if(isset($pn)) $this->_pages[$pn]->doLocalAction( 'beforeRender' );
	}
	
	/**
	 * @see xf_wp_IAdminPage::addChildByName();
	 */
	final public function &addChildByName( $shortName, xf_wp_AAdminPage &$obj ) {
		parent::addChildByName( $shortName, $obj );
	}
	
	// RESERVED PROPERTIES
	
	/**
	 * @property-read string $currentPageName Get's the current page name set as 'page' as GET varaible if within the admin page
	 *
	 * It is retrieved as the POST varaible 'option_page' from the options.php file.
	 * This input is set as a hidden variable by the settings_fields() function.
	 */
	public function &get__currentPageName() {
		if( !empty($_GET['page']) ) return $_GET['page'];
		if( !empty($_POST['page']) ) return $_POST['page'];
		return null;
	}
}
?>