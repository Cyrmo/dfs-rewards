<?php
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

if (!defined('_PS_VERSION_'))
	exit;

require_once(_PS_MODULE_DIR_.'/allinone_rewards/plugins/RewardsGenericPlugin.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/models/RewardsModel.php');

class RewardsFacebookPlugin extends RewardsGenericPlugin
{
	public $name = 'facebook';

	public function checkWarning()
	{
		if (!(Module::isInstalled('statsdata') && ($mod=Module::getInstanceByName('statsdata')) && $mod->active == 1))
			$this->instance->warning = $this->l('To be able to use the facebook rewards you must enable the module named statsdata');
	}

	public function install()
	{
		// hooks
		if (!$this->registerHook('displayHeader') || !$this->registerHook('displayTop') || !$this->registerHook('displayLeftColumn')
			|| !$this->registerHook('displayRightColumn') || !$this->registerHook('displayFooter') || !$this->registerHook('displayShoppingCartFooter') || (version_compare(_PS_VERSION_, '1.7', '>=') && !$this->registerHook('displayBeforeBodyClosingTag'))
			|| !$this->registerHook('actionObjectCustomerDeleteAfter')
			|| (version_compare(_PS_VERSION_, '1.6.0.12', '>=') && !$this->registerHook('displayNav'))
			|| (version_compare(_PS_VERSION_, '1.7', '>=') && (!$this->registerHook('displayNav1') || !$this->registerHook('displayNav2'))))
			return false;

		$idEn = Language::getIdByIso('en');
		$details = array();
		$member_title_block = array();
		$guest_title_block = array();
		$member_txt_block = array();
		$guest_txt_block = array();
		$member_txt_cart = array();
		$guest_txt_cart = array();
		$member_txt_confirm = array();
		$guest_txt_confirm = array();
		foreach (Language::getLanguages() AS $language) {
			$tmp = $this->l('Facebook reward', (int)$language['id_lang']);
			$details[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('Facebook reward', $idEn);
			$tmp = $this->l('Join us on Facebook !', (int)$language['id_lang']);
			$member_title_block[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('Join us on Facebook !', $idEn);
			$tmp = $this->l('Join us on Facebook !', (int)$language['id_lang']);
			$guest_title_block[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('Join us on Facebook !', $idEn);
			$tmp = $this->l('member_txt_block', (int)$language['id_lang']);
			$member_txt_block[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('member_txt_block', $idEn);
			$tmp = $this->l('guest_txt_block', (int)$language['id_lang']);
			$guest_txt_block[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('guest_txt_block', $idEn);
			$tmp = $this->l('member_txt_cart', (int)$language['id_lang']);
			$member_txt_cart[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('member_txt_cart', $idEn);
			$tmp = $this->l('guest_txt_cart', (int)$language['id_lang']);
			$guest_txt_cart[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('guest_txt_cart', $idEn);
			$tmp = $this->l('member_txt_confirm', (int)$language['id_lang']);
			$member_txt_confirm[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('member_txt_confirm', $idEn);
			$tmp = $this->l('guest_txt_confirm', (int)$language['id_lang']);
			$guest_txt_confirm[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('guest_txt_confirm', $idEn);
		}

		if (!Configuration::updateValue('RFACEBOOK_ACTIVE', 0)
		|| !Configuration::updateValue('RFACEBOOK_HIDE_BLOCK', 0)
		|| !Configuration::updateValue('RFACEBOOK_HOOKS', 'top,leftblock,right,footer,shoppingcart')
		|| !Configuration::updateValue('RFACEBOOK_MEMBER_TITLE_BLOCK', $member_title_block)
		|| !Configuration::updateValue('RFACEBOOK_GUEST_TITLE_BLOCK', $guest_title_block)
		|| !Configuration::updateValue('RFACEBOOK_MEMBER_TXT_BLOCK', $member_txt_block)
		|| !Configuration::updateValue('RFACEBOOK_GUEST_TXT_BLOCK', $guest_txt_block)
		|| !Configuration::updateValue('RFACEBOOK_MEMBER_TXT_CART', $member_txt_cart)
		|| !Configuration::updateValue('RFACEBOOK_GUEST_TXT_CART', $guest_txt_cart)
		|| !Configuration::updateValue('RFACEBOOK_MEMBER_TXT_CONFIRM', $member_txt_confirm)
		|| !Configuration::updateValue('RFACEBOOK_GUEST_TXT_CONFIRM', $guest_txt_confirm)
		|| !Configuration::updateValue('RFACEBOOK_MAIL_LIKE', 1)
		|| !Configuration::updateValue('RFACEBOOK_MAIL_UNLIKE', 1)
		|| !Configuration::updateValue('RFACEBOOK_GUEST', 1)
		|| !Configuration::updateValue('RFACEBOOK_VOUCHER_DETAILS', $details)
		|| !Configuration::updateValue('RFACEBOOK_VOUCHER_PREFIX', 'FBK')
		|| !Configuration::updateValue('RFACEBOOK_VOUCHER_DURATION', 365)
		|| !Configuration::updateValue('RFACEBOOK_ALL_CATEGORIES', 1)
		|| !Configuration::updateValue('RFACEBOOK_VOUCHER_CATEGORIES', '')
		|| !Configuration::updateValue('RFACEBOOK_VOUCHER_TYPE', 2)
		|| !Configuration::updateValue('RFACEBOOK_VOUCHER_FREESHIPPING', 0)
		|| !Configuration::updateValue('RFACEBOOK_VOUCHER_VALUE', 1)
		|| !Configuration::updateValue('RFACEBOOK_VOUCHER_CUMUL', 1)
		|| !Configuration::updateValue('RFACEBOOK_VOUCHER_QUANTITY', 1)
		|| !Configuration::updateValue('RFACEBOOK_VOUCHER_BEHAVIOR', 0)
		|| !Configuration::updateValue('RFACEBOOK_VOUCHER_MIN_TAX', 1))
			return false;

		if (version_compare(_PS_VERSION_, '1.5.2', '<')) {
			Configuration::set('RFACEBOOK_MEMBER_TITLE_BLOCK', $member_title_block);
			Configuration::set('RFACEBOOK_GUEST_TITLE_BLOCK', $guest_title_block);
			Configuration::set('RFACEBOOK_MEMBER_TXT_BLOCK', $member_txt_block);
			Configuration::set('RFACEBOOK_GUEST_TXT_BLOCK', $guest_txt_block);
			Configuration::set('RFACEBOOK_MEMBER_TXT_CART', $member_txt_cart);
			Configuration::set('RFACEBOOK_GUEST_TXT_CART', $guest_txt_cart);
			Configuration::set('RFACEBOOK_MEMBER_TXT_CONFIRM', $member_txt_confirm);
			Configuration::set('RFACEBOOK_GUEST_TXT_CONFIRM', $guest_txt_confirm);
		}

		foreach ($this->instance->getCurrencies() as $currency) {
			Configuration::updateValue('RFACEBOOK_REWARD_VALUE_'.(int)($currency['id_currency']), 1);
			Configuration::updateValue('RFACEBOOK_VOUCHER_VALUE_'.(int)($currency['id_currency']), 1);
			Configuration::updateValue('RFACEBOOK_VOUCHER_MIN_VALUE_'.(int)($currency['id_currency']), 0);
		}

		// database
		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_facebook` (
			`id_facebook` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_guest` INT UNSIGNED NOT NULL,
			`id_customer` INT UNSIGNED NOT NULL,
			`id_cart_rule` INT UNSIGNED DEFAULT NULL,
			`id_reward` INT UNSIGNED DEFAULT NULL,
			`ip_address` VARCHAR(255) NOT NULL,
			`date_add` DATETIME NOT NULL,
			`date_upd` DATETIME NOT NULL,
			PRIMARY KEY (`id_facebook`),
			UNIQUE KEY `index_unique_facebook_guest` (`id_guest`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		return true;
	}

	public function uninstall()
	{
		//Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_facebook`;');
		Db::getInstance()->Execute('
			DELETE FROM `'._DB_PREFIX_.'configuration_lang`
			WHERE `id_configuration` IN (SELECT `id_configuration` from `'._DB_PREFIX_.'configuration` WHERE `name` like \'RFACEBOOK_%\')');

		Db::getInstance()->Execute('
			DELETE FROM `'._DB_PREFIX_.'configuration`
			WHERE `name` like \'RFACEBOOK_%\'');

		return true;
	}

	public function isActive()
	{
		return Configuration::get('RFACEBOOK_ACTIVE') && trim(Configuration::get('RFACEBOOK_FAN_PAGE')) != '' && !(Configuration::get('RFACEBOOK_HIDE_BLOCK') && RewardsFacebookModel::getAlreadyLiked());
	}

	public function isRewardsAccountVisible()
	{
		return $this->isActive();
	}

	public function getTitle()
	{
		return $this->l('Facebook Like');
	}

	public function getDetails($reward, $admin) {
		return  $this->l('Facebook - Like');
	}

	protected function postProcess($params=null)
	{
		if (Tools::isSubmit('submitFacebook')) {
			$this->_postValidation();
			if (!sizeof($this->_errors)) {
				Configuration::updateValue('RFACEBOOK_ACTIVE', (int)Tools::getValue('facebook_active'));
				Configuration::updateValue('RFACEBOOK_HIDE_BLOCK', (int)Tools::getValue('facebook_hide_block'));
				Configuration::updateValue('RFACEBOOK_FAN_PAGE', Tools::getValue('facebook_fan_page'));
				Configuration::updateValue('RFACEBOOK_HOOKS', is_array(Tools::getValue('facebook_hooks')) ? implode(",", Tools::getValue('facebook_hooks')) : '');
				Configuration::updateValue('RFACEBOOK_GUEST', (int)Tools::getValue('facebook_reward_guest'));
				Configuration::updateValue('RFACEBOOK_VOUCHER_DETAILS', Tools::getValue('facebook_voucher_details'));
				Configuration::updateValue('RFACEBOOK_VOUCHER_PREFIX', Tools::getValue('facebook_voucher_prefix'));
				Configuration::updateValue('RFACEBOOK_VOUCHER_DURATION', (int)Tools::getValue('facebook_voucher_duration'));
				Configuration::updateValue('RFACEBOOK_ALL_CATEGORIES', (int)Tools::getValue('facebook_all_categories'));
				Configuration::updateValue('RFACEBOOK_VOUCHER_CATEGORIES', Tools::getValue('categoryBox') ? implode(',', Tools::getValue('categoryBox')) : '');
				Configuration::updateValue('RFACEBOOK_VOUCHER_CUMUL', (int)(Tools::getValue('facebook_voucher_cumul')));
				Configuration::updateValue('RFACEBOOK_VOUCHER_BEHAVIOR', (int)Tools::getValue('facebook_voucher_behavior'));
				Configuration::updateValue('RFACEBOOK_VOUCHER_QUANTITY', (int)(Tools::getValue('facebook_voucher_quantity')));
				Configuration::updateValue('RFACEBOOK_VOUCHER_MIN_TAX', (int)(Tools::getValue('facebook_voucher_min_tax')));
				Configuration::updateValue('RFACEBOOK_VOUCHER_TYPE', (int)(Tools::getValue('facebook_voucher_type')));
				Configuration::updateValue('RFACEBOOK_VOUCHER_FREESHIPPING', (int)Tools::getValue('facebook_voucher_freeshipping'));

				$currencies = $this->instance->getCurrencies();
				foreach ($currencies as $currency) {
					Configuration::updateValue('RFACEBOOK_REWARD_VALUE_'.$currency['id_currency'], (float)Tools::getValue('facebook_reward_value_'.$currency['id_currency']));
					Configuration::updateValue('RFACEBOOK_VOUCHER_MIN_VALUE_'.$currency['id_currency'], (float)Tools::getValue('facebook_voucher_min_value_'.$currency['id_currency']));
					if ((int)Tools::getValue('facebook_voucher_type') == 0)
						Configuration::updateValue('RFACEBOOK_VOUCHER_VALUE_'.$currency['id_currency'], 0);
					else
						Configuration::updateValue('RFACEBOOK_VOUCHER_VALUE_'.$currency['id_currency'], (float)Tools::getValue('facebook_voucher_value_'.$currency['id_currency']));
				}
				$this->instance->confirmation = $this->instance->displayConfirmation($this->l('Settings updated.'));
			} else
				$this->instance->errors = $this->instance->displayError(implode('<br />', $this->_errors));
		} else if (Tools::isSubmit('submitFacebookNotifications')) {
			Configuration::updateValue('RFACEBOOK_MAIL_LIKE', (int)Tools::getValue('facebook_mail_like'));
			Configuration::updateValue('RFACEBOOK_MAIL_UNLIKE', (int)Tools::getValue('facebook_mail_unlike'));
			$this->instance->confirmation = $this->instance->displayConfirmation($this->l('Settings updated.'));
		} else if (Tools::isSubmit('submitFacebookText')) {
			Configuration::updateValue('RFACEBOOK_MEMBER_TITLE_BLOCK', Tools::getValue('facebook_member_title_block'));
			Configuration::updateValue('RFACEBOOK_GUEST_TITLE_BLOCK', Tools::getValue('facebook_guest_title_block'));
			Configuration::updateValue('RFACEBOOK_MEMBER_TXT_BLOCK', Tools::getValue('facebook_member_txt_block'), true);
			Configuration::updateValue('RFACEBOOK_GUEST_TXT_BLOCK', Tools::getValue('facebook_guest_txt_block'), true);
			Configuration::updateValue('RFACEBOOK_MEMBER_TXT_CART', Tools::getValue('facebook_member_txt_cart'), true);
			Configuration::updateValue('RFACEBOOK_GUEST_TXT_CART', Tools::getValue('facebook_guest_txt_cart'), true);
			Configuration::updateValue('RFACEBOOK_MEMBER_TXT_CONFIRM', Tools::getValue('facebook_member_txt_confirm'), true);
			Configuration::updateValue('RFACEBOOK_GUEST_TXT_CONFIRM', Tools::getValue('facebook_guest_txt_confirm'), true);
			if (version_compare(_PS_VERSION_, '1.5.2', '<')) {
				Configuration::set('RFACEBOOK_MEMBER_TITLE_BLOCK', Tools::getValue('facebook_member_title_block'));
				Configuration::set('RFACEBOOK_GUEST_TITLE_BLOCK', Tools::getValue('facebook_guest_title_block'));
				Configuration::set('RFACEBOOK_MEMBER_TXT_BLOCK', Tools::getValue('facebook_member_txt_block'));
				Configuration::set('RFACEBOOK_GUEST_TXT_BLOCK', Tools::getValue('facebook_guest_txt_block'));
				Configuration::set('RFACEBOOK_MEMBER_TXT_CART', Tools::getValue('facebook_member_txt_cart'));
				Configuration::set('RFACEBOOK_GUEST_TXT_CART', Tools::getValue('facebook_guest_txt_cart'));
				Configuration::set('RFACEBOOK_MEMBER_TXT_CONFIRM', Tools::getValue('facebook_member_txt_confirm'));
				Configuration::set('RFACEBOOK_GUEST_TXT_CONFIRM', Tools::getValue('facebook_guest_txt_confirm'));
			}
			$this->instance->confirmation = $this->instance->displayConfirmation($this->l('Settings updated.'));
		}
	}

	private function _postValidation()
	{
		$this->_errors = array();

		if (Tools::isSubmit('submitFacebook')) {
			$currencies = $this->instance->getCurrencies();

			if (!Tools::getValue('facebook_fan_page') || !Validate::isAbsoluteUrl(Tools::getValue('facebook_fan_page')))
				$this->_errors[] = $this->l('The Facebook Fan Page url is required/invalid.');
			foreach ($currencies as $currency) {
				if (Tools::getValue('facebook_reward_value_'.$currency['id_currency'])=='')
					$this->_errors[] = $this->l('Reward value for the currency').' '.$currency['name'].' '.$this->l('is empty.');
				elseif (!Tools::getValue('facebook_reward_value_'.$currency['id_currency']) || !Validate::isUnsignedFloat(Tools::getValue('facebook_reward_value_'.$currency['id_currency'])))
					$this->_errors[] = $this->l('Reward value for the currency').' '.$currency['name'].' '.$this->l('is invalid.');
			}
			if (Tools::getValue('facebook_reward_guest')) {
				foreach ($currencies as $currency) {
					if (Tools::getValue('facebook_voucher_value_'.$currency['id_currency'])=='' && (int)Tools::getValue('facebook_voucher_type') != 0)
						$this->_errors[] = $this->l('Voucher value for the currency').' '.$currency['name'].' '.$this->l('is empty.');
					elseif (!Tools::getValue('facebook_voucher_value_'.$currency['id_currency']) || !Validate::isUnsignedFloat(Tools::getValue('facebook_voucher_value_'.$currency['id_currency'])))
						$this->_errors[] = $this->l('Voucher value for the currency').' '.$currency['name'].' '.$this->l('is invalid.');
					if (Tools::getValue('facebook_voucher_min_value_'.$currency['id_currency'])!='' && !Validate::isUnsignedFloat(Tools::getValue('facebook_voucher_min_value_'.$currency['id_currency'])))
						$this->_errors[] = $this->l('Minimum order amount for the currency').' '.$currency['name'].' '.$this->l('is invalid.');
				}
				foreach (Tools::getValue('facebook_voucher_details') as $id_language => $voucher_details) {
					$lang = Language::getLanguage($id_language);
					if (empty($voucher_details))
						$this->_errors[] = $this->l('Voucher description is required for').' '.$lang['name'];
				}
				if (Tools::getValue('facebook_voucher_prefix') == '' || !Validate::isDiscountName(Tools::getValue('facebook_voucher_prefix')))
					$this->_errors[] = $this->l('Prefix for the voucher code is required/invalid.');
				if (!is_numeric(Tools::getValue('facebook_voucher_duration')) || (int)Tools::getValue('facebook_voucher_duration') <= 0)
					$this->_errors[] = $this->l('The validity of the voucher is required/invalid.');
				if (!is_numeric(Tools::getValue('facebook_voucher_quantity')) || (int)Tools::getValue('facebook_voucher_quantity') <= 0)
					$this->_errors[] = $this->l('The number of times the voucher can be used is required/invalid.');
				if (!Tools::getValue('facebook_all_categories') && (!is_array(Tools::getValue('categoryBox')) || !sizeof(Tools::getValue('categoryBox'))))
					$this->_errors[] = $this->l('You must choose at least one category of products');
				if ((int)Tools::getValue('facebook_voucher_type')==0 && (int)Tools::getValue('facebook_voucher_freeshipping')==0)
					$this->_errors[] = $this->l('You must offer at least free shipping or/and discount.');
			}
		}
	}

	public function displayForm()
	{
		$this->postProcess();

		// Languages preliminaries
		$defaultLanguage = (int)Configuration::get('PS_LANG_DEFAULT');
		$languages = Language::getLanguages();

		$currencies = $this->instance->getCurrencies();
		$categories = Tools::getValue('categoryBox', explode(',', Configuration::get('RFACEBOOK_VOUCHER_CATEGORIES')));
		$hooks = Tools::getValue('facebook_hooks', explode(',', Configuration::get('RFACEBOOK_HOOKS')));

		$html = '
		<div class="tabs" style="display: none">
			<ul>
				<li><a href="#tabs-'.$this->name.'-1">'.$this->l('Settings').'</a></li>
				<li><a href="#tabs-'.$this->name.'-2">'.$this->l('Notifications').'</a></li>
				<li><a href="#tabs-'.$this->name.'-3">'.$this->l('Texts').'</a></li>
			</ul>
			<div id="tabs-'.$this->name.'-1">
				<form action="'.$this->instance->getCurrentPage($this->name).'" method="post">
					<input type="hidden" name="tabs-'.$this->name.'" value="tabs-'.$this->name.'-1" />
					<fieldset>
						<legend>'.$this->l('General settings').'</legend>
						<label>'.$this->l('Activate Facebook reward').'</label>
						<div class="margin-form">
							<label class="t" for="facebook_active_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="facebook_active_on" name="facebook_active" value="1" '.(Tools::getValue('facebook_active', Configuration::get('RFACEBOOK_ACTIVE')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_active_on">' . $this->l('Yes') . '</label>
							<label class="t" for="facebook_active_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="facebook_active_off" name="facebook_active" value="0" '.(Tools::getValue('facebook_active', Configuration::get('RFACEBOOK_ACTIVE')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_active_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Hide the like button/block once the customer is rewarded').'</label>
						<div class="margin-form">
							<label class="t" for="facebook_hide_block_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="facebook_hide_block_on" name="facebook_hide_block" value="1" '.(Tools::getValue('facebook_hide_block', Configuration::get('RFACEBOOK_HIDE_BLOCK')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_hide_block_on">' . $this->l('Yes') . '</label>
							<label class="t" for="facebook_hide_block_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="facebook_hide_block_off" name="facebook_hide_block" value="0" '.(Tools::getValue('facebook_hide_block', Configuration::get('RFACEBOOK_HIDE_BLOCK')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_hide_block_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Facebook Fan Page URL').'</label>
						<div class="margin-form">
							<input type="text" size="50" name="facebook_fan_page" value="'.Tools::getValue('facebook_fan_page', Configuration::get('RFACEBOOK_FAN_PAGE')).'" />
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Place(s) where the like button will be displayed').'</label>
						<div class="margin-form">
							<select name="facebook_hooks[]" multiple="multiple" class="multiselect">'.
								(version_compare(_PS_VERSION_, '1.6.0.12', '>=') && version_compare(_PS_VERSION_, '1.7', '<') ? '<option '.(is_array($hooks) && in_array('nav', $hooks) ? 'selected':'').' value="nav"> '.$this->l('In the header of the page').'</option>' : '').
								(version_compare(_PS_VERSION_, '1.7', '>=') ? '
								<option '.(is_array($hooks) && in_array('nav1', $hooks) ? 'selected':'').' value="nav1"> '.$this->l('In the header of the page (left)').'</option>
								<option '.(is_array($hooks) && in_array('nav2', $hooks) ? 'selected':'').' value="nav2"> '.$this->l('In the header of the page (right)').'</option>' : '').'
								<option '.(is_array($hooks) && in_array('top', $hooks) ? 'selected':'').' value="top"> '.$this->l('At the top of the page').'</option>
								<option '.(is_array($hooks) && in_array('left', $hooks) ? 'selected':'').' value="left"> '.$this->l('In the left column').'</option>
								<option '.(is_array($hooks) && in_array('leftblock', $hooks) ? 'selected':'').' value="leftblock"> '.$this->l('In a left column block').'</option>
								<option '.(is_array($hooks) && in_array('right', $hooks) ? 'selected':'').' value="right"> '.$this->l('In the right column').'</option>
								<option '.(is_array($hooks) && in_array('rightblock', $hooks) ? 'selected':'').' value="rightblock"> '.$this->l('In a right column block').'</option>
								<option '.(is_array($hooks) && in_array('footer', $hooks) ? 'selected':'').' value="footer"> '.$this->l('In the footer of the page').'</option>
								<option '.(is_array($hooks) && in_array('shoppingcart', $hooks) ? 'selected':'').' value="shoppingcart"> '.$this->l('In the cart summary').'</option>
							</select>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Allow reward for visitors who have no account on the shop').'<br/><small>'.$this->l('A voucher code will be generated instead of a reward in the rewards account').'</small></label>
						<div class="margin-form">
							<label class="t" for="facebook_reward_guest_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="facebook_reward_guest_on" name="facebook_reward_guest" value="1" '.(Tools::getValue('facebook_reward_guest', Configuration::get('RFACEBOOK_GUEST')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_reward_guest_on">' . $this->l('Yes') . '</label>
							<label class="t" for="facebook_reward_guest_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="facebook_reward_guest_off" name="facebook_reward_guest" value="0" '.(Tools::getValue('facebook_reward_guest', Configuration::get('RFACEBOOK_GUEST')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_reward_guest_off">' . $this->l('No') . '</label>
						</div>
					</fieldset>
					<fieldset>
						<legend>'.$this->l('Reward settings (for members only)').'</legend>
						<div>
							<table>
								<tr>
									<td class="label" style="font-weight: bold">' . $this->l('Currency used by the member') . '</td>
									<td align="left" style="font-weight: bold">' . $this->l('Reward value') . '</td>
								</tr>';
		foreach ($currencies as $currency) {
			$html .= '
								<tr>
									<td><label class="indent">' . htmlentities($currency['name'], ENT_NOQUOTES, 'utf-8') . '</label></td>
									<td align="left"><input class="notvirtual '. ((int)$currency['id_currency'] == (int)Configuration::get('PS_CURRENCY_DEFAULT') ? 'currency_default' : '') . '" type="text" size="8" maxlength="8" name="facebook_reward_value_'.$currency['id_currency'].'" id="facebook_reward_value_'.$currency['id_currency'].'" value="'.Tools::getValue('facebook_reward_value_'.$currency['id_currency'], Configuration::get('RFACEBOOK_REWARD_VALUE_'.$currency['id_currency'])).'" onBlur="showVirtualValue(this, '.$currency['id_currency'].', true)" /> <label class="t">'.$currency['sign'].' <span class="virtualvalue"></span></label>'.((int)$currency['id_currency'] != (int)Configuration::get('PS_CURRENCY_DEFAULT') ? ' <a href="#" onClick="return convertCurrencyValue(this, \'facebook_reward_value\', '.$currency['conversion_rate'].')"><img src="'._MODULE_DIR_.'allinone_rewards/img/convert.gif" style="vertical-align: middle !important"></a>' : '').'</td>
								</tr>';
		}
		$html .= '
							</table>
						</div>
					</fieldset>
					<fieldset id="facebook_voucher" class="facebook_voucher_optional">
						<legend>'.$this->l('Voucher settings (for visitors who have no account)').'</legend>
						<label>'.$this->l('Voucher details (will appear in cart next to voucher code)').'</label>
						<div class="margin-form translatable">';
		$facebook_voucher_details = Tools::getValue('facebook_voucher_details');
		foreach ($languages as $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
								<input size="30" type="text" name="facebook_voucher_details['.$language['id_lang'].']" value="'.($facebook_voucher_details[(int)$language['id_lang']] ? $facebook_voucher_details[(int)$language['id_lang']] : Configuration::get('RFACEBOOK_VOUCHER_DETAILS', (int)$language['id_lang'])).'" />
							</div>';
		}
		$html .= '
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Prefix for the voucher code (at least 3 letters long)').'</label>
						<div class="margin-form">
							<input type="text" size="10" maxlength="10" id="facebook_voucher_prefix" name="facebook_voucher_prefix" value="'.Tools::getValue('facebook_voucher_prefix', Configuration::get('RFACEBOOK_VOUCHER_PREFIX')).'" />
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Validity of the voucher (in days)').'</label>
						<div class="margin-form">
							<input type="text" size="4" maxlength="4" id="facebook_voucher_duration" name="facebook_voucher_duration" value="'.Tools::getValue('facebook_voucher_duration', Configuration::get('RFACEBOOK_VOUCHER_DURATION')).'" />
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Number of times the voucher can be used').'</label>
						<div class="margin-form">
							<input type="text" size="4" maxlength="4" id="facebook_voucher_quantity" name="facebook_voucher_quantity" value="'.Tools::getValue('facebook_voucher_quantity', Configuration::get('RFACEBOOK_VOUCHER_QUANTITY')).'" />
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Free shipping').'</label>
						<div class="margin-form">
							<label class="t" for="facebook_voucher_freeshipping_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="facebook_voucher_freeshipping_on" name="facebook_voucher_freeshipping" value="1" '.(Tools::getValue('facebook_voucher_freeshipping', Configuration::get('RFACEBOOK_VOUCHER_FREESHIPPING')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_voucher_freeshipping_on">' . $this->l('Yes') . '</label>
							<label class="t" for="facebook_voucher_freeshipping_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="facebook_voucher_freeshipping_off" name="facebook_voucher_freeshipping" value="0" '.(Tools::getValue('facebook_voucher_freeshipping', Configuration::get('RFACEBOOK_VOUCHER_FREESHIPPING')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_voucher_freeshipping_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Apply a discount').'</label>
						<div class="margin-form">
							<input onClick="$(\'#facebook_voucher td.voucher_value\').html(\'' . $this->l('Voucher %') . '\');$(\'#facebook_voucher td.value_cols\').show();$(\'#facebook_voucher #behavior\').hide();$(\'#facebook_voucher_behavior\').val(0)" type="radio" id="facebook_voucher_type_1" name="facebook_voucher_type" value="1" '.(Tools::getValue('facebook_voucher_type', Configuration::get('RFACEBOOK_VOUCHER_TYPE')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_voucher_type_1" style="padding-right: 10px">'.$this->l('Percentage').'</label>
							<input onClick="$(\'#facebook_voucher td.value_cols\').hide();$(\'#facebook_voucher td.voucher_value\').html(\'' . $this->l('Voucher value') . '\');$(\'#facebook_voucher td.value_cols\').show();$(\'#facebook_voucher #behavior\').show()" type="radio" id="facebook_voucher_type_2" name="facebook_voucher_type" value="2" '.(Tools::getValue('facebook_voucher_type', Configuration::get('RFACEBOOK_VOUCHER_TYPE')) == 2 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_voucher_type_2" style="padding-right: 10px">'.$this->l('Amount').'</label>
							<input onClick="$(\'#facebook_voucher td.value_cols\').hide();$(\'#facebook_voucher #behavior\').hide();$(\'#facebook_voucher_behavior\').val(0)" type="radio" id="facebook_voucher_type_0" name="facebook_voucher_type" value="0" '.(Tools::getValue('facebook_voucher_type', Configuration::get('RFACEBOOK_VOUCHER_TYPE')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_voucher_type_0">'.$this->l('None').'</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Allowed categories').'</label>
						<div class="margin-form">
							<input class="all_categories" type="radio" id="all_categories_on" name="facebook_all_categories" value="0" '.(!Tools::getValue('facebook_all_categories', MyConf::get('RFACEBOOK_ALL_CATEGORIES', null, $this->id_template)) ? 'checked="checked"' : '').' /> <label class="t" for="all_categories_on">' . $this->l('Choose categories') . '</label>&nbsp;
							<input class="all_categories" type="radio" id="all_categories_off" name="facebook_all_categories" value="1" '.(Tools::getValue('facebook_all_categories', MyConf::get('RFACEBOOK_ALL_CATEGORIES', null, $this->id_template)) ? 'checked="checked"' : '').' /> <label class="t" for="all_categories_off">' . $this->l('All categories') . '</label>
							<div class="optional categories_optional" style="padding-top: 15px">
								'.$this->getCategoriesTree($categories).'
							</div>
						</div>
						<div class="clear"></div>
						<div id="behavior" style="display:'.(Tools::getValue('facebook_voucher_type', Configuration::get('RFACEBOOK_VOUCHER_TYPE')) == 2 ? 'block' : 'none').'">
							<div class="clear"></div>
							<label>'.$this->l('If the voucher is not depleted when used').'</label>&nbsp;
							<div class="margin-form">
								<select name="facebook_voucher_behavior" id="facebook_voucher_behavior">
									<option '.(!Tools::getValue('facebook_voucher_behavior', (int)Configuration::get('RFACEBOOK_VOUCHER_BEHAVIOR')) ?'selected':'').' value="0">'.$this->l('Cancel the remaining amount').'</option>
									<option '.(Tools::getValue('facebook_voucher_behavior', (int)Configuration::get('RFACEBOOK_VOUCHER_BEHAVIOR')) ?'selected':'').' value="1">'.$this->l('Create a new voucher with remaining amount').'</option>
								</select>
							</div>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Cumulative with other vouchers').'</label>
						<div class="margin-form">
							<label class="t" for="facebook_voucher_cumul_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="facebook_voucher_cumul_on" name="facebook_voucher_cumul" value="1" '.(Tools::getValue('facebook_voucher_cumul', Configuration::get('RFACEBOOK_VOUCHER_CUMUL')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_voucher_cumul_on">' . $this->l('Yes') . '</label>
							<label class="t" for="facebook_voucher_cumul_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="facebook_voucher_cumul_off" name="facebook_voucher_cumul" value="0" '.(Tools::getValue('facebook_voucher_cumul', Configuration::get('RFACEBOOK_VOUCHER_CUMUL')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_voucher_cumul_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('The minimum order\'s amount to use the voucher includes tax').'</label>
						<div class="margin-form">
							<label class="t" for="facebook_voucher_min_tax_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="facebook_voucher_min_tax_on" name="facebook_voucher_min_tax" value="1" '.(Tools::getValue('facebook_voucher_min_tax_on', Configuration::get('RFACEBOOK_VOUCHER_MIN_TAX')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_voucher_min_tax_on">' . $this->l('Yes') . '</label>
							<label class="t" for="facebook_voucher_min_tax_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="facebook_voucher_min_tax_off" name="facebook_voucher_min_tax" value="0" '.(Tools::getValue('facebook_voucher_min_tax_on', Configuration::get('RFACEBOOK_VOUCHER_MIN_TAX')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_voucher_min_tax_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<div>
							<table>
								<tr>
									<td class="label" style="font-weight: bold">' . $this->l('Currency used by the visitor') . '</td>
									<td width="120" class="voucher_value value_cols" style="font-weight: bold; display:'.(Tools::getValue('facebook_voucher_type', Configuration::get('RFACEBOOK_VOUCHER_TYPE')) == 3 ? 'none' : 'block').'">' . (Tools::getValue('facebook_voucher_type', Configuration::get('RFACEBOOK_VOUCHER_TYPE')) == 1 ? $this->l('Voucher %') : $this->l('Voucher value')) . '</td>
									<td class="value_cols" style="width: 30px">&nbsp;</td>
									<td width="200" style="font-weight: bold">' . $this->l('Minimum order\'s amount') . '</td>
								</tr>';
		foreach ($currencies as $currency) {
			$html .= '
								<tr>
									<td><label class="indent">' . htmlentities($currency['name'], ENT_NOQUOTES, 'utf-8') . '</label></td>
									<td align="left" class="value_cols" style="display:'.(Tools::getValue('facebook_voucher_type', Configuration::get('RFACEBOOK_VOUCHER_TYPE')) == 3 ? 'none' : 'block').'"><input '. ((int)$currency['id_currency'] == (int)Configuration::get('PS_CURRENCY_DEFAULT') ? 'class="currency_default"' : '') . ' type="text" size="8" maxlength="8" name="facebook_voucher_value_'.$currency['id_currency'].'" id="facebook_voucher_value_'.$currency['id_currency'].'" value="'.Tools::getValue('facebook_voucher_value_'.$currency['id_currency'], Configuration::get('RFACEBOOK_VOUCHER_VALUE_'.$currency['id_currency'])).'" />'.((int)$currency['id_currency'] != (int)Configuration::get('PS_CURRENCY_DEFAULT') ? ' <a href="#" onClick="return convertCurrencyValue(this, \'facebook_voucher_value\', '.$currency['conversion_rate'].')"><img src="'._MODULE_DIR_.'allinone_rewards/img/convert.gif" style="vertical-align: middle !important"></a>' : '').'</td>
									<td class="value_cols">&nbsp;</td>
									<td align="left"><input '. ((int)$currency['id_currency'] == (int)Configuration::get('PS_CURRENCY_DEFAULT') ? 'class="currency_default"' : '') . ' type="text" size="8" maxlength="8" name="facebook_voucher_min_value_'.$currency['id_currency'].'" id="facebook_voucher_min_value_'.$currency['id_currency'].'" value="'.Tools::getValue('facebook_voucher_min_value_'.$currency['id_currency'], Configuration::get('RFACEBOOK_VOUCHER_MIN_VALUE_'.$currency['id_currency'])).'" />'.((int)$currency['id_currency'] != (int)Configuration::get('PS_CURRENCY_DEFAULT') ? ' <a href="#" onClick="return convertCurrencyValue(this, \'facebook_voucher_min_value\', '.$currency['conversion_rate'].')"><img src="'._MODULE_DIR_.'allinone_rewards/img/convert.gif" style="vertical-align: middle !important"></a>' : '').'</td>
								</tr>';
		}
		$html .= '
							</table>
						</div>
					</fieldset>
					<div class="clear center"><input type="submit" name="submitFacebook" id="submitFacebook" value="'.$this->l('Save settings').'" class="button" /></div>
				</form>
			</div>
			<div id="tabs-'.$this->name.'-2">
				<form action="'.$this->instance->getCurrentPage($this->name).'" method="post">
					<input type="hidden" name="tabs-'.$this->name.'" value="tabs-'.$this->name.'-2" />
					<fieldset>
						<legend>'.$this->l('Notifications').'</legend>
						<label>'.$this->l('Send a mail to the admin when someone likes the shop').'</label>
						<div class="margin-form">
							<label class="t" for="facebook_mail_like_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="facebook_mail_like_on" name="facebook_mail_like" value="1" '.(Tools::getValue('facebook_mail_like', Configuration::get('RFACEBOOK_MAIL_LIKE')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_mail_like_on">' . $this->l('Yes') . '</label>
							<label class="t" for="facebook_mail_like_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="facebook_mail_like_off" name="facebook_mail_like" value="0" '.(Tools::getValue('facebook_mail_like', Configuration::get('RFACEBOOK_MAIL_LIKE')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_mail_like_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Send a mail to the admin when someone unlikes the shop').'</label>
						<div class="margin-form">
							<label class="t" for="facebook_mail_unlike_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="facebook_mail_unlike_on" name="facebook_mail_unlike" value="1" '.(Tools::getValue('facebook_mail_unlike', Configuration::get('RFACEBOOK_MAIL_UNLIKE')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_mail_unlike_on">' . $this->l('Yes') . '</label>
							<label class="t" for="facebook_mail_unlike_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="facebook_mail_unlike_off" name="facebook_mail_unlike" value="0" '.(Tools::getValue('facebook_mail_unlike', Configuration::get('RFACEBOOK_MAIL_UNLIKE')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="facebook_mail_unlike_off">' . $this->l('No') . '</label>
						</div>
					</fieldset>
					<div class="clear center"><input type="submit" name="submitFacebookNotifications" id="submitFacebookNotifications" value="'.$this->l('Save settings').'" class="button" /></div>
				</form>
			</div>
			<div id="tabs-'.$this->name.'-3">
				<form action="'.$this->instance->getCurrentPage($this->name).'" method="post" enctype="multipart/form-data">
					<input type="hidden" name="tabs-'.$this->name.'" value="tabs-'.$this->name.'-3" />
					<fieldset>
						<legend>'.$this->l('Text for members only').'</legend>
						<label style="width: 300px !important">'.$this->l('Title for the left/right column block').'</label>
						<div class="margin-form translatable">';
		foreach ($languages as $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
								<input size="30" type="text" name="facebook_member_title_block['.$language['id_lang'].']" value="'.Configuration::get('RFACEBOOK_MEMBER_TITLE_BLOCK', (int)$language['id_lang']).'" />
							</div>';
		}
		$html .= '
						</div>
						<div class="clear"></div>
						<label style="width: 300px !important">'.$this->l('Text for the left/right column block').'</label>
						<div class="translatable">';
		foreach ($languages AS $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').';float: left;">
								<textarea class="rte autoload_rte" cols="80" rows="25" name="facebook_member_txt_block['.$language['id_lang'].']">'.Configuration::get('RFACEBOOK_MEMBER_TXT_BLOCK', (int)$language['id_lang']).'</textarea>
							</div>';
		}
		$html .= '

						</div>
						<div class="clear"><br/></div>
						<label style="width: 300px !important">'.$this->l('Text for the cart summary').'</label>
						<div class="translatable">';
		foreach ($languages AS $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').';float: left;">
								<textarea class="rte autoload_rte" cols="80" rows="25" name="facebook_member_txt_cart['.$language['id_lang'].']">'.Configuration::get('RFACEBOOK_MEMBER_TXT_CART', (int)$language['id_lang']).'</textarea>
							</div>';
		}
		$html .= '
						</div>
						<div class="clear"><br/></div>
						<label style="width: 300px !important">'.$this->l('Text for the confirmation message when a reward is created').'</label>
						<div class="translatable">';
		foreach ($languages AS $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').';float: left;">
								<textarea class="rte autoload_rte" cols="80" rows="25" name="facebook_member_txt_confirm['.$language['id_lang'].']">'.Configuration::get('RFACEBOOK_MEMBER_TXT_CONFIRM', (int)$language['id_lang']).'</textarea>
							</div>';
		}
		$html .= '
						</div>

					</fieldset>
					<fieldset>
						<legend>'.$this->l('Text for visitors who have no account on the shop').'</legend>
						<label style="width: 300px !important">'.$this->l('Title for the left/right column block').'</label>
						<div class="margin-form translatable">';
		foreach ($languages as $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
								<input size="30" type="text" name="facebook_guest_title_block['.$language['id_lang'].']" value="'.Configuration::get('RFACEBOOK_GUEST_TITLE_BLOCK', (int)$language['id_lang']).'" />
							</div>';
		}
		$html .= '
						</div>
						<div class="clear"></div>
						<label style="width: 300px !important">'.$this->l('Text for the left/right column block').'</label>
						<div class="translatable">';
		foreach ($languages AS $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').';float: left;">
								<textarea class="rte autoload_rte" cols="80" rows="25" name="facebook_guest_txt_block['.$language['id_lang'].']">'.Configuration::get('RFACEBOOK_GUEST_TXT_BLOCK', (int)$language['id_lang']).'</textarea>
							</div>';
		}
		$html .= '
						</div>
						<div class="clear"><br/></div>
						<label style="width: 300px !important">'.$this->l('Text for the cart summary').'</label>
						<div class="translatable">';
		foreach ($languages AS $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').';float: left;">
								<textarea class="rte autoload_rte" cols="80" rows="25" name="facebook_guest_txt_cart['.$language['id_lang'].']">'.Configuration::get('RFACEBOOK_GUEST_TXT_CART', (int)$language['id_lang']).'</textarea>
							</div>';
		}
		$html .= '
						</div>
						<div class="clear"><br/></div>
						<label style="width: 300px !important">'.$this->l('Text for the confirmation message when a voucher is created').'</label>
						<div class="translatable">';
		foreach ($languages AS $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').';float: left;">
								<textarea class="rte autoload_rte" cols="80" rows="25" name="facebook_guest_txt_confirm['.$language['id_lang'].']">'.Configuration::get('RFACEBOOK_GUEST_TXT_CONFIRM', (int)$language['id_lang']).'</textarea>
							</div>';
		}
		$html .= '
						</div>
					</fieldset>
					<div class="clear center"><input type="submit" name="submitFacebookText" value="'.$this->l('Save settings').'" class="button"/></div>
				</form>
			</div>
		</div>';

		return $html;
	}

	private function _getFacebookLanguage($id_lang)
	{
		$lang = new Language((int)$id_lang);
		if (strstr($lang->language_code, '-')) {
			$res = explode('-', $lang->language_code);
			$language_iso = Tools::strtolower($res[0]).'_'.Tools::strtoupper($res[1]);
		} else
			$language_iso = Tools::strtolower($lang->language_code).'_'.Tools::strtoupper($lang->language_code);

		if (($xml=simplexml_load_file(_PS_MODULE_DIR_ . "allinone_rewards/FacebookLocales.xml")) === false)
			return 'en_US';

		$result = $xml->xpath('/locales/locale/codes/code/standard/representation');
		foreach ($result as $value) {
			if ($language_iso == $value)
				return $language_iso;
		}
		return 'en_US';
	}

	public function hookDisplayHeader($params)
	{
		if ($this->context->customer->isLogged() || Configuration::get('RFACEBOOK_GUEST')) {
			$this->context->controller->addjqueryPlugin('fancybox');
			$this->context->controller->addJS($this->instance->getPath().'js/facebook.js');
			$this->context->smarty->assign('facebook_page', Configuration::get('RFACEBOOK_FAN_PAGE'));
			$this->context->smarty->assign('facebook_language', $this->_getFacebookLanguage((int)$this->context->language->id));
		}
		return false;
	}

	public function hookDisplayNav($params)
	{
		return $this->_displayLike('nav');
	}

	public function hookDisplayNav1($params)
	{
		return $this->_displayLike('nav1');
	}

	public function hookDisplayNav2($params)
	{
		return $this->_displayLike('nav2');
	}

	public function hookDisplayTop($params)
	{
		return $this->_displayLike('top');
	}

	public function hookDisplayLeftColumn($params)
	{
		return $this->_displayPub('leftblock') . $this->_displayLike('left');
	}

	public function hookDisplayRightColumn($params)
	{
		return $this->_displayPub('rightblock') . $this->_displayLike('right');
	}

	public function hookDisplayFooter($params)
	{
		return (version_compare(_PS_VERSION_, '1.7', '<') ? $this->_displayConfirmationMessage() : '').$this->_displayLike('footer');
	}

	public function hookDisplayBeforeBodyClosingTag($params)
	{
		return $this->_displayConfirmationMessage();
	}

	public function hookDisplayShoppingCartFooter($params)
	{
		$hooks = explode(',', Configuration::get('RFACEBOOK_HOOKS'));
		if (is_array($hooks) && in_array('shoppingcart', $hooks) && ($this->context->customer->isLogged() || Configuration::get('RFACEBOOK_GUEST'))) {
			$this->context->smarty->assign('facebook_cart_txt', $this->context->customer->isLogged() ? Configuration::get('RFACEBOOK_MEMBER_TXT_CART', (int)$this->context->language->id) : Configuration::get('RFACEBOOK_GUEST_TXT_CART', (int)$this->context->language->id));
			if (version_compare(_PS_VERSION_, '1.7', '>='))
				return $this->instance->display($this->instance->path, 'presta-1.7/facebook_shopping_cart.tpl');
			return $this->instance->display($this->instance->path, 'facebook_shopping_cart.tpl');
		}
	}

	public function hookActionObjectCustomerDeleteAfter($params)
	{
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_facebook` WHERE `id_customer`='.(int)$params['object']->id);
	}

	private function _displayConfirmationMessage()
	{
		if ($this->context->customer->isLogged() || Configuration::get('RFACEBOOK_GUEST')) {
			$this->context->smarty->assign(
				array(
					'facebook_confirm_txt' 	=> $this->context->customer->isLogged() ? Configuration::get('RFACEBOOK_MEMBER_TXT_CONFIRM', (int)$this->context->language->id) : Configuration::get('RFACEBOOK_GUEST_TXT_CONFIRM', (int)$this->context->language->id),
					'facebook_code' 		=> $this->context->customer->isLogged() ? false : true
				)
			);
			if (version_compare(_PS_VERSION_, '1.7', '>='))
				return $this->instance->display($this->instance->path, 'presta-1.7/facebook_confirmation.tpl');
			return $this->instance->display($this->instance->path, 'facebook_confirmation.tpl');
		}
	}

	private function _displayLike($hook)
	{
		$hooks = explode(',', Configuration::get('RFACEBOOK_HOOKS'));
		if (is_array($hooks) && in_array($hook, $hooks) && ($this->context->customer->isLogged() || Configuration::get('RFACEBOOK_GUEST')))
			return $this->instance->display($this->instance->path, 'facebook_like.tpl');
	}

	private function _displayPub($hook)
	{
		$hooks = explode(',', Configuration::get('RFACEBOOK_HOOKS'));
		if (is_array($hooks) && in_array($hook, $hooks) && ($this->context->customer->isLogged() || Configuration::get('RFACEBOOK_GUEST'))) {
			$this->context->smarty->assign(
				array(
					'facebook_block_title' 	=> $this->context->customer->isLogged() ? Configuration::get('RFACEBOOK_MEMBER_TITLE_BLOCK', (int)$this->context->language->id) : Configuration::get('RFACEBOOK_GUEST_TITLE_BLOCK', (int)$this->context->language->id),
					'facebook_block_txt' 	=> $this->context->customer->isLogged() ? Configuration::get('RFACEBOOK_MEMBER_TXT_BLOCK', (int)$this->context->language->id) : Configuration::get('RFACEBOOK_GUEST_TXT_BLOCK', (int)$this->context->language->id)
				)
			);
			if (version_compare(_PS_VERSION_, '1.7', '>='))
				return $this->instance->display($this->instance->path, 'presta-1.7/facebook_block.tpl');
			return $this->instance->display($this->instance->path, 'facebook_block.tpl');
		}
	}
}