<?php

require_once(dirname(__FILE__).'/../../xf/display/Renderables.php');
require_once(dirname(__FILE__).'/../../xf/wp/AAdminPage.php');
require_once(dirname(__FILE__).'/../../wpew.php');

/**
 * wpew_admin_CreateController_Page
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_CreateController_Page extends wpew_admin_wpew_Page {
	
	/**
	 * @var string $title This page's title
	 */
	public $title = "Create Controller";
	
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
				$widgetsDir = $settings['widgetsDir'];
				if( !xf_system_Path::isAbs( $settings['widgetsDir']) ) {
					$widgetsDir = xf_system_Path::replace( $settings['widgetsDir'], ABSPATH );
					if( !file_exists( xf_system_Path::join( ABSPATH, $widgetsDir ) ) ) {
						$this->noticeErrors .= '<p><strong>Failed to save!</strong> Widgets Directory does not exist.</p>';
						return false;
					}
					$settings['widgetsDir'] = $widgetsDir;
				} else if( !file_exists( $widgetsDir ) ) {
					$this->noticeErrors .= '<p><strong>Failed to save!</strong> Widgets Directory does not exist.</p>';
					return false;
				}
				$settings['widgetsDir'] = $widgetsDir;
			}
			if( empty($settings['roles']) ) {
				$this->noticeErrors .= '<p><strong>Failed to save!</strong> At least one role for must be selected.</p>';
				return false;
			} else {
				global $current_user;
				$added = array();
				$roles = new WP_Roles();
				$cap = $this->parentPage->capability;
				foreach( $roles->role_names as $index => $name ) {
					if( in_array( $index, $settings['roles'], true ) ) {
						$roles->add_cap( $index, $cap );
						$added[] = $index;
					} else if( $current_user->has_cap($index) ){
						$this->noticeErrors .= '<p>Failed to remove the role <strong>'.$name.'</strong>, this is the your role!</p>';
						$added[] = $index;
					} else {
						$roles->remove_cap( $index, $cap );
					}
				}
				$settings['roles'] = $added;
			}
			$this->root->settings = $settings;
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
	public function defaultState() {
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
	public function saveSettingsState() {
		if( $this->saveSettings( $this->submitted ) ) {
			$this->noticeUpdates .= '<p><strong>Settings saved.</strong></p>';
		}
		$this->defaultState();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function resetSettingsState() {
		if( $this->saveSettings( $this->root->defaultSettings ) ) {
			$this->noticeUpdates .= '<p><strong>Settings reset.</strong></p>';
		}
		$this->defaultState();
	}
	
	public function settingsForm() { ?>
		<form method="post" action="<?php echo $this->formAction; ?>">
			<?php $this->doStateField( 'saveSettingsState' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->getFieldID('widgetsDir'); ?>">Widgets Directory</label></th>
					<td><input size="80" type="text" id="<?php echo $this->getFieldID('widgetsDir'); ?>" name="<?php echo $this->getFieldName('widgetsDir'); ?>" value="<?php echo $this->root->settings['widgetsDir']; ?>">
					<p class="description">This is the directory Extensible Widgets uses for looking up widget templates. Remember widget templates are NOT the same as theme templates. The path specified may be an absolute path or relative to the the root of your WordPress installation.</p></td>
				</tr>
				<tr valign="top">
					<th scope="row"><span>Example:</span></th>
					<td><?php
					$this->widgets->importWidget( 'wpew_widgets_View', false );
					$testClass = 'custom_MyExtended_Widget';
					if( !class_exists($testClass, false)) {
						eval('class '.$testClass.' extends wpew_widgets_View {}');
					}
					$testWidget = new $testClass(); ?>
					Lets say you have a PHP class by the name of:<br />
					<code><?php echo $testClass; ?></code>
					<p>The directory of this widget's view templates would be located here:<br />
					<code><?php echo $testWidget->getViewsDir(); ?></code></p>
					<p>Within that directory, view templates are defined with a comment header in this format:<br />
					<code>&lt;?php /* Template Name: My Template */ ?&gt;</code></p>
					<p>Where <code>My Template</code> is the name that appears in the dropdown of the view controls.</p></td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><label for="<?php echo $this->getFieldID('roles'); ?>">Administrative Access</label><p class="description">To select multiple items hold the shift or control (command on Macintosh) keyboard keys.</p></th>
					<td><select class="multiple" id="<?php echo $this->getFieldID('roles'); ?>" name="<?php echo $this->getFieldName('roles'); ?>[]" multiple="multiple"><?php
					$roles = new WP_Roles();
					xf_display_Renderables::buildSelectOptions( array_flip( $roles->role_names ), $this->root->settings['roles'] );
					?></select>
					<p class="description">The selected WordPress user role(s) will have access to the Extensible Widgets administrative menu while logged in.</p></td>
				</tr>
			</table>
			<p><input type="submit" name="<?php echo $this->getFieldName('submit'); ?>" class="button-primary" value="Save Changes" /></p>
		</form>
	<?php }
	
	public function resetSettingsForm() { ?>
		<h3>Reset to Default Settings</h3>
		<p>This does not include all data associated with this plugin, it only pertains to data that is editable on this page.</p>
		<form method="post" action="<?php echo $this->formAction; ?>">
			<?php $this->doStateField( 'resetSettingsState' ); ?>
			<p><input onclick="return confirm('Are you sure you want to reset to the default settings?');" type="submit" name="<?php echo $this->getFieldName('submit'); ?>" class="button-primary" value="Reset Settings" /></p>
		</form>
<?php }
	
	/**
	 * Used internally for a common content header
	 *
	 * @return void
	 */
	public function header() {
		$this->parentPage->header(); ?>
			<h2><?php echo $this->parentPage->title; ?> &raquo; <?php echo $this->title; ?></h2>
			<?php do_action( 'admin_notices' ); ?>
	<?php }
	
	/**
	 * Used internally for a common content footer
	 *
	 * @return void
	 */
	public function footer() {
		$this->parentPage->footer();
	}
}
?>