<?php

require_once(dirname(__FILE__).'/../../xf/display/Renderables.php');
require_once('QueryPosts.php');

/**
 * wpew_widgets_QueryPostsExtended
 * 
 * This is an example of a widget that used the previous widget's functionality, but is still higher up in the inheritance tree.
 * This widget is a simplified version of the Query Posts widget, for beginners.
 * @package wpew
 * @subpackage widgets
 */

// START class
class wpew_widgets_QueryPostsExtended extends wpew_widgets_QueryPosts {
	
	// STATIC MEMBERS
	
	/**
	 * @see wpew_AWidget::$tabLabel
	 */
	public static $tabLabel = 'QP Extended';
	
	/**
	 * @see wpew_AWidget::getDefaultSettings()
	 */
	public static function getDefaultSettings( &$obj ) {
		return array( 'query' => array() );
	}
	
	/**
	 * @see wpew_IWidget::save()
	 */
	public static function save( &$obj, $new_settings ) {
		// Check if this was serialized
		// Also this works a bit differently to, we don't want to run this function is the the form was rendered
		if( wpew_Widgets::isSerialized( 'query', $new_settings ) && !isset($new_settings['post_type']) ) return;
		// overwrite the query setting with the settings from the form
		$obj->settings['query'] = array_filter( wp_parse_args( array(
			'showposts' => (int) $new_settings['showposts'],
			'post_parent' => (int) $new_settings['post_parent'],
			'post_type' => $new_settings['post_type'],
			'post_status' => $new_settings['post_status'],
			'orderby' => $new_settings['orderby'],
			'order' => $new_settings['order'],
			'meta_key' => $new_settings['meta_key'],
			'meta_value' => $new_settings['meta_value'],
			'meta_compare' => $new_settings['meta_compare'],
			'post__in' => array_filter( split( ',', $new_settings['post__in'] ) ),
			'post__not_in' => array_filter( split( ',', $new_settings['post__not_in'] ) )
		), $obj->settings['query'] ) );
	}
	
	/**
	 * @see wpew_IWidget::renderAdmin()
	 */
	public static function renderAdmin( &$obj ) {
		extract( $obj->settings['query'] ); 
		if( isset( $post__in ) ) $post__in = implode(',', $post__in);
		if( isset( $post__not_in ) )  $post__not_in = implode(',', $post__not_in); ?>
		
		<p><small class="description">No field is required except when certain fields are set to certain values, these should be common sense. Leave any field blank for the default value. Leave all fields blank to use the global query set by WordPress.</small></p>
			
		<fieldset class="setting_group toggle">
			<legend class="handle"><span class="widget-top">General Parameters<a class="widget-action hide-if-no-js" href="#"></a></span></legend>
			
			<div class="content">
			
				<fieldset class="setting_group">
					<p><small>Status of Inherit pertains to attachments and revisions:</small></p>
					<p><label for="<?php echo $obj->get_field_id('post_status'); ?>">Status:</label> 
					<select name="<?php echo $obj->get_field_name('post_status'); ?>" id="<?php echo $obj->get_field_id('post_status'); ?>">
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
					<label for="<?php echo $obj->get_field_id('post_type'); ?>">Type:</label> 
					<select name="<?php echo $obj->get_field_name('post_type'); ?>" id="<?php echo $obj->get_field_id('post_type'); ?>">
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
				
					<p><label for="<?php echo $obj->get_field_id('showposts'); ?>">Show Posts:</label> <input id="<?php echo $obj->get_field_id('showposts'); ?>" name="<?php echo $obj->get_field_name('showposts'); ?>" value="<?php echo $showposts; ?>" type="text" size="3" /> <small class="description">Number of posts to show per page.</small><br />
					<label for="<?php echo $obj->get_field_id('post_parent'); ?>">Post Parent:</label> <input id="<?php echo $obj->get_field_id('post_parent'); ?>" name="<?php echo $obj->get_field_name('post_parent'); ?>" value="<?php echo $post_parent; ?>" type="text" size="3" /> <small class="description">Only retrieve children of this ID.</small></p>
				</fieldset>
				
				<fieldset class="setting_group toggle closed">
					<legend class="handle"><span class="widget-top">Sorting<a class="widget-action hide-if-no-js" href="#"></a></span></legend>
					<div class="content">
						<p><label for="<?php echo $obj->get_field_id('orderby'); ?>">Order By:</label> 
						<select name="<?php echo $obj->get_field_name('orderby'); ?>" id="<?php echo $obj->get_field_id('orderby'); ?>">
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
						<label for="<?php echo $obj->get_field_id('order'); ?>">Order:</label> 
						<select name="<?php echo $obj->get_field_name('order'); ?>" id="<?php echo $obj->get_field_id('order'); ?>">
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
						<p><label for="<?php echo $obj->get_field_id('meta_key'); ?>">Has Key:</label> <input id="<?php echo $obj->get_field_id('meta_key'); ?>" name="<?php echo $obj->get_field_name('meta_key'); ?>" value="<?php echo $meta_key; ?>" type="text" size="8" /> <label for="<?php echo $obj->get_field_id('meta_value'); ?>">Has Value:</label> <input id="<?php echo $obj->get_field_id('meta_value'); ?>" name="<?php echo $obj->get_field_name('meta_value'); ?>" value="<?php echo $meta_value; ?>" type="text" size="8" /></p>
						
						<p><label for="<?php echo $obj->get_field_id('meta_compare'); ?>">Compare:</label> 
						<select name="<?php echo $obj->get_field_name('meta_compare'); ?>" id="<?php echo $obj->get_field_id('meta_compare'); ?>">
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
					<p><label for="<?php echo $obj->get_field_id('post__in'); ?>">Entry IDs to Include:</label>
					<input class="widefat" id="<?php echo $obj->get_field_id('post__in'); ?>" name="<?php echo $obj->get_field_name('post__in'); ?>" value="<?php echo $post__in; ?>" type="text" size="3" /></p>
				</td>
				<td width="50%">
					<p><label for="<?php echo $obj->get_field_id('post__not_in'); ?>">Entry IDs to Exclude:</label>
					<input class="widefat" id="<?php echo $obj->get_field_id('post__not_in'); ?>" name="<?php echo $obj->get_field_name('post__not_in'); ?>" value="<?php echo $post__not_in; ?>" type="text" size="3" /></p>
					
				</td>
				</tr></table>
			</div>
		</fieldset>
				
	<?php }
	
	// INSTANCE MEMBERS
	
	// CONSTRUCTOR
	public function __construct( $name = '', $wOpts = array(), $cOpts = array() )
	{
		// Set Name
		if( empty( $name ) ) $name = __('QP Extended');
		// Set Options
		$wOpts = wp_parse_args( $wOpts, array(
			'description' => "This is an extended version of the Query Posts widget with a controlled form."
		) );
		$cOpts = wp_parse_args( $wOpts, array(
			'width' => 350
		) );
		// parent constructor
		parent::__construct( $name, $wOpts, $cOpts );
	}
}
// END class
?>