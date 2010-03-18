<?php
/**
 * This file defines wpew_admin_WidgetsOverride, a controller class a plugin admin page.
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
 * This class should be instantiated upon enter the WordPress builtin widgets.php page.
 * This extends the functionality of the ajax page because we don't need to redefine anything other than to render things on this page.
 * Everything else is taken care of by the ajax override because when a widget is saved with ajax, it doesn't call the widget.php page.
 * It goes straight to the ajax page which is why all of the necessary scope functionality exists there.
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_WidgetsOverride extends wpew_admin_WidgetsAjaxOverride {

	// STATIC MEMBERS
	
	/**
	 * @see xf_wp_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return xf_patterns_ASingleton::getSingleton(__CLASS__);
	}
	
	/**
	 * @var string $_alertStr For debugging purposes, this shows up as an alert below the title of this page
	 */
	private $_alertStr = '';
	private $_activeWidgets = array();
	
	/**
	 * We are overriding the abstract constructor because we must do stuff before the action hooks.
	 */
	/*public function init() {
		// Call parent
		parent::init();
		// Queue up the widgets JavaScript for this admin page
		$this->queueScript( 'wpew_widgets_admin', array('jquery'), array(
			'path' => dirname( $this->pluginRootURI ) . '/js',
			'filename' => 'admin_widgets.js',
			'version' => '1.0'
		));
	}*/
	
	// SESSION MANIPULATION
	
	/**
	 * @see admin_ajax_Override::initSession()
	 */
	public function initSession() {
		// Call parent
		parent::initSession();
		// Check session and backups
		if( $this->inSession ) {
			$this->addAction( 'sidebar_admin_setup' );
		} else if( $this->plugin->widgets->backups ) {
			// We are filtering this because it's the only way to keep things intact
			// This filter is applied by WordPress when it retrieves this option
			$this->addFilter( 'sidebars_widgets' );
		}
	}
	
	/**
	 * @see admin_ajax_Override::newSession()
	 */
	public function newSession() {
		// Call parent
		if( $data = parent::newSession() ) {
			$this->addAction('admin_notices');
		}
		return $data;
	}
	
	/**
	 * @see admin_ajax_Override::killSession()
	 */
	public function killSession() {
		// Call parent
		if( $data = parent::killSession() ) {
			$this->addAction('admin_notices');
		}
		return $data;
	}
	
	/**
	 * @see admin_ajax_Override::saveGlobal()
	 */
	public function saveGlobal() {
		$this->_alertStr .= '<p>Trying to save global scope...</p>';
		// Call parent
		$saved = parent::saveGlobal();
		if( $saved ) $this->_alertStr .= '<p>Scope saved successfully.</p>';
		return $saved;
	}
	
	/**
	 * @see admin_ajax_Override::saveLocal()
	 */
	public function saveLocal() {	
		$this->_alertStr .= '<p>Trying to save local scope...</p>';
		// Call parent
		$saved = parent::saveLocal();
		if( $saved ) $this->_alertStr .= '<p>Scope saved successfully.</p>';
		return $saved;
	}
	
	// OVERRIDDEN ACTIONS & FILTERS
	
	/**
	 * @see admin_ajax_Override::globalToLocal()
	 */
	public function globalToLocal() {
		$this->_alertStr .= '<p>Trying to move from global to local scope...</p>';
		// Call parent
		$moved = parent::globalToLocal();
		if( $moved ) $this->_alertStr .= '<p>Moved successfully.</p>';
		return $moved;
	}
	
	/**
	 * @see admin_ajax_Override::localToLocal()
	 */
	public function localToLocal() {
		$this->_alertStr .= '<p>Trying to move to new local scope...</p>';
		// Call parent
		$moved = parent::localToLocal();
		if( $moved ) $this->_alertStr .= '<p>Moved successfully.</p>';
		return $moved;
	}
	
	/**
	 * @see admin_ajax_Override::localToGlobal()
	 */
	public function localToGlobal() {
		// starting the alert...
		$this->_alertStr .= '<p>Trying to move to global from local scope...</p>';
		// Call parent
		$moved = parent::localToGlobal();
		// alert is finished
		if( $moved ) $this->_alertStr .= '<p>Moved successfully.</p>';
		return $moved;
	}
	
	// ERROR ACTIONS & FILTERS
	
	/**
	 * sidebars_widgets
	 *
	 * Called as WordPress Filter, hooks into when sidebars_widgets is retreived.
	 * Must return false because this is filtering an option we don't want.
	 *
	 * @return false
	 */
	public function sidebars_widgets( $sidebars_widgets ) {
		// temporarily unregister all the widgets.
		// doesn't save anything, just should make this page unusable for the moment.
		$this->plugin->widgets->factory->widgets = array();
		$this->addAction( 'admin_notices', 'admin_notices_session_error' );
		return false;
	}
	
	/**
	 * admin_notices_session_error
	 *
	 * Called as WordPress Action, hooks into when admin notices are rendered.
	 * This should have only been added if a session is not in progress but there are currently backups in place.
	 * This means that the user forgot to close the session, or another user is trying to edit.
	 *
	 * @return void
	 */
	public function admin_notices_session_error() {
		// remove all registered groups
		$this->plugin->widgets->registeredGroups = array(); ?>	
		
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>Sorry!</h2>
			<div class="error">
				<p>This page has temporarily been disabled by <a href="admin.php?page=extensible-widgets"><?php echo $this->plugin->pluginName; ?></a></p>
			</div>
			<p>This of course is not a normal process of WordPress.</p>
			<p>It happened because <?php echo $this->plugin->pluginName; ?> detected that another user entered another widget scope and now cannot allow you access to the global scope.</p>
			<p><a class="button-primary" href="<?php echo $this->guidURI; ?>?force&g=<?php echo $this->defaultGuid; ?>" title="Click to Edit Widgets Regardless of This Error">Edit Anyway...</a> but be warned, you will probably discard any changes other users have made to the scope they are editing!</p>
		</div>
		
		<?php require_once( ABSPATH . 'wp-admin/admin-footer.php' );
		exit;
	}
	
	// ACTIONS & FILTERS
	
	/**
	 * sidebar_admin_setup
	 *
	 * Called as WordPress Action, hooks into when the widget page is setup but before being rendered.
	 *
	 * @return void
	 */
	public function sidebar_admin_setup() {
		// Clear the registered groups, the necessary ones will be registered following this
		$this->plugin->widgets->registeredGroups = array();
		// REGISTER THIS GROUP
		$this->plugin->widgets->registerGroup( array(
			'id' => $this->sessionData['id'],
			'name' => $this->sessionData['instance']['group_name'],
			'before_widget' => $this->sessionData['instance']['before_widget'],
			'after_widget' => $this->sessionData['instance']['after_widget'],
			'before_title' => $this->sessionData['instance']['before_title'],
			'after_title' => $this->sessionData['instance']['after_title'],
			'description' => 'Drag widgets here to add them to this scope. Widgets can be moved to another scope by dragging them to Inactive Widgets below.' // 2.9

		) );
		// add the admin notice that we are editing a widget group locally
		$this->addAction('admin_notices', 'admin_notices_session');
	}
	
	/**
	 * admin_notices
	 *
	 * Called as WordPress Action, hooks into when admin notices are rendered.
	 * Debugging purposes. Prints out status string in the admin notices.
	 *
	 * @return void
	 */
	public function admin_notices() { ?>
		<?php if( empty( $this->_alertStr ) ) return; ?>
		<div class="updated fade fadeOut">
			<?php echo $this->_alertStr; ?>
		</div>
	<?php }
	
	/**
	 * admin_notices_session
	 *
	 * Called as WordPress Action, hooks into when admin notices are rendered.
	 * This should have only been added if a session was successfully started.
	 * It displays info and controls about the current session.
	 *
	 * @return void
	 */
	public function admin_notices_session() { 
		$crumbs = explode( xf_system_Path::DS, $this->sessionData['guid'] );
		$levels = array_reverse( array_values( $crumbs ) );
		$links = array();
		for( $i=0 ; $i<count($levels) ; $i++ ) {
			if( empty( $levels[$i] ) ) {
				$name = 'Global';
				$guid = $this->defaultGuid;
			} else {
				$name = $levels[$i];
				$guid = implode( xf_system_Path::DS, $crumbs );
			}
			$link = ( $i==0 ) ? $name : '<a class="button" href="'.$this->guidURI . '?g='.urlencode($guid).'" title="Edit Level '.(count($levels)-$i).'">'.$name.'</a>';
			array_unshift( $links, $link );
			array_pop( $crumbs );
		}
		$links = implode( ' '.xf_system_Path::DS.' ', $links ); ?>
		
		<div id="edit_level" class="wrap">
			<h2><small>Editing Scope Level <?php echo count($levels); ?> &raquo; </small><?php echo $this->sessionData['instance']['group_name']; ?></h2>
			<div class="setting_group">
				<div class="alignright">
					<a class="button-primary" href="<?php echo $this->defaultURI; ?>" title="Save the Current Scope and Exit">Save and Exit</a> <a class="button-primary" href="<?php echo $this->parentURI; ?>" title="Save and Go Back One Level">Go Back</a> <a class="button-primary" href="<?php echo $this->currentURI; ?>&force" title="Save the Current Scope">Save Level</a>
				</div>
				<div>Current Level: <?php echo $links; ?></div>
			</div>
			<p class="description alignleft">All other users will be denied access to this page until you exit to the global scope.</p>
			<p class="description alignright">Changes will not appear on the front-end until saving or exiting this scope.</p>
			<div class="clear"></div>
		</div>
		
	<?php
	}
}
?>