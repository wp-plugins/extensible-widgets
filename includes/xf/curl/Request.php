<?php
/**
 * This file defines xf_curl_Request, a wrapper around the curl library.
 * 
 * PHP version 5
 * 
 * @package xf
 * @subpackage curl
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @link http://jidd.jimisaacs.com
 */

/**
 * This class is a combination of two patterns, Singleton and Factory.
 * Extending it should turn the extending class into a singleton.
 */
require_once(dirname(__FILE__).'/../patterns/ASingleton.php');

/**
 * xf_curl_Request
 *
 * A wrapper class around the CURL library
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage curl
 */

// START CLASS
class xf_curl_Request extends xf_patterns_ASingleton {
	
	// CONSTANTS
	const MAX_REQUEST_TRIES = 2;
	
	// STATIC MEMBERS
	
	// list of statuses
	// Notice status = 0 is success, while another > 0 is an error.
	static protected $status = array(
		0 => 'Request made successfully',
		1 => 'CURL library not installed',
		2 => 'Could not send request',
		3 => 'Authentication requires a username',
		4 => '401 Authentication failed',
		5 => '404 Not Found',
		6 => 'Max request tries exceeded',
		7 => 'Post value too long/not set' // not used initially
	);
	
	/**
	 * @see xf_patterns_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return self::getSingleton(__CLASS__);
	}
	
	/**
	 * getStringStatus
	 *
	 * Returns status string corresponding to the status integer
	 *
	 * @param int $status The index of the string in the status array
	 * @return void
	 */
	public static function getStringStatus( $int ) {
		if( array_key_exists( $int, self::$status ) ) {
			return self::$status[$int];
		}
		// -1 is an unknown status, cannot use zero because that is success.
		return -1;
	}
	
	// INSTANCE MEMBERS
	
	protected $_status = -1;
	public $outputErrors = false;
	
	// USER CREDS (for this class instance)
	public $username;
	public $password;
    	
	/**
	 * CONSTRUCTOR
	 *
	 * @param string $user HTTP Auth username
	 * @param string $password HTTP Auth password
	 * @return void
	 */
	function __construct( $user = '', $password = '' )
	{
		$this->username = $user;
		$this->password = $password;
		parent::__construct();
	}
	
    /**
	 * initCurl
	 *
	 * Checks if CURL ibrary is installed
	 *
	 * @param string $url the URL to send a request to
	 * @param string $method the request method to use GET, POST, PUT, DELETE
	 * @param bool $auth whether this an authenticated request or not
	 * @param bool $verbose whether this is a verbose request or not
	 * @return int|false this is actually the cURL handle or false
	 */
	protected function initCurl( $url = '', $method = 'GET', $auth = false, $verbose = false ) {
		if( !function_exists( 'curl_init' ) ) return $this->error(1);
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Expect:'));
		// set the request method
		switch($method) {
			case 'POST' :
				curl_setopt( $ch, CURLOPT_POST, true);
			break;
			case 'PUT' :
				curl_setopt( $ch, CURLOPT_PUT, true);
			break;
		}
		if( $auth ) {
			curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ); 
			curl_setopt( $ch, CURLOPT_USERPWD, "$this->username:$this->password" );
		}
		if( $verbose ) curl_setopt( $ch, CURLOPT_VERBOSE, 1 );
		return $ch;		
	}	
		
	/**
	 * error
	 *
	 * Returns status and outputs error if outputErrors is true
	 * Status codes are keys ffrom the status array
	 *
	 * @param int $errno the error index to trigger and output
	 * @return void
	 */
	public function error( $errno ) {
		$this->_status = $errno;
		$return = ( $this->outputErrors ) ? 'string' : 'int';
		$sts = $this->getCurrentStatus( $return );
		if( $this->outputErrors ) echo $sts;
		return $this->_status;
	}
	
	/**
	 * getCurrentStatus
	 *
	 * Returns current status that was last set by a method of this class.
	 *
	 * @return void
	 */
	public function getCurrentStatus( $return = 'int' ) {
		$sts = self::getStringStatus( $this->_status );
		$ststr = ( $sts === -1 ) ? 'Unknown Status' : $sts;
		switch( $return ) {
			case 'string' :
				$sts = '('.$this->_status.') '.$ststr;
			break;
			case 'array' :
				$sts = array( $this->_status => $ststr );
			break;
			default :
				$sts = $this->_status;
			break;
		}
		return $sts;
	}
	
	// Request Methods
	
	/**
	 * authRequest
	 *
	 * Authenicated request to the url doing authenication checking before sending
	 *
	 * @param string $url the URL to send a request to
	 * @param string $method the request method to use GET, POST, PUT, DELETE
	 * @return string|false the string is unparsed data from the request
	 */
	public function authRequest( $url = '' , $method = 'GET') {
		if( empty( $this->username ) ) {
			return $this->error(3);
		}
		return $this->request( $url, $method, true );
	}
	
	/**
	 * request
	 *
	 * Request to the url authenticated and/or verbose
	 *
	 * @param string $url the URL to send a request to
	 * @param string $method the request method to use GET, POST, PUT, DELETE
	 * @param bool $auth whether this an authenticated request or not
	 * @param bool $verbose whether this is a verbose request or not
	 * @param int $try the current try of the request
	 * @return string|false the string is unparsed data from the request
	 */
	public function request( $url = '', $method = 'GET', $auth = false, $verbose = false, $expectedType = null, $try = 1 ) {
		// CURL check
		$ch = $this->initCurl( $url, $method, $auth, $verbose );
		if( !$ch ) return false;
		$data = curl_exec( $ch ); 
		$info = curl_getinfo( $ch );
		curl_close( $ch );
		// handle response
		switch ( $info['http_code'] ) {
			case 401 :
				return $this->error(4);
			break;
			case 404 :
				return $this->error(5);
			break;
			case 0 :
				return $this->error(2);
			break;
			case 200 :	
			default :
				if( !is_null($expectedType) ) {
					switch( $expectedType ) {
						case 'xml' :
							$patterns = array(
								'/<?xml+/'
							);
						break;
						case 'json' :
							$patterns = array(
								'/^{+/'
							);
						break;
						default : // html
							$patterns = array(
								'/<html+/',
								'/\/html+>/'
							);
						break;
					}
					if( isset($patterns) ) {
						if( !preg_match_all( $patterns, $data ) ) {
							if($try < self::MAX_REQUEST_TRIES) {
								return $this->request($url, $method, $auth, $verbose, $try+1);
							}
							return $this->error(6);
						}
					}
				}
				// SUCCESS!!!!!!
				$this->_status = 0;
				return $data;
			break;
		}
		return false;
	}
}
?>