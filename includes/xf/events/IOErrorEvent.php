<?php
/**
 * This file defines xf_events_IOErrorEvent.
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
 * Base class for more specific error events
 */
require_once('ErrorEvent.php');

/**
 * Dispatched when an error causes a send or load operation to fail (I/O over a network)
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage events
 */
class xf_events_IOErrorEvent extends xf_events_ErrorEvent {
	
	// CONSTANTS
	
	/**
	 * The xf_events_ErrorEvent::IO_ERROR constant defines the value of the type property of a ioError event object.
	 */
	const IO_ERROR = 'ioError';
	
	// INSTANCE MEMBERS
	
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
		parent::__construct( $type, $cancelable, $text, $id );
	}
}
?>