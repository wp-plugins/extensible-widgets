<?php

require_once(dirname(__FILE__).'/../../xf/utils/Misc.php');
require_once('View.php');

/**
 * This is an example of a widget that used the previous widget's functionality, but is still higher up in the inheritance tree.
 * Use this widget to retrieve statuses from a specified twitter account.
 * @package wpew
 * @subpackage widgets
 */
class wpew_widgets_Twitter extends wpew_widgets_View {
	
	// STATIC MEMBERS
	
	/**
	 * @see wpew_AWidget::$tabLabel
	 */
	public static $tabLabel = 'Twitter';
	
	/**
	 * @see wpew_AWidget::getDefaultSettings()
	 */
	public static function getDefaultSettings( &$obj ) {
		return array( 
			'username' => '',
			'password' => '',
			'limit' => '5'
		);
	}
	
	/**
	 * @see wpew_IWidget::save()
	 */
	public static function save( &$obj, $new_settings ) {
		$obj->settings['username'] = sanitize_user( $new_settings['username'] );
		$obj->settings['password'] = $new_settings['password']; // no sanitizing on this setting
		$obj->settings['limit'] = (int) $new_settings['limit'];
	}
	
	/**
	 * @see wpew_IWidget::renderAdmin()
	 */
	public static function renderAdmin( &$obj ) {
		$username = esc_attr( $obj->settings['username'] );
		$password = esc_attr( $obj->settings['password'] );
		$limit = (int) $obj->settings['limit']; ?>
		
		<fieldset class="setting_group">
			<legend class="description">Special View Parameters:</legend>
			
			<fieldset class="setting_group">
				<legend class="description">The credentials of the Twitter account:</legend>
				
				<p><label for="<?php echo $obj->get_field_id('username'); ?>">Username:</label> <input id="<?php echo $obj->get_field_id('username'); ?>" name="<?php echo $obj->get_field_name('username'); ?>" value="<?php echo $username; ?>" type="text" class="widefat" /></p>
				
				<p><label for="<?php echo $obj->get_field_id('password'); ?>">Password:</label> <input id="<?php echo $obj->get_field_id('password'); ?>" name="<?php echo $obj->get_field_name('password'); ?>" value="<?php echo $password; ?>" type="password" class="widefat" /></p>
			</fieldset>
			
			<p><label for="<?php echo $obj->get_field_id('limit'); ?>">Limit:</label> <input id="<?php echo $obj->get_field_id('limit'); ?>" name="<?php echo $obj->get_field_name('limit'); ?>" value="<?php echo $limit; ?>" type="text" size="3" /><br />
			<small class="description">How many tweets to return per page.</small></p>
			
			<p><small class="description">Access object in custom view with <code>$twitter</code><br />
			Access array in custom view with <code>$tweets</code></small></p>
			
		</fieldset>
		
	<?php }
	
	// INSTANCE MEMBERS
	
	// CONSTRUCTOR
	public function __construct( $name = '', $wOpts = array(), $cOpts = array() )
	{
		// Set Name
		if( empty( $name ) ) $name = __('Twitter');
		// Set Options
		$wOpts = wp_parse_args( $wOpts, array(
			'description' => __( "Use this widget to retrieve statuses from a specified twitter account." )
		) );
		// parent constructor
		parent::__construct( $name, $wOpts, $cOpts );
	}
	
	/**
	 * @see wpew_IWidget::beforeOutput()
	 */
	public function beforeOutput() {
		// call parent
		parent::beforeOutput();
		// Add the data to the view params added by the parent class, this way you can access the data extracted in the view!
		require_once(dirname(__FILE__).'/../../xf/webservices/Twitter.php');
		$twitter = new xf_webservices_Twitter($this->settings['username'], $this->settings['password']);
		$this->settings['view_params']['twitter'] = $twitter;
		$this->settings['view_params']['tweets'] = $twitter->getUserTimeline( $this->settings['limit'] );
	}
	
	/**
	 * @see wpew_widgets_IView::defaultView()
	 */
	public function defaultView() {
		// START DEFAULT
		$tweets = $this->settings['view_params']['tweets'];
		$html = "";
		if( is_array( $tweets ) ) {
			$html .= '';
			foreach($tweets as $status){
				//echo $status['text'];
				$string = $status['text'];
				//$chars = preg_split('/@/', $str, -1, PREG_SPLIT_OFFSET_CAPTURE);
				$len = strpos($string, " ");
				if($len) {
					$newstring = substr($string, 0, $len);
					if($newstring[0] == "@") { 
						$user = ltrim($newstring, "@");
						$string = substr($string, strlen($newstring));
						$string = '@<a href="'.xf_webservices_Twitter::URL_TWITTER.$user.'" target="_blank">'.$user.'</a> '.$string;
					}
				}
				$html .= '<div class="balloonTop"><br /></div>';
				$html .= '<div class="balloonMiddle">' . $string . '</div>';
				$html .= '<div class="balloonBottom"><br /></div>';
				$timestamp = strtotime($status['created_at']);
				$html .= '<small><strong>' . htmlentities($status['user']['screen_name']) . '</strong> &#45 ' . xf_utils_Misc::getTimeAgo( $timestamp ) . ', ' . date("m.d.y", $timestamp) . '</small></li>';
			}
			echo $html;
		} else {
			echo $this->settings['view_params']['twitter']->username . ' has no tweets!';
		}
		// END DEFAULT
	}
}
?>