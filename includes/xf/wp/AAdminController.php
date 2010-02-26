<?php
/**
 * This file defines xf_wp_AAdminController, an abstract 
 * controller for WordPress admin pages.
 * 
 * PHP version 5
 * 
 * @package    xf
 * @subpackage wp
 * @author     Jim Isaacs <jimpisaacs@gmail.com>
 * @copyright  2009-2010 Jim Isaacs
 * @link       http://jidd.jimisaacs.com
 */

require_once('IAdminController.php');
require_once('AExtension.php');

/**
 * This is an abstract class and is meant to be extended
 * This class is a controller representing WordPress admin pages.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage wp
 */
abstract class xf_wp_AAdminController extends xf_wp_AExtension implements xf_wp_IAdminController {
	
	// CONSTANTS
	
	const DEFAULT_STATE = 'index';
	
	// PRIVATE MEMBERS
	
	/**
	 * @ignore
	 */
	protected $_name;
	
	/**
	 * @ignore
	 */
	protected $_route;
	
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
	 * @var bool $isDefault The flag that tells a parent controller if this controller is the default
	 */
	public $isDefault = false;
	/**
	 * @var bool $isAsync The flag that tells a parent controller if this controller was requested asyncronously
	 */
	public $isAsync = false;
	
	// PUBLIC MEMBERS (NOT MEANT TO EVER REALLY TOUCH)

	/**
	 * @ignore
	 */
	public $parent;
	
	/**
	 * @ignore
	 */
	public $hookname;
	
	// PUBLIC MEMBERS
	
	/**
	 * @var string $title The controller and/or menu title
	 */
	public $title;
	/**
	 * @var string $title Optional menu title, defaults to the controller's title
	 */
	public $menuTitle = false;
	/**
	 * @var string|int $capability The Capability, Role, or Level a user needs to access this controller
	 */
	public $capability = 'activate_plugins';
	/**
	 * @var string $otherNotices A string representing updates from other sources such as plugins, the system, other pages, etc.
	 */
	public $otherNotices = '';
	/**
	 * @var string $noticeUpdates A string representing updates from within the current controller, renders in admin_notices action.
	 */
	public $noticeUpdates = '';
	/**
	 * @var string $noticeErrors A string representing errors from within the current controller, renders in admin_notices action.
	 */
	public $noticeErrors = '';
		
	/**
	 * @see xf_wp_ASingleton::__construct()
	 */
	public function __construct( $unregistrable = __CLASS__ )
	{
		// Parent constructor
		parent::__construct( $unregistrable );
		// set the name
		if( empty($this->_name) && empty($this->title) ) {
			$this->_name = $this->shortName;
		} else if( empty($this->_name) ) {
			$this->_name = $this->title;
		}
		$this->_name = sanitize_title_with_dashes( $this->_name );
		if( empty($this->title) ) {
			$this->title = __("Page");
		}
		// the property menuTitle is optional
		if( empty($this->menuTitle) ) $this->menuTitle = $this->title;
		// Add before render hook
		$this->addLocalAction( 'onBeforeRender' );
	}
	
	/**
	 * @see xf_wp_IPluggable::init()
	 */
	public function init() {}
	
	/**
	 * @see xf_wp_IAdminController::setCapabilities()
	 */
	final public function setCapabilities( $cap = null ) {
		if( !empty($cap) ) $this->capability = $cap;
		if( !$this->hasChildren ) return;
		reset($this->_children);
		do {
			$controller = current($this->_children);
			$controller->capability = $this->capability;
		} while( next($this->_children) !== false );
	}

	/**
	 * @see xf_wp_IAdminController::onBeforeRender();
	 */
	public function onBeforeRender() {
		if( has_action('admin_notices') ) {
			// Start buffer
			ob_start();
			do_action('admin_notices');
			$this->otherNotices = ob_get_clean();
			remove_all_actions('admin_notices');
		}
		// START THE BUFFER
		if( $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' || !empty(self::$get['ajax']) ) {
			// Yes, it is asyncronous
			$this->isAsync = true;
			// Start buffer
			ob_start();
			// Render the page (this will be only the controller itself, no WordPress admin)
			$this->render();
			$output = ob_get_clean();
			echo $output;
			// end script
			die(0);
		}
	}
	
	/**
	 * @see xf_wp_IAdminController::render();
	 */
	final public function render() {
		if( !$this->_rendered ) {
			// This was to remove some things and save some memory but more trouble than it is worth
			/*if( $this->isChild ) {
				if( $this->isDefault ) {
					$this->parent->removeDefaultChild(); 
				} else {
					$this->parent->removeChild( $this ); 
				}
			}*/
			$this->_rendered = true;
			$this->addAction('admin_notices');
			if( method_exists( $this, $this->state ) ) $this->addLocalAction( $this->state );
			if( !$this->isAsync ) echo $this->otherNotices;
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
	 * @see xf_wp_IAdminController::hasChildByName();
	 */
	final public function hasChildByName( $name ) {
		if( !$this->hasChildren || !is_string($name) ) return false;
		return array_key_exists( $name, $this->_children );
	}
	
	/**
	 * @see xf_wp_IAdminController::hasChild();
	 */
	final public function hasChild( xf_wp_AAdminController $obj ) {
		if( !$this->hasChildren ) return false;
		return array_key_exists( $obj->name, $this->_children );
	}
	
	/**
	 * @see xf_wp_IAdminController::addChildByName();
	 */
	final public function &addChildByName( $name, xf_wp_AAdminController &$obj ) {
		if( $this->hasChildByName( $name ) ) return false;
		$obj->parent =& $this;
		if( !$this->hasChildren ) {
			$this->_children = array( $name => $obj );
			return $obj;
		}
		return $this->_children[$name] = $obj;
	}
	
	/**
	 * @see xf_wp_IAdminController::addChild();
	 */
	final public function &addChild( xf_wp_AAdminController &$obj ) {
		return $this->addChildByName( $obj->name, $obj );
	}
	
	/**
	 * @see xf_wp_IAdminController::addChildren();
	 */
	final public function addChildren( &$controllers ) {
		if( $this->hasChildren ) {
			if( $this->hasDefaultChild ) $this->_defaultChild = null;
			$this->_children = array_merge( $this->_children, $controllers );
		} else {
			$this->_children = $controllers;
		}
	}
	
	/**
	 * @see xf_wp_IAdminController::removeChildByName();
	 */
	final public function removeChildByName( $name ) {
		if( !$this->hasChildByName( $name ) ) return false;
		$child = $this->_children[$name];
		unset( $this->_children[$name] );
		$child->parent = null;
		return $child;
	}
	
	/**
	 * @see xf_wp_IAdminController::removeChild();
	 */
	final public function removeChild( xf_wp_AAdminController $obj ) {
		return $this->removeChildByName( $obj->name );
	}
	
	/**
	 * @see xf_wp_IAdminController::removeDefaultChild();
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
	 * @return string The formatted field element id for this controller
	 */
	final public function getFieldID( $field ) {
		return self::joinShortName( $this->_name, $field );
	}
	
	/**
	 * Simply an easy way to print the state input field from render() methods.
	 *
	 * @param string $field
	 * @return string The formatted field element name for this controller
	 */
	final public function getFieldName( $field ) {
		return $this->_name.'['.$field.']';
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
	 * @property string $name The name of this controller's URI component
	 */
	final public function get__name() {
		if( $this->isChild && $this->isDefault ) {
			return $this->parent->name;
		}
		return $this->_name;
	}
	
	/**
	 * @property array $route An array of controller names that represents the route to this controller
	 */
	final public function get__route() {
		if( is_array($this->_route) ) return $this->_route;
		if( $this->isChild && $this->isDefault ) return $this->parent->route;
		$this->_route = array();
		$current =& $this;
		do {
			array_unshift( $this->_route, $current->name );
			$current =& $current->parent;
		} while( is_object($current) );
		return $this->_route ;
	}
	
	/**
	 * @property string $routeString The string representation of the this controller's $route property
	 */
	final public function get__routeString() {
		return implode( '/', $this->route );
	}
	
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
		if( isset( self::$post[$this->_name] ) ) $this->_submitted = self::$post[$this->_name];
		return $this->_submitted;
	}
	
	/**
	 * @property string $state
	 */
	final public function get__state() {
		if( !empty(self::$get['state']) ) return self::$get['state']; 
		if( is_array( $this->submitted ) ) {
			if( isset($this->submitted['state']) ) return $this->submitted['state'];
		}
		return self::DEFAULT_STATE;
	}
	final public function set__state( $v ) {
		unset( self::$get['state'] );
		if( empty($v) ) $v = self::DEFAULT_STATE;
		$this->_submitted['state'] = $v;
	}
	
	/**
	 * @property-read string $controllerURI
	 */
	final public function get__controllerURI() {
		return 'admin.php?page=' . $this->routeString;
	}
	
	/**
	 * @property-read bool $isChild
	 */
	final public function get__isChild() {
		if( !is_object( $this->parent ) ) return false;
		return ( $this->parent instanceof xf_wp_AAdminController );
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
	 * @property xf_wp_AAdminController $defaultChild
	 */
	final public function &get__defaultChild() {
		return $this->_defaultChild;
	}
	final public function set__defaultChild( xf_wp_AAdminController &$v ) {
		if( $v instanceof xf_wp_AAdminController && $this->hasChild( $v ) ) {
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