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

class RewardsCorePlugin extends RewardsGenericPlugin
{
	public $name = 'core';
	private static $_is_loading = false;

	public function install()
	{
		// hooks
		if (!$this->registerHook('displayHeader') || !$this->registerHook('displayFooter') || !$this->registerHook('displayCustomerAccount')
			|| !$this->registerHook('displayMyAccountBlock') || !$this->registerHook('displayMyAccountBlockFooter')
			|| !$this->registerHook('displayProductButtons') || !$this->registerHook('displayProductAdditionalInfo') || (version_compare(_PS_VERSION_, '1.6.1.0', '<=') && !$this->registerHook('displayProductListReviews')) || !$this->registerHook('displayProductPriceBlock')
			|| !$this->registerHook('actionCartSave')
			|| !$this->registerHook('displayAdminCustomers') || !$this->registerHook('displayAdminProductsExtra') || !$this->registerHook('ActionAdminControllerSetMedia')
			|| !$this->registerHook('actionObjectCustomerDeleteAfter') || !$this->registerHook('actionObjectProductDeleteAfter'))
			return false;

		// conf
		$idEn = Language::getIdByIso('en');
		$desc = array();
		$rewards_payment_txt = array();
		$reward_virtual_name = array();
		foreach (Language::getLanguages() as $language) {
			$tmp = $this->l('Loyalty reward', (int)$language['id_lang']);
			$desc[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('Loyalty reward', $idEn);
			$tmp = $this->l('rewards_payment_txt', (int)$language['id_lang']);
			$rewards_payment_txt[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('rewards_payment_txt', $idEn);
			$tmp = $this->l('points', (int)$language['id_lang']);
			$reward_virtual_name[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $this->l('points', $idEn);
		}

		$groups_off = array(Configuration::get('PS_UNIDENTIFIED_GROUP'), Configuration::get('PS_GUEST_GROUP'));
		$groups_config = '';
		$groups = Group::getGroups((int)Configuration::get('PS_LANG_DEFAULT'));
		foreach ($groups as $group) {
			if (!in_array($group['id_group'], $groups_off))
				$groups_config .= (int)$group['id_group'].',';
		}
		$groups_config = rtrim($groups_config, ',');

		if (!Configuration::updateValue('REWARDS_VERSION', $this->instance->version)
    	|| !Configuration::updateValue('REWARDS_VIRTUAL', 0)
    	|| !Configuration::updateValue('REWARDS_VIRTUAL_NAME', $reward_virtual_name)
		|| !Configuration::updateValue('REWARDS_GIFT', 1)
		|| !Configuration::updateValue('REWARDS_GIFT_NB_ORDERS', 0)
		|| !Configuration::updateValue('REWARDS_GIFT_SHOW_LINK', 1)
		|| !Configuration::updateValue('REWARDS_GIFT_LIST_BUTTON', 1)
		|| !Configuration::updateValue('REWARDS_GIFT_BUY_BUTTON', 1)
		|| !Configuration::updateValue('REWARDS_GIFT_GROUPS', $groups_config)
		|| !Configuration::updateValue('REWARDS_GIFT_TAX', 1)
		|| !Configuration::updateValue('REWARDS_GIFT_PREFIX', 'GIFT')
		|| !Configuration::updateValue('REWARDS_GIFT_DURATION', 365)
		|| !Configuration::updateValue('REWARDS_GIFT_MINIMAL_TAX', 0)
		|| !Configuration::updateValue('REWARDS_GIFT_MINIMAL_SHIPPING', 0)
		|| !Configuration::updateValue('REWARDS_GIFT_ALL_CATEGORIES', 1)
		|| !Configuration::updateValue('REWARDS_PAYMENT', 0)
		|| !Configuration::updateValue('REWARDS_PAYMENT_NB_ORDERS', 0)
		|| !Configuration::updateValue('REWARDS_PAYMENT_INVOICE',  1)
		|| !Configuration::updateValue('REWARDS_PAYMENT_RATIO',  100)
		|| !Configuration::updateValue('REWARDS_PAYMENT_TXT', $rewards_payment_txt)
		|| !Configuration::updateValue('REWARDS_VOUCHER', 0)
		|| !Configuration::updateValue('REWARDS_VOUCHER_NB_ORDERS', 0)
		|| !Configuration::updateValue('REWARDS_VOUCHER_GROUPS', $groups_config)
		|| !Configuration::updateValue('REWARDS_VOUCHER_TAX', 1)
		|| !Configuration::updateValue('REWARDS_MINIMAL_TAX', 0)
		|| !Configuration::updateValue('REWARDS_MINIMAL_SHIPPING', 0)
		|| !Configuration::updateValue('REWARDS_VOUCHER_DETAILS', $desc)
		|| !Configuration::updateValue('REWARDS_VOUCHER_CUMUL_S', 0)
		|| !Configuration::updateValue('REWARDS_VOUCHER_PREFIX', 'FID')
		|| !Configuration::updateValue('REWARDS_VOUCHER_DURATION', 365)
		|| !Configuration::updateValue('REWARDS_VOUCHER_BEHAVIOR', 0)
		|| !Configuration::updateValue('REWARDS_DISPLAY_CART', 1)
		|| !Configuration::updateValue('REWARDS_WAIT_RETURN_PERIOD', 1)
		|| !Configuration::updateValue('REWARDS_USE_CRON', 0)
		|| !Configuration::updateValue('REWARDS_DURATION', 0)
		|| !Configuration::updateValue('REWARDS_CRON_SECURE_KEY', Tools::strtoupper(Tools::passwdGen(16)))
		|| !Configuration::updateValue('REWARDS_MAILS_IGNORED', '@marketplace.amazon,@alerts-shopping-flux')
		|| !Configuration::updateValue('REWARDS_REMINDER', 0)
		|| !Configuration::updateValue('REWARDS_REMINDER_MINIMUM', 5)
		|| !Configuration::updateValue('REWARDS_REMINDER_FREQUENCY', 30)
		|| !Configuration::updateValue('REWARDS_INITIAL_CONDITIONS', 0)
		|| !Configuration::updateGlobalValue('PS_CART_RULE_FEATURE_ACTIVE', 1))
			return false;

		$this->_createFreeGiftProduct();

		foreach ($this->instance->getCurrencies() as $currency) {
			Configuration::updateValue('REWARDS_GIFT_MIN_ORDER_'.(int)($currency['id_currency']), 0);
			Configuration::updateValue('REWARDS_PAYMENT_MIN_VALUE_'.(int)($currency['id_currency']), 0);
			Configuration::updateValue('REWARDS_VOUCHER_MIN_VALUE_'.(int)($currency['id_currency']), 0);
			Configuration::updateValue('REWARDS_VOUCHER_MIN_ORDER_'.(int)($currency['id_currency']), 0);
			Configuration::updateValue('REWARDS_VIRTUAL_VALUE_'.(int)($currency['id_currency']), 0);
		}

		// database
		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards` (
			`id_reward` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_reward_state` INT UNSIGNED NOT NULL DEFAULT 1,
			`id_customer` INT UNSIGNED NOT NULL,
			`id_order` INT UNSIGNED DEFAULT NULL,
			`id_cart_rule` INT UNSIGNED DEFAULT NULL,
			`id_payment` INT UNSIGNED DEFAULT NULL,
			`credits` DECIMAL(20,2) NOT NULL DEFAULT \'0.00\',
			`plugin` VARCHAR(20) NOT NULL DEFAULT \'loyalty\',
			`reason` VARCHAR(80) DEFAULT NULL,
			`date_end` DATETIME DEFAULT \'0000-00-00 00:00:00\',
			`date_add` DATETIME NOT NULL,
			`date_upd` DATETIME NOT NULL,
			PRIMARY KEY (`id_reward`),
			INDEX index_rewards_reward_state (`id_reward_state`),
			INDEX index_rewards_order (`id_order`),
			INDEX index_rewards_cart_rule (`id_cart_rule`),
			INDEX index_rewards_customer (`id_customer`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_history` (
			`id_reward_history` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_reward` INT UNSIGNED DEFAULT NULL,
			`id_reward_state` INT UNSIGNED NOT NULL DEFAULT 1,
			`credits` DECIMAL(20,2) NOT NULL DEFAULT \'0.00\',
			`date_add` DATETIME NOT NULL,
			PRIMARY KEY (`id_reward_history`),
			INDEX `index_rewards_history_reward` (`id_reward`),
			INDEX `index_rewards_history_reward_state` (`id_reward_state`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_state` (
			`id_reward_state` INT UNSIGNED NOT NULL,
			`id_order_state` TEXT,
			PRIMARY KEY (`id_reward_state`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_state_lang` (
			`id_reward_state` INT UNSIGNED NOT NULL,
			`id_lang` INT UNSIGNED NOT NULL,
			`name` varchar(64) NOT NULL,
			UNIQUE KEY `index_unique_rewards_state_lang` (`id_reward_state`,`id_lang`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_payment` (
			`id_payment` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`credits` DECIMAL(20,2) NOT NULL DEFAULT \'0.00\',
			`detail` TEXT,
			`invoice` VARCHAR(100) DEFAULT NULL,
			`paid` TINYINT(1) NOT NULL DEFAULT \'0\',
			`date_add` DATETIME NOT NULL,
			`date_upd` DATETIME NOT NULL,
			PRIMARY KEY (`id_payment`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_account` (
			`id_customer` INT UNSIGNED NOT NULL,
			`date_last_remind` DATETIME DEFAULT NULL,
			`remind_active` TINYINT(1) NOT NULL DEFAULT \'1\',
			`date_add` DATETIME NOT NULL,
			`date_upd` DATETIME NOT NULL,
			PRIMARY KEY (`id_customer`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_template` (
			`id_template` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(100) NOT NULL,
			`plugin` VARCHAR(20) NOT NULL,
			PRIMARY KEY (`id_template`),
			UNIQUE KEY `index_unique_rewards_template` (`name`, `plugin`),
  			INDEX `index_rewards_template_plugin` (`plugin`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_template_config` (
			`id_template_config` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_template` INT UNSIGNED NOT NULL,
			`name` varchar(254) NOT NULL,
			`value` TEXT,
			PRIMARY KEY (`id_template_config`),
			UNIQUE KEY `index_unique_rewards_template_config` (`id_template`, `name`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_template_config_lang` (
			`id_template_config` INT UNSIGNED NOT NULL,
			`id_lang` INT UNSIGNED NOT NULL,
			`value` TEXT,
			PRIMARY KEY (`id_template_config`, `id_lang`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_template_customer` (
			`id_template` INT UNSIGNED NOT NULL,
			`id_customer` INT UNSIGNED NOT NULL,
			PRIMARY KEY (`id_template`, `id_customer`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_product` (
			`id_reward_product` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_product` INT UNSIGNED NOT NULL,
			`type` INT UNSIGNED NOT NULL DEFAULT 0,
			`value` DECIMAL(20, 2) UNSIGNED NOT NULL DEFAULT \'0\',
			`date_from` DATETIME,
			`date_to` DATETIME,
			`plugin` varchar(20) NOT NULL DEFAULT \'loyalty\',
			`level` INT UNSIGNED NOT NULL DEFAULT \'1\',
			PRIMARY KEY (`id_reward_product`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_gift_product` (
  			`id_product` INT UNSIGNED NOT NULL,
  			`gift_allowed` INT UNSIGNED NOT NULL DEFAULT \'0\',
  			PRIMARY KEY (`id_product`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;');

		Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_gift_product_attribute` (
  			`id_product` INT UNSIGNED NOT NULL,
  			`id_product_attribute` INT UNSIGNED NOT NULL,
  			`purchase_allowed` INT UNSIGNED NOT NULL DEFAULT \'0\',
  			PRIMARY KEY (`id_product`, `id_product_attribute`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;');

		if (!RewardsStateModel::insertDefaultData())
			return false;

		return true;
	}

	public function uninstall()
	{
		/*Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards`;');
		Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_state`;');
		Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_state_lang`;');
		Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_history`;');
		Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_payment`;');
		Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_account`;');
		Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_template`;');
		Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_template_config`;');
		Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_template_config_lang`;');
		Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_template_customer`;');
		Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_product`;');
		Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_gift_product`;');
		Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'rewards_gift_product_attribute`;');
		*/
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'hook` WHERE `name` like \'rewards%\'');

		Db::getInstance()->Execute('
			DELETE FROM `'._DB_PREFIX_.'configuration_lang`
			WHERE `id_configuration` IN (SELECT `id_configuration` from `'._DB_PREFIX_.'configuration` WHERE `name` like \'REWARDS_%\')');

		Db::getInstance()->Execute('
			DELETE FROM `'._DB_PREFIX_.'configuration`
			WHERE `name` like \'REWARDS_%\'');

		return true;
	}

	public function isActive()
	{
		return true;
	}

	public function isRewardsAccountVisible()
	{
		foreach($this->instance->plugins as $plugin) {
			if (!($plugin instanceof RewardsCorePlugin) && $plugin->isRewardsAccountVisible())
				return true;
		}
	}

	public function getTitle()
	{
		return $this->l('Rewards account');
	}

	public function getDetails($reward, $admin)
	{
		return false;
	}

	private function _createFreeGiftProduct() {
		if (!Validate::isLoadedObject(new Product((int)Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT')))) {
        	$product = new Product();
        	$product->out_of_stock = 2;
        	$product->active = 1;
        	$product->visibility = 'none';
        	$product->is_virtual = 1;
        	$product->id_category_default = $this->context->shop->id_category;
        	foreach (Language::getLanguages(true) as $lang) {
            	$product->link_rewrite[$lang['id_lang']] = 'rewards-free-product';
            	$product->name[$lang['id_lang']] = $this->l('Free product');
        	}
        	$product->add();
        	Configuration::updateGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT', $product->id);
        	StockAvailable::setQuantity($product->id, 0, 99999);
        	$product->updateCategories(array($this->context->shop->id_category));
		}
	}

	protected function postProcess($params=null)
	{
		$this->instanceDefaultStates();

		// on initialise le template à chaque chargement
		$this->initTemplate();

		if (Tools::isSubmit('submitReward')) {
			$this->_postValidation();
			if (!sizeof($this->_errors)) {
				if (empty($this->id_template)) {
					Configuration::updateValue('REWARDS_USE_CRON', (int)Tools::getValue('rewards_use_cron'));
					Configuration::updateValue('REWARDS_GIFT_GROUPS', implode(',', (array)Tools::getValue('rewards_gift_groups')));
					Configuration::updateValue('REWARDS_VOUCHER_GROUPS', implode(',', (array)Tools::getValue('rewards_voucher_groups')));
					Configuration::updateValue('REWARDS_PAYMENT_GROUPS', implode(',', (array)Tools::getValue('rewards_payment_groups')));
					Configuration::updateValue('REWARDS_WAIT_RETURN_PERIOD', (int)Tools::getValue('wait_order_return'));
					Configuration::updateValue('REWARDS_DURATION', (int)Tools::getValue('rewards_duration'));

					$this->rewardStateValidation->id_order_state = implode(',', Tools::getValue('id_order_state_validation'));
					$this->rewardStateCancel->id_order_state = implode(',', Tools::getValue('id_order_state_cancel'));
					$this->rewardStateValidation->save();
					$this->rewardStateCancel->save();
				}

				MyConf::updateValue('REWARDS_VIRTUAL', (int)Tools::getValue('rewards_virtual'), null, $this->id_template);
				MyConf::updateValue('REWARDS_GIFT', (int)Tools::getValue('rewards_gift'), null, $this->id_template);
				if ((int)Tools::getValue('rewards_gift'))
					$this->_createFreeGiftProduct();

				MyConf::updateValue('REWARDS_GIFT_NB_ORDERS', (int)Tools::getValue('rewards_gift_nb_orders'), null, $this->id_template);
				MyConf::updateValue('REWARDS_GIFT_SHOW_LINK', (int)Tools::getValue('rewards_gift_show_link'), null, $this->id_template);
				MyConf::updateValue('REWARDS_GIFT_LIST_BUTTON', (int)Tools::getValue('rewards_gift_list_button'), null, $this->id_template);
				if (version_compare(_PS_VERSION_, '1.6', '>=') && (int)MyConf::get('REWARDS_GIFT_BUY_BUTTON', null, $this->id_template) != (int)Tools::getValue('rewards_gift_buy_button'))
					Tools::clearSmartyCache();
				MyConf::updateValue('REWARDS_GIFT_BUY_BUTTON', (int)Tools::getValue('rewards_gift_buy_button'), null, $this->id_template);
				MyConf::updateValue('REWARDS_GIFT_TAX', (int)Tools::getValue('rewards_gift_tax'), null, $this->id_template);
				MyConf::updateValue('REWARDS_GIFT_PREFIX', Tools::getValue('rewards_gift_prefix'), null, $this->id_template);
				MyConf::updateValue('REWARDS_GIFT_DURATION', (int)Tools::getValue('rewards_gift_duration'), null, $this->id_template);
				MyConf::updateValue('REWARDS_GIFT_MINIMAL_TAX', Tools::getValue('rewards_gift_min_order_include_tax'), null, $this->id_template);
				MyConf::updateValue('REWARDS_GIFT_MINIMAL_SHIPPING', Tools::getValue('rewards_gift_min_order_include_shipping'), null, $this->id_template);
				MyConf::updateValue('REWARDS_GIFT_ALL_CATEGORIES', (int)Tools::getValue('rewards_gift_all_categories'), null, $this->id_template);
				MyConf::updateValue('REWARDS_GIFT_CATEGORIES', Tools::getValue('categoryBox') ? implode(',', Tools::getValue('categoryBox')) : '', null, $this->id_template);
				MyConf::updateValue('REWARDS_PAYMENT', (int)Tools::getValue('rewards_payment'), null, $this->id_template);
				MyConf::updateValue('REWARDS_PAYMENT_NB_ORDERS', (int)Tools::getValue('rewards_payment_nb_orders'), null, $this->id_template);
				MyConf::updateValue('REWARDS_PAYMENT_INVOICE',  (int)Tools::getValue('rewards_payment_invoice'), null, $this->id_template);
				MyConf::updateValue('REWARDS_PAYMENT_RATIO', (float)Tools::getValue('rewards_payment_ratio'), null, $this->id_template);
				MyConf::updateValue('REWARDS_VOUCHER', (int)Tools::getValue('rewards_voucher'), null, $this->id_template);
				MyConf::updateValue('REWARDS_VOUCHER_NB_ORDERS', (int)Tools::getValue('rewards_voucher_nb_orders'), null, $this->id_template);
				MyConf::updateValue('REWARDS_VOUCHER_PREFIX', Tools::getValue('voucher_prefix'), null, $this->id_template);
				MyConf::updateValue('REWARDS_VOUCHER_DURATION', (int)Tools::getValue('voucher_duration'), null, $this->id_template);
				MyConf::updateValue('REWARDS_VOUCHER_TAX', (int)Tools::getValue('voucher_tax'), null, $this->id_template);
				MyConf::updateValue('REWARDS_DISPLAY_CART', (int)Tools::getValue('display_cart'), null, $this->id_template);
				MyConf::updateValue('REWARDS_VOUCHER_CUMUL_S', (int)Tools::getValue('cumulative_voucher_s'), null, $this->id_template);
				MyConf::updateValue('REWARDS_MINIMAL_TAX', Tools::getValue('include_tax'), null, $this->id_template);
				MyConf::updateValue('REWARDS_MINIMAL_SHIPPING', Tools::getValue('include_shipping'), null, $this->id_template);
				MyConf::updateValue('REWARDS_VOUCHER_BEHAVIOR', (int)Tools::getValue('voucher_behavior'), null, $this->id_template);

				$arrayVirtualName = array();
				$arrayVoucherDetails = array();
				$languages = Language::getLanguages();
				foreach ($languages AS $language) {
					$arrayVoucherDetails[(int)($language['id_lang'])] = Tools::getValue('voucher_details_'.(int)($language['id_lang']));
					$arrayVirtualName[(int)($language['id_lang'])] = Tools::getValue('rewards_virtual_name_'.(int)($language['id_lang']));
				}
				MyConf::updateValue('REWARDS_VOUCHER_DETAILS', $arrayVoucherDetails, null, $this->id_template);
				MyConf::updateValue('REWARDS_VIRTUAL_NAME', $arrayVirtualName, null, $this->id_template);

				$currencies = $this->instance->getCurrencies();
				foreach ($currencies as $currency) {
					MyConf::updateValue('REWARDS_VIRTUAL_VALUE_'.$currency['id_currency'], (float)Tools::getValue('rewards_virtual_value_'.$currency['id_currency']), null, $this->id_template);
					MyConf::updateValue('REWARDS_GIFT_MIN_ORDER_'.$currency['id_currency'], (float)Tools::getValue('rewards_gift_min_order_'.$currency['id_currency']), null, $this->id_template);
					MyConf::updateValue('REWARDS_VOUCHER_MIN_VALUE_'.$currency['id_currency'], (float)Tools::getValue('rewards_voucher_min_value_'.$currency['id_currency']), null, $this->id_template);
					MyConf::updateValue('REWARDS_PAYMENT_MIN_VALUE_'.$currency['id_currency'], (float)Tools::getValue('rewards_payment_min_value_'.$currency['id_currency']), null, $this->id_template);
					MyConf::updateValue('REWARDS_VOUCHER_MIN_ORDER_'.$currency['id_currency'], (float)Tools::getValue('rewards_voucher_min_order_'.$currency['id_currency']), null, $this->id_template);
				}
				$this->instance->confirmation = $this->instance->displayConfirmation($this->l('Settings updated.'));
			} else
				$this->instance->errors = $this->instance->displayError(implode('<br />', $this->_errors));
		} else if (Tools::isSubmit('submitRewardsNotifications')) {
			$this->_postValidation();
			if (!sizeof($this->_errors)) {
				Configuration::updateValue('REWARDS_MAILS_IGNORED', Tools::getValue('rewards_mails_ignored'));
				Configuration::updateValue('REWARDS_REMINDER', (int)Tools::getValue('rewards_reminder'));
				Configuration::updateValue('REWARDS_REMINDER_MINIMUM', (float)Tools::getValue('rewards_reminder_minimum'));
				Configuration::updateValue('REWARDS_REMINDER_FREQUENCY', (int)Tools::getValue('rewards_reminder_frequency'));
				$this->instance->confirmation = $this->instance->displayConfirmation($this->l('Settings updated.'));
			} else
				$this->instance->errors = $this->instance->displayError(implode('<br />', $this->_errors));
		} else if (Tools::isSubmit('submitRewardText')) {
			$this->_postValidation();

			if (!sizeof($this->_errors)) {
				if (empty($this->id_template)) {
					foreach (Language::getLanguages() AS $language) {
						$this->rewardStateDefault->name[(int)($language['id_lang'])] = Tools::getValue('default_reward_state_'.(int)($language['id_lang']));
						$this->rewardStateValidation->name[(int)($language['id_lang'])] = Tools::getValue('validation_reward_state_'.(int)($language['id_lang']));
						$this->rewardStateCancel->name[(int)($language['id_lang'])] = Tools::getValue('cancel_reward_state_'.(int)($language['id_lang']));
						$this->rewardStateConvert->name[(int)($language['id_lang'])] = Tools::getValue('convert_reward_state_'.(int)($language['id_lang']));
						$this->rewardStateReturnPeriod->name[(int)($language['id_lang'])] = Tools::getValue('return_period_reward_state_'.(int)($language['id_lang']));
						$this->rewardStateWaitingPayment->name[(int)($language['id_lang'])] = Tools::getValue('waiting_payment_reward_state_'.(int)($language['id_lang']));
						$this->rewardStatePaid->name[(int)($language['id_lang'])] = Tools::getValue('paid_reward_state_'.(int)($language['id_lang']));
					}
					$this->rewardStateDefault->save();
					$this->rewardStateValidation->save();
					$this->rewardStateCancel->save();
					$this->rewardStateConvert->save();
					$this->rewardStateReturnPeriod->save();
					$this->rewardStateWaitingPayment->save();
					$this->rewardStatePaid->save();
				}

				MyConf::updateValue('REWARDS_GENERAL_TXT', Tools::getValue('rewards_general_txt'), true, $this->id_template);
				MyConf::updateValue('REWARDS_PAYMENT_TXT', Tools::getValue('rewards_payment_txt'), true, $this->id_template);
				$this->instance->confirmation = $this->instance->displayConfirmation($this->l('Settings updated.'));
			} else
				$this->instance->errors = $this->instance->displayError(implode('<br />', $this->_errors));
		} else if (Tools::getValue('accept_payment')) {
			RewardsPaymentModel::acceptPayment((int)Tools::getValue('accept_payment'));
		} else if (Tools::isSubmit('submitRewardReminder')) {
			RewardsAccountModel::sendReminder((int)$params['id_customer']);
		} else if (Tools::isSubmit('submitRewardReminderOff')) {
			Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'rewards_account` SET remind_active=0 WHERE id_customer='.(int)$params['id_customer']);
			$rewards_account = new RewardsAccountModel((int)$params['id_customer']);
			if (!Validate::isLoadedObject($rewards_account)) {
				$rewards_account->id_customer = (int)$params['id_customer'];
				$rewards_account->remind_active = 0;
				$rewards_account->save();
			}
		} else if (Tools::isSubmit('submitRewardReminderOn')) {
			Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'rewards_account` SET remind_active=1 WHERE id_customer='.(int)$params['id_customer']);
			$rewards_account = new RewardsAccountModel((int)$params['id_customer']);
			if (!Validate::isLoadedObject($rewards_account)) {
				$rewards_account->id_customer = (int)$params['id_customer'];
				$rewards_account->remind_active = 1;
				$rewards_account->save();
			}
		} else if (Tools::isSubmit('submitRewardUpdate')) {
			// manage rewards update
			$this->_postValidation();
			if (!sizeof($this->_errors)) {
				$reward = new RewardsModel((int)Tools::getValue('id_reward_to_update'));
				$reward->id_reward_state = (int)Tools::getValue('reward_state_' . Tools::getValue('id_reward_to_update'));
				$reward->credits = (float)Tools::getValue('reward_value_' . Tools::getValue('id_reward_to_update'));
				if ($reward->plugin=="free")
					$reward->reason = Tools::getValue('reward_reason_' . Tools::getValue('id_reward_to_update'));
				$reward->save();
				return $this->instance->displayConfirmation($this->l('The reward has been updated.'));
			} else
				return $this->instance->displayError(implode('<br />', $this->_errors));
		} else if (Tools::isSubmit('submitNewReward')) {
			$this->_postValidation();
			if (!sizeof($this->_errors)) {
				$reward = new RewardsModel();
				$reward->id_reward_state = (int)Tools::getValue('new_reward_state');
				$reward->id_customer = (int)$params['id_customer'];
				$reward->credits = (float)Tools::getValue('new_reward_value');
				$reward->plugin = 'free';
				$reward->reason = Tools::getValue('new_reward_reason');
				$reward->save();
				$_POST['new_reward_value'] = $_POST['new_reward_reason'] = $_POST['new_reward_state'] = null;
				return $this->instance->displayConfirmation($this->l('The new reward has been created.'));
			} else
				return $this->instance->displayError(implode('<br />', $this->_errors));
		} else if (Tools::getValue('action') == 'core_template' || Tools::getValue('action') == 'loyalty_template') {
			$id_new_template = Tools::getValue(Tools::getValue('action'));
			$id_old_template = (int)MyConf::getIdTemplate(Tools::getValue('action') == 'core_template' ? 'core' : 'loyalty', (int)$params['id_customer']);
			RewardsTemplateModel::deleteCustomer($id_old_template, (int)$params['id_customer']);
			if ($id_new_template)
				RewardsTemplateModel::addCustomer((int)$id_new_template, (int)$params['id_customer']);
			return $this->instance->displayConfirmation($this->l('The template has been updated.'));
		}
	}

	private function _postValidation()
	{
		$this->_errors = array();

		$languages = Language::getLanguages();
		if (Tools::isSubmit('submitReward')) {
			$currencies = $this->instance->getCurrencies();

			if (empty($this->id_template)) {
				$states_valid = Tools::getValue('id_order_state_validation');
				$states_cancel = Tools::getValue('id_order_state_cancel');
				if (!is_array($states_valid) || !sizeof($states_valid))
					$this->_errors[] = $this->l('You must choose the states when reward is awarded');
				if (!is_array($states_cancel) || !sizeof($states_cancel))
					$this->_errors[] = $this->l('You must choose the states when reward is cancelled');
				if (is_array($states_valid) && is_array($states_cancel) && count(array_intersect($states_valid, $states_cancel)) > 0)
					$this->_errors[] = $this->l('You can\'t choose the same state(s) for validation and cancellation');
				if (!is_numeric(Tools::getValue('rewards_duration')) || Tools::getValue('rewards_duration') < 0)
					$this->_errors[] = $this->l('The validity of the rewards is required/invalid.');
				if (Tools::getValue('rewards_gift') && !is_array(Tools::getValue('rewards_gift_groups')))
					$this->_errors[] = $this->l('Please select at least 1 customer group allowed to pick gift products');
				if (Tools::getValue('rewards_voucher') && !is_array(Tools::getValue('rewards_voucher_groups')))
					$this->_errors[] = $this->l('Please select at least 1 customer group allowed to transform rewards into vouchers');
				if (Tools::getValue('rewards_payment') && !is_array(Tools::getValue('rewards_payment_groups')))
					$this->_errors[] = $this->l('Please select at least 1 customer group allowed to ask for payment');
			}
			if (!Tools::getValue('rewards_gift') && !Tools::getValue('rewards_payment') && !Tools::getValue('rewards_voucher'))
				$this->_errors[] = $this->l('Please select at least 1 way to use the rewards');

			if (Tools::getValue('rewards_virtual')) {
				foreach ($currencies as $currency)
					if (!Tools::getValue('rewards_virtual_value_'.$currency['id_currency']) || !Validate::isUnsignedFloat(Tools::getValue('rewards_virtual_value_'.$currency['id_currency'])))
						$this->_errors[] = $this->l('The value of the virtual points is required/invalid for the currency').' '.$currency['name'];
				foreach ($languages as $language)
					if (Tools::getValue('rewards_virtual_name_'.(int)($language['id_lang'])) == '')
						$this->_errors[] = $this->l('Name of the virtual points is required for').' '.$language['name'];
			}
			if (Tools::getValue('rewards_gift')) {
				if (Tools::getValue('rewards_gift_prefix') == '' || !Validate::isDiscountName(Tools::getValue('rewards_gift_prefix')))
					$this->_errors[] = $this->l('Prefix for the voucher code is required/invalid.');
				if (!is_numeric(Tools::getValue('rewards_gift_duration')) || Tools::getValue('rewards_gift_duration') <= 0)
					$this->_errors[] = $this->l('The validity of the voucher is required/invalid.');
				foreach ($currencies as $currency) {
					if (Tools::getValue('rewards_gift_min_order_'.$currency['id_currency'])!='' && !Validate::isUnsignedFloat(Tools::getValue('rewards_gift_min_order_'.$currency['id_currency'])))
						$this->_errors[] = $this->l('Minimum amount of the order to be able to use the voucher in the currency').' '.$currency['name'].' '.$this->l('is invalid.');
				}
				if (!Tools::getValue('rewards_gift_all_categories') && (!is_array(Tools::getValue('categoryBox')) || !sizeof(Tools::getValue('categoryBox'))))
					$this->_errors[] = $this->l('You must choose at least one category for gift products');
				if (!Tools::getValue('rewards_gift_buy_button') && Tools::getValue('rewards_gift_all_categories')==1)
					$this->_errors[] = $this->l('You can\'t choose all categories and that gift products can\'t be bought normally at the same time, else your customers won\'t be able to buy anything.');
			}
			if (Tools::getValue('rewards_payment')) {
				if (Tools::getValue('rewards_payment') && (!Tools::getValue('rewards_payment_ratio') || !Validate::isUnsignedFloat(Tools::getValue('rewards_payment_ratio')) || (float)Tools::getValue('rewards_payment_ratio') > 100 || (float)Tools::getValue('rewards_payment_ratio') < 1))
					$this->_errors[] = $this->l('The convertion rate must be a number between 1 and 100');
				foreach ($currencies as $currency)
					if (Tools::getValue('rewards_payment_min_value_'.$currency['id_currency'])!='' && !Validate::isUnsignedFloat(Tools::getValue('rewards_payment_min_value_'.$currency['id_currency'])))
						$this->_errors[] = $this->l('Minimum required in account for payment and the currency').' '.$currency['name'].' '.$this->l('is invalid.');
			}
			if (Tools::getValue('rewards_voucher')) {
				foreach ($currencies as $currency) {
					if (Tools::getValue('rewards_voucher_min_value_'.$currency['id_currency'])!='' && !Validate::isUnsignedFloat(Tools::getValue('rewards_voucher_min_value_'.$currency['id_currency'])))
						$this->_errors[] = $this->l('Minimum required in account for transformation and the currency').' '.$currency['name'].' '.$this->l('is invalid.');
					if (Tools::getValue('rewards_voucher_min_order_'.$currency['id_currency'])!='' && !Validate::isUnsignedFloat(Tools::getValue('rewards_voucher_min_order_'.$currency['id_currency'])))
						$this->_errors[] = $this->l('Minimum amount of the order to be able to use the voucher in the currency').' '.$currency['name'].' '.$this->l('is invalid.');
				}
				foreach ($languages as $language)
					if (Tools::getValue('voucher_details_'.(int)($language['id_lang'])) == '')
						$this->_errors[] = $this->l('Voucher description is required for').' '.$language['name'];
				if (Tools::getValue('voucher_prefix') == '' || !Validate::isDiscountName(Tools::getValue('voucher_prefix')))
					$this->_errors[] = $this->l('Prefix for the voucher code is required/invalid.');
				if (!is_numeric(Tools::getValue('voucher_duration')) || Tools::getValue('voucher_duration') <= 0)
					$this->_errors[] = $this->l('The validity of the voucher is required/invalid.');
			}
		} else if (Tools::isSubmit('submitRewardsNotifications') && (int)Tools::getValue('rewards_reminder') == 1) {
			if (Tools::getValue('rewards_reminder_minimum') && !Validate::isUnsignedFloat(Tools::getValue('rewards_reminder_minimum')))
				$this->_errors[] = $this->l('Minimum required in account to receive a mail is required/invalid.');
			if (!is_numeric(Tools::getValue('rewards_reminder_frequency')) || Tools::getValue('rewards_reminder_frequency') <= 0)
				$this->_errors[] = $this->l('The frequency of the emails is required/invalid.');
		} else if (Tools::isSubmit('submitRewardText')) {
			foreach ($languages as $language) {
				if (Tools::getValue('default_reward_state_'.(int)($language['id_lang'])) == '')
					$this->_errors[] = $this->l('Label is required for Initial state in').' '.$language['name'];
				if (Tools::getValue('validation_reward_state_'.(int)($language['id_lang'])) == '')
					$this->_errors[] = $this->l('Label is required for validation state in').' '.$language['name'];
				if (Tools::getValue('cancel_reward_state_'.(int)($language['id_lang'])) == '')
					$this->_errors[] = $this->l('Label is required for cancellation state in').' '.$language['name'];
				if (Tools::getValue('convert_reward_state_'.(int)($language['id_lang'])) == '')
					$this->_errors[] = $this->l('Label is required for converted state in').' '.$language['name'];
				if (Tools::getValue('return_period_reward_state_'.(int)($language['id_lang'])) == '')
					$this->_errors[] = $this->l('Label is required for Return period not exceeded state in').' '.$language['name'];
			}
		} else if (Tools::isSubmit('submitRewardUpdate') && (int)Tools::getValue('id_reward_to_update') != 0) {
			 if (!Validate::isUnsignedFloat(Tools::getValue('reward_value_' . Tools::getValue('id_reward_to_update'))) || (float)Tools::getValue('reward_value_' . Tools::getValue('id_reward_to_update')) == 0)
			 	$this->_errors[] = $this->l('The value of the reward is required/invalid.');
			 if (Tools::getValue('reward_reason_' . Tools::getValue('id_reward_to_update'))==='')
			 	$this->_errors[] = $this->l('The reason of the reward is required/invalid.');
		} else if (Tools::isSubmit('submitNewReward')) {
			 if (!Validate::isUnsignedFloat(Tools::getValue('new_reward_value')) || (float)Tools::getValue('new_reward_value') == 0)
			 	$this->_errors[] = $this->l('The value of the reward is required/invalid.');
			 if (Tools::getValue('new_reward_reason') == '')
			 	$this->_errors[] = $this->l('The reason of the reward is required/invalid.');
		}
	}

	public function displayForm()
	{
		if (Tools::getValue('stats'))
			return $this->_getStatistics();
		else if (Tools::getValue('payments'))
			return $this->_getPayments();

		$this->postProcess();

		if ((int)Tools::getValue('rewards_gift', MyConf::get('REWARDS_GIFT', null, $this->id_template)))
			$this->_createFreeGiftProduct();

		$order_states = OrderState::getOrderStates((int)$this->context->language->id);
		$groups = Group::getGroups($this->context->language->id);
		$groups_off = array(Configuration::get('PS_UNIDENTIFIED_GROUP'), Configuration::get('PS_GUEST_GROUP'));
		$rewards_gift_groups = Tools::getValue('rewards_gift_groups', explode(',', Configuration::get('REWARDS_GIFT_GROUPS')));
		$rewards_voucher_groups = Tools::getValue('rewards_voucher_groups', explode(',', Configuration::get('REWARDS_VOUCHER_GROUPS')));
		$rewards_payment_groups = Tools::getValue('rewards_payment_groups', explode(',', Configuration::get('REWARDS_PAYMENT_GROUPS')));
		$categories = Tools::getValue('categoryBox', explode(',', MyConf::get('REWARDS_GIFT_CATEGORIES', null, $this->id_template)));

		$currencies = $this->instance->getCurrencies();
		$defaultLanguage = (int)Configuration::get('PS_LANG_DEFAULT');
		$languages = Language::getLanguages();

		$html = $this->getTemplateForm($this->id_template, $this->name, $this->l('Rewards account')).'
		<div class="tabs" style="display: none">
			<ul>
				<li><a href="#tabs-'.$this->name.'-1">'.$this->l('Settings').'</a></li>
				<li class="not_templated"><a href="#tabs-'.$this->name.'-2">'.$this->l('Notifications').'</a></li>
				<li><a href="#tabs-'.$this->name.'-3">'.$this->l('Texts').'</a></li>
				<li class="not_templated"><a href="'.$this->instance->getCurrentPage($this->name, true).'&stats=1">'.$this->l('Statistics').'</a></li>
				<li class="not_templated"><a href="'.$this->instance->getCurrentPage($this->name, true).'&payments=1">'.$this->l('Payment requests').'</a></li>
			</ul>
			<div id="tabs-'.$this->name.'-1">
				<form action="'.$this->instance->getCurrentPage($this->name).'" method="post" enctype="multipart/form-data">
					<input type="hidden" name="tabs-'.$this->name.'" value="tabs-'.$this->name.'-1" />
					<fieldset>
						'.$this->l('All rewards will be calculated and stored into the database with the default currency. You can choose to use real money or "points" at any time, and you can change the values of the points without any problem, it will only affect the display of the rewards but not their real values. If you create different templates for the rewards account and set different values for the "points" on each template, the others tabs will only display the points\' value according to the default rewards template but the final value for the customer will be calculated with the value depending on the template he is linked to.').'
						<label class="t" style="width: 100% !important; padding-top: 20px; display: block"><strong>'.$this->l('Rewards type').'</strong></label>
						<div class="clear" style="padding-top: 5px"></div>
						<label class="indent">'.$this->l('What kind of rewards will be used by the module').'</label>
						<div class="margin-form">
							<input type="radio" id="rewards_virtual_on" name="rewards_virtual" value="1" '.(Tools::getValue('rewards_virtual', MyConf::get('REWARDS_VIRTUAL', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_virtual_on">' . $this->l('Virtual points') . '</label>&nbsp;
							<input type="radio" id="rewards_virtual_off" name="rewards_virtual" value="0" '.(Tools::getValue('rewards_virtual', MyConf::get('REWARDS_VIRTUAL', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_virtual_off">' . $this->l('Real money') . '</label>
						</div>
						<div class="clear optional rewards_virtual_optional">
							<div class="clear"></div>
							<div>
								<table>
									<tr>
										<td class="label indent">'.$this->l('Currency used by the customer').'</td>
										<td align="left">'.$this->l('Value of the virtual points').'</td>
									</tr>';
		foreach ($currencies as $currency) {
			$html .= '
									<tr>
										<td><label class="indent">' . htmlentities($currency['name'], ENT_NOQUOTES, 'utf-8') . '</label></td>
										<td align="left"><input '. ((int)$currency['id_currency'] == (int)Configuration::get('PS_CURRENCY_DEFAULT') ? 'class="currency_default"' : '') . ' type="text" size="8" maxlength="8" name="rewards_virtual_value_'.$currency['id_currency'].'" id="rewards_virtual_value_'.$currency['id_currency'].'" value="'.Tools::getValue('rewards_virtual_value_'.$currency['id_currency'], MyConf::get('REWARDS_VIRTUAL_VALUE_'.$currency['id_currency'], null, $this->id_template)).'" /> <label class="t">"'.$this->l('points').'" = 1 '.$currency['sign'].'</label>'.((int)$currency['id_currency'] != (int)Configuration::get('PS_CURRENCY_DEFAULT') ? ' <a href="#" onClick="return convertCurrencyValue(this, \'rewards_virtual_value\', '.$currency['conversion_rate'].')"><img src="'._MODULE_DIR_.'allinone_rewards/img/convert.gif" style="vertical-align: middle !important"></a>' : '').'</td>
									</tr>';
		}
		$html .= '
								</table>
							</div>
							<div class="clear"></div>
							<label>'.$this->l('Name of the virtual points').'</label>
							<div class="margin-form translatable">';
		foreach ($languages as $language)
			$html .= '
								<div class="lang_'.$language['id_lang'].'" id="rewards_virtual_name_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
									<input size="33" type="text" name="rewards_virtual_name_'.$language['id_lang'].'" value="'.htmlentities(Tools::getValue('rewards_virtual_name_'.$language['id_lang'], MyConf::get('REWARDS_VIRTUAL_NAME', (int)$language['id_lang'], $this->id_template)), ENT_QUOTES, 'utf-8').'" />
								</div>';
		$html .= '
							</div>
						</div>
						<div class="clear"></div>
						<div class="not_templated" style="padding-top: 10px;">
							<label class="t" style="width: 100% !important"><strong>'.$this->l('Settings for rewards obtained through a command').'</strong></label>
							<div class="clear" style="padding-top: 5px"></div>
							<label class="indent">'.$this->l('Reward is awarded when the order is').'</label>
							<div class="margin-form">
								<select name="id_order_state_validation[]" multiple="multiple" class="multiselect">';
		foreach ($order_states AS $order_state)	{
			$html .= '				<option '.(is_array($this->rewardStateValidation->getValues()) && in_array($order_state['id_order_state'], $this->rewardStateValidation->getValues()) ? 'selected':'').' value="' . $order_state['id_order_state'] . '" style="background-color:' . $order_state['color'] . '"> '.$order_state['name'].'</option>';
		}
		$html .= '
								</select>
							</div>
							<div class="clear"></div>
							<label class="indent">'.$this->l('Reward is cancelled when the order is').'</label>
							<div class="margin-form">
								<select name="id_order_state_cancel[]" multiple="multiple" class="multiselect">';
		foreach ($order_states AS $order_state)	{
			$html .= '			<option '.(is_array($this->rewardStateCancel->getValues()) && in_array($order_state['id_order_state'], $this->rewardStateCancel->getValues()) ? 'selected':'').' value="' . $order_state['id_order_state'] . '" style="background-color:' . $order_state['color'] . '"> '.$order_state['name'].'</option>';
		}
		$html .= '
								</select>
							</div>
							<div class="clear"></div>
							<label class="indent">'.$this->l('Reward is validated only once the return period is exceeded').'</label>&nbsp;
							<div class="margin-form">
								<label class="t" for="wait_order_return_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
								<input type="radio" id="wait_order_return_on" name="wait_order_return" value="1" '.(Tools::getValue('wait_return_period', Configuration::get('REWARDS_WAIT_RETURN_PERIOD')) ? 'checked="checked"' : '').' /> <label class="t" for="wait_order_return_on">' . $this->l('Yes') . '</label>
								<label class="t" for="wait_order_return_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
								<input type="radio" id="wait_order_return_off" name="wait_order_return" value="0" '.(!Tools::getValue('wait_return_period', Configuration::get('REWARDS_WAIT_RETURN_PERIOD')) ? 'checked="checked"' : '').' /> <label class="t" for="wait_order_return_off">' . $this->l('No') . '</label>
								- '.(Configuration::get('PS_ORDER_RETURN')==1 ? $this->l('Order return period = ') . ' ' . Configuration::get('PS_ORDER_RETURN_NB_DAYS') . ' ' . $this->l('days') : $this->l('Actually, order return is not allowed')).'
							</div>
							<div class="clear"></div>
						</div>
						<label class="t" style="width: 100% !important; padding-top: 10px; display: block"><strong>'.$this->l('Use of the rewards').'</strong></label>
						<div class="clear" style="padding-top: 5px"></div>
						<label class="indent">'.$this->l('Allow customers to pick gift products with their rewards').'</label>
						<div class="margin-form">
							<label class="t" for="rewards_gift_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="rewards_gift_on" name="rewards_gift" value="1" '.(Tools::getValue('rewards_gift', MyConf::get('REWARDS_GIFT', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_gift_on">' . $this->l('Yes') . '</label>
							<label class="t" for="rewards_gift_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="rewards_gift_off" name="rewards_gift" value="0" '.(Tools::getValue('rewards_gift', MyConf::get('REWARDS_GIFT', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_gift_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<label class="indent">'.$this->l('Allow customers to transform rewards into vouchers').'</label>
						<div class="margin-form">
							<label class="t" for="rewards_voucher_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="rewards_voucher_on" name="rewards_voucher" value="1" '.(Tools::getValue('rewards_voucher', MyConf::get('REWARDS_VOUCHER', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_voucher_on">' . $this->l('Yes') . '</label>
							<label class="t" for="rewards_voucher_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="rewards_voucher_off" name="rewards_voucher" value="0" '.(Tools::getValue('rewards_voucher', MyConf::get('REWARDS_VOUCHER', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_voucher_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<label class="indent">'.$this->l('Allow customers to ask for payment (cash)').'</label>
						<div class="margin-form">
							<label class="t" for="rewards_payment_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="rewards_payment_on" name="rewards_payment" value="1" '.(Tools::getValue('rewards_payment', MyConf::get('REWARDS_PAYMENT', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_payment_on">' . $this->l('Yes') . '</label>
							<label class="t" for="rewards_payment_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="rewards_payment_off" name="rewards_payment" value="0" '.(Tools::getValue('rewards_payment', MyConf::get('REWARDS_PAYMENT', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_payment_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear not_templated">
							<label class="indent">'.$this->l('Validity of the rewards before being canceled if not used (in days, 0=unlimited)').'</label>
							<div class="margin-form">
								<input type="text" size="4" maxlength="4" id="rewards_duration" name="rewards_duration" value="'.Tools::getValue('rewards_duration', Configuration::get('REWARDS_DURATION')).'" />
							</div>
							<div class="clear"></div>
							<label class="t" style="width: 100% !important; padding-top: 20px; display: block"><strong>'.$this->l('Settings for automatic actions').'</strong></label>
							<div class="clear" style="padding-top: 5px"></div>
							<label class="indent">'.$this->l('How do you want to execute automatic actions').'<br/><small>'.$this->l('(unlock rewards, send reminders, cancel rewards with expired validity)').'</small></label>
							<div class="margin-form">
								<label class="t" for="rewards_use_cron_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
								<input type="radio" id="rewards_use_cron_on" name="rewards_use_cron" value="1" '.(Tools::getValue('rewards_use_cron', Configuration::get('REWARDS_USE_CRON')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_use_cron_on">' . $this->l('Crontab') . '</label>
								<label class="t" for="rewards_use_cron_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
								<input type="radio" id="rewards_use_cron_off" name="rewards_use_cron" value="0" '.(Tools::getValue('rewards_use_cron', Configuration::get('REWARDS_USE_CRON')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_use_cron_off">' . $this->l('I don\'t know') . '</label> - ' . $this->l('will be called on every page load') . '
							</div>
							<div class="clear optional rewards_use_cron_optional">
								<div class="margin-form" style="width: 95% !important; padding-left: 30px">'.$this->l('Place this URL in crontab or call it manually daily :').' '.Tools::getShopDomain(true, true).__PS_BASE_URI__.'modules/allinone_rewards/cron.php?secure_key='.Configuration::get('REWARDS_CRON_SECURE_KEY').'</div>
							</div>
							<div class="clear"></div>
						</div>
					</fieldset>
					<fieldset id="rewards_gift" class="rewards_gift_optional">
						<legend>'.$this->l('Settings applied when picking gift products with the rewards').'</legend>
						'.sprintf($this->l('A voucher for a free product will be generated with the following settings and automatically applied to the cart when the customer will decide to buy the product with his rewards. In case the cart does not contain any paid product, a default "Free product" with price=0 will be added to the cart, because Prestashop does not allow a cart with vouchers only. If you want, you can customize this product to change its name or add an image. The ID of this product is %s'), Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT')).'
						<div class="clear" style="padding-top: 20px"></div>
						<label>'.$this->l('Number of orders to make before being able to use this feature (0 is allowed)').'</label>
						<div class="margin-form">
							<input type="text" size="3" maxlength="3" name="rewards_gift_nb_orders" id="rewards_gift_nb_orders" value="'.(int)(Tools::getValue('rewards_gift_nb_orders', MyConf::get('REWARDS_GIFT_NB_ORDERS', null, $this->id_template))).'" />
						</div>
						<div class="clear not_templated">
							<label>'.$this->l('Customers groups allowed to pick gift products with their rewards').'</label>
							<div class="margin-form">
								<select name="rewards_gift_groups[]" multiple="multiple" class="multiselect">';
		foreach($groups as $group) {
			if (!in_array($group['id_group'], $groups_off))
				$html .= '				<option '.(is_array($rewards_gift_groups) && in_array($group['id_group'], $rewards_gift_groups) ? 'selected':'').' value="'.$group['id_group'].'"> '.$group['name'].'</option>';
		}
		$html .= '
								</select>
							</div>
							<div class="clear"></div>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Display a link to the gifts list in the rewards account').'</label>
						<div class="margin-form">
							<label class="t" for="rewards_gift_show_link_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="rewards_gift_show_link_on" name="rewards_gift_show_link" value="1" '.(Tools::getValue('rewards_gift_show_link', MyConf::get('REWARDS_GIFT_SHOW_LINK', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_gift_show_link_on">' . $this->l('Yes') . '</label>
							<label class="t" for="rewards_gift_show_link_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="rewards_gift_show_link_off" name="rewards_gift_show_link" value="0" '.(Tools::getValue('rewards_gift_show_link', MyConf::get('REWARDS_GIFT_SHOW_LINK', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_gift_show_link_off">' . $this->l('No') . '</label>
						</div>'.(version_compare(_PS_VERSION_, '1.7', '<') ? '
						<div class="clear"></div>
						<label>'.$this->l('Display a button to pick gift products on the products lists').(version_compare(_PS_VERSION_, '1.6', '<') ? '<br/><small>'.$this->l('It requires some custom modifications, please check installation guide').'</small>' : '').'</label>
						<div class="margin-form">
							<label class="t" for="rewards_gift_list_button_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="rewards_gift_list_button_on" name="rewards_gift_list_button" value="1" '.(Tools::getValue('rewards_gift_list_button', MyConf::get('REWARDS_GIFT_LIST_BUTTON', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_gift_list_button_on">' . $this->l('Yes') . '</label>
							<label class="t" for="rewards_gift_list_button_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="rewards_gift_list_button_off" name="rewards_gift_list_button" value="0" '.(Tools::getValue('rewards_gift_list_button', MyConf::get('REWARDS_GIFT_LIST_BUTTON', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_gift_list_button_off">' . $this->l('No') . '</label>
						</div>':'').'
						<div class="clear"></div>
						<label>'.$this->l('Gift products can also be bought normally').(version_compare(_PS_VERSION_, '1.6', '>=') && version_compare(_PS_VERSION_, '1.7', '<') ? '<br/><small>'.$this->l('It requires some custom modifications, please check installation guide').'</small>' : '').'</label>
						<div class="margin-form">
							<label class="t" for="rewards_gift_buy_button_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="rewards_gift_buy_button_on" name="rewards_gift_buy_button" value="1" '.(Tools::getValue('rewards_gift_buy_button', MyConf::get('REWARDS_GIFT_BUY_BUTTON', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_gift_buy_button_on">' . $this->l('Yes') . '</label>
							<label class="t" for="rewards_gift_buy_button_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="rewards_gift_buy_button_off" name="rewards_gift_buy_button" value="0" '.(Tools::getValue('rewards_gift_buy_button', MyConf::get('REWARDS_GIFT_BUY_BUTTON', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_gift_buy_button_off">' . $this->l('No') . ' - ' . $this->l('Add to cart buttons will be hidden') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Product price to pay with the rewards').'</label>
						<div class="margin-form">
							<input type="radio" id="rewards_gift_tax_off" name="rewards_gift_tax" value="0" '.(Tools::getValue('rewards_gift_tax', MyConf::get('REWARDS_GIFT_TAX', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_gift_tax_off">' . $this->l('VAT Excl.') . '</label>
							<input type="radio" id="rewards_gift_tax_on" name="rewards_gift_tax" value="1" '.(Tools::getValue('rewards_gift_tax', MyConf::get('REWARDS_GIFT_TAX', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_gift_tax_on">' . $this->l('VAT Incl.') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Prefix for the voucher code (at least 3 letters long)').'</label>
						<div class="margin-form">
							<input type="text" size="10" maxlength="10" id="rewards_gift_prefix" name="rewards_gift_prefix" value="'.Tools::getValue('rewards_gift_prefix', MyConf::get('REWARDS_GIFT_PREFIX', null, $this->id_template)).'" />
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Validity of the voucher (in days)').'</label>
						<div class="margin-form">
							<input type="text" size="4" maxlength="4" id="rewards_gift_duration" name="rewards_gift_duration" value="'.Tools::getValue('rewards_gift_duration', MyConf::get('REWARDS_GIFT_DURATION', null, $this->id_template)).'" />
						</div>
						<div class="clear"></div>
						<div>
							<table>
								<tr>
									<td class="label">' . $this->l('Currency used by the member') . '</td>
									<td align="left">' . $this->l('Minimum amount of the order to be able to use the voucher') . '</td>
								</tr>';
		foreach ($currencies as $currency) {
			$html .= '
								<tr>
									<td><label class="indent">' . htmlentities($currency['name'], ENT_NOQUOTES, 'utf-8') . '</label></td>
									<td align="left"><input class="'. ((int)$currency['id_currency'] == (int)Configuration::get('PS_CURRENCY_DEFAULT') ? 'currency_default' : '') . '" type="text" size="8" maxlength="8" name="rewards_gift_min_order_'.$currency['id_currency'].'" id="rewards_gift_min_order_'.$currency['id_currency'].'" value="'.Tools::getValue('rewards_gift_min_order_'.$currency['id_currency'], MyConf::get('REWARDS_GIFT_MIN_ORDER_'.$currency['id_currency'], null, $this->id_template)).'" /> <label class="t">'.$currency['sign'].'</label>'.((int)$currency['id_currency'] != (int)Configuration::get('PS_CURRENCY_DEFAULT') ? ' <a href="#" onClick="return convertCurrencyValue(this, \'rewards_gift_min_order\', '.$currency['conversion_rate'].')"><img src="'._MODULE_DIR_.'allinone_rewards/img/convert.gif" style="vertical-align: middle !important"></a>' : '').'</td>
								</tr>';
		}
		$html .= '
								<tr>
									<td>&nbsp;</td>
									<td>
										<select name="rewards_gift_min_order_include_tax">
											<option '.(!Tools::getValue('rewards_gift_min_order_include_tax', MyConf::get('REWARDS_GIFT_MINIMAL_TAX', null, $this->id_template))?'selected':'').' value="0">'.$this->l('VAT Excl.').'</option>
											<option '.(Tools::getValue('rewards_gift_min_order_include_tax', MyConf::get('REWARDS_GIFT_MINIMAL_TAX', null, $this->id_template))?'selected':'').' value="1">'.$this->l('VAT Incl.').'</option>
										</select>
										<select name="rewards_gift_min_order_include_shipping">
											<option '.(!Tools::getValue('rewards_gift_min_order_include_shipping', MyConf::get('REWARDS_GIFT_MINIMAL_SHIPPING', null, $this->id_template))?'selected':'').' value="0">'.$this->l('Shipping Excluded').'</option>
											<option '.(Tools::getValue('rewards_gift_min_order_include_shipping', MyConf::get('REWARDS_GIFT_MINIMAL_SHIPPING', null, $this->id_template))?'selected':'').' value="1">'.$this->l('Shipping Included').'</option>
										</select>
									</td>
								</tr>
							</table>
						</div>
						<div class="clear" style="margin-top: 10px"></div>
						<label>'.$this->l('Gift products can be picked from the following categories :').'<br/><small>'.$this->l('You can also enable/disable products or combinations individually from the product sheet').'</small></label>
						<div class="margin-form">
							<input class="all_categories" type="radio" id="all_categories_on" name="rewards_gift_all_categories" value="0" '.((int)Tools::getValue('rewards_gift_all_categories', MyConf::get('REWARDS_GIFT_ALL_CATEGORIES', null, $this->id_template))==0 ? 'checked="checked"' : '').' /> <label class="t" for="all_categories_on">' . $this->l('Choose categories') . '</label>&nbsp;
							<input class="all_categories" type="radio" id="all_categories_off" name="rewards_gift_all_categories" value="1" '.((int)Tools::getValue('rewards_gift_all_categories', MyConf::get('REWARDS_GIFT_ALL_CATEGORIES', null, $this->id_template))==1 ? 'checked="checked"' : '').' /> <label class="t" for="all_categories_off">' . $this->l('All categories') . '</label>
							<input class="all_categories" type="radio" id="all_categories_none" name="rewards_gift_all_categories" value="-1" '.((int)Tools::getValue('rewards_gift_all_categories', MyConf::get('REWARDS_GIFT_ALL_CATEGORIES', null, $this->id_template))==-1 ? 'checked="checked"' : '').' /> <label class="t" for="all_categories_none">' . $this->l('None') . '</label>
							<div class="optional categories_optional" style="padding-top: 15px">
								'.$this->getCategoriesTree($categories).'
							</div>
						</div>
					</fieldset>
					<fieldset id="rewards_voucher" class="rewards_voucher_optional">
						<legend>'.$this->l('Settings applied when transforming rewards into vouchers').'</legend>
						<label>'.$this->l('Number of orders to make before being able to use this feature (0 is allowed)').'</label>
						<div class="margin-form">
							<input type="text" size="3" maxlength="3" name="rewards_voucher_nb_orders" id="rewards_voucher_nb_orders" value="'.(int)(Tools::getValue('rewards_voucher_nb_orders', MyConf::get('REWARDS_VOUCHER_NB_ORDERS', null, $this->id_template))).'" />
						</div>
						<div class="clear"></div>
						<div class="not_templated">
							<label>'.$this->l('Customers groups allowed to transform rewards into vouchers').'</label>
							<div class="margin-form">
								<select name="rewards_voucher_groups[]" multiple="multiple" class="multiselect">';
		foreach($groups as $group) {
			if (!in_array($group['id_group'], $groups_off))
				$html .= '				<option '.(is_array($rewards_voucher_groups) && in_array($group['id_group'], $rewards_voucher_groups) ? 'selected':'').' value="'.$group['id_group'].'"> '.$group['name'].'</option>';
		}
		$html .= '
								</select>
							</div>
							<div class="clear"></div>
						</div>
						<div style="padding-bottom: 5px">
							<table>
								<tr>
									<td class="label">' . $this->l('Currency used by the member') . '</td>
									<td align="left">' . $this->l('Minimum required in account to be able to transform rewards into vouchers') . '</td>
								</tr>';
		foreach ($currencies as $currency) {
			$html .= '
								<tr>
									<td><label class="indent">' . htmlentities($currency['name'], ENT_NOQUOTES, 'utf-8') . '</label></td>
									<td align="left"><input class="notvirtual '. ((int)$currency['id_currency'] == (int)Configuration::get('PS_CURRENCY_DEFAULT') ? 'currency_default' : '') . '" type="text" size="8" maxlength="8" name="rewards_voucher_min_value_'.$currency['id_currency'].'" id="rewards_voucher_min_value_'.$currency['id_currency'].'" value="'.Tools::getValue('rewards_voucher_min_value_'.$currency['id_currency'], MyConf::get('REWARDS_VOUCHER_MIN_VALUE_'.$currency['id_currency'], null, $this->id_template)).'" onBlur="showVirtualValue(this, '.$currency['id_currency'].', false)" /> <label class="t">'.$currency['sign'].' <span class="virtualvalue"></span></label>'.((int)$currency['id_currency'] != (int)Configuration::get('PS_CURRENCY_DEFAULT') ? ' <a href="#" onClick="return convertCurrencyValue(this, \'rewards_voucher_min_value\', '.$currency['conversion_rate'].')"><img src="'._MODULE_DIR_.'allinone_rewards/img/convert.gif" style="vertical-align: middle !important"></a>' : '').'</td>
								</tr>';
		}
		$html .= '
							</table>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Amount type for the generated voucher').'</label>
						<div class="margin-form">
							<input type="radio" id="voucher_tax_off" name="voucher_tax" value="0" '.(Tools::getValue('voucher_tax', MyConf::get('REWARDS_VOUCHER_TAX', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="voucher_tax_off">' . $this->l('VAT Excl.') . '</label>
							<input type="radio" id="voucher_tax_on" name="voucher_tax" value="1" '.(Tools::getValue('voucher_tax', MyConf::get('REWARDS_VOUCHER_TAX', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="voucher_tax_on">' . $this->l('VAT Incl.') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Voucher details (will appear in cart next to voucher code)').'</label>
						<div class="margin-form translatable">';
		foreach ($languages as $language)
			$html .= '
							<div class="lang_'.$language['id_lang'].'" id="voucher_details_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
								<input size="33" type="text" name="voucher_details_'.$language['id_lang'].'" value="'.htmlentities(Tools::getValue('voucher_details_'.$language['id_lang'], MyConf::get('REWARDS_VOUCHER_DETAILS', (int)$language['id_lang'], $this->id_template)), ENT_QUOTES, 'utf-8').'" />
							</div>';
		$html .= '
						</div>
						<div class="clear" style="margin-top: 20px"></div>
						<label>'.$this->l('Prefix for the voucher code (at least 3 letters long)').'</label>
						<div class="margin-form">
							<input type="text" size="10" maxlength="10" id="voucher_prefix" name="voucher_prefix" value="'.Tools::getValue('voucher_prefix', MyConf::get('REWARDS_VOUCHER_PREFIX', null, $this->id_template)).'" />
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Validity of the voucher (in days)').'</label>
						<div class="margin-form">
							<input type="text" size="4" maxlength="4" id="voucher_duration" name="voucher_duration" value="'.Tools::getValue('voucher_duration', MyConf::get('REWARDS_VOUCHER_DURATION', null, $this->id_template)).'" />
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Display vouchers in the cart summary').'</label>&nbsp;
						<div class="margin-form">
							<label class="t" for="display_cart_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="display_cart_on" name="display_cart" value="1" '.(Tools::getValue('display_cart', MyConf::get('REWARDS_DISPLAY_CART', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="display_cart_on">' . $this->l('Yes') . '</label>
							<label class="t" for="display_cart_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="display_cart_off" name="display_cart" value="0" '.(Tools::getValue('display_cart', MyConf::get('REWARDS_DISPLAY_CART', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="display_cart_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Cumulative with other vouchers').'</label>
						<div class="margin-form">
							<label class="t" for="cumulative_voucher_s_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="cumulative_voucher_s_on" name="cumulative_voucher_s" value="1" '.(Tools::getValue('cumulative_voucher_s', MyConf::get('REWARDS_VOUCHER_CUMUL_S', null, $this->id_template)) ? 'checked="checked"' : '').' /> <label class="t" for="cumulative_voucher_s_on">' . $this->l('Yes') . '</label>
							<label class="t" for="cumulative_voucher_s_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="cumulative_voucher_s_off" name="cumulative_voucher_s" value="0" '.(!Tools::getValue('cumulative_voucher_s', MyConf::get('REWARDS_VOUCHER_CUMUL_S', null, $this->id_template)) ? 'checked="checked"' : '').' /> <label class="t" for="cumulative_voucher_s_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<div>
							<table>
								<tr>
									<td class="label">' . $this->l('Currency used by the member') . '</td>
									<td align="left">' . $this->l('Minimum amount of the order to be able to use the voucher') . '</td>
								</tr>';
		foreach ($currencies as $currency) {
			$html .= '
								<tr>
									<td><label class="indent">' . htmlentities($currency['name'], ENT_NOQUOTES, 'utf-8') . '</label></td>
									<td align="left"><input class="'. ((int)$currency['id_currency'] == (int)Configuration::get('PS_CURRENCY_DEFAULT') ? 'currency_default' : '') . '" type="text" size="8" maxlength="8" name="rewards_voucher_min_order_'.$currency['id_currency'].'" id="rewards_voucher_min_order_'.$currency['id_currency'].'" value="'.Tools::getValue('rewards_voucher_min_order_'.$currency['id_currency'], MyConf::get('REWARDS_VOUCHER_MIN_ORDER_'.$currency['id_currency'], null, $this->id_template)).'" /> <label class="t">'.$currency['sign'].'</label>'.((int)$currency['id_currency'] != (int)Configuration::get('PS_CURRENCY_DEFAULT') ? ' <a href="#" onClick="return convertCurrencyValue(this, \'rewards_voucher_min_order\', '.$currency['conversion_rate'].')"><img src="'._MODULE_DIR_.'allinone_rewards/img/convert.gif" style="vertical-align: middle !important"></a>' : '').'</td>
								</tr>';
		}
		$html .= '
								<tr>
									<td>&nbsp;</td>
									<td>
										<select name="include_tax">
											<option '.(!Tools::getValue('include_tax', MyConf::get('REWARDS_MINIMAL_TAX', null, $this->id_template))?'selected':'').' value="0">'.$this->l('VAT Excl.').'</option>
											<option '.(Tools::getValue('include_tax', MyConf::get('REWARDS_MINIMAL_TAX', null, $this->id_template))?'selected':'').' value="1">'.$this->l('VAT Incl.').'</option>
										</select>
										<select name="include_shipping">
											<option '.(!Tools::getValue('include_shipping', MyConf::get('REWARDS_MINIMAL_SHIPPING', null, $this->id_template))?'selected':'').' value="0">'.$this->l('Shipping Excluded').'</option>
											<option '.(Tools::getValue('include_shipping', MyConf::get('REWARDS_MINIMAL_SHIPPING', null, $this->id_template))?'selected':'').' value="1">'.$this->l('Shipping Included').'</option>
										</select>
									</td>
								</tr>
							</table>
						</div>
						<div class="clear" style="margin-top: 10px"></div>
						<label>'.$this->l('If the voucher is not depleted when used').'</label>&nbsp;
						<div class="margin-form">
							<select name="voucher_behavior">
								<option '.(!Tools::getValue('voucher_behavior', (int)MyConf::get('REWARDS_VOUCHER_BEHAVIOR', null, $this->id_template)) ?'selected':'').' value="0">'.$this->l('Cancel the remaining amount').'</option>
								<option '.(Tools::getValue('voucher_behavior', (int)MyConf::get('REWARDS_VOUCHER_BEHAVIOR', null, $this->id_template)) ?'selected':'').' value="1">'.$this->l('Create a new voucher with remaining amount').'</option>
							</select>
						</div>
					</fieldset>
					<fieldset id="rewards_payment" class="rewards_payment_optional">
						<legend>'.$this->l('Settings applied for the rewards payment').'</legend>
						<label>'.$this->l('Number of orders to make before being able to use this feature (0 is allowed)').'</label>
						<div class="margin-form">
							<input type="text" size="3" maxlength="3" name="rewards_payment_nb_orders" id="rewards_payment_nb_orders" value="'.(int)(Tools::getValue('rewards_payment_nb_orders', MyConf::get('REWARDS_PAYMENT_NB_ORDERS', null, $this->id_template))).'" />
						</div>
						<div class="clear"></div>
						<div class="not_templated">
							<label>'.$this->l('Customers groups allowed to ask for payment').'</label>
							<div class="margin-form">
								<select name="rewards_payment_groups[]" multiple="multiple" class="multiselect">';
		foreach($groups as $group) {
			if (!in_array($group['id_group'], $groups_off))
				$html .= '				<option '.(is_array($rewards_payment_groups) && in_array($group['id_group'], $rewards_payment_groups) ? 'selected':'').' value="'.$group['id_group'].'"> '.$group['name'].'</option>';
		}
		$html .= '
								</select>
							</div>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('An invoice must be uploaded to ask for payment').'</label>
						<div class="margin-form">
							<label class="t" for="rewards_payment_invoice_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
							<input type="radio" id="rewards_payment_invoice_on" name="rewards_payment_invoice" value="1" '.(Tools::getValue('rewards_payment_invoice', MyConf::get('REWARDS_PAYMENT_INVOICE', null, $this->id_template)) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_payment_invoice_on">' . $this->l('Yes') . '</label>
							<label class="t" for="rewards_payment_invoice_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
							<input type="radio" id="rewards_payment_invoice_off" name="rewards_payment_invoice" value="0" '.(Tools::getValue('rewards_payment_invoice', MyConf::get('REWARDS_PAYMENT_INVOICE', null, $this->id_template)) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_payment_invoice_off">' . $this->l('No') . '</label>
						</div>
						<div class="clear"></div>
						<div>
							<table>
								<tr>
									<td class="label">' . $this->l('Currency used by the member') . '</td>
									<td align="left">' . $this->l('Minimum required in account to be able to ask for payment') . '</td>
								</tr>';
		foreach ($currencies as $currency) {
			$html .= '
								<tr>
									<td><label class="indent">' . htmlentities($currency['name'], ENT_NOQUOTES, 'utf-8') . '</label></td>
									<td align="left"><input class="notvirtual '.((int)$currency['id_currency'] == (int)Configuration::get('PS_CURRENCY_DEFAULT') ? 'currency_default' : '').'" type="text" size="8" maxlength="8" name="rewards_payment_min_value_'.$currency['id_currency'].'" id="rewards_payment_min_value_'.$currency['id_currency'].'" value="'.Tools::getValue('rewards_payment_min_value_'.$currency['id_currency'], MyConf::get('REWARDS_PAYMENT_MIN_VALUE_'.$currency['id_currency'], null, $this->id_template)).'" onBlur="showVirtualValue(this, '.$currency['id_currency'].', false)" /> <label class="t">'.$currency['sign'].' <span class="virtualvalue"></span></label> '.((int)$currency['id_currency'] != (int)Configuration::get('PS_CURRENCY_DEFAULT') ? ' <a href="#" onClick="return convertCurrencyValue(this, \'rewards_payment_min_value\', '.$currency['conversion_rate'].')"><img src="'._MODULE_DIR_.'allinone_rewards/img/convert.gif" style="vertical-align: middle !important"></a>' : '').'</td>
								</tr>';
		}
		$html .= '
							</table>
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Convertion rate').'<br/><small>'.$this->l('Example: for 100€ in reward account, if ratio is 75 then the customer will get only 75€ payment').'</small></label>
						<div class="margin-form">
							<input type="text" size="4" maxlength="4" id="rewards_payment_ratio" name="rewards_payment_ratio" value="'.Tools::getValue('rewards_payment_ratio', MyConf::get('REWARDS_PAYMENT_RATIO', null, $this->id_template)).'" />
						</div>
					</fieldset>
					<div class="clear center"><input type="submit" name="submitReward" id="submitReward" value="'.$this->l('Save settings').'" class="button" /></div>
				</form>
			</div>
			<div id="tabs-'.$this->name.'-2" class="not_templated">
				<form action="'.$this->instance->getCurrentPage($this->name).'" method="post">
				<input type="hidden" name="tabs-'.$this->name.'" value="tabs-'.$this->name.'-2" />
				<fieldset>
					<legend>'.$this->l('Notifications').'</legend>
					<label>'.$this->l('Ignore list for all emails sent by the module').'</label>
					<div class="margin-form">
						<input type="text" size="50" id="rewards_mails_ignored" name="rewards_mails_ignored" value="'.Tools::getValue('rewards_mails_ignored', Configuration::get('REWARDS_MAILS_IGNORED')).'" />
						<br>'.$this->l('You can enter some emails or mask of emails, all separated by a coma, which will never receive any emails from the module.').'
						<br>'.$this->l('For example : john@doe.com,@marketplace.amazon,@alerts-shopping-flux').'
					</div>
					<div class="clear"></div>
					<label>'.$this->l('Send a periodic email to the customer with his rewards account balance').'</label>
					<div class="margin-form">
						<label class="t" for="rewards_reminder_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="rewards_reminder_on" name="rewards_reminder" value="1" '.(Tools::getValue('rewards_reminder', Configuration::get('REWARDS_REMINDER')) == 1 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_reminder_on">' . $this->l('Yes') . '</label>
						<label class="t" for="rewards_reminder_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="rewards_reminder_off" name="rewards_reminder" value="0" '.(Tools::getValue('rewards_reminder', Configuration::get('REWARDS_REMINDER')) == 0 ? 'checked="checked"' : '').' /> <label class="t" for="rewards_reminder_off">' . $this->l('No') . '</label>
					</div>
					<div class="clear optional rewards_reminder_optional">
						<div class="clear"></div>
						<label>'.$this->l('Minimum required in account to receive an email').'</label>
						<div class="margin-form">
							<input type="text" size="3" name="rewards_reminder_minimum" value="'.Tools::getValue('rewards_reminder_minimum', (float)Configuration::get('REWARDS_REMINDER_MINIMUM')).'" /> '.$this->context->currency->sign.'&nbsp;
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Frequency of the emails (in days)').'</label>
						<div class="margin-form">
							<input type="text" size="3" name="rewards_reminder_frequency" value="'.Tools::getValue('rewards_reminder_frequency', (float)Configuration::get('REWARDS_REMINDER_FREQUENCY')).'" />
						</div>
					</div>
				</fieldset>
				<div class="clear center"><input class="button" name="submitRewardsNotifications" id="submitRewardsNotifications" value="'.$this->l('Save settings').'" type="submit" /></div>
				</form>
			</div>
			<div id="tabs-'.$this->name.'-3">
				<form action="'.$this->instance->getCurrentPage($this->name).'" method="post" enctype="multipart/form-data">
					<input type="hidden" name="tabs-'.$this->name.'" value="tabs-'.$this->name.'-3" />
					<fieldset class="not_templated">
						<legend>'.$this->l('Labels of the different rewards states displayed in the rewards account').'</legend>
						<label>'.$this->l('Initial').'</label>
						<div class="margin-form translatable">';
		foreach ($languages as $language)
			$html .= '
							<div class="lang_'.$language['id_lang'].'" id="default_reward_state_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
								<input size="33" type="text" name="default_reward_state_'.$language['id_lang'].'" value="'.(isset($this->rewardStateDefault->name[(int)$language['id_lang']]) ? htmlentities($this->rewardStateDefault->name[(int)$language['id_lang']], ENT_QUOTES, 'utf-8') : htmlentities($this->rewardStateDefault->name[(int)$defaultLanguage], ENT_QUOTES, 'utf-8')).'" />
							</div>';
		$html .= '
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Converted').'</label>
						<div class="margin-form translatable">';
		foreach ($languages as $language)
			$html .= '
							<div class="lang_'.$language['id_lang'].'" id="convert_reward_state_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
								<input size="33" type="text" name="convert_reward_state_'.$language['id_lang'].'" value="'.(isset($this->rewardStateConvert->name[(int)$language['id_lang']]) ? htmlentities($this->rewardStateConvert->name[(int)$language['id_lang']], ENT_QUOTES, 'utf-8') : htmlentities($this->rewardStateConvert->name[(int)$defaultLanguage], ENT_QUOTES, 'utf-8')).'" />
							</div>';
		$html .= '
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Validation').'</label>
						<div class="margin-form translatable">';
		foreach ($languages as $language)
			$html .= '
							<div class="lang_'.$language['id_lang'].'" id="validation_reward_state_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
								<input size="33" type="text" name="validation_reward_state_'.$language['id_lang'].'" value="'.(isset($this->rewardStateValidation->name[(int)$language['id_lang']]) ? htmlentities($this->rewardStateValidation->name[(int)$language['id_lang']], ENT_QUOTES, 'utf-8') : htmlentities($this->rewardStateValidation->name[(int)$defaultLanguage], ENT_QUOTES, 'utf-8')).'" />
							</div>';
		$html .= '
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Return period not exceeded').'</label>
						<div class="margin-form translatable">';
		foreach ($languages as $language)
			$html .= '
							<div class="lang_'.$language['id_lang'].'" id="return_period_reward_state_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
								<input size="33" type="text" name="return_period_reward_state_'.$language['id_lang'].'" value="'.(isset($this->rewardStateReturnPeriod->name[(int)$language['id_lang']]) ? htmlentities($this->rewardStateReturnPeriod->name[(int)$language['id_lang']], ENT_QUOTES, 'utf-8') : htmlentities($this->rewardStateReturnPeriod->name[(int)$defaultLanguage], ENT_QUOTES, 'utf-8')).'" />
							</div>';
		$html .= '
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Cancelled').'</label>
						<div class="margin-form translatable">';
		foreach ($languages as $language)
			$html .= '
							<div class="lang_'.$language['id_lang'].'" id="cancel_reward_state_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
								<input size="33" type="text" name="cancel_reward_state_'.$language['id_lang'].'" value="'.(isset($this->rewardStateCancel->name[(int)$language['id_lang']]) ? htmlentities($this->rewardStateCancel->name[(int)$language['id_lang']], ENT_QUOTES, 'utf-8') : htmlentities($this->rewardStateCancel->name[(int)$defaultLanguage], ENT_QUOTES, 'utf-8')).'" />
							</div>';
		$html .= '
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Waiting for payment').'</label>
						<div class="margin-form translatable">';
		foreach ($languages as $language)
			$html .= '
							<div class="lang_'.$language['id_lang'].'" id="waiting_payment_reward_state_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
								<input size="33" type="text" name="waiting_payment_reward_state_'.$language['id_lang'].'" value="'.(isset($this->rewardStateWaitingPayment->name[(int)$language['id_lang']]) ? htmlentities($this->rewardStateWaitingPayment->name[(int)$language['id_lang']], ENT_QUOTES, 'utf-8') : htmlentities($this->rewardStateWaitingPayment->name[(int)$defaultLanguage], ENT_QUOTES, 'utf-8')).'" />
							</div>';
		$html .= '
						</div>
						<div class="clear"></div>
						<label>'.$this->l('Paid').'</label>
						<div class="margin-form translatable">';
		foreach ($languages as $language)
			$html .= '
							<div class="lang_'.$language['id_lang'].'" id="paid_reward_state_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
								<input size="33" type="text" name="paid_reward_state_'.$language['id_lang'].'" value="'.(isset($this->rewardStatePaid->name[(int)$language['id_lang']]) ? htmlentities($this->rewardStatePaid->name[(int)$language['id_lang']], ENT_QUOTES, 'utf-8') : htmlentities($this->rewardStatePaid->name[(int)$defaultLanguage], ENT_QUOTES, 'utf-8')).'" />
							</div>';
		$html .= '
						</div>
					</fieldset>
					<fieldset>
						<legend>'.$this->l('Text to display in the rewards account').'</legend>
						<div class="translatable">';
		foreach ($languages AS $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').';float: left;">
								<textarea class="rte autoload_rte" cols="80" rows="25" name="rewards_general_txt['.$language['id_lang'].']">'.MyConf::get('REWARDS_GENERAL_TXT', (int)$language['id_lang'], $this->id_template).'</textarea>
							</div>';
		}
		$html .= '
						</div>
					</fieldset>
					<fieldset>
						<legend>'.$this->l('Recommendations for the payment (bank information, invoice, delay...)').'</legend>
						<div class="translatable">';
		foreach ($languages AS $language) {
			$html .= '
							<div class="lang_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').';float: left;">
								<textarea class="rte autoload_rte" cols="80" rows="25" name="rewards_payment_txt['.$language['id_lang'].']">'.MyConf::get('REWARDS_PAYMENT_TXT', (int)$language['id_lang'], $this->id_template).'</textarea>
							</div>';
		}
		$html .= '
						</div>
					</fieldset>
					<div class="clear center"><input type="submit" name="submitRewardText" id="submitRewardText" value="'.$this->l('Save settings').'" class="button" /></div>
				</form>
			</div>
		</div>';

		return $html;
	}

	private function _getStatistics()
	{
		$this->instanceDefaultStates();
		$stats = RewardsModel::getAdminStatistics();
		$token = Tools::getAdminToken('AdminCustomers'.(int)Tab::getIdFromClassName('AdminCustomers').(int)$this->context->employee->id);
		$html = "
		<div class='statistics'>
			<div class='title'>".$this->l('General synthesis')."</div>
			<table class='general'>
				<tr class='title'>
					<td>".$this->l('Number of rewards')."</td>
					<td>".$this->l('Rewarded customers')."</td>
					<td class='right'>".$this->l('Total rewards')."</td>
					<td class='right'>".$this->l('Used as a voucher during an order')."</td>
				</tr>
				<tr>
					<td>".$stats['nb_rewards']."</td>
					<td>".$stats['nb_customers']."</td>
					<td class='right'>".Tools::displayPrice($stats['total_rewards'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
					<td class='right'>".Tools::displayPrice($stats['total_cart_rules'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
				</tr>

				</tr>
			</table>

			<div class='title'>".$this->l('Details by reward status')."</div>
			<table class='status'>
				<tr class='title'>
					<td>".$this->l('Status')."</td>
					<td>".$this->l('Number of rewards')."</td>
					<td>".$this->l('Rewarded customers')."</td>
					<td class='right'>".$this->l('Total rewards')."</td>
				</tr>
				<tr>
					<td class='left'>".$this->rewardStateDefault->name[(int)$this->context->language->id]."</td>
					<td>".$stats['nb_rewards'.$this->rewardStateDefault->id]."</td>
					<td>".$stats['nb_customers'.$this->rewardStateDefault->id]."</td>
					<td class='right'>".Tools::displayPrice($stats['total_rewards'.$this->rewardStateDefault->id]+$stats['total_rewards'.$this->rewardStateReturnPeriod->id], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
				</tr>
				<tr>
					<td class='left'>".$this->rewardStateValidation->name[(int)$this->context->language->id]."</td>
					<td>".$stats['nb_rewards'.$this->rewardStateValidation->id]."</td>
					<td>".$stats['nb_customers'.$this->rewardStateValidation->id]."</td>
					<td class='right'>".Tools::displayPrice($stats['total_rewards'.$this->rewardStateValidation->id], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
				</tr>
				<tr>
					<td class='left'>".$this->rewardStateReturnPeriod->name[(int)$this->context->language->id]."</td>
					<td>".$stats['nb_rewards'.$this->rewardStateReturnPeriod->id]."</td>
					<td>".$stats['nb_customers'.$this->rewardStateReturnPeriod->id]."</td>
					<td class='right'>".Tools::displayPrice($stats['total_rewards'.$this->rewardStateDefault->id]+$stats['total_rewards'.$this->rewardStateReturnPeriod->id], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
				</tr>
				<tr>
					<td class='left'>".$this->rewardStateCancel->name[(int)$this->context->language->id]."</td>
					<td>".$stats['nb_rewards'.$this->rewardStateCancel->id]."</td>
					<td>".$stats['nb_customers'.$this->rewardStateCancel->id]."</td>
					<td class='right'>".Tools::displayPrice($stats['total_rewards'.$this->rewardStateCancel->id], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
				</tr>
				<tr>
					<td class='left'>".$this->rewardStateConvert->name[(int)$this->context->language->id]."</td>
					<td>".$stats['nb_rewards'.$this->rewardStateConvert->id]."</td>
					<td>".$stats['nb_customers'.$this->rewardStateConvert->id]."</td>
					<td class='right'>".Tools::displayPrice($stats['total_rewards'.$this->rewardStateConvert->id], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
				</tr>
				<tr>
					<td class='left'>".$this->rewardStateWaitingPayment->name[(int)$this->context->language->id]."</td>
					<td>".$stats['nb_rewards'.$this->rewardStateWaitingPayment->id]."</td>
					<td>".$stats['nb_customers'.$this->rewardStateWaitingPayment->id]."</td>
					<td class='right'>".Tools::displayPrice($stats['total_rewards'.$this->rewardStateWaitingPayment->id], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
				</tr>
				<tr>
					<td class='left'>".$this->rewardStatePaid->name[(int)$this->context->language->id]."</td>
					<td>".$stats['nb_rewards'.$this->rewardStatePaid->id]."</td>
					<td>".$stats['nb_customers'.$this->rewardStatePaid->id]."</td>
					<td class='right'>".Tools::displayPrice($stats['total_rewards'.$this->rewardStatePaid->id], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
				</tr>
			</table>

			<div class='title'>".$this->l('Details by reward type')."</div>
			<table class='status'>
				<tr class='title'>
					<td class='left'>".$this->l('Type')."</td>
					<td>".$this->l('Number of rewards')."</td>
					<td>".$this->l('Rewarded customers')."</td>
					<td class='right'>".$this->l('Total rewards')."</td>
				</tr>";
		foreach($this->instance->plugins as $plugin) {
			if (!$plugin instanceof RewardsCorePlugin) {
				$html .= "
					<tr>
						<td class='left'>".$plugin->getTitle()."</td>
						<td>".$stats['nb_rewards'.$plugin->name]."</td>
						<td>".$stats['nb_customers'.$plugin->name]."</td>
						<td class='right'>".Tools::displayPrice($stats['total_rewards'.$plugin->name], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
					</tr>";
			}
		}

		$html .= "
					<tr>
						<td class='left'>".$this->l('Free')."</td>
						<td>".(isset($stats['nb_rewardsfree']) ? $stats['nb_rewardsfree'] : 0)."</td>
						<td>".(isset($stats['nb_customersfree']) ? $stats['nb_customersfree'] : 0)."</td>
						<td class='right'>".Tools::displayPrice(isset($stats['total_rewardsfree']) ? $stats['total_rewardsfree'] : 0, (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
					</tr>
			</table>

			<div class='title'>".$this->l('Details by customer')."</div>
			<table class='tablesorter tablesorter-ice'>
				<thead>
					<tr>
						<th>".$this->l('Name')."</th>
						<th>".$this->l('Number of rewards')."</th>
						<th>".$this->rewardStateDefault->name[(int)$this->context->language->id]."</th>
						<th>".$this->rewardStateValidation->name[(int)$this->context->language->id]."</th>
						<th>".$this->rewardStateConvert->name[(int)$this->context->language->id]."</th>
						<th>".$this->l('Used during an order')."</th>
						<th>".$this->rewardStateWaitingPayment->name[(int)$this->context->language->id]."</th>
						<th>".$this->rewardStatePaid->name[(int)$this->context->language->id]."</th>
						<th>".$this->l('Total rewards')."</th>
					</tr>
				</thead>
				<tbody>";
		if (isset($stats['customers'])) {
			foreach($stats['customers'] as $id_customer => $customer) {
				$html .= "
					<tr>
						<td class='left'><a href='?tab=AdminCustomers&id_customer=".$id_customer."&viewcustomer&token=".$token."'>".$customer['lastname']." ".$customer['firstname']."</a></td>
						<td>".(isset($customer['nb_rewards']) ? $customer['nb_rewards'] : 0)."</td>
						<td class='right'>".Tools::displayPrice(isset($customer['total_rewards'.$this->rewardStateDefault->id]) ? $customer['total_rewards'.$this->rewardStateDefault->id] : 0, (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='right'>".Tools::displayPrice(isset($customer['total_rewards'.$this->rewardStateValidation->id]) ? $customer['total_rewards'.$this->rewardStateValidation->id] : 0, (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='right'>".Tools::displayPrice(isset($customer['total_rewards'.$this->rewardStateConvert->id]) ? $customer['total_rewards'.$this->rewardStateConvert->id] : 0, (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='right'>".Tools::displayPrice(isset($customer['total_cart_rules_used']) ? $customer['total_cart_rules_used'] : 0, (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='right'>".Tools::displayPrice(isset($customer['total_rewards'.$this->rewardStateWaitingPayment->id]) ? $customer['total_rewards'.$this->rewardStateWaitingPayment->id] : 0, (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='right'>".Tools::displayPrice(isset($customer['total_rewards'.$this->rewardStatePaid->id]) ? $customer['total_rewards'.$this->rewardStatePaid->id] : 0, (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
						<td class='right'>".Tools::displayPrice(isset($customer['total_rewards']) ? $customer['total_rewards'] : 0, (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
					</tr>";
			}
		}
		$html .= "
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
		return $html;
	}

	private function _getPayments()
	{
		if (Tools::getValue('accept_payment')) {
			RewardsPaymentModel::acceptPayment((int)Tools::getValue('accept_payment'));
			die();
		}

		$payments = RewardsPaymentModel::getPendingList();
		if (count($payments) > 0) {
			$token = Tools::getAdminToken('AdminCustomers'.(int)Tab::getIdFromClassName('AdminCustomers').(int)$this->context->employee->id);
			$html = "
			<div class='payments'>
				<table class='tablesorter tablesorter-ice'>
					<thead>
						<tr>
							<th>".$this->l('Request date')."</th>
							<th>".$this->l('Customer')."</th>
							<th>".$this->l('Value')."</th>
							<th>".$this->l('Details')."</th>
							<th class='filter-false sorter-false'>".$this->l('Invoice')."</th>
							<th class='filter-false sorter-false'>".$this->l('Action')."</th>
						</tr>
					</thead>
					<tbody>";
			foreach($payments as $payment) {
				$html .= "
						<tr>
							<td>".$payment['date_add']."</td>
							<td><a href='?tab=AdminCustomers&id_customer=".$payment['id_customer']."&viewcustomer&token=".$token."'>".$payment['lastname']." ".$payment['firstname']."</a></td>
							<td align='right'>".Tools::displayPrice($payment['credits'], (int)Configuration::get('PS_CURRENCY_DEFAULT'))."</td>
							<td>".nl2br($payment['detail'])."</td>
							<td align='center'>".($payment['invoice'] ? "<a href='"._MODULE_DIR_.$this->instance->name."/uploads/".$payment['invoice']."' download='Invoice.".pathinfo($payment['invoice'], PATHINFO_EXTENSION)."'>".$this->l('View')."</a>" : "-")."</td>
							<td align='center'><a href='#' class='payment_validation' id='".$payment['id_payment']."'>".$this->l('Mark as paid')."</a></td>
						</tr>";
			}
			$html .= "
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
				$('.payment_validation').live('click', function(){
					var obj = $(this).parent().parent();
					$.ajax({
						type	: 'POST',
						cache	: false,
						url		: '".$this->instance->getCurrentPage($this->name, true)."&payments=1&accept_payment='+$(this).attr('id'),
						dataType: 'html',
						success : function(data) {
							obj.remove();
							$('.tablesorter').trigger('update');
						}
					});
					return false;
				});

				var footer_pager = \"".$this->l('{startRow} to {endRow} of {totalRows} rows')."\";
				initTableSorter();
			</script>";
		} else
			$html = "<div class='payments'>".$this->l('No request found')."</div>";
		return $html;
	}

	// add the css used by the module
	public function hookDisplayHeader()
	{
		$this->context = Context::getContext();
		if ($this->context->controller instanceof ProductController && Tools::getValue('id_product') == (int)Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT'))
			Tools::redirect($this->context->link->getPageLink(Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order', true));

		if (version_compare(_PS_VERSION_, '1.7', '>='))
			$this->context->controller->addCSS($this->instance->getPath().'css/presta-1.7/allinone_rewards-1.7.css', 'all');
		else
			$this->context->controller->addCSS($this->instance->getPath().'css/allinone_rewards.css', 'all');

		if (!Tools::getValue('content_only') && Tools::getValue('action')!='quickview' && ((version_compare(_PS_VERSION_, '1.7', '<') && ($this->context->controller instanceof CategoryController || $this->context->controller instanceof IndexController || $this->context->controller instanceof Allinone_rewardsGiftsModuleFrontController))	|| $this->context->controller instanceof ProductController) && RewardsModel::isCustomerAllowedForGiftProduct()) {
			$id_template = (int)MyConf::getIdTemplate('core', $this->context->customer->id);
			if ($this->context->controller instanceof ProductController) {
				$this->context->controller->addJS($this->instance->getPath().'js/product.js');
				$this->context->controller->addJS($this->instance->getPath().'js/product-purchase-button.js');
			} else if (MyConf::get('REWARDS_GIFT_LIST_BUTTON', null, $id_template))
				$this->context->controller->addJS($this->instance->getPath().'js/product-purchase-button.js');
		}

		// Convertit les récompenses à l'état ReturnPeriodId en ValidationId si la date de retour est dépassée, et envoie les mails de rappel
		if (!Configuration::get('REWARDS_USE_CRON')) {
			RewardsModel::checkRewardsStates();
			RewardsAccountModel::sendReminder();
		}

		if (($this->context->controller instanceof OrderOpcController || $this->context->controller instanceof OrderController || $this->context->controller instanceof CartController) && RewardsModel::isCustomerAllowedForGiftProduct()) {
			if (version_compare(_PS_VERSION_, '1.7', '>=')) {
				$this->context->controller->addJS($this->instance->getPath().'js/cart.js');
				Media::addJsDef(array('aior_id_default_gift_product' => Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT')));
			} else {
				return '<style>
							tr[id*="product_'.Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT').'"] .cart_quantity * { display: none; }
							tr[id*="product_'.Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT').'"] .cart_delete * { display: none; }
							tr[id*="product_'.Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT').'"] .cart_avail * { display: none; }
						</style>';
			}
		}
		return false;
	}

	// display the link to access to the rewards account
	public function hookDisplayCustomerAccount($params)
	{
		if ($this->isRewardsAccountVisible()) {
			if (version_compare(_PS_VERSION_, '1.7', '>='))
				return $this->instance->display($this->instance->path, 'presta-1.7/customer-account.tpl');
			return $this->instance->display($this->instance->path, 'customer-account.tpl');
		}
		return false;
	}

	public function hookDisplayMyAccountBlock($params)
	{
		if ($this->isRewardsAccountVisible()) {
			if (version_compare(_PS_VERSION_, '1.7', '>='))
					return $this->instance->display($this->instance->path, 'presta-1.7/my-account.tpl');
				return $this->instance->display($this->instance->path, 'my-account.tpl');
		}
		return false;
	}

	public function hookDisplayMyAccountBlockFooter($params)
	{
		return $this->hookDisplayMyAccountBlock($params);
	}

	// display rewards account information in customer admin page
	public function hookDisplayAdminCustomers($params)
	{
		$customer = new Customer((int)$params['id_customer']);
		if ($customer && !Validate::isLoadedObject($customer))
			die(Tools::displayError('Incorrect object Customer.'));

		$msg = $this->postProcess($params);
		$totals = RewardsModel::getAllTotalsByCustomer((int)$params['id_customer']);
		$rewards = RewardsModel::getAllByIdCustomer((int)$params['id_customer'], true);
		$payments = RewardsPaymentModel::getAllByIdCustomer((int)$params['id_customer']);
		$rewards_account = new RewardsAccountModel((int)$params['id_customer']);
		$states_for_update = array(RewardsStateModel::getDefaultId(), RewardsStateModel::getValidationId(), RewardsStateModel::getCancelId(), RewardsStateModel::getReturnPeriodId());
		$core_template_id = (int)MyConf::getIdTemplate('core', (int)$params['id_customer']);
		$core_templates = RewardsTemplateModel::getList('core');
		$loyalty_template_id = (int)MyConf::getIdTemplate('loyalty', (int)$params['id_customer']);
		$loyalty_templates = RewardsTemplateModel::getList('loyalty');

		$smarty_values = array(
			'customer' => $customer,
			'msg' => $msg,
			'totals' => $totals,
			'rewards' => $rewards,
			'payments' => $payments,
			'payment_authorized' => (int)MyConf::get('REWARDS_PAYMENT', null, $core_template_id),
			'rewards_account' => $rewards_account,
			'states_for_update' => $states_for_update,
			'sign' => $this->context->currency->sign,
			'rewardStateDefault' => $this->rewardStateDefault->name[(int)$this->context->language->id],
			'rewardStateValidation' => $this->rewardStateValidation->name[(int)$this->context->language->id],
			'rewardStateCancel' => $this->rewardStateCancel->name[(int)$this->context->language->id],
			'rewardStateConvert' => $this->rewardStateConvert->name[(int)$this->context->language->id],
			'rewardStateReturnPeriod' => $this->rewardStateReturnPeriod->name[(int)$this->context->language->id],
			'rewardStateWaitingPayment' => $this->rewardStateWaitingPayment->name[(int)$this->context->language->id],
			'rewardStatePaid' => $this->rewardStatePaid->name[(int)$this->context->language->id],
			'new_reward_value' => (float)Tools::getValue('new_reward_value'),
			'new_reward_state' => (int)Tools::getValue('new_reward_state'),
			'new_reward_reason' => Tools::getValue('new_reward_reason'),
			'core_template_id' => $core_template_id,
			'core_templates' => $core_templates,
			'loyalty_template_id' => $loyalty_template_id,
			'loyalty_templates' => $loyalty_templates,
			'date_format' => $this->context->language->date_format_full
		);
		$this->context->smarty->assign($smarty_values);
		return $this->instance->display($this->instance->path, 'admincustomer.tpl');
	}

	// Hook called in tab AdminProduct
	public function hookDisplayAdminProductsExtra($params)
	{
		$id_product = version_compare(_PS_VERSION_, '1.7', '>=') ? $params['id_product'] : Tools::getValue('id_product');
		if (Validate::isLoadedObject($product = new Product((int)$id_product))) {
			if (!$product->customizable && $product->minimal_quantity <= 1) {
				$rewards_gift_product = new RewardsGiftProductModel($product->id);

				$attributes = $product->getAttributesResume($this->context->language->id);
				if (empty($attributes))
		            $attributes[] = array('id_product_attribute' => 0, 'attribute_designation' => '');

		        $product_combinations = array();
		        foreach ($attributes as $attribute) {
	       			if ($rewards_gift_product->gift_allowed)
		            	$product_combinations[$attribute['id_product_attribute']] = RewardsGiftProductAttributeModel::getGiftProductAttribute($product->id, $attribute['id_product_attribute']);
		            else
		            	$product_combinations[$attribute['id_product_attribute']] = array('gift_allowed' => 0, 'purchase_allowed' => 0);
		            $product_combinations[$attribute['id_product_attribute']]['name'] = rtrim($product->name[$this->context->language->id].' - '.$attribute['attribute_designation'], ' - ');
		        }

		        $this->context->smarty->assign(array(
		        	'gift_allowed' => isset($rewards_gift_product->gift_allowed) ? $rewards_gift_product->gift_allowed : -1,
		        	'product_combinations' => $product_combinations
		        ));
		    }

			$smarty_values = array(
				'currency' => $this->context->currency,
				'product_rewards_url' => $this->context->link->getAdminLink('AdminProductReward').'&ajax=1&id_product='.$product->id,
				'virtual_value' => (float)Configuration::get('REWARDS_VIRTUAL_VALUE_'.(int)Configuration::get('PS_CURRENCY_DEFAULT')),
				'virtual_name' => Configuration::get('REWARDS_VIRTUAL_NAME', (int)$this->context->language->id),
				'product_loyalty_rewards' => RewardsProductModel::getProductRewardsList($product->id, 'loyalty'),
				'loyalty_templates' => RewardsTemplateModel::getList('loyalty'),
				'product_sponsorship_rewards' => RewardsProductModel::getProductRewardsList($product->id, 'sponsorship'),
				'sponsorship_templates' => RewardsTemplateModel::getList('sponsorship'),
			);
			$this->context->smarty->assign($smarty_values);
			return $this->instance->display($this->instance->path, 'adminproductsextra.tpl');
		}
		return $this->l('Please, create the product first');
	}

	public function hookActionAdminControllerSetMedia($params)
	{
    	// add necessary javascript to customers back office
		if ($this->context->controller->controller_name == 'AdminCustomers') {
			$this->context->controller->addCSS($this->instance->getPath().'js/tablesorter/css/theme.ice.css', 'all');
			$this->context->controller->addCSS($this->instance->getPath().'js/tablesorter/addons/pager/jquery.tablesorter.pager.css', 'all');
			$this->context->controller->addJS($this->instance->getPath().'js/tablesorter/jquery.tablesorter.min.js');
			$this->context->controller->addJS($this->instance->getPath().'js/tablesorter/jquery.tablesorter.widgets.js');
			$this->context->controller->addJS($this->instance->getPath().'js/tablesorter/addons/pager/jquery.tablesorter.pager.js');
			$this->context->controller->addJS($this->instance->getPath().'js/admin-customer.js');
		}
    	if ($this->context->controller->controller_name == 'AdminProducts') {
    		if (version_compare(_PS_VERSION_, '1.7', '>=')) {
	    		$this->context->controller->addCSS('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
        		$this->context->controller->addCSS($this->instance->getPath().'css/presta-1.7/admin-product.css');
    		}
        	$this->context->controller->addJS($this->instance->getPath().'js/admin-product.js');
    	}
	}

	public function hookActionObjectCustomerDeleteAfter($params)
	{
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards` WHERE `id_customer` NOT IN (SELECT `id_customer` FROM `'._DB_PREFIX_.'customer`)');
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_history` WHERE `id_reward` NOT IN (SELECT `id_reward` FROM `'._DB_PREFIX_.'rewards`)');
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_payment` WHERE `id_payment` NOT IN (SELECT `id_payment` FROM `'._DB_PREFIX_.'rewards`)');
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_account` WHERE `id_customer` NOT IN (SELECT `id_customer` FROM `'._DB_PREFIX_.'customer`)');
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_template_customer` WHERE `id_customer` NOT IN (SELECT `id_customer` FROM `'._DB_PREFIX_.'customer`)');
	}

	public function hookActionObjectProductDeleteAfter($params)
	{
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_product` WHERE `id_product`='.(int)$params['object']->id);
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_gift_product` WHERE `id_product`='.(int)$params['object']->id);
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_gift_product_attribute` WHERE `id_product`='.(int)$params['object']->id);
	}

	// check if the product is in a category which is allowed for free gift
	// or if a custom behavior is defined on that product
	private function _isGiftProductAllowed($id_template, $id_product, $id_product_attribute)
	{
		if (Validate::isLoadedObject($product = new Product($id_product)) && !$product->customizable && $product->minimal_quantity <= 1 && (float)$product->getPrice(false, $id_product_attribute) > 0) {
			$gift_allowed = RewardsGiftProductAttributeModel::getGiftProductAttributeAllowed($id_product, $id_product_attribute);
			switch($gift_allowed) {
				// product has no custom value defined in the product sheet
				case -1:
					// all categories
					if ((int)MyConf::get('REWARDS_GIFT_ALL_CATEGORIES', null, $id_template)==1)
						return true;
					// none
					else if ((int)MyConf::get('REWARDS_GIFT_ALL_CATEGORIES', null, $id_template)==-1)
						return false;
					else {
						$allowed_categories = array();
						$categories = explode(',', MyConf::get('REWARDS_GIFT_CATEGORIES', null, $id_template));
						foreach($categories as $category)
							$allowed_categories[] = array('id_category' => $category);
						return Product::idIsOnCategoryId($id_product, $allowed_categories);
					}
				// product is not allowed in product sheet
				case 0:
					return false;
				// product is active in product sheet
				case 1:
					return true;
			}
		}
		return false;
	}

	private function _isGiftProductPurchaseAllowed($id_template, $id_product, $id_product_attribute)
	{
		$purchase_allowed = RewardsGiftProductAttributeModel::getGiftProductAttributePurchaseAllowed($id_product, $id_product_attribute);
		if ($purchase_allowed == -1) {
			// product has no custom value defined in the product sheet
			return (bool)MyConf::get('REWARDS_GIFT_BUY_BUTTON', null, $id_template);
		} else
			return $purchase_allowed;
	}

	// add the common object to the footer for the product page, and list of products
	public function hookDisplayFooter()
	{
		if (!Tools::getValue('content_only') && Tools::getValue('action')!='quickview' && ((version_compare(_PS_VERSION_, '1.7', '<') && ($this->context->controller instanceof CategoryController || $this->context->controller instanceof IndexController || $this->context->controller instanceof Allinone_rewardsGiftsModuleFrontController))	|| $this->context->controller instanceof ProductController) && RewardsModel::isCustomerAllowedForGiftProduct()) {
			$id_template = (int)MyConf::getIdTemplate('core', $this->context->customer->id);
			if (!($this->context->controller instanceof ProductController)) {
				if (MyConf::get('REWARDS_GIFT_LIST_BUTTON', null, $id_template)) {
					$totals = RewardsModel::getAllTotalsByCustomer((int)$this->context->customer->id);
					$total_available = isset($totals[RewardsStateModel::getValidationId()]) ? (float)$totals[RewardsStateModel::getValidationId()] : 0;
					if ($total_available > 0) {
						$this->context->smarty->assign('aior_total_available_display', $this->instance->getRewardReadyForDisplay($total_available, (int)$this->context->currency->id));
						$this->context->smarty->assign('aior_total_available_real', $total_available);
						return $this->instance->display($this->instance->path, 'product-footer.tpl');
					}
				}
			} else {
				$this->context->smarty->assign('aior_total_available_display', '');
				$this->context->smarty->assign('aior_total_available_real', '');
				return $this->instance->display($this->instance->path, 'product-footer.tpl');
			}
		}
		return false;
	}

	// necesary because hookDisplayProductButtons changed name in 1.7.1
	// and alias doesn't work
	public function hookDisplayProductAdditionalInfo($params)
	{
		if (version_compare(_PS_VERSION_, '1.7.1.0', '>='))
			return $this->hookDisplayProductButtons($params);
	}

	public function hookDisplayProductButtons($params)
	{
		// TODO : récupérer le purchase_allowed de la déclinaison par défaut,
		// et masquer le bouton si besoin, pour éviter de le voir disparaitre
		$this->context = Context::getContext();
		if (!Tools::getValue('content_only') && Tools::getValue('action')!='quickview' && RewardsModel::isCustomerAllowedForGiftProduct()) {
			$totals = RewardsModel::getAllTotalsByCustomer((int)$this->context->customer->id);
			$total_available = isset($totals[RewardsStateModel::getValidationId()]) ? (float)$totals[RewardsStateModel::getValidationId()] : 0;
			if ($total_available > 0) {
				if (version_compare(_PS_VERSION_, '1.7', '>='))
					return $this->instance->display($this->instance->path, 'presta-1.7/product-purchase-button.tpl');
				return $this->instance->display($this->instance->path, 'product-purchase-button.tpl');
			}
		}
	}

	// called on product page to display the button allowing to buy the selected combination from rewards account
	public function displayPurchaseButtonOnProductPage($id_product, $id_product_attribute=0)
	{
		$id_template = (int)MyConf::getIdTemplate('core', $this->context->customer->id);
		if (RewardsModel::isCustomerAllowedForGiftProduct() && $this->_isGiftProductAllowed($id_template, $id_product, $id_product_attribute)) {
			$totals = RewardsModel::getAllTotalsByCustomer((int)$this->context->customer->id);
			$total_available = isset($totals[RewardsStateModel::getValidationId()]) ? (float)$totals[RewardsStateModel::getValidationId()] : 0;
			if ($total_available > 0) {
				$product = new Product((int)$id_product);
				$price = $product->getPrice(false, $id_product_attribute);
				if (MyConf::get('REWARDS_GIFT_TAX', null, $id_template))
					$price = $product->getPrice(true, $id_product_attribute);
				$price = (float)round(Tools::convertPrice($price, $this->context->currency, false), 2);

				if ($price > 0 && $total_available >= $price) {
					return Tools::jsonEncode(array(
						'has_error' => false,
						'aior_product_price_display' => $this->instance->getRewardReadyForDisplay($price, (int)$this->context->currency->id),
						'aior_total_available_display' => $this->instance->getRewardReadyForDisplay($total_available, (int)$this->context->currency->id),
						'aior_total_available_real' => $total_available,
						'aior_total_available_after' => $this->instance->getRewardReadyForDisplay($total_available - $price, (int)$this->context->currency->id),
						'aior_show_buy_button' => $this->_isGiftProductPurchaseAllowed($id_template, $id_product, $id_product_attribute),
					));
				}
			}
			return Tools::jsonEncode(array(
				'has_error' => true,
				'aior_show_buy_button' => $this->_isGiftProductPurchaseAllowed($id_template, $id_product, $id_product_attribute)
			));
		}
		return Tools::jsonEncode(array('has_error' => true, 'aior_show_buy_button' => true));
	}

	public function hookDisplayProductListReviews($params)
	{
		if (version_compare(_PS_VERSION_, '1.6.1.0', '<=')) {
			$params['type']='after_price';
			return $this->_displayProductListButtons($params);
		}
		return false;
	}

	public function hookDisplayProductPriceBlock($params)
	{
		if ((version_compare(_PS_VERSION_, '1.6', '>=') && ($params['type'] == 'aior_crossseling' || $params['type'] == 'aior_productscategory')) || ($params['type'] == 'after_price' && !$this->context->controller instanceof ProductController))
			return $this->_displayProductListButtons($params);
		return false;
	}

	private function _displayProductListButtons($params)
	{
		$id_template = (int)MyConf::getIdTemplate('core', $this->context->customer->id);
		$id_product = (int)$params['product']['id_product'];
		$id_product_attribute = isset($params['product']) && isset($params['product']['id_product_attribute']) ? (int)$params['product']['id_product_attribute'] : 0;
		if (RewardsModel::isCustomerAllowedForGiftProduct() && $this->_isGiftProductAllowed($id_template, $id_product, $id_product_attribute)) {
			$this->context->smarty->assign('aior_id_product', $id_product);
			$this->context->smarty->assign('aior_id_product_attribute', $id_product_attribute);
			$this->context->smarty->assign('aior_show_buy_button', $this->_isGiftProductPurchaseAllowed($id_template, $id_product, $id_product_attribute));

			if ($params['type'] == 'after_price' && MyConf::get('REWARDS_GIFT_LIST_BUTTON', null, $id_template)) {
				$product = new Product((int)$id_product);
				$product->id_product_attribute = $id_product_attribute;
				$price = $product->getPrice(false, $id_product_attribute);
				if (MyConf::get('REWARDS_GIFT_TAX', null, $id_template))
					$price = $product->getPrice(true, $id_product_attribute);
				$price = (float)round(Tools::convertPrice($price, $this->context->currency, false), 2);
				$this->context->smarty->assign('aior_product_price_display', $this->instance->getRewardReadyForDisplay($price, (int)$this->context->currency->id));
				$this->context->smarty->assign('aior_product_price_real', $price);
			}
			return $this->instance->display($this->instance->path, 'product-list-purchase-button.tpl');
		}
		return false;
	}

	public function purchaseProductFromRewards($id_product, $id_product_attribute=0)
	{
		$id_template = (int)MyConf::getIdTemplate('core', $this->context->customer->id);
		if (RewardsModel::isCustomerAllowedForGiftProduct() && $this->_isGiftProductAllowed($id_template, $id_product, $id_product_attribute)) {
			$totals = RewardsModel::getAllTotalsByCustomer((int)$this->context->customer->id);
			$total_available = isset($totals[RewardsStateModel::getValidationId()]) ? (float)$totals[RewardsStateModel::getValidationId()] : 0;
			if ($total_available > 0) {
				$product = new Product((int)$id_product);
				$product->id_product_attribute = $id_product_attribute;
				$price = $product->getPrice(false, $id_product_attribute);
				if (MyConf::get('REWARDS_GIFT_TAX', null, $id_template))
					$price = $product->getPrice(true, $id_product_attribute);
				$price = (float)round(Tools::convertPrice($price, $this->context->currency, false), 2);

				if ($price > 0 && $total_available >= $price) {
					$result = RewardsModel::purchaseProductFromRewards($product, $total_available > $price ? $price : $total_available);
					if ($result === true)
						return Tools::jsonEncode(array('has_error' => false, 'aior_total_available_display' => $this->instance->getRewardReadyForDisplay($total_available - $price, (int)$this->context->currency->id), 'aior_total_available_real' => $total_available - $price));
					else {
						$error_msg = $this->l('A gift voucher has been generated, but it has not been added to the cart due to the following error:').'<br>'.($result===false ? $this->l('unknow error') : $result);
						$totals = RewardsModel::getAllTotalsByCustomer((int)$this->context->customer->id);
						$total_available = isset($totals[RewardsStateModel::getValidationId()]) ? (float)$totals[RewardsStateModel::getValidationId()] : 0;
						return Tools::jsonEncode(array('has_error' => true, 'error_msg' => $error_msg, 'aior_total_available_display' => $this->instance->getRewardReadyForDisplay($total_available, (int)$this->context->currency->id), 'aior_total_available_real' => $total_available));
					}
				} else {
					$price = $this->instance->getRewardReadyForDisplay($price, (int)$this->context->currency->id);
					$total_available_display = $this->instance->getRewardReadyForDisplay($total_available, (int)$this->context->currency->id);
					$error_msg = sprintf($this->l('You can not buy this product with your rewards. %s are required and you have only %s available.'), $price, $total_available_display);
					return Tools::jsonEncode(array('has_error' => true, 'error_msg' => $error_msg, 'aior_total_available_display' => $total_available_display, 'aior_total_available_real' => $total_available));
				}
			}
		}
		return Tools::jsonEncode(array('has_error' => true, 'error_msg' => $this->l('You are not allowed to buy this product with your rewards.')));
	}

	// add or remove the default product from the cart each time the cart is modified
	public function hookActionCartSave($params)
	{
		if (!defined('_PS_ADMIN_DIR_')) {
			//file_put_contents('yann.txt', '*****************'.chr(13), FILE_APPEND);
			if (!self::$_is_loading && RewardsModel::isCustomerAllowedForGiftProduct() && Validate::isLoadedObject($this->context->cart) && Validate::isLoadedObject(new Product((int)Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT')))) {
				// to avoid infinite loop caused by addCartRule
				self::$_is_loading = true;

				// does the cart contains free gift ?
				$cart_rules = $this->context->cart->getCartRules(CartRule::FILTER_ACTION_GIFT);
				//file_put_contents('yann.txt', 'regles panier : '.count($cart_rules).chr(13), FILE_APPEND);

				// prestashop efface tous les produits (standards + gift) quand on enlève un produit du panier qui est aussi en cadeau mais la règle du produit cadeau est toujours dans le panier.
				// Comme on peut avoir des règles liées au panier et sans le produit associé, il faut les réappliquer pour remettre le produit
				foreach($cart_rules as $cart_rule) {
					//file_put_contents('yann.txt', 'remove cart rules'.chr(13), FILE_APPEND);
					$this->context->cart->removeCartRule($cart_rule['id_cart_rule']);
					//file_put_contents('yann.txt', 'fin remove cart rules'.chr(13), FILE_APPEND);
				}
				foreach($cart_rules as $cart_rule) {
					//file_put_contents('yann.txt', 'add cart rules'.chr(13), FILE_APPEND);
					$this->context->cart->addCartRule($cart_rule['id_cart_rule']);
					//file_put_contents('yann.txt', 'fin add cart rules'.chr(13), FILE_APPEND);
				}

				// si il n'y a plus de règle valide, plus besoin du produit
				$cart_rules = $this->context->cart->getCartRules(CartRule::FILTER_ACTION_GIFT);
				if (count($cart_rules) == 0) {
					//file_put_contents('yann.txt', 'plus de regle panier, on supprime le produit'.chr(13), FILE_APPEND);
					Db::getInstance()->execute('
						DELETE FROM `'._DB_PREFIX_.'cart_product`
						WHERE `id_product` = '.(int)Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT').'
						AND `id_cart` = '.(int)$this->context->cart->id);
				} else {
					// does the cart contains the default product
					$is_in_cart = false;
					$result = $this->context->cart->containsProduct((int)Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT'), 0, null);
					if (!empty($result['quantity'])) {
						//file_put_contents('yann.txt', 'le produit est dans le panier'.chr(13), FILE_APPEND);
						$is_in_cart = true;
						// if quantity is more than 1, set it to 1
						if ((int)$result['quantity'] > 1) {
							Db::getInstance()->execute('
								UPDATE `'._DB_PREFIX_.'cart_product`
								SET `quantity` = 1
								WHERE `id_cart` = '.(int)$this->context->cart->id.'
								AND `id_product` = '.(int)Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT'));
						}
					}

					$nb_products = $this->context->cart->nbProducts();
					//file_put_contents('yann.txt', 'nb produits : '.$nb_products.chr(13), FILE_APPEND);
					$is_required = $nb_products > 0 && (count($cart_rules) == $nb_products || ($is_in_cart && $nb_products == count($cart_rules)+1));

					if ($is_required && !$is_in_cart) {
						//file_put_contents('yann.txt', 'on ajoute le produit '.(int)Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT').chr(13), FILE_APPEND);
						$this->context->cart->updateQty(1, (int)Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT'), 0);
						//file_put_contents('yann.txt', 'fin ajout du produit'.chr(13), FILE_APPEND);
						$nb_products = $this->context->cart->nbProducts();
						//file_put_contents('yann.txt', 'nb produits : '.$nb_products.chr(13), FILE_APPEND);
					} else if (!$is_required && $is_in_cart) {
						//file_put_contents('yann.txt', 'on supprime le produit'.chr(13), FILE_APPEND);
						Db::getInstance()->execute('
							DELETE FROM `'._DB_PREFIX_.'cart_product`
							WHERE `id_product` = '.(int)Configuration::getGlobalValue('REWARDS_ID_DEFAULT_GIFT_PRODUCT').'
							AND `id_cart` = '.(int)$this->context->cart->id);
					}
				}

				self::$_is_loading = false;
			}
		}
	}
}