/**
 * All-in-one Rewards Module
 *
 * @author    Yann BONNAILLIE - ByWEB
 * @copyright 2012-2020 Yann BONNAILLIE - ByWEB (http://www.prestaplugins.com)
 * @license   Commercial license see license.txt
 * @category  Module
 * Support by mail  : contact@prestaplugins.com
 * Support on forum : Patanock
 * Support on Skype : Patanock13
 */

jQuery(function($){
	if (window.location.href.indexOf('http://')===0) {
		url_allinone_facebook = url_allinone_facebook.replace('https://','http://');
    } else {
		url_allinone_facebook = url_allinone_facebook.replace('http://','https://');
    }

    // add facebook SDK if needed
	(function(d, s, id) {
		var js, fjs = d.getElementsByTagName(s)[0];
		if (d.getElementById(id)) return;
		js = d.createElement(s); js.id = id;
		js.src = url_facebook_api;
		fjs.parentNode.insertBefore(js, fjs);
	}(document, "script", "facebook-jssdk"));

	// init facebook SDK if needed
	if (!window.FB) {
		window.fbAsyncInit = function() {
			FB.init({
			  appId      : 'your-app-id',
			  xfbml      : true,
			  version    : 'v2.11'
			});

			FB.Event.subscribe('edge.create', function(response){
				likeFB(response, 1);
			});
			FB.Event.subscribe('edge.remove', function(response){
				likeFB(response, 0);
			});
		};
	} else {
		FB.Event.subscribe('edge.create', function(response){
			likeFB(response, 1);
		});
		FB.Event.subscribe('edge.remove', function(response){
			likeFB(response, 0);
		});
	}
});

function likeFB(url, like) {
	$.ajax({
		type: 'POST',
		url: url_allinone_facebook,
		async: true,
		cache: false,
		data: 'url=' + url + '&like=' + like,
		dataType: 'json',
		success: function(obj)	{
			if (obj && obj.result == 1) {
				if (obj.code) {
					$('#rewards_facebook_code').html(obj.code);
					if (typeof(ajaxCart) != 'undefined')
						ajaxCart.refresh();
				}
				$.fancybox({
					content	: $('#rewards_facebook_confirm').html(),
					// before presta 1.5.5.0
					enableEscapeButton: false,
					autoDimensions: true,
					hideOnContentClick: false,
					hideOnOverlayClick: false,
					titleShow: false,
					// since presta 1.5.5.0
					minHeight : 20,
					helpers : {
				        overlay : {
				            locked : true,
				            closeClick : false,
				        }
				    }
				});
				//$('.reward_facebook_block').hide();
			}
		}
	});
}