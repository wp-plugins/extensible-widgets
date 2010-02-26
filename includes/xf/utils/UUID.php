<?php
/**
 * This file defines xf_utils_UUID, a class used for object Universal Unique Identifiers
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
 * xf_utils_UUID
 *
 * Static class, it contains operations specific for generating a Universal Unique Identifier
 * Version 3, Version 4, and Version 5.
 * xf_Objects use Version 5 for self identification.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage utils
 */
 
// START CLASS
class xf_utils_UUID {
	
	// STATIC MEMBERS
		
	public static function isValid( $uuid ) {
		return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?'.
                      '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
	}
	
	/**
	 * Version 3 UUIDs use a scheme deriving a UUID via MD5 from a URL, a fully qualified domain name, an object identifier, a distinguished name (DN as used in Lightweight Directory Access Protocol), or on names in unspecified namespaces. Version 3 UUIDs have the form xxxxxxxx-xxxx-3xxx-xxxx-xxxxxxxxxxxx with hexadecimal digits x.
To determine the version 3 UUID of a given name, the UUID of the namespace, e.g. 6ba7b810-9dad-11d1-80b4-00c04fd430c8 for a domain, is transformed to a string of bytes corresponding to its hexadecimal digits, concatenated with the input name, hashed with MD5 yielding 128 bits. Six bits are replaced by fixed values, four of these bits indicate the version, 0011 for version 3. Finally the fixed hash is transformed back into the hexadecimal form with hyphens separating the parts relevant in other UUID versions.
	 */
	public static function v3( $namespace, $name ) {
		if( !self::isValid($namespace) ) return false;
		// Get hexadecimal components of namespace
		$nhex = str_replace(array('-','{','}'), '', $namespace);
		// Binary Value
		$nstr = '';
		// Convert Namespace UUID to bits
		for($i = 0; $i < strlen($nhex); $i+=2) {
			$n = ( empty($nhex[$i+1]) ) ? '' : $nhex[$i+1];
			$nstr .= chr(hexdec($nhex[$i].$n));
		}
	    // Calculate hash value
	    $hash = md5($nstr . $name);
	    return sprintf('%08s-%04s-%04x-%04x-%12s',
	    	// 32 bits for "time_low"
	    	substr($hash, 0, 8),
			// 16 bits for "time_mid"
			substr($hash, 8, 4),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 3
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
			// 48 bits for "node"
			substr($hash, 20, 12)
	    );
	}
	
	/**
	 * Version 4 UUIDs use a scheme relying only on random numbers. This algorithm sets the version number as well as two reserved bits. All other bits are set using a random or pseudorandom data source. Version 4 UUIDs have the form xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx with hexadecimal digits x and hexadecimal digits 8, 9, A, or B for y. e.g. f47ac10b-58cc-4372-a567-0e02b2c3d479.
	 */
	public static function v4() {
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}
	
	/**
	 * Version 5 UUIDs use a scheme with SHA-1 hashing, otherwise it is the same idea as in version 3. RFC 4122 states that version 5 is preferred over version 3 name based UUIDs. Note that the 160 bit SHA-1 hash is truncated to 128 bits to make the length work out.
	 */
	public static function v5( $namespace, $name ) {
		//if( !self::isValid($namespace) ) return false;
		// Get hexadecimal components of namespace
		$nhex = str_replace(array('-','{','}'), '', $namespace);
		// Binary Value
		$nstr = '';
		// Convert Namespace UUID to bits
		for($i = 0; $i < strlen($nhex); $i+=2) {
			$n = ( empty($nhex[$i+1]) ) ? '' : $nhex[$i+1];
			$nstr .= chr(hexdec($nhex[$i].$n));
		}
		// Calculate hash value
		$hash = sha1($nstr . $name);
		return sprintf('%08s-%04s-%04x-%04x-%12s',
			// 32 bits for "time_low"
			substr($hash, 0, 8),
			// 16 bits for "time_mid"
			substr($hash, 8, 4),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 5
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
			// 48 bits for "node"
			substr($hash, 20, 12)
		);
	}
}
// END CLASS
?>