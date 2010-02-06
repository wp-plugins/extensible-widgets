<?php

require_once(dirname(__FILE__).'/../xf/Object.php');
require_once('IWidget.php');

/**
 * This is an abstract class and is meant to be extended.
 *
 * This is an Abstract class. There are finished methods which call abstract methods.
 * This class is meant to be extended and within any child class the abstract methods should be defined (as well as a constructor).
 * @package wpew
 */
abstract class wpew_AWidget extends WP_Widget implements wpew_IWidget {
	
	// STATIC
	
	/**
	 * @var object $manager set by the setManager static method, this should not be set anywhere else
	 */
	public static $manager;
	/**
	 * @var array $baseWidgetOptions set at the same time the manager is so we can have access to the manager's properties in the value
	 */
	public static $baseWidgetOptions;
	/**
	 * @var array $baseControlOptions set at the same time the manager is so we can have access to the manager's properties in the value
	 */
	public static $baseControlOptions;
	/**
	 * @var string $tabLabel optional common static property among all wpew widget classes
	 */
	public static $tabLabel = 'Abstract';
	
	// INSTANCE
	
	/**
	 * @var array $parentClasses  any wpew widget instance's class hierarchy as an associative array, not including anything below from wpew_AWidget
	 */
	public $parentClasses;
	/**
	 * @var bool $tabular flag to set or override that tells this widget whether to use inheritance tabs within the admin form
	 */
	public $tabular = true;
	/**
	 * @var array $adminRenderFlags this should be set to false to not use render flags, render flags can be used to control the inheritance output of this widget
	 */
	public $adminRenderFlags = false;
	/**
	 * @var string $name this is the name of the widget, this should be overwritten
	 */
	public $name = __CLASS__;
	/**
	 * @var array $settings holds the settings for the current widget instance. This is not an object, it is an array of settings
	 */
	public $settings;
	/**
	 * @var array $args these are the arguments of the widget group the widget instance belongs too and passed when group was registered
	 */
	public $args;
		
	// CONSTRUCTOR
	public function __construct( $name = '', $wOpts = array(), $cOpts = array() )
	{
		// Set the static manager of all wpew widgets extending this class
		$this->setManager( $GLOBALS['wpew']->widgets );
		// See if we need to set a default name
		if( empty( $name ) ) $name = $this->name;
		// parent constructor WP_Widget
		// the id_base is false because it should use the sanitized version of the PHP classname by default
		// The name is appended to "wpew:" to differentiate these widgets from all the other installed widgets.
		// The arguments are merged with the base options of this class
		parent::__construct( false, $name, wp_parse_args( $wOpts, self::$baseWidgetOptions ), wp_parse_args( $cOpts, self::$baseControlOptions ) );
		
		// get all the parent classes, but stop the list at this class and don't include the stop class because it is abstract
		$this->parentClasses = xf_Object::getParentClasses( $this, true, __CLASS__, false );
	}
	
	/**
	 * @see wpew_IWidget::setManager()
	 */
	final public function setManager( &$mngr ) {
		if( !is_null(self::$manager) ) return;
		self::$manager = $mngr;
		// set defaults
		self::$baseWidgetOptions = array(
			'description' => "This is an abstract wpew Widget. If you managed to get this description to display you did something wrong."
		);
		self::$baseControlOptions = array(
			//'id_base' => self::$manager->shortName
			'width' => 200
		);
		// add actions to flush cache, method is located within the class's settings
		self::$manager->addAction( 'save_post', 'flushWidgetCache', $this );
		self::$manager->addAction( 'deleted_post', 'flushWidgetCache', $this );
		self::$manager->addAction( 'switch_theme', 'flushWidgetCache', $this );
	}
	
	/**
	 * @see wpew_IWidget::flushWidgetCache()
	 */
	final public function flushWidgetCache() {
		wp_cache_delete( $this->option_name, 'widget' );
	}
	
	/**
	 * @see wpew_IWidget::update()
	 */
	final public function update( $new_settings, $old_settings ) {
		$this->settings = &$old_settings;
		foreach( $this->parentClasses as $class ) {
			// call abstract
			call_user_func( array( $class, 'save' ), $this, $new_settings );
		}
		// flush the cache
		$this->flushWidgetCache();
		// wp_cache_get - global
		$alloptions = self::$manager->cache->get( 'alloptions', 'options' );
		if ( isset( $alloptions[ $this->option_name ] ) ) {
			delete_option( $this->option_name );
		}
		//return array_filter( $this->settings );
		return $this->settings;
	}
	
	/**
	 * @see wpew_IWidget::form()
	 */
	final public function form( &$settings ) {
		// Get registration
		$registration = self::$manager->registration[get_class($this)];
		if( is_array($registration) ) {
			$this->tabular = ($registration['display'] == 'tabular') ? true : false;
			$this->adminRenderFlags = $registration['renderFlags'];
		}
		
		// merge all the default settings from all the classes from highest to lowest class
		$defaults = array();
		foreach( $this->parentClasses as $class ) {
			$classDefaults = call_user_func( array( $class, 'getDefaultSettings' ), $this );
			if( !is_array( $classDefaults ) ) continue;
			$classSettings[$class] = array_keys($classDefaults);
			$defaults = wp_parse_args( $classDefaults, $defaults );
		}
		// set the instance settings member (very important to do this here)
		$this->settings = wp_parse_args( $settings, $defaults );
		
		// call abstract from decendant class only
		$this->beforeAdminOutput( $this );
				
		echo $this->class_name;
		
		// start tab array
		$tabControls = array();
		// start form output array
		$renderedForms = array();
		// render flags are set so we must start a counter
		if( is_array( $this->adminRenderFlags ) ) {
			$counter = count( $this->adminRenderFlags );
			$skipped = 0;
		}
		foreach( $this->parentClasses as $class ) {
			// there is a counter set, so we check to see whether we are rendering the form of a class
			if( isset( $counter ) ) {
				$counter--;
				if( isset( $this->adminRenderFlags[$counter] ) ) {
					if( (bool) $this->adminRenderFlags[$counter] == false ) {
						$skipped++;
						// Render all the skipped settings as hidden fields
						foreach( $classSettings[$class] as $name ) {
							if( isset($serialized[$name]) ) continue;
							if(  is_array($this->settings[$name]) ) {
								$inputName = $this->get_field_name(wpew_Widgets::SERIALIZED).'['.$name.']';
								$value = serialize( $this->settings[$name] );
								$serialized[$name] = true;;
							} else {
								$inputName = $this->get_field_name($name);
								$value = $this->settings[$name];
							}
							echo '<input type="hidden" id=name="'.$this->get_field_id($name).'" name="'.$inputName.'" value="'.esc_attr($value).'">';
						}
						if( $skipped == count( $this->adminRenderFlags ) ) {
							echo '<p>Sorry, no controls are available at this time.</p>';
						}
						continue;
					}
				}
			}
			// append more to tab string
			$classVars = get_class_vars( $class );
			$label = ( !empty( $classVars['tabLabel'] ) ) ? $classVars['tabLabel'] : $class;
			array_unshift($tabControls, '<a class="tabButton button" href="#" tabindex="'.$i.'" rel="tab[' . strtolower( $class ) . ']">'.$label.'</a>');
			// call abstract of each class
			ob_start();
			$cssclass = strtolower( $class );
			if($this->tabular) $cssclass .= ' tabs-panel';
			echo '<div class="' . $cssclass . '">';
			call_user_func( array( $class, 'renderAdmin' ), $this );
			do_action( $class . '_renderAdmin' );
			echo '</div>';
			// get and finish the form buffer and string
			array_unshift($renderedForms, ob_get_clean());
		}
		
		// this is the actual output
		if( $this->tabular && empty( $_GET['editwidget'] ) ) {
			// output the tabs and the forms		
			echo '<p id="'.$this->id.'_tabs" class="controlTabs hide-if-no-js">'.implode('',$tabControls).'</p>';
			echo '<div class="tabContent inside">'.implode('',$renderedForms).'</div>';
		} else {
			// no tabs, only output the forms
			echo implode('',$renderedForms);
		}
		
		// call abstract from decendant class only
		$this->afterAdminOutput( $this );
	}
	
	/**
	 * @see wpew_IWidget::widget()
	 */
	final public function widget( $args, &$settings ) {
		// set the instance settings member (very important to do this here)
		$this->settings =& $settings;
		// set the instance args member (very important to do this here)
		$this->args = &$args;
		$cache = self::$manager->cache->get( $this->option_name, 'widget' );
		if ( !is_array($cache) ) $cache = array();
		if ( isset($cache[$args['widget_id']]) ) return $cache[$args['widget_id']];
		
		// call abstract
		$this->beforeOutput();
		
		// START WIDGET OUTPUT
		ob_start();
		
		$this->render();
		
		$cache[$args['widget_id']] = ob_get_flush();
		// END WIDGET OUTPUT
		
		// call abstract
		$this->afterOutput();
		
		self::$manager->cache->add( $this->option_name, $cache, 'widget' );
	}
	
	/**
	 * render
	 * 
	 * Although this method is defined in the abstract class, it is left open to be overridden
	 * @see wpew_IWidget::render()
	 */
	public function render() {
		// call abstract
		$this->defaultOutput();
	}
}
// END abstract class
?>