<?php
/**
 * This file defines xf_webservices_Twitter, a wrapper around the Twitter API
 * @TODO This packages uses the older Twitter API and needs updated using AuthRequest
 * 
 * PHP version 5
 * 
 * @package    xf
 * @subpackage webservices
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

require_once('TwitterError.php');

/**
 * xf_webservices_Twitter
 *
 * A wrapper class around the Twitter API
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage webservices
 */

// START CLASS
class xf_webservices_Twitter {
	
	// CONSTANTS
	
	const MAX_LENGTH = 140;
	const URL_TWITTER = 'http://twitter.com/';
	const URL_STATUS = 'http://twitter.com/statuses/';
	const URL_SEARCH = 'http://search.twitter.com/';
	const MAX_REQUEST_TRIES = 2;
	
	// STATIC MEMBERS
	
	private static $instances = 0;
	
	// INSTANCE MEMEBERS
	
	// USER CREDS (for this Twitter class instance)
	public $username;
	public $password;
	// statuses memory
	public $search = array();
	public $userTimeline = array();
	public $friendsTimeline = array();
	public $publicTimeline = array();
	public $replies = array();
	// user memory
	public $following = array();
	public $followers = array();
	public $featured = array();
	// messages memory
	public $directMessages = array();
	public $sentMessages = array();
		
	/**
	 * CONSTRUCTOR
	 *
	 * @param string $user Twitter username
	 * @param string $password Twitter password
	 * @return void
	 */
	 
	function __construct( $user = '', $password = '' ) 
	{
		$this->instance = ++self::$instances;
		$this->username = $user;
		$this->password = $password;
	}
	
	/**
	 * __clone
	 *
	 * Usage in php5 - $copy_of_object = clone $object;
	 *
	 * This clones this class, this is a good way to reuse a connection while changing other unrelated connection vars.
	 * @return xf_webservices_Twitter a clone of the xf_webservices_Twitter instance specified with clone $obj
	 */
	 
	function __clone()
	{
        $this->instance = ++self::$instances;
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
		if( empty( $this->username ) || empty( $this->password ) ) {
			throw new xf_webservices_TwitterError( 0 );
			return false;
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
	public function request( $url = '', $method = 'GET', $auth = false, $verbose = false, $try = 1 ) {
		// CURL check
		$ch = $this->initCurl( $url, $method, $auth, $verbose );
		if( !$ch ) return false;
		$data = curl_exec( $ch ); 
		$info = curl_getinfo( $ch );
		curl_close( $ch );
		// handle response
		switch ( $info['http_code'] ) {
			case 401 :
				throw new xf_webservices_TwitterError( 3 );
			break;
			case 404 :
				throw new xf_webservices_TwitterError( 4 );
			break;
			case 200 :
			default :
				// MUST check if twitter responded with the "fail whale"
				// This may change in future versions of the API to an actual xml error, but for now we check the data.
				if( substr($data, 0, 5) != '<?xml' && substr($data, 0, 1) != '{' ) {
					if($try < self::MAX_REQUEST_TRIES) {
						return $this->request($url, $method, $auth, $verbose, $try+1);
					} else {
						throw new xf_webservices_TwitterError( 9 );
						return false;
					}
				}
				return $data;
			break;
		}
		return false;
	}
	
	// Status Methods
	
	/**
	 * getSearch
	 *
	 * Do a search in the Twitter webservices
	 *
	 * @param array $args argments to the search query
	 * @return array|false indexed array json decoded
	*/
	public function getSearch( $args = array() ) {
		$query = http_build_query( $args );
		$url = self::URL_SEARCH . 'search.json?' . $query;
		return $this->search = $this->parseJsonRsp( $url );
	}
	
	/**
	 * getUserTimeline
	 *
	 * Retreive all the user's statuses
	 *
	 * @param int $count the amount of statuses that make up a page of the timeline
	 * @param int|null $page the page of the timeline to retreive
	 * @param string|null $since_id an id of a status to start the timeline from
	 * @return array|false indexed array in simplexml format
	*/
	public function getUserTimeline( $count = '2000', $page = NULL, $since_id = NULL ) {
		$url = self::URL_STATUS . 'user_timeline.xml?count=' . $count;
		if( !empty( $page ) ) $url .= '&page=' . $page;
		if( !empty( $since_id ) ) $url .= '&since_id=' . $since_id;
		return $this->userTimeline = $this->parseTimelineRsp( $url );
	}
	
	/**
	 * getFriendsTimeline
	 *
	 * Retreive updates from people the user follows (friends)
	 *
	 * @param int $page the page of the timeline to retreive
	 * @return array|false indexed array in simplexml format
	 */
	public function getFriendsTimeline( $page = '1' ) {
		$url = self::URL_STATUS . 'friends_timeline.xml?page='. $page;
		return $this->friendsTimeline = $this->parseTimelineRsp( $url );
	}
	
	/**
	 * getPublicTimeline
	 *
	 * Retreive public statuses from Twitter itself
	 *
	 * @return array|false indexed array in simplexml format
	 */
	public function getPublicTimeline() {
		$url = self::URL_STATUS . 'public_timeline.xml';
		return $this->publicTimeline = $this->parseTimelineRsp( $url );	
	}
	
	/**
	 * getReplies
	 *
	 * Retreive replies of user replies
	 *
	 * @return array|false indexed array in simplexml format
	 */
	public function getReplies( $page = '1' ) {
		$url = self::URL_STATUS . 'replies.xml?page='. $page;
		return $this->replies = $this->parseTimelineRsp( $url );	
	}
	
	/**
	 * updateStatus
	 *
	 * Post a new status to twitter for the user
	 *
	 * @param string $id the status content
	 * @return array|false indexed array in simplexml format
	 */
	public function updateStatus( $status = '' ) {
		if( empty( $status ) || strlen( $status ) > self::MAX_LENGTH ) {
			throw new xf_webservices_TwitterError( 2 );
			return false;
		} 
		$url = self::URL_STATUS . 'update.xml?status=' . urlencode( stripslashes( urldecode( trim($status) ) ) );
		$data = $this->authRequest( $url , 'POST');
		if( !$data ) return false;
		
		$xml = simplexml_load_string( $data );
		return $this->parseStatus( $xml );
	}
	
	/**
	 * showStatus
	 *
	 * Retreive a single status from the user, specified by the id parameter below
	 *
	 * @param string $id the id of the status to retreive
	 * @return array|false indexed array in simplexml format
	 */
	public function showStatus( $id = '' ) {
		if(empty($id) && !is_integer($id)) {
			throw new xf_webservices_TwitterError( 5 );
			return false;
		}
		$url = self::URL_STATUS . 'show/'. $id .'.xml';
		$data = $this->request( $url );
		if(!$data) return false;

		$xml = simplexml_load_string( $data );
		return $this->parseStatus( $xml );
	}
	
	/**
	 * destroyStatus
	 *
	 * Delete a single status from the user, specified by the id parameter below
	 *
	 * @param string $id the id of the status the delete
	 * @return string|false the string is unparsed data from the request
	 */
	public function destroyStatus( $id = '' ) {
		if(empty($id) && !is_integer($id)) {
			throw new xf_webservices_TwitterError( 5 );
			return false;
		}
		$url = self::URL_STATUS . 'destroy/'. $id .'.xml';
		return $this->authRequest($url);
	}	

	// User Methods
	
	/**
	 * userFollowing
	 *
	 * Retreive up to 100 of the users that the user follows who have most recently updated
	 *
	 * @return array|false indexed array in simplexml format
	 */
	public function userFollowing() {
		$url = self::URL_STATUS . 'friends.xml';
		return $this->following = $this->parseUserRsp( $url );
	}

	/**
	 * userFollowers
	 *
	 * Retreive the user's followers
	 *
	 * @return array|false indexed array in simplexml format
	 */
	public function userFollowers() {
		$url = self::URL_STATUS . 'followers.xml';
		return $this->followers = $this->parseUserRsp( $url );
	}

	/**
	 * userFeatured
	 *
	 * Retreive the list of the users currently featured on the site with their current statuses inline.
	 *
	 * @return array|false indexed array in simplexml format
	 */
	public function userFeatured() {
		$url = self::URL_STATUS . 'featured.xml';
		return $this->featured = $this->parseUserRsp( $url );
	}
	
	/**
	 * userShow
	 *
	 * Retreive a single user's data, specified by the id parameter below
	 *
	 * @param string $id the id of the user to retreive
	 * @return array|false indexed array in simplexml format
	 */
	public function userShow( $id = '' ) {
		if(empty($id)) {
		throw new xf_webservices_TwitterError( 5 );
			return false;
		}
		$url = self::URL_TWITTER . 'users/show/'. $id .'.xml';
		$data = $this->request( $url, 'GET', true, true );
		if(!$data) return false;
		
		$xml = simplexml_load_string($data);
		$prtd = "protected";	
		
		return array(
			'userid' => (string) $xml->id,
			'name'  =>  (string) $xml->name,
			'screen_name' => (string) $xml->screen_name,
			'location' => (string) $xml->location,
			'description' => (string) $xml->description,
			'profile_image_url' => (string) $xml->profile_image_url,
			'url' => (string) $xml->url,
			'protected' => (string) $xml->$prtd,
			'bg_color' => (string) $xml->profile_background_color,
			'text_color' => (string) $xml->profile_text_color,
			'link_color' => (string) $xml->profile_link_color,
			'sidebar_bg_color' => (string) $xml->profile_sidebar_fill_color,
			'sidebar_border_color' => (string) $xml->profile_sidebar_border_color,
			'following_count' => (string) $xml->friends_count,
			'followers_count' => (string) $xml->followers_count,
			'favourites_count' => (string) $xml->favourites_count,
			'utc_offset' => (string) $xml->utc_offset,
			'bg_image' => (string) $xml->profile_background_image,
			'bg_tile' => (string) $xml->profile_background_tile,
			'statuses_count' => (string) $xml->statuses_count,
			'status' => array(
				'created_at' => (string) $xml->status->created_at,
				'id' => (string) $xml->status->id,
				'text' => (string) $xml->status->text,
				'source' => (string) $xml->status->source
			)
		);
	}
	
	// Direct Message Methods
	 
	/**
	 * directMessages
	 *
	 * list 20 most recent direct messages sent to the authenticating user
	 *
	 * @return array|false indexed array in simplexml format
	 */
	public function directMessages() {
		$url = self::URL_TWITTER . 'direct_messages.xml';
		return $this->directMessages = $this->parseMessagesRsp( $url );	
	}
	
	/**
	 * sentMessages
	 *
	 * list 20 most recent direct messages sent by the authenticating user
	 *
	 * @return array|false indexed array in simplexml format
	 */
	public function sentMessages() {
		$url = self::URL_TWITTER . 'direct_messages/sent.xml';
		return $this->sentMessages = $this->parseMessagesRsp( $url );	
	}
	
	/**
	 * newMessage
	 *
	 * Post a new direct message to the specified user from the user
	 *
	 * @param string $for the id of the user to send the message to
	 * @param string $message the message content
	 * @return string|false the string is unparsed data from the request
	 */
	public function newMessage( $for = '', $message = '' ) {
		if(empty($for) || empty($message)) {
			throw new xf_webservices_TwitterError( 8 );
			return false;
		}
		if (strlen($message) >= self::MAX_LENGTH) {
			throw new xf_webservices_TwitterError( 8 ); 
			return false;
		}	
		$url = self::URL_TWITTER . 'direct_messages/new.xml?user='. $for .'&text='. urlencode( stripslashes( urldecode( trim($message) ) ) );
		return $this->authRequest( $url );
	}
	
	/**
	 * destroyMessage
	 *
	 * Delete a single message, specified by the id parameter below
	 *
	 * @param string $id the id of the message to delete
	 * @return string|false the string is unparsed data from the request
	 */
	public function destroyMessage( $id = '' ) {
		if(empty($id) && !is_integer($id)) {
			throw new xf_webservices_TwitterError( 5 );
			return false;
		}
		$url = self::URL_TWITTER . 'direct_messages/destroy/'. $id .'.xml';
		return $this->authRequest( $url );
	}
	
	// Following Methods
	
	/**
	 * follow
	 *
	 * Start following a user, specified by the id parameter below
	 *
	 * @param string $id the id of the user to follow
	 * @return string|false the string is unparsed data from the request
	 */
	public function follow( $id = '' ) {
		if(empty($id)) {
			throw new xf_webservices_TwitterError( 5 );
			return false;
		}
		$url = self::URL_TWITTER . 'friendships/create/'. $id .'.xml';
		return $this->authRequest($url);
	}
	
	/**
	 * destroyFollow
	 *
	 * Stop following a user, specified by the id parameter below
	 *
	 * @param string $id the id of the user to stop following
	 * @return string|false the string is unparsed data from the request
	 */
	public function destroyFollow( $id = '' ) {
		if(empty($id)) {
			throw new xf_webservices_TwitterError( 5 );
			return false;
		}
		$url = self::URL_TWITTER . 'friendships/destroy/'. $id .'.xml';
		return $this->authRequest($url);
	}
	
	// PRIVATE METHODS
	
	/**
	 * parseStatus
	 *
	 * Parse an individual status
	 *
	 * @param node $n the simplexml node to parse
	 * @return array|false this is an indexed array
	 */
	private function parseStatus( $n ) {
		$prtd = "protected";	
		return array(
			'id' =>  (string) $n->id,
			'created_at' => (string) $n->created_at,
			'text' => (string) $n->text,
			'source' => (string) $n->source,
			'user' => array(
				'userid' => (string) $n->user->id,
				'name' => (string) $n->user->name,
				'screen_name' => (string) $n->user->screen_name,
				'location' => (string) $n->user->location,
				'description' => (string) $n->user->description,
				'profile_image_url' => (string) $n->user->profile_image_url,
				'url' => (string) $n->user->url,
				'protected' => (string) $n->user->$prtd
			)
		);
	}
	
	/**
	 * parseTimelineRsp
	 *
	 * Parse the timeline XML response
	 *
	 * @param string $url the URL to send a request to
	 * @return array|false this is an indexed array
	 */
	private function parseJsonRsp( $url = '' ) {
		$data = $this->request( $url );
		if( !$data ) return false;
		$decoded = json_decode( $data );
		return $decoded;
	}
	
	/**
	 * parseTimelineRsp
	 *
	 * Parse the timeline XML response
	 *
	 * @param string $url the URL to send a request to
	 * @return array|false this is an indexed array
	 */
	private function parseTimelineRsp( $url = '' ) {
		$data = $this->authRequest( $url );
		if( !$data ) return false;
		$xml = simplexml_load_string( $data );
		$a = array();	
		foreach ( $xml->status as $n ) { 
			array_push( $a, $this->parseStatus( $n ) );
		}
		return $a;
	}
	
	/**
	 * parseUserRsp
	 *
	 * Parse the user XML response
	 *
	 * @param string $url the URL to send a request to
	 * @return array|false this is an indexed array
	 */
	private function parseUserRsp( $url = '' ) {
		$data = $this->authRequest( $url );
		if( !$data ) return false;
			
		$xml = simplexml_load_string( $data );
		$prtd = "protected";
		$a = array();
		foreach ( $xml->user as $n ) {
			array_push( $a, array(
				'userid' => (string) $n->id,
				'name' =>  (string) $n->name,
				'screen_name' => (string) $n->screen_name,
				'location' => (string) $n->location,
				'description' => (string) $n->description,
				'profile_image_url' => (string) $n->profile_image_url,
				'url' => (string) $n->url,
				'protected' => (string) $n->$prtd,
				'status' => array(
					'id' => (string) $n->status->id,
					'created_at' => (string) $n->status->created_at,
					'text' => (string) $n->status->text,
					'source' => (string)$n->status->source
				)
			));
		}
		return $a;
	}

	/**
	 * parseMessagesRsp
	 *
	 * Parse the messages XML response
	 *
	 * @param string $url the URL to send to request a response from
	 * @return array|false this is an indexed array
	 */
	private function parseMessagesRsp( $url = '' ) {
		$data = $this->authRequest( $url );
		if( !$data ) return false;	
		
		$xml = simplexml_load_string( $data );
		$a = array();
		foreach ( $xml->direct_message as $n ) { 
			array_push( $a, array(
				'id' => (string) $n->id,
				'text' => (string) $n->text,
				'sender_id' => (string) $n->sender_id,
				'recipient_id' => (string) $n->recipient_id,
				'created_at' => (string) $n->created_at,
				'sender_screen_name' => (string) $n->sender_screen_name,
				'recipient_screen_name' => (string) $n->recipient_screen_name
			));
		}
		return $a;
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
	private function initCurl( $url = '', $method = 'GET', $auth = false, $verbose = false ) {
		if( !function_exists( 'curl_init' ) ) {
			throw new xf_webservices_TwitterError( 1 );
			return false;
		}
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		// added Expect: for new twitter stuff
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Expect:'));
		// set the request method
		switch($method) {
			case 'POST' :
				curl_setopt( $ch, CURLOPT_POST, true);
			break;
		}
		if( $auth ) {
			curl_setopt( $ch, CURLOPT_USERPWD, "$this->username:$this->password" );
		}
		if( $verbose ) curl_setopt( $ch, CURLOPT_VERBOSE, 1 );
		return $ch;		
	}
}
// END CLASS
?>