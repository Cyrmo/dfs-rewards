{*
* All-in-one Rewards Module
*
* @category  Prestashop
* @category  Module
* @author    Yann BONNAILLIE - ByWEB
* @copyright 2012-2020 Yann BONNAILLIE - ByWEB (http://www.prestaplugins.com)
* @license   Commercial license see license.txt
* Support by mail  : contact@prestaplugins.com
* Support on forum : Patanock
* Support on Skype : Patanock13
*}

{if isset($aior_product_price_real) || !$aior_show_buy_button}
<!-- MODULE allinone_rewards -->
<script>
	/* need to be done here and not globally because of blocklayered not reloading JS */
	jQuery(function($){
	{if isset($aior_product_price_real)}
		if ($('#aior_add_to_cart_available_real').length > 0 && {$aior_product_price_real} <= $('#aior_add_to_cart_available_real').html()) {
			$('a.ajax_add_to_cart_button[data-id-product={$aior_id_product|intval}], a.ajax_add_to_cart_button[rel=ajax_id_product_{$aior_id_product|intval}]').each(function() {
				if ($(this).parent().find('a.aior_add_to_cart').length == 0)
					$(this).after('<a href="#" class="aior_add_to_cart button {if version_compare($smarty.const._PS_VERSION_,'1.6','>=')}button-medium exclusive{/if}" data-id-product="{$aior_id_product|intval}" data-id-product-attribute="{$aior_id_product_attribute|intval}" data-aior-product-price-display="{$aior_product_price_display|escape:'htmlall':'UTF-8'}" data-aior-product-price-real="{$aior_product_price_real|floatval}"><span>{l s='Buy with my rewards' mod='allinone_rewards'}</span></a>');
			});
		}
	{/if}
	{if !$aior_show_buy_button}
		$('a.ajax_add_to_cart_button[data-id-product={$aior_id_product|intval}], a.ajax_add_to_cart_button[rel=ajax_id_product_{$aior_id_product|intval}]').hide();
	{/if}
	});
</script>
<!-- END : MODULE allinone_rewards -->
{/if}