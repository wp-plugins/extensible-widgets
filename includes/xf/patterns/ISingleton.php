<?php
/**
 * This file defines xf_patterns_ISingleton, an interface to use the singleton pattern.
 * 
 * PHP version 5
 * 
 * @package xf
 * @subpackage patterns
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @link http://jidd.jimisaacs.com
 */
		
/**
 * xf_patterns_ISingleton interface
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage patterns
 */

// START interface
interface xf_patterns_ISingleton {
	
	 /**
	 * &getInstance
	 *
	 * This is a standard method of any Singleton pattern, it must be defined it extended class to be truely a Singleton.
	 * @example
	 * 	public static function &getInstance() {
	 * 		return parent::getSingleton(__CLASS__);
	 * 	}
	 *
	 * @return object Instance of the class in which this method is defined
	 */
	public static function &getInstance();
}
// END interface
?>