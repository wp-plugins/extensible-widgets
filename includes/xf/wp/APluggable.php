<?php
/**
 * This file defines xf_wp_APluggable, an abstract class for objects
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

require_once(dirname(__FILE__).'/../Object.php');
require_once(dirname(__FILE__).'/../errors/Error.php');
require_once(dirname(__FILE__).'/../system/Path.php');
require_once('IPluggable.php');

/**
 * This is an abstract class and is meant to be extended.
 *
 * Provides an easier way to get into the WordPress filter/action sytem, and provide methods to deal with shortNames
 * Shortnames are very usefull when dealing with filter/action names, option names, etc...
 * PHP versions prior to 5.3 do not support Name Spaces.
 * I think a good thing to compare ShortNames to would be with Name Spaces.
 * With that comparison you may continue with the next section with a littile more understanding (hopefully).
 *
 * This class provides a piece of functionality to give any class extending it a way to create its own "local" filter/actions.
 * These "local" filter/actions are just the filter/action name prepended with the object's shortname.
 * Again, this is an optional peice of functionality, you don't have to use it...
 * You can still only just use the global WordPress connectivity this class provides.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
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
	 * @var array $get Accessible copy of PHP super global $_GET
	 */
	public static $get;
	/**
	 * @var array $post Accessible copy of PHP super global $_POST
	 */
	public static $post;
	
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
		if ( empty( $append ) ) return $n;
		$joined = rtrim( $n, self::SNS ) . self::SNS . ltrim( $append, self::SNS );
		$count = func_num_args();
		if( $count > 2 ) {
			$args = func_get_args();
			for( $i=2 ; $i < $count ; $i++ ) {
				$joined = self::joinShortName( $joined, $args[$i] );
			}
		}
		return $joined;
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
		// Save the super globals because WordPress likes to unset certain values it sets in the admin and so forth
		// This is a quick fix, instead of course I could just make sure I save all the necessary variables it does unset.
		self::$get = $_GET;
		self::$post = $_POST;
		
		// Set the shortName to a version of this instance's class name
		$this->shortName = $this->className;
		
		// call init method
		$this->init();
		
		// fork admin side from client side
		if( is_admin() ) {
			// call admin method
			$this->admin();
			// call hooks for {myclass}_admin_onAdminInitiated
			$this->doLocalAction( 'onAdminInitiated' );
		} else {
			// call client method
			$this->client();
			// call hooks for {myclass}_client_onClientInitiated
			$this->doLocalAction( 'onClientInitiated' );
		}
		
		// call hooks for {myclass}_onInitiated
		$this->doLocalAction( 'onInitiated' );
	}
	
	/**
	 * @see xf_wp_IPluggable::admin()
	 */
	public function admin() {}
	
	/**
	 * @see xf_wp_IPluggable::client()
	 */
	public function client() {}
	
	/**
	 * Wrapper of the xf_errors_Error static method, passing a die callback
	 * @see xf_errors_Error::trigger()
	 */
	public function error( $message, $backtrace = 2, $flag = E_USER_ERROR ) {
		return xf_errors_Error::trigger( $message, 'wp_die', $backtrace, $flag );
	}
	
	/**
	 * __get magic method
	 *
	 * Override this Magic method for getting properties
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
		if( in_array( $n, self::$_unfilteredProps ) ) return parent::__get( $n );
		return $this->applyLocalFilters( self::sanitizeShortName( $n ), parent::__get( $n ) );
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
	 * when the action "widgets_initiated" is passed though these functions
	 * it is actually being passed as "myclass_widgets_initiated" but still through the WordPress action system.
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
		// Convert to a POSIX path and we're halfway there
		$pxPath = xf_system_Path::toPOSIX( $path );
		if ( !xf_system_Path::isAbs( $pxPath ) ) {
			return get_bloginfo('wpurl') . '/' . $pxPath;
		} else {
			$uri = str_replace( xf_system_Path::toPOSIX(ABSPATH), get_bloginfo('wpurl').'/', $pxPath );
		}
		return $uri;
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
	
	// RESERVERED PROPERTIES
	
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
}
?>