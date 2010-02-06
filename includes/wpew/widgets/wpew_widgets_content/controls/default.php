<?php
$content = esc_attr( $this->settings['content'] );
?>
		
<fieldset class="setting_group">
	<legend class="description">Special View Parameter:</legend>
	
	<p><label for="<?php echo $this->get_field_id('content'); ?>">Content:</label> 
	<textarea class="widefat" rows="10" id="<?php echo $this->get_field_id('content'); ?>" name="<?php echo $this->get_field_name('content'); ?>"><?php echo $content; ?></textarea></p>
	
	<p><small class="description">Access in custom view with <code>$content</code>.</small></p>
	
</fieldset>