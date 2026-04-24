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
	if (typeof(url_allinone_sponsorship) != "undefined") {
		if (window.location.href.indexOf('http://')===0) {
			url_allinone_sponsorship = url_allinone_sponsorship.replace('https://','http://');
	    } else {
			url_allinone_sponsorship = url_allinone_sponsorship.replace('http://','https://');
	    }
	}

	$('#sponsorship_link a').fancybox();

	if ($('#sponsorship_popup').size() > 0)
		openPopup();

	if ($('#rewards_sponsorship').length > 0)
		initRewards();
});

function openPopup(skeepStep) {
	var scheduled = $('#sponsorship_popup').hasClass('scheduled') ? '1' : '0';
	$.ajax({
		type	: "POST",
		cache	: false,
		url		: url_allinone_sponsorship,
		dataType: "html",
		data 	: "popup=1&scheduled=" + scheduled,
		success : function(data) {
			fancybox(data);
			if (skeepStep) {
				$('#sponsorship_text').hide();
				$('#sponsorship_form').show();
			}
		}
	});
	return false;
}

function initRewards() {
	// utile pour order-confirmation et sponsorship.php
	$('#invite').click(function(){
		$('#sponsorship_text').hide();
		$('#sponsorship_form').show();
		$.fancybox.resize();
	});

	$('#noinvite').click(function(){
		$.fancybox.close();
	});

	$('a.rules, a.mail').fancybox({
		'titleShow' : false
	});

	$('#list_contacts_form').submit(function() {
		return submitForm($(this));
	});
}

function acceptSponsorshipCGV(form) {
	if (!$('input.cgv:checked', $(form)).length) {
		alert(msg);
		return false;
	}
	return true;
}

function submitForm(form) {
	if ($('#sponsorship_popup').size() > 0) {
		if (acceptSponsorshipCGV($(form))) {
			var scheduled = $('#sponsorship_popup').hasClass('scheduled') ? '1' : '0';
			$.fancybox.showActivity();
			$.ajax({
				type	: "POST",
				cache	: false,
				url		: url_allinone_sponsorship,
				data	: $(form).serialize() + "&popup=1&scheduled=" + scheduled,
				dataType: "html",
				success : function(data) {
					fancybox(data);
				}
			});
		}
		return false;
	} else
		return acceptSponsorshipCGV($(form));
}

function fancybox(data) {
	$.fancybox(
	[
		{
			'content'			: data,
			'enableEscapeButton': false,
			'onComplete': function() {
				initRewards();
			}
		}
	],
	{
		'autoDimensions'	: true,
		'hideOnContentClick': false,
		'hideOnOverlayClick': false,
		'titleShow'			: false,
		'showNavArrows'		: false
	});
}

function checkAll() {
	if ($('#checkall').attr('checked'))
		$('#checkall').parents('table.std').find(':checkbox').attr('checked', true);
	else
		$('#checkall').parents('table.std').find(':checkbox').attr('checked', false);
}