<?php
/**
 * This file defines wpew_admin_AdminMenu, a controller class for a plugin admin menu.
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
 * wpew_admin_AdminMenu is the admin menu controller class for this plugin
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_AdminMenu extends xf_wp_AAdminMenu {
	
	/**
	 * @see xf_wp_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return xf_patterns_ASingleton::getSingleton(__CLASS__);
	}
	
	/**
	 * @var string $title This controller's title
	 */
	public $title = "Extensible Widgets";
	/**
	 * @var string $title Optional menu title, defaults to the controller's title
	 */
	public $menuTitle = "Ext. Widgets";
	
	/**
	 * @see xf_wp_IPluggable::init()
	 */
	public function init() {
		// Do everything through this hook to make sure this object has been set as an extension
		$this->addLocalAction( 'onSetReference' );
	}
	
	/**
	 * Action Hook - wpew_admin_AdminMenu_onSetReference
	 *
	 * @return void
	 */
	public function onSetReference() {
		
		// Build admin menu
		$this->addChild( wpew_admin_RegistrationPage::getInstance() );
		$this->addChild( wpew_admin_SettingsPage::getInstance() );
		$this->addChild( wpew_admin_ExportPage::getInstance() );
		$this->addChild( wpew_admin_ImportPage::getInstance() );
		$this->addChild( wpew_admin_UninstallPage::getInstance() );
		
		// Set the capabilities of the menu to match the plugin
		$this->setCapabilities( $this->plugin->capability );
		
		// Finally build the menu adding to WordPress admin
		$this->build();
	}
	
	// PAGE STATES
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onDocumentation() {
		$this->header();
		include('views/documentation.php');
		$this->footer();
	}
	
	/**
	 * Used internally for a common content header
	 *
	 * @return void
	 */
	public function header( $title = '' ) { 
		if( empty($title) ) $title = $this->currentController->title; ?>
		<?php if( !$this->currentController->isAsync ) : ?><div id="wpew-wrap" class="wrap"><?php endif; ?>
			<div id="icon-themes" class="icon32"><br /></div>
			<h2><?php echo $this->plugin->pluginName; ?> &raquo; <?php echo $title; ?></h2>
			<div id="wpew-subnav" class="setting_group description"><p><?php 
			$navs = array();
			reset($this->_children);
			do {
				$child = current($this->_children);
				$class = ($child == $this->currentController) ? 'current ' : '';
				$navs[] = '<a href="'.$child->controllerURI.'" class="'.$class.'wpew-navigation">'.$child->title.'</a>';
			} while( next($this->_children) !== false ); 
			echo implode(' | ', $navs ); ?></p></div>
			<?php do_action( 'admin_notices' ); ?>
			<div id="wpew-content">
	<?php }
	
	/**
	 * Used internally for a common content footer
	 *
	 * @return void
	 */
	public function footer() { ?>
		</div>
		<?php if( $this->currentController->isAsync ) return; ?></div>
		<div class="wrap">
			<form id="wpew-footer" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
				<input type="hidden" name="cmd" value="_s-xclick">
				<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
				<p class="alignright">Version <?php echo $this->plugin->version; ?></p>
				<p>Thank you for using <a href="http://wordpress.org/extend/plugins/extensible-widgets/" target="wpew_window"><?php echo $this->plugin->pluginName; ?></a>. | <a href="<?php echo $this->controllerURI; ?>&state=onDocumentation">Documentation</a> | <a href="http://jidd.jimisaacs.com/archives/863#footer" target="wpew_window">Feedback</a> | Please <input style="vertical-align:middle;" type="image" src="http://jidd.jimisaacs.com/files/2010/02/donate_button.png" border="0" name="submit" alt="Help Me! Heeeelp Meeee!"> if this plugin has been of any use.<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1"></p>
			</form>
		</div>
	<?php }
	
	// STATES
	
	/**
	 * @see xf_wp_IAdminController::index()
	 */
	public function index() {}
}
?>