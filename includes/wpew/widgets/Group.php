<?php
/**
 * This file defines wpew_widgets_Group, an Extensible Widget class.
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
 * but is still higher up in the inheritance tree. Use this widget to create a 
 * new widget group, as a widget? Yes... this is where it gets interesting.
 *
 * @package wpew
 * @subpackage widgets
 */

// START class
class wpew_widgets_Group extends wpew_widgets_View {
	
	// STATIC
	
	/**
	 * @var string $tabLabel optional common property among all wpew widgets
	 */
	public static $tabLabel = 'Group';
	
	/**
	 * @see wpew_AWidget::getDefaultSettings()
	 */
	public static function getDefaultSettings( &$obj ) {
		$lvl = self::getLevel( $obj );
		$defaults =  array(
			'group_name' => 'New Group (' . $lvl . '-' . $obj->number . ')',
			'presets' => '',
			'before_widget' => '<li id="%1$s" class="widget %2$s">',
			'after_widget' => "</li>",
			'before_title' => '<h2 class="widgettitle">',
			'after_title' => "</h2>",
			'view_params' => array(
				'before_group' => '<ul>',
				'after_group' => '</ul>'
			)/*,
			'widgetIDs' => array(), // Not included in the form
			'widgetOptions' => array() // Not included in the form*/
		);
		return $defaults;
	}
	
	/**
	 * @see wpew_IWidget::save()
	 */
	public static function save( &$obj, $new_settings ) {
		$obj->settings['group_name'] = $new_settings['group_name']; // no sanitizing on this variable
		$obj->settings['presets'] = $new_settings['presets']; // no sanitizing on this variable
		switch( $obj->settings['presets'] ) {
			case '*' :
				$newParams = array(
					'before_group' => $new_settings['before_group'], // no sanitizing on this variable
					'after_group' => $new_settings['after_group'] // no sanitizing on this variable
				);
				$obj->settings['before_widget'] = $new_settings['before_widget']; // no sanitizing on this variable
				$obj->settings['after_widget'] = $new_settings['after_widget']; // no sanitizing on this variable
				$obj->settings['before_title'] = $new_settings['before_title']; // no sanitizing on this variable
				$obj->settings['after_title'] = $new_settings['after_title']; // no sanitizing on this variable
			break;
			case 'blank' :
				$newParams = array(
					'before_group' => '',
					'after_group' => ''
				);
				$obj->settings['before_widget'] = '';
				$obj->settings['after_widget'] = '';
				$obj->settings['before_title'] = '';
				$obj->settings['after_title'] = '';
			break;
			case 'div' :
				$newParams = array(
					'before_group' => '<div>',
					'after_group' => '</div>'
				);
				$obj->settings['before_widget'] = '<div id="%1$s" class="widget %2$s">';
				$obj->settings['after_widget'] = "</div>\n";
				$obj->settings['before_title'] = '<div class="widgettitle">';
				$obj->settings['after_title'] = "</div>\n";
			break;
			case 'simple_div' :
				$newParams = array(
					'before_group' => '<div>',
					'after_group' => '</div>'
				);
				$obj->settings['before_widget'] = '<div class="widget %2$s">';
				$obj->settings['after_widget'] = "</div>\n";
				$obj->settings['before_title'] = '<div>';
				$obj->settings['after_title'] = "</div>\n";
			break;
			default :
				$defaults = self::getDefaultSettings( $obj );
				$newParams = $defaults['view_params'];
				$obj->settings['before_widget'] = $defaults['before_widget'];
				$obj->settings['after_widget'] = $defaults['after_widget'];
				$obj->settings['before_title'] = $defaults['before_title'];
				$obj->settings['after_title'] = $defaults['after_title'];
			break;
		}
		// Check if this was serialized
		if( wpew_Widgets::isSerialized( 'view_params', $new_settings ) ) {
			wpew_Widgets::unserialize( 'view_params', $new_settings );
			$old_params = $new_settings['view_params'];
		} else {
			$old_params = $obj->settings['view_params'];
		}
		// overwrite the query setting with the settings from the form
		if( isset( $newParams ) ) {
			$obj->settings['view_params'] = array_filter( wp_parse_args( $newParams, $old_params ) );
		}
		// These should be saved, but are not editable by the user
		//$obj->settings['widgetIDs'] = $new_settings['widgetIDs'];
		//$obj->settings['widgetOptions'] = $new_settings['widgetOptions'];
	}
	
	/**
	 * This method only works while in WordPress Admin.
	 * Retrieves the guid of a group by pulling the current session data.
	 * @param object &$obj Reference to a widget instance
	 * @return int The guid of the group passed into this method
	 */
	public static function getGuid( &$obj ) {
		if( !is_admin() || !isset(self::$manager->plugin->override) ) return '';
		if( self::$manager->plugin->override->sessionData ) {
			$guid = xf_system_Path::join( self::$manager->plugin->override->sessionData['guid'], $obj->id );
		} else {
			$guid = xf_system_Path::join( self::$manager->plugin->override->defaultGuid, $obj->id );
		}
		return $guid;
	}
	
	/**
	 * This method only works while in WordPress Admin.
	 * Retrieves the current level of a group by looking at the guid of the group.
	 * Global is 1, therefore the level of any groups added globally should be above this.
	 * If not in the admin this returns false
	 * @param object &$obj Reference to a widget instance
	 * @return int The level of the group passed into this method
	 */
	public static function getLevel( &$obj ) {
		$guid = self::getGuid( $obj );
		if( empty( $guid ) ) return 0;
		$parts = explode( xf_system_Path::DS, trim( $guid, xf_system_Path::DS ) );
		return (int) count( $parts ) + 1;
	}
	
	/**
	 * This method only works while in WordPress Admin.
	 * Retrieves the URI to edit a group's widgets by getting the guid of the admin and the group.
	 * @param object &$obj Reference to a widget instance
	 * @return int The URI to edit widgets of the group passed into this method
	 */
	public static function getEditURI( &$obj ) {
		$guid = self::getGuid( $obj ); 
		if( empty( $guid ) ) return '';
		return self::$manager->plugin->override->guidURI . '?g=' . urlencode( $guid );
	}
	
	/**
	 * WordPress Hook - When widget is unregistered on Registration controller
	 * Return false to prevent the widget from being unregistered.
	 *
	 * @return void|false
	 */
	public static function onUnregister( $widget ) {
		session_start();
		if( isset($_SESSION['group_data']) || self::$manager->backups ) {
			add_action('admin_notices', array($widget,'admin_notices'));
			return false;
		}
	}
	
	// INSTANCE MEMBERS
	
	public $parentCallback = null;
	
	// Callback members, for passing around on the front-end
	public $tmpID;
	public $preOptionFilters;
	public $childCallback;
	public $reregister;
	
	// CONSTRUCTOR
	public function __construct( $name = '', $wOpts = array(), $cOpts = array() )
	{
		// Set Name
		if( empty( $name ) ) $name = __('Widget Group');
		// Set Options
		$wOpts = wp_parse_args( $wOpts, array(
			'description' => __( "Use this widget to create a new widget group, as a widget? Yes... this is where it gets interesting." )
		) );
		$cOpts = wp_parse_args( $cOpts, array(
			'width' => 350
		) );
		// Add hook for unregistering
		add_action( xf_wp_APluggable::joinShortName( 'onUnregister', __CLASS__ ), array(__CLASS__, 'onUnregister') );
		// parent constructor
		parent::__construct( $name, $wOpts, $cOpts );
	}
	
	/**
	 * WordPress Hook - admin_notices
	 */
	public function admin_notices() { ?>
		<div class="error fade">
			<p>Failed unregistering widget - <strong><?php echo $this->name; ?></strong></p>
			<?php if( !isset($_SESSION['group_data']) ) : ?>
			<p>Currently there is a user editing a widget group. You cannot access this page until that user has completed, or you go to the <a href="widgets.php">Widgets Administration Page</a> to force edit.</p>
			<?php else : ?>
			<p>You are currently editing a widget group, you must go to the <a href="widgets.php">Widgets Administration Page</a> to save and exit to the global scope before unregistering this widget.</p>
			<?php endif; ?>
		</div>
	<?php }
	
	/**
	 * Just a shortcut to reset all the members that have to do with front-end rendering.
	 * These are reset at render start to not interfere with the current group instance.
	 * They are reset after render simply for memory cleanup.
	 */
	public function resetCallbackMembers( ) {
		$this->tmpID = null;
		$this->preOptionFilters = array();
		$this->childCallback = null;
		$this->reregister = array();
	}
	
	/**
	 * This is a filter used to filter all of the widget options contained by this group.
	 * This happens only while it is rendering, and only if a child is not rendering.
	 */
	public function preOptionFilter( $data ) {
		// Skip this filter if the child is busy
		if( $this->childCallback ) {
			if( !is_null( $this->childCallback->tmpID ) ) return $data;
		}
		// Continue with this filter returning the appropriate widgetOption
		if( !preg_match( '/^pre_option_(.+)$/', self::$manager->currentFilter , $matches ) ) return $data;
		$name = $matches[1];
		return $this->settings['widgetOptions'][$name];
	}
	
	/**
	 * This is a filter used to filter the specific option of sidebars_widgets.
	 * This happens only while it is rendering.
	 */
	public function sidebars_widgets( $data ) {
		return array( $this->tmpID => $this->settings['widgetIDs'] );
	}
	
	/**
	 * @see wpew_IWidget::beforeOutput()
	 */
	public function beforeOutput() {
	
		// call parent
		parent::beforeOutput();
		
		// Reset
		$this->resetCallbackMembers();
				
		// Register this group
		// Must be registered to not interfere with the defaultView
		$this->tmpID = self::$manager->registerGroup( array(
			'group_name' => $this->settings['group_name'],
			'before_widget' => $this->settings['before_widget'],
			'after_widget' => $this->settings['after_widget'],
			'before_title' => $this->settings['before_title'],
			'after_title' => $this->settings['after_title'],
		) );
		
		// Check if there is anything to output, if not no need to continue
		if( !is_array($this->settings['widgetIDs']) || !is_array($this->settings['widgetOptions']) ) return;
		
		// THERE ARE WIDGETS, SO WE DO THE FOLLOWING...
		
		// Set a temporary filter on the sidebars_widgets, will return this groups widgets only
		self::$manager->addFilter( 'sidebars_widgets', false, $this );	
		
		// Here we are safe to add filters attributed with an indexed array because it will run in order.
		for( $i=0 ; $i<count($this->settings['widgetIDs']) ; $i++) {
			$id = $this->settings['widgetIDs'][$i];
			// Parse the ID in
			if( !$parsed = wpew_Widgets::parseWidgetID( $id ) ) continue;
			extract( $parsed );
			// If a filter hasn't already been added for an option add it, and remove the widget cache
			// Only need to do this once for each option.
			if( !in_array( $option_name, $this->preOptionFilters, true ) ) {
				self::$manager->addFilter( 'pre_option_' . $option_name, 'preOptionFilter', $this );
				self::$manager->cache->delete( $option_name, 'widget' );
				// Add an option filter to the array
				$this->preOptionFilters[] = $option_name;
			}
			// Remove any widgets already registered, but save them in a list to register again after rendering
			if( $removed = self::$manager->removeWidget( $id ) ) $this->reregister[$id] = $removed;
			// If the widget is a widget group, we MUST create a new object to not overwrite this object in the registry
			if( $option_name == $this->option_name ) {
				if( is_null( $this->childCallback ) ) {
					$group = new self();
					$group->parentCallback =& $this;
					$group->_register();
					$this->childCallback = $group;
				}
			} else if( $removed ) {
				// For any widgets removed that are not widget group, we check if they are objects, if so register them
				if( isset( $removed['callback'][0] ) ) {
					$clone = clone $removed['callback'][0];
					$clone->_register();
				}
			} else {
				// For any widget not groups, and not removed, we must look for the original multiwidget, and register from that
				$multiID = $id_base . '-' . ($number-1);
				if( self::$manager->isWidget( $multiID ) ) {
					$orig =& self::$manager->getWidget( $multiID );
					if( isset( $orig[0] ) ) {
						$orig[0]->_register();
					}
				}
			}
		}
		$this->settings['view_params']['group_id'] = $this->tmpID;
		$this->settings['view_params']['group_name'] = $this->settings['group_name'];
	}
	
	/**
	 * @see wpew_widgets_IView::defaultView()
	 */
	public function defaultView() {
		// START DEFAULT
		if( !empty( $this->settings['view_params']['before_group'] ) ) echo $this->settings['view_params']['before_group'];
		if( !is_null( $this->tmpID ) ) self::$manager->renderGroup( $this->tmpID );
		if( !empty( $this->settings['view_params']['after_group'] ) ) echo $this->settings['view_params']['after_group'];
		// END DEFAULT
	}
	
	/**
	 * @see wpew_IWidget::afterOutput()
	 */
	public function afterOutput() {
		// Remove filter
		self::$manager->removeFilter( 'sidebars_widgets', false, $this );
		// Reset to back to current options
		foreach( $this->preOptionFilters as $option ) {
			self::$manager->removeFilter( 'pre_option_'.$option, 'preOptionFilter', $this );
		}
		// Reregister anything that was overwritten
		foreach( $this->reregister as $id => $widget ) {
			if( !is_null( $this->parentCallback ) ) {
				if( array_key_exists( $id, $this->parentCallback->reregister ) ) continue;
			}
			if( is_object( $widget['callback'][0]) ) {
				self::$manager->cache->delete( $widget['callback'][0]->option_name, 'widget' );
			}
			self::$manager->registeredWidgets[$id] = $widget;
		}
		// Reset
		$this->resetCallbackMembers();
	}
}
// END class
?>