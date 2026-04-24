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
	$('.remove-from-cart[data-id-product='+aior_id_default_gift_product+']').hide();
	$('.remove-from-cart[data-id-product='+aior_id_default_gift_product+']').parents('.product-line-grid').find('.qty div').hide();
});