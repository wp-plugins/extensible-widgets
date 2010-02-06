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
require_once('xf/wp/ASingleton.php');

// Debugging purposes only
xf_errors_Error::setDebug(true);

/**
 * Main Application class for the WordPress Plugin Extensible Widgets
 * This class is the only object in this plugin to be instantiated globally.
 *
 * @package WordPress
 * @since 2.9
 * @author Jim Isaacs <jim@jimisaacs.com>
 */
class wpew extends xf_wp_ASingleton {
	
	// STATIC MEMBERS
	
	/**
	 * @see xf_wp_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return xf_patterns_ASingleton::getSingleton(__CLASS__);
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * @var string $version The current version of the plugin
	 */
	public $version = '1.0';
	
	/**
	 * @var string $capability The name of the main capability of this plugin
	 */
	public $capability = 'manage_plugin_wpew';
	
	/**
	 * @ignore
	 */
	public $_post;
	
	/**
	 * Install the plugin
	 *
	 * @return void
	 */
	public function install() {
		$this->settings = $this->defaultSettings;
		// For initial registration, right now I'm keeping it with nothing registered
		/*$registration = array(
			'wpew_widgets_Content' => true,
			'wpew_widgets_QueryPostsExtended'=> true
		);
		$this->widgets->registration = $registration;*/
	}
	
	/**
	 * @see xf_wp_APluggable::init()
	 */
	public function init() {		
		// Set the root for this and all extensions to this.
		// Yes there's potential for infinite loops, just don't do it, this is normal.
		xf_wp_APluggable::$_root =& $this;
		
		// Save the post data because WordPress likes to unset certain values it sets in the admin and so forth
		// This is a quick fix of course, I could just make sure I save all the necessary variables it does unset.
		$this->_post = $_POST;
		
		// Instantiate extension
		require_once('xf/source/Loader.php');
		$this->extend->loader = new xf_source_Loader( xf_system_Path::join( WP_PLUGIN_DIR, 'wpew', 'includes' ) );
		
		// Instantiate extension
		require_once('wpew/Widgets.php');
		$this->extend->widgets =& wpew_Widgets::getInstance();
		
		// Add Hooks
		$this->addLocalAction( 'initiated' );
	}
	
	/**
	 * WordPress Admin Fork
	 *
	 * @see parent::admin()
	 */
	public function admin() {
		// Instantiate the admin extension
		require_once('wpew/Admin.php');
		$this->extend->admin =& wpew_Admin::getInstance();
	}
	
	/**
	 * WordPress Client Fork
	 *
	 * @see parent::client() 
	 */
	public function client() {}
	
	/**
	 * Action Hook - wpew_initiated
	 *
	 * @return void
	 */
	public function initiated() {
		// For ajax calls do these actions after wpew, and all extensions have initiated, but before WordPress has initiated.
		// This is because we can hook and manipulate things that normally WordPress does not allow hooks for.
		if( !empty($this->_post['action']) ) {
			do_action( 'wpew_ajax_' . xf_wp_APluggable::sanitizeShortName( $this->_post['action'] ) );
		}
	}
	
	// RESERVED PROPERTIES
	
	/**
	 * @property-read array $defaultSettings Retieves this plugin's default settings
	 */
	public function get__defaultSettings() {
		$settings = array(
			'roles' => array('administrator')
		);
		$widgetsDir = xf_system_Path::join( $this->includeRoot, $this->widgets->dirWidgets );
		$settings['widgetsDir'] = xf_system_Path::replace( $widgetsDir, ABSPATH );
		return $settings;
	}
	
	/**
	 * @property array $settings Option holding all the global settings for this plugin
	 */
	public function &get__settings() {
		$v =& get_option( $this->getOptionName('settings') );
		if( empty($v) ) return false;
		return $v;
	}
	public function set__settings( $v ) {
		$v['version'] = $this->version;
		update_option( $this->getOptionName('settings'), $v );
	}
}
?>