<?php
/**
 * This file defines wpew_Admin, the administrative class for the overall plugin.
 * 
 * PHP version 5
 * 
 * @package wpew
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @link http://jidd.jimisaacs.com
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * Adds additional functionality while within the WordPress admin
 * The WordPress admin does not provide very great hooks for admin controllers, this is a remedy for that.
 * It also puts the wpew admin css in the queue, and adds the base admin menu for wpew.
 *
 * @package wpew
 */
class wpew_Admin extends xf_wp_AExtension {
	
	// STATIC MEMBERS
	
	/**
	 * @see xf_wp_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return xf_patterns_ASingleton::getSingleton(__CLASS__);
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * @var string $adminPage The current admin file loaded in the WordPress admin
	 */
	public $adminPage = '';
	/**
	 * @var string $pluginPage The current plugin page loaded in the WordPress admin
	 */
	public $pluginPage = '';
	
	/**
	 * @see xf_wp_IPluggable::init()
	 */
	public function init() {
		// Do everything through this hook to make sure this object has been set as an extension
		$this->addLocalAction( 'onSetReference' );
	}
	
	/**
	 * Action Hook - wpew_Admin_onSetReference
	 *
	 * @return void
	 */
	public function onSetReference() {
		// The Admin Menu
		$this->addAction( 'admin_menu' );
		$this->addAction( 'wpew_admin_admin-ajax.php', 'admin_ajax_Override' );
		$this->addAction( 'wpew_admin_widgets.php', 'widgets_Override' );
		// Queue up the admin scripts for this package
		$this->queueScript( 'jquery_ajaxify', array('jquery'), array(
			'filename' => 'jquery.ajaxify-0.4.js',
			'version' => '0.4'
		));
		$this->queueScript( 'wpew_admin', array('jquery_ajaxify'), array(
			'filename' => 'admin.js',
			'version' => '1.0'
		));
		$this->queueScript( 'wpew_widgets_admin', array('jquery'), array(
			'filename' => 'admin_widgets.js',
			'version' => '1.0'
		));
		// Queue up the admin css style for this package
		$this->queueStyle( 'wpew_admin', false, array(
			'filename' => 'admin.css',
			'version' => '1.0'
		));
		
		// Add builtInPage or pluginPage to any and all admin page hooks
		$page = basename( $_SERVER['SCRIPT_NAME'] );
		// check if the member isn't empty
		if( !empty( $page ) ) {
			$this->adminPage = $page;
			// run the action attributed to the builtin WordPress admin page
			$this->doLocalAction( $this->adminPage );
			// is it a plugin page too, if so do action too
			if( $this->adminPage == 'admin.php' ) {
				$this->pluginPage = ( isset( self::$get['page'] ) ) ? urldecode( self::$get['page'] ) : false;
				if( !empty( $this->pluginPage ) ) $this->doLocalAction( self::joinShortName( 'plugin', $this->pluginPage ) );
			}
		}
	}
	
	/**
	 * Action Hook - wpew_admin_builtin_admin-ajax.php
	 *
	 * @return void
	 */
	public function admin_ajax_Override() {
		// Don't need to do anything if no widgets are registered
		if( !$this->plugin->widgets->registration ) return;
		$this->plugin->addExtension( 'override', wpew_admin_WidgetsAjaxOverride::getInstance() );
	}
	
	/**
	 * Action Hook - wpew_admin_builtin_widgets.php
	 *
	 * @return void
	 */
	public function widgets_Override() {
		// Don't need to do anything if no widgets are registered
		if( !$this->plugin->widgets->registration ) return;
		$this->plugin->addExtension( 'override', wpew_admin_WidgetsOverride::getInstance() );
	}
	
	/**
	 * Action Hook - admin_menu
	 *
	 * @return void
	 */
	public function admin_menu() {
		// Build admin menu
		$menu = wpew_admin_AdminMenu::getInstance();
		$this->plugin->addExtension( 'menu', $menu );
	}
}
?>