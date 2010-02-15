<?php

require_once(dirname(__FILE__).'/../../xf/system/Path.php');
require_once(dirname(__FILE__).'/../../xf/wp/AAdminPage.php');
require_once('WidgetsAjaxOverride.php');

/**
 * wpew_admin_Uninstall_Page
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_Uninstall_Page extends xf_wp_AAdminPage {
	
	/**
	 * @var string $title This page's title
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
	/*public function onBeforeRender() {		
		if( $this->state == 'onUninstall' ) {}
	}*/
	
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
		// Here we must deactivate the plugin first in order to call the deactivation hook which uses the settings
		deactivate_plugins( xf_system_Path::join('extensible-widgets','plugin.php') );
		$this->header();
		if( isset($_SESSION['group_data']) ) : 
			$override =& wpew_admin_WidgetsAjaxOverride::getInstance();
			$override->killSession(); 
			?>
			<p>Scope Session Ended...</p>
		<?php endif;
		delete_option( $this->widgets->getOptionName('widget_option_backups') );
		if( $this->widgets->registration ) {
			foreach( $this->widgets->registration as $class => $flags ) {
				$widget = new $class();
				delete_option( $widget->option_name );
				unset( $widget );
			}
		}
		delete_option( $this->widgets->getOptionName('registration') ); ?>
		<p>Options Removed...</p>
		<?php 
		// Now we can delete the settings option
		delete_option( $this->root->getOptionName('settings') );
		?>
		<p>Plugin Deactivated...</p>
		<p>Plugin Uninstalled Successfully.</p>
		<p><strong>Redirecting...</strong></p>
		<script type="text/javascript">
		setTimeout(function(){window.location = 'plugins.php?deactivate=true'},1000); 
		</script>
		<?php $this->footer();
		die(0);
	}
	
	/**
	 * Used internally to render the uninstallForm
	 *
	 * @return void
	 */
	public function uninstallForm() { ?>
		<form onsubmit="return confirm('Are you sure? You cannot undo this action, though you may download an export of all data associated with this plugin.');" name="uninstallForm" method="post" action="<?php echo $this->pageURI; ?>">
			<p class="description">Clicking the button below will remove all data from the database that may have been added by this plugin, and then deactivates it. If you do not want to lose this data, then simply <a href="plugins.php">deactivate</a> this plugin instead, all the data with the exception of the widgets will remain. If you wish to save the data before uninstalling try the <a href="admin.php?page=wpew_admin_export" class="wpew-navigation">export</a> page.</p>
			<?php $this->doStateField( 'onUninstall' ); ?>
			<p><input type="submit" name="Submit" class="button-primary" value="Uninstall this Plugin" /></p>
		</form>
	<?php }
	
	/**
	 * Used internally for a common content header
	 *
	 * @return void
	 */
	public function header() {
		$this->parentPage->header(); ?>
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