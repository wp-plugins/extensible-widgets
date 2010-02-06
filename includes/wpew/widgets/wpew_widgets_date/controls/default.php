<?php
$jj = esc_attr( $this->settings['jj'] );
$mm = esc_attr( $this->settings['mm'] );
$aa = esc_attr( $this->settings['aa'] );
$hh = esc_attr( $this->settings['hh'] );
$mn = esc_attr( $this->settings['mn'] );
$ss = esc_attr( $this->settings['ss'] );
?>

<fieldset class="setting_group">
	<legend class="description">Special View Parameter:</legend>
	
	<p><label for="<?php echo $this->get_field_id('mm'); ?>"><?php _e('Date:'); ?></label><br />
	<?php
	global $wp_locale;
	echo "<select id=\"" . $this->get_field_id('mm') . "\" name=\"" . $this->get_field_name('mm') . "\">\n";
	for ( $i = 1; $i < 13; $i = $i +1 ) {
		echo "\t\t\t" . '<option value="' . zeroise($i, 2) . '"';
		if ( $i == $mm )
			echo ' selected="selected"';
		echo '>' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) . "</option>\n";
	}
	echo '</select>';
	?>
	<input type="text" id="<?php echo $this->get_field_id('jj'); ?>" name="<?php echo $this->get_field_name('jj'); ?>" value="<?php echo $jj; ?>" size="2" maxlength="2" autocomplete="off" />
	<input type="text" id="<?php echo $this->get_field_id('aa'); ?>" name="<?php echo $this->get_field_name('aa'); ?>" value="<?php echo $aa; ?>" size="4" maxlength="4" autocomplete="off" />
	<input type="text" id="<?php echo $this->get_field_id('hh'); ?>" name="<?php echo $this->get_field_name('hh'); ?>" value="<?php echo $hh; ?>" size="2" maxlength="2" autocomplete="off" />
	<input type="text" id="<?php echo $this->get_field_id('mn'); ?>" name="<?php echo $this->get_field_name('mn'); ?>" value="<?php echo $mn; ?>" size="2" maxlength="2" autocomplete="off" />
	<input type="hidden" id="<?php echo $this->get_field_id('ss'); ?>" name="<?php echo $this->get_field_name('ss'); ?>" value="<?php echo $ss; ?>" /></p>
	
	<p><small class="description">Access this in a custom view with <code>$date</code>.</small></p>

</fieldset>