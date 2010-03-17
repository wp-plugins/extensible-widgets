<?php
/**
 * This file defines xf_utils_Misc, a utility of miscellaneous methods.
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
 * This class is a compilation of various functions and methods 
 * that help with miscellaneous tasks that to not have place in their own class (yet).
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage utils
 */
class xf_utils_Misc {
	
	// notices
	public static $_notices;
		 
	/**
	 * Retrieves the time the has elapsed from one date to another.
	 * This actually returns a string that outputs "{time unit} {time until label} ago"
	 *
	 * @param number $dateFrom
	 * @param number $dateTo
	 * @return string
	 */
	public static function getTimeAgo( $dateFrom, $dateTo=-1 ) {
		// Defaults and assume if 0 is passed in that, its an error rather than the epoch
		if( $dateFrom <= 0 ) return "A long time ago";
		if( $dateTo == -1 ) $dateTo = time();
		// Calculate the difference in seconds betweeen the two timestamps
		$diff = $dateTo - $dateFrom;
		// If difference is less than 60 seconds, seconds is a good interval of choice
		if($diff < 60) {
			$interval = "s";
		} else if( $diff >= 60 && $diff < 60*60 ) {
			// If difference is between 60 seconds and 60 minutes, minutes is a good interval
			$interval = "n";
		} else if( $diff >= 60*60 && $diff < 60*60*24 ) {
			// If difference is between 1 hour and 24 hours hours is a good interval
			$interval = "h";
		} else if( $diff >= 60*60*24 && $diff < 60*60*24*7 ) {
			// If difference is between 1 day and 7 days days is a good interval
			$interval = "d";
		} else if( $diff >= 60*60*24*7 && $diff < 60*60*24*30 ) {
			// If difference is between 1 week and 30 days weeks is a good interval
			$interval = "ww";
		} else if( $diff >= 60*60*24*30 && $diff < 60*60*24*365 ) {
			// If difference is between 30 days and 365 days months is a good interval, again, the same thing
			// applies, if the 29th February happens to exist between your 2 dates, the function will return the 'incorrect' value for a day
			$interval = "m";
		} else if( $diff >= 60*60*24*365 ) {
			// If difference is greater than or equal to 365 days, return year. This will be incorrect if
			// for example, you call the function on the 28th April 2008 passing in 29th April 2007. It will return
			// 1 year ago when in actual fact (yawn!) not quite a year has gone by
			$interval = "y";
		}
		// Based on the interval, determine the number of units between the two dates from this point on, you would be hard
		// pushed telling the difference between this function and DateDiff. If the $dateDiff returned is 1,
		// be sure to return the singular of the unit, e.g. 'day' rather 'days'
		switch($interval) {
			case "m":
				$monthsDiff = floor($diff / 60 / 60 / 24 / 29);
				while (mktime(date("H", $dateFrom), date("i", $dateFrom), date("s", $dateFrom), date("n", $dateFrom)+($monthsDiff), date("j", $dateTo), date("Y", $dateFrom)) < $dateTo) {
					$monthsDiff++;
				}
				$dateDiff = $monthsDiff;
				// We need this in here because it is possible to have an 'm' interval and a months
				// difference of 12 because we are using 29 days in a month
				if( $dateDiff == 12 ) {
					$dateDiff--;
				}
				$res = ( $dateDiff == 1 ) ? "$dateDiff month ago" : "$dateDiff
				months ago";
			break;
			case "y":
				$dateDiff = floor($diff / 60 / 60 / 24 / 365);
				$res = ( $dateDiff == 1 ) ? "$dateDiff year ago" : "$dateDiff
				years ago";
			break;
			case "d":
				$dateDiff = floor($diff / 60 / 60 / 24);
				$res = ( $dateDiff == 1 ) ? "$dateDiff day ago" : "$dateDiff
				days ago";
			break;
			case "ww":
				$dateDiff = floor($diff / 60 / 60 / 24 / 7);
				$res = ( $dateDiff == 1 ) ? "$dateDiff week ago" : "$dateDiff
				weeks ago";
			break;
			case "h":
				$dateDiff = floor($diff / 60 / 60);
				$res = ( $dateDiff == 1 ) ? "$dateDiff hour ago" : "$dateDiff
				hours ago";
			break;
			case "n":
				$dateDiff = floor($diff / 60);
				$res = ( $dateDiff == 1 ) ? "$dateDiff minute ago" :
				"$dateDiff minutes ago";
			break;
			case "s":
				$dateDiff = $diff;
				$res = ( $dateDiff == 1 ) ? "$dateDiff second ago" :
				"$dateDiff seconds ago";
			break;
		}
		return $res;
	}
	
	/**
	 * Just a quick way to add a string via id to a group of other strings
	 *
	 * @return void
	 */
	public static function addNotice( $notice, $id = '' ) {
		if( empty( $notice ) ) return;
		if( !isset( self::$_notices[$id] ) ) self::$_notices[$id] = array();
		self::$_notices[$id][] = $notice;
	}
	
	/**
	 * Now we can render all strings added via the same id (in the same group)
	 *
	 * @return void
	 */
	public static function renderNotices( $id = '', $before = '<p>', $after = '</p>' ) {
		if( !isset(self::$_notices[$id]) ) return;
		foreach( array_filter( self::$_notices[$id] ) as $message ) {
			echo $before.$message.$after;
		}
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