<?php
/**
 * This file defines wpew_admin_ImportPage, a controller class a plugin admin page.
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
 * Use this controller to import data exported from this plugin. In case you are wandering, 
 * yes it also imports any data that can simply be parsed into valid WordPress options 
 * based on the specified format. A successful import will overwrite all the current data, 
 * if you do not wish to lose it please make a backup by downloading an export.
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_ImportPage extends xf_wp_AAdminController {
	
	/**
	 * @see xf_wp_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return xf_patterns_ASingleton::getSingleton(__CLASS__);
	}
	
	/**
	 * @var string $title This controller's title
	 */
	public $title = "Import";
	
	/**
	 * @ignore
	 * Used internally
	 */
	private $_format;
	/**
	 * @ignore
	 * Used internally
	 */
	private $_autoRun = ' checked="checked"';
	/**
	 * @ignore
	 * Used internally
	 */
	private $_importedData = '';
	/**
	 * @ignore
	 * Used internally
	 */
	private $_checksum = '';
	/**
	 * @ignore
	 * Used internally
	 */
	private $_exporter;
	
	/**
	 * Actually imports the decoded data into the database.
	 *
	 * @param string $data
	 * @return false|array Either an array of the saved options or false on fail
	 */
	public function importData( $decoded ) {
		if( is_array($decoded) && count($decoded) ) {
			foreach( $decoded as $name => $value ) {
				update_option( $name, $value );
				$imported[] = $name;
			}
			return $imported;
		} else {
			return false;
		}
	}
	
	/**
	 * Runs an md5 checksum on the current data against the provided checksum.
	 * It uses an object of the export controller to retrieve the current md5 checksum.
	 *
	 * @param string $checksum
	 * @return bool
	 */
	public function md5Checksum( $checksum ) {
		$md5 = $this->_exporter->formatData( $this->_exporter->onExport(true), 'md5' );
		return ( $checksum === $md5 );
	}
	
	/**
	 * Function called before any rendering occurs within the WordPress admin
	 *
	 * return void
	 */
	public function onBeforeRender() {
		// Set the exporter member to grab data set there
		$this->_exporter =& wpew_admin_ExportPage::getInstance();
		// Grav the first format available as the default
		$this->_format = key($this->_exporter->formats);
		// Grab any errors that may have been added from not meeting format requirements
		$this->noticeErrors .= $this->_exporter->noticeErrors;
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
		$this->uploadForm();
		$this->importForm();
		$this->checksumForm();
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
	 * @return void
	 */
	public function onImport() {
		$doImport = false;
		// Which button was submitted?
		if( isset($this->submitted['submit-upload']) ) {
			$this->_autoRun = ( isset($this->submitted['auto-run']) ) ? $this->_autoRun : '';
			if( !empty($_FILES['upload']['tmp_name']) ) {
				if( is_readable($_FILES['upload']['tmp_name']) ) {
					$data = trim(file_get_contents($_FILES['upload']['tmp_name'], FILE_TEXT ));
					if( is_writeable(dirname($_FILES['upload']['tmp_name'])) ) unlink( $_FILES['upload']['tmp_name'] );
					$format = array_pop( explode( '.', trim($_FILES['upload']['name']) ) );
					$this->noticeUpdates = '<p>Uploaded file <strong>'.$_FILES['upload']['name'].'</strong></p>';
					$doImport = !empty($this->_autoRun);
				} else {
					$this->noticeErrors = '<p><strong>Upload failed!</strong> Uploaded file is not readable by the webserver.</p>';
				}
			} else {
				$this->noticeErrors = '<p><strong>Upload failed!</strong> No file provided.</p>';
			}
		} else if( isset($this->submitted['submit-import']) ) {
			$format = $this->submitted['format'];
			if( !empty($this->submitted['data']) ) {
				$data = stripslashes(trim($this->submitted['data']));
				$doImport = array_key_exists( $format, $this->_exporter->formats );
			} else {
				$this->noticeErrors = '<p><strong>Import failed!</strong> No data provided.</p>';
			}
		}
		// Is there data?
		if( !empty($data) && !empty($format) ) {
			$this->_format = $format;
			// Are we parsing and importing?
			if( $doImport === true ) {
				$this->noticeUpdates .= '<p>Parsing data provided as <strong>'.$this->_exporter->formats[$format].'</strong>...</p>';
				switch( $format ) {
					case 'json' :
						$decoded = json_decode( $data, true );
					break;
					case 'xml' :
						$arr = xf_utils_DOMDocument::xml2Array( $data );
						$decoded = (isset($arr['#document']['options'])) ? $arr['#document']['options'] : false;
					break;
					case 'dat' :
						$decoded = unserialize( base64_decode($data) );
					break;
					case 'php' :
						$debug = xf_errors_Error::getDebug();
						xf_errors_Error::setDebug(false);
						$decoded = eval( $data );
						xf_errors_Error::setDebug($debug);
					break;
					case 'checksum' :
						$this->_format = key($this->_exporter->formats);
						$this->_checksum = $data;
						$this->onChecksum();
						// The checksum stops this function here
						return;
					break;
					default :
						$this->noticeErrors .= '<p><strong>Parsing failed!</strong> Invalid format.</p>';
					break;
				}
				if( is_array($decoded) && count($decoded) ) {
					$this->noticeUpdates .= '<p><strong>Starting import...</strong></p>';
					// Loop though decoded options
					$counter = 1;
					foreach( $decoded as $name => $value ) {
						update_option( $name, $value );
						$this->noticeUpdates .= '<p>'.$counter.'. Saved option - '.$name.'</p>';
						$counter++;
					}
					$this->noticeUpdates .= '<p><strong>Import completed.</strong></p>';
				} else {
					$this->noticeErrors .= '<p><strong>Import failed!</strong> Data could not be parsed.</p>';
				}
			}
			if( $format == 'checksum' ) {
				$this->_checksum = $data;
			} else {
				$this->_importedData =& $data;
			}
		}
		$this->header();
		$this->uploadForm();
		$this->importForm();
		$this->checksumForm();
		$this->footer();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onChecksum() {
		if( isset($this->submitted['checksum']) ) $this->_checksum = $this->submitted['checksum'];
		if( $this->md5Checksum( $this->_checksum ) ) {
			$this->noticeUpdates .= '<p><strong>Checksum passed.</strong></p>';
		} else {
			$this->noticeErrors .= '<p><strong>Checksum failed.</strong></p>';
		}
		$this->header();
		$this->uploadForm();
		$this->importForm();
		$this->checksumForm();
		$this->footer();
	}
	
	/**
	 * Used internally to render the uploadForm
	 *
	 * @return void
	 */
	public function uploadForm() { ?>
		<tr valign="top">
			<th scope="row"><h3><label for="upload">Upload File</label></h3></th>
			<td><form name="uploadForm" enctype="multipart/form-data" method="post" action="<?php echo $this->controllerURI; ?>">
				<?php $this->doStateField( 'onImport' ); ?>
				<p><input type="file" id="upload"  name="upload"> <label><input type="checkbox" name="<?php echo $this->getFieldName('auto-run'); ?>"<?php echo $this->_autoRun; ?>> Automatically Run the Import or Checksum</label></p>
				<p class="description">For the process of uploading, the parsing format is chosen automatically by the file's extension.<br />
				All supported file types may be downloaded from the export page: <strong><?php echo implode( ', ', array_keys($this->_exporter->formats) ); ?></strong></p>
				<p><input type="submit" name="<?php echo $this->getFieldName('submit-upload'); ?>" class="button-primary" value="Upload" /></p>
			</form></td>
		</tr>
	<?php }
	
	/**
	 * Used internally to render the importForm
	 *
	 * @return void
	 */
	public function importForm() { ?>
		<tr valign="top">
			<th scope="row"><h3><label for="<?php echo $this->getFieldID('data'); ?>">Import Data</label></h3></th>
			<td><form name="importForm" method="post" action="<?php echo $this->controllerURI; ?>">
				<?php $this->doStateField( 'onImport' ); ?>
				<p><?php xf_display_Renderables::buildInputList( $this->getFieldID('format'), $this->getFieldName('format'), $this->_exporter->formats, array(
					'checked' => $this->_format,
					'afterInput' => ' &nbsp; ',
					'beforeLabel' => ' <small>',
					'afterLabel' => '</small>',
					'type' => 'radio'
				)); ?></p>
				<p class="description">For anything entered manually, please make sure the the appropriate parsing format is specified.</p>
				<p><label>Paste data here:<textarea class="widefat" rows="10" id="<?php echo $this->getFieldID('data'); ?>" name="<?php echo $this->getFieldName('data'); ?>"><?php echo esc_attr($this->_importedData); ?></textarea></label></p>
				<p><input type="submit" name="<?php echo $this->getFieldName('submit-import'); ?>" class="button-primary" value="Run Import" /></p>
			</form></td>
		</tr>
	<?php }
	
	/**
	 * Used internally to render the checksumForm
	 *
	 * @return void
	 */
	public function checksumForm() { ?>
		<tr valign="top">
			<th scope="row"><h3><label for="<?php echo $this->getFieldID('checksum'); ?>">MD5 Checksum</label></h3></th>
			<td><form name="checksumForm" method="post" action="<?php echo $this->controllerURI; ?>">
				<?php $this->doStateField( 'onChecksum' ); ?>
				<p><input type="text" size="32" id="<?php echo $this->getFieldID('checksum'); ?>" name="<?php echo $this->getFieldName('checksum'); ?>" value="<?php echo esc_attr( $this->_checksum );
				?>"> <input type="submit" name="<?php echo $this->getFieldName('submit-checksum'); ?>" class="button-primary" value="Run Checksum" /></p>
				<p class="description">You may run an <a href="http://en.wikipedia.org/wiki/MD5" target="wpew_window">MD5</a> <a href="http://en.wikipedia.org/wiki/Checksum" target="wpew_window">Checksum</a> by pasting an exported checksum in the field provided.</p>
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
		<p class="description">Use this page to import data <a href="admin.php?page=extensible-widgets/export" class="wpew-navigation">exported</a> from this plugin. Just in case you were wandering, <strong>yes</strong> it also imports any anything that can simply be parsed into valid WordPress options based on the specified format. A successful import will overwrite all the current data, if you do not wish to lose it please make a backup by downloading an export.</p>
		<h3><span class="red">Beware!</span> This page does not...</h3>
		<ol>
			<li><p class="description">Merge what is being imported intelligently with what is saved currently - <strong>Data is overwritten</strong></p></li>
			<li><p class="description">Fix any formatting errors within exported data that may have been corrupted externally - <strong>Parsing failure stops the import</strong></p></li>
			<li><p class="description">Filter out useless data originating from deactivated plugins that were activated at the time of the export - <strong>This means widgets</strong></p></li>
		</ol>
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