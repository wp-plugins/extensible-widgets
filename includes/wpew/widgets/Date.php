<?php
/**
 * This file defines wpew_widgets_Date, an Extensible Widget class.
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
 * but is still higher up in the inheritance tree. Use this widget to select a 
 * view template and handle any arbitrary date.
 *
 * @package wpew
 * @subpackage widgets
 */

// START class
class wpew_widgets_Date extends wpew_widgets_View {
	
	// STATIC MEMBERS
	
	/**
	 * @see wpew_AWidget::$tabLabel
	 */
	public static $tabLabel = 'Date';
	
	//public $adminRenderFlags = array(1,0,0,0);
	//public $tabular = false;
		
	/**
	 * @see wpew_AWidget::getDefaultSettings()
	 */
	public static function getDefaultSettings( &$obj ) {
		// get the current date for the default
		$curr_date = time() + ( get_option( 'gmt_offset' ) * 3600 );
		return array( 
			'jj' => gmdate( 'd', $curr_date ), // date
			'mm' => gmdate( 'm', $curr_date ), // month
			'aa' => gmdate( 'Y', $curr_date ), // year
			'hh' => gmdate( 'H', $curr_date ), // hour
			'mn' => gmdate( 'i', $curr_date ), // minute
			'ss' => gmdate( 's', $curr_date ), // second
		);
	}
	
	/**
	 * @see wpew_IWidget::save()
	 */
	public static function save( &$obj, $new_settings ) {
		$obj->settings['jj'] = strip_tags( $new_settings['jj'] ); // date
		$obj->settings['mm'] = strip_tags( $new_settings['mm'] ); // month
		$obj->settings['aa'] = strip_tags( $new_settings['aa'] ); // year
		$obj->settings['hh'] = strip_tags( $new_settings['hh'] ); // hour
		$obj->settings['mn'] = strip_tags( $new_settings['mn'] ); // minute
		$obj->settings['ss'] = strip_tags( $new_settings['ss'] ); // second
	}
	
	// INSTANCE MEMBERS
	
	// CONSTRUCTOR
	public function __construct( $name = '', $wOpts = array(), $cOpts = array() )
	{
		// Set Name
		if( empty( $name ) ) $name = __('Date');
		// Set Options
		$wOpts = wp_parse_args( $wOpts, array(
			'description' => __( "Use this widget to select a view template and handle any arbitrary date." )
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
		/* TODO: Convert the date to the correct date format that can be converted with other standard date functions */
		$this->settings['view_params']['date'] = array(
			'jj' => $this->settings['jj'], // date
			'mm' => $this->settings['mm'], // month
			'aa' => $this->settings['aa'], // year
			'hh' => $this->settings['hh'], // hour
			'mn' => $this->settings['mn'], // minute
			'ss' => $this->settings['ss'], // second
		);
	}
	
	/**
	 * @see wpew_widgets_IView::defaultView()
	 */
	public function defaultView() {
		$date = $this->settings['view_params']['date'];
		// START DEFAULT
		/**
		 * TODO: Convert the date to the correct date format that can be converted with other standard date functions
		 */
		echo $date['mm'] . '/' . $date['jj'] . '/'.$date['aa'] . ' - ' . $date['hh'] . ':' . $date['mn'] . ':' . $date['ss'];
		// END DEFAULT
	}
}
// END class
?>