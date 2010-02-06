<?php
/**
 * Instances of classes implementing this interface must define these method.
 * They are called from within the xf_wp_APluggable class in the contructor.
 *
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