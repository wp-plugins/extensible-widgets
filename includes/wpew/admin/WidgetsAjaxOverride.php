<?php
/**
 * This file defines wpew_admin_WidgetsAjaxOverride, a controller class a plugin admin page.
 * 
 * PHP version 5
 * 
 * @package wpew
 * @subpackage admin
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @link http://jidd.jimisaacs.com
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * wpew_admin_WidgetsAjaxOverride
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_WidgetsAjaxOverride extends xf_wp_AExtension {
	
	// STATIC MEMBERS
	
	/**
	 * @see xf_wp_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return xf_patterns_ASingleton::getSingleton(__CLASS__);
	}
	
	// INSTANCE MEMBERS
	
	public $autoSubmit = false;
		
	// Set here or only once later
	public $defaultGuid = xf_system_Path::DS;
	public $guidURI;
	
	// May be set only at certain times, at other they remain false
	protected $_inactiveOpts = false; // this an array with option names as keys, and values of the inactive instance data
	protected $_currentGuid = false; // this is only is the GET var 'g' has been set on the request
	protected $_sessionData = false; // this is of course only if there is a session
	protected $_tmpSession = false; // this is if a session is currently trying to be set
	
	/**
	 * We are overriding the abstract constructor because we must do stuff before the action hooks.
	 */
	public function init() {
		// Do everything through this hook to make sure this object has been set as an extension
		$this->addLocalAction( 'onSetReference' );
	}
	
	/**
	 * Action Hook - wpew_onInitiated
	 *
	 * @return void
	 */
	public function onSetReference() {
		// Force links using this guid to point to this page regardless of what override is active.
		$this->guidURI = 'widgets.php';
		// Initiate the session
		$this->initSession();
		// Add Hooks
		if( !isset(self::$get['editwidget']) ) {
			$this->addAction( 'wpew_onAjax_savewidget' );
			$this->addAction( 'widget_form_callback', '', $this, 10, 2 ); 
		}
	}
	
	
	public function wpew_onAjax_savewidget() {
		if( self::$post['add_new'] == 'multi' && !empty(self::$post['multi_number']) ) {
			$this->addAction( 'sidebar_admin_setup', 'ajax_sidebar_admin_setup' );
		}
	}
	
	public function ajax_sidebar_admin_setup() {
		$widget_id = self::$post['widget-id'];
		if( !$parsed = wpew_Widgets::parseWidgetID($widget_id) ) return;
		extract( $parsed );
		foreach( $this->plugin->widgets->factory->widgets as $obj ) {
			if( $obj->option_name == $option_name ) {
				$class = get_class($obj);
				$ref =& $this->plugin->widgets->factory->widgets[$class];
				$number = self::$post['multi_number'];
				$defaults = array();
				$this->autoSubmit = true;
				$ref->form_callback($number);
				break;
			}
		}
	}
	
	public function widget_form_callback( $instance, $widget ) {
		if( is_numeric($widget->number) ) {
			if( $widget->updated ) {
				echo '<div class="updated fade fadeOut"><strong>'.$widget->name.' '.$widget->number.'</strong> Saved.</div>';
			} else if( isset(self::$post['add_new']) ) {
				echo '<div class="updated"><strong>'.$widget->name.' '.$widget->number.'</strong> Added.</div>';
			}
			if( $this->autoSubmit ) echo '<p class="description">Saving widget...</p><div class="hidden">';
			$widget->form( $instance );
			if( $this->autoSubmit ) echo '</div>';
			echo '<div id="'.$widget->id.'_preselector"></div>';
			echo '<script type="text/javascript"> ';
			echo "var widget = jQuery('#".$widget->id."_preselector').parents().filter('.widget');";
			if( preg_match('/wpew_/', $widget->id ) ) {
				echo " initControlTabs( widget );";
			}
			echo " initFadeOut( widget );";
			//if( $this->autoSubmit ) echo ' widget.find(".widget-control-save").click();';
			if( $this->autoSubmit ) echo " wpWidgets.save(widget,0,1,0);";
			//if( $this->autoSubmit ) echo " widget.find('input.widget-control-save').trigger('click');";
			echo ' </script>';
		} else {
			// Output anything here to show up in the widget before it is added to a group
			// Available widgets have a number of '__i__', this isn't numeric, therefor it will not output the form.
			echo '<p class="description">Adding widget...</p>';
		}
		return false;
	}
	
	// SESSION MANIPULATION
	
	/**
	 * initSession
	 *
	 * This method initializes the PHP session, and checks based on GET variables.
	 * Based on the GET variables this session calls other methods to create a new session, or kill on already in progress.
	 *
	 * @return void
	 */
	public function initSession() {
		// start the session
		session_start();
		// First check if we need to start or clear the session
		if( $this->currentGuid ) {	
			if( $this->currentGuid != $this->defaultGuid ) {
				$force = (bool) isset(self::$get['force']);
				$this->_tmpSession = $this->newSession( $force );
			} else if( isset($_SESSION['group_data']) || isset( self::$get['force'] ) ) {
				$this->killSession();
			}
		}
	}
	
	/**
	 * newSession
	 *
	 * This method is called new session data needed.
	 * It is where we check and set all the session data within the session.
	 * Whether it is the first session, or current session is changing, this should swap out data for new.
	 * If nothing needs to change, then it just returns before anything changes.
	 *
	 * @return array|false
	 */
	public function newSession() {
		// Check if there is not already a session
		$tmp = array();
		$sameScope = false;
		if( $this->sessionData ) {
			// No need to continue is the session is unchanged.
			$sameScope = ( $this->sessionData['guid'] == $this->currentGuid );
			if( $sameScope && !isset(self::$get['force']) ) return false;
		}
		// We are changing or starting a new session!!!
		// Setup the preliminary session data.
		// This is data that is contained within the guid itself by parsing and converting it.
		$tmp['id'] = basename( $this->currentGuid );
		if( isset($this->plugin->widgets->currentGroups['wp_inactive_widgets']) ) {
			if( in_array( $tmp['id'], $this->plugin->widgets->currentGroups['wp_inactive_widgets'] ) ) return false;
		}
		
		// Parse the ID into it's respective parts, if it fails something is wrong with the ID
		if ( !$parsed = wpew_Widgets::parseWidgetID( $tmp['id'] ) ) return false;
		$tmp = array_merge( $tmp, $parsed );
		
		// Set the guid of the new session data
		if( !$tmp['guid'] = $this->currentGuid ) return false;
		
		// Are we saving the current scope, moving from global to local, or moving from local to local?
		if( $sameScope ) {
			// Save current scope
			$this->addAction( 'widgets_init', 'saveLocal' );
		} else if( $this->plugin->widgets->backups ) {
			// Move from local to local, this can be from any tree level to any tree lavel
			$this->addAction( 'widgets_init', 'localToLocal' );
		} else {
			// Move from global to local
			$this->addAction( 'widgets_init', 'globalToLocal' );
		}
		// return the temporary data
		return $tmp;
	}
	
	/**
	 * killSession
	 *
	 * This trys to take the local scope back to global.
	 * If it can't then the session stays active.
	 * If someone forces a session kill with the GET variable 'force', this also does a redirect.
	 *
	 * @return bool
	 */
	public function killSession() {
		if( isset(self::$get['force']) ) {
			if( !$this->restoreGlobal() ) return false;
		} else {
			if( !$this->localToGlobal() ) return false;
		}
		$this->_sessionData = false;
		unset( $_SESSION['group_data'] );
		// This was a forced kill, so we redirect in order to prevent another one by a refresh or something
		if( isset( self::$get['force'] ) ) wp_redirect( $this->plugin->admin->adminPage );
		return true;
	}
	
	// OPTION MANIPULATION
	
	/**
	 * getInactive
	 *
	 * @return void
	 */
	public function getInactive() {
		if( is_array( $this->_inactiveOpts ) ) return $this->_inactiveOpts;
		$curr = $this->plugin->widgets->currentGroups;
		$opts = array();
		if( !is_array( $curr['wp_inactive_widgets'] ) ) return $opts;
		foreach( $curr['wp_inactive_widgets'] as $id ) {
			if( !$parsed = wpew_Widgets::parseWidgetID( $id ) ) continue;
			extract( $parsed );
			if( !$option = get_option( $option_name ) ) continue;
			if( !isset( $opts[$option_name] ) ) $opts[$option_name] = array();
			$opts[$option_name][$number] = $option[$number];
		}
		return $this->_inactiveOpts = $opts;
	}
	
	/**
	 * setNewLocalScope
	 *
	 * Takes the scope that should be entered and resets all needed the options to do so.
	 * Options are of course always global, but to be within a local scope just means the options are changed temporarily.
	 *
	 * @return bool
	 */
	public function setNewLocalScope() {
		// get inactive, again
		$inactive = $this->getInactive();
		// Get the current groups
		$curr = $this->plugin->widgets->currentGroups;
		// Create a new array that we will modify with possible inactive widget options
		$groups = array( 'wp_inactive_widgets' => array() );
		$groups[$this->_tmpSession['id']] = $this->_tmpSession['instance']['widgetIDs'];
		// Reset all widget the options
		$options = array_keys(  $this->plugin->widgets->backups['widgetOptions'] );
		foreach( $options as $name ) {
			$new = array();
			$id_base = preg_replace('/^widget_/', '', $name);
			if( isset( $this->_tmpSession['instance']['widgetOptions'][$name] ) ) {
				$new = $this->_tmpSession['instance']['widgetOptions'][$name];
				// We must check if there are options of the same name in the inactive so this groups widgets don't conflict
				if( array_key_exists( $name, $inactive ) ) {
					$newNumber = max( array_keys( $new ) );
					foreach( $inactive[$name] as $i ) {
						$newNumber++;
						array_push( $groups['wp_inactive_widgets'], $id_base.'-'.$newNumber );
						$new[$newNumber] = $i;
					}
				}
			} else if( array_key_exists( $name, $inactive ) ) {
				// There is no option of the specified name in this group but there are inactives
				$newNumber = 1;
				foreach( $inactive[$name] as $i ) {
					$newNumber++;
					array_push( $groups['wp_inactive_widgets'], $id_base.'-'.$newNumber );
					$new[$newNumber] = $i;
				}
			}
			$new['_multiwidget'] = 1;
			// This key is the new style of widget data options, all widgets should be multiwidgets we're at WP 2.8!
			update_option( $name, $new );
		}
		// Reset the group option
		$this->plugin->widgets->currentGroups = $groups;
		// Set the session data
		$this->sessionData = $this->_tmpSession;
		$this->_tmpSession = false;
		return true;
	}
	
	/**
	 * saveGlobal
	 *
	 * This is ran only when moving from global to a local scope.
	 * It should not run otherwise, as the saved backup data is overwritten with the data in current global scope.
	 * So although the scope if technically always virtually global, this should only run when moving from the "real" global scope.
	 *
	 * @return array|false
	 */
	public function saveGlobal() {
		if( $this->plugin->widgets->backups ) return false;
		// get inactive, again
		$inactive = $this->getInactive();
		// Set the names of the options we are backing up
		$curr = $this->plugin->widgets->currentGroups;
		$curr['wp_inactive_widgets'] = array();
		$backups = array( 'sidebars_widgets' => $curr );
		// Each widget registered in the factory has an option, back all of them up
		// Of course this only works for widgets written in with the 2.8 API
		// Sorry... UPDATE YOUR WIDGETS!
		$backups['widgetOptions'] = array();
		foreach( $this->plugin->widgets->factory->widgets as $obj ) {
			$n = $obj->option_name;
			if( !$backup = get_option($n) ) continue;
			if( array_key_exists($n, $inactive) ) {
				foreach( array_keys($inactive[$n]) as $k ) {
					if( isset($backup[$k]) && is_numeric($k) ) unset($backup[$k]);
				}
			}
			$backups['widgetOptions'][$n] = $backup;
		}
		// Set the new backup data
		return $this->plugin->widgets->backups = $backups;
	}
	
	/**
	 * saveLocal
	 *
	 * This method should ensure all the options that were backed up are reset.
	 * Otherwise we'll have duplicate settings in this new scope.
	 * These new values really represent the local scope being entered.
	 *
	 * @return array|false
	 */
	public function saveLocal() {
		// get inactive, again
		$inactive = $this->getInactive();	
		// Create arrays
		$curr = $this->plugin->widgets->currentGroups;
		$widgetIDs = ( is_array( $curr[$this->sessionData['id']] ) ) ? $curr[$this->sessionData['id']] : array();
		$widgetOptions = array();
		// Loop
		foreach( $widgetIDs as $id ) {
			// If it doesn't parse successfully something is wrong.
			if ( !$parsed = wpew_Widgets::parseWidgetID( $id ) ) continue;
			extract( $parsed );
			// only need to set the key once, continue if it is the same option
			if( isset( $widgetOptions[$option_name] ) ) continue;
			// there is no option so continue here too
			if( !$new = get_option( $option_name ) ) continue;
			// Check if there are any option conflicts with the inactive options
			if( array_key_exists( $option_name, $inactive ) ) {
				foreach( array_keys( $inactive[$option_name] ) as $k ) {
					if( is_numeric($k) ) unset($new[$k]);
				}
			}
			$widgetOptions[$option_name] = $new;
		}
		// traverse the backup and set new data
		$tree = $this->plugin->widgets->backups;
		if( !$branch =& wpew_Widgets::getGuidScope( $this->sessionData['guid'], $tree ) ) return false;
		$branch['widgetIDs'] = $widgetIDs;
		$branch['widgetOptions'] = $widgetOptions;
		// Set the new backup data
		return $this->plugin->widgets->backups = $tree;
	}
	
	// ACTIONS - FILTERS
	
	/**
	 * globalToLocal
	 *
	 * Called as WordPress Action, hooks into when widgets initialize
	 * Tries to move from global to local scope.
	 *
	 * @return bool
	 */
	public function globalToLocal() {
		// Save data within the backups
		if( !$backups = $this->saveGlobal() ) return false;
		// If we made it this far that means that we now must try to get the appropriate instance data for this session.
		// If that fails we do not continue, becuase someone is doing something bad.
		// Now we try to pull the instance data, this may fail, if it does, fault the overall session.
		if( !$this->_tmpSession['instance'] = wpew_Widgets::getGuidScope( $this->_tmpSession['guid'], $backups ) ) return false;
		// Set options to the correct scope
		return $this->setNewLocalScope();
	}
	
	/**
	 * localToLocal
	 *
	 * Called as WordPress Action, hooks into when widgets initialize
	 * Tries to move to new local scope.
	 *
	 * @return bool
	 */
	public function localToLocal() {
		// Save data within the backups
		if( !$backups = $this->saveLocal() ) return false;
		// If we made it this far that means that we now must try to get the appropriate instance data for this session.
		// If that fails we do not continue, becuase someone is doing something bad.
		// Now we try to pull the instance data, this may fail, if it does, fault the overall session.
		if( !$this->_tmpSession['instance'] = wpew_Widgets::getGuidScope( $this->_tmpSession['guid'], $backups ) ) return false;
		// Set options to the correct scope
		return $this->setNewLocalScope();
	}
	
	/**
	 * localToGlobal
	 *
	 * Called from killSession()
	 * Tries to move to local from global scope.
	 *
	 * @return bool
	 */
	public function localToGlobal() {
		// Do this before saving
		$inactive = $this->getInactive();
		// Save data within the backups
		if( !$this->saveLocal() ) return false;
		// Restore everything
		// Continue only if there are backups
		if( !$backups = $this->plugin->widgets->backups ) return false;
		$groups = $backups['sidebars_widgets'];
		// Set the new options
		foreach( $backups['widgetOptions'] as $n => $new ) {
			if( array_key_exists( $n, $inactive ) ) {
				unset($new['_multiwidget']);
				$id_base = preg_replace('/^widget_/', '', $n);
				$newNumber = ( count($new) > 0 ) ? max( array_keys( $new ) ) : 1;
				foreach( $inactive[$n] as $i ) {
					$newNumber++;
					array_push( $groups['wp_inactive_widgets'], $id_base.'-'.$newNumber );
					$new[$newNumber] = $i;
				}
				$new['_multiwidget'] = 1;
			}
			update_option( $n, $new );
		}
		// set the current groups in the database
		$this->plugin->widgets->currentGroups = $groups;
		// clear backups from database
		$this->plugin->widgets->backups = false;
		return true;
	}
	
	/**
	 * restoreGlobal
	 *
	 * Called from killSession()
	 * Tries restore the global scope overriding any local scope that may be active.
	 *
	 * @return bool
	 */
	public function restoreGlobal() {
		// Restore everything
		// Continue only if there are backups
		if( !$backups = $this->plugin->widgets->backups ) return false;
		foreach( $backups['widgetOptions'] as $n => $v ) {
			update_option( $n, $v );
		}
		// set the current groups in the database
		$this->plugin->widgets->currentGroups = $backups['sidebars_widgets'];
		// clear backups from database
		$this->plugin->widgets->backups = false;
		return true;
	}
	
	// RESERVED PROPERTIES
	
	/**
	 * @ignore
	 * read-only string|false $currentGuid
	 */
	public function &get__currentGuid() {
		if( !empty( $this->_currentGuid ) ) return $this->_currentGuid;
		if( !isset( self::$get['g'] ) ) return false;
		return $this->_currentGuid = urldecode( self::$get['g'] );
	}
	
	/**
	 * @ignore
	 * read-only bool $inSession
	 */
	public function &get__inSession() {
		return ( isset($_SESSION['group_data']) || $this->_tmpSession );
	}
	
	/**
	 * @ignore
	 * read-write array|false $sessionData
	 */
	public function &get__sessionData() {
		if( !empty( $this->_sessionData ) ) return $this->_sessionData;
		if( !isset( $_SESSION['group_data'] ) ) return false;
		return $this->_sessionData = unserialize( $_SESSION['group_data'] );
	}
	/**
	 * @ignore
	 */
	public function set__sessionData( $v ) {
		$this->_sessionData = $v;
		$_SESSION['group_data'] = serialize( $v );
	}
	
	/**
	 * @ignore
	 * read-only string $currentURI
	 */
	public function get__currentURI() {
		return $this->guidURI . '?g=' . urlencode( $this->sessionData['guid'] );
	}
	
	/**
	 * @ignore
	 * read-only string $defaultURI
	 */
	public function get__defaultURI() {
		return $this->guidURI . '?g=' . urlencode( $this->defaultGuid );
	}
	
	/**
	 * @ignore
	 * read-only string $parentURI
	 */
	public function &get__parentURI() {
		if( !$this->sessionData ) return $this->defaultURI;
		return $this->guidURI . '?g=' . urlencode( dirname( $this->sessionData['guid'] ) );
	}
}
?>