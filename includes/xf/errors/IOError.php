<?php
/**
 * This file defines xf_errors_IOError, an exception of the errors subpackage.
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
 * Base class in the this subpackage's exception implementation
 */
require_once('Error.php');

/**
 * Exceptions thrown by conditions that would result in input output errors
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage errors
 */
class xf_errors_IOError extends xf_errors_Error {
	
	// STATIC MEMBERS
	
	private static $codes = array(
		'0' => 'Undefined system error.',
		'1' => 'File does not exist.',
		'2' => 'File is not readable by the webserver.',
		'3' => 'File is not writable by the webserver.',
		'4' => 'File is not executable by the webserver.',
		'5' => 'Directory does not exist.',
		'6' => 'Directory is not readable by the webserver.',
		'7' => 'Directory is not writable by the webserver.',
		'8' => 'Not a directory.',
		'9' => 'Is a directory.',
		'10' => 'Directory is not empty.',
	);
	
	// INSTANCE MEMBERS
	
	/**
	 * Create new instance
	 *
	 * @param string $code
	 * @param string $path
	 * @return void
	 */
	public function __construct( $code = 0, $path = null )
	{
		$message = '';
		if( !is_null($path) ) $message .= ' PATH[' . xf_errors_Error::emptyVarToString( $path ) . ']: ';
		$message .= self::$codes[$code];
		parent::__construct( $message, false, $code );
	}
}
?>