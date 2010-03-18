<?php
/**
 * This file defines wpew_widgets_Widget, an Extensible Widget class.
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
 * It can still serve as a dynamic element.
 *
 * @package wpew
 * @subpackage widgets
 */
class wpew_widgets_Widget extends wpew_AWidget {
	
	/**
	 * @see wpew_AWidget::$tabLabel
	 */
	public static $tabLabel = 'Title/CSS';
	
	/**
	 * @see wpew_AWidget::getDefaultSettings()
	 */
	public static function getDefaultSettings( &$obj ) {
		return array( 
			'title' => '',
			'render_title' => 0,
			'add_css_class' => ''
		);
	}
	
	// STATIC MEMBERS
	
	/**
	 * @see wpew_IWidget::save()
	 */
	public static function save( &$obj, $new_settings ) {
		$obj->settings['title'] = $new_settings['title'];
		$obj->settings['render_title'] = $new_settings['render_title'] ? 1 : 0;
		//$obj->settings['add_css_class'] = str_replace( '-', '_', sanitize_title_with_dashes( $new_settings['add_css_class'] ) );
		$obj->settings['add_css_class'] = $new_settings['add_css_class'];
	}
	
	/**
	 * @see wpew_IWidget::beforeAdminOutput()
	 */
	public static function beforeAdminOutput( &$obj ) {}
	
	/**
	 * @see wpew_IWidget::afterAdminOutput()
	 */
	public static function afterAdminOutput( &$obj ) {}
	
	// INSTANCE MEMBERS
	
	/**
	 * @see wpew_AWidget::$tabular
	 */
	public $tabular = false;
	
	// CONSTRUCTOR
	public function __construct( $name = '', $wOpts = array(), $cOpts = array() )
	{
		// Set Name
		if( empty( $name ) ) $name = 'Widget Base';
		// Set Options
		$wOpts = wp_parse_args( $wOpts, array(
			'description' => __( "The base for Extensible Widgets and not much on its own, it can still serve as a useful dynamic element." )
		) );
		// parent constructor
		parent::__construct( $name, $wOpts, $cOpts );
	}
	
	/**
	 * @see wpew_IWidget::beforeOutput()
	 */
	public function beforeOutput() {}
	
	/**
	 * final render
	 *
	 * This method is final because no child widget should have to redefine this method.
	 * They should only need to redefine other methods like defaultOutput().
	 * @see wpew_IWidget::render()
	 */
	final public function render() {
		$title = apply_filters( 'widget_title', empty( $this->settings['title'] ) ? '' : $this->settings['title'] );
		$render_title = (bool) $this->settings['render_title'];
		$add_css_class = $this->settings['add_css_class'];
		
		// add class if there is one
		if( !empty( $add_css_class ) ) $this->args['before_widget'] = str_replace( $this->widget_options['classname'], $this->widget_options['classname'] . ' ' . $add_css_class, $this->args['before_widget'] );
				
		echo $this->args['before_widget'];
		
		if ( $render_title ) {
			echo $this->args['before_title'];
			echo $title; 
			echo $this->args['after_title'];
		}
		
		// call abstract
		$this->defaultOutput();
		
		echo $this->args['after_widget'];
	}
	
	/**
	 * @see wpew_IWidget::afterOutput()
	 */
	public function afterOutput() {}
	
	/**
	 * @see wpew_IWidget::defaultOutput()
	 */
	public function defaultOutput() {}
}
// END class
?>