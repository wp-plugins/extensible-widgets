<?php

require_once(dirname(__FILE__).'/../../xf/display/Renderables.php');
require_once(dirname(__FILE__).'/../../xf/wp/AAdminPage.php');
require_once(dirname(__FILE__).'/../Widgets.php');

/**
 * Use this page to import data exported from this plugin. In case you are wandering, 
 * yes it also imports any data that can simply be parsed into valid WordPress options 
 * based on the specified format. A successful import will overwrite all the current data, 
 * if you do not wish to lose it please make a backup by downloading an export.
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_Import_Page extends xf_wp_AAdminPage {
	
	/**
	 * @var string $title This page's title
	 */
	public $title = "Import";
	
	/**
	 * @ignore
	 * Used internally
	 */
	private $format = 'json';
	/**
	 * @ignore
	 * Used internally
	 */
	private $autoRun = ' checked="checked"';
	/**
	 * @ignore
	 * Used internally
	 */
	private $importedData = '';
	/**
	 * @ignore
	 * Used internally
	 */
	private $checksum = '';
	
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
	 * It uses an object of the export page to retrieve the current md5 checksum.
	 *
	 * @param string $checksum
	 * @return bool
	 */
	public function md5Checksum( $checksum ) {
		require_once('Export_Page.php');
		$exporter = new wpew_admin_Export_Page();
		$md5 = $exporter->formatData( $exporter->export(true), 'md5' );
		return ( $checksum === $md5 );
	}
	
	/**
	 * Function called before any rendering occurs within the WordPress admin
	 *
	 * return void
	 */
	public function onBeforeRender() {
		session_start();
		// Call parent
		parent::onBeforeRender();
	}
	
	// PAGE STATES
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function defaultState() {
		if( isset($_SESSION['group_data']) || $this->widgets->backups  ) : 
			$this->parentPage->header(); ?>
			<div class="error"><p><strong>There was an error when trying to access this page.</strong></p>
			<?php if( !isset($_SESSION['group_data']) ) : ?>
			<p>Currently there is a user editing a widget group. You cannot access this page until that user has completed, or you go to the <a href="widgets.php">Widgets Administration Page</a> to force edit.</p>
			<?php else : ?>
			<p>You are currently editing a widget group, you must go to the <a href="widgets.php">Widgets Administration Page</a> to save and exit the the global scope before using this page's functionality.</p>
		</div>
			<?php endif; ?></div>
		<?php else :
			$this->header();
			$this->uploadForm();
			$this->importForm();
			$this->checksumForm();
		endif;
		$this->footer();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function importState() {
		$doImport = false;
		// Which button was submitted?
		if( isset($this->submitted['submit-upload']) ) {
			$this->autoRun = ( isset($this->submitted['auto-run']) ) ? $this->autoRun : '';
			if( !empty($_FILES['upload']['tmp_name']) ) {
				if( is_readable($_FILES['upload']['tmp_name']) ) {
					$data = trim(file_get_contents($_FILES['upload']['tmp_name'], FILE_TEXT ));
					if( is_writeable(dirname($_FILES['upload']['tmp_name'])) ) unlink( $_FILES['upload']['tmp_name'] );
					$format = array_pop( explode( '.', trim($_FILES['upload']['name']) ) );
					$this->noticeUpdates = '<p>Uploaded file <strong>'.$_FILES['upload']['name'].'</strong></p>';
					$doImport = !empty($this->autoRun);
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
				$doImport = true;
			} else {
				$this->noticeErrors = '<p><strong>Import failed!</strong> No data provided.</p>';
			}
		}
		// Is there data?
		if( !empty($data) && !empty($format) ) {
			$this->format = $format;
			// Are we parsing and importing?
			if( $doImport === true ) {
				$this->noticeUpdates .= '<p>Parsing <strong>'.$format.'</strong> data...</p>';
				switch( $format ) {
					case 'json' :
						$decoded = json_decode( $data, true );
					break;
					case 'xml' :
						require_once(dirname(__FILE__).'/../../xf/utils/DOMDocument.php');
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
						$this->format = 'json';
						$this->checksum = $data;
						$this->checksumState();
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
				$this->checksum = $data;
			} else {
				$this->importedData =& $data;
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
	public function checksumState() {
		if( isset($this->submitted['checksum']) ) $this->checksum = $this->submitted['checksum'];
		if( $this->md5Checksum( $this->checksum ) ) {
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
		<h3>Upload File</h3>
		<form name="uploadForm" enctype="multipart/form-data" method="post" action="<?php echo $this->pageURI; ?>">
			<?php $this->doStateField( 'importState' ); ?>
			<p class="description">In the process of uploading files, the format is chosen automatically by the file's extension.<br />
			Supported types for importing or to run a checksum are json, xml, dat, php, and checksum.<br />
			All of these types can be downloaded from the export page.</p>
			<p><input type="file" name="upload"> <label><input type="checkbox" name="<?php echo $this->getFieldName('auto-run'); ?>"<?php echo $this->autoRun; ?>> Automatically Run Import or Checksum</span></label></p>
			<p><input type="submit" name="<?php echo $this->getFieldName('submit-upload'); ?>" class="button-primary" value="Upload" /></p>
		</form>
	<?php }
	
	/**
	 * Used internally to render the importForm
	 *
	 * @return void
	 */
	public function importForm() { ?>
		<h3>Import Data</h3>
		<form name="importForm" method="post" action="<?php echo $this->pageURI; ?>">
			<?php $this->doStateField( 'importState' ); ?>
			<p class="description">For data you wish to enter manually, you need to specify the correct parsing format.</p>
			<p><?php xf_display_Renderables::buildInputList( $this->getFieldID('format'), $this->getFieldName('format'), array(
				'json' => __('JSON'),
				'xml' => __('XML 1.0'),
				'dat' => __('Serialized & Encoded'),
				'php' => __('Raw PHP')
			), array(
				'checked' => $this->format,
				'afterInput' => ' &nbsp; ',
				'beforeLabel' => ' <small>',
				'afterLabel' => '</small>',
				'type' => 'radio'
			)); ?></p>
			<p><label>Paste data here:<textarea class="widefat" rows="10" name="<?php echo $this->getFieldName('data'); ?>"><?php echo esc_attr($this->importedData); ?></textarea></label></p>
			<p><input type="submit" name="<?php echo $this->getFieldName('submit-import'); ?>" class="button-primary" value="Import" /></p>
		</form>
	<?php }
	
	/**
	 * Used internally to render the checksumForm
	 *
	 * @return void
	 */
	public function checksumForm() { ?>
		<h3>MD5 Checksum</h3>
		<p class="description">You may run an <a href="http://en.wikipedia.org/wiki/MD5" target="wpew_window">MD5</a> <a href="http://en.wikipedia.org/wiki/Checksum" target="wpew_window">Checksum</a> by pasting your exported checksum here.</p>
		<form name="checksumForm" method="post" action="<?php echo $this->pageURI; ?>">
			<?php $this->doStateField( 'checksumState' ); ?>
			<p><label>Checksum: <input type="text" size="32" name="<?php echo $this->getFieldName('checksum'); ?>" value="<?php echo esc_attr( $this->checksum );
			?>"></label> <input type="submit" name="<?php echo $this->getFieldName('submit-checksum'); ?>" class="button-primary" value="Run" /></p>
		</form>
	<?php }
	
	/**
	 * Used internally for a common content header
	 *
	 * @return void
	 */
	public function header() {
		$this->parentPage->header(); ?>
			<p class="description">Use this page to import data <a href="admin.php?page=wpew_admin_export">exported</a> from this plugin. In case you are wandering, yes it also imports any data that can simply be parsed into valid WordPress options based on the specified format.</p>
			<p class="description">A successful import will overwrite all the current data, if you do not wish to lose it please make a backup by downloading an export.</p>
			<h3>This page does not...</h3>
			<ol>
				<li>Filter useless data (including widgets) registered from other active plugins at the time of the export, but not active currently</li>
				<li>Fix any errors within exported data that may have been corrupted externally. If parsing fails it simply stops importing</li>
				<li>Intelligently merge imported data with current data, <strong>it is only overwritten</strong></li>
			</ol>
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