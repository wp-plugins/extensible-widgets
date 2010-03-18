<?php
/**
 * This class initiates the autoloader for the xf library
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 */
class xf_init {
	
	/**
	 * @var bool $_initiated Flag to keep track of first time initiation
	 */
	protected static $_initiated = false;
	/**
	 * @var string $prefix_filter The class prefix to check before trying to autoload
	 */
	public static $prefix_filter;
	/**
	 * @var string $base The base path to autoload from, or to set the include path to
	 */
	public static $base;
	
	/**
	 * Checks a class name for a valid prefix within the current prefix filter.
	 * If there is no prefix filter then automatically returns true.
	 *
	 * @return bool True if the class has a prefix within the filter
	 */
	protected static function _hasPrefix( $class ) {
		if( !is_array(self::$prefix_filter) || !count(self::$prefix_filter) ) return true;
		reset(self::$prefix_filter);
		do {
			if( strpos( $class, current(self::$prefix_filter) ) !== false ) return true;
		} while( next(self::$prefix_filter) !== false );
		return false;
	}
	
	/**
	 * Used internally to set any necessary member variables
	 *
	 * @param string $base The base path, defaults to 2 directories up from where this file resides
	 * @param array $prefixes An array of class prefixes to use for autoload filtering
	 * @return void
	 */
	protected static function _init( $base = '', $prefixes = null ) {
		self::$prefix_filter = $prefixes;
		self::$base = empty($base) ? dirname(dirname(__FILE__)) : $base;
		if( self::$_initiated ) return false;
		return self::$_initiated = true;
	}
	
	/**
	 * Callback function for autoload
	 *
	 * @return void
	 */
	public static function _autoloadCallback( $class ) {
		if( !class_exists($class, false) ) {
			if( self::_hasPrefix( $class ) ) {
				$filepath = self::$base.DIRECTORY_SEPARATOR.str_replace( '_', DIRECTORY_SEPARATOR, $class.'.php' );
				if( file_exists($filepath) ) require_once($filepath);
			}
		}
	}
	
	/**
	 * initiate autoload mecahnism
	 *
	 * @param array $prefixes An array of class prefixes to use for autoload filtering
	 * @return void
	 */
	public static function autoload( $base = '', $prefixes = null ) {
		if( self::_init( $base, $prefixes ) ) spl_autoload_register( array( __CLASS__, '_autoloadCallback' ) );
	}
	
	/**
	 * initiate include_path mecahnism
	 *
	 * @return void
	 */
	public static function includePath( $base = '' ) {
		$include_path = str_replace( self::$base, '', get_include_path() );
		self::_init( $base );
		set_include_path( $include_path . PATH_SEPARATOR . self::$base );
	}
}
?>