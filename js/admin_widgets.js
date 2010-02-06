function initFadeOut( elmt ) {
	if( typeof elmt == 'undefined' ) elmt = jQuery(document);
	window.setTimeout( function() {
		elmt.find('.fadeOut').animate({opacity:0}, 500,function(){ jQuery(this).remove(); });
	}, 1500 );
}
// This is global and as soon as this file is loaded, which is fine
var _controls = {};
if(window.location.hash) {
	var vars = window.location.hash.substring(1).split("&");
	for (var i=0;i<vars.length;i++) { 
		var pair = vars[i].split("="); 
		if (pair && pair.length > 1) _controls[pair[0]] = {current: pair[1]};
	}
}
// Called from the jQuery block below this, and from each active widget on the page
function initControlTabs( w ) {
	var lwid = w.attr('id');
	if( !lwid || lwid.match('__i__')) return false;				
	if( typeof(_controls[lwid]) == 'object' ) {
		var last = _controls[lwid].current;
	} else {
		var last = 0;
		_controls[lwid] = {current:last};
	}
	_controls[lwid].tabs = new Array();
	w.find('a.tabButton').each(function(i){
		var pattern = new RegExp(/^tab\[(.+)\]$/);
		var matches = jQuery(this).attr('rel').match(pattern);
		if( matches && matches.length > 1 ) {
			var tabContent = w.find('.tabs-panel.'+matches[1]);
			_controls[lwid].tabs.push({
				clicked: false,
				content: tabContent
			});
			jQuery(this).click(function(){
				var index = w.find('a.tabButton').index(this);
				if( _controls[lwid].current == index ) return false;
				_controls[lwid].clicked = true;
				jQuery(this).addClass('button-primary');
				w.find('a.tabButton').eq(_controls[lwid].current).removeClass('button-primary');
				_controls[lwid].tabs[_controls[lwid].current].content.hide();
				_controls[lwid].current = index;
				_controls[lwid].tabs[index].content.fadeIn();
				var pairs = new Array();
				for ( var id in _controls ) {
					if(_controls[id].clicked) pairs.push(id+'='+_controls[id].current);
				}
				document.location.hash = pairs.join('&');
				return false;
			});
		}
	});
	w.find('.tabs-panel').addClass('hidden').eq(last).show();
	w.find('a.tabButton').eq(last).addClass('button-primary');
	// For forms with presets dropdown
	w.find('select.wpew_presets').each(function(i) {
		var s = jQuery(this);
		var form = s.parents().filter('form');
		form.find('input.wpew_presets').change(function() {
			s.find('option[selected=selected]').attr('selected','');
			s.find('option[value=*]').attr('selected','selected');
		});
	});
	// For forms with checkbox/radio relationships
	w.find('input.wpew_relcheck, select.wpew_relcheck').each(function(i) {
		var field = jQuery(this);
		var fieldElm = this;
		var rel = jQuery('#'+field.attr('rel'));
		if( rel.attr('type') == 'checkbox' || rel.attr('type') == 'radio' ) {
			field.change(function(e) {
				e.currentTarget.defaultValue = e.currentTarget.value;
				if( e.currentTarget.value == '' && rel.attr('type') != 'radio' ) {
					rel.attr('checked', '' );
				} else {
					rel.attr('checked', 'checked');
				}
			});
			if( !field.hasClass('wpew_allowempty') ) {
				rel.change(function(e){
					if( e.currentTarget.checked == false ) {
						fieldElm.defaultValue = fieldElm.value;
						fieldElm.value = '';
					} else {
						fieldElm.value = fieldElm.defaultValue;
					}
				});
			}
		}
	});
	w.find('.toggle').each(function(i){
		var set = this;
		var content = jQuery(set).find('.content').eq(0);
		var handle = jQuery(set).find('.handle').eq(0);
		handle.add( handle.find('a') ).click(function(){
			if(jQuery(set).hasClass('closed')) {
				jQuery(set).removeClass('closed').addClass('opened');
				content.fadeIn();
			} else {
				jQuery(set).addClass('closed').removeClass('opened');
				content.hide();
			}
			return false;
		});
		if(jQuery(set).hasClass('closed')) content.hide();
	})
}
// This is for the drag and dropping widgets, it MUST be after the window loads
jQuery(function(){
	initFadeOut();
});