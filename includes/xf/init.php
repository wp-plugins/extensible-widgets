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
	 * @var string $class_prefix The class prefix to check before trying to autoload
	 */
	public static $class_prefix = 'xf_';
	/**
	 * @var string $base The base path to autoload from
	 */
	public static $base;
	
	/**
	 * Used internally to set any necessary member variables
	 *
	 * @return void
	 */
	protected static function _init() {
		if( !empty(self::$base) ) return false;
		// set the base to the directory containing the xf library
		self::$base = dirname(dirname(__FILE__));
		return true;
	}
	
	/**
	 * Callback function for autoload
	 *
	 * @return void
	 */
	public static function _autoloadCallback( $class ) {
		if( strpos( $class, self::$class_prefix ) !== false && !class_exists($class, false) ) {
			$filepath = self::$base.DIRECTORY_SEPARATOR.str_replace( '_', DIRECTORY_SEPARATOR, $class.'.php' );
			if( file_exists($filepath) ) require_once($filepath);
		}
	}
	
	/**
	 * initiate autoload mecahnism
	 *
	 * @return void
	 */
	public static function autoload() {
		if( !self::_init() ) return;
		spl_autoload_register( array( __CLASS__, '_autoloadCallback' ) );
	}
	
	/**
	 * initiate include_path mecahnism
	 *
	 * @return void
	 */
	public static function includePath() {
		if( !self::_init() ) return;
		set_include_path( get_include_path() . PATH_SEPARATOR . self::$base );
	}
}
?>