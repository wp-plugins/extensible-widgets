<?php
/**
 * This file defines xf_events_Event, which is the base 
 * class for all event objects in the Dispatcher pattern.
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
 * Base class in the this subpackage's exception implementation
 */
require_once(dirname(__FILE__).'/../errors/Error.php');

/**
 * Base class for objects that allow getter setter implementations.
 */
require_once(dirname(__FILE__).'/../Object.php');

/**
 * Base class for event objects in the Dispatcher patter.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage events
 */
class xf_events_Event extends xf_Object {
	
	// CONSTANTS
	
	/**
	 * The xf_events_Event::ACTIVATE constant defines the value of the type property of an activate event object.
	 */
	const ACTIVATE = 'activate';
	/**
	 * The xf_events_Event::ADDED constant defines the value of the type property of an added event object.
	 */
	const ADDED = 'added';
	/**
	 * The xf_events_Event::CANCEL constant defines the value of the type property of a cancel event object.
	 */
	const CANCEL = 'cancel';
	/**
	 * The xf_events_Event::CHANGE constant defines the value of the type property of a change event object.
	 */
	const CHANGE = 'change';
	/**
	 * The xf_events_Event::CLOSE constant defines the value of the type property of a close event object.
	 */
	const CLOSE = 'close';
	/**
	 * The xf_events_Event::COMPLETE constant defines the value of the type property of a complete event object.
	 */
	const COMPLETE = 'complete';
	/**
	 * The xf_events_Event::CONNECT constant defines the value of the type property of a connect event object.
	 */
	const CONNECT = 'connect';
	/**
	 * The xf_events_Event::DEACTIVATE constant defines the value of the type property of a deactivate event object.
	 */
	const DEACTIVATE = 'deactivate';
	/**
	 * The xf_events_Event::INIT constant defines the value of the type property of an init event object.
	 */
	const INIT = 'init';
	/**
	 * The xf_events_Event::OPEN constant defines the value of the type property of an open event object.
	 */
	const OPEN = 'open';
	/**
	 * The xf_events_Event::REMOVED constant defines the value of the type property of a removed event object.
	 */
	const REMOVED = 'removed';
	/**
	 * The xf_events_Event::BEFORE_RENDER constant defines the value of the type property of a beforeRender event object.
	 */
	const BEFORE_RENDER = 'beforeRender';
	/**
	 * The xf_events_Event::RENDER constant defines the value of the type property of a render event object.
	 */
	const RENDER = 'render';
	/**
	 * The xf_events_Event::AFTER_RENDER constant defines the value of the type property of a afterRender event object.
	 */
	const AFTER_RENDER = 'afterRender';
 	 	 
	// INSTANCE MEMBERS
	
	/**
	 * @ignore
	 * Used internally for process saving
	 */
	private $_cancelled = false;
	/**
	 * @ignore
	 * Used internally for process saving
	 */
	private $_cancelable;
	/**
	 * @ignore
	 * Used internally for process saving
	 */
	private $_currentTarget = null;
	/**
	 * @ignore
	 * Used internally for process saving
	 */
	private $_target = null;
	/**
	 * @ignore
	 * Used internally for process saving
	 */
	private $_type;
		
	/**
	 * Create new instance to pass as a parameter to event listeners.
	 *
	 * @param string $type
	 * @param bool $cancelable
	 * @return void
	 */
	public function __construct( $type, $cancelable = false )
	{
		parent::__construct();
		$this->_type = $type;
		$this->_cancelable = $cancelable;
	}
	
	/**
	 * An instance was cloned
	 *
	 * @param string $type
	 * @param bool $cancelable
	 * @return void
	 */
	public function __clone()
	{
		$this->_currentTarget = null;
		$this->_target = null;
	}
	
	// RESERVERED PROPERTIES
	
	/**
	 * @property-read bool $cancelable Indicates whether the behavior associated with the event can be prevented.
	 */
	public function get__cancelable() {
		return $this->_cancelable;
	}
	
	/**
	 * @property-read bool $currentTarget The object that is actively processing the Event object with an event listener.
	 */
	public function &get__currentTarget() {
		if( is_object( $this->_currentTarget ) ) return $this->_currentTarget;
		return $this->_currentTarget =& xf_Object::getObjectByUUID( xf_events_EventDispatcher::$switchBoard );
	}
	
	/**
	 * @property-read bool $target The object that is actively processing the Event object with an event listener.
	 *
	 * @TODO Yes I know that this property is the same as currentTarget, 
	 * but this exists for future implementations of Event Dispatching in PHP.
	 */
	public function &get__target() {
		if( is_object( $this->_target ) ) return $this->_target;
		$this->_target =& $this->currentTarget;
	}
	
	/**
	 * @property-read string $type The type of event.
	 */
	public function get__type() {
		return $this->_type;
	}
	
	// METHODS
	
	/**
	 * Cancels an event's default behavior if that behavior can be canceled.
	 * Many events have associated behaviors that are carried out by default.
	 * 
	 * @return void
	 */
	public function preventDefault() {
		$this->_cancelled = true;
	}
	
	/**
	 * Prevents processing of any event listeners in the current node and 
	 * any subsequent nodes in the event flow. This method takes effect 
	 * immediately, and it affects event listeners in the current node. 
	 * In contrast, the stopPropagation() method doesn't take effect until 
	 * all the event listeners in the current node finish processing.
	 * 
	 * @return void
	 */
	public function stopImmediatePropagation() {
		// Nulling xf_events_EventDispatcher::$currentEvent cancels the current listener loop and the dispatch loop
		// as well as releasing the event object from memory
		xf_events_EventDispatcher::$currentEvent = null;
	}
	
	/**
	 * Prevents processing of any event listeners in nodes subsequent to 
	 * the current node in the event flow. This method does not affect any 
	 * event listeners in the current node (currentTarget). In contrast, 
	 * the stopImmediatePropagation() method prevents processing of event 
	 * listeners in both the current node and subsequent nodes. Additional 
	 * calls to this method have no effect. This method can be called in 
	 * any phase of the event flow.
	 *
	 * @TODO Yes I know that this is the same as stopImmediatePropagation, 
	 * but this exists for future implementations of Event Dispatching in PHP.
	 * 
	 * @return void
	 */
	public function stopPropagation() {
		// Setting xf_events_EventDispatcher::$currentEvent to false cancels the dispatch loop
		xf_events_EventDispatcher::$currentEvent = false;
	}
	
	// MAGIC METHODS
	
    /**
	 * Magic - Custom string representation of object
	 *
	 * @return string
	 */
    public function __toString() {
    	return '['.$this->className.'('.$this.') type="'.xf_errors_Error::emptyVarToString($this->type).'" cancelable="'.xf_errors_Error::emptyVarToString($this->cancelable).'" target="'.xf_errors_Error::emptyVarToString($this->currentTarget).'"]'.PHP_EOL;
    }
}
?>