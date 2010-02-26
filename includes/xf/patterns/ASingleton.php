<?php
/**
 * This file defines xf_patterns_ASingleton, an abstract class to use the singleton pattern.
 * 
 * PHP version 5
 * 
 * @package xf
 * @subpackage patterns
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @link http://jidd.jimisaacs.com
 */

require_once(dirname(__FILE__).'/../errors/DefinitionError.php');
require_once(dirname(__FILE__).'/../Object.php');
require_once('ISingleton.php');

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
 * @subpackage patterns
 */
abstract class xf_patterns_ASingleton extends xf_Object implements xf_patterns_ISingleton {
	
	// STATIC MEMBERS
	
	/**
	 * @ignore
	 * Classes that are used to stop Singleton inheritence registration
	 */
	private static $_unregistrable = array();
	
	/**
	 * @ignore
	 * Static instance memory to keep instantiation under wraps.
	 */
	private static $_instances = array();
	
	/**
	 * @see xf_ISingleton::addUnregistrable()
	 */
	final public static function addUnregistrable( $class ) {
		self::$_unregistrable[ $class ] = $class;
	}
	
	/**
	 * @see xf_ISingleton::setSingletonInstance()
	 */
	final public static function setSingletonInstance( &$instance, $unregistrable = __CLASS__ ) {
		$allClasses = xf_Object::getParentClasses( $instance, true, $unregistrable, false );
		self::addUnregistrable( $unregistrable );
		$registered = array();
		foreach( $allClasses as $class ) {
			if( array_key_exists( $class, self::$_unregistrable )  ) break;
			self::$_instances[$class] =& $instance;
			$registered[] = $class;
		}
		return $registered;
	}
	
	/**
	 * @see xf_ISingleton::setSingletonClass()
	 */
	final public static function &setSingletonClass( $class, $unregistrable = __CLASS__ ) {
		$instance = new $class();
		self::setSingletonInstance( $instance, $unregistrable );
		return $instance;
	}
	
	/**
	 * @see xf_ISingleton::isSingleton()
	 */
	final public static function isSingleton( $class ) {
		return array_key_exists( $class, self::$_instances );
	}
	
	/**
	 * @see xf_ISingleton::getSingleton()
	 */
	final public static function &getSingleton( $class ) {
		if( self::isSingleton( $class ) ) return self::$_instances[$class];
		return self::setSingletonClass( $class );
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * This constructor is different than normal Singleton patterns.
	 * Here we almost duplicate the functionality of the registerSingleton method but instead use $this as our instance.
	 * Essentially we can register a class as a Singleton simply upon calling the constructor.
	 * There is an error thrown if you try to call the constructor again which is more like the regular Singleton pattern.
	 */
	public function __construct()
	{
		if( self::isSingleton( $this->className ) ) {
			throw new xf_errors_DefinitionError( 4, $this->className );
		} else {
			// parent constructor
			parent::__construct();
			// here is the weirdness that seems to work!
			self::setSingletonInstance( $this );
		}
	}
}
?>