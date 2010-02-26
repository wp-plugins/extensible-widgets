<?php
/**
 * This file defines xf_errors_DefinitionError, an exception of the errors subpackage.
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
 * Exceptions thrown by conditions that would result in definition errors
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage errors
 */
class xf_errors_DefinitionError extends xf_errors_Error {
	
	// STATIC MEMBERS
	
	private static $codes = array(
		'0' => 'Undefined definition error.',
		'1' => 'Failed to load class, class file cannot be found.',
		'2' => 'Failed to load class, it has already been defined.',
		'3' => 'Instantiation of a static class is not allowed.',
		'4' => 'This Singleton class has already been instantiated.',
		'5' => 'The $_members property for reference map is invalid.'
	);
	
	// INSTANCE MEMBERS
	
	/**
	 * Create new instance
	 *
	 * @param string $code
	 * @param string $argument
	 * @return void
	 */
	public function __construct( $code = 0, $definition = null )
	{
		if( is_null( $argument ) ) $argument = 'null';
		else if( $argument === true ) $argument = 'true';
		else if( $argument === false ) $argument = 'false';
		else $argument = 'empty';
		$message = ' CLASS[' . xf_errors_Error::emptyVarToString( $definition ) . ']: ' . self::$codes[$code];
		parent::__construct( $message, false, $code );
	}
}
?>