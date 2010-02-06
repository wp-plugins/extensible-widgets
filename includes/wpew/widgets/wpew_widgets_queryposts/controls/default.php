<?php
$query = esc_attr( urldecode( http_build_query( $this->settings['query'] ) ) );
?>
		
<fieldset class="setting_group">
	<legend>Query parameters used to retrieved entries:</legend>
	<p><label for="<?php echo $this->get_field_id('query'); ?>">Parameters:</label>
	<textarea class="widefat" rows="5" id="<?php echo $this->get_field_id('query'); ?>" name="<?php echo $this->get_field_name('query'); ?>"><?php echo $query; ?></textarea></p>
	
	<p><small class="description">Access in custom view with <code>$wp_query</code> using <a href="http://codex.wordpress.org/The_Loop" target="wpew_window">The Loop</a>.</small></p>
	
</fieldset>