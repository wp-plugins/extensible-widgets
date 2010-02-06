<?php
/**
 * This file defines xf_events_EventDispatcher, which is the base 
 * class for all event dispatcher objects in the Dispatcher pattern.
 * 
 * PHP version 5
 * 
 * @package    xf
 * @subpackage events
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

/**
 * Base class for objects that allow getter setter implementations
 */
require_once(dirname(__FILE__).'/../Object.php');

/**
 * Exceptions thrown by fatally missiterpreted method arguments
 */
require_once(dirname(__FILE__).'/../errors/ArgumentError.php');

/**
 * Interface used by any event dispatcher in the Dispatcher pattern
 */
require_once('IEventDispatcher.php');

/**
 * Base Class for event objects in the Dispatcher pattern
 */
require_once('Event.php');

/**
 * Base Class for event dispatcher objects in the Dispatcher pattern
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage events
 */
class xf_events_EventDispatcher extends xf_Object implements xf_events_IEventDispatcher {

	// STATIC MEMBERS
	
	/**
	 * public static property used for cross class communication in ready only instance properties
	 */
	public static $switchBoard = null;
	/**
	 * @ignore
	 * Used internally for process saving
	 */
	public static $currentEvent = null;
	/**
	 * @ignore
	 * Used internally for process saving
	 */
	public static $currentDispatcher = null;
	
	/**
	 * @ignore
	 * Used internally for process saving on the event flow
	 */
	private static $_listeners = array();
	/**
	 * @ignore
	 * Used internally for process saving on the event flow
	 */
	private static $_priorityHeap = array();
	/**
	 * @ignore
	 * Used internally for process saving on the event flow
	 */
	private static $_dispatchSorted = array();
	
	/**
	 * Build Unique ID for storage and retrieval.
	 * Functions and static method callbacks are just returned as strings and
	 * shouldn't have any speed penalty.
	 *
	 * @param string $event Used in counting how many hooks were applied
	 * @param callback $listener Used for creating unique id
	 * @param int|bool $priority Used in counting how many hooks were applied. If === false and $listener is an object reference, we return the unique id only if it already has one, false otherwise.
	 * @param string $type filter or action
	 * @return string|bool Unique ID for usage as array key or false if $priority === false and $listener is an object reference, and it does not already have a uniqe id.
	 */
	protected static function getListenerUUID( $listener ) {
		if( !is_callable( $listener ) ) return false;
		$uuid = array_search( $listener, self::$_listeners, true );
		if( $uuid !== false ) return $uuid;
		if ( is_string($listener) ) { // Old school global PHP function
			return $listener;
		} else if(is_object($listener)) { // An Object that may be invoked, for only later versions of PHP
			return xf_Object::addUUIDObject( $listener );
		} else if (is_object($listener[0]) ) { // Regular instance method referenced as - array( $object, 'methodName' )
			return xf_Object::addUUIDObject( $listener[0] ).$listener[1];
		} else if ( is_string($listener[0]) ) { // Static method referenced as - array( Class, 'staticMethodName' )
			return $listener[0].$listener[1];
		}
		return false;
	}
	
	/**
	 * @ignore
	 * Gets the Listener object by the specified ID
	 *
	 * @return object|false
	 */
	protected static function &getListenerByUUID( $uuid ) {
		if( isset(self::$_listeners[$uuid]) ) return self::$_listeners[$uuid];
		return null; 
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * Create new instance
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		self::$_priorityHeap[$this->uuid] = array();
	}
	
	/**
	 * Removes this object UUID from the priority heap
	 *
	 * @return void
	 */
	public function __destruct() {
		unset( self::$_priorityHeap[$this->uuid] );
		unset( self::$_dispatchSorted[$this->uuid] );
    }
	
	/**
	 * @see xf_IEventDispatcher::addEventListener()
	 */
	public function addEventListener( $type, $listener, $priority = 0, $useWeakReference = true ) {
		// UUID will not be returned if listener is not callable
		if( $uuid = self::getListenerUUID( $listener ) ) {
			// The weak reference refers to whether we should make a copy of the callback (strong - Stays in memory).
			// Or if it be should set to a reference only (weak - Garbage collected).
			if( $useWeakReference && !is_string($listener) ) {
				self::$_listeners[$uuid] =& $listener;
			} else {
				self::$_listeners[$uuid] = $listener;
			}
			// Make sure to remove the listener if it has already been added.
			// This is important to avoid duplicate calls withint the heap.
			$this->removeEventListener( $type, $listener );
			$heap =& $this->getHeap($type);
			$heap[$priority][$uuid] = $useWeakReference;
			// The heap may need sorting again
			$this->setTypeSortFlag( false, $type );
		} else {
			throw new xf_errors_ArgumentError( 3, 2, $listener, 'Expected valid callback' );
		}
	}
	
	/**
	 * @see xf_IEventDispatcher::dispatchEvent()
	 */
	public function dispatchEvent( xf_events_Event &$event ) {
		// If a tree falls with nobody there to hear it, does it make a sound?
		if( !$this->hasEventListener($event->type) ) return;
		// If event already has a target, then it's being redispatched, so make a clone to change target
		if( !empty( $event->target ) ) $event = clone $event;
		// Set static, important for xf_events_Event propagation methods
		self::$currentEvent =& $event;
		// Make sure to give the event object a UUID
		// Use the static method in case the event is not extending the xf_Object
		$uuid = xf_Object::addUUIDObject( $event );
		// Round about away to set a read only property in another class
		// First set the public static member
		self::$switchBoard = $this->uuid;
		// Then call a getter which uses $switchBoard to set a private member in the event class
		self::$currentDispatcher =& $event->currentTarget;
		// Now null it out, that is all
		self::$switchBoard = null;
		// Sort listener priority heap
		$heap =& $this->getHeap($event->type);
		// The heap only has to be sorted once unless a listener was added or removed, this checks to see if it has been
		if( !isset( self::$_dispatchSorted[$this->uuid][$event->type] ) ) {
			ksort($heap); // Sort by priority key
			$this->setTypeSortFlag( true, $event->type );
		}
		// The following might look a little scary, but it performs better than anything I've tested.
		// do-while loops using internal array pointers do not create any unnecessary temporary variables.
		// Internal pointers use what is already there, leaving the overall event flow free of unnecessary memory usage.
		end( $heap );
		do {
			$current =& current($heap);
			end($current);
			do {
				$uuid = key($current);
				$listener =& self::getListenerByUUID( $uuid );
				if( !is_null($listener) ) call_user_func( $listener, $event ); // Listner couldn't have been added if not callable
				if( !is_object( self::$currentEvent ) ) break; // $event->stopPropagation() or $event->stopImmediatePropagation() was called (set to false)
			} while ( prev($current) !== false );
			if( is_null( self::$currentEvent ) ) break; // $event->stopImmediatePropagation() was called (set to null)
		} while ( prev($heap) !== false );
		// Null out! dispatcher is finished propagating event
		self::$currentEvent = null;
		self::$currentDispatcher = null;
		return true;
	}
	
	/**
	 * @see xf_IEventDispatcher::hasEventListener()
	 */
	public function hasEventListener( $type ) {
		if( !isset( self::$_priorityHeap[$this->uuid][$type] ) ) return false;
		return ( count(self::$_priorityHeap[$this->uuid][$type]) > 0 );
	}
	
	/**
	 * @see xf_IEventDispatcher::removeEventListener()
	 */
	public function removeEventListener( $type, $listener ) {
		$priority = $this->getListenerPriority( $type, $listener );
		if( $priority < 0 ) return;
		if( $uuid = self::getListenerUUID( $listener ) ) {
			$heap =& $this->getHeap($type);
			unset( $heap[$priority][$uuid] );
			if( !count($heap[$priority]) ) unset( $heap[$priority] );
			// The heap may need sorting again
			$this->setTypeSortFlag( false, $type );
		}
	}
	
	/**
	 * Remove all of the listeners from a type of event.
	 * Optionally remove all listeners within a given priority heap.
	 *
	 * @param string $type The type of event.
	 * @param int $priority The priority number to remove.
	 * @return void
	 */
	public function removeAllEventListeners( $type, $priority = -1 ) {
		if( $this->hasEventListener($type) ) {
			$heap =& $this->getHeap($type);
			if( $priority >= 0 && isset($heap[$priority]) ) {
				unset($heap[$priority]);
			} else {
				unset( self::$_priorityHeap[$this->uuid][$type] );
			}
		}
		// The heap may need sorting again
		$this->setTypeSortFlag( false, $type );
	}
	
	/**
	 * @ignore
	 * Helper function to manipulate the static $_priorityHeap array
	 */
	protected function &getHeap( $type = null ) {
		if( empty($type) ) {
			return self::$_priorityHeap[$this->uuid];
		} else if( !isset( self::$_priorityHeap[$this->uuid][$type] ) ) {
			self::$_priorityHeap[$this->uuid][$type] = array();
		}
		return self::$_priorityHeap[$this->uuid][$type];
	}
	
	/**
	 * @ignore
	 * Helper function to manipulate the static $_dispatchSorted array
	 */
	protected function setTypeSortFlag( $flag, $type ) {
		if( $flag ) {
			self::$_dispatchSorted[$this->uuid][$type] = true;
		} else {
			if( isset(self::$_dispatchSorted[$this->uuid][$type]) ) unset(self::$_dispatchSorted[$this->uuid][$type]);
		}
	}
		
	/**
	 * @ignore
	 * Loops through the given listener type and provides the priority if it exists.
	 * This returns -1 if there is no listener, and therefore no priority.
	 */
	protected function getListenerPriority( $type, $listener ) {
		if( empty($listener) || !$this->hasEventListener($type) ) return -1;
		if( !$uuid = self::getListenerUUID($listener) ) return -1;
		$heap =& $this->getHeap($type);
		foreach( $heap as $priority => $ids ) {
			if( array_key_exists( $uuid, $ids ) ) return $priority;
		}
		return -1;
	}
}
?>
