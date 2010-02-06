<?php

require_once(dirname(__FILE__).'/../Object.php');
require_once(dirname(__FILE__).'/../errors/Error.php');
require_once(dirname(__FILE__).'/../system/Path.php');
require_once('IPluggable.php');

/**
 * This is an abstract class and is meant to be extended.
 *
 * Provides an easier way to get into the WordPress filter/action sytem, and provide methods to deal with shortNames
 * Shortnames are very usefull when dealing with filter/action names, option names, ids, CSS class names, etc...
 * PHP versions prior to 5.3 do not support Name Spaces.
 * I think a good thing to compare ShortNames to would be with Name Spaces.
 * With that comparison you may continue with the next section with a littile more understanding (hopefully).
 *
 * This class provides a piece of functionality to give any class extending it a way to create its own "local" filter/actions.
 * These "local" filter/actions are just the filter/action name prepended with the object's shortname.
 * Again, this is an optional peice of functionality, you don't have to use it...
 * You can still only just use the global WordPress connectivity this class provides.
 *
 * @package xf
 * @subpackage wp
 */
abstract class xf_wp_APluggable extends xf_Object implements xf_wp_IPluggable {
	
	/**
	 * Short-Name-Separator
	 */
	const SNS = '_';
	
	// STATIC MEMBERS
	
	/**
	 * @ignore
	 * Array of property names that will not be filtered with this class's __get magic method.
	 */
	private static $_unfilteredProps = array(
		'className',
		'parentClassName',
		'parentClasses',
		'shortName'
	);
	
	/**
	 * @ignore
	 * A Static property of all instances of xf_wp_APluggable
	 */
	protected static $_root = null;
	
	/**
	 * @ignore
	 * A Static property of all instances of xf_wp_APluggable, this will be set and an Object
	 */
	protected static $_extend = null;
	
	/**
	 * sanitizeShortName
	 *
	 * Santizes a string to what is called, a shortName.
	 *
	 * @param string $s The string to santize
	 * @return string The santized shortName
	 */
	final public static function sanitizeShortName( $s ) {
		$s = trim( preg_replace( '/[^A-Za-z'.self::SNS.']/', '', $s ), self::SNS );
		return strtolower( $s );
	}
	
	/**
	 * joinShortName
	 *
	 * Joins one ShortName with another, returning the full shortname.
	 *
	 * @param string $n Base shortName
	 * @param string $append shortName to append
	 * @return string Joined shortName
	 */
	final public static function joinShortName( $n, $append ) {
		return rtrim( $n, self::SNS ) . self::SNS . ltrim( $append, self::SNS );
	}
	
	/**
	 * unJoinShortName
	 *
	 * Removes part of a ShortName returning the modified shortname.
	 *
	 * @param string $n Full name to extract from
	 * @param string $remove Part of the shortName to remove
	 * @return string Modified shortName
	 */
	final public static function unJoinShortName( $n, $remove ) {
		return trim( str_replace( $remove, '', $n), self::SNS );
	}
	
	/**
	 * baseShortName
	 *
	 * The equivelant of basename() but instead with shortNames.
	 *
	 * @param string $n shortName to extract the base from
	 * @return string Extracted base
	 */
	final public static function baseShortName( $n ) {
		return array_pop( explode( self::SNS, trim( $n, self::SNS ) ) );
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * @ignore
	 * Used internally as instance's shortName memory variable (so not to parse it everytime)
	 */
	private $_shortName;
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
	 * @var mixed Used internally for WordPress action/filter system, but must remain public for WordPress to access.
	 */
	public $wp_filter_id;
	
	/**
	 * Create new intance
	 *
	 * @return void
	 */
	public function __construct()
	{		
		// set the common ext property
		if( is_null(self::$_extend) ) self::$_extend = new stdClass();
		
		// Set the shortName to a version of this instance's class name
		$this->shortName = $this->className;
		
		// call init method
		$this->init();
		
		// call hooks for {myclass}_init
		$this->doLocalAction( 'init' );
		
		// fork admin side from client side
		if( is_admin() ) {
			// call admin method
			$this->admin();
			// call hooks for {myclass}_admin_init
			$this->doLocalAction( 'admin_init' );
		} else {
			// call client method
			$this->client();
			// call hooks for {myclass}_client_init
			$this->doLocalAction( 'client_init' );
		}
		
		// call hooks for {myclass}_initiated
		$this->doLocalAction( 'initiated' );
	}
	
	/**
	 * Wrapper of the xf_errors_Error static method, passing a die callback
	 * @see xf_errors_Error::trigger()
	 */
	public function error( $message ) {
		return xf_errors_Error::trigger( $message, 'wp_die', 2 );
	}
	
	/**
	 * __get magic method
	 *
	 * Override this Magic method for getting properties
	 * This first checks to see if property is an extension, if it is it returns that.
	 *
	 * What this is doing is dynamically adding a filter to every property of this object.
	 * Lets say you have an instance of a child class called My_Class which is $myObject.
	 * So when a property is called like this: $myObject->myProperty
	 * A filter is then applied in WordPress with the global name of "shortname_myproperty"
	 * This means any and all filters added to these kinds of filter names, effects ALL instances of the class My_Class.
	 *
	 * NOTE: "shortName" and "className" have been ommitted from the filter process.
	 * This is because these properties are needed for the global to local filter name formatting to actaully work.
	 *
	 * @param string $n Name of the undefined property
	 * @return mixed
	 */
	public function &__get( $n ) {
		if( $this->isExt($n) ) return $this->extend->$n;
		if( in_array( $n, self::$_unfilteredProps ) ) return parent::__get( $n );
		return $this->applyLocalFilters( self::sanitizeShortName( $n ), parent::__get( $n ) );
	}
	
	/**
	 * Checks to see if property exists in the ext object.
	 *
	 * @param string $n The name of the extension to check
	 * @return bool
	 */
	final public function isExt( $n ) {
		if( is_object( $this->extend ) ) return property_exists( $this->extend, $n );
		return false;
	}
	
	/**
	 * Retrieves the callback for the name of a method or funciton for this instance.
	 * If no callback or object is supplied - returns the method of the current object of the same name as the action.
	 * If no callback is specified but an object is, then it returns the method of that object of the same name as the action.
	 * If a callback and an object is specified, then it returns the method of the specified callback name on the specified object.
	 *
	 * @param string $n The name of the filter or action.
	 * @param string $func An optional string name of the function or method.
	 * @param object &$obj An optional Object to retrieve the callback as a method of that object
	 * @return string Name of the callback function
	 */
	final public function getCallback( $n, $func = '', &$obj = null ) {
		if( empty( $func ) ) $func = $n;
		//if( !is_null( $obj )  ) return $func;
		if( !is_object($obj) ) $obj =& $this;
		$func = array( $obj, $func );
		return $func;
	}
	
	/**
	 * A wrapper around WordPress's add_filter.
	 * Allows easier filter handling for methods.
	 */
	final public function addFilter( $n, $func = '', &$obj = null, $priority = 10, $accepted_args = 1 ) {
		add_filter( $n, $this->getCallback( $n, $func, $obj), $priority, $accepted_args );
	}
	
	/**
	 * A wrapper around WordPress's remove_filter
	 * Allows easier filter handling for methods.
	 */
	final public function removeFilter( $n, $func = '', &$obj = null, $priority = 10, $accepted_args = 1 ) {
		remove_filter( $n, $this->getCallback( $n, $func, $obj), $priority, $accepted_args );
	}
	
	/**
	 * A wrapper around WordPress's add_action
	 * Allows easier action handling for methods.
	 */
	final public function addAction( $n, $func = '', &$obj = null, $priority = 10, $accepted_args = 1 ) {
		add_action( $n, $this->getCallback( $n, $func, $obj), $priority, $accepted_args );
	}
	
	/**
	 * A wrapper around WordPress's remove_action
	 * Allows easier action handling for methods.
	 */
	final public function removeAction( $n, $func = '', &$obj = null, $priority = 10, $accepted_args = 1 ) {
		remove_action( $n, $this->getCallback( $n, $func, $obj), $priority, $accepted_args );
	}
	
	/**
	 * Shortcut to format a string (action/filter name) to a local filter/action name.
	 *
	 * @param string $globalAction filter/action name in its global format
	 * @return string Reformatted filter/action name to local format
	 */
	final public function globalToLocalShortName( $globalAction ) {
		return self::joinShortName( $this->shortName, $globalAction );
	}
	
	/**
	 * Shortcut to format a local action/filter name to it's global format.
	 *
	 * @param string $localAction filter/action name in its local format
	 * @return string Reformatted filter/action name to global format
	 */
	final public function localToGlobalShortName( $localAction ) {
		return self::unJoinShortName( $localAction, $this->shortName );
	}
	
	/*
	 * Essentially everything sent to and from these methods in the action system are automatically prepended with class's shortName.
	 * This means if the shortName is "myclass",
	 * when the action "widgets_init" is passed though these functions
	 * it is actually being passed as "myclass_widgets_init" but still through the WordPress action system.
	 */
	
	/**
	 * A quick way to prepend the local prefix to filter names before applying them.
	 */
	final public function applyLocalFilters( $n, $v ) {
		return apply_filters( $this->globalToLocalShortName( $n ), $v );
	}
	
	/**
	 * A quick way to prepend the local prefix to filter names before adding them.
	 */
	final public function addLocalFilter( $n, $func = '', $object = null, $priority = 10, $accepted_args = 1 ) {
		$this->addFilter( $this->globalToLocalShortName( $n ), $func, $object, $priority, $accepted_args );
	}
	
	/**
	 * A quick way to prepend the local prefix to the filter names to remove them
	 */
	final public function removeLocalFilter( $n, $func = '', $object = null, $priority = 10, $accepted_args = 1 ) {
		$this->removeFilter( $this->globalToLocalShortName( $n ), $func, $object, $priority, $accepted_args );
	}
	
	/**
	 * A quick way to prepend the local prefix to action names before doing them.
	 */
	final public function doLocalAction( $n ) {
		$args = func_get_args();
		$n = array_shift( $args );
		//print $this->className . ' - ' . $this->globalToLocalShortName( $n ) . '<br />';
		do_action( $this->globalToLocalShortName( $n ), $args );
	}
	
	/**
	 * A quick way to prepend the local prefix to action names before adding them.
	 */
	final public function addLocalAction( $n, $func = '', $priority = 10, $accepted_args = 1 ) {
		if( empty($func) ) $func = $n;
		$this->addAction( $this->globalToLocalShortName( $n ), $func, $this, $priority, $accepted_args );
	}
	
	/**
	 * A quick way to prepend the local prefix to action names to remove them.
	 */
	final public function removeLocalAction( $n, $func = '', $priority = 10, $accepted_args = 1 ) {
		if( empty($func) ) $func = $n;
		$this->removeAction( $this->globalToLocalShortName( $n ), $func, $this, $priority, $accepted_args );
	}
	
	/**
	 * Shortcut to format a setting name added by this class.
	 * This is left as public because you may want to use this outside of this class.
	 *
	 * @param string $n Name of the option without the shortName of this class.
	 * @return string the final joined name of the option with the shortName of this class prepended
	 */
	public function getOptionName( $n ) {
		return self::joinShortName( $this->shortName, $n );
	}
	
	/**
	 * Convert a path to an absolute URI.
	 * This only works for files on your local file system.
	 *
	 * @param string $path Relative or absolute path on your server, preferably absolute
	 * @return string
	 */
	public function absURIfromPath( $path ) {
		$uriPath = str_replace( xf_system_Path::DS, '/', $path );
		if ( !xf_system_Path::isAbs( $uriPath ) ) {
			return get_bloginfo('wpurl') . '/' . $uriPath;
		}
		$patterns = array( '|^'. ABSPATH . '|' );
		$uri = trim(preg_replace( $patterns, get_bloginfo('wpurl') . xf_system_Path::DS, $uriPath ));
		return str_replace( xf_system_Path::DS, '/', $uri );
	}
	
	/**
	 * Convert either a path or URI to the correct absolute path.
	 * This only works for files on your local file system.
	 *
	 * @param string $pathOrURI may be either a URI, relative path, or absolute path on your server
	 * @param bool $absolute if true then it makes sure it returns an absolute path, if false it doesn't have to be absolute
	 * @return string
	 */
	public function pathFromURI( $pathOrURI, $abs = true ) {
		$patterns = array( '|^'. get_bloginfo('url') . '/' . '|', '|^'. get_bloginfo('wpurl') . '/' . '|');
		$path = trim(preg_replace( $patterns, '', $pathOrURI ));
		if($abs) $path = xf_system_Path::join( ABSPATH, $path );
		return str_replace( '/', xf_system_Path::DS, $path );
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
	 * @property-read object $root Common property accessable across all instances of this class
	 */
	final public function &get__root() {
		return self::$_root;
	}
	
	/**
	 * @property-read object $extend Common property accessable across all instances of this class
	 */
	final public function &get__extend() {
		return self::$_extend;
	}
	
	/**
	 * @property-read WPDB $db The WordPress Database object
	 */
	public function &get__db() {
		return $GLOBALS['wpdb'];
	}
	
	/**
	 * @property-read WP_Object_Cache $cache The WordPress Object Cache object
	 */
	public function &get__cache() {
		return $GLOBALS['wp_object_cache'];
	}
	
	/**
	 * @property-read string $currentFilter Property wrapper around the global wordpress function
	 */
	public function &get__currentFilter() {
		return current_filter();
	}
	
	/**
	 * @property-read-write string $shortName
	 */
	final public function get__shortName() {
		return $this->_shortName;
	}
	final public function set__shortName( $v ) {
		// sanitize this because it will be used for a lot of things.
		// Mainly for local action prefixes within this class.
		$this->_shortName = self::sanitizeShortName( $v );
		if( empty( $this->_shortName ) ) $this->_shortName =  self::sanitizeShortName( $this->className );
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