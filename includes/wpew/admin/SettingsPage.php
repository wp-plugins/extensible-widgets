<?php
/**
 * This file defines wpew_admin_SettingsPage, a controller class a plugin admin page.
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
 * wpew_admin_SettingsPage
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_SettingsPage extends xf_wp_AAdminController {
	
	/**
	 * @see xf_wp_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return xf_patterns_ASingleton::getSingleton(__CLASS__);
	}
	
	/**
	 * @var string $title This controller's title
	 */
	public $title = "Settings";

	/**
	 * This is just a private helper function for saving settings and giving notices where needed.
	 *
	 * @return bool
	 */
	private function saveSettings( $settings ) {
		if( !empty($settings) ) {
			if( empty($settings['widgetsDir']) ) {
				$this->noticeErrors .= '<p><strong>Failed to save!</strong> Widget Directory cannot be empty.</p>';
				return;
			} else {
				// Convert to POSIX path because well... everything favors this format for string manipulation
				$widgetsDir = xf_system_Path::toPOSIX( stripslashes($settings['widgetsDir']) );
				if( !xf_system_Path::isAbs( $settings['widgetsDir']) ) {
					if( !file_exists( ABSPATH . $widgetsDir ) ) {
						$this->noticeErrors .= '<p><strong>Failed to save!</strong> Widgets Directory does not exist.</p>';
						return false;
					}
					$settings['widgetsDir'] = $widgetsDir;
				} else if( !file_exists( $widgetsDir ) ) {
					$this->noticeErrors .= '<p><strong>Failed to save!</strong> Widgets Directory does not exist or is not relative to the WordPress root.</p>';
					return false;
				} else {
					$widgetsDir = xf_system_Path::replace( $widgetsDir, xf_system_Path::toPOSIX(ABSPATH) );
				}
				$settings['widgetsDir'] = $widgetsDir;
			}
			if( empty($settings['roles']) ) {
				$this->noticeErrors .= '<p><strong>Failed to save!</strong> At least one role for must be selected.</p>';
				return false;
			} else {
				global $current_user;
				$removing = array_diff( array_keys($this->parent->plugin->roles), $settings['roles'] );
				do {
					$role = current($removing);
					if( !empty($role) && $current_user->has_cap($role) ) {
						$this->noticeErrors .= '<p>Failed to remove the role <strong>'.$role.'</strong>, this is the current user\'s role!</p>';
						// Add the role back into the roles add
						$settings['roles'][] = $role;
					}
				} while( next($removing) !== false );
				// Save the roles
				$this->parent->plugin->roles = $settings['roles'];
			}
			// Save the settings
			$this->parent->plugin->settings = $settings;
			return true;
		}
		return false;
	}
	
	// PAGE STATES
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function index() {
		$this->header();
		$this->settingsForm();
		$this->resetSettingsForm();
		$this->footer();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onSaveSettings() {
		if( $this->saveSettings( $this->submitted ) ) {
			$this->noticeUpdates .= '<p><strong>Settings saved.</strong></p>';
		}
		$this->index();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onResetSettings() {
		global $current_user;
		$settings = $this->parent->plugin->defaultSettings;
		$settings['roles'] = $current_user->roles;
		if( $this->saveSettings( $settings ) ) {
			$this->noticeUpdates .= '<p><strong>Settings reset.</strong></p>';
		}
		$this->index();
	}
	
	public function settingsForm() { ?>
		<form method="post" action="<?php echo $this->controllerURI; ?>">
			<?php $this->doStateField( 'onSaveSettings' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->getFieldID('widgetsDir'); ?>">Widgets Directory</label></th>
					<td><input size="80" type="text" id="<?php echo $this->getFieldID('widgetsDir'); ?>" name="<?php echo $this->getFieldName('widgetsDir'); ?>" value="<?php echo esc_attr( stripslashes($this->parent->plugin->settings['widgetsDir']) ); ?>">
					<p class="description">This is the directory <?php echo $this->parent->plugin->pluginName; ?> uses for looking up widget templates. Remember widget templates are NOT the same as theme templates. The path specified may be an absolute path or relative to the the root of your WordPress installation.</p></td>
				</tr>
				<tr valign="top">
					<th scope="row"><span>Widget Example</span></th>
					<td><?php $this->parent->plugin->widgets->importWidget( 'wpew_widgets_View', false );
					$testClass = 'custom_MyExtended_Widget';
					if( !class_exists($testClass, false)) {
						eval('class '.$testClass.' extends wpew_widgets_View {}');
					}
					$testWidget = new $testClass(); ?>
					Lets say you have a PHP Widget class by the name of:<br />
					<code><?php echo $testClass; ?></code>
					<p>The directory of this widget's view templates would be located here:<br />
					<code><?php // I know PHP doesn't mind different slash styles in a path, this conversion is just for cosmetic reasons.
					echo xf_system_Path::toSystem($testWidget->getViewsDir()); ?></code></p>
					<p>Within that directory, view templates are defined with a comment header in this format:<br />
					<code>&lt;?php /* Template Name: My Template */ ?&gt;</code></p>
					<p>Where <strong>"My Template"</strong> is the name that appears in the view controls' dropdown. Files without this comment header are not valid custom view templates.</p></td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><span>Administrative Access</span></th>
					<td><ul><?php
					$roles = new WP_Roles();
					xf_display_Renderables::buildInputList( $this->getFieldID('roles'), $this->getFieldName('roles'), $roles->role_names, array(
						'checked' => array_keys($this->parent->plugin->roles),
						'beforeInput' => '<li>',
						'afterInput' => '</li>',
						'beforeLabel' => ' &nbsp; <span>',
						'afterLabel' => '</span>'/*,
						'type' => 'radio'*/
					)); ?></ul>
					<p class="description">The selected WordPress user role(s) will have access to the <?php echo $this->parent->plugin->pluginName; ?> administrative menu while logged in. This functionality is completely dependent on the <a href="http://codex.wordpress.org/Roles_and_Capabilities" target="wpew_window">WordPress role/capability API</a>. It should be compatible with any role/capability management system, and custom roles should also appear within the list above.</p></td>
				</tr>
			</table>
			<p><input type="submit" name="<?php echo $this->getFieldName('submit'); ?>" class="button-primary" value="Save Changes" /></p>
		</form>
	<?php }
	
	public function resetSettingsForm() { ?>
		<form onsubmit="return confirm('Are you sure you want to reset to the default settings?');" method="post" action="<?php echo $this->controllerURI; ?>">
			<?php $this->doStateField( 'onResetSettings' ); ?>
			<h3>Reset to Default Settings</h3>
			<p class="description">This does not include all data associated with this plugin, it only pertains to <span class="red">everything editable on this page</span>.</p>
			<p><input type="submit" name="<?php echo $this->getFieldName('submit'); ?>" class="button-primary" value="Reset Settings" /></p>
		</form>
<?php }
	
	/**
	 * Used internally for a common content header
	 *
	 * @return void
	 */
	public function header() {
		$this->parent->header();
	}
	
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