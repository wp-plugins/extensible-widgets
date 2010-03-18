<?php
/**
 * This file defines wpew_widgets_QueryPostsExtended, an Extensible Widget class.
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
 * This is an example of a widget that used the previous widget's functionality, 
 * but is still higher up in the inheritance tree. This widget is a simplified 
 * version of the Query Posts widget, for beginners.
 *
 * @package wpew
 * @subpackage widgets
 */

// START class
class wpew_widgets_QueryPostsExtended extends wpew_widgets_QueryPosts {
	
	// STATIC MEMBERS
	
	/**
	 * @see wpew_AWidget::$tabLabel
	 */
	public static $tabLabel = 'QP Extended';
	
	/**
	 * @see wpew_AWidget::getDefaultSettings()
	 */
	public static function getDefaultSettings( &$obj ) {
		return array( 'query' => array() );
	}
	
	/**
	 * @see wpew_IWidget::save()
	 */
	public static function save( &$obj, $new_settings ) {
		// Check if this was serialized
		// Also this works a bit differently to, we don't want to run this function is the the form was rendered
		if( wpew_Widgets::isSerialized( 'query', $new_settings ) && !isset($new_settings['post_type']) ) return;
		// overwrite the query setting with the settings from the form
		$obj->settings['query'] = array_filter( wp_parse_args( array(
			'showposts' => (int) $new_settings['showposts'],
			'post_parent' => (int) $new_settings['post_parent'],
			'post_type' => $new_settings['post_type'],
			'post_status' => $new_settings['post_status'],
			'orderby' => $new_settings['orderby'],
			'order' => $new_settings['order'],
			'meta_key' => $new_settings['meta_key'],
			'meta_value' => $new_settings['meta_value'],
			'meta_compare' => $new_settings['meta_compare'],
			'post__in' => array_filter( split( ',', $new_settings['post__in'] ) ),
			'post__not_in' => array_filter( split( ',', $new_settings['post__not_in'] ) )
		), $obj->settings['query'] ) );
	}
	
	// INSTANCE MEMBERS
	
	// CONSTRUCTOR
	public function __construct( $name = '', $wOpts = array(), $cOpts = array() )
	{
		// Set Name
		if( empty( $name ) ) $name = __('QP Extended');
		// Set Options
		$wOpts = wp_parse_args( $wOpts, array(
			'description' => "This is an extended version of the Query Posts widget with a controlled form."
		) );
		$cOpts = wp_parse_args( $wOpts, array(
			'width' => 380
		) );
		// parent constructor
		parent::__construct( $name, $wOpts, $cOpts );
	}
}
// END class
?>