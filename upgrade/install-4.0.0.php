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

function upgrade_module_4_0_0($object)
{
	$result = true;

	/* Column managment for the module */
    if (version_compare(_PS_VERSION_, '1.6.0.2', '>=')) {
    	$controllers = array('gifts');
    	$themes = Theme::getThemes();
        $theme_meta_value = array();
        foreach ($controllers as $controller) {
            $page = 'module-'.$object->name.'-'.$controller;
            $tmp = Db::getInstance()->getValue('SELECT * FROM '._DB_PREFIX_.'meta WHERE page="'.pSQL($page).'"');
            if ((int)$tmp > 0)
                continue;

            $meta = new Meta();
            $meta->page = $page;
            $meta->configurable = 1;
            $meta->save();
            if ((int)$meta->id > 0) {
                foreach ($themes as $theme) {
                    $theme_meta_value[] = array(
                        'id_theme' => $theme->id,
                        'id_meta' => $meta->id,
                        'left_column' => (int)$theme->default_left_column,
                        'right_column' => (int)$theme->default_right_column
                    );
                }
            }
        }

        if (count($theme_meta_value) > 0) {
            try {
                @Db::getInstance()->insert('theme_meta', $theme_meta_value);
            } catch (Exception $e) {}
        }
    }

	/* do not put result=false when error, because 3.0.2.1 (specific) already have these tables */
	try {
		@Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'rewards_product` ADD `plugin` VARCHAR(20) NOT NULL DEFAULT \'loyalty\'');
		@Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'rewards_product` ADD `level` INT UNSIGNED NOT NULL DEFAULT \'1\'');
	} catch (Exception $e) {}

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

	Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards` WHERE id_reward_state=5');
	Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_history` WHERE id_reward_state=5');
	Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_state` WHERE id_reward_state=5');
	Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_state_lang` WHERE id_reward_state=5');

	/* new column for reward's valdity */
	Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'rewards` ADD `date_end` DATETIME DEFAULT \'0000-00-00 00:00:00\' AFTER `reason`');
	if (Configuration::get('REWARDS_DURATION'))
		Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'rewards` SET date_end=DATE_ADD(date_upd, INTERVAL '.(int)Configuration::get('REWARDS_DURATION').' DAY)');

	/* new column for reminder activation */
	Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'rewards_account` ADD `remind_active` TINYINT NOT NULL DEFAULT \'1\' AFTER  `date_last_remind`');

	/* new option */
	Configuration::updateValue('RSPONSORSHIP_DEF_PRODUCT_REWARD', 0);
	Configuration::updateValue('RSPONSORSHIP_DEF_PRODUCT_TYPE', 0);
	Configuration::updateValue('REWARDS_GIFT_NB_ORDERS', 0);
	Configuration::updateValue('REWARDS_VOUCHER_NB_ORDERS', 0);
	Configuration::updateValue('REWARDS_PAYMENT_NB_ORDERS', 0);
	Configuration::updateValue('REWARDS_GIFT', 0);
	Configuration::updateValue('REWARDS_GIFT_SHOW_LINK', 1);
	Configuration::updateValue('REWARDS_GIFT_LIST_BUTTON', 1);
	Configuration::updateValue('REWARDS_GIFT_BUY_BUTTON', 1);
	Configuration::updateValue('REWARDS_GIFT_TAX', 1);
	Configuration::updateValue('REWARDS_GIFT_PREFIX', 'GIFT');
	Configuration::updateValue('REWARDS_GIFT_DURATION', 365);
	Configuration::updateValue('REWARDS_GIFT_MINIMAL_TAX', 0);
	Configuration::updateValue('REWARDS_GIFT_MINIMAL_SHIPPING', 0);
	Configuration::updateValue('REWARDS_GIFT_ALL_CATEGORIES', 0);
	Configuration::updateValue('REWARDS_GIFT_CATEGORIES', Configuration::get('REWARDS_VOUCHER_CATEGORY'));
    foreach (Currency::getCurrencies() as $currency)
        Configuration::updateValue('REWARDS_GIFT_MIN_ORDER_'.$currency['id_currency'], 0);

	/* delete useless key */
	MyConf::deleteByName('REWARDS_VOUCHER_CATEGORY');

	/* new version */
	Configuration::updateValue('REWARDS_VERSION', $object->version);

	/* new hooks */
	$object->registerHook('displayProductButtons');
	$object->registerHook('actionCartSave');
	if (version_compare(_PS_VERSION_, '1.6.1.0', '<='))
		$object->registerHook('displayProductListReviews');
	$object->registerHook('displayProductPriceBlock');

	/* clear cache */
	if (version_compare(_PS_VERSION_, '1.5.5.0', '>='))
		Tools::clearSmartyCache();

	return $result;
}