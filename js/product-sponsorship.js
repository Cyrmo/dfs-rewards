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
	var clipboard = new ClipboardJS('#sponsorship_product .btn');
	clipboard.on('success', function(e) {
		$.fancybox.close(true);
	});

	clipboard.on('error', function(e) {
	    $.fancybox.close(true);
	});
});