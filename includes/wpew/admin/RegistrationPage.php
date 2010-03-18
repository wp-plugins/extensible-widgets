<?php
/**
 * This file defines wpew_admin_RegistrationPage, a controller class a plugin admin page.
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
 * wpew_admin_RegistrationPage
 *
 * @package wpew
 * @subpackage admin
 */
class wpew_admin_RegistrationPage extends xf_wp_AAdminController {
	
	/**
	 * @see xf_wp_ASingleton::getInstance();
	 */
	public static function &getInstance() {
		return xf_patterns_ASingleton::getSingleton(__CLASS__);
	}
	
	/**
	 * @var string $title This controller's title
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
	private $activeClass;
	
	/**
	 * @ignore
	 * Used internally
	 */
	private $_defaultRegSettings = array(
		'display' => 'tabular',
		'renderFlags' => null,
		'defaults' => null
	);
	
	/**
	 * Function called before any rendering occurs within the WordPress admin
	 *
	 * return void
	 */
	public function onBeforeRender() {
		// Set active widget class if there is one
		if( empty($this->submitted['widget']) ) {
			$this->activeClass = ( !empty(self::$get['widget']) ) ? self::$get['widget'] : null;
		} else {
			$this->activeClass = $this->submitted['widget'];
		}
		// get_option
		$registration = ( $this->parent->plugin->widgets->registration ) ? $this->parent->plugin->widgets->registration : array();
		// There was either a bulk action or a single action
		if( isset($this->submitted['checked']) || !empty($this->activeClass) ) {
			// find and set the current action
			$action = false;
			if( !empty($this->submitted['action']) ) {
				$action = $this->submitted['action'];
			} else if( !empty($this->submitted['action2']) ) {
				$action = $this->submitted['action2'];
			} else if( !empty(self::$get['action']) ) {
				$action = self::$get['action'];
			}
			// Was there an action in the request
			if( $action ) {
				// Get the classes we are applying the action to
				$classes = ( isset($this->submitted['checked']) ) ? $this->submitted['checked'] : array();
				if( !empty($this->activeClass) ) $classes[] = $this->activeClass;
				// Loop through the classes
				foreach( $classes as $class ) {
					$this->parent->plugin->widgets->importWidget( $class, false );
					$widget = new $class();
					switch( $action ) {
						case 'register-selected' :
							if( !isset($registration[$class]) ) {
								if( apply_filters( self::joinShortName('onRegister', $class), $widget ) === false ) continue; 
								$registration[$class] = true;
								// For now, we are setting the option ourselves 
								// until I figure out where the rogue widget is orginating from
								if( !get_option($widget->option_name) ) {
									update_option( $widget->option_name, array( '_multiwidget' => 1 ) );
								}
								$this->noticeUpdates .= '<p>Registered <strong>'.$widget->name.'</strong></p>';
							}
						break;
						case 'unregister-selected' :
							if( isset($registration[$class]) ) {
								if( apply_filters( self::joinShortName('onUnregister', $class), $widget ) === false ) continue; 
								unset( $registration[$class] );
								$this->noticeUpdates .= '<p>Unregistered <strong>'.$widget->name.'</strong></p>';
							}
						break;
					}
					ksort($registration);
					// update_option
					$this->parent->plugin->widgets->registration = $registration;
					// free memory
					unset( $widget );
				}
			}
		}
		// Look and find all the widget class files of this plugin
		$files = $this->parent->plugin->loader->locate( $this->parent->plugin->widgets->dirWidgets, false, true, false);
		foreach( $files as $file ) {
			$a = explode('.',basename($file));
			$class = 'wpew_widgets_'.array_shift($a);
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
		if( $this->isAsync ) {
			$this->registrationTable();
		} else {
			$this->header();
			$this->registrationForm();
			$this->footer();
		}
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onRegistered() {
		$this->classes =& $this->registeredClasses;
		$this->header();
		$this->registrationForm();
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
		$this->registrationForm();
		$this->footer();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onResetSettings() {
		$class = $this->activeClass;
		$widget = new $class();
		
		$registration = $this->parent->plugin->widgets->registration;
		$registration[$class] = true;
		$this->parent->plugin->widgets->registration = $registration;
		$this->noticeUpdates = '<p>Widget - <strong>'.$widget->name.'</strong> reset.</p>';
		
		$this->noticeUpdates .= '<p><a href="'.$this->controllerURI.'">Go back</a> to registration.</p>';
		$this->parent->header( ' Editing &raquo; ' . $widget->name ); ?>
		<?php unset( $widget );
		$this->editForm( $class );
		$this->footer();
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onEdit() {
		$class = $this->activeClass;
		if( !in_array( $class, $this->allClasses, true ) ) {
			$this->noticeErrors = '<p>Sorry, the specified widget <strong>'.$class.'</strong> does not exist.</p>';
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
			if( $this->submitted['widget_defaults'] == 'custom' && !empty(self::$post['widget-'.$widget->id_base][0]) ) {
				// null out the option_name to make sure it doesn't actually save anything
				$widget->option_name = null;
				$old_settings = $widget->settings;
				$new_settings = self::$post['widget-'.$widget->id_base][0];
				$widget->update( $new_settings, $old_settings );
				$settings['defaults'] = $widget->settings;
			} else {
				$settings['defaults'] = null;
			};
			$registration = $this->parent->plugin->widgets->registration;
			$registration[$class] = $settings;
			$this->parent->plugin->widgets->registration = $registration;
			$this->noticeUpdates = '<p>Widget - <strong>'.$widget->name.'</strong> updated.</p>';
		}
		if( isset( $this->submitted['edit-close'] ) ) {
			$this->state = null;
			$this->index();
			return;
		}
		if( !$this->isAsync ) {
			$this->noticeUpdates .= '<p><a href="'.$this->controllerURI.'">Go back</a> to registration.</p>';
			$this->parent->header( ' Editing &raquo; ' . $widget->name ); ?>
			<?php unset( $widget );
			$this->editForm( $class );
			$this->footer();
		} else if( !empty($this->activeClass) ) {
			unset( $widget );
			$this->editForm( $this->activeClass );
		} else {
			$this->onControlHierarchy( $class );
		}
		unset( $widget );
	}
	
	/**
	 * State called by corresponding submited or preset state
	 *
	 * @return void
	 */
	public function onControlHierarchy( $class = null ) {
		if( empty($class) ) $class = $this->activeClass;
		$widget = new $class(); ?>
		<p class="description">
		<?php if( in_array( $class, $this->unregisteredClasses, true ) ) : ?>
			Register this widget to allow it to be added within widgets administration.
		<?php else :
			$registration = $this->parent->plugin->widgets->registration[get_class($widget)];
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
		<?php endif;
		unset( $widget );
	}
	
	/**
	 * Used internally to render a registrationTableRow
	 *
	 * @return void
	 */
	public function registrationTableRow( $class = null ) {
		if( empty($class) ) $class = $this->activeClass;
		$widget = new $class();
		if( apply_filters( self::joinShortName('onRegistrationListing', $class), $widget ) === false ) return;
		$nameLink = $widget->name;
		if( in_array( $class, $this->registeredClasses ) ) {
			$rowClass = 'active';
			$regLink = 'Unregister';
			$action = 'unregister-selected';
			$editLink = $this->controllerURI.'&widget='.$class.'&state=onEdit';
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
					echo '<a href="'.$this->controllerURI.'&widget='.$class.'&state=onEdit">'.$widget->name.'</a>';
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
				echo $this->controllerURI.'&action='.$action.'&widget='.$class.'&state='.$this->state;
			?>" title="<?php echo $regLink; ?> this widget" class="edit"><?php echo $regLink; ?></a><?php
				if( $rowClass == 'active' ) {
					echo '<span class="hide-if-no-js"> | <a class="ajaxify" href="'.$this->controllerURI.'&widget='.$class.'&state=onEdit" target=".edit-'.$class.'">Quick Edit</a></span>';
				}
			?></div></td>
			<td class="desc"><div class="edit-<?php echo $class; ?>"><?php $this->onControlHierarchy( $class ); ?></div></td>
		</tr>
		<?php unset( $widget );
	}
	
	/**
	 * Used internally to render the registrationTable
	 *
	 * @return void
	 */
	public function registrationTable() { ?>
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
			<?php foreach( $this->classes as $class ) {
				$this->registrationTableRow( $class );
			} ?>
			</tbody>
		</table>
	<?php }
	
	/**
	 * Used internally to render the registrationForm
	 *
	 * @return void
	 */
	public function registrationForm() { ?>
		<form method="post" action="<?php echo $this->controllerURI; ?>">
			<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo wp_create_nonce('load_template'); ?>" />
			<input type="hidden" name="_wp_http_referer" value="<?php echo $this->controllerURI; ?>" /><input type="hidden" name="plugin_status" value="all" />
			<input type="hidden" name="paged" value="1" />
			<?php $this->doStateField( $this->state ); ?>
			
			<ul class="subsubsub">
			<li><a <?php if( $this->state == self::DEFAULT_STATE ) echo 'class="current" '; ?>href="<?php echo $this->controllerURI; ?>">All <span class="count">(<?php echo count($this->allClasses); ?>)</span></a></li>
			<?php if( count($this->registeredClasses) > 0 ) : ?><li> | <a <?php if( $this->state == 'onRegistered' ) echo 'class="current" '; ?>href="<?php echo $this->controllerURI; ?>&state=onRegistered">Registered <span class="count">(<?php echo count($this->registeredClasses); ?>)</span></a></li><?php endif; ?>
			<?php if( count($this->unregisteredClasses) > 0 ) : ?><li> | <a <?php if( $this->state == 'onUnregistered' ) echo 'class="current" '; ?>href="<?php echo $this->controllerURI; ?>&state=onUnregistered">Unregistered <span class="count">(<?php echo count($this->unregisteredClasses); ?>)</span></a></li><?php endif; ?></ul>
		
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
			
			<?php $this->registrationTable(); ?>
			
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
		if( !is_array($this->parent->plugin->widgets->registration[$class]) ) {
			$settings = $this->_defaultRegSettings;
		} else {
			$settings = $this->parent->plugin->widgets->registration[$class];
		}
		$widget = new $class(); ?>
		
		<h3>Administrative Controls</h3>
		<?php if($this->isAsync) : ?>
		<form class="ajaxify" name="exportForm" method="post" action="<?php echo $this->controllerURI; ?>" target=".edit-<?php echo $class; ?>">
			<?php $this->doStateField( 'onEdit' ); ?>
		<?php else : ?>
		<form name="exportForm"  method="post" action="<?php echo $this->controllerURI; ?>">
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
				
			<p><?php if( $this->isAsync ) : ?>
			<input type="submit" name="<?php echo $this->getFieldName('edit-submit'); ?>" class="button-primary" value="Save &amp; Close" />
			<a href="<?php echo $this->controllerURI; ?>&state=onControlHierarchy&widget=<?php echo $class; ?>" class="ajaxify button-primary" target=".edit-<?php echo $class; ?>">Cancel</a>
			<?php else : ?>
			
			<h3>Default Settings</h3>
			<p class="description">Default settings represent the initial settings for each widget of this kind when it is first added to a widget area.</p>
			<?php
			$defaults = ( is_array($settings['defaults']) ) ? $settings['defaults'] : array();
			$widget->number = 0;
			?>
			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label><input type="radio" name="<?php echo $this->getFieldName('widget_defaults'); ?>" value="system"<?php if( !is_array($settings['defaults']) ) echo ' checked="checked"'; ?>> Use System Defaults</label><p class="description">This deletes any custom settings that may be set within the controls preview and uses the defaults set by the system.</p></th>
					
					<th><label><input type="radio" name="<?php echo $this->getFieldName('widget_defaults'); ?>" value="custom"<?php if( is_array($settings['defaults']) ) echo ' checked="checked"'; ?>> Use Custom Defaults</label>
						<p class="description">The following preview represents custom default settings. You may edit and save these settings within this overall form, though you must also select the button above to activate them for actual usage.</p>
						
						<h3>Controls Preview</h3>
						<div id="<?php echo $widget->id_base; ?>-0" class="wpew_widget_default widget <?php echo $widget->control_options['id_base']; ?>" style="width: <?php echo $widget->control_options['width']+10;?>px">
						<?php $widget->form( $defaults ); ?>
						</div>
					</th>
				</tr>
			</table>
			
			<input type="submit" name="<?php echo $this->getFieldName('edit-submit'); ?>" class="button-primary" value="Save" />
			<input type="submit" name="<?php echo $this->getFieldName('edit-close'); ?>" class="button-primary" value="Save &amp; Close" />
			<a href="<?php echo $this->controllerURI; ?>" class="button-primary">Close</a>
			<?php endif; ?>
		</form>
		<?php if( !$this->isAsync ) : ?>		
		<form onsubmit="return confirm('Are you sure you want to reset the settings back to the registration defaults?');" name="resetForm" method="post" action="<?php echo $this->controllerURI; ?>">
			<?php $this->doStateField( 'onResetSettings' ); ?>
			<input type="hidden" name="<?php echo $this->getFieldName('widget'); ?>" value="<?php echo $class; ?>" />
			<h3>Reset to Registration Defaults</h3>
			<p class="description">Resetting <span class="red">clears any customizations</span> made within the form above, setting everything back to the initial registration defaults.</p>
			<p><input type="submit" name="<?php echo $this->getFieldName('reset-submit'); ?>" class="button-primary" value="Reset Settings" /></p>
		</form>
		<?php endif; ?>
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
		$this->parent->header(); ?>
		<?php if( $this->parent->plugin->widgets->registration ) : ?>
			<p class="description">This plugin has slightly changed the functionality of WordPress's <a href="widgets.php">Widgets Administration Page</a>. As a normal user you may not notice, overall your experience will be the same. For developers, you will notice what looks to be an extra step when adding a widget. This is necessary because of how extensive the controls are for most widgets included with this plugin. The advantage is that no widget control is rendered until that widget is added. The second save action is forced to initiate the default settings that were before pre-rendered as hidden elements. Widgets with functionalities as great as the <strong>Group</strong> widget need access to more data than WordPress allows normally by the <a href="http://codex.wordpress.org/Widgets_API" target="wpew_window">Widget API</a>. For this reason these changes are also necessary to keep the modularity of these kinds of 'uber-widgets' intact.</p>
			<p class="description">For everything to return to default functionality, either unregister all widgets below, <a href="plugins.php">deactivate</a>, or <a href="admin.php?page=extensible-widgets/uninstall" class="wpew-navigation">uninstall</a> this plugin.</p>
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
		$this->parent->footer();
	}
}
?>