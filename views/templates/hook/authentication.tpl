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
<script>
	var ps_version = '{$smarty.const._PS_VERSION_}';
	var img_path = '{$module_template_dir|escape:'html':'UTF-8'}';
	var error_sponsor = '{l s='This sponsor does not exist' mod='allinone_rewards' js=1}';
	var url_allinone_sponsorship = "{$link->getModuleLink('allinone_rewards', 'sponsorship', [], true)|escape:'javascript':'UTF-8'}";
	// appelé ici plutôt que dans le fichier JS, sinon ne marche pas en 1.5 et 1.6 car le form est chargé en ajax
	jQuery(function($){
		initSponsorshipJS();
	});
</script>
<fieldset class="account_creation aior_sponsor">
	<h3 class="page-subheading">{l s='Sponsorship program' mod='allinone_rewards'}</h3>
	<p class="text form-group">
		<label>{l s='Code or E-mail address of your sponsor' mod='allinone_rewards'}</label>
		<input type="text" size="52" maxlength="128" autocomplete="off" class="form-control text" id="sponsorship" name="sponsorship" value="{if isset($smarty.post.sponsorship)}{$smarty.post.sponsorship|escape:'htmlall':'UTF-8'}{/if}" />
	</p>
</fieldset>
<!-- END : MODULE allinone_rewards -->