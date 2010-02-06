<?php

require_once('IWP_Widget.php');

/**
 * Most of this and the parent interface is defined in the wpew_AWidget, but not everything.
 *
 * @package wpew
 */
interface wpew_IWidget extends wpew_IWP_Widget {
	
	// STATIC MEMBERS
	
	/**
	 * Called from the form() method it should return an array representing 
	 * all the default settings for this particular widget class.
	 *
	 * @param object $obj the current object instance of the widget class
	 * @return array associative array where keys are setting names and values are the defaults for those settings 
	 */
	public static function getDefaultSettings( &$obj );
	
	/**
	 * Called from the update() method this is intended to add anything 
	 * to the settings before actually updating. Manipulating the settings 
	 * member here would be the usual approach.
	 *
	 * @param object $obj the current object instance of the widget class
	 * @param array $new_settings the current settings data available
	 * @return void
	 */
	public static function save( &$obj, $new_settings );
	
	/**
	 * Called from the form() method this is intended to be anything needed 
	 * to be done before the widget admin output starts. It is usually where 
	 * you define the default settings for the particular widget class.
	 *
	 * @param object $obj the current object instance of the widget class
	 * @return void
	 */
	public static function beforeAdminOutput( &$obj );
	
	/**
	 * Called from the form() method tThis is intended to output the 
	 * actual widget back-end display.
	 *
	 * @param object $obj the current object instance of the widget class
	 * @return void
	 */
	//public static function renderAdmin( &$obj );
	
	/**
	 * Called from the form() method this is intended to be anything needed 
	 * to be done after the widget admin output finishes.
	 *
	 * @param object $obj the current object instance of the widget class
	 * @return void
	 */
	public static function afterAdminOutput( &$obj );
	
	// INSTANCE MEMBERS
	
	/**
	 * Method called by the manager itself when importing the widget 
	 * and BEFORE the widget is instantiated by the Widget_Factory
	 * This is so all widget instances that extend this class will 
	 * have access to the same widget manager.
	 *
	 * @param object $mngr the current manager of all the widgets registered by this framework
	 * @return void
	 */
	public function setManager( &$mngr );
	
	/**
	 * This is called from the base widget class within the framework, 
	 * and from the WordPress action system.
	 *
	 * @return void
	 */
	public function flushWidgetCache();
	
	/**
	 * Called from the widget() method this is intended to be anything 
	 * needed to be done before the widget output starts.
	 *
	 * @return void
	 */
	public function beforeOutput();
	
	/**
	 * Called from the widget() method this is intended to output 
	 * the actual widget front-end display.
	 *
	 * @return void
	 */
	public function render();
	
	/**
	 * Called from the widget() method this is intended to be anything 
	 * needed to be done after the widget output finishes.
	 *
	 * @return void
	 */
	public function afterOutput();
	
	/**
	 * Called from the render() method
	 *
	 * @return void
	 */
	public function defaultOutput();
	
}
?>