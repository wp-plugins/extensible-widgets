<?php
/**
 * This file defines wpew_widgets_Content, an Extensible Widget class.
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
 * but is still higher up in the inheritance tree. Use this widget to enter any 
 * data (ex: text/HTML/XML/JavaScript) and optionally access it within in a view template.
 *
 * @package wpew
 * @subpackage widgets
 */

// START class
class wpew_widgets_Content extends wpew_widgets_View {
	
	// STATIC MEMBERS
	
	/**
	 * @see wpew_AWidget::$tabLabel
	 */
	public static $tabLabel = 'Content';
	
	/**
	 * @see wpew_AWidget::getDefaultSettings()
	 */
	public static function getDefaultSettings( &$obj ) {
		return array( 
			'content' => ''
		);
	}
	
	/**
	 * @see wpew_IWidget::save()
	 */
	public static function save( &$obj, $new_settings ) {
		$obj->settings['content'] = $new_settings['content']; // no sanitizing on this variable
	}
	
	// INSTANCE MEMBERS
	
	// CONSTRUCTOR
	public function __construct( $name = '', $wOpts = array(), $cOpts = array() )
	{		
		// Set Name
		if( empty( $name ) ) $name = __('Content');
		// Set Options
		$wOpts = wp_parse_args( $wOpts, array(
			'description' => __( "Use this widget to enter any data (ex: text/HTML/XML/JavaScript) and optionally access it within in a view template." )
		) );
		$cOpts = wp_parse_args( $cOpts, array(
			'width' => 400
		) );
		// parent constructor
		parent::__construct( $name, $wOpts, $cOpts );
	}
	
	/**
	 * @see wpew_IWidget::beforeOutput()
	 */
	public function beforeOutput() {
		// call parent
		parent::beforeOutput();
		// Add the data to the view params added by the parent class, this way you can access the data extracted in the view!
		$this->settings['view_params']['content'] = apply_filters( 'widget_text', $this->settings['content'] );
	}
	
	/**
	 * @see wpew_widgets_IView::defaultView()
	 */
	public function defaultView() {
		// START DEFAULT
		echo $this->settings['view_params']['content'];
		// END DEFAULT
	}
}
// END class
?>