<?php
/**
 * This file defines xf_events_ErrorEvent.
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
 * Base class for objects that allow getter setter implementations.
 */
require_once('Event.php');

/**
 * Base class in the this subpackage's exception implementation
 */
require_once(dirname(__FILE__).'/../errors/Error.php');

/**
 * An object dispatches an ErrorEvent object when an error causes a network operation to fail.
 * There is only one type of error event: ErrorEvent.ERROR.
 * Base class for more specific error events (ex: IOErrorEvent, AsyncErrorEvent)
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage events
 */
class xf_events_ErrorEvent extends xf_events_Event {
	
	// CONSTANTS
	
	/**
	 * The xf_events_ErrorEvent::ERROR constant defines the value of the type property of a error event object.
	 */
	const ERROR = 'error';
 	 	 
	// INSTANCE MEMBERS
	
	/**
	 * @ignore
	 * Used internally for read-only property
	 */
	private $_id;
	
	/**
	 * @ignore
	 * Used internally for read-only property
	 */
	private $_text;
	
	/**
	 * Create new instance to pass as a parameter to event listeners.
	 *
	 * @param string $type
	 * @param bool $cancelable
	 * @param string $text Text to be displayed as an error message.
	 * @param int $id A reference number to associate with the specific error.
	 * @return void
	 */
	public function __construct( $type, $cancelable = false, $text = '', $id = 0 )
	{
		parent::__construct( $type, $cancelable );
		$this->_text = $text;
		$this->_id = $id;
	}
	
	// RESERVERED PROPERTIES
	
	/**
	 * @property-read int $id A reference number to associate with the specific error.
	 */
	public function get__id() {
		return $this->_id;
	}
	
	/**
	 * @property-read string $text Text to be displayed as an error message.
	 */
	public function get__text() {
		return $this->_text;
	}
	
	// MAGIC METHODS
	
    /**
	 * Magic - Custom string representation of object
	 *
	 * @return string
	 */
    public function __toString() {
    	return '['.$this->className.'('.$this.') type="'.xf_errors_Error::emptyVarToString($this->type).'" cancelable="'.xf_errors_Error::emptyVarToString($this->cancelable).'" target="'.xf_errors_Error::emptyVarToString($this->currentTarget).'" text="'.xf_errors_Error::emptyVarToString($this->text).'" id="'.xf_errors_Error::emptyVarToString($this->id).'"]'.PHP_EOL;
    }
}
?>