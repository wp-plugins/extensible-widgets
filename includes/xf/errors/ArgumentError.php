<?php
/**
 * This file defines xf_errors_ArgumentError, an exception of the errors subpackage.
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
 * Certain function argument errors that may not be handled by php itself.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage errors
 */
class xf_errors_ArgumentError extends xf_errors_Error {
	
	// STATIC MEMBERS
	
	private static $codes = array(
		'0' => 'Undefined argument error.',
		'1' => 'Invalid number of arguments for this function.',
		'3' => 'Argument is not the expected type.',
		'4' => 'Argument is required and cannot be null.',
		'5' => 'Exceeded the maximum allowed number of arguments.',
		'6' => 'This argument is required upon first instantiation, but not for any following instantiations.'
	);
	
	// INSTANCE MEMBERS
	
	/**
	 * Create new instance
	 *
	 * @param string $code
	 * @param int $argIndex
	 * @param string $argument
	 * @param string $extra
	 * @return void
	 */
	public function __construct( $code = 0, $argIndex = null, $argument = null, $extra = '' )
	{
		$message = '';
		if( !is_null($argIndex) ) $message .= ' ARG[' . $argIndex . '] '.xf_errors_Error::emptyVarToString( $argument );
		$message .= ': '.self::$codes[$code];
		if( !empty($extra) ) $message .= ' ('.$extra.')';
		parent::__construct( $message, false, $code );
	}
}
?>