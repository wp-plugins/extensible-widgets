<?php
/**
 * This file defines wpew, the application class for WP Extensilble Widgets.
 * 
 * PHP version 5
 * 
 * @package WordPress
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @link http://jidd.jimisaacs.com
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

require_once('xf/errors/Error.php');
require_once('xf/patterns/ASingleton.php');
require_once('xf/wp/APluggable.php');
require_once('xf/wp/APlugin.php');

// Debugging purposes only
xf_errors_Error::setDebug( true );

/**
 * Main Application class for the WordPress Plugin Extensible Widgets
 * This class is the only object in this plugin to be instantiated globally.
 *
 * @package WordPress
 * @since 2.9
 * @author Jim Isaacs <jim@jimisaacs.com>
 */
class wpew extends xf_wp_APlugin {
	
	// STATIC MEMBERS
	
	/**
	 * @see xf_wp_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return xf_patterns_ASingleton::getSingleton(__CLASS__);
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * @see xf_wp_APlugin::$version
	 */
	public $version = '0.9.1';
	
	/**
	 * @see xf_wp_APlugin::$pluginName
	 */
	public $pluginName = 'Extensible Widgets';
	
	/**
	 * Install the plugin
	 *
	 * @return void
	 */
	public function install() {
		if( !$this->currentUserHasCap && is_admin() ) $this->roles = $GLOBALS['current_user']->roles;
		$this->settings = $this->defaultSettings;
		// For initial registration, right now I'm keeping it with nothing registered
		/*$registration = array(
			'wpew_widgets_Content' => true,
			'wpew_widgets_QueryPostsExtended'=> true
		);
		$this->widgets->registration = $registration;*/
	}
	
	/**
	 * @see xf_wp_IPluggable::init()
	 */
	public function init() {
		// Instantiate extension
		require_once('wpew/Widgets.php');
		$this->addExtension( 'widgets', wpew_Widgets::getInstance() );
		// Add Hooks
		$this->addLocalAction( 'onInitiated' );
	}
	
	/**
	 * WordPress Admin Fork
	 *
	 * @see parent::admin()
	 */
	public function admin() {
		// Instantiate the admin extension
		require_once('wpew/Admin.php');
		$this->addExtension( 'admin', wpew_Admin::getInstance() );
	}
	
	/**
	 * WordPress Client Fork
	 *
	 * @see parent::client() 
	 */
	public function client() {}
	
	/**
	 * Action Hook - wpew_onInitiated
	 *
	 * @return void
	 */
	public function onInitiated() {
		// For ajax calls do these actions after wpew, and all extensions have initiated, but before WordPress has initiated.
		// This is because we can hook and manipulate things that normally WordPress does not allow hooks for.
		if( !empty(self::$post['action']) ) {
			$this->doLocalAction( self::joinShortName( 'onAjax', xf_wp_APluggable::sanitizeShortName( self::$post['action'] ) ) );
		}
	}
	
	// RESERVED PROPERTIES
	
	/**
	 * @property-read array $defaultSettings Retieves this plugin's default settings
	 */
	public function get__defaultSettings() {
		$settings = array();
		// Convert to POSIX for easy string manipulation (this method shouldn't be called all the time anyway)
		$widgetsDir = xf_system_Path::toPOSIX( xf_system_Path::join( $this->includeRoot, $this->widgets->dirWidgets ) );
		$settings['widgetsDir'] = xf_system_Path::replace( $widgetsDir, xf_system_Path::toPOSIX(ABSPATH) );
		return $settings;
	}
	
	/**
	 * @property array $settings Option holding all the global settings for this plugin
	 */
	public function &get__settings() {
		$v = get_option( $this->getOptionName('settings') );
		if( empty($v) ) return false;
		return $v;
	}
	public function set__settings( $v ) {
		$v['version'] = $this->version;
		update_option( $this->getOptionName('settings'), $v );
	}
}
?>