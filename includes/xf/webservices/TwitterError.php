<?php
require_once(dirname(__FILE__).'/../errors/Error.php');

/**
 * xf_webservices_TwitterError
 *
 * Errors that are thrown by the xf_webservices_Twitter class
 *
 * @package xf
 * @subpackage webservices
 */

// START class
class xf_webservices_TwitterError extends xf_errors_Error {
	
	// STATIC MEMBERS
	
	private static $codes = array(
		'0' => 'Username and/or password not set',
		'1' => 'CURL library not installed',
		'2' => 'Post value too long/not set',
		'3' => 'Invalid username/password',
		'4' => 'Invalud URL for CURL request',
		'5' => 'Invalid ID value entered',
		'6' => 'You are not authorized to view this page',
		'7' => 'All variables for requested function not set',
		'8' => 'For and/or Message not set',
		'9' => 'Unable to connect to Twitter'
	);
	
	// INSTANCE MEMBERS
	
	/**
	 * CONSTRUCTOR
	 *
	 * @param string $code
	 * @return void
	 */
	public function __construct( $code = 0 )
	{
		parent::__construct( self::$codes[$code], false, $code );
	}
}
// END class
?>