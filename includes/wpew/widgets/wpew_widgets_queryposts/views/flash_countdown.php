<?php
/*
Template Name:Flash Countdown
*/
?>
<?php if (have_posts()) :		
	while (have_posts()) : the_post(); 
		global $post;
		$end_jj = get_the_time('d'); // date
		$end_mm = get_the_time('m'); // month
		$end_aa = get_the_time('Y'); // year
		$end_hh = get_the_time('H'); // hours
		$end_mn = get_the_time('i'); // minutes
		$end_ss = get_the_time('s'); // seconds
	?>
	<script type="text/javascript">
	<!--
	var flashvars = {
		endDay: "<?php echo $end_jj; ?>",
		endMonth: "<?php echo $end_mm-1; // minus 1 because flash counts months from ZERO ?>",
		endYear: "<?php echo $end_aa; ?>",
		endHour: "<?php echo $end_hh; ?>",
		endMinute: "<?php echo $end_mn; ?>",
		endSecond: "<?php echo $end_ss; ?>"
	};
	var attributes = {id:"<?php echo $this->id; ?>-<?php echo $post->ID; ?>-flash"};
	var params = {
		allowScriptAccess: "true",
		wmode: "transparent"
	};
	swfobject.embedSWF("<?php bloginfo('stylesheet_directory'); ?>/swf/countdown_widget.swf","<?php echo $this->id; ?>-<?php echo $post->ID; ?>-replace","230","137","9.0.0","<?php bloginfo('stylesheet_directory'); ?>/swf/expressInstall.swf",flashvars, params, attributes);
	-->
	</script>
	<div id="<?php echo $this->id; ?>-<?php echo $post->ID; ?>-replace">Scheduled: <?php the_time('F jS, Y h:i:s') ?></div>
	<?php edit_post_link('Edit this entry.'); ?>
	<?php endwhile; ?>
<?php else : ?>
	<p class="center">No posts found.</p>
<?php endif; ?>