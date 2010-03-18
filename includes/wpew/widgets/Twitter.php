<?php
/**
 * This file defines wpew_widgets_Twitter, an Extensible Widget class.
 * 
 * PHP version 5
 * 
 * @package wpew
 * @subpackage widgets
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @link http://jidd.jimisaacs.com
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * This is an example of a widget that used the previous widget's functionality, 
 * but is still higher up in the inheritance tree. Use this widget to retrieve 
 * statuses from a specified twitter account.
 *
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
	 * WordPress Hook - When widget is registered on Registration controller
	 * Return false to prevent the widget from being registered.
	 *
	 * @return void|false
	 */
	public static function onRegister( $widget ) {
		if( !function_exists('curl_init') ) {
			add_action('admin_notices', array($widget,'admin_notices'));
			return false;
		}
	}
	
	// INSTANCE MEMBERS
	
	// CONSTRUCTOR
	public function __construct( $name = '', $wOpts = array(), $cOpts = array() )
	{
		// Set Name
		if( empty( $name ) ) $name = __('Twitter');
		// Set Options
		$wOpts = wp_parse_args( $wOpts, array(
			'description' => __( "Use this widget to retrieve statuses from a specified twitter account. Requires CURL library." )
		) );
		// Add hook for registering
		add_action( xf_wp_APluggable::joinShortName( 'onRegister', __CLASS__ ), array(__CLASS__, 'onRegister') );
		// parent constructor
		parent::__construct( $name, $wOpts, $cOpts );
	}
	
	/**
	 * WordPress Hook - admin_notices
	 */
	public function admin_notices() { ?>
		<div class="error fade">
			<p>Failed registering widget - <strong><?php echo $this->name; ?></strong></p>
			<p>This widget requires the PHP CURL library.</p>
		</div> 
	<?php }
	
	/**
	 * @see wpew_IWidget::beforeOutput()
	 */
	public function beforeOutput() {
		// call parent
		parent::beforeOutput();
		// Add the data to the view params added by the parent class, this way you can access the data extracted in the view!
		if( !empty($this->settings['username']) && !empty($this->settings['password']) ) {
			$twitter = new xf_webservices_Twitter($this->settings['username'], $this->settings['password']);
			$this->settings['view_params']['twitter'] = $twitter;
			$this->settings['view_params']['tweets'] = $twitter->getUserTimeline( $this->settings['limit'] );
		}
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
		} else if( is_object($this->settings['view_params']['twitter']) ) {
			echo $this->settings['view_params']['twitter']->username . ' has no tweets!';
		} else {
			echo 'Cannot access Twitter!';
		}
		// END DEFAULT
	}
}
?>