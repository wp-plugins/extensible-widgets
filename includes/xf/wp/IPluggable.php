<?php
/**
 * This file defines xf_wp_IPluggable, an interface for objects
 * that hook into the WordPress filter/action system.
 * 
 * PHP version 5
 * 
 * @package    xf
 * @subpackage wp
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

/**
 * Instances of classes implementing this interface must define these methods.
 * They are called from within the xf_wp_APluggable class in the contructor.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage wp
 */
interface xf_wp_IPluggable {
	
	/**
	 * Object initiated
	 *
	 * This method is called before the admin/client fork.
	 *
	 * @return void 
	 */
	public function init();
	
	/**
	 * WordPress Admin Fork
	 *
	 * This method is only called when within from the back-end or admin side of WordPress.
	 *
	 * @return void 
	 */
	public function admin();
	
	/**
	 * WordPress Client Fork
	 *
	 * This method is only called when within from the front-end or admin side of WordPress.
	 *
	 * @return void 
	 */
	public function client();
}
?>