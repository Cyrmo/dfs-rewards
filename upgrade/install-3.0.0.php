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

function upgrade_module_3_0_0($object)
{
	$result = true;

    /* Column managment for the module */
    if (version_compare(_PS_VERSION_, '1.6.0.2', '>=')) {
    	$controllers = array('rewards', 'sponsorship');
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

    /* Change field type */
    Db::getInstance()->Execute('ALTER TABLE `' . _DB_PREFIX_ . 'rewards_product` CHANGE `value` `value` DECIMAL(20, 2) UNSIGNED NOT NULL DEFAULT \'0\'');

    /* Create new table */
    Db::getInstance()->Execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_sponsorship_code` (
            `id_sponsor` INT UNSIGNED NOT NULL,
            `code` VARCHAR(20) NOT NULL,
            PRIMARY KEY (`id_sponsor`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

    /* Delete rows if customer or product doesn't exist anymore */
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards` WHERE `id_customer` NOT IN (SELECT `id_customer` FROM `'._DB_PREFIX_.'customer`)');
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_history` WHERE `id_reward` NOT IN (SELECT `id_reward` FROM `'._DB_PREFIX_.'rewards`)');
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_payment` WHERE `id_payment` NOT IN (SELECT `id_payment` FROM `'._DB_PREFIX_.'rewards`)');
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_account` WHERE `id_customer` NOT IN (SELECT `id_customer` FROM `'._DB_PREFIX_.'customer`)');
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_template_customer` WHERE `id_customer` NOT IN (SELECT `id_customer` FROM `'._DB_PREFIX_.'customer`)');
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_facebook` WHERE `id_customer` != 0 AND `id_customer` NOT IN (SELECT `id_customer` FROM `'._DB_PREFIX_.'customer`)');
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_product` WHERE `id_product` NOT IN (SELECT `id_product` FROM `'._DB_PREFIX_.'product`)');
    Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_sponsorship_detail` WHERE `id_reward` NOT IN (SELECT `id_reward` FROM `'._DB_PREFIX_.'rewards`)');

    /* new hook */
    $object->registerHook('actionObjectOrderDetailAddAfter');
    $object->registerHook('actionObjectOrderDetailUpdateAfter');
    $object->registerHook('actionObjectOrderDetailDeleteAfter');
    $object->registerHook('actionObjectCustomerDeleteAfter');
    $object->registerHook('actionObjectProductDeleteAfter');
    $object->registerHook('displayLeftColumnProduct');

    /* new options */
    Configuration::updateValue('REWARDS_ALL_CATEGORIES', 0);
    Configuration::updateValue('REWARDS_VOUCHER_TAX', 1);
    Configuration::updateValue('RLOYALTY_ALL_CATEGORIES', 0);
    Configuration::updateValue('RLOYALTY_TAX', 1);
    Configuration::updateValue('RSPONSORSHIP_ALL_CATEGORIES', 0);
    Configuration::updateValue('RSPONSORSHIP_TAX', 1);
    Configuration::updateValue('RFACEBOOK_ALL_CATEGORIES', 0);
    Configuration::updateValue('REWARDS_VIRTUAL', 0);
    Configuration::updateValue('RSPONSORSHIP_REAL_VOUCHER_GC', 0);
    Configuration::updateValue('RSPONSORSHIP_PRODUCT_SHARE', 0);
    Configuration::updateValue('RSPONSORSHIP_REWARD_REGISTRATION', 0);
    Configuration::updateValue('RSPONSORSHIP_REWARD_ORDER', (int)Configuration::get('RSPONSORSHIP_REWARD_S'));
    Configuration::updateValue('RFACEBOOK_HIDE_BLOCK', 0);
    Configuration::deleteByName('RSPONSORSHIP_REWARD_S');
    Configuration::deleteByName('RSPONSORSHIP_OPEN_INVITER');
    Configuration::deleteByName('RSPONSORSHIP_OPEN_INVITER_LOGIN');
    Configuration::deleteByName('RSPONSORSHIP_OPEN_INVITER_KEY');

    $idEn = (int)Language::getIdByIso('en');
    $reward_virtual_name = array();
    foreach (Language::getLanguages() as $language) {
        $tmp = $object->l2('points', (int)$language['id_lang'], 'install-3.0.0');
        $reward_virtual_name[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $object->l('points', $idEn);
    }
    Configuration::updateValue('REWARDS_VIRTUAL_NAME', $reward_virtual_name);

    $templates = RewardsTemplateModel::getList('core');
    foreach (Currency::getCurrencies() as $currency) {
        Configuration::updateValue('REWARDS_VIRTUAL_VALUE_'.$currency['id_currency'], 0);
        Configuration::updateValue('REWARDS_VOUCHER_MIN_ORDER_'.$currency['id_currency'], Configuration::get('REWARDS_MINIMAL'));
        if (is_array($templates)) {
            foreach($templates as $template)
                MyConf::updateValue('REWARDS_VOUCHER_MIN_ORDER_'.$currency['id_currency'], (float)Configuration::get('REWARDS_MINIMAL'), null, $template['id_template']);
        }
    }
    Configuration::deleteByName('REWARDS_MINIMAL');

    /* create an invisible tab so we can call an admin controller to manage the sponsor autocomplete field in the customer page */
    $tab = new Tab();
    $tab->active = 1;
    $tab->class_name = "AdminSponsor";
    $tab->name = array();
    foreach (Language::getLanguages(true) as $lang)
        $tab->name[$lang['id_lang']] = 'AllinoneRewards Sponsor';
    $tab->id_parent = -1;
    $tab->module = $object->name;
    if (!$tab->add())
        $result = false;

	/* new version */
	Configuration::updateValue('REWARDS_VERSION', $object->version);

	/* clear cache */
	if (version_compare(_PS_VERSION_, '1.5.5.0', '>='))
		Tools::clearSmartyCache();

	return $result;
}