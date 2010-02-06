<?php

require_once(dirname(__FILE__).'/../../xf/wp/AAdminMenu.php');

/**
 * wpew_admin_wpew_Page
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_wpew_Page extends xf_wp_AAdminMenu {
	
	/**
	 * @var string $title This page's title
	 */
	public $title = "Extensible Widgets";
	/**
	 * @var string $title Optional menu title, defaults to the page's title
	 */
	public $menuTitle = "Ext. Widgets";
	
	/**
	 * Used internally for a common content header
	 *
	 * @return void
	 */
	public function header() { ?>
		<div class="wrap">
			<div id="icon-themes" class="icon32"><br /></div>
	<?php }
	
	/**
	 * Used internally for a common content header
	 *
	 * @return void
	 */
	public function footer() { ?>
		<br />
		<p class="description">Thank you for using <strong>Extensible Widgets Version <?php echo $this->root->settings['version']; ?></strong> by <a href="http://jimisaacs.com" target="wpew_window">Jim Isaacs</a>. | <a href="http://jidd.jimisaacs.com" target="wpew_window">Documentation</a> | <a href="http://jidd.jimisaacs.com" target="wpew_window">Feedback</a></p>
		</div>
	<?php }
	
	// STATES
	
	/**
	 * @see xf_wp_IPluggable::defaultState()
	 */
	public function defaultState() {}
}
?>