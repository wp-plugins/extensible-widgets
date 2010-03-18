<?php
// Build form settings
$context_calls_id = $this->get_field_id('context_calls');
$context_calls_name = $this->get_field_name('context_calls');
$general_calls_args = array(
	'checked' => $this->settings['context_calls'],
	'afterInput' => '<br />',
	'beforeLabel' => ' <small>',
	'afterLabel' => '</small>'
);
$context_args_id = $this->get_field_id('context_args');
$context_args_name = $this->get_field_name('context_args');
if( is_array( $this->settings['context_args'] ) ) {
	foreach( $this->settings['context_args'] as $key => $value ) {
		$this->settings['context_args'][$key] = implode(',', $value );
	}
	extract( $this->settings['context_args'] );
}

// Build and RETURN specific fields as a variable to control output
$specific_fields = xf_display_Renderables::buildInputList( $context_calls_id, $context_calls_name, array(
	'is_sticky' => __('Sticky').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Sticky_Post" target="help">?</a>',
	'is_single' => __('Single').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Single_Post_Page" target="help">?</a>',
	'is_page_template' => __('Template'),
	'is_page' => __('Page').' <a href="http://codex.wordpress.org/Conditional_Tags#A_PAGE_Page" target="help">?</a>',
	'is_author' => __('Author').' <a href="http://codex.wordpress.org/Conditional_Tags#An_Author_Page" target="help">?</a>',
	'is_category' => __('Category').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Category_Page" target="help">?</a>',
	'is_tag' => __('Tag').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Tag_Page" target="help">?</a>',
	'is_tax' => __('Taxonomy').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Taxonomy_Page" target="help">?</a>'
), array(
	'return' => true,
	'checked' => $this->settings['context_calls'],
	'beforeLabel' => ' <small>',
	'afterLabel' => '</small>'
));
?>

<p><?php xf_display_Renderables::buildInputList( $this->get_field_id('context'), $this->get_field_name('context'), array(
	'exc' => __('Choose where this widget').' <span class="red strikeout">'.__('WILL NOT').'</span> '.__('render.'),
	'inc' => __('Choose where this widget <span class="green">WILL</span> render.')
), array(
	'type' => 'radio',
	'checked' => array( $this->settings['context'] ),
	'afterInput' => '<br />'
) ); ?></p>

<fieldset class="setting_group toggle">
	<legend class="handle"><span class="widget-top">General Contexts<a class="widget-action hide-if-no-js" href="#"></a></span></legend>
	
	<div class="content">
		<table width="100%" cellpadding="0" cellspacing="0"><tr>
			<td valign="top">
				<p><?php xf_display_Renderables::buildInputList( $context_calls_id, $context_calls_name, array(
					'is_home' => __('Home').' <a href="http://codex.wordpress.org/Conditional_Tags#The_Main_Page" target="help">?</a>',
					'is_front_page' => __('Front Page').' <a href="http://codex.wordpress.org/Conditional_Tags#The_Front_Page" target="help">?</a>',
					'is_singular' => __('Singular').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Single_Page.2C_Single_Post_or_Attachment" target="help">?</a>',
					'is_attachment' => __('Attachment').' <a href="http://codex.wordpress.org/Conditional_Tags#An_Attachment" target="help">?</a>',
					'comments_open' => __('Comments Open').' <a href="http://codex.wordpress.org/Conditional_Tags#Any_Page_Containing_Posts" target="help">?</a>',
					'pings_open' => __('Pings Open').' <a href="http://codex.wordpress.org/Conditional_Tags#Any_Page_Containing_Posts" target="help">?</a>',
					'is_comments_popup' => __('Comments Popup').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Comments_Popup" target="help">?</a>',
					'is_404' => __('404').' <a href="http://codex.wordpress.org/Conditional_Tags#A_404_Not_Found_Page" target="help">?</a>'
				), $general_calls_args ); ?></p>
			</td>
			<td valign="top">
				<p><?php xf_display_Renderables::buildInputList( $context_calls_id, $context_calls_name, array(
					'is_archive' => __('Archive').' <a href="http://codex.wordpress.org/Conditional_Tags#Any_Archive_Page" target="help">?</a>',
					'is_date' => __('Date').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Date_Page" target="help">?</a>',
					'is_year' => __('Year').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Date_Page" target="help">?</a>',
					'is_month' => __('Month').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Date_Page" target="help">?</a>',
					'is_day' => __('Day').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Date_Page" target="help">?</a>',
					'is_time' => __('Time').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Date_Page" target="help">?</a>',
					'is_search' => __('Search').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Search_Result_Page" target="help">?</a>',
					'is_paged' => __('Paged').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Paged_Page" target="help">?</a>'
				), $general_calls_args ); ?></p>
			</td>
			<td valign="top">
				<p><?php xf_display_Renderables::buildInputList( $context_calls_id, $context_calls_name, array(
					'is_preview' => __('Preview').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Preview" target="help">?</a>',
					'is_admin' => __('Admin').' <a href="http://codex.wordpress.org/Conditional_Tags#The_Administration_Panels" target="help">?</a>',
					'is_feed' => __('Feed').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Syndication" target="help">?</a>',
					'is_trackback' => __('Trackback').' <a href="http://codex.wordpress.org/Conditional_Tags#A_Trackback" target="help">?</a>',
					'is_plugin_page' => __('Plugin Page'),
					'is_robots' => __('Robots'),
					'is_user_logged_in' => __('Logged In')
				), $general_calls_args ); ?></p>
			</td>
		</tr></table>
	</div>
</fieldset>

<fieldset class="setting_group toggle closed">
	<legend class="handle"><span class="widget-top">Specific Contexts<a class="widget-action hide-if-no-js" href="#"></a></span></legend>
	
	<div class="content">
		<p><small>All fields are optional, use checkbox for general testing. For multiple entries use comma. (ex: 1,3,7)</small></p>
		
		<table width="100%" cellpadding="0" cellspacing="0">
			<?php if ( count( get_page_templates() ) ) : ?>
			<tr>
				<td valign="top"><?php echo $specific_fields['is_page_template']; ?></td>
				<td>
					<p><select rel="<?php echo $context_calls_id; ?>-is_page_template" class="widefat wpew_relcheck" id="<?php echo $context_args_id; ?>-is_page_template" name="<?php echo $context_args_name; ?>[is_page_template]">
						<option value=''><?php _e('Default Template'); ?></option>
						<?php page_template_dropdown($is_page_template); ?>
					</select></p>
				</td>
			</tr>
			<?php endif; ?>
			<tr>
				<td valign="top" width="90"><?php echo $specific_fields['is_sticky']; ?></td>
				<td>
					<p><input rel="<?php echo $context_calls_id; ?>-is_sticky" class="wpew_relcheck" size="6" maxlength="6" id="<?php echo $context_args_id; ?>-is_sticky" name="<?php echo $context_args_name; ?>[is_sticky]" type="text" value="<?php echo $is_sticky; ?>" /> <small>Post ID</small></p>
				</td>
			</tr>
			<tr>
				<td valign="top"><?php echo $specific_fields['is_single']; ?></td>
				<td>
					<p><input rel="<?php echo $context_calls_id; ?>-is_single" class="widefat wpew_relcheck" id="<?php echo $context_args_id; ?>-is_single" name="<?php echo $context_args_name; ?>[is_single]" type="text" value="<?php echo $is_single; ?>" /><br />
					<small>Post: titles, slugs, IDs</small></p>
				</td>
			</tr>
			<tr>
				<td valign="top"><?php echo $specific_fields['is_page']; ?></td>
				<td>
					<p><input rel="<?php echo $context_calls_id; ?>-is_page" class="widefat wpew_relcheck" id="<?php echo $context_args_id; ?>-is_page" name="<?php echo $context_args_name; ?>[is_page]" type="text" value="<?php echo $is_page; ?>" /><br />
					<small>Page: titles, slugs, IDs</small></p>
				</td>
			</tr>
			<tr>
				<td valign="top"><?php echo $specific_fields['is_author']; ?></td>
				<td>
					<p><input rel="<?php echo $context_calls_id; ?>-is_author" class="widefat wpew_relcheck" id="<?php echo $context_args_id; ?>-is_author" name="<?php echo $context_args_name; ?>[is_author]" type="text" value="<?php echo $is_author; ?>" /><br />
					<small>User: nice-names, nicknames, IDs</small></p>
				</td>
			</tr>
			<tr>
				<td valign="top"><?php echo $specific_fields['is_category']; ?></td>
				<td>
					<p><input rel="<?php echo $context_calls_id; ?>-is_category" class="widefat wpew_relcheck" id="<?php echo $context_args_id; ?>-is_category" name="<?php echo $context_args_name; ?>[is_category]" type="text" value="<?php echo $is_category; ?>" /><br />
					<small>Category: names, slugs, IDs</small></p>
				</td>
			</tr>
			<tr>
				<td valign="top"><?php echo $specific_fields['is_tag']; ?></td>
				<td>
					<p><input rel="<?php echo $context_calls_id; ?>-is_tag" class="widefat wpew_relcheck" id="<?php echo $context_args_id; ?>-is_tag" name="<?php echo $context_args_name; ?>[is_tag]" type="text" value="<?php echo $is_tag; ?>" /><br />
					<small>Tag: slugs</small></p>
				<td>
			</tr>
			<tr>
				<td valign="top"><?php echo $specific_fields['is_tax']; ?></td>
				<td>
					<p><input rel="<?php echo $context_calls_id; ?>-is_tax" class="widefat wpew_relcheck" id="<?php echo $context_args_id; ?>-is_tax" name="<?php echo $context_args_name; ?>[is_tax]" type="text" value="<?php echo $is_tax; ?>" /><br />
					<small>Category/Tag: slugs</small></p>
				</td>
			</tr>
		</table>
	</div>
</fieldset>