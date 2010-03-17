<?php
$group_name = esc_attr( $this->settings['group_name'] );
$before_widget = esc_attr( $this->settings['before_widget'] );
$after_widget = esc_attr( $this->settings['after_widget'] );
$before_title = esc_attr( $this->settings['before_title'] );
$after_title = esc_attr( $this->settings['after_title'] );
$before_group = esc_attr( $this->settings['view_params']['before_group'] );
$after_group = esc_attr( $this->settings['view_params']['after_group'] );

$presets =  esc_attr( $this->settings['presets'] );
$presetDropdown = array(
	'Default' => '',
	'Custom' => '*',
	'Blank' => 'blank',
	'&lt;div&gt;' => 'div',
	'Simple &lt;div&gt;' => 'simple_div'
);
// These should not be editable by the user
$widgets = $this->settings['widgetIDs'];
$count = count( $widgets );
?>

<fieldset class="setting_group toggle">
	<legend><span class="widget-top"><strong>Contains <?php echo $count; ?> Widget<?php if($count!=1) echo 's'; ?> &raquo; <a class="button" href="<?php echo wpew_widgets_Group::getEditURI( $this ); ?>" title="Order/Remove/Add Widgets">Edit Widgets</a></strong><a class="handle widget-action hide-if-no-js" href="#"></a></span></legend>
	<div class="content">
	<p><label>Group Name:<input class="widefat" name="<?php echo $this->get_field_name('group_name'); ?>" type="text" value="<?php echo $group_name; ?>" /></label></p>
	
	<p><small class="description">The name has nothing to do with the title of this widget.<br />
	Render in a custom view using <code>$group_id</code> or <code>$group_name</code></small></p>
	</div>
</fieldset>

<fieldset class="setting_group">
	<legend>These settings support custom or preset values:</legend>
	
	<p><label>Setting Presets: <select class="wpew_presets" name="<?php echo $this->get_field_name('presets'); ?>">
		<?php xf_display_Renderables::buildSelectOptions( $presetDropdown, $presets); ?>
	</select></label></p>

	<fieldset class="setting_group toggle closed">
		<legend class="handle"><span class="widget-top">Group Registration Settings<a class="widget-action hide-if-no-js" href="#"></a></span></legend>
		
		<div class="content">
			<p><label>Before Widget:<input class="widefat wpew_presets" name="<?php echo $this->get_field_name('before_widget'); ?>" type="text" value="<?php echo $before_widget; ?>" /></label></p>
			
			<p><label>After Widget:<input class="widefat wpew_presets" name="<?php echo $this->get_field_name('after_widget'); ?>" type="text" value="<?php echo $after_widget; ?>" /></label></p>
			
			<p><label>Before Title:<input class="widefat wpew_presets" name="<?php echo $this->get_field_name('before_title'); ?>" type="text" value="<?php echo $before_title; ?>" /></label></p>
			
			<p><label>After Title:<input class="widefat wpew_presets" name="<?php echo $this->get_field_name('after_title'); ?>" type="text" value="<?php echo $after_title; ?>" /></label></p>
		</div>
	</fieldset>
	
	<fieldset class="setting_group toggle closed">
		<legend class="handle"><span class="widget-top">Special View Parameters<a class="widget-action hide-if-no-js" href="#"></a></span></legend>
		
		<div class="content">
			<p><label>Before Group:<input class="widefat wpew_presets" name="<?php echo $this->get_field_name('before_group'); ?>" type="text" value="<?php echo $before_group; ?>" /></label><br />
			<small class="description">Access in custom view with <code>$before_group</code>.</small></p>
				
			<p><label>After Group:<input class="widefat wpew_presets" name="<?php echo $this->get_field_name('after_group'); ?>" type="text" value="<?php echo $after_group; ?>" /></label><br />
			<small class="description">Access in custom view with <code>$after_group</code>.</small></p>
		</div>
	</fieldset>
		
</fieldset>