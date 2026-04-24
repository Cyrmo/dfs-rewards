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
require_once(_PS_MODULE_DIR_.'/allinone_rewards/models/RewardsSponsorshipModel.php');

class RewardsSponsorshipPlugin extends RewardsGenericPlugin
{
	public $name = 'sponsorship';
	private $_configuration;
	private $_popup = false;

	public function install()
	{
		// hooks
		if (!$this->registerHook('displayHeader') || !$this->registerHook('displayFooter')
		|| !$this->registerHook('displayCustomerAccount') || !$this->registerHook('displayMyAccountBlock') || !$this->registerHook('displayMyAccountBlockFooter')
		|| !$this->registerHook('actionCustomerAccountAdd') || !$this->registerHook('displayCustomerAccountForm') || !$this->registerHook('displayCustomerAccountFormTop') || (version_compare(_PS_VERSION_, '1.7', '>=') && (!$this->registerHook('additionalCustomerFormFields') || !$this->registerHook('actionFrontControllerSetMedia')))
		|| !$this->registerHook('displayLeftColumnProduct') || !$this->registerHook('displayProductButtons') || !$this->registerHook('displayProductAdditionalInfo')
		|| !$this->registerHook('actionValidateOrder')|| !$this->registerHook('displayOrderConfirmation') || !$this->registerHook('actionOrderStatusUpdate')
		|| !$this->registerHook('displayAdminCustomers') || !$this->registerHook('displayAdminOrder') || !$this->registerHook('ActionAdminControllerSetMedia')
		|| !$this->registerHook('actionObjectOrderDetailAddAfter') || !$this->registerHook('actionObjectOrderDetailUpdateAfter') || !$this->registerHook('actionObjectOrderDetailDeleteAfter')
		|| !$this->registerHook('actionObjectCustomerDeleteAfter'))
			return false;

		$idEn = Language::getIdByIso('en');
		$desc = array();
		$account_txt = array();
		$order_txt = array();
		$popup_txt = array();
		$rules_txt = array();
		foreach (Language::getLanguages() AS $language) {
			$tmp = $this->l('Sponsorship', (int)$language['id_lang']);
			$desc[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('Sponsorship', $idEn);
			$tmp = $this->l('account_txt', (int)$language['id_lang']);
			$account_txt[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('account_txt', $idEn);
			$tmp = $this->l('order_txt', (int)$language['id_lang']);
			$order_txt[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('order_txt', $idEn);
			$tmp = $this->l('popup_txt', (int)$language['id_lang']);
			$popup_txt[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('popup_txt', $idEn);
			$tmp = $this->l('rules_txt', (int)$language['id_lang']);
			$rules_txt[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('rules_txt', $idEn);
		}

		$groups_off = array(Configuration::get('PS_UNIDENTIFIED_GROUP'), Configuration::get('PS_GUEST_GROUP'));
		$groups_config = '';
		$groups = Group::getGroups((int)Configuration::get('PS_LANG_DEFAULT'));
		foreach ($groups AS $group)
			if (!in_array($group['id_group'], $groups_off))
				$groups_config .= (int)$group['id_group'].',';
		$groups_config = rtrim($groups_config, ',');

		if (!Configuration::updateValue('RSPONSORSHIP_ORDER_QUANTITY_S', 0)
		|| !Configuration::updateValue('RSPONSORSHIP_VOUCHER_DETAILS', $desc)
		|| !Configuration::updateValue('RSPONSORSHIP_REAL_VOUCHER_GC', 0)
		|| !Configuration::updateValue('RSPONSORSHIP_VOUCHER_PREFIX_GC', 'REWARD')
		|| !Configuration::updateValue('RSPONSORSHIP_VOUCHER_DURATION_GC', 365)
		|| !Configuration::updateValue('RSPONSORSHIP_ALL_CATEGORIES', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_TAX', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_CATEGORIES_GC', '')
		|| !Configuration::updateValue('RSPONSORSHIP_REWARD_TYPE_S', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_DISCOUNT_TYPE_GC', 2)
		|| !Configuration::updateValue('RSPONSORSHIP_FREESHIPPING_GC', 0)
		|| !Configuration::updateValue('RSPONSORSHIP_CUMUL_GC', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_QUANTITY_GC', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_VOUCHER_BEHAVIOR', 0)
		|| !Configuration::updateValue('RSPONSORSHIP_MINIMAL_TAX_GC', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_NB_FRIENDS', 5)
		|| !Configuration::updateValue('RSPONSORSHIP_REWARD_REGISTRATION', 0)
		|| !Configuration::updateValue('RSPONSORSHIP_REWARD_ORDER', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_REWARD_PERCENTAGE', 5)
		|| !Configuration::updateValue('RSPONSORSHIP_DEF_PRODUCT_REWARD', 0)
		|| !Configuration::updateValue('RSPONSORSHIP_DEF_PRODUCT_TYPE', 0)
		|| !Configuration::updateValue('RSPONSORSHIP_ON_EVERY_ORDER', 0)
		|| !Configuration::updateValue('RSPONSORSHIP_DURATION', 0)
		|| !Configuration::updateValue('RSPONSORSHIP_DISCOUNTED_ALLOWED', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_DISCOUNT_GC', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_UNLOCK_SHIPPING', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_ACTIVE', 0)
		|| !Configuration::updateValue('RSPONSORSHIP_REDIRECT', 'home')
		|| !Configuration::updateValue('RSPONSORSHIP_PRODUCT_SHARE', 0)
		|| !Configuration::updateValue('RSPONSORSHIP_MAIL_REGISTRATION', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_MAIL_ORDER', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_MAIL_REGISTRATION_S', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_MAIL_ORDER_S', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_MAIL_VALIDATION_S', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_MAIL_CANCELPROD_S', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_UNLIMITED_LEVELS', 0)
		|| !Configuration::updateValue('RSPONSORSHIP_AFTER_ORDER', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_POPUP', 1)
		|| !Configuration::updateValue('RSPONSORSHIP_POPUP_DELAY', 3)
		|| !Configuration::updateValue('RSPONSORSHIP_POPUP_KEY', Tools::passwdGen())
		|| !Configuration::updateValue('RSPONSORSHIP_ACCOUNT_TXT', $account_txt, true)
		|| !Configuration::updateValue('RSPONSORSHIP_ORDER_TXT', $order_txt, true)
		|| !Configuration::updateValue('RSPONSORSHIP_POPUP_TXT', $popup_txt, true)
		|| !Configuration::updateValue('RSPONSORSHIP_RULES_TXT', $rules_txt, true)
		|| !Configuration::updateValue('RSPONSORSHIP_GROUPS', $groups_config))
			return false;

		if (version_compare(_PS_VERSION_, '1.5.2', '<')) {
			Configuration::set('RSPONSORSHIP_ACCOUNT_TXT', $account_txt);
			Configuration::set('RSPONSORSHIP_ORDER_TXT', $order_txt);
			Configuration::set('RSPONSORSHIP_POPUP_TXT', $popup_txt);
			Configuration::set('RSPONSORSHIP_RULES_TXT', $rules_txt);
		}

		foreach ($this->instance->getCurrencies() as $currency) {
			Configuration::updateValue('RSPONSORSHIP_REWARD_VALUE_S_'.(int)($currency['id_currency']), 5);
			Configuration::updateValue('RSPONSORSHIP_VOUCHER_VALUE_GC_'.(int)($currency['id_currency']), 5);
			Configuration::updateValue('RSPONSORSHIP_MINIMUM_VALUE_GC_'.(int)($currency['id_currency']), 0);
			Configuration::updateValue('RSPONSORSHIP_UNLOCK_GC_'.(int)($currency['id_currency']), 0);
		}

		// database
		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_sponsorship` (
			`id_sponsorship` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_sponsor` INT UNSIGNED NOT NULL,
			`channel` INT UNSIGNED DEFAULT 1,
			`email` VARCHAR(255) NOT NULL,
			`lastname` VARCHAR(128) DEFAULT NULL,
			`firstname` VARCHAR(128) DEFAULT NULL,
			`id_customer` INT UNSIGNED DEFAULT NULL,
			`id_cart_rule` INT UNSIGNED DEFAULT NULL,
			`date_end` DATETIME DEFAULT \'0000-00-00 00:00:00\',
			`date_add` DATETIME NOT NULL,
			`date_upd` DATETIME NOT NULL,
			PRIMARY KEY (`id_sponsorship`),
			UNIQUE KEY `index_unique_sponsorship_email` (`email`),
			KEY `index_id_customer` (`id_customer`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_sponsorship_detail` (
			`id_reward` INT UNSIGNED NOT NULL,
			`id_sponsorship` INT UNSIGNED DEFAULT \'0\',
			`level_sponsorship` INT UNSIGNED DEFAULT \'0\',
			PRIMARY KEY (`id_reward`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_sponsorship_code` (
			`id_sponsor` INT UNSIGNED NOT NULL,
			`code` VARCHAR(20) NOT NULL,
			PRIMARY KEY (`id_sponsor`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		// create an invisible tab so we can call an admin controller to manage the sponsor autocomplete field in the customer page
		$tab = new Tab();
		$tab->active = 1;
		$tab->class_name = "AdminSponsor";
		$tab->name = array();
		foreach (Language::getLanguages(true) as $lang)
			$tab->name[$lang['id_lang']] = 'AllinoneRewards Sponsor';
		$tab->id_parent = -1;
		$tab->module = $this->instance->name;
		if (!$tab->add())
			return false;

		return true;
	}

	public function uninstall()
	{
		//Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_sponsorship_detail`;');
		//Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_sponsorship`;');
		Db::getInstance()->Execute('
			DELETE FROM `'._DB_PREFIX_.'configuration_lang`
			WHERE `id_configuration` IN (SELECT `id_configuration` from `'._DB_PREFIX_.'configuration` WHERE `name` like \'RSPONSORSHIP_%\')');

		Db::getInstance()->Execute('
			DELETE FROM `'._DB_PREFIX_.'configuration`
			WHERE `name` like \'RSPONSORSHIP_%\'');

		return true;
	}

	// get the configuration by level
	private function _initConf($id_template)
	{
		unset($this->_configuration);
		$this->_configuration['reward_type'] = explode(',', MyConf::get('RSPONSORSHIP_REWARD_TYPE_S', null, $id_template));
		$this->_configuration['reward_percentage'] = explode(',', MyConf::get('RSPONSORSHIP_REWARD_PERCENTAGE', null, $id_template));
		$this->_configuration['default_product_reward'] = explode(',', MyConf::get('RSPONSORSHIP_DEF_PRODUCT_REWARD', null, $id_template));
		$this->_configuration['default_product_type'] = explode(',', MyConf::get('RSPONSORSHIP_DEF_PRODUCT_TYPE', null, $id_template));
		$this->_configuration['unlimited'] = (int)MyConf::get('RSPONSORSHIP_UNLIMITED_LEVELS', null, $id_template);

		$currencies = $this->instance->getCurrencies();
		$tmp = Tools::getValue('reward_value_s');
		foreach ($currencies as $currency) {
			$values = explode(',', myConf::get('RSPONSORSHIP_REWARD_VALUE_S_'.$currency['id_currency'], null, $id_template));
			foreach($this->_configuration['reward_percentage'] as $level => $percentage) {
				if (isset($tmp[$currency['id_currency']][$level]))
					$values[$level] = $tmp[$currency['id_currency']][$level];
				$this->_configuration['reward_value'][$level][$currency['id_currency']] = isset($values[$level]) ? $values[$level] : 0;
			}
		}
	}

	public function isActive()
	{
		// TODO : il faudrait aussi regarder si le parrainage est actif pour le parrain
		// car par exemple sur actionValidateOrder c'est le compte du parrain qui compte et pas du filleul
		// Que se passe t'il si le filleul qui a un compte membre est sur un template inactif, mais le parrain sur un template actif ? ActionValidateOrder se déclenche ?
		// Actuellement oui, c'est un coup de bol, car dans ActionValidateOrder isLogged renvoi false.
		// Solution : passe le nom du hook en cours a isActive et faire des cas différents (peut-être aussi le contexte front/admin)
		// si on vérifie le compte parrain (dépend de la méthode passée en param), peut-être vérifier en même temps qu'il est toujours dans un groupe autorisé à parrainer
		// ou bien alors isActive return true, et on test sur chaque hook en fonction de la personne concernée si c'est actif et s'il a le droit
		if ($this->context->customer->isLogged()) {
			// si le client est loggué on regarde si le parrainage est actif pour lui
			$id_template = (int)MyConf::getIdTemplate('sponsorship', $this->context->customer->id);
			return MyConf::get('RSPONSORSHIP_ACTIVE', null, $id_template);
		} else {
			// sinon, on teste si au moins un modèle est actif car dans ce cas il faut toujours afficher le champs de parrainage sur le formulaire d'inscription
			// et traiter les URL
			return Configuration::get('RSPONSORSHIP_ACTIVE') || MyConf::isActiveAtLeastOnce('RSPONSORSHIP_ACTIVE');
		}
	}

	public function isRewardsAccountVisible()
	{
		return $this->isActive() && RewardsSponsorshipModel::isCustomerAllowed($this->context->customer);
	}

	public function getTitle()
	{
		return $this->l('Sponsorship program');
	}

	public function getDetails($reward, $admin) {
		$row = RewardsSponsorshipModel::getRewardDetails($reward['id_reward']);
		if ($reward['id_order']) {
			if (!$admin) {
				if ($row['level_sponsorship'] == 1)
					return sprintf($this->l('Sponsorship - order from %s'), $row['order_firstname'].' '.$row['order_lastname']);
				else
					return sprintf($this->l('Sponsorship - order from level %s'), $row['level_sponsorship']);
			} else {
				$tokenCustomer = Tools::getAdminToken('AdminCustomers'.(int)Tab::getIdFromClassName('AdminCustomers').(int)$this->context->employee->id);
				return sprintf($this->l('Sponsorship - order #%s from %s (level %d)'), '<a href="'.$this->context->link->getAdminLink('AdminOrders').'&vieworder=1&id_order='.$reward['id_order'].'" style="display: inline; width: auto">'.(empty($row['reference']) ? sprintf('%06d', $reward['id_order']) : $row['reference']).'</a>', '<a href="?tab=AdminCustomers&id_customer='.$row['id_customer'].'&viewcustomer&token='.$tokenCustomer.'">'.$row['order_firstname'].' '.$row['order_lastname'].'</a>', $row['level_sponsorship']);
			}
		} else {
			if (!$admin) {
				return sprintf($this->l('Sponsorship - registration from %s'), $row['firstname'].' '.$row['lastname']);
			} else {
				$token = Tools::getAdminToken('AdminCustomers'.(int)Tab::getIdFromClassName('AdminCustomers').(int)$this->context->employee->id);
				return sprintf($this->l('Sponsorship - registration from %s'), '<a href="?tab=AdminCustomers&id_customer='.$row['id_customer'].'&viewcustomer&token='.$token.'">'.$row['firstname'].' '.$row['lastname'].'</a>');
			}
		}
	}

	public function duplicateReward($old_id, $id_reward) {
		return Db::getInstance()->execute('
			INSERT INTO `'._DB_PREFIX_.'rewards_sponsorship_detail` (`id_reward`, `id_sponsorship`, `level_sponsorship`)
			SELECT '.(int)$id_reward.', id_sponsorship, level_sponsorship
			FROM `'._DB_PREFIX_.'rewards_sponsorship_detail`
			WHERE id_reward='.(int)$old_id);
	}

	protected function postProcess($params=null)
	{
		// on initialise le template à chaque chargement
		$this->initTemplate();

		if (Tools::isSubmit('submitSponsorship')) {
			$this->_postValidation();
			if (!sizeof($this->_errors)) {
				if (empty($this->id_template)) {
					Configuration::updateValue('RSPONSORSHIP_GROUPS', implode(",", Tools::getValue('rsponsorship_groups')));
				}
				MyConf::updateValue('RSPONSORSHIP_UNLIMITED_LEVELS', (int)Tools::getValue('unlimited_levels') > 0 ? count(Tools::getValue('reward_type_s')) : 0, null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_REDIRECT', Tools::getValue('sponsorship_redirect'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_PRODUCT_SHARE', (int)Tools::getValue('sponsorship_product_share'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_AFTER_ORDER', (int)Tools::getValue('after_order'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_POPUP', (int)Tools::getValue('popup'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_POPUP_DELAY', (int)Tools::getValue('popup_delay'), null, $this->id_template);
				if (Tools::getValue('popup_reset'))
					MyConf::updateValue('RSPONSORSHIP_POPUP_KEY', Tools::passwdGen(), null, $this->id_template);

				MyConf::updateValue('RSPONSORSHIP_ACTIVE', (int)Tools::getValue('sponsorship_active'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_REWARD_REGISTRATION', (int)Tools::getValue('reward_registration'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_REGISTR_MULTIPLE', implode(',', Tools::getValue('rsponsorship_registr_multiple')), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_REGISTR_REPEAT', implode(',', Tools::getValue('rsponsorship_registr_repeat')), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_REGISTR_VALUE', implode(',', Tools::getValue('rsponsorship_registr_value')), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_NB_FRIENDS', (int)Tools::getValue('nb_friends'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_ORDER_QUANTITY_S', (int)Tools::getValue('order_quantity_s'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_VOUCHER_DETAILS', Tools::getValue('description_gc'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_REAL_VOUCHER_GC', (int)Tools::getValue('real_voucher_gc'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_REAL_CODE_GC', Tools::getValue('real_code_gc'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_REAL_DESC_GC', Tools::getValue('real_description_gc'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_VOUCHER_PREFIX_GC', Tools::getValue('voucher_prefix_gc'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_VOUCHER_DURATION_GC', (int)Tools::getValue('voucher_duration_gc'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_ALL_CATEGORIES', (int)Tools::getValue('rsponsorship_all_categories'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_CATEGORIES_GC', Tools::getValue('categoryBox') ? implode(',', Tools::getValue('categoryBox')) : '', null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_CUMUL_GC', (int)Tools::getValue('cumulative_voucher_gc'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_VOUCHER_BEHAVIOR', (int)Tools::getValue('voucher_behavior_gc'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_QUANTITY_GC', (int)Tools::getValue('voucher_quantity_gc'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_MINIMAL_TAX_GC', (int)Tools::getValue('include_tax_gc'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_REWARD_ORDER', (int)Tools::getValue('reward_order'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_TAX', (int)Tools::getValue('rsponsorship_tax'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_DURATION', (int)Tools::getValue('rsponsorship_duration'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_ON_EVERY_ORDER', (int)Tools::getValue('reward_on_every_order'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_DISCOUNT_GC', (int)Tools::getValue('discount_gc'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_UNLOCK_SHIPPING', (int)Tools::getValue('unlock_shipping'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_DISCOUNT_TYPE_GC', (int)Tools::getValue('discount_type_gc'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_FREESHIPPING_GC', (int)Tools::getValue('freeshipping_gc'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_DISCOUNTED_ALLOWED', (int)Tools::getValue('rsponsorship_discounted_allowed'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_SHARE_IMAGE_URL', Tools::getValue('share_image_url'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_SHARE_TITLE', Tools::getValue('share_title'), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_SHARE_DESCRIPTION', Tools::getValue('share_description'), null, $this->id_template);

				$currencies = $this->instance->getCurrencies();
				foreach ($currencies as $currency) {
					MyConf::updateValue('RSPONSORSHIP_MINIMUM_VALUE_GC_'.$currency['id_currency'], (float)Tools::getValue('minimum_value_gc_'.$currency['id_currency']), null, $this->id_template);
					MyConf::updateValue('RSPONSORSHIP_UNLOCK_GC_'.$currency['id_currency'], (float)Tools::getValue('unlock_gc_'.$currency['id_currency']), null, $this->id_template);
					if ((int)Tools::getValue('discount_type_gc') == 0)
						MyConf::updateValue('RSPONSORSHIP_VOUCHER_VALUE_GC_'.$currency['id_currency'], 0, null, $this->id_template);
					else
						MyConf::updateValue('RSPONSORSHIP_VOUCHER_VALUE_GC_'.$currency['id_currency'], (float)Tools::getValue('discount_value_gc_'.$currency['id_currency']), null, $this->id_template);
				}
				// For levels
				MyConf::updateValue('RSPONSORSHIP_REWARD_TYPE_S', implode(',', Tools::getValue('reward_type_s')), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_REWARD_PERCENTAGE', implode(',', Tools::getValue('reward_percentage')), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_DEF_PRODUCT_REWARD', implode(',', Tools::getValue('rsponsorship_def_product_reward')), null, $this->id_template);
				MyConf::updateValue('RSPONSORSHIP_DEF_PRODUCT_TYPE', implode(',', Tools::getValue('rsponsorship_def_product_type')), null, $this->id_template);
				foreach (Tools::getValue('reward_value_s') as $id_currency => $reward_value_s) {
					if (is_array($reward_value_s)) {
						MyConf::updateValue('RSPONSORSHIP_REWARD_VALUE_S_'.(int)$id_currency, implode(",", $reward_value_s), null, $this->id_template);
					}
				}
				$this->instance->confirmation = $this->instance->displayConfirmation($this->l('Settings updated.'));
			} else
				$this->instance->errors =  $this->instance->displayError(implode('<br />', $this->_errors));
		} else if (Tools::isSubmit('submitSponsorshipNotifications')) {
			Configuration::updateValue('RSPONSORSHIP_MAIL_REGISTRATION', (int)Tools::getValue('mail_admin_registration'));
			Configuration::updateValue('RSPONSORSHIP_MAIL_ORDER', (int)Tools::getValue('mail_admin_order'));
			Configuration::updateValue('RSPONSORSHIP_MAIL_REGISTRATION_S', (int)Tools::getValue('mail_sponsor_registration'));
			Configuration::updateValue('RSPONSORSHIP_MAIL_ORDER_S', (int)Tools::getValue('mail_sponsor_order'));
			Configuration::updateValue('RSPONSORSHIP_MAIL_VALIDATION_S', (int)Tools::getValue('mail_sponsor_validation'));
			Configuration::updateValue('RSPONSORSHIP_MAIL_CANCELPROD_S', (int)Tools::getValue('mail_sponsor_cancel_product'));
			$this->instance->confirmation = $this->instance->displayConfirmation($this->l('Settings updated.'));
		} else if (Tools::isSubmit('submitSponsorshipText')) {
			MyConf::updateValue('RSPONSORSHIP_ACCOUNT_TXT', Tools::getValue('account_txt'), true, $this->id_template);
			MyConf::updateValue('RSPONSORSHIP_ORDER_TXT', Tools::getValue('order_txt'), true, $this->id_template);
			MyConf::updateValue('RSPONSORSHIP_POPUP_TXT', Tools::getValue('popup_txt'), true, $this->id_template);
			MyConf::updateValue('RSPONSORSHIP_RULES_TXT', Tools::getValue('rules_txt'), true, $this->id_template);
			$this->instance->confirmation = $this->instance->displayConfirmation($this->l('Settings updated.'));
		} else if (Tools::isSubmit('submitSponsorCustomCode')) {
			$this->_postValidation($params);
			if (!sizeof($this->_errors)) {
				$reward_sponsorship_code = new RewardsSponsorshipCodeModel((int)$params['id_customer']);
				if (Tools::getValue('sponsorship_custom_code') || Validate::isLoadedObject($reward_sponsorship_code)) {
					if (Tools::getValue('sponsorship_custom_code')) {
						$reward_sponsorship_code->id_sponsor = (int)$params['id_customer'];
						$reward_sponsorship_code->code = Tools::getValue('sponsorship_custom_code');
						$reward_sponsorship_code->save();
					} else
						$reward_sponsorship_code->delete();
					return $this->instance->displayConfirmation($this->l('The sponsor code has been updated.'));
				}
			} else
				return $this->instance->displayError(implode('<br />', $this->_errors));
		} else if (Tools::isSubmit('submitSponsor') && (int)Tools::getValue('new_sponsor')) {
			$customer = new Customer((int)$params['id_customer']);
			$new_sponsor = new Customer((int)Tools::getValue('new_sponsor'));
			if (Validate::isLoadedObject($new_sponsor)) {
				if ($this->_createSponsorship($new_sponsor, $customer, true, (bool)Tools::getValue('generate_voucher'), (int)Tools::getValue('generate_currency')))
					return $this->instance->displayConfirmation($this->l('The sponsor has been updated.'));
				else
					return $this->instance->displayError($this->l('The sponsor update failed.'));
			}
		} else if (Tools::isSubmit('submitSponsorshipEndDate') && (int)Tools::getValue('id_sponsorship_to_update')) {
			$this->_postValidation();
			if (!sizeof($this->_errors)) {
				$sponsorship = new RewardsSponsorshipModel((int)Tools::getValue('id_sponsorship_to_update'));
				$sponsorship->date_end = Tools::getValue('date_end_' . Tools::getValue('id_sponsorship_to_update'));
				$sponsorship->save();
				return $this->instance->displayConfirmation($this->l('The sponsorship end date has been updated.'));
			} else
				return $this->instance->displayError(implode('<br />', $this->_errors));
		} else if (Tools::getValue('action') == 'sponsorship_template') {
			$id_old_template = (int)MyConf::getIdTemplate('sponsorship', (int)$params['id_customer']);
			RewardsTemplateModel::deleteCustomer($id_old_template, (int)$params['id_customer']);
			if (Tools::getValue('sponsorship_template'))
				RewardsTemplateModel::addCustomer((int)Tools::getValue('sponsorship_template'), (int)$params['id_customer']);
			return $this->instance->displayConfirmation($this->l('The template has been updated.'));
		}
	}

	private function _postValidation($params=null)
	{
		$this->_errors = array();

		if (Tools::isSubmit('submitSponsorship')) {
			$currencies = $this->instance->getCurrencies();

			if (Tools::getValue('popup') && (!is_numeric(Tools::getValue('popup_delay')) || Tools::getValue('popup_delay') <= 0))
				$this->_errors[] = $this->l('The number of days before opening the popup again, is invalid.');
			if (Tools::getValue('share_image_url') && !Validate::isAbsoluteUrl(Tools::getValue('share_image_url')))
				$this->_errors[] = $this->l('The url to force for Facebook share is invalid.');
			if (!is_numeric(Tools::getValue('order_quantity_s')) || Tools::getValue('order_quantity_s') < 0)
				$this->_errors[] = $this->l('The number of orders to be able to become a sponsor is invalid.');
			if (!is_numeric(Tools::getValue('nb_friends')) || Tools::getValue('nb_friends') <= 0)
				$this->_errors[] = $this->l('The number of lines displayed in the invitation form is required/invalid.');
			if (empty($this->id_template) && !is_array(Tools::getValue('rsponsorship_groups')))
				$this->_errors[] = $this->l('Please select at least 1 customer group allowed to sponsor its friends');

			if (Tools::getValue('reward_registration')) {
				$sponsorship_registr_multiple = Tools::getValue('rsponsorship_registr_multiple');
				$sponsorship_registr_repeat = Tools::getValue('rsponsorship_registr_repeat');
				$sponsorship_registr_value = Tools::getValue('rsponsorship_registr_value');
				foreach($sponsorship_registr_multiple as $key => $value) {
					if (!Validate::isUnsignedInt($sponsorship_registr_multiple[$key]) || $sponsorship_registr_multiple[$key] <= 0)
						$this->_errors[] = sprintf($this->l('The number of friends registrations is required/invalid for rule #%s.'), $key+1);
					if (!Validate::isUnsignedInt($sponsorship_registr_repeat[$key]))
						$this->_errors[] = sprintf($this->l('The repeat value is required/invalid for rule #%s.'), $key+1);
					if (!Validate::isUnsignedFloat($sponsorship_registr_value[$key]) || $sponsorship_registr_value[$key] <= 0)
						$this->_errors[] = sprintf($this->l('The reward value is required/invalid for rule #%s.'), $key+1);
				}
			}

			if(Tools::getValue('reward_order')) {
				$reward_type_s = Tools::getValue('reward_type_s');
				if (!is_numeric(Tools::getValue('rsponsorship_duration')) || Tools::getValue('rsponsorship_duration') < 0)
					$this->_errors[] = $this->l('The duration of the sponsorship is required/invalid.');
				foreach (Tools::getValue('reward_percentage') as $level => $reward_percentage) {
					if ($reward_type_s[$level] == 2 && !Validate::isUnsignedFloat($reward_percentage))
						$this->_errors[] = $this->l('The percentage of the sponsored\'s order is invalid for level').' '.($level+1);
				}
				foreach (Tools::getValue('rsponsorship_def_product_reward') as $level => $reward_product) {
					if ($reward_type_s[$level] == 3 && !Validate::isUnsignedFloat($reward_product))
						$this->_errors[] = $this->l('The default reward is invalid for level').' '.($level+1);
				}
				$reward_value_s = Tools::getValue('reward_value_s');
				foreach ($currencies as $currency) {
					if (Tools::getValue('unlock_gc_'.$currency['id_currency'])!='' && !Validate::isUnsignedFloat(Tools::getValue('unlock_gc_'.$currency['id_currency'])))
						$this->_errors[] = $this->l('Minimum unlock amount for the currency').' '.$currency['name'].' '.$this->l('is invalid.');

					if (is_array($reward_value_s[$currency['id_currency']])) {
						foreach($reward_value_s[$currency['id_currency']] as $level => $value) {
							if ($reward_type_s[$level] == 1) {
								if (empty($value))
									$this->_errors[] = $this->l('Reward amount for the level').' '.($level+1).' '.$this->l('and the currency').' '.$currency['name'].' '.$this->l('is empty.');
								elseif (!Validate::isUnsignedFloat($value))
									$this->_errors[] = $this->l('Reward amount for the level').' '.($level+1).' '.$this->l('and the currency').' '.$currency['name'].' '.$this->l('is invalid.');
							}
						}
					}
				}
			}
			if (Tools::getValue('discount_gc')) {
				if (Tools::getValue('voucher_prefix_gc') == '' || !Validate::isDiscountName(Tools::getValue('voucher_prefix_gc')))
					$this->_errors[] = $this->l('Prefix for the voucher code is required/invalid.');
				if (!is_numeric(Tools::getValue('voucher_duration_gc')) || (int)Tools::getValue('voucher_duration_gc') <= 0)
					$this->_errors[] = $this->l('The validity of the voucher is required/invalid.');
				if (!Tools::getValue('real_voucher_gc')) {
					foreach ($currencies as $currency) {
						if ((int)Tools::getValue('discount_type_gc') != 0) {
							if (Tools::getValue('discount_value_gc_'.$currency['id_currency'])=='')
								$this->_errors[] = $this->l('Voucher value for the currency').' '.$currency['name'].' '.$this->l('is empty.');
							elseif (!Tools::getValue('discount_value_gc_'.$currency['id_currency']) || !Validate::isUnsignedFloat(Tools::getValue('discount_value_gc_'.$currency['id_currency'])))
								$this->_errors[] = $this->l('Voucher value for the currency').' '.$currency['name'].' '.$this->l('is invalid.');
							if (Tools::getValue('minimum_value_gc_'.$currency['id_currency'])!='' && !Validate::isUnsignedFloat(Tools::getValue('minimum_value_gc_'.$currency['id_currency'])))
								$this->_errors[] = $this->l('Minimum order amount for the currency').' '.$currency['name'].' '.$this->l('is invalid.');
						}
					}
					foreach (Tools::getValue('description_gc') as $id_language => $description) {
						$lang = Language::getLanguage($id_language);
						if (empty($description))
							$this->_errors[] = $this->l('Voucher description is required for').' '.$lang['name'];
					}
					if (!is_numeric(Tools::getValue('voucher_quantity_gc')) || (int)Tools::getValue('voucher_quantity_gc') <= 0)
						$this->_errors[] = $this->l('The number of times the voucher can be used is required/invalid.');
					if (!Tools::getValue('rsponsorship_all_categories') && (!is_array(Tools::getValue('categoryBox')) || !sizeof(Tools::getValue('categoryBox'))))
						$this->_errors[] = $this->l('You must choose at least one category of products');
					if ((int)Tools::getValue('discount_type_gc')==0 && (int)Tools::getValue('freeshipping_gc')==0)
						$this->_errors[] = $this->l('You must offer at least free shipping or/and discount.');
				} else {
					if (!Tools::getValue('real_code_gc') || !Validate::isDiscountName(Tools::getValue('real_code_gc')))
						$this->_errors[] = $this->l('Code of the existing voucher is required/invalid.');
					else {
						$cart_rule = new CartRule((int)CartRule::getIdByCode(Tools::getValue('real_code_gc')));
						if (!Validate::isLoadedObject($cart_rule))
							$this->_errors[] = $this->l('That voucher doesn\'t exist.');
						else {
							if ($cart_rule->id_customer)
								$this->_errors[] = $this->l('That voucher is not valid because it\'s linked to a customer.');
							if ($cart_rule->highlight)
								$this->_errors[] = $this->l('That voucher is not valid because it\'s highlighted.');
							if ((int)$cart_rule->quantity < 1 || (int)$cart_rule->quantity_per_user < 1)
								$this->_errors[] = $this->l('That voucher is not valid because its quantity or its quantity per user is lower than 1.');
							if ($cart_rule->date_to <= date('Y-m-d :h:i:s'))
								$this->_errors[] = $this->l('That voucher is not valid because its end date is not valid.');
						}
					}
					foreach (Tools::getValue('real_description_gc') as $id_language => $description) {
						$lang = Language::getLanguage($id_language);
						if (empty($description))
							$this->_errors[] = $this->l('Voucher description is required for').' '.$lang['name'];
					}

				}
			}
		} else if (Tools::isSubmit('submitSponsorshipEndDate')) {
			if (Tools::getValue('date_end_' . Tools::getValue('id_sponsorship_to_update')) && !Validate::isDate(Tools::getValue('date_end_' . Tools::getValue('id_sponsorship_to_update'))))
				$this->_errors[] = $this->l('The date is invalid.');
		} else if (Tools::isSubmit('submitSponsorCustomCode') && Tools::getValue('sponsorship_custom_code')) {
			$id_sponsor = RewardsSponsorshipCodeModel::getIdSponsorByCode(Tools::getValue('sponsorship_custom_code'));
			if (!ctype_alnum(Tools::getValue('sponsorship_custom_code')) || Tools::strlen(Tools::getValue('sponsorship_custom_code')) < 5 || Tools::strlen(Tools::getValue('sponsorship_custom_code')) > 20) {
				$this->_errors[] = $this->l('The sponsor code is not valid, it must contain only digits or letters and length must be between 5 and 20 characters.');
			} else if ($id_sponsor && $id_sponsor != (int)$params['id_customer']) {
				$this->_errors[] = $this->l('The sponsor code is not valid, it already exists for another customer.');
			}
		}
	}

	public function displayForm() {
		if (Tools::getValue('stats')) {
			$id_sponsor = Tools::getValue('id_sponsor');
			return $this->_getStatistics(empty($id_sponsor) ? null : $id_sponsor);
		}

		$this->postProcess();
		$this->_initConf($this->id_template);

		// Languages preliminaries
		$defaultLanguage = (int)Configuration::get('PS_LANG_DEFAULT');
		$languages = Language::getLanguages();

		$currencies = $this->instance->getCurrencies();
		$defaultCurrency = new Currency((int)Configuration::get('PS_CURRENCY_DEFAULT'));

		$groups = Group::getGroups($this->context->language->id);
		$groups_off = array(Configuration::get('PS_UNIDENTIFIED_GROUP'), Configuration::get('PS_GUEST_GROUP'));
		$allowed_groups = Tools::getValue('rsponsorship_groups', explode(',', Configuration::get('RSPONSORSHIP_GROUPS')));
		$categories = Tools::getValue('categoryBox', explode(',', MyConf::get('RSPONSORSHIP_CATEGORIES_GC', null, $this->id_template)));
		$code_cart_rule = Tools::getValue('real_code_gc', MyConf::get('RSPONSORSHIP_REAL_CODE_GC', null, $this->id_template));
		$cart_rule = new CartRule((int)CartRule::getIdByCode($code_cart_rule));
		$token = Tools::getAdminToken('AdminCartRules'.(int)Tab::getIdFromClassName('AdminCartRules').(int)$this->context->employee->id);

		$html = $this->getTemplateForm($this->id_template, $this->name, $this->l('Sponsorship')).'
		<div class="tabs" style="display: none">
			<ul>
				<li><a href="#tabs-'.$this->name.'-1">'.$this->l('Settings').'</a></li>
				<li class="not_templated"><a href="#tabs-'.$this->name.'-2">'.$this->l('Notifications').'</a></li>
				<li><a href="#tabs-'.$this->name.'-3">'.$this->l('Texts').'</a></li>
				<li class="not_templated"><a href="'.$this->instance->getCurrentPage($this->name, true).'&stats=1">'.$this->l('Statistics').'</a></li>
			</ul>
			<div id="tabs-'.$this->name.'-1">
				<form action="'.$this->instance->getCurrentPage($this->name).'" method="post">
				<input type="hidden" name="tabs-'.$this->name.'" value="tabs-'.$this->name.'-1" />
				<fieldset>
					<legend>'.$this->l('General settings').'</legend>
					<div>
						<label>'.$this->l('Activate sponsorship program').'</label>
						<div class="margin-form">
							<label class="t" for="sponsorship_active_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="sponsorship_active_on" name="sponsorship_active" value="1" '.(Tools::getValue('sponsorship_active', MyConf::get('RSPONSORSHIP_ACTIVE', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="sponsorship_active_on">' . $this->l('Yes') . '</label>
							<label class="t" for="sponsorship_active_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="sponsorship_active_off" name="sponsorship_active" value="0" '.(Tools::getValue('sponsorship_active', MyConf::get('RSPONSORSHIP_ACTIVE', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="sponsorship_active_off">' . $this->l('No') . '</label>
						</div>
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Give a reward to the sponsor for his friends\' registrations').'</label>
					<div class="margin-form">
						<label class="t" for="reward_registration_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="reward_registration_on" name="reward_registration" value="1" '.(Tools::getValue('reward_registration', MyConf::get('RSPONSORSHIP_REWARD_REGISTRATION', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="reward_registration_on">' . $this->l('Yes') . '</label>
						<label class="t" for="reward_registration_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="reward_registration_off" name="reward_registration" value="0" '.(Tools::getValue('reward_registration', MyConf::get('RSPONSORSHIP_REWARD_REGISTRATION', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="reward_registration_off">' . $this->l('No') . '</label>
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Give a reward to the sponsor for his friends\' orders').'</label>
					<div class="margin-form">
						<label class="t" for="reward_order_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="reward_order_on" name="reward_order" value="1" '.(Tools::getValue('reward_order', MyConf::get('RSPONSORSHIP_REWARD_ORDER', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="reward_order_on">' . $this->l('Yes') . '</label>
						<label class="t" for="reward_order_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="reward_order_off" name="reward_order" value="0" '.(Tools::getValue('reward_order', MyConf::get('RSPONSORSHIP_REWARD_ORDER', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="reward_order_off">' . $this->l('No') . '</label>
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Give a welcome voucher to the sponsored friend').'</label>
					<div class="margin-form">
						<label class="t" for="discount_gc_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="discount_gc_on" name="discount_gc" value="1" '.(Tools::getValue('discount_gc', MyConf::get('RSPONSORSHIP_DISCOUNT_GC', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="discount_gc_on">' . $this->l('Yes') . '</label>
						<label class="t" for="discount_gc_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="discount_gc_off" name="discount_gc" value="0" '.(Tools::getValue('discount_gc', MyConf::get('RSPONSORSHIP_DISCOUNT_GC', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="discount_gc_off">' . $this->l('No') . '</label>
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Display a sponsorship link on the product page').'</label>
					<div class="margin-form">
						<label class="t" for="product_share_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="product_share_on" name="sponsorship_product_share" value="1" '.(Tools::getValue('sponsorship_product_share', MyConf::get('RSPONSORSHIP_PRODUCT_SHARE', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="product_share_on">' . $this->l('Yes') . '</label>
						<label class="t" for="product_share_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="product_share_off" name="sponsorship_product_share" value="0" '.(Tools::getValue('sponsorship_product_share', MyConf::get('RSPONSORSHIP_PRODUCT_SHARE', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="product_share_off">' . $this->l('No') . '</label>
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Propose the sponsorship program on the order confirmation page').'</label>
					<div class="margin-form">
						<label class="t" for="after_order_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="after_order_on" name="after_order" value="1" '.(Tools::getValue('after_order', MyConf::get('RSPONSORSHIP_AFTER_ORDER', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="after_order_on">' . $this->l('Yes') . '</label>
						<label class="t" for="after_order_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="after_order_off" name="after_order" value="0" '.(Tools::getValue('after_order', MyConf::get('RSPONSORSHIP_AFTER_ORDER', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="after_order_off">' . $this->l('No') . '</label>
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Open a popup to propose sponsorship program to customers').'</label>
					<div class="margin-form">
						<label class="t" for="popup_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="popup_on" name="popup" value="1" '.(Tools::getValue('popup', MyConf::get('RSPONSORSHIP_POPUP', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="popup_on">' . $this->l('Yes') . '</label>
						<label class="t" for="popup_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="popup_off" name="popup" value="0" '.(Tools::getValue('popup', MyConf::get('RSPONSORSHIP_POPUP', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="popup_off">' . $this->l('No') . '</label>
					</div>
					<div class="clear optional popup_optional">
						<div class="clear"></div>
						<label>'.$this->l('Reset the last opening date for all customers').'</label>
						<div class="margin-form">
							<label class="t" for="popup_reset_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="popup_reset_on" name="popup_reset" value="1" /> <label class="t" for="popup_reset_on">' . $this->l('Yes') . '</label>
							<label class="t" for="popup_reset_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="popup_reset_off" name="popup_reset" value="0" checked /> <label class="t" for="popup_reset_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Delay before opening the popup again for the same customer (in days)').'</label>
						<div class="margin-form">
							<input type="text" size="2" maxlength="2" name="popup_delay" id="popup_delay" value="'.(Tools::getValue('popup_delay', MyConf::get('RSPONSORSHIP_POPUP_DELAY', null, $this->id_template))).'" />
						</div>
					</div>
					<div class="clear"></div>
					<label class="t">'.$this->l('Settings for sponsorship shared on Facebook').'</label>
					<div class="clear" style="padding-top: 5px"></div>
					<label class="indent">'.$this->l('Title to force for Facebook share').'</label>
					<div class="margin-form translatable">';
		$share_title = Tools::getValue('share_title');
		foreach ($languages as $language) {
			$html .= '
						<div class="lang_'.$language['id_lang'].'" id="share_title_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
							<input size="60" type="text" name="share_title['.$language['id_lang'].']" value="'.htmlentities($share_title[(int)$language['id_lang']] ? $share_title[(int)$language['id_lang']] : MyConf::get('RSPONSORSHIP_SHARE_TITLE', (int)$language['id_lang'], $this->id_template), ENT_QUOTES, 'utf-8').'" />
						</div>';
		}
		$html .= '
					</div>
					<div class="clear"></div>
					<label class="indent">'.$this->l('Description to force for Facebook share').'</label>
					<div class="margin-form translatable">';
		$share_description = Tools::getValue('share_description');
		foreach ($languages as $language) {
			$html .= '
						<div class="lang_'.$language['id_lang'].'" id="share_description_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
							<input size="60" type="text" name="share_description['.$language['id_lang'].']" value="'.htmlentities($share_description[(int)$language['id_lang']] ? $share_description[(int)$language['id_lang']] : MyConf::get('RSPONSORSHIP_SHARE_DESCRIPTION', (int)$language['id_lang'], $this->id_template), ENT_QUOTES, 'utf-8').'" />
						</div>';
		}
		$html .= '
					</div>
					<div class="clear"></div>
					<label class="indent">'.$this->l('Url of the image to force for Facebook share (at least 200*200px)').'</label>
					<div class="margin-form">
						<input type="text" size="60" name="share_image_url" id="share_image_url" value="'.(Tools::getValue('share_url_image', MyConf::get('RSPONSORSHIP_SHARE_IMAGE_URL', null, $this->id_template))).'" />
						<br/><a href="https://developers.facebook.com/tools/debug/sharing/?q='.$this->context->link->getPageLink('index', true, $this->context->language->id, 's=123456').'">'.$this->l('Flush Facebook cache and test your settings').'</a> ('.$this->l('save your settings first').')
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Number of lines displayed in the invitation form').'</label>
					<div class="margin-form">
						<input type="text" size="3" maxlength="3" name="nb_friends" id="nb_friends" value="'.(int)(Tools::getValue('nb_friends', MyConf::get('RSPONSORSHIP_NB_FRIENDS', null, $this->id_template))).'" />
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Number of orders to be able to become a sponsor (0 is allowed)').'</label>
					<div class="margin-form">
						<input type="text" size="3" maxlength="3" name="order_quantity_s" id="order_quantity_s" value="'.(int)(Tools::getValue('order_quantity_s', MyConf::get('RSPONSORSHIP_ORDER_QUANTITY_S', null, $this->id_template))).'" />
					</div>
					<div class="clear not_templated">
						<label>'.$this->l('Customers groups allowed to sponsor their friends').'</label>
						<div class="margin-form">
							<select name="rsponsorship_groups[]" multiple="multiple" class="multiselect">';
		foreach($groups as $group) {
			if (!in_array($group['id_group'], $groups_off))
				$html .= '			<option '.(is_array($allowed_groups) && in_array($group['id_group'], $allowed_groups) ? 'selected':'').' value="'.$group['id_group'].'"> '.$group['name'].'</option>';
		}
		$html .= '
							</select>
						</div>
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Redirection of the sponsorship link').'</label>
					<div class="margin-form">
						<select name="sponsorship_redirect" id="sponsorship_redirect">
							<option '.(Tools::getValue('sponsorship_redirect', MyConf::get('RSPONSORSHIP_REDIRECT', null, $this->id_template))=='home' ? 'selected' : '').' value="home">'.$this->l('No redirection').'</option>
							<option '.(Tools::getValue('sponsorship_redirect', MyConf::get('RSPONSORSHIP_REDIRECT', null, $this->id_template))=='form' ? 'selected' : '').' value="form">'.$this->l('Subscription form').'</option>
							<optgroup label="'.$this->l('CMS page').'">';
        foreach (CMS::listCms($this->context->language->id) as $cms_file) {
        	$html .= '			<option '.(Tools::getValue('sponsorship_redirect', MyConf::get('RSPONSORSHIP_REDIRECT', null, $this->id_template))==$cms_file['id_cms'] ? 'selected' : '').' value="'.$cms_file['id_cms'].'" style="text-indent: 20px">'.$cms_file['meta_title'].'</option>';
        }
        $html .= '
							</optgroup>
						</select>
					</div>
				</fieldset>
				<fieldset class="registration_optional">
					<legend>'.$this->l('Sponsor\'s settings - Rewards for registrations').'</legend>
					<table class="reward_for_registration">
						<thead>
							<tr>
								<th>&nbsp;</th>
								<th>'.$this->l('Nb of friends registrations').'</th>
								<th>'.$this->l('Repeat while it\'s lower or equal to').'</th>
								<th>'.$this->l('Reward by registration').'</th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>';
		$sponsorship_registr_multiple = Tools::getValue('rsponsorship_registr_multiple', explode(',', MyConf::get('RSPONSORSHIP_REGISTR_MULTIPLE', null, $this->id_template)));
		$sponsorship_registr_repeat = Tools::getValue('rsponsorship_registr_repeat', explode(',', MyConf::get('RSPONSORSHIP_REGISTR_REPEAT', null, $this->id_template)));
		$sponsorship_registr_value = Tools::getValue('rsponsorship_registr_value', explode(',', MyConf::get('RSPONSORSHIP_REGISTR_VALUE', null, $this->id_template)));
		if (!is_array($sponsorship_registr_multiple) || count($sponsorship_registr_multiple) == 0) {
			$sponsorship_registr_multiple = array('');
			$sponsorship_registr_repeat = array('');
			$sponsorship_registr_value = array('');
		}
		foreach($sponsorship_registr_multiple as $key => $value) {
			$html .= '
							<tr>
								<td>'.$this->l('Rule #').'<span class="numrule">'.($key+1).'</span></td>
								<td>'.$this->l('is multiple of').' <input type="text" name="rsponsorship_registr_multiple[]" value="'.$sponsorship_registr_multiple[$key].'" size="8" maxlength="8"></td>
								<td><input type="text" name="rsponsorship_registr_repeat[]" value="'.$sponsorship_registr_repeat[$key].'" size="8" maxlength="8"> '.$this->l('(0 = unlimited)').'</td>
								<td><input class="notvirtual" type="text" size="8" maxlength="8" name="rsponsorship_registr_value[]" value="'.$sponsorship_registr_value[$key].'" onBlur="showVirtualValue(this, '.$defaultCurrency->id.', true)" /> <label class="t">'.$defaultCurrency->sign.' <span class="virtualvalue"></span></label></td>
								<td><a href="#" onClick="return delSponsorshipRegistrationRule(this)"><img src="../img/admin/delete.gif" alt="'.$this->l('Delete this rule').'" align="absmiddle"></a></td>
							</tr>';
		}
		$html .= '
						</tbody>
					</table>
					<div class="clear center">
						<input class="button" style="margin-top: 10px" id="add_rule" value="'.$this->l('Add a rule').'" type="button" />
					</div>
				</fieldset>
				<fieldset class="order_optional">
					<legend>'.$this->l('Sponsor\'s settings - Rewards for orders').'</legend>
					<label>'.$this->l('Duration of the sponsorship (in days, 0=unlimited)').'</label>
					<div class="margin-form">
						<input type="text" size="4" maxlength="4" id="rsponsorship_duration" name="rsponsorship_duration" value="'.Tools::getValue('rsponsorship_duration', MyConf::get('RSPONSORSHIP_DURATION', null, $this->id_template)).'" />
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Get a reward for every orders from a sponsored friend, when total > 0 (shipping excluded)').'</label>
					<div class="margin-form">
						<label class="t" for="discount_gc_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="reward_on_every_order_on" name="reward_on_every_order" value="1" '.(Tools::getValue('reward_on_every_order', MyConf::get('RSPONSORSHIP_ON_EVERY_ORDER', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="discount_gc_on">'.$this->l('Yes').'</label>
						<label class="t" for="discount_gc_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="reward_on_every_order_off" name="reward_on_every_order" value="0" '.(Tools::getValue('reward_on_every_order', MyConf::get('RSPONSORSHIP_ON_EVERY_ORDER', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="discount_gc_off">'.$this->l('No, only for the first one').'</label>
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Take in account the discounted products to calculate the total').'</label>
					<div class="margin-form">
						<label class="t" for="rsponsorship_discounted_allowed_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="rsponsorship_discounted_allowed_on" name="rsponsorship_discounted_allowed" value="1" '.(Tools::getValue('rsponsorship_discounted_allowed', MyConf::get('RSPONSORSHIP_DISCOUNTED_ALLOWED', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="rsponsorship_discounted_allowed_on">' . $this->l('Yes') . '</label>
						<label class="t" for="rsponsorship_discounted_allowed_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="rsponsorship_discounted_allowed_off" name="rsponsorship_discounted_allowed" value="0" '.(Tools::getValue('rsponsorship_discounted_allowed', MyConf::get('RSPONSORSHIP_DISCOUNTED_ALLOWED', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="rsponsorship_discounted_allowed_off">' . $this->l('No') . '</label>
					</div>
					<div class="clear"></div>
						<label>'.$this->l('Price to use to calculate the total (when the customer pays the VAT)').'</label>
						<div class="margin-form">
							<input type="radio" id="rsponsorship_tax_off" name="rsponsorship_tax" value="0" '.(Tools::getValue('rsponsorship_tax', MyConf::get('RSPONSORSHIP_TAX', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="rsponsorship_tax_off">' . $this->l('VAT Excl.') . '</label>
							<input type="radio" id="rsponsorship_tax_on" name="rsponsorship_tax" value="1" '.(Tools::getValue('rsponsorship_tax', MyConf::get('RSPONSORSHIP_TAX', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="rsponsorship_tax_on">' . $this->l('VAT Incl.') . '</label>
						</div>
					<div class="clear" style="padding-top: 10px"></div>
					<label class="t" style="width: 100% !important"><strong>'.$this->l('Minimum amount required for the sponsored\'s order to unlock the sponsor\'s reward (discounted products included)').'</strong></label>
					<div class="clear" style="padding-top: 5px"></div>
					<label class="indent">'.$this->l('Calculated using').'</label>
					<div class="margin-form">
						<input type="radio" id="unlock_shipping_on" name="unlock_shipping" value="1" '.(Tools::getValue('unlock_shipping', MyConf::get('RSPONSORSHIP_UNLOCK_SHIPPING', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="unlock_shipping_on" style="padding-right: 10px">' . $this->l('Total with shipping included') . '</label>
						<input type="radio" id="unlock_shipping_off" name="unlock_shipping" value="0" '.(Tools::getValue('unlock_shipping', MyConf::get('RSPONSORSHIP_UNLOCK_SHIPPING', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="unlock_shipping_off">' . $this->l('Total without shipping') . '</label>
					</div>
					<div class="clear">';
		foreach ($currencies as $currency) {
			$html .= '
						<div class="clear"></div>
						<label class="indent">'.$this->l('Minimum value for an order in').' '.htmlentities($currency['name'], ENT_NOQUOTES, 'utf-8').'</label>
						<div class="margin-form"><input '. ((int)$currency['id_currency'] == (int)Configuration::get('PS_CURRENCY_DEFAULT') ? 'class="currency_default"' : '') . ' type="text" size="8" maxlength="8" name="unlock_gc_'.$currency['id_currency'].'" id="unlock_gc_'.$currency['id_currency'].'" value="'.Tools::getValue('unlock_gc_'.$currency['id_currency'], MyConf::get('RSPONSORSHIP_UNLOCK_GC_'.$currency['id_currency'], null, $this->id_template)).'" /> <label class="t">'.$currency['sign'].'</label>'.((int)$currency['id_currency'] != (int)Configuration::get('PS_CURRENCY_DEFAULT') ? ' <a href="#" onClick="return convertCurrencyValue(this, \'unlock_gc\', '.$currency['conversion_rate'].')"><img src="'._MODULE_DIR_.'allinone_rewards/img/convert.gif" style="vertical-align: middle !important"></a>' : '').'</div>';
		}
		$html .= '
					</div>
					<div class="clear" style="padding-top: 10px"></div>
					<label class="t" style="width: 100% !important"><strong>'.$this->l('If you want to reward the sponsors on several levels, you can define as many levels as necessary').'</strong></label>';

		foreach($this->_configuration['reward_type'] as $level => $reward_type) {
			$html .= '
					<div class="clear level_information">
						<label class="level"><strong>'.$this->l('Reward\'s settings for level').' <span class="numlevel">'.($level + 1).'</span></strong> <a href="#" onClick="return delSponsorshipLevel(this)"><img src="../img/admin/delete.gif" alt="'.$this->l('Delete this level').'" align="absmiddle"></a></label>
						<div class="clear"></div>
						<label class="indent">'.$this->l('Reward type').'</label>
						<div class="margin-form">
							<input type="radio" onClick="checkType($(this))" name="reward_type_s['.$level.']" value="1" '.($reward_type == 1 ? 'checked="checked"' : '').' /> <label class="t" style="padding-right: 10px">'.$this->l('Fixed amount').'</label>
							<input type="radio" onClick="checkType($(this))" name="reward_type_s['.$level.']" value="2" '.($reward_type == 2 ? 'checked="checked"' : '').' /> <label class="t">'.$this->l('% of the order\'s total (shipping excluded)').'</label>
							<input type="radio" onClick="checkType($(this))" name="reward_type_s['.$level.']" value="3" '.($reward_type == 3 ? 'checked="checked"' : '').' /> <label class="t">'.$this->l('Product per product').'</label>
						</div>
						<div class="reward_amount">';
			foreach ($currencies as $currency) {
				$html .= '
							<div class="clear"></div>
							<label class="indent">'.$this->l('Reward value for an order in').' '.htmlentities($currency['name'], ENT_NOQUOTES, 'utf-8').'</label>
							<div class="margin-form"><input class="notvirtual '. ((int)$currency['id_currency'] == (int)Configuration::get('PS_CURRENCY_DEFAULT') ? 'currency_default' : '') . '" type="text" size="8" maxlength="8" name="reward_value_s['.(int)($currency['id_currency']).']['.$level.']" value="'.$this->_configuration['reward_value'][$level][$currency['id_currency']].'" onBlur="showVirtualValue(this, '.$currency['id_currency'].', true)" /> <label class="t">'.$currency['sign'].' <span class="virtualvalue"></span></label>'.((int)$currency['id_currency'] != (int)Configuration::get('PS_CURRENCY_DEFAULT') ? ' <a href="#" onClick="return convertCurrencyValue(this, \'reward_value_s\', '.$currency['conversion_rate'].')"><img src="'._MODULE_DIR_.'allinone_rewards/img/convert.gif" style="vertical-align: middle !important"></a>' : '').'</div>';
			}
			$html .= '
						</div>
						<div class="clear"></div>
						<div class="reward_percentage">
							<label class="indent">'.$this->l('Percentage').'</label>
							<div class="margin-form">
								<input type="text" size="3" name="reward_percentage['.$level.']" value="'.$this->_configuration['reward_percentage'][$level].'" /> %
							</div>
						</div>
						<div class="clear reward_product_per_product">
							<label></label>
							<div class="margin-form">'.$this->l('You can configure each product individually from the product sheet').'</div>
							<div class="clear"></div>
							<label class="indent">'.$this->l('Default reward for product with no custom value').'</label>
							<div class="margin-form">
								<input class="notvirtual product_per_product" type="text" size="3" name="rsponsorship_def_product_reward['.$level.']" value="'.$this->_configuration['default_product_reward'][$level].'" onBlur="showVirtualValue(this, '.$defaultCurrency->id.', true)" />
								<select class="product_per_product" name="rsponsorship_def_product_type['.$level.']" onChange="showVirtualValue(this, '.$defaultCurrency->id.', true)">
									<option '.($this->_configuration['default_product_reward'][$level] == 0 ? 'selected' : '').' value="0">% '.$this->l('of its own price').'</option>
									<option '.($this->_configuration['default_product_type'][$level] == 1 ? 'selected' : '').' value="1">'.$defaultCurrency->sign.'</option>
								</select>
								&nbsp;<span class="virtualvalue"></span>
							</div>
						</div>
					</div>';
		}
		$html .= '
					<div class="clear">
						<label>'.$this->l('All next levels will use the settings from level').' <span id="unlimited_level">'.count($this->_configuration['reward_type']).'</span></label>
						<div class="margin-form">
							<label class="t" for="unlimited_levels_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="unlimited_levels_on" name="unlimited_levels" value="1" '.(Tools::getValue('unlimited_levels', $this->_configuration['unlimited']) ? 'checked="checked"' : '').' /> <label class="t" for="unlimited_levels_on">' . $this->l('Yes') . '</label>
							<label class="t" for="unlimited_levels_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="unlimited_levels_off" name="unlimited_levels" value="0" '.(Tools::getValue('unlimited_levels', MyConf::get('RSPONSORSHIP_UNLIMITED_LEVELS', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="unlimited_levels_off">' . $this->l('No, we don\'t need any additional levels') . '</label>
						</div>
						<div class="clear center">
							<input class="button" style="margin-top: 10px" id="add_level" value="'.$this->l('Add a level').'" type="button" />
						</div>
					</div>
				</fieldset>
				<fieldset id="sponsored" class="sponsored_optional">
					<legend>'.$this->l('Sponsored\'s settings').'</legend>
					'.$this->l('You can manually set all settings for the voucher offered to the sponsored friend, or choose a model among the pre-existing vouchers. In this case the model will be duplicated during the sponsored registration, with the same settings. That allows you a more accurate choice of the characteristics. For example, you can offer a free product, put restrictions on carriers or on products concerned by the discount...').'
					<div class="clear"></div>
					<br/><label>'.$this->l('Voucher to offer').'</label>
					<div class="margin-form">
						<input type="radio" id="real_voucher_gc_off" name="real_voucher_gc" value="0" '.(Tools::getValue('real_voucher_gc', MyConf::get('RSPONSORSHIP_REAL_VOUCHER_GC', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="real_voucher_gc_off">' . $this->l('Define the settings') . '</label>
						<input type="radio" id="real_voucher_gc_on" name="real_voucher_gc" value="1" '.(Tools::getValue('real_voucher_gc', MyConf::get('RSPONSORSHIP_REAL_VOUCHER_GC', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="real_voucher_gc_on">' . $this->l('Choose an existing voucher (more possibilities)') . '</label>
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Prefix for the voucher code (at least 3 letters long)').'</label>
					<div class="margin-form">
						<input type="text" size="10" maxlength="10" id="voucher_prefix_gc" name="voucher_prefix_gc" value="'.Tools::getValue('voucher_prefix_gc', MyConf::get('RSPONSORSHIP_VOUCHER_PREFIX_GC', null, $this->id_template)).'" />
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Validity of the voucher (in days)').'</label>
					<div class="margin-form">
						<input type="text" size="4" maxlength="4" id="voucher_duration_gc" name="voucher_duration_gc" value="'.Tools::getValue('voucher_duration_gc', MyConf::get('RSPONSORSHIP_VOUCHER_DURATION_GC', null, $this->id_template)).'" />
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Voucher details (will appear in cart next to voucher code)').'</label>
					<div class="margin-form translatable">';
		$description_gc = Tools::getValue('description_gc');
		foreach ($languages as $language) {
			$html .= '
						<div class="lang_'.$language['id_lang'].'" id="descgc_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
							<input size="30" type="text" name="description_gc['.$language['id_lang'].']" value="'.htmlentities($description_gc[(int)$language['id_lang']] ? $description_gc[(int)$language['id_lang']] : MyConf::get('RSPONSORSHIP_VOUCHER_DETAILS', (int)$language['id_lang'], $this->id_template), ENT_QUOTES, 'utf-8').'" />
						</div>';
		}
		$html .= '
					</div>
					<div class="clear real_voucher_on_optional">
						<label>'.$this->l('Code of the voucher model (must be available for all and not highlighted)').'</label>
						<div class="margin-form">
							<input type="text" size="12" id="real_code_gc" name="real_code_gc" value="'.Tools::getValue('real_code_gc', MyConf::get('RSPONSORSHIP_REAL_CODE_GC', null, $this->id_template)).'" />'.(Validate::isLoadedObject($cart_rule) ? '&nbsp<a href="?tab=AdminCartRules&id_cart_rule='.$cart_rule->id.'&updatecart_rule&token='.$token.'" target="_blank">'.$this->l('View or edit the voucher model').'</a>' : '').'
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Description of the voucher that will be used in the emails').'<br/><small>'.$this->l('Example: This voucher will give you a surprise gift').'</small></label>
						<div class="margin-form translatable">';
		$real_description_gc = Tools::getValue('real_description_gc');
		foreach ($languages as $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" id="real_description_gc_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
								<textarea class="rte autoload_rte" cols="120" rows="25" name="real_description_gc['.$language['id_lang'].']">'.($real_description_gc[(int)$language['id_lang']] ? $real_description_gc[(int)$language['id_lang']] : MyConf::get('RSPONSORSHIP_REAL_DESC_GC', (int)$language['id_lang'], $this->id_template)).'</textarea>
							</div>';
		}
		$html .= '
						</div>
					</div>
					<div class="clear real_voucher_off_optional">
						<label>'.$this->l('Number of times the voucher can be used by the sponsored friend').'</label>
						<div class="margin-form">
							<input type="text" size="4" maxlength="4" id="voucher_quantity_gc" name="voucher_quantity_gc" value="'.Tools::getValue('voucher_quantity_gc', MyConf::get('RSPONSORSHIP_QUANTITY_GC', null, $this->id_template)).'" />
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Free shipping').'</label>
						<div class="margin-form">
							<label class="t" for="freeshipping_gc_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="freeshipping_gc_on" name="freeshipping_gc" value="1" '.(Tools::getValue('freeshipping_gc', MyConf::get('RSPONSORSHIP_FREESHIPPING_GC', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="freeshipping_gc_on">' . $this->l('Yes') . '</label>
							<label class="t" for="freeshipping_gc_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="freeshipping_gc_off" name="freeshipping_gc" value="0" '.(Tools::getValue('freeshipping_gc', MyConf::get('RSPONSORSHIP_FREESHIPPING_GC', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="freeshipping_gc_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Apply a discount').'</label>
						<div class="margin-form">
							<input onClick="$(\'#sponsored td.voucher_value\').html(\'' . $this->l('Voucher %') . '\');$(\'#sponsored td.value_cols\').show();$(\'#behavior_gc\').hide();$(\'#voucher_behavior_gc\').val(0)" type="radio" id="discount_type_gc_1" name="discount_type_gc" value="1" '.(Tools::getValue('discount_type_gc', MyConf::get('RSPONSORSHIP_DISCOUNT_TYPE_GC', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="discount_type_gc_1" style="padding-right: 10px">'.$this->l('Percentage').'</label>
							<input onClick="$(\'#sponsored td.value_cols\').hide();$(\'#sponsored td.voucher_value\').html(\'' . $this->l('Voucher value') . '\');$(\'#sponsored td.value_cols\').show();$(\'#behavior_gc\').show()" type="radio" id="discount_type_gc_2" name="discount_type_gc" value="2" '.(Tools::getValue('discount_type_gc', MyConf::get('RSPONSORSHIP_DISCOUNT_TYPE_GC', null, $this->id_template)) == 2 ? 'checked="checked"' : '').' /> <label class="t" for="discount_type_gc_2" style="padding-right: 10px">'.$this->l('Amount').'</label>
							<input onClick="$(\'#sponsored td.value_cols\').hide();$(\'#behavior_gc\').hide();$(\'#voucher_behavior_gc\').val(0)" type="radio" id="discount_type_gc_0" name="discount_type_gc" value="0" '.(Tools::getValue('discount_type_gc', MyConf::get('RSPONSORSHIP_DISCOUNT_TYPE_GC', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="discount_type_gc_0">'.$this->l('None').'</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Allowed categories').'</label>
						<div class="margin-form">
							<input class="all_categories" type="radio" id="all_categories_on" name="rsponsorship_all_categories" value="0" '.(!Tools::getValue('rsponsorship_all_categories', MyConf::get('RSPONSORSHIP_ALL_CATEGORIES', null, $this->id_template)) ? 'checked="checked"' : '').' /> <label class="t" for="all_categories_on">' . $this->l('Choose categories') . '</label>&nbsp;
							<input class="all_categories" type="radio" id="all_categories_off" name="rsponsorship_all_categories" value="1" '.(Tools::getValue('rsponsorship_all_categories', MyConf::get('RSPONSORSHIP_ALL_CATEGORIES', null, $this->id_template)) ? 'checked="checked"' : '').' /> <label class="t" for="all_categories_off">' . $this->l('All categories') . '</label>
							<div class="optional categories_optional" style="padding-top: 15px">
								'.$this->getCategoriesTree($categories).'
							</div>
						</div>
						<div class="clear"></div>
						<div id="behavior_gc" style="display:'.(Tools::getValue('discount_type_gc', MyConf::get('RSPONSORSHIP_DISCOUNT_TYPE_GC', null, $this->id_template)) == 2 ? 'block' : 'none').'">
							<div class="clear"></div>
							<label>'.$this->l('If the voucher is not depleted when used').'</label>&nbsp;
							<div class="margin-form">
								<select name="voucher_behavior_gc" id="voucher_behavior_gc">
									<option '.(!Tools::getValue('voucher_behavior_gc', (int)MyConf::get('RSPONSORSHIP_VOUCHER_BEHAVIOR', null, $this->id_template)) ?'selected':'').' value="0">'.$this->l('Cancel the remaining amount').'</option>
									<option '.(Tools::getValue('voucher_behavior_gc', (int)MyConf::get('RSPONSORSHIP_VOUCHER_BEHAVIOR', null, $this->id_template)) ?'selected':'').' value="1">'.$this->l('Create a new voucher with remaining amount').'</option>
								</select>
							</div>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Cumulative with other vouchers').'</label>
						<div class="margin-form">
							<label class="t" for="cumulative_voucher_gc_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="cumulative_voucher_gc_on" name="cumulative_voucher_gc" value="1" '.(Tools::getValue('cumulative_voucher_gc', MyConf::get('RSPONSORSHIP_CUMUL_GC', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="cumulative_voucher_gc_on">' . $this->l('Yes') . '</label>
							<label class="t" for="cumulative_voucher_gc_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="cumulative_voucher_gc_off" name="cumulative_voucher_gc" value="0" '.(Tools::getValue('cumulative_voucher_gc', MyConf::get('RSPONSORSHIP_CUMUL_GC', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="cumulative_voucher_gc_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('The minimum order\'s amount to use the voucher includes tax').'</label>
						<div class="margin-form">
							<label class="t" for="include_tax_gc_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="include_tax_gc_on" name="include_tax_gc" value="1" '.(Tools::getValue('include_tax_gc', MyConf::get('RSPONSORSHIP_MINIMAL_TAX_GC', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="include_tax_gc_on">' . $this->l('Yes') . '</label>
							<label class="t" for="include_tax_gc_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="include_tax_gc_off" name="include_tax_gc" value="0" '.(Tools::getValue('include_tax_gc', MyConf::get('RSPONSORSHIP_MINIMAL_TAX_GC', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="include_tax_gc_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<div>
							<table>
								<tr>
									<td class="label" style="font-weight: bold">' . $this->l('Currency used by the sponsored when registering') . '</td>
									<td width="165" class="voucher_value value_cols" style="font-weight: bold; display:'.(Tools::getValue('discount_type_gc', MyConf::get('RSPONSORSHIP_DISCOUNT_TYPE_GC', null, $this->id_template)) == 0 ? 'none' : 'block').'">' . (Tools::getValue('discount_type_gc', MyConf::get('RSPONSORSHIP_DISCOUNT_TYPE_GC', null, $this->id_template)) == 1 ? $this->l('Voucher %') : $this->l('Voucher value')) . '</td>
									<td class="value_cols" style="width: 30px">&nbsp;</td>
									<td width="200" style="font-weight: bold">' . $this->l('Minimum order\'s amount') . '</td>
								</tr>';
		foreach ($currencies as $currency) {
			$html .= '
								<tr>
									<td><label class="indent">' . htmlentities($currency['name'], ENT_NOQUOTES, 'utf-8') . '</label></td>
									<td align="left" class="value_cols" style="display:'.(Tools::getValue('discount_type_gc', MyConf::get('RSPONSORSHIP_DISCOUNT_TYPE_GC', null, $this->id_template)) == 0 ? 'none' : 'block').'"><input '. ((int)$currency['id_currency'] == (int)Configuration::get('PS_CURRENCY_DEFAULT') ? 'class="currency_default"' : '') . ' type="text" size="8" maxlength="8" name="discount_value_gc_'.$currency['id_currency'].'" id="discount_value_gc_'.$currency['id_currency'].'" value="'.Tools::getValue('discount_value_gc_'.$currency['id_currency'], MyConf::get('RSPONSORSHIP_VOUCHER_VALUE_GC_'.$currency['id_currency'], null, $this->id_template)).'" />'.((int)$currency['id_currency'] != (int)Configuration::get('PS_CURRENCY_DEFAULT') ? ' <a href="#" onClick="return convertCurrencyValue(this, \'discount_value_gc\', '.$currency['conversion_rate'].')"><img src="'._MODULE_DIR_.'allinone_rewards/img/convert.gif" style="vertical-align: middle !important"></a>' : '').'</td>
									<td class="value_cols">&nbsp;</td>
									<td align="left"><input '. ((int)$currency['id_currency'] == (int)Configuration::get('PS_CURRENCY_DEFAULT') ? 'class="currency_default"' : '') . ' type="text" size="8" maxlength="8" name="minimum_value_gc_'.$currency['id_currency'].'" id="minimum_value_gc_'.$currency['id_currency'].'" value="'.Tools::getValue('minimum_value_gc_'.$currency['id_currency'], MyConf::get('RSPONSORSHIP_MINIMUM_VALUE_GC_'.$currency['id_currency'], null, $this->id_template)).'" />'.((int)$currency['id_currency'] != (int)Configuration::get('PS_CURRENCY_DEFAULT') ? ' <a href="#" onClick="return convertCurrencyValue(this, \'minimum_value_gc\', '.$currency['conversion_rate'].')"><img src="'._MODULE_DIR_.'allinone_rewards/img/convert.gif" style="vertical-align: middle !important"></a>' : '').'</td>
								</tr>';
		}
		$html .= '
							</table>
						</div>
					</div>
				</fieldset>
				<div class="clear center"><input class="button" name="submitSponsorship" id="submitSponsorship" value="'.$this->l('Save settings').'" type="submit" /></div>
				</form>
			</div>
			<div id="tabs-'.$this->name.'-2" class="not_templated">
				<form action="'.$this->instance->getCurrentPage($this->name).'" method="post">
				<input type="hidden" name="tabs-'.$this->name.'" value="tabs-'.$this->name.'-2" />
				<fieldset>
					<legend>'.$this->l('Notifications').'</legend>
					<label>'.$this->l('Send a mail to the admin on sponsored registration').'</label>
					<div class="margin-form">
						<label class="t" for="mail_admin_registration_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="mail_admin_registration_on" name="mail_admin_registration" value="1" '.(Tools::getValue('mail_admin_registration', Configuration::get('RSPONSORSHIP_MAIL_REGISTRATION')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="mail_admin_registration_on">' . $this->l('Yes') . '</label>
						<label class="t" for="mail_admin_registration_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="mail_admin_registration_off" name="mail_admin_registration" value="0" '.(Tools::getValue('mail_admin_registration', Configuration::get('RSPONSORSHIP_MAIL_REGISTRATION')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="mail_admin_registration_off">' . $this->l('No') . '</label>
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Send a mail to the admin on sponsored order').'</label>
					<div class="margin-form">
						<label class="t" for="mail_admin_order_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="mail_admin_order_on" name="mail_admin_order" value="1" '.(Tools::getValue('mail_admin_order', Configuration::get('RSPONSORSHIP_MAIL_ORDER')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="mail_admin_order_on">' . $this->l('Yes') . '</label>
						<label class="t" for="mail_admin_order_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="mail_admin_order_off" name="mail_admin_order" value="0" '.(Tools::getValue('mail_admin_order', Configuration::get('RSPONSORSHIP_MAIL_ORDER')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="mail_admin_order_off">' . $this->l('No') . '</label>
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Send a mail to the sponsor on sponsored registration').'</label>
					<div class="margin-form">
						<label class="t" for="mail_sponsor_registration_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="mail_sponsor_registration_on" name="mail_sponsor_registration" value="1" '.(Tools::getValue('mail_sponsor_registration', Configuration::get('RSPONSORSHIP_MAIL_REGISTRATION_S')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="mail_sponsor_registration_on">' . $this->l('Yes') . '</label>
						<label class="t" for="mail_sponsor_registration_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="mail_sponsor_registration_off" name="mail_sponsor_registration" value="0" '.(Tools::getValue('mail_sponsor_registration', Configuration::get('RSPONSORSHIP_MAIL_REGISTRATION_S')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="mail_sponsor_registration_off">' . $this->l('No') . '</label>
					</div>
					<div class="clear"></div>
					<div>
						<label>'.$this->l('Send a mail to the sponsor(s) on sponsored order').'</label>
						<div class="margin-form">
							<label class="t" for="mail_sponsor_order_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="mail_sponsor_order_on" name="mail_sponsor_order" value="1" '.(Tools::getValue('mail_sponsor_order', Configuration::get('RSPONSORSHIP_MAIL_ORDER_S')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="mail_sponsor_order_on">' . $this->l('Yes') . '</label>
							<label class="t" for="mail_sponsor_order_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="mail_sponsor_order_off" name="mail_sponsor_order" value="0" '.(Tools::getValue('mail_sponsor_order', Configuration::get('RSPONSORSHIP_MAIL_ORDER_S')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="mail_sponsor_order_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Send a mail to the sponsor(s) on reward validation/cancellation').'</label>
						<div class="margin-form">
							<label class="t" for="mail_sponsor_validation_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="mail_sponsor_validation_on" name="mail_sponsor_validation" value="1" '.(Tools::getValue('mail_sponsor_validation', Configuration::get('RSPONSORSHIP_MAIL_VALIDATION_S')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="mail_sponsor_validation_on">' . $this->l('Yes') . '</label>
							<label class="t" for="mail_sponsor_validation_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="mail_sponsor_validation_off" name="mail_sponsor_validation" value="0" '.(Tools::getValue('mail_sponsor_validation', Configuration::get('RSPONSORSHIP_MAIL_VALIDATION_S')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="mail_sponsor_validation_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Send a mail to the sponsor(s) on reward modification').'</label>
						<div class="margin-form">
							<label class="t" for="mail_sponsor_cancel_product_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="mail_sponsor_cancel_product_on" name="mail_sponsor_cancel_product" value="1" '.(Tools::getValue('mail_sponsor_cancel_product', Configuration::get('RSPONSORSHIP_MAIL_CANCELPROD_S')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="mail_sponsor_cancel_product_on">' . $this->l('Yes') . '</label>
							<label class="t" for="mail_sponsor_cancel_product_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="mail_sponsor_cancel_product_off" name="mail_sponsor_cancel_product" value="0" '.(Tools::getValue('mail_sponsor_cancel_product', Configuration::get('RSPONSORSHIP_MAIL_CANCELPROD_S')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="mail_sponsor_cancel_product_off">' . $this->l('No') . '</label>
						</div>
					</div>
				</fieldset>
				<div class="clear center"><input class="button" name="submitSponsorshipNotifications" id="submitSponsorshipNotifications" value="'.$this->l('Save settings').'" type="submit" /></div>
				</form>
			</div>
			<div id="tabs-'.$this->name.'-3">
				<form method="post" action="'.$this->instance->getCurrentPage($this->name).'" enctype="multipart/form-data">
					<input type="hidden" name="tabs-'.$this->name.'" value="tabs-'.$this->name.'-3" />
					<fieldset>
						<legend>'.$this->l('Text for the sponsorship form displayed in the customer account').'</legend>
						<div class="translatable">';
		foreach ($languages AS $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" id="account_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').';float: left;">
								<textarea class="rte autoload_rte" cols="120" rows="25" name="account_txt['.$language['id_lang'].']">'.MyConf::get('RSPONSORSHIP_ACCOUNT_TXT', (int)$language['id_lang'], $this->id_template).'</textarea>
							</div>';
		}
		$html .= '

						</div>
					</fieldset>
					<fieldset>
						<legend>'.$this->l('Text for the sponsorship popup displayed after an order').'</legend>
						<div class="translatable">';
		foreach ($languages AS $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" id="order_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').';float: left;">
								<textarea class="rte autoload_rte" cols="120" rows="25" name="order_txt['.$language['id_lang'].']">'.MyConf::get('RSPONSORSHIP_ORDER_TXT', (int)$language['id_lang'], $this->id_template).'</textarea>
							</div>';
		}
		$html .= '
						</div>
					</fieldset>
					<fieldset>
						<legend>'.$this->l('Text for the sponsorship popup displayed every X days').'</legend>
						<div class="translatable">';
		foreach ($languages AS $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" id="popup_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').';float: left;">
								<textarea class="rte autoload_rte" cols="120" rows="25" name="popup_txt['.$language['id_lang'].']">'.MyConf::get('RSPONSORSHIP_POPUP_TXT', (int)$language['id_lang'], $this->id_template).'</textarea>
							</div>';
		}
		$html .= '
						</div>
					</fieldset>
					<fieldset>
						<legend>'.$this->l('Sponsorship program rules').'</legend>
						<div class="translatable">';
		foreach ($languages AS $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" id="rules_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').';float: left;">
								<textarea class="rte autoload_rte" cols="120" rows="25" name="rules_txt['.$language['id_lang'].']">'.MyConf::get('RSPONSORSHIP_RULES_TXT', (int)$language['id_lang'], $this->id_template).'</textarea>
							</div>';
		}
		$html .= '
						</div>
					</fieldset>
					<div class="clear center"><input type="submit" name="submitSponsorshipText" value="'.$this->l('Save settings').'" class="button"/></div>
				</form>
			</div>
		</div>';
		return $html;
	}

	private function _getStatistics($id_sponsor=null)
	{
		$stats = RewardsSponsorshipModel::getAdminStatistics();
		$token = Tools::getAdminToken('AdminCustomers'.(int)Tab::getIdFromClassName('AdminCustomers').(int)$this->context->employee->id);
		if (!isset($id_sponsor)) {
			$result = "
			<div class='statistics'>
				<div>".$this->l('Only validated orders are taken in account in these statistics.')."</div>
				<div class='title'>".$this->l('General synthesis')."</div>
				<table class='general_sponsorship'>
					<tr class='title'>
						<td class='borderright' colspan='3' style='text-align: center'>".$this->l('Sponsors')."</td>
						<td colspan='5' style='text-align: center'>".$this->l('Sponsored friends')."</td>
					</tr>
					<tr class='title'>
						<td>".$this->l('Sponsors')."</td>
						<td>".$this->l('Rewards for orders')."</td>
						<td class='borderright'>".$this->l('Rewards for registrations')."</td>
						<td>".$this->l('Pending')."</td>
						<td>".$this->l('Registered')."</td>
						<td>".$this->l('With orders')."</td>
						<td>".$this->l('Number of orders')."</td>
						<td>".$this->l('Total orders')."</td>
					</tr>
					<tr>
						<td>".(int)$stats['nb_sponsors']."</td>
						<td>".Tools::displayPrice($stats['total_rewards_orders'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='borderright'>".Tools::displayPrice($stats['total_rewards_registrations'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td>".(int)$stats['nb_pending']."</td>
						<td>".(int)$stats['nb_sponsored']."</td>
						<td>".(int)$stats['nb_buyers']."</td>
						<td>".(int)$stats['nb_orders']."</td>
						<td>".Tools::displayPrice($stats['total_orders'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
					</tr>
				</table>

				<div class='title'>".$this->l('Details by registration channel')."</div>
				<table class='channels_sponsorship'>
					<tr class='title'>
						<td class='title'>".$this->l('Channels')."</td>
						<td>".$this->l('Registered')."</td>
						<td>".$this->l('With orders')."</td>
						<td>".$this->l('Number of orders')."</td>
						<td class='price'>".$this->l('Total orders')."</td>
						<td class='price'>".$this->l('Rewards for orders')."</td>
						<td class='price'>".$this->l('Rewards for registrations')."</td>
					</tr>
					<tr>
						<td class='title'>".$this->l('Email invitation')."</td>
						<td>".(int)$stats['nb_sponsored1']."</td>
						<td>".(int)$stats['nb_buyers1']."</td>
						<td>".(int)$stats['nb_orders1']."</td>
						<td class='price'>".Tools::displayPrice($stats['total_orders1'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='price'>".Tools::displayPrice($stats['total_rewards_orders_channel1'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='price'>".Tools::displayPrice($stats['total_rewards_registrations_channel1'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
					</tr>
					<tr>
						<td class='title'>".$this->l('Sponsorship link')."</td>
						<td>".(int)$stats['nb_sponsored2']."</td>
						<td>".(int)$stats['nb_buyers2']."</td>
						<td>".(int)$stats['nb_orders2']."</td>
						<td class='price'>".Tools::displayPrice($stats['total_orders2'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='price'>".Tools::displayPrice($stats['total_rewards_orders_channel2'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='price'>".Tools::displayPrice($stats['total_rewards_registrations_channel2'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
					</tr>
					<tr>
						<td class='title'>".$this->l('Facebook')."</td>
						<td>".(int)$stats['nb_sponsored3']."</td>
						<td>".(int)$stats['nb_buyers3']."</td>
						<td>".(int)$stats['nb_orders3']."</td>
						<td class='price'>".Tools::displayPrice($stats['total_orders3'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='price'>".Tools::displayPrice($stats['total_rewards_orders_channel3'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='price'>".Tools::displayPrice($stats['total_rewards_registrations_channel3'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
					</tr>
					<tr>
						<td class='title'>".$this->l('Twitter')."</td>
						<td>".(int)$stats['nb_sponsored4']."</td>
						<td>".(int)$stats['nb_buyers4']."</td>
						<td>".(int)$stats['nb_orders4']."</td>
						<td class='price'>".Tools::displayPrice($stats['total_orders4'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='price'>".Tools::displayPrice($stats['total_rewards_orders_channel4'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='price'>".Tools::displayPrice($stats['total_rewards_registrations_channel4'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
					</tr>".((int)$stats['nb_sponsored5'] > 0 ? "
					<tr>
						<td class='title'>".$this->l('Google +1')."</td>
						<td>".(int)$stats['nb_sponsored5']."</td>
						<td>".(int)$stats['nb_buyers5']."</td>
						<td>".(int)$stats['nb_orders5']."</td>
						<td class='price'>".Tools::displayPrice($stats['total_orders5'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='price'>".Tools::displayPrice($stats['total_rewards_orders_channel5'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='price'>".Tools::displayPrice($stats['total_rewards_registrations_channel5'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
					</tr>":"")."
				</table>

				<div class='title'>".$this->l('Details by sponsor')."</div>
				<table class='customers tablesorter tablesorter-ice'>
					<thead>
						<tr>
							<th class='filter-false sorter-false' colspan='5'>".$this->l('Sponsors')."</th>
							<th class='filter-false sorter-false' colspan='5'>".$this->l('Sponsored friends (Level 1)')."</th>
						</tr>
						<tr>
							<th class='filter-false sorter-false'>&nbsp;</th>
							<th>".$this->l('Name')."</th>
							<th>".$this->l('Rewards for orders')."</th>
							<th>".$this->l('Rewards for registrations')."</th>
							<th>".$this->l('Indirect rewards')."</th>
							<th>".$this->l('Pending')."</th>
							<th>".$this->l('Registered')."</th>
							<th>".$this->l('With orders')."</th>
							<th>".$this->l('Orders')."</th>
							<th>".$this->l('Total')."</th>
						</tr>
					</thead>
					<tbody>";
			if (isset($stats['sponsors'])) {
				foreach($stats['sponsors'] as $sponsor) {
					$result .= "
						<tr id='line_".$sponsor['id_sponsor']."'>
							<td>";
					if (isset($stats['sponsored'][$sponsor['id_sponsor']]) && is_array($stats['sponsored'][$sponsor['id_sponsor']])) {
						$result .= '
								<a href="javascript:showDetails(\''.$sponsor['id_sponsor'].'\', \''.$this->instance->getCurrentPage($this->name, true).'\')"><img src="'._PS_ADMIN_IMG_.'details.gif"></a>
						';
					}
					$result .= "
							</td>
							<td class='left'><a href='?tab=AdminCustomers&id_customer=".$sponsor['id_sponsor']."&viewcustomer&token=".$token."'>" . $sponsor['lastname'] . " " . $sponsor['firstname'] . "</a></td>
							<td class='right'>" . Tools::displayPrice($sponsor['direct_rewards_orders'], (int)Configuration::get('PS_CURRENCY_DEFAULT')) . "</td>
							<td class='right'>" . Tools::displayPrice($sponsor['direct_rewards_registrations'], (int)Configuration::get('PS_CURRENCY_DEFAULT')) . "</td>
							<td class='right'>" . Tools::displayPrice(isset($sponsor['indirect_rewards']) ? $sponsor['indirect_rewards'] : 0, (int)Configuration::get('PS_CURRENCY_DEFAULT')) . "</td>
							<td>".(int)$sponsor['nb_pending']."</td>
							<td>".(int)$sponsor['nb_registered']."</td>
							<td>".(int)$sponsor['nb_buyers']."</td>
							<td>".(int)$sponsor['nb_orders']."</td>
							<td class='right'>" . Tools::displayPrice((float)$sponsor['total_orders'], (int)Configuration::get('PS_CURRENCY_DEFAULT')) . "</td>
						</tr>";
				}
			}
			$result .= "
					</tbody>
				</table>
				<div class='pager'>
			    	<img src='"._MODULE_DIR_.$this->instance->name."/js/tablesorter/addons/pager/first.png' class='first'/>
			    	<img src='"._MODULE_DIR_.$this->instance->name."/js/tablesorter/addons/pager/prev.png' class='prev'/>
			    	<span class='pagedisplay'></span> <!-- this can be any element, including an input -->
			    	<img src='"._MODULE_DIR_.$this->instance->name."/js/tablesorter/addons/pager/next.png' class='next'/>
			    	<img src='"._MODULE_DIR_.$this->instance->name."/js/tablesorter/addons/pager/last.png' class='last'/>
			    	<select class='pagesize'>
			      		<option value='10'>10</option>
			      		<option value='20'>20</option>
			      		<option value='50'>50</option>
			      		<option value='100'>100</option>
			      		<option value='500'>500</option>
			    	</select>
				</div>
			</div>
			<script>
				var footer_pager = \"".$this->l('{startRow} to {endRow} of {totalRows} rows')."\";
				initTableSorter();
			</script>";
		} else if (isset($stats['sponsored'][$id_sponsor]) && is_array($stats['sponsored'][$id_sponsor])) {
			$result = "
					<tr class='details tablesorter-childRow'>
						<td colspan='10'>
							<table style='width: 90%; margin: 0 auto; margin-bottom: 30px'>
								<tr class='title'>
									<td>".$this->l('Levels')."</td>
									<td class='left'>".$this->l('Channels')."</td>
									<td class='left'>".$this->l('Name of the friends')."</td>
									<td>".$this->l('Number of orders')."</td>
									<td class='right'>".$this->l('Total orders')."</td>
									<td class='right'>".$this->l('Total rewards')."</td>
								</tr>";
			foreach($stats['sponsored'][$id_sponsor] as $sponsored) {
				$channel = $this->l('Email invitation');
				if ($sponsored['channel']==2)
					$channel = $this->l('Sponsorship link');
				else if ($sponsored['channel']==3)
					$channel = $this->l('Facebook');
				else if ($sponsored['channel']==4)
					$channel = $this->l('Twitter');
				else if ($sponsored['channel']==5)
					$channel = $this->l('Google +1');
				$result .= "
								<tr>
									<td>".$sponsored['level_sponsorship']."</td>
									<td class='left'>".$channel."</td>
									<td class='left'><a href='?tab=AdminCustomers&id_customer=".$sponsored['id_sponsored']."&viewcustomer&token=".$token."'>".$sponsored['lastname']." ".$sponsored['firstname']."</a></td>
									<td>".$sponsored['nb_orders']."</td>
									<td class='right'>".Tools::displayPrice($sponsored['total_orders'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
									<td class='right'>".Tools::displayPrice($sponsored['total_rewards'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
								</tr>";
			}
			$result .= "
							</table>
						</td>
					</tr>";
		}
		return $result;
	}

	// Return the reward calculated from a price in a specific currency, and converted in the 2nd currency
	private function _getNbCreditsByPrice($price, $idCurrencyFrom, $idCurrencyTo = NULL, $extraParams = array())
	{
		if (!isset($idCurrencyTo))
			$idCurrencyTo = $idCurrencyFrom;

		// for a fixed reward, special offers are always taken in account
		if (Configuration::get('PS_CURRENCY_DEFAULT') != $idCurrencyFrom)
		{
			// convert from customer's currency to default currency
			$price = (float)Tools::convertPrice($price, Currency::getCurrency($idCurrencyFrom), false);
		}
		if ($price > 0) {
			if ((int)$extraParams['type'] == 1) {
				$credits = (float)number_format($extraParams['value'], 2, '.', '');
				// convert from customer's currency to default currency
				$credits = round(Tools::convertPrice($credits, Currency::getCurrency($idCurrencyFrom), false), 2);
			} else {
				$credits = (float)number_format($price, 2, '.', '') * (float)$extraParams['value'] / 100;
			}
			return round(Tools::convertPrice($credits, Currency::getCurrency($idCurrencyTo)), 2);
		} else {
			return 0;
		}
	}

	// check if a sponsorship link has been clicked
	private function _checkSponsorshipLink()
	{
		if (Tools::getValue('s') && !(version_compare(_PS_VERSION_, '1.7', '>') && $this->context->controller instanceof SearchController)) {
			$sponsor = null;
			$id_template = 0;
			$sponsorship = new RewardsSponsorshipModel(RewardsSponsorshipModel::decodeSponsorshipMailLink(Tools::getValue('s')));
			if (Validate::isLoadedObject($sponsorship))
				$sponsor = new Customer($sponsorship->id_sponsor);
			else
				$sponsor = new Customer(RewardsSponsorshipModel::decodeSponsorshipLink(Tools::getValue('s')));
			if (Validate::isLoadedObject($sponsor) && RewardsSponsorshipModel::isCustomerAllowed($sponsor)) {
				$this->context->cookie->rewards_sponsor_id = $sponsor->id;
				$this->context->cookie->rewards_sponsor_channel = (Tools::getValue('c') && is_numeric(Tools::getValue('c'))) ? Tools::getValue('c') : 2;
				$this->context->cookie->rewards_sponsorship_id = Validate::isLoadedObject($sponsorship) ? $sponsorship->id : '';
				$id_template = (int)MyConf::getIdTemplate('sponsorship', $sponsor->id);
			} else {
				unset($this->context->cookie->rewards_sponsor_id);
				unset($this->context->cookie->rewards_sponsorship_id);
			}

			if (MyConf::get('RSPONSORSHIP_REDIRECT', null, $id_template) != 'home' && $this->context->controller instanceof IndexController) {
				if (MyConf::get('RSPONSORSHIP_REDIRECT', null, $id_template) == 'form')
					Tools::redirect('index.php?controller=authentication&create_account=1&s='.Tools::getValue('s'));
				else {
					$link = $this->context->link->getCMSLink(MyConf::get('RSPONSORSHIP_REDIRECT', null, $id_template));
					$link .= strpos($link, '?') !== false ? '&s='.Tools::getValue('s') : '?s='.Tools::getValue('s');
					Tools::redirect($link);
				}
			}
		}
	}

	public function hookDisplayHeader()
	{
		// check if the sponsor in cookie is still authorized to sponsor
		if (!empty($this->context->cookie->rewards_sponsor_id)) {
			$sponsor = new Customer($this->context->cookie->rewards_sponsor_id);
			if (!RewardsSponsorshipModel::isCustomerAllowed($sponsor)) {
				unset($this->context->cookie->rewards_sponsor_id);
				unset($this->context->cookie->rewards_sponsorship_id);
			}
		}

		// check for the sponsor
		$this->_checkSponsorshipLink();

		if (version_compare(_PS_VERSION_, '1.7', '<') && $this->context->controller instanceof AuthController && !empty($this->context->cookie->rewards_sponsorship_id)) {
			$sponsorship = new RewardsSponsorshipModel($this->context->cookie->rewards_sponsorship_id);
			if (Validate::isLoadedObject($sponsorship)) {
				// hack for display sponsorship information in form
				$_POST['customer_firstname'] = $sponsorship->firstname;
				$_POST['firstname'] = $sponsorship->firstname;
				$_POST['customer_lastname'] = $sponsorship->lastname;
				$_POST['lastname'] = $sponsorship->lastname;
				$_POST['email'] = $sponsorship->email;
				$_POST['email_create'] = $sponsorship->email;
				$_POST['sponsorship_invisible'] = '1';
			}
		}

		// add css and js for the sponsorship form and popup
		if (RewardsSponsorshipModel::isCustomerAllowed($this->context->customer)) {
			if (version_compare(_PS_VERSION_, '1.7', '>=')) {
				Media::addJsDef(array(
					'msg' => Tools::htmlentitiesUTF8($this->l('You must agree to the terms of service before continuing.')),
					'url_allinone_sponsorship' => $this->context->link->getModuleLink('allinone_rewards', 'sponsorship', array(), true)
				));
			}

			$this->context->controller->addjqueryPlugin('fancybox');
			if (version_compare(_PS_VERSION_, '1.5.5.0', '<'))
				$this->context->controller->addJS($this->instance->getPath().'js/sponsorship-before-1550.js');
			else
				$this->context->controller->addJS($this->instance->getPath().'js/sponsorship.js');

			if ((int)MyConf::get('RSPONSORSHIP_PRODUCT_SHARE', null, (int)MyConf::getIdTemplate('sponsorship', (int)$this->context->customer->id)) && $this->context->controller instanceof ProductController) {
				$this->context->controller->addJS($this->instance->getPath().'js/clipboard/clipboard.min.js');
				$this->context->controller->addJS($this->instance->getPath().'js/product-sponsorship.js');
			}
		}

		// add Facebook tags
		$id_template = (int)MyConf::getIdTemplate('sponsorship', (int)$this->context->cookie->rewards_sponsor_id);
		$ogimage = MyConf::get('RSPONSORSHIP_SHARE_IMAGE_URL', null, $id_template);
		$ogtitle = MyConf::get('RSPONSORSHIP_SHARE_TITLE', (int)$this->context->language->id, $id_template);
		$ogdescription = MyConf::get('RSPONSORSHIP_SHARE_DESCRIPTION', (int)$this->context->language->id, $id_template);
		if (Tools::getValue('s') && (($this->context->controller instanceof IndexController && MyConf::get('RSPONSORSHIP_REDIRECT', null, $id_template) == 'home') || ($this->context->controller instanceof AuthController && MyConf::get('RSPONSORSHIP_REDIRECT', null, $id_template) == 'form') || ($this->context->controller instanceof CmsController && (int)MyConf::get('RSPONSORSHIP_REDIRECT', null, $id_template) == (int)Tools::getValue('id_cms')))) {
			$this->context->smarty->assign(array('ogurl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.ltrim($_SERVER['REQUEST_URI'], '/')));
			if ($ogimage)
				$this->context->smarty->assign(array('ogimage' => $ogimage));
			if ($ogtitle)
				$this->context->smarty->assign(array('ogtitle' => $ogtitle));
			if ($ogdescription)
				$this->context->smarty->assign(array('ogdescription' => $ogdescription));

			return $this->instance->display($this->instance->path, 'header-sponsorship.tpl');
		}
		return false;
	}

	// Open sponsorship popup
	public function hookDisplayFooter($params)
	{
		// if popup is activated and cookie time is over
		$id_template = (int)MyConf::getIdTemplate('sponsorship', $this->context->customer->id);
		$key = 'rewards_sponsor' . MyConf::get('RSPONSORSHIP_POPUP_KEY', null, $id_template);
		if (MyConf::get('RSPONSORSHIP_POPUP', null, $id_template) && (!$this->context->cookie->$key || ($this->context->cookie->$key + (MyConf::get('RSPONSORSHIP_POPUP_DELAY', null, $id_template)*86400)) < time())
			&& strpos($_SERVER['REQUEST_URI'], "/sponsorship.php") === false)
			return $this->_popup(true);
		return false;
	}

	public function hookDisplayCustomerAccount($params)
	{
		if (RewardsSponsorshipModel::isCustomerAllowed($this->context->customer)) {
			if (version_compare(_PS_VERSION_, '1.7', '>='))
				return $this->instance->display($this->instance->path, 'presta-1.7/customer-account-sponsorship.tpl');
			return $this->instance->display($this->instance->path, 'customer-account-sponsorship.tpl');
		}
	}

	public function hookDisplayMyAccountBlock($params)
	{
		if (RewardsSponsorshipModel::isCustomerAllowed($this->context->customer)) {
			if (version_compare(_PS_VERSION_, '1.7', '>='))
				return $this->instance->display($this->instance->path, 'presta-1.7/my-account-sponsorship.tpl');
			return $this->instance->display($this->instance->path, 'my-account-sponsorship.tpl');
		}
	}

	public function hookDisplayMyAccountBlockFooter($params)
	{
		return $this->hookDisplayMyAccountBlock($params);
	}

	// Add an additional input on bottom for the sponsor's email address
	public function hookDisplayCustomerAccountForm($params)
	{
    	if (empty($this->context->cookie->rewards_sponsor_id)) {
			if (version_compare(_PS_VERSION_, '1.7', '<')) {
	   			$this->context->controller->addJS($this->instance->getPath().'js/sponsorship-registration.js');
				return $this->instance->display($this->instance->path, 'authentication.tpl');
			}
		} else if (version_compare(_PS_VERSION_, '1.7', '>=') && $this->context->controller instanceof OrderController) {
			return $this->instance->display($this->instance->path, 'authentication-top.tpl');
		}
		return false;
	}

	// Inform the customer that he has been sponsored
    public function hookDisplayCustomerAccountFormTop($params)
    {
    	if (!empty($this->context->cookie->rewards_sponsor_id) && (version_compare(_PS_VERSION_, '1.7', '<') || !($this->context->controller instanceof OrderController)))
    		return $this->instance->display($this->instance->path, 'authentication-top.tpl');
    	return false;
    }

    // presta >= 1.7
	// Add an additional input on bottom for the sponsor's email address
	public function hookAdditionalCustomerFormFields($params)
    {
    	if (!($this->context->controller instanceof IdentityController) && empty($this->context->cookie->rewards_sponsor_id)) {
   			$this->context->controller->addJS($this->instance->getPath().'js/sponsorship-registration.js');
   			Media::addJsDef(array('ps_version' => _PS_VERSION_, 'url_allinone_sponsorship' => $this->context->link->getModuleLink('allinone_rewards', 'sponsorship', array(), true)));
   			Media::addJsDefL('error_sponsor', $this->l('This sponsor does not exist'));

	        $form = new FormField();
	        return array($form->setName('sponsorship')->setType('text')->setLabel($this->l('Code or E-mail address of your sponsor')));
	    }
	    return array();
    }

	// Create the sponsorship, and a discount for the customer
	public function hookActionCustomerAccountAdd($params)
	{
		$newCustomer = $params['newCustomer'];
		if (!Validate::isLoadedObject($newCustomer))
			return false;

		$sponsor = null;
		if (!empty($this->context->cookie->rewards_sponsor_id)) {
			// sponsor already in the cookie
			$sponsor = new Customer($this->context->cookie->rewards_sponsor_id);
		} else {
			// sponsor email entered on the form
			if (!Tools::getIsset('sponsorship'))
				return false;
			$sponsorship = trim(Tools::getValue('sponsorship'));
			$sponsor = new Customer();
			if (Validate::isEmail($sponsorship))
				$sponsor=$sponsor->getByEmail($sponsorship);
			else
				$sponsor = new Customer(RewardsSponsorshipModel::decodeSponsorshipLink($sponsorship));
		}
		unset($this->context->cookie->rewards_sponsor_id);
		unset($this->context->cookie->rewards_sponsorship_id);
		return $this->_createSponsorship($sponsor, $newCustomer);
	}

	private function _createSponsorship($sponsor, $customer, $force=false, $voucher=true, $currency=0) {
		if (Validate::isLoadedObject($sponsor) && RewardsSponsorshipModel::isCustomerAllowed($sponsor) && $sponsor->email != $customer->email)
		{
			$id_template = (int)MyConf::getIdTemplate('sponsorship', $sponsor->id);
			$stats = $sponsor->getStats();
			$nbOrdersSponsor = (int)($stats['nb_orders']);
			if ($nbOrdersSponsor >= (int)(MyConf::get('RSPONSORSHIP_ORDER_QUANTITY_S', null, $id_template)) || $force) {
				if ($id_sponsorship = RewardsSponsorshipModel::isMailSponsorised($sponsor->id, $customer->email, true)) {
					$sponsorship = new RewardsSponsorshipModel((int)$id_sponsorship);
					// guest account turning into real account
					// This should be improved, because both customer should be considerated as sponsored, so probably create a new sponsorship !
					if (!empty($sponsorship->id_customer))
						return false;
					$sponsorship->id_customer = $customer->id;
					$sponsorship->firstname = $customer->firstname;
					$sponsorship->lastname = $customer->lastname;
				} else {
					// if this customer has been sponsored by another sponsor, it is deleted
					RewardsSponsorshipModel::deleteSponsoredByOther($customer->email);
					$sponsorship = new RewardsSponsorshipModel();
					$sponsorship->id_sponsor = $sponsor->id;
					$sponsorship->email = $customer->email;
					$sponsorship->id_customer = $customer->id;
					$sponsorship->firstname = $customer->firstname;
					$sponsorship->lastname = $customer->lastname;
					if (!empty($this->context->cookie->rewards_sponsor_channel))
						$sponsorship->channel = $this->context->cookie->rewards_sponsor_channel;
					else
						$sponsorship->channel = 2;
				}
				if ((int)MyConf::get('RSPONSORSHIP_DURATION', null, $id_template))
					$sponsorship->date_end = date('Y-m-d H:i:s', time() + (int)MyConf::get('RSPONSORSHIP_DURATION', null, $id_template)*24*60*60);

				if ($sponsorship->save()) {
					// check if there's some reward to give for registration
					$sponsor_reward = 0;
					if (MyConf::get('RSPONSORSHIP_REWARD_REGISTRATION', null, $id_template) && $friends = RewardsSponsorshipModel::getSponsorFriends((int)$sponsor->id, 'subscribed')) {
						$nbFriends = count($friends);
						$multiples = explode(',', MyConf::get('RSPONSORSHIP_REGISTR_MULTIPLE', null, $id_template));
						$repeats = explode(',', MyConf::get('RSPONSORSHIP_REGISTR_REPEAT', null, $id_template));
						$values = explode(',', MyConf::get('RSPONSORSHIP_REGISTR_VALUE', null, $id_template));
						foreach($multiples as $key => $value) {
							if (($nbFriends % $multiples[$key]) == 0 && ($nbFriends <= $repeats[$key] || $repeats[$key] == 0))
								$sponsor_reward += (float)$values[$key];
						}
						if ($sponsor_reward > 0) {
							$reward = new RewardsModel();
							$reward->plugin = $this->name;
							$reward->id_customer = (int)$sponsor->id;
							$reward->id_reward_state = RewardsStateModel::getValidationId();
							$reward->credits = (float)$sponsor_reward;
							if (Configuration::get('REWARDS_DURATION'))
								$reward->date_end = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d') + Configuration::get('REWARDS_DURATION'), date('Y')));
							if ($reward->save())
								RewardsSponsorshipModel::saveDetails($reward->id, (int)$sponsorship->id, 1);
						}
					}

					// send notifications
					if (Configuration::get('RSPONSORSHIP_MAIL_REGISTRATION') || Configuration::get('RSPONSORSHIP_MAIL_REGISTRATION_S')) {
						$id_template_core = (int)MyConf::getIdTemplate('core', $sponsor->id);
						$lang = (int)Configuration::get('PS_LANG_DEFAULT');
						if (version_compare(_PS_VERSION_, '1.5.4.0', '>='))
							$lang = (int)$sponsor->id_lang;
						$rewardAmount = $this->instance->getRewardReadyForDisplay((float)$sponsor_reward, (int)Configuration::get('PS_CURRENCY_DEFAULT'), $lang, true, $id_template_core);
						$rewardAmountAdmin = $this->instance->getRewardReadyForDisplay((float)$sponsor_reward, (int)Configuration::get('PS_CURRENCY_DEFAULT'), (int)Configuration::get('PS_LANG_DEFAULT'));
						$data = array(
							'{sponsored_firstname}' => $customer->firstname,
							'{sponsored_lastname}' => $customer->lastname,
							'{sponsored_email}' => $customer->email,
							'{sponsor_firstname}' => $sponsor->firstname,
							'{sponsor_lastname}' => $sponsor->lastname,
							'{sponsor_email}' => $sponsor->email);
						if (Configuration::get('RSPONSORSHIP_MAIL_REGISTRATION')) {
							$data['{sponsor_reward}'] = $rewardAmountAdmin;
							$this->instance->sendMail((int)Configuration::get('PS_LANG_DEFAULT'), 'sponsorship-registration-admin', $this->l('Sponsorship', (int)Configuration::get('PS_LANG_DEFAULT')), $data, Configuration::get('PS_SHOP_EMAIL'), NULL);
						}
						if (Configuration::get('RSPONSORSHIP_MAIL_REGISTRATION_S')) {
							$data['{sponsor_reward}'] = $rewardAmount;
							$this->instance->sendMail($lang, $sponsor_reward > 0 ? 'sponsorship-registration-reward' : 'sponsorship-registration', $this->l('Sponsorship', $lang), $data, $sponsor->email, $sponsor->firstname.' '.$sponsor->lastname);
						}
					}

					if (MyConf::get('RSPONSORSHIP_DISCOUNT_GC', null, $id_template) && $voucher) {
						// when called from back-end, currency is provided
						if ($currency == 0)
							$currency = (int)$this->context->currency->id;
						if ($sponsorship->registerDiscount($currency)) {
							$cartRule = new CartRule((int)$sponsorship->id_cart_rule);
							if (Validate::isLoadedObject($cartRule))
							{
								$data = array(
									'{firstname}' => $customer->firstname,
									'{lastname}' => $customer->lastname,
									'{nb_discount}' => $cartRule->quantity_per_user,
									'{voucher_num}' => $cartRule->code);
								if (MyConf::get('RSPONSORSHIP_REAL_VOUCHER_GC', null, $id_template))
									$data['{voucher_details}'] = $this->instance->getDiscountReadyForDisplay(null, null, null, null, MyConf::get('RSPONSORSHIP_REAL_DESC_GC', (int)$this->context->language->id, $id_template));
								else {
									if ((float)$cartRule->reduction_amount > 0 || (float)$cartRule->reduction_percent > 0) {
										if ((float)$cartRule->reduction_percent > 0)
											$data['{voucher_details}'] = $this->instance->getDiscountReadyForDisplay(1, $cartRule->free_shipping, $cartRule->reduction_percent);
										else
											$data['{voucher_details}'] = $this->instance->getDiscountReadyForDisplay(2, $cartRule->free_shipping, $cartRule->reduction_amount, $currency);
									} else
										$data['{voucher_details}'] = $this->l('Free shipping');
								}
								$this->instance->sendMail((int)$this->context->language->id, 'sponsorship-voucher', $this->l('Congratulations!'), $data, $customer->email, $customer->firstname.' '.$customer->lastname);
							}
						}
					}
					return true;
				}
			}
		}
		return false;
	}

	// Create all sponsorship rewards for an order
	private function _createAllRewards($order, $customer)
	{
		// All sponsors who should get a reward
		$sponsorships = RewardsSponsorshipModel::getSponsorshipAscendants($customer->id);

		if (count($sponsorships) > 0) {
			// totals with and without discounted products
			$totals = RewardsModel::getOrderTotalsForReward($order);

			// loop on sponsor, starting from the nearest
			$sponsorsMailHtml = $sponsorsMailTxt = '';
			$bMail = false;
			$level = -1;
			foreach($sponsorships as $sponsorship) {
				// if a sponsorship is over, stop all rewards for the ascendants
				if ($sponsorship['date_end']!='0000-00-00 00:00:00' && $sponsorship['date_end'] <= date('Y-m-d H:i:s'))
					break;

				$level++;
				$id_template = (int)MyConf::getIdTemplate('sponsorship', $sponsorship['id_sponsor']);
				$this->_initConf($id_template);
				// maximum level for this template
				$limit = count($this->_configuration['reward_type']) - 1;
				// try to get settings for the level, if not found last will be used
				$indice = $level;
				if ($level > $limit && !$this->_configuration['unlimited'])
					continue;
				else if (!isset($this->_configuration['reward_type'][$level]))
					$indice = $limit;

				$reward = new RewardsModel();
				$reward->plugin = $this->name;
				$reward->id_customer = (int)$sponsorship['id_sponsor'];
				$reward->id_order = (int)$order->id;
				$reward->id_reward_state = RewardsStateModel::getDefaultId();

				// if reward is product per product
				if ((int)$this->_configuration['reward_type'][$indice] == 3) {
					$reward->credits = RewardsModel::getOrderRewardByProduct($order, MyConf::get('RSPONSORSHIP_DISCOUNTED_ALLOWED', null, $id_template), MyConf::get('RSPONSORSHIP_TAX', null, $id_template), 'sponsorship', $id_template, $indice+1);
				} else {
					$price = MyConf::get('RSPONSORSHIP_DISCOUNTED_ALLOWED', null, $id_template) ? $totals[MyConf::get('RSPONSORSHIP_TAX', null, $id_template) ? 'tax_incl' : 'tax_excl']['with_discounted'] : $totals[MyConf::get('RSPONSORSHIP_TAX', null, $id_template) ? 'tax_incl' : 'tax_excl']['without_discounted'];
					if ($price > 0) {
						$extraParams = array();
						$extraParams['type'] = (int)$this->_configuration['reward_type'][$indice];
						$extraParams['value'] = (float)($extraParams['type'] == 1 ? $this->_configuration['reward_value'][$indice][$order->id_currency] : $this->_configuration['reward_percentage'][$indice]);
						$reward->credits = (float)$this->_getNbCreditsByPrice($price, $order->id_currency, Configuration::get('PS_CURRENCY_DEFAULT'), $extraParams);
					}
				}
				// if sponsor's reward=0 (only special offers, voucher used, or % set to 0 in BO)
				if ($reward->credits == 0)
					continue;

				if ($reward->save()) {
					RewardsSponsorshipModel::saveDetails($reward->id, (int)$sponsorship['id_sponsorship'], $level+1);
					$bMail = true;

					// send customer's notifications
					if (Configuration::get('RSPONSORSHIP_MAIL_ORDER_S') || Configuration::get('RSPONSORSHIP_MAIL_ORDER')) {
						$sponsor = new Customer((int) $sponsorship['id_sponsor']);
						$id_template_core = (int)MyConf::getIdTemplate('core', $sponsor->id);
						$lang = (int)Configuration::get('PS_LANG_DEFAULT');
						if (version_compare(_PS_VERSION_, '1.5.4.0', '>='))
							$lang = (int)$sponsor->id_lang;
						$rewardAmount = $this->instance->getRewardReadyForDisplay((float)$reward->credits, (int)$order->id_currency, $lang, true, $id_template_core);
						$rewardAmountAdmin = $this->instance->getRewardReadyForDisplay((float)$reward->credits, (int)$order->id_currency, (int)Configuration::get('PS_LANG_DEFAULT'));
						$data = array(
							'{sponsored_firstname}' => $customer->firstname,
							'{sponsored_lastname}' => $customer->lastname,
							'{sponsored_email}' => $customer->email,
							'{sponsor_firstname}' => $sponsor->firstname,
							'{sponsor_lastname}' => $sponsor->lastname,
							'{sponsor_email}' => $sponsor->email,
							'{sponsor_reward}' => $rewardAmount,
							'{link_rewards}' => $this->context->link->getModuleLink('allinone_rewards', 'rewards', array(), true));
						$template = 'sponsorship-order';
						if ($level > 0)
							$template = 'sponsorship-order-levels';
						if (Configuration::get('RSPONSORSHIP_MAIL_ORDER_S'))
							$result = $this->instance->sendMail($lang, $template, $this->l('Sponsorship', $lang), $data, $sponsor->email, $sponsor->firstname.' '.$sponsor->lastname);

						// text for the admin notification
						$sponsorsMailHtml .= $this->l('Level', (int)Configuration::get('PS_LANG_DEFAULT')).' '.($level+1).' : '. $sponsor->firstname.' '.$sponsor->lastname.' ('.$sponsor->email.') '.$this->l('will receive', (int)Configuration::get('PS_LANG_DEFAULT')).' '.$rewardAmountAdmin.'<br>';
						$sponsorsMailTxt .= $this->l('Level', (int)Configuration::get('PS_LANG_DEFAULT')).' '.($level+1).' : '. $sponsor->firstname.' '.$sponsor->lastname.' ('.$sponsor->email.') '.$this->l('will receive', (int)Configuration::get('PS_LANG_DEFAULT')).' '.$rewardAmountAdmin.'\r\n';
					}
				}
			}
			// admin notification
			if ($bMail && Configuration::get('RSPONSORSHIP_MAIL_ORDER')) {
				$data = array(
					'{sponsored_firstname}' => $customer->firstname,
					'{sponsored_lastname}' => $customer->lastname,
					'{sponsored_email}' => $customer->email,
					'{sponsors_html}' => $sponsorsMailHtml,
					'{sponsors_txt}' => $sponsorsMailTxt);
				$this->instance->sendMail((int)Configuration::get('PS_LANG_DEFAULT'), 'sponsorship-order-admin', $this->l('Sponsorship', (int)Configuration::get('PS_LANG_DEFAULT')), $data, Configuration::get('PS_SHOP_EMAIL'), NULL);
			}
		}
	}

	// give reward to sponsor in "Waiting for validation" state
	// send notification to sponsor to inform them a sponsored placed an order
	public function hookActionValidateOrder($params)
	{
		if (!Validate::isLoadedObject($customer = $params['customer']) || !Validate::isLoadedObject($order = $params['order']))
			die(Tools::displayError('Missing parameters for hookActionValidateOrder'));

		// check if the customer has been sponsored
		$sponsorship = new RewardsSponsorshipModel(RewardsSponsorshipModel::isSponsorised((int)$customer->id, true, true));
		if (!Validate::isLoadedObject($sponsorship))
			return false;

		// TODO : toute cette partie devrait être faite dans la boucle des parrains dans createAllRewards, car un parrain peut avoir été désactivé, ou bien avoir des conditions différentes
		// par exemple être récompensé sur toutes les commandes dans un template en étant niveau 2, alors que le niveau 1 est dans un autre template qui n'est récompensé qu'à la 1ère.
		// ou bien avoir des minimums différents dans les conditions d'attribution, ou de taxe, ou de frais de livraison, ou de discount...
		$id_template = (int)MyConf::getIdTemplate('sponsorship', $sponsorship->id_sponsor);

		// TODO : if order is splitted but if it's the first order, should be allowed for both orders even if rewards is only for the first order
		// if sponsorship reward is active (because of order creation from admin generating reward even when it was turned off), and sponsor is allowed to get a reward
		if (RewardsSponsorshipModel::isCustomerAllowed(new Customer($sponsorship->id_sponsor)) && MyConf::get('RSPONSORSHIP_REWARD_ORDER', null, $id_template)) {
			// if there's reward only on the first order and the sponsor has already beeen rewarded for this customer, do nothing
			if ((int)MyConf::get('RSPONSORSHIP_ON_EVERY_ORDER', null, $id_template) == 0 && RewardsSponsorshipModel::isAlreadyRewarded($sponsorship->id))
				return false;

			// Shipping included in minimum to unlock sponsor's reward ?
			$total_unlock = MyConf::get('RSPONSORSHIP_TAX', null, $id_template) ? (float)$order->total_paid_tax_incl : (float)$order->total_paid_tax_excl;
			$total_shipping = MyConf::get('RSPONSORSHIP_TAX', null, $id_template) ? (float)$order->total_shipping_tax_incl : (float)$order->total_shipping_tax_excl;

			if ((int)MyConf::get('RSPONSORSHIP_UNLOCK_SHIPPING', null, $id_template) == 0)
				$total_unlock = $total_unlock - $total_shipping;

			// Check if minimum is reached
			if ($total_unlock >= (float)MyConf::get('RSPONSORSHIP_UNLOCK_GC_' . $order->id_currency, null, $id_template)) {
				$this->_createAllRewards($order, $customer);
				return true;
			}
		}
		return false;
	}

	// modify all rewards for a given order
	private function _updateStatusAllRewards($order, $customer, $orderState)
	{
		// all sponsors who will get a reward
		$sponsorships = RewardsSponsorshipModel::getSponsorshipAscendants($customer->id);

		// loop on sponsor, starting from the nearest
		$level = 0;
		foreach($sponsorships as $sponsorship) {
			// if no reward has been granted for this sponsorship
			if (!($rewards=RewardsSponsorshipModel::getByOrderId($order->id, $sponsorship['id_sponsorship']))) {
				$level++;
				continue;
			}

			foreach($rewards as $reward) {
				$reward = new RewardsModel((int)$reward['id_reward']);
				if (!Validate::isLoadedObject($reward))
					continue;

				if ($reward->credits > 0 && $reward->id_reward_state != RewardsStateModel::getConvertId() && $reward->id_reward_state != RewardsStateModel::getWaitingPaymentId() && $reward->id_reward_state != RewardsStateModel::getPaidId()) {
					$sponsor = new Customer((int) $sponsorship['id_sponsor']);
					$lang = (int)Configuration::get('PS_LANG_DEFAULT');
					if (version_compare(_PS_VERSION_, '1.5.4.0', '>='))
						$lang = (int)$sponsor->id_lang;

					// if not already converted, validate or cancel the reward
					if (in_array($orderState->id, $this->rewardStateValidation->getValues())) {
						// if reward is locked during return period
						if (Configuration::get('REWARDS_WAIT_RETURN_PERIOD') && Configuration::get('PS_ORDER_RETURN') && (int)Configuration::get('PS_ORDER_RETURN_NB_DAYS') > 0) {
							$reward->id_reward_state = RewardsStateModel::getReturnPeriodId();
							$template = 'sponsorship-return-period';
							$subject = $this->l('Reward validation', $lang);
						} else {
							$reward->id_reward_state = RewardsStateModel::getValidationId();
							if (Configuration::get('REWARDS_DURATION'))
								$reward->date_end = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d') + Configuration::get('REWARDS_DURATION'), date('Y')));
							$template = 'sponsorship-validation';
							$subject = $this->l('Reward validation', $lang);
						}
					} else {
						$reward->id_reward_state = RewardsStateModel::getCancelId();
						$template = 'sponsorship-cancellation';
						$subject = $this->l('Reward cancellation', $lang);
					}
					$reward->save();

					// send customers's notifications
					if (Configuration::get('RSPONSORSHIP_MAIL_VALIDATION_S')) {
						$id_template_core = (int)MyConf::getIdTemplate('core', $sponsor->id);

						if ($level > 0)
							$template .= '-levels';

						$data = array(
							'{sponsored_firstname}' => $customer->firstname,
							'{sponsored_lastname}' => $customer->lastname,
							'{sponsored_email}' => $customer->email,
							'{sponsor_firstname}' => $sponsor->firstname,
							'{sponsor_lastname}' => $sponsor->lastname,
							'{sponsor_email}' => $sponsor->email,
							'{sponsor_reward}' => $this->instance->getRewardReadyForDisplay((float)$reward->credits, (int)$order->id_currency, $lang, true, $id_template_core),
							'{link_rewards}' => $this->context->link->getModuleLink('allinone_rewards', 'rewards', array(), true));
						if ($reward->id_reward_state = RewardsStateModel::getReturnPeriodId()) {
							$data['{reward_unlock_date}'] = Tools::displayDate($reward->getUnlockDate(), null, true);
						}
						$this->instance->sendMail($lang, $template, $subject, $data, $sponsor->email, $sponsor->firstname.' '.$sponsor->lastname);
					}
				}
			}
			$level++;
		}
	}

	// Validate or cancel the sponsor's rewards
	// Send mail to notify about validation or cancellation of the reward
	public function hookActionOrderStatusUpdate($params)
	{
		$this->instanceDefaultStates();

		if (!Validate::isLoadedObject($orderState = $params['newOrderStatus']) || !Validate::isLoadedObject($order = new Order((int)$params['id_order'])) || !Validate::isLoadedObject($customer = new Customer((int)$order->id_customer)))
			return false;

		// check if a sponsorship is in progress
		$sponsorship = new RewardsSponsorshipModel(RewardsSponsorshipModel::isSponsorised((int)$customer->id, true));
		if (!Validate::isLoadedObject($sponsorship))
			return false;

		// if sponsor is allowed to get a reward
		if (MyConf::get('RSPONSORSHIP_REWARD_ORDER', null, (int)MyConf::getIdTemplate('sponsorship', $sponsorship->id_sponsor))) {
			// if status change to validation status or cancellation status for the reward
			if ($orderState->id != $order->getCurrentState() && (in_array($orderState->id, $this->rewardStateValidation->getValues()) || in_array($orderState->id, $this->rewardStateCancel->getValues()))) {
				$this->_updateStatusAllRewards($order, $customer, $orderState);
				return true;
			}
		}
		return false;
	}

	// calulate all rewards after an order detail has been modified
	private function _modifyOrderDetailAllRewards($order, $customer)
	{
		// all sponsors who will get a reward
		$sponsorships = RewardsSponsorshipModel::getSponsorshipAscendants($customer->id);

		if (count($sponsorships) > 0) {
			// totals with and without discounted products
			$totals = RewardsModel::getOrderTotalsForReward($order);

			// loop on sponsor, starting from the nearest
			$level = -1;
			foreach($sponsorships as $sponsorship) {
				$level++;

				// if no reward has been granted for this sponsorship or if a reward has already been partially used
				if (!($rewards=RewardsSponsorshipModel::getByOrderId($order->id, $sponsorship['id_sponsorship'])) || count($rewards) > 1)
					continue;

				foreach($rewards as $reward) {
					$reward = new RewardsModel((int)$reward['id_reward']);
					if (!Validate::isLoadedObject($reward))
						continue;

					if ($reward->id_reward_state != RewardsStateModel::getConvertId() && $reward->id_reward_state != RewardsStateModel::getWaitingPaymentId() && $reward->id_reward_state != RewardsStateModel::getPaidId()) {
						$id_template = (int)MyConf::getIdTemplate('sponsorship', $sponsorship['id_sponsor']);
						$this->_initConf($id_template);

						$oldCredits = (float)$reward->credits;

						// maximum level for this template
						$limit = count($this->_configuration['reward_type']) - 1;
						// try to get settings for the level, if not found last will be used
						if ($level > $limit && !$this->_configuration['unlimited'])
							$reward->credits = 0;
						else {
							$indice = $level;
							if (!isset($this->_configuration['reward_type'][$level]))
								$indice = $limit;

							if ((int)$this->_configuration['reward_type'][$indice] == 3) {
								$reward->credits = (float)RewardsModel::getOrderRewardByProduct($order, MyConf::get('RSPONSORSHIP_DISCOUNTED_ALLOWED', null, $id_template), MyConf::get('RSPONSORSHIP_TAX', null, $id_template), 'sponsorship', $id_template, $indice+1);
							} else {
								$price = MyConf::get('RSPONSORSHIP_DISCOUNTED_ALLOWED', null, $id_template) ? $totals[MyConf::get('RSPONSORSHIP_TAX', null, $id_template) ? 'tax_incl' : 'tax_excl']['with_discounted'] : $totals[MyConf::get('RSPONSORSHIP_TAX', null, $id_template) ? 'tax_incl' : 'tax_excl']['without_discounted'];
								$extraParams = array();
								$extraParams['type'] = (int)$this->_configuration['reward_type'][$indice];
								$extraParams['value'] = (float)($extraParams['type'] == 1 ? $this->_configuration['reward_value'][$indice][$order->id_currency] : $this->_configuration['reward_percentage'][$indice]);
								$reward->credits = (float)$this->_getNbCreditsByPrice($price, $order->id_currency, Configuration::get('PS_CURRENCY_DEFAULT'), $extraParams);
							}
						}

						// test if something has changed, because return product doesn't change the price of the cart
						if ($oldCredits != $reward->credits) {
							if ($reward->credits == 0)
								$reward->id_reward_state = RewardsStateModel::getCancelId();
							$reward->save();

							// send notification
							if (Configuration::get('RSPONSORSHIP_MAIL_CANCELPROD_S')) {
								$sponsor = new Customer((int) $sponsorship['id_sponsor']);
								$id_template_core = (int)MyConf::getIdTemplate('core', $sponsor->id);
								$lang = (int)Configuration::get('PS_LANG_DEFAULT');
								if (version_compare(_PS_VERSION_, '1.5.4.0', '>='))
									$lang = (int)$sponsor->id_lang;

								$data = array(
									'{sponsored_firstname}' => $customer->firstname,
									'{sponsored_lastname}' => $customer->lastname,
									'{sponsored_email}' => $customer->email,
									'{sponsor_firstname}' => $sponsor->firstname,
									'{sponsor_lastname}' => $sponsor->lastname,
									'{sponsor_email}' => $sponsor->email,
									'{old_sponsor_reward}' => $this->instance->getRewardReadyForDisplay((float)$oldCredits, (int)$order->id_currency, $lang, true, $id_template_core),
									'{new_sponsor_reward}' => $this->instance->getRewardReadyForDisplay((float)$reward->credits, (int)$order->id_currency, $lang, true, $id_template_core));
								$template = 'sponsorship-cancel-product';
								if ($level > 0)
									$template = 'sponsorship-cancel-product-levels';

								$this->instance->sendMail($lang, $template, $this->l('Reward modification', $lang), $data, $sponsor->email, $sponsor->firstname.' '.$sponsor->lastname);
							}
						}
					}
				}
			}
		}
	}

	// Hook called when the order detail is modified
	public function hookActionObjectOrderDetailAddAfter($params)
	{
		return $this->_modifyOrderDetail($params);
	}

	// Hook called when the order detail is modified
	public function hookActionObjectOrderDetailDeleteAfter($params)
	{
		return $this->_modifyOrderDetail($params);
	}

	// Hook called when the order detail is modified
	public function hookActionObjectOrderDetailUpdateAfter($params)
	{
		return $this->_modifyOrderDetail($params);
	}

	// calculate reward when the order detail is modified
	private function _modifyOrderDetail($params)
	{
		if (!Validate::isLoadedObject($order_detail = $params['object'])
		|| !Validate::isLoadedObject($order = new Order((int)$order_detail->id_order))
		|| !Validate::isLoadedObject($customer = new Customer((int)$order->id_customer))) {
			return false;
		}

		// if the sponsorship exists
		$sponsorship = new RewardsSponsorshipModel(RewardsSponsorshipModel::isSponsorised((int)$customer->id, true));
		if (!Validate::isLoadedObject($sponsorship))
			return false;

		// if sponsor is allowed to get a reward
		if (MyConf::get('RSPONSORSHIP_REWARD_ORDER', null, (int)MyConf::getIdTemplate('sponsorship', $sponsorship->id_sponsor))) {
			$this->_modifyOrderDetailAllRewards($order, $customer);
		}
		return true;
	}

	// display the sponsorship form
	public function hookDisplayOrderConfirmation($params)
	{
		$id_template = (int)MyConf::getIdTemplate('sponsorship', $this->context->customer->id);
		if (MyConf::get('RSPONSORSHIP_AFTER_ORDER', null, $id_template))
			return $this->_popup();
		return false;
	}

	// open the sponsorship popup
	private function _popup($scheduled=false)
	{
		if (!($this->context->controller instanceof Allinone_rewardsSponsorshipModuleFrontController) && !$this->_popup && $this->context->customer->isLogged() && Validate::isLoadedObject($this->context->customer) && RewardsSponsorshipModel::isCustomerAllowed($this->context->customer)) {
			$stats = $this->context->customer->getStats();
			$nbOrdersCustomer = (int)$stats['nb_orders'];
			// if nb orders required to sponsor is reached
			$id_template = (int)MyConf::getIdTemplate('sponsorship', $this->context->customer->id);
			if ($nbOrdersCustomer >= (int)MyConf::get('RSPONSORSHIP_ORDER_QUANTITY_S', null, $id_template)) {
				$this->_popup = true;
				$key = 'rewards_sponsor' . MyConf::get('RSPONSORSHIP_POPUP_KEY', null, $id_template);
				$this->context->cookie->$key = time();
				$this->context->smarty->assign(array('scheduled' => $scheduled));
				if (version_compare(_PS_VERSION_, '1.7', '>='))
					return $this->instance->display($this->instance->path, 'presta-1.7/popup.tpl');
				return $this->instance->display($this->instance->path, 'popup.tpl');
			}
		}
		return false;
	}

	// Display sponsorship information in the order page
	public function hookDisplayAdminOrder($params)
	{
		$smarty_values = array(
			'rewards' => RewardsSponsorshipModel::getAllSponsorshipRewardsByOrderId($params['id_order'])
		);
		$this->context->smarty->assign($smarty_values);
		return $this->instance->display($this->instance->path, 'adminorders-sponsorship.tpl');
	}

	// Display sponsorship information in the customer page
	public function hookDisplayAdminCustomers($params)
	{
		$customer = new Customer((int)$params['id_customer']);
		if (!Validate::isLoadedObject($customer))
			die(Tools::displayError('Incorrect object Customer.'));

		$msg = $this->postProcess($params);

		$stats = RewardsSponsorshipModel::getAdminStatistics((int)$customer->id);
		$customerStats = $stats['sponsors'][(int)$customer->id];
		$friends = $stats['sponsored'][(int)$customer->id];
		$code_sponsorship = RewardsSponsorshipModel::getSponsorshipCode($customer);
		$link_sponsorship = RewardsSponsorshipModel::getSponsorshipLink($customer);
		$rewards_sponsorship_code = new RewardsSponsorshipCodeModel((int)$customer->id);

		$sponsorship_template_id = (int)MyConf::getIdTemplate('sponsorship', $this->context->customer->id);
		$sponsorship_templates = RewardsTemplateModel::getList('sponsorship');

		if ($id_sponsorship = RewardsSponsorshipModel::isSponsorised((int)$customer->id, true)) {
			$sponsorship = new RewardsSponsorshipModel((int)$id_sponsorship);
			$sponsor = new Customer((int)$sponsorship->id_sponsor);
		}

		$smarty_values = array(
			'msg' => $msg,
			'sponsorship_templates' => $sponsorship_templates,
			'sponsorship_template_id' => $sponsorship_template_id,
			'sponsor' => isset($sponsor) ? $sponsor : null,
			'sponsorship_code' => $code_sponsorship,
			'sponsorship_custom_code' => Tools::getValue('sponsorship_custom_code', Validate::isLoadedObject($rewards_sponsorship_code) ? $rewards_sponsorship_code->code : ''),
			'sponsorship_link' => $link_sponsorship,
			'sponsorship_allowed' => RewardsSponsorshipModel::isCustomerAllowed($customer),
			'currencies' => $this->instance->getCurrencies(),
			'default_currency' => Configuration::get('PS_CURRENCY_DEFAULT'),
			'friends' => $friends,
			'stats' => $customerStats,
			'sponsor_url' =>$this->context->link->getAdminLink('AdminSponsor').'&ajax=1&id_customer='.$params['id_customer']
		);
		$this->context->smarty->assign($smarty_values);
		return $this->instance->display($this->instance->path, 'admincustomer-sponsorship.tpl');
	}

	public function hookActionAdminControllerSetMedia($params)
	{
    	// add necessary javascript to customers back office
		if ($this->context->controller->controller_name == 'AdminCustomers') {
			$this->context->controller->addjQueryPlugin('date');

			if (version_compare(_PS_VERSION_, '1.6', '>=')) {
				$this->context->controller->addJqueryUI(array(
					'ui.core',
					'ui.widget',
					'ui.accordion',
					'ui.effect',
					'ui.slider',
					'ui.datepicker'
				));

				if (version_compare(_PS_VERSION_, '1.7.6', '>=')) {
					$this->context->controller->addJS(_PS_JS_DIR_.'jquery/jquery-migrate-1.2.1.min.js');
				}
				$this->context->controller->addJS(_PS_JS_DIR_.'jquery/plugins/autocomplete/jquery.autocomplete.js');
				$this->context->controller->addCSS(_PS_JS_DIR_.'jquery/plugins/autocomplete/jquery.autocomplete.css');
			} else {
				$this->context->controller->addJqueryUI(array(
					'ui.core',
					'ui.widget',
					'ui.accordion',
					'ui.slider',
					'ui.datepicker'
				));
				$this->context->controller->addJS($this->instance->getPath().'js/jquery-ui-1.8.16.custom.min.js');
			}

			$this->context->controller->addJS(_PS_JS_DIR_.'jquery/plugins/timepicker/jquery-ui-timepicker-addon.js');
			$this->context->controller->addCSS(_PS_JS_DIR_.'jquery/plugins/timepicker/jquery-ui-timepicker-addon.css');
			$this->context->controller->addCSS($this->instance->getPath().'css/admin-customer.css', 'all');
			$this->context->controller->addJS($this->instance->getPath().'js/admin-customer.js');
		}
	}

	// set the POST values to prefill the field with the sponsored friend information
	// can't be done in hookDisplayHeader on presta > 1.7, because fields are already loaded
	// It has no effect on standart OPC on 1.7, but it has been implemented in custom onepagecheckoutps module
	public function hookActionFrontControllerSetMedia($params)
	{
		if (($this->context->controller instanceof AuthController || $this->context->controller instanceof OrderController) && !empty($this->context->cookie->rewards_sponsorship_id)) {
			$sponsorship = new RewardsSponsorshipModel($this->context->cookie->rewards_sponsorship_id);
			if (Validate::isLoadedObject($sponsorship)) {
				// hack for display sponsorship information in form
				$_POST['customer_firstname'] = $sponsorship->firstname;
				$_POST['firstname'] = $sponsorship->firstname;
				$_POST['customer_lastname'] = $sponsorship->lastname;
				$_POST['lastname'] = $sponsorship->lastname;
				$_POST['email'] = $sponsorship->email;
				$_POST['email_create'] = $sponsorship->email;
				$_POST['sponsorship_invisible'] = '1';
			}
		}
	}

	// Hook called on product page
	public function hookDisplayLeftColumnProduct($params)
	{
		if (version_compare(_PS_VERSION_, '1.7', '<'))
			return $this->_displayProductShareLink();
	}

	public function hookDisplayProductButtons($params)
	{
		if (version_compare(_PS_VERSION_, '1.7', '>=') && version_compare(_PS_VERSION_, '1.7.1.0', '<'))
			return $this->_displayProductShareLink();
	}

	public function hookDisplayProductAdditionalInfo($params)
	{
		if (version_compare(_PS_VERSION_, '1.7.1.0', '>='))
			return $this->_displayProductShareLink();
	}

	private function _displayProductShareLink() {
		if (!Tools::getValue('content_only') && Tools::getValue('action')!='quickview') {
			$id_template = (int)MyConf::getIdTemplate('sponsorship', $this->context->customer->id);
			if ((int)MyConf::get('RSPONSORSHIP_PRODUCT_SHARE', null, $id_template) && RewardsSponsorshipModel::isCustomerAllowed($this->context->customer)) {
				$link = RewardsSponsorshipModel::getSponsorshipProductLink(Tools::getValue('id_product'));
				$this->context->smarty->assign(array('sponsorship_link' => $link));
				if (version_compare(_PS_VERSION_, '1.7', '>='))
					return $this->instance->display($this->instance->path, 'presta-1.7/product-sponsorship.tpl');
				return $this->instance->display($this->instance->path, 'product-sponsorship.tpl');
			}
		}
	}

	public function hookActionObjectCustomerDeleteAfter($params)
	{
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_sponsorship_detail` WHERE `id_reward` NOT IN (SELECT `id_reward` FROM `'._DB_PREFIX_.'rewards`)');
	}
}