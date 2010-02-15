<?php

require_once('IPluggable.php');

/**
 * xf_wp_IAdminPage interface
 *
 * @package xf
 * @subpackage wp
 */
interface xf_wp_IAdminPage extends xf_wp_IPluggable {
	
	/**
	 * Sets the menu and all the children to the same capability
	 *
	 * @param string $cap Role, Cabability, or Level to set to
	 * @return void
	 */
	public function setCapabilities( $cap = null );
	
	/**
	 * Called if the page will be the current page rendered.
	 * Also before any of the WordPress admin has been rendered.
	 *
	 * @return void
	 */
	public function onBeforeRender();
	
	// This is callback that actually prints out the content of the page.
	public function render();
	
	// called by user defined method
	public function doStateField( $state, $echo = true );
	
	// NODE METHODS
	
	/**
	 * Checks if shortName is a child page object
	 *
	 * @return bool
	 */
	public function hasChildByName( $shortName );
	
	/**
	 * Checks if object is a child page object
	 *
	 * @return bool
	 */
	public function hasChild( xf_wp_AAdminPage $obj );
	
	/**
	 * Add a child page to this object by name
	 *
	 * @return xf_wp_AAdminPage|false
	 */
	public function &addChildByName( $shortName, xf_wp_AAdminPage &$obj );
	
	/**
	 * Add a child page to this object
	 *
	 * @return xf_wp_AAdminPage|false
	 */
	public function &addChild( xf_wp_AAdminPage &$obj );
	
	/**
	 * Adds children to this object
	 *
	 * @return void
	 */
	public function addChildren( &$pages );
	
	/**
	 * Remove a child page from this object by name
	 *
	 * @return xf_wp_AAdminPage|false
	 */
	public function removeChildByName( $shortName );
	
	/**
	 * Remove a child page from this object
	 *
	 * @return xf_wp_AAdminPage|false
	 */
	public function removeChild( xf_wp_AAdminPage $obj );
	
	/**
	 * Remove the default child without setting it back to normal.
	 * It just removes the child altogether.
	 * return: the removed page object, or false;
	 *
	 * @return xf_wp_AAdminPage|false
	 */
	public function removeDefaultChild();
	
	// RESERVED PROPERTIES
	
	// rendered
	public function get__rendered();
	// state
	public function get__state();
	// pageURI
	public function get__pageURI();
	// isChild
	public function get__isChild();
	// isDefault
	//public function get__isDefault(); Had this as a getter, but made more sense as a public member
	// hasChildren
	public function get__hasChildren();
	// children
	public function &get__children();
	// hasDefaultChild
	public function get__hasDefaultChild();
	// menuCallback
	public function get__menuCallback();
	// defaultChild
	public function &get__defaultChild();
	public function set__defaultChild( xf_wp_AAdminPage &$v );
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function index();
}
// END interface
?>