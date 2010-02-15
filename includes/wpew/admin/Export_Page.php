<?php

require_once(dirname(__FILE__).'/../../xf/display/Renderables.php');
require_once(dirname(__FILE__).'/../../xf/wp/AAdminPage.php');
require_once(dirname(__FILE__).'/../Widgets.php');

/**
 * Use this form to export data associated to this plugin. 
 * You may save it as a backup file, or import into a different blog also using this plugin.
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_Export_Page extends xf_wp_AAdminPage {
	
	/**
	 * @var string $title This page's title
	 */
	public $title = "Export";
	
	/**
	 * @ignore
	 * Used internally
	 */
	private $exportedOptions;
	
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
				require_once(dirname(__FILE__).'/../../xf/utils/DOMDocument.php');
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
		session_start();
		if( isset($_SESSION['group_data']) || $this->widgets->backups ) { 
			$this->state = 'onDisabled';
			$this->noticeErrors .= '<p><strong>There was an error trying to access this page.</strong></p>';
			if( !isset($_SESSION['group_data']) ) {
				$this->noticeErrors .= '<p>Currently there is a user editing a widget group. You cannot access this page until that user has completed, or you go to the <a href="widgets.php">Widgets Administration Page</a> to force edit.</p>';
			} else {
				$this->noticeErrors .= '<p>You are currently editing a widget group, you must go to the <a href="widgets.php">Widgets Administration Page</a> to save and exit to the global scope before using this page\'s functionality.</p>';
			}
			// Call parent
			parent::onBeforeRender();
			return;
		}
		if( $this->state == 'downloadState' || isset($this->submitted['force']) ) {
			if( isset($this->submitted['force']) ) {
				$string = $this->formatData( $this->onExport(true), $this->submitted['format'] );
			} else {
				$string = $this->submitted['data'];
			}
			if( empty($string) ) return;
			$filename = 'wpew_export';
			$ext = $this->submitted['format'];
			switch( $ext ) {
				case 'json' :
					// Damn pesky carriage returns...
					$string = str_replace("\r\n", "\n", $string);
					$string = str_replace("\r", "\n", $string);
					// JSON requires new line characters be escaped
				    $string = str_replace("\n", "\\n", $string);
					header('Content-type: application/octet-stream');
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
		$this->parentPage->header();
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
			$this->root->getOptionName('settings'),
			$this->widgets->getOptionName('registration'),
			$this->widgets->getOptionName('widget_option_backups'),
			'sidebars_widgets'
		);
		if( !empty($this->widgets->currentGroups) ) {
			foreach( $this->widgets->currentGroups as $group ) {
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
		$this->exportedOptions =& $options;
		$this->noticeUpdates .= '<p>Exported data as <strong>'.$this->submitted['format'].'</strong>.</p>';
		if( $return ) return $this->exportedOptions;
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
		$this->exportedOptions =& $this->submitted['data'];
		$this->downloadForm();
		$this->footer();
	}
	
	/**
	 * Used internally to render the exportForm
	 *
	 * @return void
	 */
	public function exportForm() { ?>
		<form name="exportForm" method="post" action="<?php echo $this->pageURI; ?>">
			<?php $this->doStateField( 'onExport' ); ?>
			<h3>Export Associated Options</h3>
			<p>Export data as: &nbsp; <?php xf_display_Renderables::buildInputList( $this->getFieldID('format'), $this->getFieldName('format'), array(
				'json' => __('JSON'),
				'xml' => __('XML 1.0'),
				'dat' => __('Serialized & Encoded'),
				'php' => __('Raw PHP'),
				'md5' => __('MD5 Checksum')
			), array(
				'checked' => (isset($this->submitted['format'])) ? $this->submitted['format'] : 'json',
				'afterInput' => ' &nbsp; ',
				'beforeLabel' => ' <small>',
				'afterLabel' => '</small>',
				'type' => 'radio'
			)); ?></p>
			<p><input type="submit" name="Submit" class="button-primary" value="Export" /><?php if($this->state == self::DEFAULT_STATE ) : ?> or <input type="submit" name="<?php echo $this->getFieldName('force'); ?>" class="button-primary" value="Download" /><?php endif; ?></p>
		</form>
	<?php }
	
	/**
	 * Used internally to render the downloadForm
	 *
	 * @return void
	 */
	public function downloadForm() { ?>
		<form name="downloadForm" method="post" action="<?php echo $this->pageURI; ?>">
			<?php $this->doStateField( 'downloadState' ); ?>
			<p><label>Exported Data: <textarea class="widefat" rows="10" name="<?php echo $this->getFieldName('data'); ?>"><?php echo esc_attr( $this->formatData( $this->exportedOptions, $this->submitted['format'] ) ); ?></textarea></label></p>
			<input type="hidden" name="<?php echo $this->getFieldName('format'); ?>" value="<?php echo $this->submitted['format'];?>" />
			<p><input type="submit" name="<?php echo $this->getFieldName('submit'); ?>" class="button-primary" value="Download" /> <span class="description">Download exported data as a backup file.</span></p>
		</form>
	<?php }
	
	/**
	 * Used internally for a common content header
	 *
	 * @return void
	 */
	public function header() { 
		$this->parentPage->header(); ?>
			<p class="description">Use this form to export data associated to this plugin. You may save it as a backup file, or <a href="admin.php?page=wpew_admin_import" class="wpew-navigation">import</a> into a different blog also using this plugin.</p>
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