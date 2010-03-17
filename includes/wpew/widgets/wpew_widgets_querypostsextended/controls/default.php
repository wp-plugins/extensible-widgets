<?php
extract( $this->settings['query'] ); 
if( isset( $post__in ) ) $post__in = implode(',', $post__in);
if( isset( $post__not_in ) )  $post__not_in = implode(',', $post__not_in);
?>

<p><small class="description">No field is required except when certain fields are set to certain values, these should be common sense. Leave any field blank for the default value. Leave all fields blank to use the global query set by WordPress.</small></p>
	
<fieldset class="setting_group toggle">
	<legend class="handle"><span class="widget-top">General Parameters<a class="widget-action hide-if-no-js" href="#"></a></span></legend>
	
	<div class="content">
	
		<fieldset class="setting_group">
			<p><small>Status of Inherit pertains to attachments and revisions:</small></p>
			<p><label for="<?php echo $this->get_field_id('post_status'); ?>">Status:</label> 
			<select name="<?php echo $this->get_field_name('post_status'); ?>" id="<?php echo $this->get_field_id('post_status'); ?>">
				<?php xf_display_Renderables::buildSelectOptions( array(
					'Not Set' => '',
					'Published' => 'publish',
					'Private' => 'private',
					'Draft' => 'draft',
					'Pending' => 'pending',
					'Inherit' => 'inherit',
					'Scheduled' => 'future',
					'Trash (v2.9)' => 'trash'
				),
				$post_status ); ?>
			</select>
			<label for="<?php echo $this->get_field_id('post_type'); ?>">Type:</label> 
			<select name="<?php echo $this->get_field_name('post_type'); ?>" id="<?php echo $this->get_field_id('post_type'); ?>">
				<?php xf_display_Renderables::buildSelectOptions( array(
					'Not Set' => '',
					'Any' => 'any',
					'Post' => 'post',
					'Page' => 'page',
					'Attachment' => 'attachment',
					'Revision' => 'revision'
				),
				$post_type ); ?>
			</select></p>
		
			<p><label for="<?php echo $this->get_field_id('showposts'); ?>">Show Posts:</label> <input id="<?php echo $this->get_field_id('showposts'); ?>" name="<?php echo $this->get_field_name('showposts'); ?>" value="<?php echo $showposts; ?>" type="text" size="3" /> <small class="description">Number of posts to show per page.</small><br />
			<label for="<?php echo $this->get_field_id('post_parent'); ?>">Post Parent:</label> <input id="<?php echo $this->get_field_id('post_parent'); ?>" name="<?php echo $this->get_field_name('post_parent'); ?>" value="<?php echo $post_parent; ?>" type="text" size="3" /> <small class="description">Only retrieve children of this ID.</small></p>
		</fieldset>
		
		<fieldset class="setting_group toggle closed">
			<legend class="handle"><span class="widget-top">Sorting<a class="widget-action hide-if-no-js" href="#"></a></span></legend>
			<div class="content">
				<p><label for="<?php echo $this->get_field_id('orderby'); ?>">Order By:</label> 
				<select name="<?php echo $this->get_field_name('orderby'); ?>" id="<?php echo $this->get_field_id('orderby'); ?>">
					<?php xf_display_Renderables::buildSelectOptions( array(
						'Not Set' => '',
						'Date' => 'date',
						'None' => 'none',
						'Author' => 'author',
						'Title' => 'title',
						'Modified' => 'modified',
						'Menu Order' => 'menu_order',
						'Parent' => 'parent',
						'ID' => 'ID',
						'Random' => 'rand',
						'Custom Field' => 'meta_value',
					),
					$orderby ); ?> 
				</select>
				<label for="<?php echo $this->get_field_id('order'); ?>">Order:</label> 
				<select name="<?php echo $this->get_field_name('order'); ?>" id="<?php echo $this->get_field_id('order'); ?>">
					<?php xf_display_Renderables::buildSelectOptions( array(
						'Not Set' => '',
						'Ascending' => 'ASC',
						'Descending' => 'DESC'
					),
					$order ); ?>
				</select><br />
				<small class="description">Custom Field sorting requires Custom Field Data.</small>
			</div>
		</fieldset>
		
		<fieldset class="setting_group toggle closed">
			<legend class="handle"><span class="widget-top">Custom Field Data<a class="widget-action hide-if-no-js" href="#"></a></span></legend>
			<div class="content">
				<p><label for="<?php echo $this->get_field_id('meta_key'); ?>">Has Key:</label> <input id="<?php echo $this->get_field_id('meta_key'); ?>" name="<?php echo $this->get_field_name('meta_key'); ?>" value="<?php echo $meta_key; ?>" type="text" size="8" /> <label for="<?php echo $this->get_field_id('meta_value'); ?>">Has Value:</label> <input id="<?php echo $this->get_field_id('meta_value'); ?>" name="<?php echo $this->get_field_name('meta_value'); ?>" value="<?php echo $meta_value; ?>" type="text" size="8" /></p>
				
				<p><label for="<?php echo $this->get_field_id('meta_compare'); ?>">Compare:</label> 
				<select name="<?php echo $this->get_field_name('meta_compare'); ?>" id="<?php echo $this->get_field_id('meta_compare'); ?>">
					<?php xf_display_Renderables::buildSelectOptions( array(
						'Default' => '',
						'Not Equal (!=)' => '!=',
						'Greater Than (>)' => '>',
						'Greater Than or Equal (>=)' => '>=',
						'Less Than (<)' => '<',
						'Less Than or Equal (<=)' => '<='
					),
					$meta_compare ); ?>
				</select></p>
			</div>
		</fieldset>
	
	</div>
</fieldset>

<fieldset class="setting_group toggle closed">
	<legend class="handle"><span class="widget-top">Specific Parameters<a class="widget-action hide-if-no-js" href="#"></a></span></legend>
	
	<div class="content">
		<p><small class="description">For multiple entries use comma. (ex: 1,3,7)</small></p>
		
		<table width="100%" cellpadding="0" cellspacing="0"><tr>
		<td width="50%">
			<p><label for="<?php echo $this->get_field_id('post__in'); ?>">Entry IDs to Include:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('post__in'); ?>" name="<?php echo $this->get_field_name('post__in'); ?>" value="<?php echo $post__in; ?>" type="text" size="3" /></p>
		</td>
		<td width="50%">
			<p><label for="<?php echo $this->get_field_id('post__not_in'); ?>">Entry IDs to Exclude:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('post__not_in'); ?>" name="<?php echo $this->get_field_name('post__not_in'); ?>" value="<?php echo $post__not_in; ?>" type="text" size="3" /></p>
			
		</td>
		</tr></table>
	</div>
</fieldset>