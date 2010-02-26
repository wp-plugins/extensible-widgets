<?php
/**
 * This file defines xf_wp_IAdminController, an interface 
 * for WordPress admin page controllers.
 * 
 * PHP version 5
 * 
 * @package    xf
 * @subpackage wp
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

require_once('IPluggable.php');

/**
 * xf_wp_IAdminController interface
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage wp
 */
interface xf_wp_IAdminController extends xf_wp_IPluggable {
	
	/**
	 * Sets the menu and all the children to the same capability
	 *
	 * @param string $cap Role, Cabability, or Level to set to
	 * @return void
	 */
	public function setCapabilities( $cap = null );
	
	/**
	 * Called if the controller will be the current controller rendered.
	 * Also before any of the WordPress admin has been rendered.
	 *
	 * @return void
	 */
	public function onBeforeRender();
	
	// This is callback that actually prints out the content of the controller.
	public function render();
	
	// called by user defined method
	public function doStateField( $state, $echo = true );
	
	// NODE METHODS
	
	/**
	 * Checks if shortName is a child controller object
	 *
	 * @return bool
	 */
	public function hasChildByName( $shortName );
	
	/**
	 * Checks if object is a child controller object
	 *
	 * @return bool
	 */
	public function hasChild( xf_wp_AAdminController $obj );
	
	/**
	 * Add a child controller to this object by name
	 *
	 * @return xf_wp_AAdminController|false
	 */
	public function &addChildByName( $shortName, xf_wp_AAdminController &$obj );
	
	/**
	 * Add a child controller to this object
	 *
	 * @return xf_wp_AAdminController|false
	 */
	public function &addChild( xf_wp_AAdminController &$obj );
	
	/**
	 * Adds children to this object
	 *
	 * @return void
	 */
	public function addChildren( &$controllers );
	
	/**
	 * Remove a child controller from this object by name
	 *
	 * @return xf_wp_AAdminController|false
	 */
	public function removeChildByName( $shortName );
	
	/**
	 * Remove a child controller from this object
	 *
	 * @return xf_wp_AAdminController|false
	 */
	public function removeChild( xf_wp_AAdminController $obj );
	
	/**
	 * Remove the default child without setting it back to normal.
	 * It just removes the child altogether.
	 * return: the removed controller object, or false;
	 *
	 * @return xf_wp_AAdminController|false
	 */
	public function removeDefaultChild();
	
	// RESERVED PROPERTIES
	
	// rendered
	public function get__rendered();
	// state
	public function get__state();
	// controllerURI
	public function get__controllerURI();
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
	public function set__defaultChild( xf_wp_AAdminController &$v );
	
	/**
	 * State called by corresponding submited or preset state
	 * This method name should correspond with the DEFAULT_STATE constant value
	 *
	 * @return void
	 */
	public function index();
}
// END interface
?>