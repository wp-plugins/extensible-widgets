<?php
/**
 * This file defines wpew_widgets_IView, an Extensible Widget interface.
 * 
 * PHP version 5
 * 
 * @package wpew
 * @subpackage widgets
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @link http://jidd.jimisaacs.com
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * wpew_widgets_IView interface
 *
 * All of this interface is defined in the wpew_widgets_View, but it is 
 * a map of what other widgets that will mimic or extend this class should include or override.
 * @package wpew
 * @subpackage widgets
 */

// START interface
interface wpew_widgets_IView {
	
	/**
	 * getViewsDir
	 *
	 * Gets current widget's directory and appends the view directory name to the end of it.
	 * @return string|false filepath, or false if no directory was returned from manager
	 */
	public function getViewsDir();
	
	/**
	 * getViews
	 *
	 * Reads the views directory and looks for files beginning with a "View Name:" comment
	 * @return array associative array of all the available views for that widget
	 */
	public function getViews();
	
	/**
	 * viewsDropdown
	 *
	 * Outputs a dropdown menu of all the views returned by the available widget
	 * @param string $default the view that should be selected within the dropdown
	 * @return void
	 */
	public function viewsDropdown( $default = '' );
	
	/**
	 * defaultView
	 *
	 * Called from the defaultOutput() method if there is nothing to output.
	 * This should be overridden by child classes to change the default view to be specific for that class.
	 * @return void
	 */
	public function defaultView();
	
}
// END interface
?>