<?php
/**
 * This file defines xf_utils_Formatting, a utility for string formatting.
 * 
 * PHP version 5
 * 
 * @package    xf
 * @subpackage utils
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

/**
 * Exceptions thrown by conditions that would result in definition errors
 */
require_once(dirname(__FILE__).'/../errors/DefinitionError.php');

/**
 * Contains various functions and methods that help with various string formating tasks.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage utils
 */
class xf_utils_Formatting {
	
	/**
	 * Removes various levels of characters from a string.
	 *
	 * @param $str string What will be sanitized
	 * @param $level int An integer representing how much sanitizing will occur.
	 * The higher the number, the more sanitization.
	 * @return string
	 */
	static function sanitize( $str, $level = 1 ) {
		// level 7 = only alpha lower case - converts to uppercase
		if( $level > 6 ) {
			$str = preg_replace('|[^A-Z]|i', '', strtoupper($str) );
		// level 6 = only alpha lower case - converts to lowercase
		// Good for markup tags
		} else if( $level > 5 ) {
			$str = preg_replace('|[^a-z]|i', '', strtolower($str) );
		// level 5 = only alphanumeric upper or lowercase
		// Good for any slug part
		} else if( $level > 4 ) {
			$str = preg_replace('|[^a-zA-Z0-9]|i', '', $str);
		// level 4 = only alphanumeric upper and lowercase
		// Good for email slugs, and other very strict strings
		} else if( $level > 3 ) {
			$str = preg_replace('|[^a-zA-Z0-9 \-]|i', '', $str);
		// level 3 = only alphanumeric upper and lowercase, with underscores
		// Underscores are used for a lot these days
		// Felt necessary to have one level devoted to them
		} else if( $level > 2 ) {
			$str = preg_replace('|[^a-zA-Z0-9 _\-]|i', '', $str);
		}
		// level 3 and above = Turns multiple dashes and whitespace into single dashes
		// also trims all dashes from the sides
		if( $level > 2 ) {
			$str = preg_replace('|\s+|', '-', $str);
			$str = trim( preg_replace('|\-+|', '-', $str), '-');
			return $str; // return NOW
		}
		// level 2 = reduce to ASCII for max portability
		// Good for email addresses
		if( $level > 1 ) {
			$str = preg_replace('|[^a-zA-Z0-9 _.\-@]|i', '', $str);
		// level 1 = Kill octets and entities
		// Good for cross-site scripting safety
		} else if( $level > 0 ) {
			$str = strip_tags($str);
			$str = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $str);
			$str = preg_replace('/&.+?;/', '', $str);
		}
		// level 1 and above = Consolidate contiguous whitespace
		if( $level > 0 ) {
			$str = preg_replace('|\s+|', ' ', $str);
		}
		// level 0 = raw
		return $str;
	}
	
	/**
	 * Create new instance - This is a static class, trigger an error
	 *
	 * @return void
	 */
	public function __construct()
	{
		throw new xf_errors_DefinitionError( 3, __CLASS__ );
	}
}
?>