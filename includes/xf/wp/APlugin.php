<?php
/**
 * This file defines xf_wp_APlugin, a wrapper around WordPress plugins.
 * 
 * PHP version 5
 * 
 * @package    xf
 * @subpackage wp
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

require_once(dirname(__FILE__).'/../source/Loader.php');
require_once(dirname(__FILE__).'/../system/Path.php');
require_once('ASingleton.php');

/**
 * This is an abstract class and is meant to be extended.
 *
 * This is the main class to wrap WordPress plugin functionality.
 * It may be extended by the use of xf_wp_AExtension objects.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage wp
 */
abstract class xf_wp_APlugin extends xf_wp_ASingleton {
	
	// STATIC MEMBERS
	
	/**
	 * @ignore
	 * Although this is a static property, is uses keys of the classes that manipulate it
	 * This holds singleton properties that are specific to the the instance that called xf_wp_APlugin::addExtension()
	 */
	private static $_extensions = array();
	
	/**
	 * @ingore
	 * @var WP_Roles $_wpRoles Object for role/capability management (should only need one)
	 */
	private static $_wpRoles;
	
	/**
	 * Easy on the use static methods, if you want to wrap a plugin in a PHP 4 bootstrap
	 */
	 
	// INSTANCE MEMBERS
	
	/**
	 * @var string $version The current version of the plugin
	 */
	public $version = '1.0';
	
	/**
	 * @var string $pluginName The name of this plugin
	 */
	public $pluginName = 'X Framework Plugin';
	
	/**
	 * @var string $pluginFile The file that this plugin object is associated with
	 */
	public $pluginFile;
	
	/**
	 * @var string $capability The capability name of this plugin (Used to add and remove from roles as needed)
	 */
	public $capability;
	
	/**
	 * @ingore
	 * @var array $_roles Array of role objects that have this plugin's capability using the role names as keys
	 */
	private $_roles;
	
	/**
	 * @var string Name of the directory that stores images for this plugin.
	 */
	public $dirImages = 'images';
	/**
	 * @var string Name of the directory that stores JavaScript for this plugin.
	 */
	public $dirScripts = 'js';
	/**
	 * @var string Name of the directory that stores CSS stylesheets for this plugin.
	 */
	public $dirStyles = 'css';
	/**
	 * @var string Name of the directory that stores all cached files for this plugin.
	 */
	public $dirCache = 'cache';
	
	/**
	 * @see xf_wp_ASingleton::__construct()
	 */
	final public function __construct()
	{
		// Set the WP_Roles object for role/capability management
		if( !isset(self::$_wpRoles) ) self::$_wpRoles = new WP_Roles();
		
		// Set this plugin's capability name
		$this->capability = (isset($this->capability)) ? $this->capability : self::joinShortName( 'manage', 'plugin', strtolower($this->className) );			
		// set the common extensions object
		if( !isset(self::$_extensions[$this->className]) ) self::$_extensions[$this->className] = array();
		
		// Instantiate the loader extension
		// The include base should be 3 directories up from this file
		// Yes this is a bit janky, I need to make this based on the plugin basename from outside of this class, was trying to do to much here.
		$loader = new xf_source_Loader( dirname(dirname(dirname(__FILE__))) );
		$this->addExtension( 'loader', $loader );
		
		// parent constructor
		parent::__construct( __CLASS__ );
	}
	
	/**
	 * Call this method to when activating the plugin this object represents
	 *
	 * @param array $roles An array of role names to add this plugin's capability to
	 * @return void
	 */
	final public function activate() {
	}
	
	/**
	 * Call this method to when deactivating the plugin this object represents
	 *
	 * @return void
	 */
	final public function deactivate() {
	}
		
	/**
	 * Checks to see if there is an extension registered under the given name
	 *
	 * @param string $n The name of the extension to check
	 * @return bool
	 */
	final public function isExtension( $n ) {
		return isset( self::$_extensions[$this->className][$n] );
	}
	
	/**
	 * Adds and extension object to this plugin
	 *
	 * @param string $n The name of the extension to add
	 * @param object $object The extension object the name will reference
	 * @return void
	 */
	final public function addExtension( $n, &$object ) {
		self::$_extensions[$this->className][$n] =& $object;
		if( method_exists($object,'setReference') ) {
			$object->setReference( $this );
		}
		$this->doLocalAction( self::joinShortName( 'onAddExtension', $n ) );
	}
	
	/**
	 * Removes and extension object from this plugin
	 *
	 * @param string $n The name of the extension to remove
	 * @return bool
	 */
	final public function removeExtension( $n ) {
		if( !$this->isExtension($n) ) return false;
		if( self::$_extensions[$this->className][$n] instanceof xf_wp_Extension ) {
			self::$_extensions[$this->className][$n]->removeReference();
		}
		$this->doLocalAction( self::joinShortName( 'onRemoveExtension', $n ) );
		unset(self::$_extensions[$this->className][$n]);
		return true;
	}
	
	/**
	 * Magic Override - Define where to check for magic properties
	 * This first checks to see if property is an extension
	 * If it's an extension, then it returns true
	 *
	 * @param string $n The property name
	 * @return bool
	 */
	public function __isset( $n ) {
		if( $this->isExtension($n) ) return true;
		return parent::__isset( $n );
	}
	
	/**
	 * Magic Override - Define where to delete magic properties from
	 * This first checks to see if property is an extension
	 * If it's an extension, then it removes that extension
	 *
	 * @param string $n The property name
	 * @return void
	 */
	public function __unset( $n ) {
		if( $this->isExtension($n) ) {
			$this->removeExtension( $n );
		} else {
			parent::__unset( $n );
		}
	}
	
	/**
	 * Magic Override - Define where to add magic properties
	 * This first checks to see if property is an extension
	 * If it's an extension, it fails because they are read-only from outside of the class
	 *
	 * @param string $n The property name
	 * @param mixed $v The property value
	 * @return void
	 */
	public function __set( $n, $v ) {
		if( $this->isExtension($n) ) {
			$this->error( 'Failed setting property "<strong>' . $n . '</strong>" on plugin class <strong>'. $this->className. '</strong>. This property is registered as an extension.' );
		} else {
			parent::__set( $n, $v );
		}
	}
	
	/**
	 * Magic Override - Define where to retrieve magic properties from
	 * This first checks to see if property is an extension
	 * If it's an extension, it returns that
	 *
	 * @param string $n Name of the undefined property
	 * @return mixed
	 */
	public function &__get( $n ) {
		if( $this->isExtension($n) ) return self::$_extensions[$this->className][$n];
		return parent::__get( $n );
	}
	
	/**
	 * This is a wrapper around WordPress's wp_register_script()
	 * The difference here is that it uses more concise and useful arguments in the $args paramter.
	 *
	 * @return bool false if script is already registered, or true if it is not
	 */
	public function registerScript( $handle, $dependencies = array(), $args = array() ) {
		if( wp_script_is( $handle, 'registered' ) ) return false;
		$defaults = array(
			'path' => false,
			'filename' => false,
			'version' => '1.0',
			'query_vars' => false,
			'in_footer' => false
		);
		extract( wp_parse_args($args, $defaults) );
		if( !$path ) {
			$path = $this->scriptRootURI;
		} else if( !strstr($path, '://') ) {
			$path = $this->scriptRootURI . '/' . $path;
		}
		$filename = (!$filename) ? $handle . '.js' : $filename;
		$query = (!$query_vars) ? '' : http_build_query( $query_vars );
		wp_register_script( $handle, $path . '/' . $filename . $query, $dependencies, $version, $in_footer );
		return true;
	}
	
	/**
	 * This is a wrapper around WordPress's wp_enqueue_script()
	 * The difference here is that it accepts the same parameters as registration, and auto-registers with those parameters.
	 *
	 * @return void
	 */
	public function queueScript( $handle, $dependencies = false, $args = array() ) {
		$this->registerScript( $handle, $dependencies, $args );
		wp_enqueue_script( $handle );
	}

	/**
	 * This is a wrapper around WordPress's wp_register_style()
	 * The difference here is that it uses more concise and useful arguments in the $args paramter.
	 *
	 * @return bool false if style is already registered, or true if it is not
	 */
	public function registerStyle( $handle, $dependencies = array(), $args = array() ) {
		if( wp_style_is( $handle, 'registered' ) ) return false;
		$defaults = array(
			'path' => false,
			'filename' => false,
			'version' => '1.0',
			'query_vars' => false,
			'media' => 'all'
		);
		extract( wp_parse_args($args, $defaults) );
		if( !$path ) {
			$path = $this->styleRootURI;
		} else if( !strstr($path, '://') ) {
			$path = $this->styleRootURI . '/' . $path;
		}
		$filename = (!$filename) ? $handle . '.css' : $filename;
		$query = (!$query_vars) ? '' : http_build_query( $query_vars );
		wp_register_style( $handle, $path . '/' . $filename . $query, $dependencies, $version, $media );
		return true;
	}
	
	/**
	 * This is a wrapper around WordPress's wp_enqueue_style()
	 * The difference here is that it accepts the same parameters as registration, and auto-registers with those parameters.
	 *
	 * @return void
	 */
	public function queueStyle( $handle, $dependencies = false, $args = array() ) {
		$this->registerStyle( $handle, $dependencies, $args );
		wp_enqueue_style( $handle );
	}
	
	// RESERVERED PROPERTIES
	
	/**
	 * @property-read object $extensions Common property accessable across all instances of this class
	 */
	final public function get__extensions() {
		// This is an array returned by value (NOT REFERENCE, it is an array of references anyway!)
		return self::$_extensions[$this->className];
	}
	
	/**
	 * @property array $roles The current roles who have access to this plugin
	 */
	final public function get__roles() {
		if( !is_array($this->_roles) ) {
			$this->_roles = array();
			reset(self::$_wpRoles->role_objects);
			do {
				$role = current(self::$_wpRoles->role_objects);
				if( $role->has_cap( $this->capability ) ) $this->_roles[$role->name] =& $role;
			} while( next(self::$_wpRoles->role_objects) !== false );
		}
		return $this->_roles;
	}
	final public function set__roles( $v ) {
		if( !is_array($v) ) {
			if( empty($v) ) {
				$v = array();
			} else {
				$this->error( 'Failed setting property "<strong>' . $n . '</strong>" on plugin class <strong>'. $this->className. '</strong>. This property expects an array of WordPress role names or an empty value to remove all roles.' );
			}
		}
		$this->_roles = array();
		reset(self::$_wpRoles->role_objects);
		do {
			$role = current(self::$_wpRoles->role_objects);
			if( in_array( $role->name, $v, true ) ) {
				if( !$role->has_cap( $this->capability ) ) $role->add_cap( $this->capability );
				$this->_roles[$role->name] =& $role;
			} else {
				if( $role->has_cap( $this->capability ) ) $role->remove_cap( $this->capability );
			}
		} while( next(self::$_wpRoles->role_objects) !== false );
	}
	
	/**
	 * @property-read string $includeRoot the absolute path to this plugin's include root
	 * @see xf_source_Loader::get__base()
	 */
	public function get__currentUserHasCap() {
		get_currentuserinfo();
		if( $GLOBALS['current_user'] instanceof WP_User ) return $GLOBALS['current_user']->has_cap( $this->capability );
		return false;
	}
	
	/**
	 * @property-read string $includeRoot the absolute path to this plugin's include root
	 * @see xf_source_Loader::get__base()
	 */
	public function get__includeRoot() {
		return $this->applyLocalFilters( 'includeRoot', $this->loader->base );
	}
	
	/**
	 * @property-read string $includeRootURI the absolute path to this plugin's include root
	 * @see xf_source_Loader::get__base()
	 */
	public function get__includeRootURI() {
		return $this->applyLocalFilters( 'includeRootURI', $this->absURIfromPath($this->loader->base) );
	}
	
	/**
	 * @property-read string $pluginRoot the absolute path to this plugin's include root
	 * @see xf_source_Loader::get__base()
	 */
	public function get__pluginRoot() {
		return $this->applyLocalFilters( 'pluginRoot', dirname($this->loader->base) );
	}
	
	/**
	 * @property-read string $pluginRootURI the absolute path to this plugin's include root
	 * @see xf_source_Loader::get__base()
	 */
	public function get__pluginRootURI() {
		return $this->applyLocalFilters( 'pluginRootURI', dirname($this->absURIfromPath($this->loader->base)) );
	}
	
	/**
	 * @property-read string $imageRoot the absolute path to this plugin's images directory
	 */
	public function get__imageRoot() {
		return xf_system_Path::join( $this->pluginRoot, $this->dirImages );
	}
	
	/**
	 * @property-read string $imageRootURI the absolute URI to this plugin's images directory
	 */
	public function get__imageRootURI() {
		$uri = $this->pluginRootURI;
		return $uri .= ( empty( $this->dirImages ) ) ? '' : '/' . $this->dirImages;
	}
	
	/**
	 * @property-read string $scriptRoot the absolute path to this plugin's scripts directory
	 */
	public function get__scriptRoot() {
		return xf_system_Path::join( $this->pluginRoot, $this->dirScripts );
	}
	
	/**
	 * @property-read string $scriptRootURI the absolute URI to this plugin's scripts directory
	 */
	public function get__scriptRootURI() {
		$uri = $this->pluginRootURI;
		return $uri .= ( empty( $this->dirScripts ) ) ? '' : '/' . $this->dirScripts;
	}
	
	/**
	 * @property-read string $styleRoot the absolute path to this plugin's styles directory
	 */
	public function get__styleRoot() {
		return xf_system_Path::join( $this->pluginRoot, $this->dirStyles );
	}
	
	/**
	 * @property-read string $styleRootURI the absolute URI to this plugin's styles directory
	 */
	public function get__styleRootURI() {
		$uri = $this->pluginRootURI;
		return $uri .= ( empty( $this->dirStyles ) ) ? '' : '/' . $this->dirStyles;
	}
	
	/**
	 * @property-read string $cacheDir the absolute path to this plugin's cache directory
	 */
	public function get__cacheDir() {
		return xf_system_Path::join( $this->pluginRoot, $this->dirCache );
	}
	
	/**
	 * @property-read string $cacheDirURI the absolute URI to this plugin's cache directory
	 */
	public function get__cacheDirURI() {
		$uri = $this->pluginRootURI;
		return $uri .= ( empty( $this->dirCache ) ) ? '' : '/' . $this->dirCache;
	}
}
?>