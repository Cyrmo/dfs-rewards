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

functions_to_load.push('loadLoyalty()');

function loadLoyalty() {
	if (typeof(url_allinone_loyalty) !== 'undefined') {
		$.ajax({
			type	: 'POST',
			cache	: false,
			url		: url_allinone_loyalty,
			dataType: 'html',
			data 	: 'id_product='+$('#product_page_product_id').val()+'&id_product_attribute='+aior_id_product_attribute,
			success : function(data) {
				if (data == '')
					$('#loyalty').hide().html('');
				else
					$('#loyalty').html(data).show();
			}
		});
	} else
		console.log('All-in-one Rewards : ERROR, url_allinone_loyalty is not initialized');
}