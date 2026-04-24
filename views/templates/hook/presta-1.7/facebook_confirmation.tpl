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
<div id="rewards_facebook_confirm">
	<div id="rewards_facebook_confirm_content">
		{$facebook_confirm_txt nofilter}
		{if $facebook_code}
		<center>{l s='Code :' mod='allinone_rewards'} <span id="rewards_facebook_code"></span></center>
		{/if}
	</div>
</div>
<script>
var url_facebook_api="//connect.facebook.net/{$facebook_language}/all.js#xfbml=1";
var url_allinone_facebook="{url entity='module' name='allinone_rewards' controller='facebook'}";
</script>
<!-- END : MODULE allinone_rewards -->