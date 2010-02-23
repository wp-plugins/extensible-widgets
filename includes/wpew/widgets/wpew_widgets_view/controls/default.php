<?php
$view_filename = $this->settings['view_filename'];
$view_params = esc_attr( urldecode( http_build_query( $this->settings['view_params'] ) ) );
		
$view_type_id = $this->get_field_id('view_type');
$view_type_name = $this->get_field_name('view_type');
$view_radios = xf_display_Renderables::buildInputList( $view_type_id, $view_type_name, array(
	'widget' => __('Widget View'),
	'theme' => __('Theme Template')
), array(
	'return' => true,
	'type' => 'radio',
	'checked' => $this->settings['view_type'],
	'beforeLabel' => ' <small>',
	'afterLabel' => '</small>'
));
?>

<fieldset class="setting_group toggle">
	<legend class="handle"><span class="widget-top">Custom View Templates<a class="widget-action hide-if-no-js" href="#"></a></span></legend>
	
	<div class="content" valign="top">
		<table width="100%" cellpadding="0" cellspacing="0"><tr>
		<td width="50%">
			<p><?php echo $view_radios['widget']; ?></p>
			<p><select <?php if( !count($this->getViews()) ) : ?>disabled="disabled"<?php endif; ?> rel="<?php echo $view_type_id; ?>-widget" class="widefat wpew_relcheck" id="<?php echo $this->get_field_id('view_filename'); ?>-widget" name="<?php echo $this->get_field_name('view_filename'); ?>[widget]">
				<?php $this->viewsDropdown( $view_filename ); ?>
			</select></p>
		</td>
		<td width="50%" valign="top">
			<p><?php echo $view_radios['theme']; ?></p>
			<p><select <?php if ( !count( get_page_templates() ) ) : ?>disabled="disabled"<?php endif; ?> rel="<?php echo $view_type_id; ?>-theme" class="widefat wpew_relcheck" id="<?php echo $this->get_field_id('view_filename'); ?>-theme" name="<?php echo $this->get_field_name('view_filename'); ?>[theme]">
				<option value=''><?php _e('Default Template'); ?></option>
				<?php page_template_dropdown( $view_filename ); ?>
			</select></p>
		</td>
		</tr></table>
		
		<p><small class="description">Edit where views are loaded from <a href="admin.php?page=extensible-widgets/settings" title="Extensible Widgets Settings" target="wpew_window">here</a>.<br />
		Add theme templates by placing files <a href="<?php echo get_stylesheet_directory_uri(); ?>/" title="Theme Location" target="wpew_window">here</a>.<br />
		All files require this comment header format:<br />
		<code>&lt;?php /* Template Name: My Template */ ?&gt;</code></small></p>
	</div>
</fieldset>

<fieldset class="setting_group toggle closed">
	<legend class="handle"><span class="widget-top">View Parameters<a class="widget-action hide-if-no-js" href="#"></a></span></legend>	
	
	<div class="content">
		<p><label for="<?php echo $this->get_field_id('view_params'); ?>">Parameters:</label>
		<textarea class="widefat" rows="5" id="<?php echo $this->get_field_id('view_params'); ?>" name="<?php echo $this->get_field_name('view_params'); ?>"><?php echo $view_params; ?></textarea></p>
					
		<p><small class="description">Example: <code>fname=john&amp;lname=smith</code><br />
		Access in custom view with <code>$fname</code> and <code>$lname</code>.</small></p>
	</div>
</fieldset>