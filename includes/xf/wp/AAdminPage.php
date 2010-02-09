<?php

require_once('Plugin.php');
require_once('IAdminPage.php');

/**
 * This is an abstract class and is meant to be extended
 * Within any child class the abstract methods should be defined (as well as a constructor).
 *
 * @package xf
 * @subpackage wp
 */
abstract class xf_wp_AAdminPage extends xf_wp_Plugin implements xf_wp_IAdminPage {
	
	// CONSTANTS
	
	const DEFAULT_STATE = 'defaultState';
	
	// PRIVATE MEMBERS
	
	/**
	 * @ignore
	 */
	protected $_children = null;
	/**
	 * @ignore
	 */
	protected $_defaultChild = null;
	/**
	 * @ignore
	 */
	protected $_submitted = null;
	/**
	 * @ignore
	 */
	protected $_rendered = false;
	/**
	 * @var bool $isDefault The flag that tells a parent page if this page is the default
	 */
	public $isDefault = false;
	/**
	 * @var bool $isAsync The flag that tells a parent page if this page was loaded asyncronously
	 */
	public $isAsync = false;
	
	// PUBLIC MEMBERS (NOT MEANT TO EVER REALLY TOUCH)

	/**
	 * @ignore
	 */
	public $parentPage;
	/**
	 * @ignore
	 */
	public $pageName;
	
	// PUBLIC MEMBERS
	
	/**
	 * @var string $title The page and/or menu title
	 */
	public $title = "Untitled";
	/**
	 * @var string $title Optional menu title, defaults to the page's title
	 */
	public $menuTitle = false;
	/**
	 * @var string|int $capability The Capability, Role, or Level a user needs to access this page
	 */
	public $capability = 'activate_plugins';
	/**
	 * @var string $noticeUpdates An string representing updates from within the current page, renders in admin_notices action.
	 */
	public $noticeUpdates = '';
	/**
	 * @var string $noticeErrors An string representing errors from within the current page, renders in admin_notices action.
	 */
	public $noticeErrors = '';
		
	/**
	 * @see xf_wp_IPluggable::init()
	 */
	final public function init()
	{
		// set the shortName
		$name = $this->unJoinShortName( $this->className, 'Page' );
		$this->shortName = $name;
		// the property menuTitle is optional
		if( empty($this->menuTitle) ) $this->menuTitle = $this->title;
		$this->addLocalAction( 'beforeRender' );
	}
	
	/**
	 * @see xf_wp_IAdminPage::setCapabilities()
	 */
	final public function setCapabilities( $cap = null ) {
		if( !empty($cap) ) $this->capability = $cap;
		if( !$this->hasChildren ) return;
		reset($this->_children);
		do {
			$page =& current($this->_children);
			$page->capability = $this->capability;
		} while( next($this->_children) !== false );
	}

	/**
	 * @see xf_wp_IAdminPage::beforeRender();
	 */
	public function beforeRender() {
		// START THE BUFFER
		if( $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' || !empty($_GET['ajax']) ) {
			// Yes, it is asyncronous
			$this->isAsync = true;
			// Start buffer
			ob_start();
			// Render the page (this will be only the page itself, no WordPress admin)
			$this->render();
			$output = ob_get_clean();
			echo $output;
			// end script
			die(0);
		}
	}
	
	/**
	 * @see xf_wp_IAdminPage::render();
	 */
	final public function render() {
		if( !$this->_rendered ) {
			// This was to remove some things and save some memory but more trouble than it is worth
			/*if( $this->isChild ) {
				if( $this->isDefault ) {
					$this->parentPage->removeDefaultChild(); 
				} else {
					$this->parentPage->removeChild( $this ); 
				}
			}*/
			$this->_rendered = true;
			$this->addAction('admin_notices');
			if( method_exists( $this, $this->state ) ) $this->addLocalAction( $this->state );
			$this->doLocalAction( $this->state );
		}
	}
	
	/**
	 * WordPress action callback - admin_notices
	 *
	 * @return void
	 */
	final public function admin_notices() { ?>
		<?php if( !empty( $this->noticeUpdates ) ) : ?>
		<div class="updated fade">
			<?php echo $this->noticeUpdates; ?>
		</div>
		<?php endif ?>
		<?php if( !empty( $this->noticeErrors ) ) : ?>
		<div class="error fade">
			<?php echo $this->noticeErrors; ?>
		</div>
		<?php endif;
	}
	
	/**
	 * @see xf_wp_IAdminPage::hasChildByName();
	 */
	final public function hasChildByName( $shortName ) {
		if( !$this->hasChildren ) return false;
		return array_key_exists( $shortName, $this->_children );
	}
	
	/**
	 * @see xf_wp_IAdminPage::hasChild();
	 */
	final public function hasChild( xf_wp_AAdminPage $obj ) {
		if( !$this->hasChildren ) return false;
		return array_key_exists( $obj->shortName, $this->_children );
	}
	
	/**
	 * @see xf_wp_IAdminPage::addChildByName();
	 */
	public function &addChildByName( $shortName, xf_wp_AAdminPage &$obj ) {
		$obj->parentPage =& $this;
		if( !$this->hasChildren ) {
			$this->_children = array( $shortName => $obj );
			return $obj;
		}
		if( $this->hasChildByName( $shortName ) ) return false;
		return $this->_children[$shortName] = $obj;
	}
	
	/**
	 * @see xf_wp_IAdminPage::addChild();
	 */
	final public function &addChild( xf_wp_AAdminPage &$obj ) {
		return $this->addChildByName( $obj->shortName, $obj );
	}
	
	/**
	 * @see xf_wp_IAdminPage::addChildren();
	 */
	final public function addChildren( &$pages ) {
		if( $this->hasChildren ) {
			if( $this->hasDefaultChild ) $this->_defaultChild = null;
			$this->_children = array_merge( $this->_children, $pages );
		} else {
			$this->_children = $pages;
		}
	}
	
	/**
	 * @see xf_wp_IAdminPage::removeChildByName();
	 */
	final public function removeChildByName( $shortName ) {
		if( $this->hasChildByName( $shortName ) ) {
			$child = $this->_children[$shortName];
			unset( $this->_children[$shortName] );
			return $child;
		}
		return false;
	}
	
	/**
	 * @see xf_wp_IAdminPage::removeChild();
	 */
	final public function removeChild( xf_wp_AAdminPage $obj ) {
		return $this->removeChildByName( $obj->shortName );
	}
	
	/**
	 * @see xf_wp_IAdminPage::removeDefaultChild();
	 */
	final public function removeDefaultChild() {
		if( $this->hasDefaultChild ) {
			$child = $this->removeChild( $this->_defaultChild );
			$child->isDefault = false;
			$this->_defaultChild = null;
			return $child;
		}
		return false;
	}
	
	/**
	 * Simply an easy way to print the state input field from render() methods.
	 *
	 * @param string $field
	 * @return string The formatted field element id for this page
	 */
	final public function getFieldID( $field ) {
		return xf_wp_APluggable::joinShortName( $this->pageName, $field );
	}
	
	/**
	 * Simply an easy way to print the state input field from render() methods.
	 *
	 * @param string $field
	 * @return string The formatted field element name for this page
	 */
	final public function getFieldName( $field ) {
		return $this->pageName.'['.$field.']';
	}
	
	/**
	 * Simply an easy way to print the state input field from render() methods.
	 *
	 * @param string $state The string to use for the field value
	 * @param bool $echo Whether to output or to return the string
	 * @return void|string If the echo param is true it returns the value instead of output
	 */
	final public function doStateField( $state, $echo = true ) {
		$field = '<input type="hidden" name="'.$this->getFieldName('state').'" value="'.$state.'" />';
		if( $echo ) { echo $field; } else { return $field; }
	}
	
	// RESERVED PROPERTIES
	
	/**
	 * @property-read bool $rendered
	 */
	final public function get__rendered() {
		return $this->_rendered;
	}
	
	/**
	 * @property-read array $submitted
	 */
	final public function &get__submitted() {
		if( is_array( $this->_submitted ) ) return $this->_submitted;
		if( isset( $_REQUEST[$this->pageName] ) ) return $this->_submitted = $_REQUEST[$this->pageName];
		return $this->_submitted;
	}
	
	/**
	 * @property string $state
	 */
	final public function get__state() {
		if( !empty($_GET['state']) ) return $_GET['state']; 
		if( is_array( $this->submitted ) ) {
			if( isset($this->submitted['state']) ) return $this->submitted['state'];
		}
		return self::DEFAULT_STATE;
	}
	final public function set__state( $v ) {
		unset( $_GET['state'] );
		if( empty($v) ) $v = self::DEFAULT_STATE;
		$this->_submitted['state'] = $v;
	}
	
	/**
	 * @property-read string $pageURI
	 */
	final public function get__pageURI() {
		return 'admin.php?page=' . $this->pageName;
	}
	
	/**
	 * @property-read bool $isChild
	 */
	final public function get__isChild() {
		if( !isset( $this->parentPage ) ) return false;
		if( !is_object( $this->parentPage ) ) return false;
		return true;
	}
	
	/**
	 * @property-read bool $hasChildren
	 */
	final public function get__hasChildren() {
		if( !is_array( $this->_children ) ) return false;
		return ( count( $this->_children ) > 0 );
	}
	
	/**
	 * @property-read array $children
	 */
	final public function &get__children() {
		if( !$this->hasChildren ) return false;
		return $this->_children;
	}
	
	/**
	 * @property-read bool $hasDefaultChild
	 */
	final public function get__hasDefaultChild() {
		return !empty($this->_defaultChild);
	}
	
	/**
	 * @property-read callback $menuCallback
	 */
	final public function get__menuCallback() {
		return $this->getCallback( 'render' );
	}
	
	/**
	 * @property xf_wp_AAdminPage $defaultChild
	 */
	final public function &get__defaultChild() {
		return $this->_defaultChild;
	}
	final public function set__defaultChild( xf_wp_AAdminPage &$v ) {
		if( $v instanceof xf_wp_AAdminPage && $this->hasChild( $v ) ) {
			if( $this->hasDefaultChild ) {
				$this->_defaultChild->isDefault = false;
			}
			$v->isDefault = true;
			$this->_defaultChild =& $v;
		} else {
			if( $this->hasDefaultChild  ) $this->_defaultChild->isDefault = false;
			$this->_defaultChild = null;
		}
	}
}
?>