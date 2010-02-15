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
	public function header( $title = '' ) { 
		if( empty($title) ) $title = $this->currentPage->title; ?>
		<?php if( !$this->currentPage->isAsync ) : ?><div id="wpew-wrap" class="wrap"><?php endif; ?>
			<div id="icon-themes" class="icon32"><br /></div>
			<h2><?php echo $this->title; ?> &raquo; <?php echo $title; ?></h2>
			<?php do_action( 'admin_notices' ); ?>
			<div id="wpew-subnav" class="setting_group description"><p><?php 
			$navs = array();
			reset($this->_children);
			do {
				$child =& current($this->_children);
				$class = ($child == $this->currentPage) ? 'current ' : '';
				$navs[] = '<a href="'.$child->pageURI.'" class="'.$class.'wpew-navigation">'.$child->title.'</a>';
			} while( next($this->_children) !== false ); 
			echo implode(' | ', $navs ); ?></p></div>
			<div id="wpew-content">
	<?php }
	
	/**
	 * Used internally for a common content header
	 *
	 * @return void
	 */
	public function footer() { ?>
		</div>
		<?php if( $this->currentPage->isAsync ) return; ?></div>
		<div class="wrap">
			<form id="wpew-footer" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
				<input type="hidden" name="cmd" value="_s-xclick">
				<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
				<p>Thank you for using <strong>Extensible Widgets Version <?php echo $this->root->version; ?></strong> by <a href="http://jimisaacs.com" target="wpew_window">Jim Isaacs</a> | <a href="http://wordpress.org/extend/plugins/extensible-widgets/" target="wpew_window">Documentation</a> | <a href="http://jidd.jimisaacs.com/archives/863#footer" target="wpew_window">Feedback</a> | Please <input style="vertical-align:middle;" type="image" src="http://jidd.jimisaacs.com/wp-content/uploads/2010/02/donate_button.png" border="0" name="submit" alt="Help Me! Heeeelp Meeee!"> if it has been of any use.<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1"></p>
			</form>
		</div>
	<?php }
	
	// STATES
	
	/**
	 * @see xf_wp_IPluggable::index()
	 */
	public function index() {}
}
?>