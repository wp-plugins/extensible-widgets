<?php
/**
 * This file defines wpew_admin_ExportPage, a controller class a plugin admin page.
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
 * Use this form to export data associated to this plugin. 
 * You may save it as a backup file, or import into a different blog also using this plugin.
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_ExportPage extends xf_wp_AAdminController {
	
	/**
	 * @see xf_wp_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return xf_patterns_ASingleton::getSingleton(__CLASS__);
	}
	
	/**
	 * @var string $title This controller's title
	 */
	public $title = "Export";
	
	/**
	 * @var array $formats The available formats for exporting, keys as file extensions and values as labels
	 */
	public $formats;
	
	/**
	 * @ignore
	 * Used internally
	 */
	private $_exportedOptions;
	
	/**
	 * @see xf_wp_IPluggable::init()
	 */
	public function init() {
		// Set the possible formats
		// JSON seems to be the smallest and most reliable, which is why it is first
		if( is_callable('json_encode') ) {
			$this->formats['json'] = __('JSON');
		} else {
			$this->noticeErrors .= '<p>Your platform did not meet the requirements for the JSON format.</p>';
		}
		$this->formats['dat'] = __('Serialized & Encoded');
		$this->formats['php'] = __('Raw PHP');
		// This is becoming a little unreliable based on the minor version of PHP and which DOMDocument class included, so it is next to last
		if( class_exists('DOMDocument', false) ) {
			$this->formats['xml'] = __('XML 1.0');
		} else {
			$this->noticeErrors .= '<p>Your platform did not meet the requirements for the XML 1.0 format.</p>';
		}
		$this->formats['md5'] = __('MD5 Checksum');
	}
	
	/**
	 * Formats the given data to the specified format
	 *
	 * @param array $options
	 * @param string $format
	 * @return string
	 */
	public function formatData( $data, $format ) {
		switch( $format ) {
			case 'json' :
				return json_encode($data);
			break;
			case 'xml' :
				$data = array( 'options' => $data );
				return xf_utils_DOMDocument::array2Xml( $data );
			break;
			case 'dat' :
				return base64_encode( serialize($data) );
			break;
			case 'php' :
				$str = var_export( $data, true );
				return 'return('.$str.');';
			break;
			case 'md5' :
				return md5( var_export( $data, true ) );
			break;
			default :
				return print_r( $data, true );
			break;
		}
	}
	
	/**
	 * Function called before any rendering occurs within the WordPress admin
	 *
	 * return void
	 */
	public function onBeforeRender() {
		// Check for a session
		session_start();
		if( isset($_SESSION['group_data']) || $this->parent->plugin->widgets->backups ) { 
			$this->state = 'onDisabled';
			$this->noticeErrors .= '<p><strong>There was an error trying to access this page.</strong></p>';
			if( isset($_SESSION['group_data']) ) {
				$this->noticeErrors .= '<p>You are currently editing a widget group, you must go to the <a href="widgets.php">Widgets Administration Page</a> to save and exit to the global scope before using this page\'s functionality.</p>';
			} else {
				$this->noticeErrors .= '<p>Currently there is a user editing a widget group. You cannot access this page until that user has completed, or you go to the <a href="widgets.php">Widgets Administration Page</a> to force edit.</p>';
			}
			// Call parent
			parent::onBeforeRender();
			return;
		}
		// Check for when to force a download
		if( $this->state == 'downloadState' || isset($this->submitted['force']) ) {
			if( isset($this->submitted['force']) ) {
				$string = $this->formatData( $this->onExport(true), $this->submitted['format'] );
			} else {
				$string = $this->submitted['data'];
			}
			if( empty($string) ) return;
			$filename = 'wpew_export-'.date("Y-m-d");
			$ext = $this->submitted['format'];
			switch( $ext ) {
				case 'json' :
					// Damn pesky carriage returns...
					$string = str_replace("\r\n", "\n", $string);
					$string = str_replace("\r", "\n", $string);
					// JSON requires new line characters be escaped
				    $string = str_replace("\n", "\\n", $string);
					header('Content-type: application/json');
				break;
				case 'xml' :
					$string = stripslashes( $string );
					header('Content-type: application/xml');
				break;
				case 'dat' :
					$string = $string;
					header('Content-type: application/octet-stream');
				break;
				case 'php' :
					$string = stripslashes( $string );
					header('Content-type: application/octet-stream');
				break;
				case 'md5' :
					$ext = 'checksum';
					header('Content-type: application/octet-stream');
				break;
				default :
					$ext = 'txt';
					$string = stripslashes( $string );
					header('Content-type: text/plain');
				break;
			}
			header('Content-Disposition: attachment; filename="'.$filename.'.'.$ext.'"');
			echo $string;
			die(0);
		}
		// Call parent
		parent::onBeforeRender();
	}
	
	// PAGE STATES
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function index() {
		$this->header();
		$this->exportForm();
		$this->footer();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onDisabled() {
		$this->parent->header();
		$this->footer();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @param bool $return If true this returns the exported options
	 * @return void
	 */
	public function &onExport( $return = false ) {
		// Build the options array (names of the options to retrieve)
		$names = array(
			$this->parent->plugin->getOptionName('settings'),
			$this->parent->plugin->widgets->getOptionName('registration'),
			$this->parent->plugin->widgets->getOptionName('widget_option_backups'),
			'sidebars_widgets'
		);
		if( !empty($this->parent->plugin->widgets->currentGroups) ) {
			foreach( $this->parent->plugin->widgets->currentGroups as $group ) {
				if( !is_array($group) || count($group) == 0 ) continue;
				foreach( $group as $widgetID ) {
					if( !$parsed = wpew_Widgets::parseWidgetID( $widgetID ) ) continue;
					$names[] = $parsed['option_name'];
				}					
			}
		}
		// Actually get the options
		$options = array();
		foreach( $names as $name ) {
			if( $value = get_option( $name ) ) {
				$options[$name] = $value;
			}					
		}
		$this->_exportedOptions =& $options;
		$this->noticeUpdates .= '<p><strong>'.$this->formats[$this->submitted['format']].'</strong> data exported successfully.</p>';
		if( $return ) return $this->_exportedOptions;
		$this->header();
		$this->exportForm();
		$this->downloadForm();
		$this->footer();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function downloadState() {
		// If the script makes it to this function, then the download never happened
		$this->noticeErrors .= '<p>Download failed.</p>';
		$this->header();
		$this->exportForm();
		$this->_exportedOptions =& $this->submitted['data'];
		$this->downloadForm();
		$this->footer();
	}
	
	/**
	 * Used internally to render the exportForm
	 *
	 * @return void
	 */
	public function exportForm() { ?>
		<tr valign="top">
			<th scope="row"><h3>Export <?php if($this->state == self::DEFAULT_STATE ) : ?> or Download<?php endif; ?></h3></th>
			<td><form name="exportForm" method="post" action="<?php echo $this->controllerURI; ?>">
				<?php $this->doStateField( 'onExport' ); ?>
				<p><?php xf_display_Renderables::buildInputList( $this->getFieldID('format'), $this->getFieldName('format'), $this->formats, array(
					'checked' => (isset($this->submitted['format'])) ? $this->submitted['format'] : key($this->formats),
					'afterInput' => ' &nbsp; ',
					'beforeLabel' => ' <small>',
					'afterLabel' => '</small>',
					'type' => 'radio'
				)); ?></p>
				<p><input type="submit" name="Submit" class="button-primary" value="Export" /><?php if($this->state == self::DEFAULT_STATE ) : ?> or <input type="submit" name="<?php echo $this->getFieldName('force'); ?>" class="button-primary" value="Download" /><?php endif; ?></p>
			</form></td>
		</tr>
	<?php }
	
	/**
	 * Used internally to render the downloadForm
	 *
	 * @return void
	 */
	public function downloadForm() { ?>
		<tr valign="top">
			<th scope="row"><h3><label for="<?php echo $this->getFieldID('data'); ?>">Exported Data</label></h3></th>
			<td><form name="downloadForm" method="post" action="<?php echo $this->controllerURI; ?>">
				<?php $this->doStateField( 'downloadState' ); ?>
				<p><textarea class="widefat" rows="10" id="<?php echo $this->getFieldID('data'); ?>" name="<?php echo $this->getFieldName('data'); ?>"><?php echo esc_attr( $this->formatData( $this->_exportedOptions, $this->submitted['format'] ) ); ?></textarea></p>
				<input type="hidden" name="<?php echo $this->getFieldName('format'); ?>" value="<?php echo $this->submitted['format'];?>" />
				<p class="description">This will download previously exported <strong><?php echo $this->formats[$this->submitted['format']]; ?></strong> data as a backup file in <strong><?php echo $this->submitted['format']; ?></strong> format.</p>
				<p><input type="submit" name="<?php echo $this->getFieldName('submit'); ?>" class="button-primary" value="Download" /></p>
			</form></td>
		</tr>
	<?php }
	
	/**
	 * Used internally for a common content header
	 *
	 * @return void
	 */
	public function header() { 
		$this->parent->header(); ?>
			<p class="description">Use this page to export all data <strong>associated to this plugin</strong>, which includes all widgets currently saved within all of your registered widget areas, yes even inactive widgets. You may copy this data or save it as a backup file via download. This also allows you the ability to <a href="admin.php?page=extensible-widgets/import" class="wpew-navigation">import</a> this data into a different WordPress installation also using this plugin.</p>
			<table class="form-table">
	<?php }
	
	/**
	 * Used internally for a common content footer
	 *
	 * @return void
	 */
	public function footer() { ?>
		</table><br />
		<?php $this->parent->footer();
	}
}
?>