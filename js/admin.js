/**
 * jQuery Extensible Widgets Admin
 *
 * @package Extensible Widgets
 * @contributors Jim Isaacs
 */
(function($) {
	/**
	 * jQuery.wpewAdmin
	 * 
	 * @contributors Jim Isaacs
	 */
	$.fn.wpewAdmin = function() {
		var ajaxOpts = {
			'buttons': 'button[type=submit], input[type=submit], input[type=image]',  // jQuery selector for buttons
			'data': null, // if set to a string will be appended to the sent data, if an object will be serialized using $.param()
			'replace': true,  // if set to true, will replace content in the update element, otherwise will just append
			'submit': { // events to be carried out onclick/submit
				'disable': {  // disable any inputs (form only)
					'selector': 'buttons',
					'className': 'disabled'
				},
				message :{ text: false },
				'waiting': { // if nothing happens after timeout * 1000 ms, update the message and re-enable the buttons
					'timeout': 10, // seconds
					'message': 'It looks like there was a problem with the request, please try again.',
					'className': 'error',
					callback: function() {
						$(this.update).children().css({opacity:1}).filter('.ajax-loader').remove();
						alert('It looks like there was a problem with the request, please try again.');
					}
				}
			},
			// jQuery AJAX options
			async: true,
			beforeSend: function() {
				$(this.update).css({position:'relative'}).children().css({opacity:0.5}).end().append('<div class="ajax-loader"><br /></div>');
			},
			complete: function(req, success) {
				//alert('Complete callback test');
				$(this.update).find('.ajax-loader').fadeOut('fast',function(){ $(this).remove() });
				$(this.update).hide().animate({opacity:'show'},'normal').wpewAdmin();
			},
			dataType: 'html'
		};
		$(this).find('form.ajaxify, a.ajaxify').ajaxify(ajaxOpts);
		var widget = $('#wpew-wrap .widget').eq(0); initControlTabs( widget ); initFadeOut( widget ); 
		// All little overboard for now...
		/*var navOpts = $.extend({},ajaxOpts);
		navOpts.update = '#wpew-wrap';
		navOpts.complete = function(req, success) {
			$(this.update).find('.ajax-loader').fadeOut('fast',function(){ $(this).remove() });
			$(this.update).hide().animate({opacity:'show'},'normal').wpewAdmin();
		};
		$(this).find('a.wpew-navigation').ajaxify(navOpts).bind('success',function(){
			$('a.wpew-navigation.current, #adminmenu li.current').removeClass('current');
			var current = $(document).find('a.wpew-navigation[href$="'+$(this).attr('href')+'"]');
			current.each(function(i){
				if( !$(this).parent().hasClass('wp-has-submenu') && !$(this).parent().hasClass('wp-menu-image') ) {
					$(this).addClass('current');
					$(this).parent().addClass('current');
				}
			});
		});*/
		// return the jQuery object for chaining
		return this;
	}
})(jQuery);

jQuery(function($) {
	if( $('.toplevel_page_extensible-widgets.wp-has-current-submenu').size() ) {
		//$('#adminmenu').find('a[href*="wpew_admin_"]').addClass('wpew-navigation');
		$(document).wpewAdmin();
	}
});