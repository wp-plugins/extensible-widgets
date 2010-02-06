<?php
/**
 * This file defines xf_events_IEventDispatcher, which is the base 
 * interface for all event dispatcher objects in the Dispatcher pattern.
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
 * Base class for event objects in the Dispatcher pattern
 */
require_once('Event.php');

/**
 * Base interface for event dispatcher objects in the Dispatcher pattern.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage events
 */
interface xf_events_IEventDispatcher {
	
	/**
	 * Registers an event listener object with an xf_EventDispatcher 
	 * object so that the listener receives notification of an event.
	 *
	 * @param string $type The type of event.
	 * @param callback $listener The listener function that processes the event.
	 * @param int $priority  The priority level of the event listener. The priority is designated by a signed 32-bit integer. The higher the number, the higher the priority. All listeners with priority n are processed before listeners of priority n-1. If two or more listeners share the same priority, they are processed in the order in which they were added. The default priority is 0.
	 * @param bool $useWeakReference Determines whether the reference to the listener is strong or weak.
	 * @return void
	 */
	public function addEventListener( $type, $listener, $priority = 0, $useWeakReference = true );
	
	/**
	 * Dispatches an event into the event flow.
	 *
	 * @param xf_Event $event The Event object that is dispatched into the event flow. If the event is being redispatched, a clone of the event is created automatically. After an event is dispatched, its target property cannot be changed, so you must create a new copy of the event for redispatching to work.
	 * @return bool A value of true if the event was successfully dispatched. A value of false indicates failure or that preventDefault() was called on the event.
	 */
	public function dispatchEvent( xf_events_Event &$event );
	
	/**
	 * Checks whether the xf_EventDispatcher object has any listeners 
	 * registered for a specific type of event.
	 *
	 * @param string $type The type of event.
	 * @return bool A value of true if a listener of the specified type is registered; false otherwise.
	 */
	public function hasEventListener( $type );
	
	/**
	 * Checks whether the xf_EventDispatcher object has any listeners 
	 * registered for a specific type of event.
	 *
	 * @param string $type The type of event.
	 * @param callback $listener The listener to remove.
	 * @return void
	 */
	public function removeEventListener( $type, $listener );
}
?>
