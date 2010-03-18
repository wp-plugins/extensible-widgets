<?php			
/*
Plugin Name: Extensible Widgets
Plugin URI: http://jidd.jimisaacs.com/archives/863
Description: In addition to adding numerous extremely useful widgets for developers and users alike, this plugin is a system written on a PHP 5 object oriented structure. In short, it is built for modification and extension. It wraps the WordPress Widget API to allow for an alternative, and in my opinion more robust method to hook into and use it. Widgets are WordPress's version of user interface modules. They already support an administrative and client-side view. This system simply leverages that with a higher potential in mind.
Author: Jim Isaacs
Version: 0.9.4
Author URI: http://jidd.jimisaacs.com/
*/

// Debugging purposes for this file only
// X Framework has it's one debugging mechanism within the class xf_errers_Error, but this must be set to debug this file specifically
/*ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL & ~E_NOTICE );*/

/**
 * Class holding the Extensible Widgets plugin hooks for WordPress.
 * This file should be strictly compatible with PHP 4 and above.
 * Which then enables a PHP version checker during plugin activation.
 *
 * @package WordPress
 */
class wpew_PHP4 {
	
	/**
	 * WordPress action callback - plugins_loaded
	 *
	 * @return void
	 */
	function plugins_loaded() {
		if( class_exists('wpew', false) || isset($GLOBALS['wpew']) ) return;
		// Here starts PHP 5, only load the script within this hook or PHP 5 version checking is moot
		require_once('includes/wpew.php');
		// Instantiate the global!!!
		$GLOBALS['wpew'] =& wpew::getInstance();
		// Register deavitvation hook to global, need to use the PHP 4 wrapper anymore here (It passed)
		register_deactivation_hook( __FILE__, array( $GLOBALS['wpew'], 'deactivate' ) );
	}
	
	/**
	 * WordPress activation callback
	 *
	 * @return void
	 */
	function activation_hook() {
		$instance = new wpew_PHP4();
		$message = $instance->activationCheckPHP( '5' );
		$message .= $instance->activationCheckWP( '2.8' );
		if( !empty($message) ) {
			$instance->activationError( __FILE__, '<ul><li><strong>Sorry, Extensible Widgets failed to activate!</strong><li>'.$message.'</ul>' );
		}
		// Checks passed, we are good so far, just make sure we have a singleton
		wpew_PHP4::plugins_loaded();
		global $wpew;
		if( empty( $wpew->settings['version'] ) ) {
			$wpew->install();
		}
		$wpew->activate();
	}
	
	/**
	 * Called during activation to check if plugin can be activated based on PHP version.
	 *
	 * @return string
	 */
	function activationCheckPHP( $version ) {
		if( version_compare( phpversion(), $version, '<') ) return '<li>Requires PHP '.$version.' or above, your version is '.phpversion().'</li>';
		return '';
	}
	
	/**
	 * Called during activation to check if plugin can be activated based on WordPress version.
	 *
	 * @return string
	 */
	function activationCheckWP( $version ) {
		global $wp_version;
		if( version_compare( $wp_version, $version, '<') ) return '<li>Requires WordPress '.$version.' or above, your version is '.$wp_version.'</li>';
		return '';
	}
	
	/**
	 * Called during activation if the activation check did not pass successfully.
	 *
	 * @return void
	 */
	function activationError( $plugin, $message ) {
		deactivate_plugins( $plugin );
		ob_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<title><?php echo $title; ?> &lsaquo; <?php bloginfo('name') ?>  &#8212; WordPress</title>
<?php
wp_admin_css( 'css/global' );
do_action('admin_print_styles');
?>
</head>
<body>
<?php echo $message; ?>
</body>
</html>
		<?php $html = ob_get_clean();
		exit( $html );
	}
}

// Add callbacks
add_action('plugins_loaded', array( wpew_PHP4, 'plugins_loaded' ) );
register_activation_hook( __FILE__, array( wpew_PHP4, 'activation_hook' ) );
?>