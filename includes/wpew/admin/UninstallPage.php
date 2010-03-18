<?php
/**
 * This file defines wpew_admin_UninstallPage, a controller class a plugin admin page.
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
 * wpew_admin_UninstallPage
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_UninstallPage extends xf_wp_AAdminController {
	
	/**
	 * @see xf_wp_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return xf_patterns_ASingleton::getSingleton(__CLASS__);
	}
	
	/**
	 * @var string $title This controller's title
	 */
	public $title = "Uninstall";
	
	/**
	 * @ignore
	 * Used internally
	 */
	private $widgetData;
	
	/**
	 * Function called before any rendering occurs within the WordPress admin
	 *
	 * return void
	 */
	public function onBeforeRender() {		
		if( $this->state == 'onUninstall' ) {
			// This object manages the session, therefore it should be set here
			// Get the singleton, and be sure to set it as the apporiate extension
			$this->parent->plugin->addExtension( 'override', wpew_admin_WidgetsAjaxOverride::getInstance() );
		}
	}
	
	// PAGE STATES
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function index() {
		$this->header();
		$this->uninstallForm();
		$this->footer();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onUninstall() {
		// Starting
		$this->noticeUpdates .= '<p>Starting Uninstaller...</p>';
		$output = '';
		// Is there a session in progress?
		$inSession = ( $this->parent->plugin->override->inSession || $this->parent->plugin->widgets->backups );
		// Get the actions as an array
		$actions = ( is_array($this->submitted['actions']) ) ? $this->submitted['actions'] : array();
		// Check for the killSession action
		if( $inSession && !isset($actions['killSession']) ) {
			$this->noticeErrors .= '<p><strong>Stopped!</strong> ';
			if( $this->parent->plugin->override->inSession ) {
				$this->noticeErrors .= 'You are currently editing a widget group on the <a href="widgets.php">Widgets Administration Page</a>.';
			} else {
				$this->noticeErrors .= 'Currently there is a user editing a widget group on the <a href="widgets.php">Widgets Administration Page</a>.';
			}
			$this->noticeErrors .= '</p>';
			$this->index();
			return;
		} else if($inSession) {
			// start session kill
			$this->noticeUpdates .= '<p>Found user currently editing a widget group!</p>';
			if( $this->parent->plugin->override->killSession() ) {
				delete_option( $this->parent->plugin->widgets->getOptionName('widget_option_backups') );
				$output .= '<p>Session Destroyed...</p>';
			} else {
				$this->noticeErrors .= '<p><strong>Stopped!</strong> Failed to destroy session!</p>';
			}
		}
		// Check if there were any errors, continue if not
		if( empty($this->noticeErrors) ) {
			// No need to do this one again
			unset($actions['killSession']);
			// Save this in case things run out of order
			$registration = $this->parent->plugin->widgets->registration;
			foreach( $actions as $action ) {
				switch($action) {
					case 'widgets' :
						if( is_array($registration) ) {
							foreach( $registration as $class => $flags ) {
								$widget = new $class();
								delete_option( $widget->option_name );
								unset( $widget );
							}
						}
						$output .= '<p>Plugin Widgets Removed...</p>';
					break;
					case 'registration' :
						delete_option( $this->parent->plugin->widgets->getOptionName('registration') );
						$output .= '<p>Widget Registration Removed...</p>';
					break;
					case 'settings' :
						delete_option( $this->parent->plugin->getOptionName('settings') );
						$output .= '<p>Plugin Settings Removed...</p>';
					break;
					case 'capabilities' :
						$this->parent->plugin->roles = null;
						$output .= '<p>Plugin Capabilities Removed...</p>';
					break;
				}
			}
			$this->header();
			echo $output;
			// Deactivate the plugin
			deactivate_plugins( xf_system_Path::join('extensible-widgets','plugin.php') ); ?>
			<p>Plugin Deactivated...</p>
			<p>Plugin Uninstalled Successfully.</p>
			<h3>Redirecting...</h3>
			<script type="text/javascript"> setTimeout(function(){window.location = 'plugins.php?deactivate=true'},2000); </script>
			<noscript><div class="error"><p>Failed to Redirect! Sorry, please <a href="plugins.php?deactivate=true">go here</a>.</p></div></noscript>
			<?php $this->footer();
		} else {
			$this->index();
		}
	}
	
	/**
	 * Used internally to render the uninstallForm
	 *
	 * @return void
	 */
	public function uninstallForm() { ?>
		<form onsubmit="return confirm('Are you sure? You cannot undo this action, though you may download all the data associated with this plugin via the export page.');" name="uninstallForm" method="post" action="<?php echo $this->controllerURI; ?>">
			<p class="description">Clicking the button below will remove all the specified data from the database that may have been added by this plugin, and then it is automatically deactivated. If you do not want to delete any data, you can also simply <a href="plugins.php">deactivate</a> this plugin instead, all the data with the exception of any widgets added will remain. If you wish to save a backup of your data before doing anything, try the <a href="admin.php?page=extensible-widgets/export" class="wpew-navigation">export</a> page.</p>
			<?php $this->doStateField( 'onUninstall' ); ?>			
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Uninstaller Actions</th>
					<td><ul><?php $inputs = array(
						'killSession' => __('Destroy Widget Group Editing Sessions'),
						'widgets' => __('Remove All Plugin Widgets'),
						'registration' => __('Remove Widget Registration Data'),
						'settings' => __('Remove Plugin Settings'),
						'capabilities' => __('Remove Plugin Capabilities')
					);
					if( isset($this->submitted['uninstall-submit']) ) {
						$checked = ( isset($this->submitted['actions']) ) ? $this->submitted['actions'] : array();
					} else {
						$checked = array_keys($inputs);
						unset($checked[0]);
					}
					xf_display_Renderables::buildInputList( $this->getFieldID('actions'), $this->getFieldName('actions'), $inputs, array(
						'checked' => $checked,
						'beforeInput' => '<li>',
						'afterInput' => '</li>',
						'beforeLabel' => ' &nbsp; <span>',
						'afterLabel' => '</span>'/*,
						'type' => 'radio'*/
					)); ?></ul>
					<p class="description">All actions are ran before deactivating the plugin, if any action fails the uninstaller stops.</p></td>
				</tr>
			</table>
			<p><input type="submit" name="<?php echo $this->getFieldName('uninstall-submit'); ?>" class="button-primary" value="Uninstall <?php echo $this->parent->plugin->pluginName; ?>" /></p>
		</form>
	<?php }
	
	/**
	 * Used internally for a common content header
	 *
	 * @return void
	 */
	public function header() {
		$this->parent->header(); ?>
	<?php }
	
	/**
	 * Used internally for a common content footer
	 *
	 * @return void
	 */
	public function footer() {
		$this->parent->footer();
	}
}
?>