(function(e,a){if(!a.__SV){var b=window;try{var c,l,i,j=b.location,g=j.hash;c=function(a,b){return(l=a.match(RegExp(b+"=([^&]*)")))?l[1]:null};g&&c(g,"state")&&(i=JSON.parse(decodeURIComponent(c(g,"state"))),"mpeditor"===i.action&&(b.sessionStorage.setItem("_mpcehash",g),history.replaceState(i.desiredHash||"",e.title,j.pathname+j.search)))}catch(m){}var k,h;window.mixpanel=a;a._i=[];a.init=function(b,c,f){function e(b,a){var c=a.split(".");2==c.length&&(b=b[c[0]],a=c[1]);b[a]=function(){b.push([a].concat(Array.prototype.slice.call(arguments,
0)))}}var d=a;"undefined"!==typeof f?d=a[f]=[]:f="mixpanel";d.people=d.people||[];d.toString=function(b){var a="mixpanel";"mixpanel"!==f&&(a+="."+f);b||(a+=" (stub)");return a};d.people.toString=function(){return d.toString(1)+".people (stub)"};k="disable time_event track track_pageview track_links track_forms register register_once alias unregister identify name_tag set_config reset people.set people.set_once people.increment people.append people.union people.track_charge people.clear_charges people.delete_user".split(" ");
for(h=0;h<k.length;h++)e(d,k[h]);a._i.push([b,c,f])};a.__SV=1.2;b=e.createElement("script");b.type="text/javascript";b.async=!0;b.src="undefined"!==typeof MIXPANEL_CUSTOM_LIB_URL?MIXPANEL_CUSTOM_LIB_URL:"file:"===e.location.protocol&&"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js".match(/^\/\//)?"https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js":"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";c=e.getElementsByTagName("script")[0];c.parentNode.insertBefore(b,c)}})(document,window.mixpanel||[]);
mixpanel.init("3cc36628768f1f84d4c6980d09ced036");
jQuery(document).ready(function(){
	if(typeof(mixpanel) !== 'undefined') {
		// Main tabs navigation
		var tabNames = {
			'ppsPopupMainOpts': 'Main'
		,	'ppsPopupTpl': 'Design'
		,	'ppsPopupEditors': 'CSS / HTML Code'
		,	'ppsPopupSubscribe': 'Subscribe'
		,	'ppsPopupContactForm': 'Contact'
		,	'ppsPopupStatistics': 'Statistics'
		// ,	'ppsPopupAbTesting': 'A/B Testing'
		,	'ppsLoginRegister': 'Login/Registration'
		};
		var $manTabsNav = jQuery('#ppsPopupEditTabs .ppsMainTabsNav');
		var getTabName = function( $tab ) {
			if($tab && $tab.length) {
				var href = $tab.attr('href')
				,	name = '';
				if(href) {
					var id = href.substr( 1 );	// Remove "#" symbol
					if( tabNames[ id ] ) {
						name = tabNames[ id ];
					}
				}
				if( !name ) {
					name = $tab.find('.ppsPopupTabTitle').text()
				}
				return name;
			}
			return 'Not Detected';
		};
		mixpanel.track('PopUp Edit Tab', {
			'Active Tab': getTabName( $manTabsNav.find('.nav-tab-active') )
		});
		$manTabsNav.find('.nav-tab').click(function(){
			mixpanel.track('PopUp Edit Tab', {
				'Active Tab': getTabName( jQuery(this) )
			});
		});
		// Main opts stats collection
		_ppsBigDataMainOpt('params[main][show_on]', 'When to show PopUp');
		_ppsBigDataMainOpt('params[main][close_on]', 'When to close PopUp');
		_ppsBigDataMainOpt('params[main][show_pages]', 'Show on next pages');
		_ppsBigDataMainOpt('params[main][show_to]', 'Whom to show');
		
	}
});
function _ppsBigDataMainOpt( inpName, label ) {
	var $inp = jQuery('#ppsPopupEditForm [name="'+ inpName+ '"]');
	mixpanel.track(label, {
		'Selected Opt': __ppsBigDataOptToLabel( $inp.filter(':checked') )
	});
	// Avoid auto startup changes trigger collection
	setTimeout(function(){
		$inp.change(function(){
			var $this = jQuery(this);
			if($this.prop('checked')) {
				mixpanel.track(label, {
					'Selected Opt': __ppsBigDataOptToLabel( $this )
				});
			}
		});
	}, 500);
}
function __ppsBigDataOptToLabel( $opt ) {
	var $label = $opt.parents('label:first');
	if(!$label || !$label.length) {
		$label = $opt.parents('.ppsPopupMainOptLbl:first');
	}
	if($label && $label.length) {
		var optName = $label.data('name');
		if(!optName) {
			optName = $label.text();
		}
		return jQuery.trim( optName );
	}
	return 'Not Detected';
}