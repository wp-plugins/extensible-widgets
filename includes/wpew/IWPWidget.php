<?php
/**
 * This file defines wpew_IWPWidget, the interface for WordPress Widgets.
 * 
 * PHP version 5
 * 
 * @package wpew
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @link http://jidd.jimisaacs.com
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * This insterface represents what should be defined for a regular WordPress widget.
 *
 * @package wpew
 */
interface wpew_IWPWidget {
	
	// INSTANCE MEMBERS
	
	/**
	 * update
	 * 
	 * This is a method that should sanatize and specify any last ditch effort to controlt data before going to the database.
	 * Think of it as a place where you should set the old settings with the new settings but selectively.
	 * @param array $new_settings the settings that were passed when submitting the form within the admin
	 * @param array $old_settings the current settings data from the database
	 * @return array the settings data to send to the database
	 */
	public function update( $new_settings, $old_settings );
	
	/**
	 * form
	 * 
	 * Output the widget admin controls within the bqck-end
	 * @param array $settings the current settings data
	 * @return void
	 */
	public function form( $settings );
	
	/**
	 * widget
	 * 
	 * Output the widget within the front-end
	 * @param array $args these are the arguments of the widget group passed when registered
	 * @param array $settings the current settings data
	 * @return void
	 */
	public function widget( $args, $settings );
		
}
?>