;(function($){
	// start plugin
	$.ajaxForms = {
		// start vars
		defaults: {
			data:				{},						// optional variables for get
			dataType:			'json'					// type of data for request to be parsed as
			ajaxClass:			'ajax',					// class of ajax request elements
			loaderClass:		'ajax-loader',			// class of loader elements
			loaderContent:		'<span class="ajax-loader-text>Loading...</span>', // optional content of loader
			animateIn:			{opacity:'show'},
			animateOut:			{opacity:0},
			speedIn:			'fast',
			speedOut:			'fast'
		},
		_hashes:{},
		_requests:{},
		// end vars
		// start methods
		parseURL: function(url) {
			var a =  document.createElement('a');
			a.href = url;
			return {
			    source: url,
			    protocol: a.protocol.replace(':',''),
			    host: a.hostname,
			    port: a.port,
			    query: a.search,
			    params: (function(){
			        var ret = {},
			            seg = a.search.replace(/^\?/,'').split('&'),
			            len = seg.length, i = 0, s;
			        for (;i<len;i++) {
			            if (!seg[i]) { continue; }
			            s = seg[i].split('=');
			            ret[s[0]] = s[1];
			        }
			        return ret;
			    })(),
			    file: (a.pathname.match(/\/([^\/?#]+)$/i) || [,''])[1],
			    hash: a.hash.replace('#',''),
			    path: a.pathname.replace(/^([^\/])/,'/$1'),
			    relative: (a.href.match(/tp:\/\/[^\/]+(.+)/) || [,''])[1],
			    segments: a.pathname.replace(/^\//,'').split('/')
			};
		},
		init: function(element){
			$(element).find('a.'+$.ajaxForms.defaults.ajaxClass).each(function(i){
				var a = $(this).eq(0);
				if( a.hasClass('cancel') ) {
					a.click(function(){
						var rel = $(this).attr('rel');
						if( $(rel).length ) {
							$(rel).find('.content *').remove();
						}
						return false;
					});
					return;
				}
				var href = a.attr('href');
				if( href == '' || href.match(/^#/) ) return;
				href = href.replace(location.pathname.toString(),''); // make relative to current path
				var parsed = $.ajaxForms.parseURL(href);
				var search = new Array();
				for( var v in parsed.params ) {
					search.push( v+'='+parsed.params[v] );
				}
				var id = parsed.segments.join('-')+'-'+search.join('-');
				var hash = '#'+parsed.segments.join('/')+search.join('/');
				$.ajaxForms._hashes[hash] = a.attr('href');
				a.attr({href:hash,id:id});
				var rel = a.attr('rel');
				if( !$(rel).length || a.attr('rel') == '' ) {
					rel = ':parent';
					a.attr('rel',rel);
				}
				if( typeof $.ajaxForms._requests[rel] == 'undefined' ) $.ajaxForms._requests[rel] = {};
				$(rel).css({position:'relative'});
				$(this).click(function(){
					if( typeof $.ajaxForms._hashes[a.attr('href')] == 'undefined' ) return false;
					var uri = $.ajaxForms._hashes[a.attr('href')];
					if( typeof $.ajaxForms._requests[rel] != 'undefined' ) {
						var count = 0;
						for( req in $.ajaxForms._requests[rel] ) {
							$.ajaxForms._requests[rel][req].abort(); delete $.ajaxForms._requests[rel][req];
							count++;
						}
					}
					var oc = $(rel).find('.content');
					if(!count) {
						oc.animate($.ajaxForms.defaults.animateOut,$.ajaxForms.defaults.speedOut);
						$(rel).append('<div class="'+$.ajaxForms.defaults.loaderClass+'">'+$.ajaxForms.defaults.loaderContent+'</div>');
					}
					var loader = $(rel).find('.'+$.ajaxForms.defaults.loaderClass);
					var d = {};
					
					$.ajaxForms._requests[rel][uri] = $.ajax({
						type: form.attr('method'),
						data: d,
						dataType: $.ajaxForms.defaults.dataType,
						url: form.attr('action'),
						success: function( html ) {
							html = $.trim( html );
							alert( html );
						},
						complete: function(){
							loader.remove();
						}
					});
		
					$.ajaxForms._requests[rel][uri] = $.get(uri,d,function(data) {
						var html = $.trim(data.html);
						oc.removeClass('content');
						$(rel).append('<div class="content">'+html+'</div>');
						var nc = $(rel).find('.content');
						nc.hide();
						var load = nc.find('img:last').eq(0);
						var onLoad = function(){
							oc.remove();
							nc.animate($.ajaxForms.defaults.animateIn,$.ajaxForms.defaults.speedIn,function(){ delete $.ajaxForms._requests[rel][uri]; });
							loader.remove();
							$.ajaxForms.init(nc);
						};
						if( typeof load.attr('src') == 'undefined' ) {
							onLoad();
						} else {
							load.load(onLoad);
						}
					},'json');
					return false;
				});
			});
		}
		// end methods
	}
	// end plugin
})(jQuery);