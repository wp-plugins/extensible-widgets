<?php
/**
 * This file defines wpew_widgets_QueryPosts, an Extensible Widget class.
 * 
 * PHP version 5
 * 
 * @package wpew
 * @subpackage widgets
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @link http://jidd.jimisaacs.com
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * This is an example of a widget that used the previous widget's functionality, 
 * but is still higher up in the inheritance tree. A Widget than can create and 
 * use a sub-query or use the current global query and output the results in 
 * a view template.
 *
 * @package wpew
 * @subpackage widgets
 */

// START class
class wpew_widgets_QueryPosts extends wpew_widgets_View {
	
	// STATIC MEMBERS
	
	/**
	 * @see wpew_AWidget::$tabLabel
	 */
	public static $tabLabel = 'Query Posts';
	
	/**
	 * @var WP_Query $_globalQuery Holds the global $wp_query temporarily while this widget renders. 
	 */
	private static $_globalQuery = null;
	
	/**
	 * @see wpew_AWidget::getDefaultSettings()
	 */
	public static function getDefaultSettings( &$obj ) {
		return array( 
			'query' => array(
				'post_status' => 'publish'
			)
		);
	}
	
	/**
	 * @see wpew_IWidget::save()
	 */
	public static function save( &$obj, $new_settings ) {
		// Check if this was serialized
		if( wpew_Widgets::isSerialized( 'query', $new_settings ) ) {
			wpew_Widgets::unserialize( 'query', $new_settings );
			$vars = $new_settings['query'];
		} else {
			parse_str( $new_settings['query'], $vars );
		}
		$obj->settings['query'] = array_filter( $vars );
	}
	
	// INSTANCE MEMBERS
	
	// CONSTRUCTOR
	public function __construct( $name = '', $wOpts = array(), $cOpts = array() )
	{
		// Set Name
		if( empty( $name ) ) $name = __('Query Posts');
		// Set Options
		$wOpts = wp_parse_args( $wOpts, array(
			'description' => "A Widget than can create and use a sub-query or use the current global query and output the results in a view template."
		) );
		$cOpts = wp_parse_args( $cOpts, array(
			'width' => 300
		) );
		// parent constructor
		parent::__construct( $name, $wOpts, $cOpts );
	}
	
	/**
	 * @see wpew_IWidget::beforeOutput()
	 */
	public function beforeOutput() {
		// call parent
		parent::beforeOutput();
		if( empty( $this->settings['query'] ) ) return;
		// Save the global $wp_query to restore later
		global $wp_the_query, $wp_query;
		if(is_null(self::$_globalQuery)) self::$_globalQuery = $wp_the_query;
		// Create the new Query!
		$wp_the_query = new WP_Query( $this->settings['query'] );
		$wp_query =& $wp_the_query;
	}
	
	/**
	 * @see wpew_widgets_IView::defaultView()
	 */
	public function defaultView() {
		// START DEFAULT ?>
		<?php if (have_posts()) : ?>
	
			<?php while (have_posts()) : the_post(); ?>
	
			<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
				<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
				<small><?php the_time('F jS, Y') ?> by <?php the_author_posts_link() ?></small>
										
				<div class="entry">
					<?php the_content('Read the rest of this entry &raquo;'); ?>
				</div>
	
				<p class="postmetadata"><?php the_tags('Tags: ', ', ', '<br />'); ?> Posted in <?php the_category(', ') ?> | <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?></p>
			</div>
			<?php edit_post_link('Edit this entry.'); ?>
				
			<?php endwhile; ?>
			
		<?php else : ?>
	
			<h2 class="center">Not Found</h2>
			<p class="center">Sorry, but you are looking for something that isn't here.</p>
			<?php get_search_form(); ?>
	
		<?php endif;
		// END DEFAULT
	}
	
	/**
	 * @see wpew_IWidget::afterOutput()
	 */
	public function afterOutput() {
		if( !is_null( self::$_globalQuery ) ) {
			// Restore global $wp_query
			global $wp_the_query, $wp_query;
			$wp_the_query = self::$_globalQuery;
			$wp_query =& $wp_the_query;
			self::$_globalQuery = null;
		}
		// Restore global post data stomped by the_post()
		wp_reset_query();
	}
}
// END class
?>