<?php
/**
 * This file defines xf_wp_ASingleton, a branch of xf/patterns
 * subpackage that is specific to the xf/wp subpackage
 * 
 * PHP version 5
 * 
 * @package    xf
 * @subpackage wp
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

require_once(dirname(__FILE__).'/../patterns/ISingleton.php');
require_once(dirname(__FILE__).'/../patterns/ASingleton.php');
require_once('APluggable.php');

/**
 * This is an abstract class and is meant to be extended.
 *
 * This class is a combination of two patterns, Singleton and Factory.
 * It lets you only allow one instance of classes.
 * You may extend this class, and as long as you call this constructor or the register method, then the extended class is a Singleton.
 * Any class that is registered as a singleton can only be instantiated once, and this class throws and error if it is tried.
 *
 * This includes the entire class hierarchy for a particular class all the way until but not including this class and it's parents.
 * For the hierarchy of a class registered as a Singleton...
 * All the parent classes that are also registered reference the same instance of the class that is responsible for registering them.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage wp
 */
abstract class xf_wp_ASingleton extends xf_wp_APluggable implements xf_patterns_ISingleton {
	
	// INSTANCE MEMBERS
	
	/**
	 * Create a new instance
	 * This constructor is different than normal Singleton patterns.
	 * Here we almost duplicate the functionality of the registerSingleton method but instead use $this as our instance.
	 * Essentially we can register a class as a Singleton simply upon calling the constructor.
	 * There is an error thrown if you try to call the constructor again which is more like the regular Singleton pattern.
	 *
	 * @return void
	 */
	public function __construct( $unregistrable = __CLASS__ )
	{
		if( xf_patterns_ASingleton::isSingleton( $this->className ) ) {
			throw new xf_errors_DefinitionError( 4, $this->className );
		} else {
			// parent constructor
			parent::__construct();
			// Add class xf_wp_ASingleton to never be registered as a Singleton.
			// This is because this is an abstract class, and we only want classes that extend this to be Singletons.
			xf_patterns_ASingleton::setSingletonInstance( $this, $unregistrable );
		}
	}
}
?>