<?php
/**
 * This file defines xf_wp_AExtension, an abstract 
 * object to extend other objects.
 * 
 * PHP version 5
 * 
 * @package    xf
 * @subpackage wp
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

require_once('ASingleton.php');
require_once('APlugin.php');

/**
 * This is an abstract class and is meant to be extended.
 *
 * This class is used specifically to extend other objects.
 * Within this subpackage it is used to extend xf_wp_APlugin instances.
 * It is a way for singletons within the plugin singleton to be
 * aware of eachother methods and properties.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage wp
 */
abstract class xf_wp_AExtension extends xf_wp_ASingleton {
	
	// STATIC MEMBERS
	
	/**
	 * @ignore
	 * A Static property holding all references from extension classes to plugin objects
	 */
	private static $_plugins = array();
	
	// INSTANCE MEMBERS
	
	/**
	 * @see xf_wp_ASingleton::__construct()
	 */
	public function __construct( $unregistrable = __CLASS__ )
	{
		parent::__construct( $unregistrable );
	}
	
	/**
	 * Checks if this object and this class is currently an extension of a plugin
	 *
	 * @return bool
	 */
	final public function hasReference() {
		return isset(self::$_plugins[$this->className]);
	}
	
	/**
	 * Set the plugin reference for the extension
	 *
	 * @return void
	 */
	final public function setReference( xf_wp_APlugin &$plugin ) {
		self::$_plugins[$this->className] =& $plugin;
		$this->doLocalAction( 'onSetReference' );
	}
	
	/**
	 * Removes the reference of this extension to it's plugin
	 *
	 * @return bool
	 */
	final public function removeReference() {
		if( $this->hasReference() ) return false;
		$this->doLocalAction( 'onRemoveReference' );
		unset( self::$_plugins[$this->className] );
		return true;
	}
	
	/**
	 * Magic Override - Define where to retrieve magic properties from
	 * This is the unique quality of extensions, It checks the property 
	 * isn't set on the current extension, and if it isn't It checks of the 
	 * property is set on the plugin also, if it is, it returns that.
	 *
	 * @param string $n Name of the undefined property
	 * @return mixed
	 */
	public function &__get( $n ) {
		if( $this->hasReference() ) {
			if( !isset($this->$n) && isset(self::$_plugins[$this->className]->$n) ) return self::$_plugins[$this->className]->$n;
		}
		return parent::__get( $n );
	}
	
	/**
	 * Magic - Define where to call methods from
	 * This is the unique quality of extensions, It checks the property 
	 * isn't set on the current extension, and if it isn't It checks of the 
	 * property is set on the plugin also, if it is, it returns that.
	 *
	 * @param string $n Name of the undefined property
	 * @return mixed
	 */
	public function &__call( $n, $arguments ) {
		if( $this->hasReference() ) {
			if( !method_exists($this, $n) && method_exists(self::$_plugins[$this->className], $n) ) {
				return call_user_func_array( array(self::$_plugins[$this->className], $n), $arguments );
			}
		}
		$this->error( 'Call to undefined method '.$this->className.'::'.$n.'()', 3 );
	}
	
	// RESERVED PROPERTIES
	
	/**
	 * @property xf_wp_Plugin $plugin A property common to all instances of this class
	 */
	final public function &get__plugin() {
		if( $this->hasReference() ) return self::$_plugins[$this->className];
		return null;
	}
	final public function set__plugin( xf_wp_APlugin &$v ) {
		if( is_object($v) && $v instanceof xf_wp_APlugin ) {
			$this->setReference( $v );
		} else {
			$this->removeReference();
		}
	}
}
?>