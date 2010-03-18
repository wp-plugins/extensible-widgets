<?php
/**
 * This file defines wpew_widgets_View, an Extensible Widget class.
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
 * but is still higher up in the inheritance tree. Use the view template control 
 * system and pass custom parameters to display data in any desired format.
 *
 * @package wpew
 * @subpackage widgets
 */

// START class
class wpew_widgets_View extends wpew_widgets_Context implements wpew_widgets_IView {
	
	// STATIC MEMBERS
	
	/**
	 * @see wpew_AWidget::$tabLabel
	 */
	public static $tabLabel = 'View';
	
	/**
	 * @see wpew_AWidget::getDefaultSettings()
	 */
	public static function getDefaultSettings( &$obj ) {
		return array(
			'view_type' => 'widget',
			'view_filename' => '',
			'view_params' => array()
		);
	}
	
	/**
	 * @see wpew_IWidget::save()
	 */
	public static function save( &$obj, $new_settings ) {
		if( is_array($new_settings['view_filename']) ) {
			$view_filename = $new_settings['view_filename'][$new_settings['view_type']];
		} else {
			$view_filename = $new_settings['view_filename'];
		}
		switch( $new_settings['view_type'] ) {
			case 'theme' :
				if ( in_array( $view_filename, get_page_templates() ) ) {
					$obj->settings['view_filename'] = $view_filename;
				} else {
					$obj->settings['view_filename'] = '';
				}
			break;
			default :
				if ( in_array( $view_filename, $obj->getViews() ) ) {
					$obj->settings['view_filename'] = $view_filename;
				} else {
					$obj->settings['view_filename'] = '';
				}
			break;
		}
		$obj->settings['view_type'] = $new_settings['view_type'];
		// Check if this was serialized
		if( wpew_Widgets::isSerialized( 'view_params', $new_settings ) ) {
			wpew_Widgets::unserialize( 'view_params', $new_settings );
			$view_params = $new_settings['view_params'];
		} else {
			parse_str( $new_settings['view_params'], $view_params );
		}
		$obj->settings['view_params'] = array_filter( $view_params );	
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * @see wpew_AWidget::$tabular
	 */
	public $tabular = true;
	/**
	 * @var string $dirViews directory name for the location of this class's view templates
	 */
	public $dirViews = 'views';
	
	/**
	 * Create new instance
	 */
	public function __construct( $name = '', $wOpts = array(), $cOpts = array() )
	{
		// Set Name
		if( empty( $name ) ) $name = __('View');
		// Set Options
		$wOpts = wp_parse_args( $wOpts, array(
			'description' => __( "Use the view template control system and pass custom parameters to display data in any desired format." )
		));
		// parent constructor
		parent::__construct( $name, $wOpts, $cOpts );
	}
		
	/**
	 * @see wpew_IWidget::beforeOutput()
	 */
	public function beforeOutput() {
		if( !empty( $this->settings['view_filename'] ) ) {
			switch( $this->settings['view_type'] ) {
				case 'theme' :
					$template = locate_template( array( $this->settings['view_filename'] ) );
				break;
				default :
					$template = xf_system_Path::join( $this->getViewsDir(), $this->settings['view_filename'] );
				break;
			}
			if( file_exists( $template ) && is_file( $template ) ) $this->settings['view_template'] = $template;
		}
	}
	
	/**
	 * @see wpew_IWidget::defaultOutput()
	 */
	final public function defaultOutput() {		
		// Include a view if there is one and send the view params in extracted, otherwise just do the default.
		if( isset( $this->settings['view_template'] ) ) {
			// Enable the global here, just so we don't have to think about it in the views.
			global $wpew;
			if( is_array( $this->settings['view_params'] ) ) extract( $this->settings['view_params'] );
			include( $this->settings['view_template'] );
		} else {
			$this->defaultView();
		}
	}
	
	// Additional wpew_widgets_View Methods
	
	/**
	 * @see wpew_widgets_IView::getViewsDir()
	 */
	public function getViewsDir() {
		// Do action here passing this widget as an argument, this allows for grabbing the correct widget just before the filter. 
		self::$manager->doLocalAction( 'onGetViewsDir', $this );
		$dir = xf_system_Path::join( self::$manager->plugin->settings['widgetsDir'], $this->id_base, $this->dirViews );
		if( !xf_system_Path::isAbs( $dir ) ) {
			$dir = ABSPATH . $dir;
		}
		// Apply the filter here after the action because callbacks could have grabbed the id_base from the widget to add the right filter.
		return apply_filters( xf_wp_APluggable::joinShortName('getViewsDir', $this->id_base), $dir );
	}
	
	/**
	 * @see wpew_widgets_IView::getViews()
	 */
	public function getViews() {
		// Do action here passing this widget as an argument, this allows for grabbing the correct widget just before the filter. 
		self::$manager->doLocalAction( 'onGetViews', $this );
		
		$views = array();
		$dir = $this->getViewsDir();
		if ( $dir && is_dir( $dir ) && is_readable( $dir ) ) {
			$dirRsc = @ opendir( $dir );
			while ( ($viewRsc = readdir($dirRsc)) !== false ) {
				$viewFile = $dir . xf_system_Path::DS . $viewRsc;
				if ( !is_file($viewFile) ) continue;
				
				$templateData = implode( '', file( $viewFile ) );
											
				$name = '';
				if ( preg_match( '|Template Name:(.*)$|mi', $templateData, $name ) ) {
					$name = _cleanup_header_comment( $name[1] );
				}
				if ( !empty( $name ) ) {
					$views[trim( strip_tags($name) )] = basename( $viewFile );
				}
			}
			@closedir($dirRsc);
		}
		// Apply the filter here after the action because callbacks could have grabbed the id_base from the widget to add the right filter.
		return apply_filters( xf_wp_APluggable::joinShortName('getViews', $this->id_base) , $views );
	}
	
	/**
	 * @see wpew_widgets_IView::viewsDropdown()
	 */
	public function viewsDropdown( $selected = '' ) {
		$views = $this->getViews();
		$names = array_keys( $views );
		$values = array_values( $views );
		array_unshift( $names, 'Default View' );
		array_unshift( $values, '' );
		$views = array_combine( $names, $values );
		xf_display_Renderables::buildSelectOptions( $views, $selected );
	}
	
	/**
	 * @see wpew_widgets_IView::defaultView()
	 */
	public function defaultView() {		
		// START DEFAULT
		if( count( $this->settings['view_params'] ) > 0 ) print_r( $this->settings['view_params'] );
		// END DEFAULT
	}
}
// END class
?>