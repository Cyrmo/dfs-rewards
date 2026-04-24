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
<div id="sponsorship_link">
	<a class="fancybox.inline" href="#sponsorship_product"><i class="material-icons">&#xE80D;</i> {l s='Sponsor for this product' mod='allinone_rewards'}</a>
</div>
<div style="display:none">
	<div id="sponsorship_product">
		{l s='You can share the URL of this product with your sponsorship included.' mod='allinone_rewards'}<br/>
		{l s='Just copy / paste the following link :' mod='allinone_rewards'}<br/><br/>
		<span id="link_to_share">{$sponsorship_link}</span><br><br>
		<div class="btn btn-primary" data-clipboard-target="#link_to_share">{l s='Copy to clipboard' mod='allinone_rewards'}</div>
	</div>
</div>