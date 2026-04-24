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
{if $sback==1}
<div style="font-size: 12px; font-family: Arial; padding-bottom: 10px; text-align: left"><a style="color: #000000" href="#" onClick="return parent.openPopup(true)">« {l s='Back' mod='allinone_rewards'}</a></div>
{/if}
<div id="sponsorship_rules">
	<div class="rte">{$rules|escape:'string':'UTF-8'}</div>
</div>
