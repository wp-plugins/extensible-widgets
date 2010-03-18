<?php
/**
 * This file defines xf_Object, which provides property based getter setter implemtations.
 * 
 * PHP version 5
 * 
 * @package    xf
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

/**
 * Exceptions thrown by getter and setter magic internals
 */
require_once('errors/ReferenceError.php');

/**
 * Utility to create Universal Unique identifiers
 */
require_once('utils/UUID.php');

/**
 * Base class for objects that allow getter setter implementations.
 *
 * This functionality is present in most Object Oriented languages, but 
 * not PHP (yet). You can add any property dynamically to this class, if 
 * that property is not already reserved with a getter, setter, or both.
 * Reserved properties are defined with getters and/or setters 
 * representing those properties.
 *
 * A getter method is preceded with the get prefix constant 
 * or "get__", and the method's name; which would look like this, 
 * "get__(property name)"
 * A setter method is preceded with the set prefix constant 
 * or "set__", and the method's name; which would look like this, 
 * "set__(property name)"
 *
 * Not defining a setter, but defining a getter defines a read-only property.
 * Not defining a getter, but defining a setter defines a write-only property.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 */
class xf_Object {
	
	// Constants
	const GET_PREFIX = 'get__';
	const SET_PREFIX = 'set__';
	
	// STATIC
	
	/**
	 * @ignore
	 */
	public static $_UUIDRefs = array();
	
	 /**
	 * Simply checks the static array if an object exists of the given ID and returns it.
	 *
	 * @return object|null The object by the given ID, or null if it does not exists.
	 */
	final public static function &getObjectByUUID( $uuid ) {
		$val = ( isset( self::$_UUIDRefs[$uuid] ) &&  is_object( self::$_UUIDRefs[$uuid] ) ) ? self::$_UUIDRefs[$uuid] : null;
		return $val;
    }
    
    /**
	 * Adds an object to the static UUID reference array.
	 *
	 * @return string The generated UUID of the provided object
	 */
	final public static function addUUIDObject( &$object ) {
		$uuid = array_search( $object, self::$_UUIDRefs, true );
		if( $uuid !== false ) return $uuid;
		$name = 'Object#'.count(self::$_UUIDRefs).'('.get_class($object).')';
		$uuid = xf_utils_UUID::v5( get_class($object), $name );
		self::$_UUIDRefs[$uuid] =& $object;
		return $uuid;
    }
	
	/**
	 * This is a recursive function that travels up the inheritance tree of an instance of a class.
	 * This should return an indexed array of class named in order of inheritance.
	 *
	 * @param object $obj The object to return the parent classes for
	 * @param bool $self Whether the array returned include the object class itself
	 * @param string|false $stop Parent class that stops recursion
	 * @param bool $includeStop Whehter to include the stop class in the returned array
	 * @param int $limit The limit of inheritance levels to traverse upward, -1 means to traverse all levels, 0 means none, and so forth
	 * @param array $plist Used programmatically for recursion, it passes this array to this method adding to it on each recursive call
	 * @return array Indexed array with values of parent class names in the order of inheritance from bottom up
	 */
	public static function getParentClasses( $obj, $self = false, $stop = false, $includeStop = true, $limit = -1, $plist = array() ) {
		if( $self && count( $plist ) == 0 ) $plist[] = get_class( $obj );
		$call = false;
		if( $parent = get_parent_class( $obj ) ) {
			$add = true;
			if( $stop == false ) {
				if( $limit < 0 || $limit > 0 ) {
					$call = true;
					if( $limit > 0 ) $limit--;
				}
			} else if( $stop != $parent ) {
				$call = true;
			} else {
				$add = $includeStop;
			}
			// should we add the parent to the array?
			if( $add ) array_unshift( $plist, $parent );
			// should we call this method again?
			if( $call ) $plist = self::getParentClasses( $parent, $self, $stop, $includeStop, $limit, $plist );
		}
		// reverse the array
		return $plist;
	}
    
	/**
	 * Simple static method to check if the string is a valid getter name
	 *
	 * @param string $n The string to validate
	 * @return bool
	 */
	final public static function validateGetterName( $n ) {
		return (bool) preg_match( '/^'.self::GET_PREFIX.'(.+)$/', $n );
    }
	
	/**
	 * Simple static method to convert a string to a getter method name.
	 *
	 * @param string $n The string to convert
	 * @return string
	 */
	final public static function getGetterName( $n ) {
		if( self::validateGetterName( $n ) ) return $n;
		return self::GET_PREFIX . $n;
    }
	
	/**
	 * Simple static method to check if the string is a valid setter name
	 *
	 * @param string $n The string to validate
	 * @return bool
	 */
	final public static function validateSetterName( $n ) {
        return (bool) preg_match( '/^'.self::SET_PREFIX.'(.+)$/', $n );
    }
	
	/**
	 * Simple static method to convert a string to a setter method name.
	 *
	 * @param string $n The string to convert
	 * @return string
	 */
	final public static function getSetterName( $n ) {
        if( self::validateSetterName( $n ) ) return $n;
		return self::SET_PREFIX . $n;
    }
	 
	/**
	 * Magic - This magic method should set all the properties of the new instance.
	 *
	 * @param an array of properties
	 * return: the instance
	 */
	final public static function __set_state( $arr ) {
        $obj = new self();
		foreach ( $arr as $k => $v ) {
			$obj->$k = $v;
		}
        return $obj;
    }
	
	// INSTANCE MEMBERS
	
	/**
	 * @var bool $_dynamic Internal flag that controls whether a class's instances can set properties dynamically
	 */
	protected $_dynamic = false;
	
	/**
	 * @ignore
	 * Used internally for process saving
	 */
	private $_className;
	/**
	 * @ignore
	 * Used internally for process saving
	 */
	private $_parentClassName;
	/**
	 * @ignore
	 * Used internally for process saving
	 */
	private $_parentClasses;
	/**
	 * @ignore
	 * Used internally for process saving
	 */
	private $_uuid;
	
	/**
	 * Create new instance
	 *
	 * @return void
	 */
	public function __construct()
	{
		// Removed because not every object needs a UUID right away
		//$this->_uuid = self::addUUIDObject( $this );
	}
	
	/**
	 * Removes this object UUID from the static array
	 *
	 * @ return void
	 */
	public function __destruct() {
		if( !empty( $this->_uuid ) ) unset( self::$_UUIDRefs[$this->uuid] );
    }
	
	// RESERVERED PROPERTIES
	
	/**
	 * @property-read string $className The class name of the object
	 */
	final public function get__className() {
		if( !empty( $this->_className ) ) return $this->_className;
		return $this->_className = get_class( $this );
	}
	
	/**
	 * @property-read string $parentClassName The parent class name of the object
	 */
	final public function get__parentClassName() {
		if( !empty( $this->_parentClassName ) ) return $this->_parentClassName;
		return $this->_parentClassName = get_parent_class( $this );
	}
	
	/**
	 * @property-read array $parentClasses An array of all parent classes of the object returned by reference
	 */
	final public function &get__parentClasses() {
		if( !empty( $this->_parentClasses ) ) return $this->_parentClasses;
		return $this->_parentClasses = self::getParentClasses( $this );
	}
	
	/**
	 * @property-read array $uuid A Universally Unique Identifier for the object
	 * @see xf_utils_UUID
	 */
	final public function get__uuid() {
		if( !empty( $this->_uuid ) ) return $this->_uuid;
		return $this->_uuid = self::addUUIDObject( $this );
	}
	
	/**
	 * Checks if there is getter method for the named property
	 *
	 * @param string $n The property name to check
	 * @return bool
	 */
	final public function hasGetter( $n ) {
		return method_exists( $this, self::getGetterName( $n ) );
	}
	
	/**
	 * Checks if there is setter method for the property
	 *
	 * @param string $n The property name to check
	 * @return bool
	 */
	final public function hasSetter( $n ) {
		return method_exists( $this, self::getSetterName( $n ) );
	}
	
	/**
	 * Checks if there is getter or setter method for the property
	 *
	 * @param string $n The property name to check
	 * @return bool
	 */
	final public function isReserved( $n ) {
		return ( $this->hasGetter( $n ) || $this->hasSetter( $n ) );
	}
	
	// MAGIC METHODS
	
	/**
	 * Magic - Define where to check for magic properties
	 * First checks if property is reserved then checks if it has been set dynamically.
	 *
	 * @param string $n The property name
	 * @return bool
	 */
	public function __isset( $n ) {
		$bool = $this->isReserved( $n );
		if(!$bool) $bool = isset( $this->$n );
		return $bool;
	}
	
	/**
	 * Magic - Define where to delete magic properties from
	 * If property is not reserved unset it.
	 *
	 * @param string $n The property name
	 * @return void
	 */
	public function __unset( $n ) {
		if( $this->isReserved( $n ) ) {
			throw new xf_errors_ReferenceError( 4, $this, $n );
		} else if( !$this->_dynamic ) {
			throw new xf_errors_ReferenceError( 5, $this, $n );
		} else {
			unset( $this->$n );
		}
	}
	
	/**
	 * Magic - Define where to add magic properties
	 * If property is reserved and has a setter call the setter.
	 * Otherwise set a dynamic property.
	 *
	 * @param string $n The property name
	 * @param mixed $v The property value
	 * @return void
	 */
	public function __set( $n, $v ) {
		if( $this->hasSetter( $n ) ) {
			$n = self::getSetterName($n);
			$this->$n( $v );
			//call_user_func( array( &$this, self::getSetterName( $n ) ), $v );
		} else if( $this->isReserved( $n ) ) {
			throw new xf_errors_ReferenceError( 3, $this, $n );
		} else if( !$this->_dynamic ) {
			throw new xf_errors_ReferenceError( 6, $this, $n );
		} else {
			$this->$n = $v;
		}
	}
	
	/**
	 * Magic - Define where to retrieve magic properties from
	 * If property has a getter, call the getter, otherwise 
	 * try to get a dynamic property.
	 *
	 * @param string $n The property name
	 * @return mixed The property value
	 */
	public function &__get( $n ) {
		if( $this->hasGetter( $n ) ) {
			$n = self::getGetterName($n);
			$v = $this->$n();
			//$v = call_user_func( array( &$this, self::getGetterName( $n ) ) );
			return $v;
		} else if( $this->isReserved( $n ) ) {
			throw new xf_errors_ReferenceError( 2, $this, $n );
		} else if( !$this->_dynamic ) {
			throw new xf_errors_ReferenceError( 1, $this, $n );
		}
		return $this->$n;
	}
	
	/**
	 * Magic - Define how this object is represented as a string
	 * This function is called only in later minor versions of PHP 5
	 * Older minor versions of PHP 5 bypass this method with a builtin implementation.
	 * Regardless, the object is still represented as a unique string when converted.
	 *
	 * @return string Representation of this object as a string
	 */
	public function __toString() {
		return 'Object#'.uniqid().'('.$this->className.')';
	}
}
?>