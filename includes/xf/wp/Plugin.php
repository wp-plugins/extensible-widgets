<?php

require_once('APluggable.php');

/**
 * @package xf
 * @subpackage wp
 */
abstract class xf_wp_Plugin extends xf_wp_APluggable {
	
	// INSTANCE MEMBERS
	
	/**
	 * @see xf_wp_IPluggable::__construct()
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * @see xf_wp_APluggable::init()
	 */
	public function init() {}
	
	/**
	 * @see xf_wp_IPluggable::admin()
	 */
	public function admin() {}
	
	/**
	 * @see xf_wp_IPluggable::client()
	 */
	public function client() {}
}
?>