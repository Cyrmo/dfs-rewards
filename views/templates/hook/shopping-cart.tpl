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
<div id="reward_loyalty">
	{if $display_credits > 0}
		{l s='By checking out this shopping cart you will collect ' mod='allinone_rewards'} <b>{$credits|escape:'html':'UTF-8'}</b>
		{l s='that can be converted into a voucher for a future purchase.' mod='allinone_rewards'}{if isset($guest_checkout) && $guest_checkout}<sup>*</sup>{/if}<br />
		{if isset($guest_checkout) && $guest_checkout}<span><sup>*</sup> {l s='Not available for Instant checkout order' mod='allinone_rewards'}</span>{/if}
	{else}
		{l s='Add some products to your shopping cart to collect some loyalty credits.' mod='allinone_rewards'}
	{/if}
</div>
<!-- END : MODULE allinone_rewards -->