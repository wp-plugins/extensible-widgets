<?php
/**
 * This file defines xf_wp_AAdminMenu, an abstract 
 * controller for WordPress admin menus.
 * 
 * PHP version 5
 * 
 * @package    xf
 * @subpackage wp
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

require_once('AAdminController.php');

/**
 * This is an abstract class and is meant to be extended.
 * This is the admin menu manager for the WordPress admin.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage wp
 */
abstract class xf_wp_AAdminMenu extends xf_wp_AAdminController {
	
	// STATIC MEMBERS
	
	/**
	 * Adds a controller of the menu manager instance to the admin menu.
	 * This is a wrapper around the WordPress add_menu_page() function.
	 * This function takes a controller object and sends that data to the WordPress function.
	 *
	 * @param xf_wp_AdminMenu $menu  The menu manager instance that is adding the controller
	 * @return bool
	 */
	protected static function addMenu( xf_wp_AAdminMenu &$menu ) {
		$function = 'add_'.$menu->type.'_page';
		if( is_callable($function) ) {
			$args =  array( $menu->title, $menu->menuTitle, $menu->capability, $menu->routeString, $menu->menuCallback, $menu->iconURI );
			return $menu->hookname = call_user_func_array( $function, $args );
		}
		return null;
	}
	
	/**
	 * Adds a controller child of the menu manager instance to an admin menu controller as a sub controller.
	 * This is a wrapper around the WordPress add_submenu_page() function.
	 * This function takes a controller object and sends that data to the WordPress function.
	 *
	 * @param xf_wp_AdminMenu $menu The menu manager instance that is adding the controller
	 * @param xf_wp_AAdminController $parent The controller of the menu manager that is the parent controller of the controller that will be added.
	 * @param xf_wp_AAdminController $controller The controller of the menu manager that will be added
	 * @param bool $addChild Whether or not to automatically add the child to the parent's children
	 * @return bool
	 */
	protected static function addToMenu( xf_wp_AAdminMenu &$menu, xf_wp_AAdminController &$controller, $addChild = true ) {
		if( $addChild && !$menu->hasChild($controller) ) $menu->addChild( $controller );
		if( $controller->isDefault && !$menu->hasDefaultChild ) {
			$menu->defaultChild = $controller;
		} 
		return $controller->hookname = add_submenu_page( $menu->routeString, $controller->title.' &lsaquo; '.$menu->title, $controller->menuTitle, $controller->capability, $controller->routeString, $controller->menuCallback );
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * @var string $type This specifies the type of this menu and thus where to add it within the overall WordPress admin menu
	 */
	public $type = 'menu';
	
	/**
	 * @var string $iconURI This specifies the url of the icon to use for this menu, relative to the pluginRootURI
	 */
	public $iconURI = null;
	
	/**
	 * @see xf_wp_ASingleton::__construct()
	 */
	public function __construct( $unregistrable = __CLASS__ )
	{
		parent::__construct( $unregistrable );
	}
	
	/**
	 * Actually builds the menu adding this instance's and the children's callbacks to WordPress's admin menu system.
	 * Before calling this method this menu remains not connected to WordPress, and will not output.
	 *
	 * @return void
	 */
	final public function build() {
		$this->doLocalAction( 'onBuildStart' );
		// First add this
		self::addMenu( $this );
		// Now if there are children, add them (must be done with internal iderator loop)
		if( !$this->hasChildren ) return;
		reset($this->_children);
		if( !$this->hasDefaultChild ) {
			$first =& current($this->_children);
			$this->defaultChild = $first;
		}
		do {
			$child = current($this->_children);
			self::addToMenu( $this, $child, false );
		} while( next($this->_children) !== false );
		$this->doLocalAction( 'onBuildComplete' );
		// The currentController will be be empty when not on any page corresponding to this menu
		if( !is_null($this->currentController) ) $this->currentController->doLocalAction( 'onBeforeRender' );
	}
	
	// RESERVED PROPERTIES
	
	/**
	 * @property-read string $currentRouteString Get's the current controller name set as 'page' as GET varaible if within the admin controller
	 *
	 * It is retrieved as the POST varaible 'option_page' from the options.php file.
	 * This input is set as a hidden variable by the settings_fields() function.
	 */
	final public function &get__currentRouteString() {
		if( !empty(self::$get['page']) ) return self::$get['page'];
		if( !empty(self::$post['page']) ) return self::$post['page'];
		return null;
	}
	
	/**
	 * @property-read xf_wp_AAdminController $currentController Get's the current controller object based on $currentRouteString
	 */
	final public function &get__currentController() {
		if( $this->currentRouteString == $this->routeString && $this->hasChildren ) {
			if( $this->hasDefaultChild ) return $this->_defaultChild;
			return $this;
		}
		if( $this->hasChildren ) {
			$parts = array_filter( explode( '/', $this->currentRouteString ) );
			end($parts);
			do {
				$current = current($parts);
				if( $this->hasChildByName( $current ) ) {
					return $this->_children[$current];
				}
			} while( prev($parts) !== false );
		}
		return null;
	}
}
?>