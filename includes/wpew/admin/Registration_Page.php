<?php

require_once(dirname(__FILE__).'/../../xf/Object.php');
require_once(dirname(__FILE__).'/../../xf/display/Renderables.php');
require_once(dirname(__FILE__).'/../../xf/wp/AAdminPage.php');
require_once(dirname(__FILE__).'/../../xf/wp/APluggable.php');

/**
 * wpew_admin_Registration_Page
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_Registration_Page extends xf_wp_AAdminPage {
	
	/**
	 * @var string $title This page's title
	 */
	public $title = "Registration";
	
	/**
	 * @ignore
	 * Used internally
	 */
	private $allClasses = array();
	/**
	 * @ignore
	 * Used internally
	 */
	private $registeredClasses = array();
	/**
	 * @ignore
	 * Used internally
	 */
	private $unregisteredClasses = array();
	/**
	 * @ignore
	 * Used internally
	 */
	private $classes;
	
	/**
	 * @ignore
	 * Used internally
	 */
	private $_defaultRegSettings = array(
		'display' => 'tabular',
		'renderFlags' => null
	);
	
	/**
	 * Function called before any rendering occurs within the WordPress admin
	 *
	 * return void
	 */
	public function onBeforeRender() {
		// get_option
		$registration = ( $this->widgets->registration ) ? $this->widgets->registration : array();
		// There was either a bulk action or a single action
		if( isset($this->submitted['checked']) || !empty($_GET['widget']) ) {
			// find and set the current action
			$action = false;
			if( !empty($this->submitted['action']) ) {
				$action = $this->submitted['action'];
			} else if( !empty($this->submitted['action2']) ) {
				$action = $this->submitted['action2'];
			} else if( !empty($_GET['action']) ) {
				$action = $_GET['action'];
			}
			// Was there an action in the request
			if( $action ) {
				// Get the classes we are applying the action to
				$classes = ( isset($this->submitted['checked']) ) ? $this->submitted['checked'] : array();
				if( !empty($_GET['widget']) ) $classes[] = $_GET['widget'];
				// Loop through the classes
				foreach( $classes as $class ) {
					$this->widgets->importWidget( $class, false );
					$widget = new $class();
					switch( $action ) {
						case 'register-selected' :
							if( !isset($registration[$class]) ) {
								if( method_exists( $widget, 'onRegister' ) ) if( $widget->onRegister() === false ) continue; 
								$registration[$class] = true;
								$this->noticeUpdates .= '<p>Registered <strong>'.$widget->name.'</strong></p>';
							}
						break;
						case 'unregister-selected' :
							if( isset($registration[$class]) ) {
								if( method_exists( $widget, 'onUnregister' ) ) if( $widget->onUnregister() === false ) continue; 
								unset( $registration[$class] );
								$this->noticeUpdates .= '<p>Unregistered <strong>'.$widget->name.'</strong></p>';
							}
						break;
					}
					ksort($registration);
					// update_option
					$this->widgets->registration = $registration;
					// free memory
					unset( $widget );
				}
			}
		}
		// Look and find all the widget class files of this plugin
		$files = $this->loader->locate( $this->widgets->dirWidgets, false, true, false);
		foreach( $files as $file ) {
			$class = 'wpew_widgets_'.array_shift(explode('.',basename($file)));
			// the unregistered classes might not have been loaded yet, so load them now
			if( !class_exists($class, false) ) {
				require_once( $file );
			}
			// check if it is a widget with a constructor and not the abstract base widget
			if( method_exists( $class, '__construct' ) && $class != 'wpew_AWidget' ) {
				if( !isset($registration[$class]) ) {
					$this->unregisteredClasses[] = $class;
				}
			}
		}
		sort($this->unregisteredClasses);
		$this->registeredClasses = array_keys($registration);
		$this->allClasses = array_merge( $this->unregisteredClasses, $this->registeredClasses );
		// Call parent
		parent::onBeforeRender();
	}
	
	// STATES
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function index() {
		$this->classes =& $this->allClasses;
		$this->header();
		$this->registrationTable();
		$this->footer();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onRegistered() {
		$this->classes =& $this->registeredClasses;
		$this->header();
		$this->registrationTable();
		$this->footer();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onUnregistered() {
		$this->classes =& $this->unregisteredClasses;
		$this->header();
		$this->registrationTable();
		$this->footer();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onEdit() {
		if( !empty($this->submitted['widget']) ) {
			$class = $this->submitted['widget'];
		} else {
			$class = $_REQUEST['widget'];
		}
		if( !in_array( $class, $this->allClasses, true ) ) {
			$this->noticeErrors = '<p>Sorry, the specified widget does not exist.</p>';
			$this->state = null;
			$this->index();
			return;
		} else if( in_array( $class, $this->unregisteredClasses, true ) ) {
			$this->noticeErrors = '<p>Sorry, only registered widgets may be edited.</p>';
			$this->state = null;
			$this->index();
			return;
		}
		$widget = new $class(); 
		if( !empty($this->submitted) ) {
			$settings = $this->_defaultRegSettings;
			if( !empty($this->submitted['display']) ) {
				$settings['display'] = $this->submitted['display'];
			}
			if( !empty($this->submitted['renderFlags']) ) {
				$flags = $this->submitted['renderFlags'];
			} else {
				$flags = array();
			}
			$parentClasses = $widget->parentClasses;
			foreach( array_reverse($parentClasses) as $parentClass ) {
				$settings['renderFlags'][] = ( array_key_exists($parentClass, $flags ) ) ? 1 : 0;
			}
			$registration = $this->widgets->registration;
			$registration[$class] = $settings;
			$this->widgets->registration = $registration;
			$this->noticeUpdates = '<p>Widget - <strong>'.$widget->name.'</strong> updated.</p>';
		}
		if( isset( $this->submitted['edit-close'] ) ) {
			$this->state = null;
			$this->index();
			return;
		}
		if( !$this->isAsync ) {
			$this->noticeUpdates .= '<p><a href="'.$this->pageURI.'">Go back</a> to registration.</p>';
			$this->parentPage->header( ' Editing &raquo; ' . $widget->name ); ?>
			<?php unset( $widget );
			$this->editForm( $class );
			$this->footer();
		} else if( empty($this->submitted['widget']) ) {
			unset( $widget );
			$this->editForm( $_GET['widget'] );
		} else {
			$this->onControlHierarchy( $widget );
		}
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onControlHierarchy( $widget = null ) {
		if( empty($widget) ) {
			$class = ( empty($this->submitted['widget']) ) ? $_GET['widget'] : $this->submitted['widget'];
			$widget = new $class();
		} else {
			$class = get_class($widget);
		} ?>
		<p class="description">
		<?php if( in_array( $class, $this->unregisteredClasses, true ) ) : ?>
			Register this widget to allow it to be added within widgets administration.
		<?php else :
			$registration = $this->widgets->registration[get_class($widget)];
			$display = ( empty($registration['display']) || $registration['display'] == 'tabular' ) ? 'With Tabs' : 'Without Tabs'; ?>
			<strong><?php echo $display; ?>:</strong> 
			<?php if( is_array($registration['renderFlags']) ) {
				$renderFlags = $registration['renderFlags'];
			}
			$parentClasses = $widget->parentClasses;
			$parentNames = array();
			foreach( array_reverse($parentClasses) as $parentClass ) {
				$instance = new $parentClass();
				$css = 'renderflag';
				if( is_array($renderFlags) ){
					if( !$flag = array_shift($renderFlags) ) {
						$css .= ' strikeout';
					}
				}
				$parentNames[] = '<span class="'.$css.'">'.$instance->name.'</span>';
				unset( $instance );
			}
			echo implode(' &raquo; ', $parentNames ); ?></p>
		<?php endif; ?>
	<?php }
	
	/**
	 * Used internally to render the registrationTable
	 *
	 * @return void
	 */
	public function registrationTable() { ?>
		
		<div class="async"><div class="content"></div></div>
		
		<form method="post" action="<?php echo $this->pageURI; ?>">
			<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo wp_create_nonce('load_template'); ?>" />
			<input type="hidden" name="_wp_http_referer" value="<?php echo $this->pageURI; ?>" /><input type="hidden" name="plugin_status" value="all" />
			<input type="hidden" name="paged" value="1" />
			<?php $this->doStateField( $this->state ); ?>
			
			<ul class="subsubsub">
			<li><a <?php if( $this->state == self::DEFAULT_STATE ) echo 'class="current" '; ?>href="<?php echo $this->pageURI; ?>">All <span class="count">(<?php echo count($this->allClasses); ?>)</span></a></li>
			<?php if( count($this->registeredClasses) > 0 ) : ?><li> | <a <?php if( $this->state == 'onRegistered' ) echo 'class="current" '; ?>href="<?php echo $this->pageURI; ?>&state=onRegistered">Registered <span class="count">(<?php echo count($this->registeredClasses); ?>)</span></a></li><?php endif; ?>
			<?php if( count($this->unregisteredClasses) > 0 ) : ?><li> | <a <?php if( $this->state == 'onUnregistered' ) echo 'class="current" '; ?>href="<?php echo $this->pageURI; ?>&state=onUnregistered">Unregistered <span class="count">(<?php echo count($this->unregisteredClasses); ?>)</span></a></li><?php endif; ?></ul>
		
			<div class="tablenav">
				<div class="alignleft actions">
					<select name="<?php echo $this->getFieldName('action'); ?>">
						<option value="" selected="selected">Bulk Actions</option>
						<option value="register-selected">Register</option>
						<option value="unregister-selected">Unregister</option>
					</select>
					<input type="submit" name="doaction_active" value="Apply" class="button-secondary action" />
				</div>
			</div>
			<div class="clear"></div>
	
			<table class="widefat" cellspacing="0" id="all-plugins-table">
				<thead>
				<tr>
					<th scope="col" class="manage-column check-column"><input type="checkbox" /></th>
					<th scope="col" class="manage-column">Name</th>
					<th scope="col" class="manage-column">Description</th>
				</tr>
				</thead>
			
				<tfoot>
				<tr>
					<th scope="col" class="manage-column check-column"><input type="checkbox" /></th>
					<th scope="col" class="manage-column">Name</th>
					<th scope="col" class="manage-column">Description</th>
				</tr>
				</tfoot>
			
				<tbody class="plugins wpew">

				<?php foreach( $this->classes as $class ) :
					$widget = new $class();
					if( method_exists( $widget, 'onRegistrationListing' ) ) {
						if( $widget->onRegistrationListing() === false ) continue; 
					}
					$nameLink = $widget->name;
					if( in_array( $class, $this->registeredClasses ) ) {
						$rowClass = 'active';
						$regLink = 'Unregister';
						$action = 'unregister-selected';
						$editLink = $this->pageURI.'&widget='.$class.'&state=onEdit';
					} else {
						$rowClass = 'inactive';
						$regLink = 'Register';
						$action = 'register-selected';
					}
					$shortNameLink = $class; ?>
					<tr class="<?php echo $rowClass; ?>">
						<th scope="row" class="check-column"><input id="<?php echo $class.'-checkbox'; ?>" type="checkbox" name="<?php echo $this->getFieldName('checked'); ?>[]" value="<?php echo $class;?>" /></th>
						<td class="plugin-title"><strong><?php 
							if( $rowClass == 'active' ) {
								echo '<a href="'.$this->pageURI.'&widget='.$class.'&state=onEdit">'.$widget->name.'</a>';
							} else {
								echo '<label for="'.$class.'-checkbox">'.$widget->name.'</label>';
							}
						?></strong></td>
						<td class="desc"><p><?php echo $widget->widget_options['description']; ?></p></td>
					</tr>
					<tr class="<?php echo $rowClass; ?> second">
						<td></td>
						<td class="plugin-title"><div class="row-actions-visible"><span><a href="<?php 
							// Set this classes single url action request URI
							echo $this->pageURI.'&action='.$action.'&widget='.$class.'&state='.$this->state;
						?>" title="<?php echo $regLink; ?> this widget" class="edit"><?php echo $regLink; ?></a><?php
							if( $rowClass == 'active' ) {
								echo '<span class="hide-if-no-js"> | <a class="ajaxify" href="'.$this->pageURI.'&widget='.$class.'&state=onEdit" target=".edit-'.$class.'">Quick Edit</a></span>';
							}
						?></div></td>
						<td class="desc"><div class="edit-<?php echo $class; ?>"><?php $this->onControlHierarchy( $widget ); ?></div></td>
					</tr>
				<?php unset($widget); endforeach; ?>
		
				</tbody>
			</table>
			<div class="tablenav">
				<div class="alignleft actions">
					<select name="<?php echo $this->getFieldName('action2'); ?>">
						<option value="" selected="selected">Bulk Actions</option>
						<option value="register-selected">Register</option>
						<option value="unregister-selected">Unregister</option>
					</select>
					<input type="submit" name="doaction_active" value="Apply" class="button-secondary action" />
				</div>
			</div>
		</form>
		
	<?php }
	
	/**
	 * Used internally to render the editForm
	 *
	 * @return void
	 */
	function editForm( $class ) { 
		$widget = new $class();
		if( !is_array($this->widgets->registration[$class]) ) {
			$settings = $this->_defaultRegSettings;
		} else {
			$settings = $this->widgets->registration[$class];
		} ?>
		
		<h3>Administrative Controls</h3>
		<?php if($this->isAsync) : ?>
		<form class="ajaxify" name="exportForm" method="post" action="<?php echo $this->pageURI; ?>" target=".edit-<?php echo $class; ?>">
			<?php $this->doStateField( 'onEdit' ); ?>
		<?php else : ?>
		<form name="exportForm"  method="post" action="<?php echo $this->pageURI; ?>">
			<?php $this->doStateField( 'onEdit' ); ?>
		<?php endif; ?>
			<input type="hidden" name="<?php echo $this->getFieldName('widget'); ?>" value="<?php echo $class; ?>" />
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Display Type<p class="description">Rendering without tabs is basically like accessibility mode.</p></th>
					<td><?php xf_display_Renderables::buildInputList( $this->getFieldID('display'), $this->getFieldName('display'), array(
						'tabular' => 'With Tabs',
						'normal' => 'Without Tabs'
					), array(
						'checked' => $settings['display'],
						'afterInput' => ' &nbsp; ',
						'beforeLabel' => ' <span>',
						'afterLabel' => '</span>',
						'type' => 'radio'
					)); ?></td>
				</tr>
				
				<tr valign="top">
					<th scope="row">Render Hierarchy<p class="description">Deselect a control group to prevent rendering it within the overall controls of this widget.</p></th>
					<td><ul><?php
					$flags = $settings['renderFlags'];
					$checked = array();
					$counter = 0;
					$parentClasses = $widget->parentClasses;
					foreach( array_reverse($parentClasses) as $parentClass ) {
						$parentWidget = new $parentClass();
						$inputs[$parentClass] = $parentWidget->name;
						if( !is_array($flags) || (isset($flags[$counter]) && $flags[$counter] == 1) ) $checked[] = $parentClass;
						$counter++;
					}
					xf_display_Renderables::buildInputList( $this->getFieldID('renderFlags'), $this->getFieldName('renderFlags'), $inputs, array(
						'checked' => $checked,
						'beforeInput' => '<li>',
						'afterInput' => '</li>',
						'beforeLabel' => ' &nbsp; <span>',
						'afterLabel' => '</span>'/*,
						'type' => 'radio'*/
					)); ?></ul></td>
				</tr>
			</table>
			<p><?php if( !$this->isAsync ) : ?>
			<h3>Roles/Capabilities</h3>
			<p class="description">Ideally the section above should be completely replaced with role or capability-based display management. I am not ready to implement this yet, but just wanted to let you know that I am aware of the importance.</p>
			<?php endif; ?>
			<p><?php if( $this->isAsync ) : ?>
			<input type="submit" name="<?php echo $this->getFieldName('edit-submit'); ?>" class="button-primary" value="Save &amp; Close" />
			<a href="<?php echo $this->pageURI; ?>&state=onControlHierarchy&widget=<?php echo $class; ?>" class="ajaxify button-primary" target=".edit-<?php echo $class; ?>">Cancel</a>
			<?php else : ?>
			<input type="submit" name="<?php echo $this->getFieldName('edit-submit'); ?>" class="button-primary" value="Save" />
			<input type="submit" name="<?php echo $this->getFieldName('edit-close'); ?>" class="button-primary" value="Save &amp; Close" />
			<a href="<?php echo $this->pageURI; ?>" class="button-primary">Cancel</a>
			<?php endif; ?></p>
		</form>
	<?php }
	
	/**
	 * Used internally for a common content header
	 *
	 * @return void
	 */
	public function header() {
		if( !count($this->classes) ) {
			$this->state = null;
			$this->classes =& $this->allClasses;
		}
		$this->parentPage->header(); ?>
		<?php if( $this->widgets->registration ) : ?>
			<p class="description">This plugin has slightly changed the functionality of WordPress's <a href="widgets.php">Widgets Administration Page</a>. As a normal user you may not notice, overall your experience will be the same. For developers, you will notice what looks to be an extra step when adding a widget. This is necessary because of how extensive the controls are for the widgets included in this plugin. Basically no widget controls are rendered until the addition of that widget. The second forced save is needed to initiate the default settings that were once set by the pre-rendered but hidden controls. Also, widgets with functionalities as great as the Widget Group need access to more data than WordPress allows normally by the <a href="http://codex.wordpress.org/Widgets_API" target="wpew_window">Widget API</a>. These changes are necessary for this reason as well to keep these kinds of uber-widgets' modularity intact.</p>
			<p class="description">For the page to return to its default functionality either unregister all widgets on this page, <a href="plugins.php">deactivate</a>, or <a href="admin.php?page=wpew_admin_uninstall">uninstall</a> this plugin.</p>
			<?php else : ?>
			<p class="description">There are currently no widgets registered by this plugin.</p>
		<?php endif ?>
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