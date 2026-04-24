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

<!-- MODULE allinone_rewards -->
<p id="aior_product_button" class="buttons_bottom_block no-print {if version_compare($smarty.const._PS_VERSION_,'1.6','>=')}version16{/if}">
	<a href="#" id="aior_add_to_cart" class="{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}button{else}version16{/if}" rel="nofollow" title="{l s='Buy with my rewards' mod='allinone_rewards'}">{l s='Buy with my rewards' mod='allinone_rewards'}</a>
	<span id="aior_add_to_cart_price"></span>
	<span id="aior_add_to_cart_available_after"></span>
</p>
<!-- END : MODULE allinone_rewards -->