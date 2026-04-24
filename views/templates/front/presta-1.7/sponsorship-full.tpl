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

{extends file='customer/page.tpl'}

{block name='page_title'}
  	{l s='Sponsorship program' mod='allinone_rewards'}
{/block}

{block name='page_content'}
	{include file='file:modules/allinone_rewards/views/templates/front/presta-1.7/sponsorship.tpl'}
{/block}