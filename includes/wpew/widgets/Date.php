<?php

require_once('View.php');

/**
 * wpew_widgets_Date class
 * 
 * This is an example of a widget that used the previous widget's functionality, but is still higher up in the inheritance tree.
 * Use this widget to select a view template and handle any arbitrary date.
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
	
	/**
	 * @see wpew_IWidget::renderAdmin()
	 */
	public static function renderAdmin( &$obj ) {
		$jj = esc_attr( $obj->settings['jj'] );
		$mm = esc_attr( $obj->settings['mm'] );
		$aa = esc_attr( $obj->settings['aa'] );
		$hh = esc_attr( $obj->settings['hh'] );
		$mn = esc_attr( $obj->settings['mn'] );
		$ss = esc_attr( $obj->settings['ss'] ); ?>
		
		<fieldset class="setting_group">
			<legend class="description">Special View Parameter:</legend>
			
			<p><label for="<?php echo $obj->get_field_id('mm'); ?>"><?php _e('Date:'); ?></label><br />
			<?php
			global $wp_locale;
			echo "<select id=\"" . $obj->get_field_id('mm') . "\" name=\"" . $obj->get_field_name('mm') . "\">\n";
			for ( $i = 1; $i < 13; $i = $i +1 ) {
				echo "\t\t\t" . '<option value="' . zeroise($i, 2) . '"';
				if ( $i == $mm )
					echo ' selected="selected"';
				echo '>' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) . "</option>\n";
			}
			echo '</select>';
			?>
			<input type="text" id="<?php echo $obj->get_field_id('jj'); ?>" name="<?php echo $obj->get_field_name('jj'); ?>" value="<?php echo $jj; ?>" size="2" maxlength="2" autocomplete="off" />
			<input type="text" id="<?php echo $obj->get_field_id('aa'); ?>" name="<?php echo $obj->get_field_name('aa'); ?>" value="<?php echo $aa; ?>" size="4" maxlength="4" autocomplete="off" />
			<input type="text" id="<?php echo $obj->get_field_id('hh'); ?>" name="<?php echo $obj->get_field_name('hh'); ?>" value="<?php echo $hh; ?>" size="2" maxlength="2" autocomplete="off" />
			<input type="text" id="<?php echo $obj->get_field_id('mn'); ?>" name="<?php echo $obj->get_field_name('mn'); ?>" value="<?php echo $mn; ?>" size="2" maxlength="2" autocomplete="off" />
			<input type="hidden" id="<?php echo $obj->get_field_id('ss'); ?>" name="<?php echo $obj->get_field_name('ss'); ?>" value="<?php echo $ss; ?>" /></p>
			
			<p><small class="description">Access this in a custom view with <code>$date</code>.</small></p>
		
		</fieldset>
		
	<?php }
	
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