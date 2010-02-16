<?php

require_once(dirname(__FILE__).'/../xf/wp/ASingleton.php');
require_once(dirname(__FILE__).'/../xf/patterns/ASingleton.php');

/**
 * Adds additional functionality while within the WordPress admin
 * The WordPress admin does not provide very great hooks for admin pages, this is a remedy for that.
 * It also puts the wpew admin css in the queue, and adds the base admin menu for wpew.
 *
 * @package wpew
 */
class wpew_Admin extends xf_wp_ASingleton {
	
	// STATIC MEMBERS
	
	/**
	 * @see xf_wp_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return xf_patterns_ASingleton::getSingleton(__CLASS__);
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * @var object $override The current admin override object that is in action
	 */
	public $override = null;
	/**
	 * @var string $adminPage The current admin file loaded in the WordPress admin
	 */
	public $adminPage = '';
	/**
	 * @var string $pluginPage The current plugin page loaded in the WordPress admin
	 */
	public $pluginPage = '';
	
	/**
	 * @see xf_wp_APluggable::init()
	 */
	public function init() {
		// Add hook to make sure everything in the plugin has initiated
		$this->addAction( 'wpew_onAdminInitiated' );
	}
	
	/**
	 * Action Hook - wpew_onAdminInitiated
	 *
	 * @return void
	 */
	public function wpew_onAdminInitiated() {
		// The Admin Menu
		$this->addAction( 'admin_menu' );
		$this->addAction( 'wpew_admin_admin-ajax.php', 'admin_ajax_Override' );
		$this->addAction( 'wpew_admin_widgets.php', 'widgets_Override' );
		// Queue up the admin scripts for this package
		$this->queueScript( 'jquery_ajaxify', array('jquery'), array(
			'path' => $this->pluginRootURI . '/js',
			'filename' => 'jquery.ajaxify-0.4.js',
			'version' => '0.4'
		));
		$this->queueScript( 'wpew_admin', array('jquery_ajaxify'), array(
			'path' => $this->pluginRootURI . '/js',
			'filename' => 'admin.js',
			'version' => '1.0'
		));
		$this->queueScript( 'wpew_widgets_admin', array('jquery'), array(
			'path' => $this->pluginRootURI . '/js',
			'filename' => 'admin_widgets.js',
			'version' => '1.0'
		));
		// Queue up the admin css style for this package
		$this->queueStyle( 'wpew_admin', false, array(
			'path' => $this->pluginRootURI . '/css',
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
				$this->pluginPage = ( isset( $_GET['page'] ) ) ? urldecode( $_GET['page'] ) : false;
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
		if( !$this->widgets->registration ) return;
		require_once('admin/WidgetsAjaxOverride.php');
		$this->override =& wpew_admin_WidgetsAjaxOverride::getInstance();
	}
	
	/**
	 * Action Hook - wpew_admin_builtin_widgets.php
	 *
	 * @return void
	 */
	public function widgets_Override() {
		// Don't need to do anything if no widgets are registered
		if( !$this->widgets->registration ) return;
		require_once('admin/WidgetsOverride.php');
		$this->override =& wpew_admin_WidgetsOverride::getInstance();
	}
	
	/**
	 * Action Hook - admin_menu
	 *
	 * @return void
	 */
	public function admin_menu() {
	
		// Build admin menu
		require_once('admin/wpew_Page.php');
		$menu = new wpew_admin_wpew_Page();
		
		require_once('admin/Registration_Page.php');
		$menu->addChild( new wpew_admin_Registration_Page() );
		
		require_once('admin/Settings_Page.php');
		$menu->addChild( new wpew_admin_Settings_Page() );
		
		require_once('admin/Export_Page.php');
		$menu->addChild( new wpew_admin_Export_Page() );
		
		require_once('admin/Import_Page.php');
		$menu->addChild( new wpew_admin_Import_Page() );
		
		require_once('admin/Uninstall_Page.php');
		$menu->addChild( new wpew_admin_Uninstall_Page() );
		
		// Set all the capabilities
		$menu->setCapabilities( $this->root->capability );
		// Finally build the menu adding to WordPress admin
		$menu->build();
		
		// do the local action so more menus may be added after this one
		$this->doLocalAction( 'onMenu' );
	}
}
?>