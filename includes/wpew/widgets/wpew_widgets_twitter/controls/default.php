<?php
$username = esc_attr( $this->settings['username'] );
$password = esc_attr( $this->settings['password'] );
$limit = (int) $this->settings['limit'];
?>

<fieldset class="setting_group">
	<legend class="description">Special View Parameters:</legend>
	
	<fieldset class="setting_group">
		<legend class="description">The credentials of the Twitter account:</legend>
		
		<p><label for="<?php echo $this->get_field_id('username'); ?>">Username:</label> <input id="<?php echo $this->get_field_id('username'); ?>" name="<?php echo $this->get_field_name('username'); ?>" value="<?php echo $username; ?>" type="text" class="widefat" /></p>
		
		<p><label for="<?php echo $this->get_field_id('password'); ?>">Password:</label> <input id="<?php echo $this->get_field_id('password'); ?>" name="<?php echo $this->get_field_name('password'); ?>" value="<?php echo $password; ?>" type="password" class="widefat" /></p>
	</fieldset>
	
	<p><label for="<?php echo $this->get_field_id('limit'); ?>">Limit:</label> <input id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" value="<?php echo $limit; ?>" type="text" size="3" /><br />
	<small class="description">How many tweets to return per page.</small></p>
	
	<p><small class="description">Access object in custom view with <code>$twitter</code><br />
	Access array in custom view with <code>$tweets</code></small></p>
	
</fieldset>