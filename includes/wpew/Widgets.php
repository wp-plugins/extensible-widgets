<?php
/**
 * This file defines wpew_Widgets, a utility class to manage WordPress widgets.
 * 
 * PHP version 5
 * 
 * @package wpew
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @link http://jidd.jimisaacs.com
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * A utility that adds more functionality to the management of WordPress widgets.
 *
 * @package wpew
 * @since 1.0
 * @author Jim Isaacs <jim@jimisaacs.com>
 */
class wpew_Widgets extends xf_wp_AExtension {
	
	// CONSTANT
	
	const SERIALIZED = '__serialized__';
	
	// STATIC MEMBERS
	
	/**
	 * @see xf_wp_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return xf_patterns_ASingleton::getSingleton(__CLASS__);
	}
	
	/**
	 * Takes a string and parses it into widget information
	 * The returned associative array has keys of 'id_base','number', and 'option_name'
	 *
	 * @param string The widget ID to parse
	 * @return array|false An associative array on success or false
	 */
	public static function parseWidgetID( $id ) {
		if ( !preg_match( '/^([a-zA-Z0-9\-_]+)-([0-9]+)$/', $id, $matches ) ) return false;
		$parsed = array();
		$parsed['id_base'] = $matches[1];
		$parsed['number'] = $matches[2];
		$parsed['option_name'] = 'widget_'.$matches[1];
		return $parsed;
	}
	
	/**
	 * This traverses a multidimensional array of widget data
	 * It loops through the tree, with each pass using references to eachother.
	 * This is so that the return is actually the correct reference to the instance in the tree, not a copy of data.
	 *
	 * @param string The guid to use to traverse the tree
	 * @param array The tree that is actually the widget data to traverse
	 * @return array|false A reference to the instance within the tree or false if nothing was found
	 */
	public static function &getGuidScope( $guid, &$tree ) {
		// Split the guid to into array
		// We trim the first level because we don't need this since we will be using an increment loop
		$parts = explode( xf_system_Path::DS, ltrim( $guid, xf_system_Path::DS ) );
		// Set the branch passing by reference
		$branch =& $tree;
		// Loop through the guid array (represents tree levels)
		while( $id = array_shift( $parts ) ) {
			// parse the id
			if( !$parsed = self::parseWidgetID( $id ) ) return false;
			extract( $parsed );
			// Check if we are in the right place
			// and reset the branch to the correct location
			if( isset( $branch['widgetOptions'] ) ) {
				if( !isset( $branch['widgetOptions'][$option_name][$number] ) ) return false;
				// Set the branch passing by reference
				$branch =& $branch['widgetOptions'][$option_name][$number];
			} else {
				if( !isset( $branch[$option_name][$number] ) ) return false;
				// Set the branch passing by reference
				$branch =& $branch[$option_name][$number];
			}
		}
		// Return the branch which should actually be the direct reference to the item in the tree
		return $branch;
	}
	
	/**
	 * Checks if the setting name exists within the new settings as serialized data
	 *
	 * @param string $name The name of the setting to check
	 * @param array $new_settings The settings to check for the setting name
	 * @return bool
	 */
	public static function isSerialized( $name, &$new_settings ) {
		if( $name == self::SERIALIZED ) return false;
		if( empty($new_settings[self::SERIALIZED]) ) return false;
		return isset($new_settings[self::SERIALIZED][$name]);
	}
	
	/**
	 * Unserializes the setting name within the provided settings.
	 * The provided settings are passed by reference and therefore there is no return.
	 *
	 * @param string $name The name of the setting to unserialize
	 * @param array $new_settings The settings to modify
	 * @return void
	 */
	public static function unserialize( $name, &$new_settings ) {
		if( self::isSerialized( $name, $new_settings ) ) {
			$new_settings[$name] = unserialize( $new_settings[self::SERIALIZED][$name] );
			unset( $new_settings[self::SERIALIZED][$name] );
		}
	}
	
	// INSTANCE MEMBERS
	
	/**
	 * @var string $dirWidgets Where widgets are located within the pluginRoot
	 */
	public $dirWidgets;
	
	/**
	 * @ignore
	 * Used internally to keep track of what sidebars this class has registered separately from WordPress
	 */
	private $_groups = array();
	/**
	 * @ignore
	 * For memory saving
	 */
	private $_currentGroups = false;
	/**
	 * @ignore
	 * For memory saving
	 */
	private $_backups = false;
	
	/**
	 * @see xf_wp_IPluggable::init()
	 */
	public function init() {
		// Add Hooks
		$this->dirWidgets = xf_system_Path::join('wpew','widgets');
		$this->addAction( 'widgets_init' );
	}
	
	/**
	 * @see xf_wp_IPluggable::client()
	 */
	public function client() {
		// Here we check if there are options in backup
		if( !$this->backups ) return;
		// Add Hooks
		// Get the option replacements
		foreach( array_keys( $this->backups['widgetOptions']) as $name ) {
			$this->addFilter( 'pre_option_'.$name, 'widgetOptionsFilter' );
		}
		$this->addFilter( 'sidebars_widgets' );	
	}
	
	// PRE WIDGET REGISTRATION METHODS
	
	/**
	 * widgetIsRegistered
	 *
	 * Adds a little more functionality by checking if a widget is already registered in the factory.
	 *
	 * @param string $class The class to check for in Widget Factory.
	 * @return bool
	 */
	public function widgetIsRegistered( $class ) {
		return array_key_exists( $class, $this->factory->widgets );
	}
	
	/**
	 * Wrapper around the WordPress Widget Factory method.
	 * Tries to set the static manager of the class, and registers class with the WordPress Widget Factory.
	 *
	 * @param The class that will be registered by the factory.
	 * @return bool May also display errors
	 */
	public function registerWidget( $class ) {
		if( $this->widgetIsRegistered( $class ) ) return $this->error( 'Failed to register widget "<strong>' . $class . '</strong>", already registered.' );
		$this->factory->register( $class );
		return $class;
	}
	
	/**
	 * Wrapper around the WordPress Widget Factory method.
	 * Registers class with WordPress Widget Factory
	 *
	 * @param string $class The class that will be unregistered by the factory.
	 * @return bool May also display errors
	 */
	public function unregisterWidget( $class ) {
		if( $this->widgetIsRegistered( $class ) ) return $this->error( 'Failed to unregister widget "<strong>' . $class . '</strong>", not registered.' );
		$this->factory->unregister( $class );
		return true;
	}
	
	/**
	 * Imports the widget class and can also auto-register it.
	 *
	 * @param string $class The class name/path of the widget being imported.
	 * @param bool $autoRegister Should this widget register itself automatically when imported
	 * @return bool false if already registered true if it registered now
	 */
	public function importWidget( $class, $autoRegister = true ) {
		if( !class_exists( $class, false ) ) {
			$file = xf_system_Path::join( $this->plugin->includeRoot, $this->dirWidgets, xf_wp_APluggable::unJoinShortName( $class, 'wpew_widgets') ).'.php';
			require_once( $file );
		}
		if( $autoRegister ) return $this->registerWidget( $class );
		return false;
	}
	
	/**
	 * Adds a little more functionality by checking if a group is already registered.
	 *
	 * @param string $id The id of the group to check for.
	 * @return bool
	 */
	public function groupIsRegistered( $id ) {
		if( !is_array( $this->registeredGroups ) ) return false;
		return (bool) array_key_exists( $id, $this->registeredGroups );
	}
	
	/**
	 * Wrapper around WordPress funcion.
	 * The term sidebar is confusing and constrictive in terms of thought and using widgets creatively.
	 * For undefined IDs we use the term "widget-group" instead of the term "sidebar".
	 *
	 * @param array $args Registers group with these registration arguments
	 * @return string The id of the group that was registered
	 */
	public function registerGroup( $args = array() ) {
		// increment based on internal memory
		$defaults = array( 'name' => __('Widget Group') );
		$defaults['id'] = sanitize_title_with_dashes( $defaults['name'] );
		$args = wp_parse_args( $args, $defaults );
		// increment the id if needed
		$idBase = $args['id'];
		$nameBase = $args['name'];
		$i = 1;
		if( $idBase == 'widget-group' ) {
			$args['id'] = $idBase . '-' . $i;
			$args['name'] = $defaults['name'] . ' ' . $i;
		}
		while( $this->groupIsRegistered( $args['id'] ) ) {
			$args['id'] = $idBase . '-' . $i;
			$args['name'] = $nameBase . ' ' . $i;
			$i++;
		}
		// set the internal memory
		$this->_groups[ $args['id'] ] = $args;
		return register_sidebar( $args );
	}
	
	/**
	 * Wrapper around the WordPress unregister_sidebar() function.
	 * The term sidebar is confusing and constrictive in terms of thought and using widgets creatively.
	 * For indexes we use the term "widget-group" for prepending ID strings first before resorting the term "sidebar".
	 *
	 * @param int|string $group Optional, default is 1. Index or ID of widget group.
	 * @return bool
	 */
	public function unregisterGroup( $group = 1 ) {
		if ( is_int($group) ) {
			$groupID = 'widget-group-'.$group;
			if( $this->groupIsRegistered( $groupID ) ) {
				$this->_groups[ $group ] = null;
				unset( $this->_groups[ $group ] );
				unregister_sidebar( $group );
				return true;
			}
			$group = 'sidebar-'.$group;
		}
		if( !$this->groupIsRegistered( $group ) ) return false;
		unregister_sidebar( $group );
		return true;
	}
	
	// POST WIDGET REGISTRATION METHODS
	
	/**
	 * Adds a little more functionality by checking if a widget is already instantiated in the widgets array.
	 *
	 * @param string $id The id of the widget to check for in the widgets array.
	 * @return bool
	 */
	public function isWidget( $id ) {
		return (bool) array_key_exists( $id, $this->registeredWidgets );
	}
	
	/**
	 * Get the actually widget data or object instance itself.
	 * The widget data is an array that is an item within another array.
	 * The actual object instance is within the data array within callback item array.
	 * Lots of arrays here, which is why this method is great!
	 *
	 * @param string $id The id of the widget to return.
	 * @param bool $callback Whether to return callback, if not it returns the widget itself
	 * @return mixed Either the false, the widget, or the widget callback
	 */
	public function &getWidget( $id, $callback = true ) {
		if( $this->isWidget( $id ) ) {
			$widget =& $this->registeredWidgets[$id];
			if( $callback ) return $widget['callback'];
			return $widget;
		}
		return false;
	}
	
	/**
	 * This is a shortcut to remove a registered widget to the registered array.
	 * It will register again on the next request, this is just a temporary removal.
	 *
	 * @param string $id The id of the widget to remove.
	 * @return bool
	 */
	public function removeWidget( $id ) {
		if( $this->isWidget( $id ) ) {
			$widget = $this->registeredWidgets[$id];
			unset( $this->registeredWidgets[$id] );
			return $widget;
		}
		return false;
	}

	/**
	 * Wrapper around the WordPress dynamic_sidebar() funcion.
	 * The term sidebar is confusing and constrictive in terms of thought and using widgets creatively.
	 * For indexes we use the term "widget-group" for prepending ID strings first before resorting the default function.
	 * The function name "dynamic_sidebar()" is especially confusing, it has nothing to do with what it actually does.
	 *
	 * @param int|string $group Optional, default is 1. Index or ID of widget group.
	 * @return bool True if widget group was found and called or false
	 */
	public function renderGroup( $group = 1 ) {
		if ( is_int($group) ) {
			$groupID = 'widget-group-'.$group;
			if( $this->groupIsRegistered( $groupID ) ) return dynamic_sidebar( $groupID );
		}
		return dynamic_sidebar( $group );
	}
	
	/**
	 * Action Hook - widgets_init
	 *
	 * Called as WordPress Action, hooks into when widgets initialize.
	 *
	 * @return void
	 */
	public function widgets_init() {
		// import widgets that are within the registration
		if( $this->registration ) {
			foreach( $this->registration as $class => $regSettings ) {
				$this->importWidget( $class );
			}
		}
		// Add a global widget group if there are none already registered
		/*if( !$this->registeredGroups ) {
			$this->registerGroup( array(
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget' => '</div>',
				'before_title' => '<h2>',
				'after_title' => '</h2>',
			));
		}*/
	}
	
	// OPTION MANIPULATION
	
	/**
	 * Filter Hook - multiple widget options (pre_option)
	 *
	 * @param mixed $data The data to filter
	 */
	public function widgetOptionsFilter( $data ) {
		if( !preg_match( '/^pre_option_(.+)$/', $this->currentFilter , $matches ) ) return $data;
		$name = $matches[1];
		return $this->backups['widgetOptions'][$name];
	}
	
	/**
	 * Filter Hook - sidebars_widgets
	 *
	 * @param mixed $data The data to filter
	 */
	public function sidebars_widgets( $data ) {
		return $this->backups['sidebars_widgets'];
	}
	
	// RESERVED PROPERTIES
	
	/**
	 * @property array $currentGroups array of option data
	 */
	public function &get__currentGroups() {
		if( !empty( $this->_currentGroups ) ) return $this->_currentGroups;
		$this->_currentGroups = get_option( 'sidebars_widgets' );
		unset( $this->_currentGroups['array_version'] );
		return $this->_currentGroups;
	}
	public function set__currentGroups( $v ) {
		$v['array_version'] = 3;
		update_option( 'sidebars_widgets', $v );
		$this->_currentGroups = $v;
	}
	
	/**
	 * @property array $backups Option holding all the backed up widget options while within another scope.
	 */
	public function &get__backups() {
		if( !empty( $this->_backups ) ) return $this->_backups;
		if( !$v = get_option( $this->getOptionName('widget_option_backups') ) ) return false;
		return $this->_backups = $v;
	}
	public function set__backups( $v ) {
		update_option( $this->getOptionName('widget_option_backups'), $v );
		$this->_backups = $v;
	}
	
	/**
	 * @property array $registration Option holding all the registration widget classes of this plugin.
	 */
	public function &get__registration() {
		$v = get_option( $this->getOptionName('registration') );
		if( empty($v) ) return false;
		return $v;
	}
	public function set__registration( $v ) {
		update_option( $this->getOptionName('registration'), $v );
	}
	
	/**
	 * @property-read WP_Widget_Factory $factory The WordPress widget factory object
	 */
	public function &get__factory() {
		return $GLOBALS['wp_widget_factory'];
	}
	
	/**
	 * @property array $registeredGroups array of the currently registered groups
	 */
	public function &get__registeredGroups() {
		return $GLOBALS['wp_registered_sidebars'];
	}
	public function set__registeredGroups( $v ) {
		$GLOBALS['wp_registered_sidebars'] = $v;
	}
	
	/**
	 * @property array $registeredUpdates array of the currently registered updates
	 */
	public function &get__registeredUpdates() {
		return $GLOBALS['wp_registered_widget_updates'];
	}
	public function set__registeredUpdates( $v ) {
		$GLOBALS['wp_registered_widget_updates'] = $v;
	}
	
	/**
	 * @property array $registeredControls array of the currently registered controls
	 */
	public function &get__registeredControls() {
		return $GLOBALS['wp_registered_widget_controls'];
	}
	public function set__registeredControls( $v ) {
		$GLOBALS['wp_registered_widget_controls'] = $v;
	}
	
	/**
	 * @property array $registeredWidgets array of the currently registered widgets
	 */
	public function &get__registeredWidgets() {
		return $GLOBALS['wp_registered_widgets'];
	}
	public function set__registeredWidgets( $v ) {
		$GLOBALS['wp_registered_widgets'] = $v;
	}
}
?>