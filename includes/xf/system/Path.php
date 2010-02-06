<?php
/**
 * This file defines xf_system_Path, which is used for
 * system path string operations (cross platform).
 * 
 * PHP version 5
 * 
 * @package    xf
 * @subpackage system
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

/**
 * Static class, it contains string operations specific to a 
 * file system's path This should deals with paths in a 
 * cross platform environment.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage system
 */
class xf_system_Path {

	// CONTSTANT MEMBERS

	/**
	 * @ignore
	 * Just a shortcut to the global PHP constant.
	 */
	const DS = DIRECTORY_SEPARATOR;

	// STATIC MEMBERS

	/**
	 * Test if a given filesystem path is actually absolute
	 * '/foo/bar', 'c:\windows'
	 *
	 * @param unknown $p
	 * @return bool
	 */
	public static function isAbs( $p ) {
		// this is definitive if true but fails if $path does not exist or contains a symbolic link
		if ( realpath( $p ) == $p ) return true;
		if ( strlen( $p ) == 0 || $p{0} == '.' ) return false;
		if ( preg_match('#^[a-zA-Z]:\\\\#', $p) ) return true;
		// a path starting with / or \ is absolute; anything else is relative
		return (bool) preg_match('#^[/\\\\]#', $p);
	}

	/**
	 * Join a path with another with absolute path checking on the appended path.
	 * First checks if the appending path is absolute, it is return it without appending it.
	 * If it isn't then continue on, trim any excess slashes on the ends.
	 *
	 * @param string $p The base path
	 * @parem string $append The path to append to the base
	 * @param string $append overloaded
	 * @return string The joined path
	 */
	public static function join( $p, $append ) {
		if ( empty( $append ) ) return $p;
		if ( self::isAbs( $append ) ) return $append;
		$joined = rtrim( $p, self::DS ) . self::DS . ltrim( $append, '.'.self::DS );
		$count = func_num_args();
		if( $count > 2 ) {
			$args = func_get_args();
			for( $i=2 ; $i < $count ; $i++ ) {
				$joined = self::join( $joined, $args[$i] );
			}
		}
		return $joined;
	}

	/**
	 * Removes a part of a path, essentially this is a wrapper arround str_replace.
	 *
	 * @parem string $remove The part to remove
	 * @param string  $p      The path to edit
	 * @param unknown $remove
	 * @return string The edited path
	 */
	public static function replace( $p, $remove ) {
		return trim( str_replace( $remove, '', $p ), self::DS );
	}
}
?>