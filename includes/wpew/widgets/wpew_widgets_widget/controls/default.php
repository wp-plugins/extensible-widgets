<?php
$title = esc_attr( $this->settings['title'] );
$render_title = ( (bool) $this->settings['render_title'] ) ? ' checked="checked"' : '';
$add_css_class = esc_attr( $this->settings['add_css_class'] );
?>

<fieldset class="setting_group">
	<legend class="description">Basic widget settings used for rendering:</legend>
	
	<p><label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
	<input rel="<?php echo $this->get_field_id('render_title'); ?>" class="widefat wpew_relcheck wpew_allowempty" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
	
	<p><input id="<?php echo $this->get_field_id('render_title'); ?>" name="<?php echo $this->get_field_name('render_title'); ?>" type="checkbox" <?php echo $render_title; ?> /> <label for="<?php echo $this->get_field_id('render_title'); ?>"><small>Render Title</small></label><br />
	<small class="description">Includes <code>before_title</code> and <code>after_title</code></small></p>
	
	<p><label>Additional CSS Classes:</label>
	<input class="widefat" id="<?php echo $this->get_field_id('add_css_class'); ?>" name="<?php echo $this->get_field_name('add_css_class'); ?>" type="text" value="<?php echo $add_css_class; ?>" /></p>
	
	<p><small class="description">Appended to widget's classes. (Dynamic classes may be rendered with <code>before_widget</code> using <code>%2$s</code>)</small></p>
</fieldset>