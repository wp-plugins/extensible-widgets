<?php
/**
 * This file defines xf_errors_ReferenceError, an exception of the errors subpackage.
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
 * A xf_errors_ReferenceError exception is thrown when a reference to an undefined 
 * property is attempted on a sealed (nondynamic) object. References to 
 * undefined variables will result in xf_errors_ReferenceError exceptions to inform 
 * you of potential bugs and help you troubleshoot application code.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage errors
 */
class xf_errors_ReferenceError extends xf_errors_Error {
	
	// STATIC MEMBERS
	
	private static $codes = array(
		'0' => 'Undefined reference error.',
		'1' => 'Failed getting property, property does not exist.',
		'2' => 'Failed getting property, property is write-only.',
		'3' => 'Failed setting property, property is read-only.',
		'4' => 'Failed unsetting property, property is reserved.',
		'5' => 'Failed unsetting dynamic property, dynamic properties are restricted.',
		'6' => 'Failed setting dynamic property, dynamic properties are restricted.'
	);
	// INSTANCE MEMBERS
	
	/**
	 * Create new instance
	 *
	 * @param string $code
	 * @param string $property
	 * @return void
	 */
	public function __construct( $code = 0, &$object, $property = null )
	{
		$message = ' '.get_class($object).'('.$object.') PROP[' . xf_errors_Error::emptyVarToString( $property ) . ']: ' . self::$codes[$code];
		parent::__construct( $message, false, $code );
	}
}
?>