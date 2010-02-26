<?php
/**
 * This file defines xf_errors_Error, the base class within the errors subpackage.
 * 
 * PHP version 5
 * 
 * @package    xf
 * @subpackage errors
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

/**
 * Simple interface to remember all attributes of the builtin php Exception class
 */
require_once('IException.php');

/**
 * Base class in this package's exception implementation
 * This is a custom exception, it may trigger php error with a custom callback.
 * Though it can also let it through as an exception to allow error handling.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage errors
 */
class xf_errors_Error extends Exception implements xf_IException {
	
	// STATIC MEMBERS
	
	/**
	 * @ignore
	 * Used internally for boolean flag memory
	 */
	protected static $_debug = false;
	/**
	 * $staticvar callback $callback The optional callback function used for exiting the php
	 */
	public static $callback = null;
	
	/**
	 * Static setter for the debug static property of this class.
	 * Error instantiation process must happen after calling this method.
	 *
	 * @param bool $flag When true sets PHP errors to display, and when false they do not.
	 * @param constant $reporting Set the current PHP error reporting method
	 * @return void
	 */
	public static function setDebug( $flag, $reporting = null ) {
		self::$_debug = (bool) $flag;
		ini_set( 'display_errors', self::$_debug );
		ini_set( 'display_startup_errors', self::$_debug );
		if( self::$_debug ) {
			if( is_null($reporting) ) $reporting = E_ALL & ~E_NOTICE;
			error_reporting( $reporting );
		} else {
			error_reporting( 0 );
		}
	}
	
	/**
	 * Static getter for the debug static property of this class
	 *
	 * @return bool
	 */
	public static function getDebug() {
		return self::$_debug;
	}
	
	/**
	 * Does empty checking on a variable, and returns the appropriate 
	 * string repsonse to that empty state.
	 *
	 * @return string
	 */
    public static function emptyVarToString( &$var, $emptyString = '' ) {        
        if( is_null( $var ) ) return 'null';
		else if( $var === true ) return 'true';
		else if( $var === false ) return 'false';
		else if( $var === '' ) return $emptyString;
		return (string) $var;
    }
    
	/**
	 * Gets the formatted string of the current error.
	 * Checks if debugging is on, and sets the string accordingly.
	 *
	 * @return string
	 */
    public static function getErrorString( $message, $code = 0, $file = 'Unknown', $line = 'Unknown' ) {        
        if( self::$_debug ) {
			return  $message .= ' ( DEBUG: Error code[' . $code . '], traced to file '.$file.' on line '.$line.' )';
		}
		return $message;
    }
	
	/**
	 * This actually triggers a notice in debug mode or exits the script in default mode.
	 *
	 * @param string $message The error message to display either with the notice, or when dies.
	 * @param string|null $callback Optional user defined "die" callback. The user defined function should accept one argument which is the error message.
	 * @return void
	 */
	public static function trigger( $message, $callback = null, $backtrace = 1, $flag = E_USER_NOTICE ) {
		if( self::$_debug ) {
			$trace = ( is_int($backtrace) ) ? debug_backtrace() : array();
			$file = ( isset($trace[$backtrace]['file']) ) ? $trace[$backtrace]['file'] : 'Unknown';
			$line = ( isset($trace[$backtrace]['line']) ) ? $trace[$backtrace]['line'] : 'Unknown';
			trigger_error( self::getErrorString( $message, 'NA', $file, $line ).' triggered ', $flag);
		} else {
			$die = (empty($callback)) ? self::$callback : $callback;
			if( is_callable($die) ) {
				call_user_func($die, $message);
				die;
			}
			die($message);
		}
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * Create new instance
	 *
	 * @param string $message
	 * @param bool $trigger
	 * @param int $code
	 * @return void
	 */
	public function __construct( $message = null, $trigger = false, $code = 0 )
	{
		$message = get_class($this) . $message;
		if( $trigger ) {
			self::trigger( $message, self::$callback, 2 );
		} else {
			parent::__construct( $message, $code );
		}
	}
    
	/**
	 *  Magic - Custom string representation of object
	 *
	 * @return string
	 */
    public function __toString() {
		return self::getErrorString( $this->message, $this->code, $this->getFile(), $this->getLine() );
    }
}
?>