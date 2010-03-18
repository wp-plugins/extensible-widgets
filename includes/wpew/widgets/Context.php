<?php
/**
 * This file defines wpew_widgets_Context, an Extensible Widget class.
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
 * This is the base wpew Widget. It is not much on its own, but is meant to be extended. 
 * wpew widgets are not available outside of this framework.
 *
 * @package wpew
 * @subpackage widgets
 */

// START class
class wpew_widgets_Context extends wpew_widgets_Widget {
	
	/**
	 * @see wpew_AWidget::$tabLabel
	 */
	public static $tabLabel = 'Context';
	
	/**
	 * @see wpew_AWidget::getDefaultSettings()
	 */
	public static function getDefaultSettings( &$obj ) {
		return array(
			'context' => 'exc',
			'context_calls' => array(),
			'context_args' => array(),
		);
	}
	
	// STATIC MEMBERS
	
	/**
	 * @see wpew_IWidget::save()
	 */
	public static function save( &$obj, $new_settings ) {
		$obj->settings['context'] = $new_settings['context'];
		// Check if this was serialized
		if( wpew_Widgets::isSerialized( 'context_calls', $new_settings ) ) {
			wpew_Widgets::unserialize( 'context_calls', $new_settings );
			$obj->settings['context_calls'] = $new_settings['context_calls'];
		} else if( is_array($new_settings['context_calls']) ) {
			$obj->settings['context_calls'] = array_values( array_filter( $new_settings['context_calls'] ) );
		} else {
			$obj->settings['context_calls'] = array();
		}
		// Check if this was serialized
		if( wpew_Widgets::isSerialized( 'context_args', $new_settings ) ) {
			wpew_Widgets::unserialize( 'context_args', $new_settings );
			$obj->settings['context_args'] = $new_settings['context_args'];
		} else {
			foreach( $new_settings['context_args'] as $key => $value ) {
				if( in_array( $key, $obj->settings['context_calls'], true ) ) {
					$split = array_filter( split( ',', $value ) );
					$arr = array();
					foreach( $split as $item ) {
						$arr[] = trim($item);
					}
					if( $key == 'is_sticky' ) {
						$new_settings['context_args'][$key] = array( array_shift( $arr ) );
					} else {
						$new_settings['context_args'][$key] = $arr;
					}
				} else {
					unset( $new_settings['context_args'][$key] );
				}
			}
			$obj->settings['context_args'] = array_filter( $new_settings['context_args'] );
		}
	}

	// INSTANCE MEMBERS
	
	/**
	 * @see wpew_AWidget::$tabular
	 */
	public $tabular = true;
	
	/**
	 * Create new instance
	 */
	public function __construct( $name = '', $wOpts = array(), $cOpts = array() )
	{
		// Set Name
		if( empty( $name ) ) $name = __('Context');
		// Set Options
		$wOpts = wp_parse_args( $wOpts, array(
			'description' => __( "More basic options that would be good for any widget, but this widget is specifically used for controlling where widgets appear." )
		));
		$cOpts = wp_parse_args( $cOpts, array(
			'width' => 300
		));
		// parent constructor
		parent::__construct( $name, $wOpts, $cOpts );
	}
	
	/**
	 * inContext
	 *
	 * Uses the settings of 'context', 'context_calls', and 'context_args' to make callbacks until it reaches a true.
	 * If a true is encountered, then it returns the opposite context boolean.
	 * If none are encountered, then it simply returns the context boolean in it's normal state.
	 *
	 * @return bool
	 */
	final public function inContext() {
		$inContext = ( $this->settings['context'] == 'inc' ) ? false : true;
		if( is_array( $this->settings['context_calls'] ) ) {
			while( $callback = array_shift( $this->settings['context_calls'] ) ) {
				$args = ( isset( $this->settings['context_args'][$callback] ) ) ? $this->settings['context_args'][$callback] : array();
				if( count($args) > 1 ) $args = array( $args );
				if( call_user_func_array( $callback, $args ) ) return !$inContext; 
			}
		}
		return $inContext;
	}
	
	/**
	 * @see WP_Widget::display_callback()
	 */
	public function widget_display_callback( $instance ) {
		// add context filter
		self::$manager->removeFilter( 'widget_display_callback', false, $this );
		$this->settings = $instance;
		return ( $this->inContext() ) ? $instance : false;
	}
	
	/**
	 * @see WP_Widget::display_callback()
	 */
	final public function display_callback( $args, $widget_args = 1 ) {
		// add context filter
		self::$manager->addFilter( 'widget_display_callback', false, $this );
		WP_Widget::display_callback( $args, $widget_args );
	}
}
// END class
?>