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

function upgrade_module_2_0_0($object)
{
	$result = true;

	try {
		if (!@Db::getInstance()->Execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_template` (
				`id_template` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`name` varchar(100) NOT NULL,
				`plugin` VARCHAR(20) NOT NULL,
				PRIMARY KEY (`id_template`),
				UNIQUE KEY `index_unique_rewards_template` (`name`, `plugin`),
	  			INDEX `index_rewards_template_plugin` (`plugin`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;')) {
			$result = false;
		}

		if (!@Db::getInstance()->Execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_template_config` (
				`id_template_config` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`id_template` INT UNSIGNED NOT NULL,
				`name` varchar(254) NOT NULL,
				`value` TEXT,
				PRIMARY KEY (`id_template_config`),
				UNIQUE KEY `index_unique_rewards_template_config` (`id_template`, `name`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;')) {
			$result = false;
		}

		if (!@Db::getInstance()->Execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_template_config_lang` (
				`id_template_config` INT UNSIGNED NOT NULL,
				`id_lang` INT UNSIGNED NOT NULL,
				`value` TEXT,
				PRIMARY KEY (`id_template_config`, `id_lang`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;')) {
			$result = false;
		}

		if (!@Db::getInstance()->Execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_template_customer` (
				`id_template` INT UNSIGNED NOT NULL,
				`id_customer` INT UNSIGNED NOT NULL,
				PRIMARY KEY (`id_template`, `id_customer`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;')) {
			$result = false;
		}

		if (!@Db::getInstance()->Execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_product` (
				`id_reward_product` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`id_product` INT UNSIGNED NOT NULL,
				`id_product_attribute` INT UNSIGNED NOT NULL,
				`type` INT UNSIGNED NOT NULL DEFAULT 0,
				`value` INT UNSIGNED NOT NULL DEFAULT 0,
				`date_from` DATETIME,
				`date_to` DATETIME,
				PRIMARY KEY (`id_reward_product`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;')) {
			$result = false;
		}

		if (!@Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'rewards_sponsorship` ADD `date_end` DATETIME DEFAULT \'0000-00-00 00:00:00\' AFTER `id_cart_rule`')) {
			$result = false;
		}
	} catch (Exception $e) {
		$result = false;
	}

	/* create an invisible tab so we can call an admin controller to manage the product rewards in the product page */
	$tab = new Tab();
	$tab->active = 1;
	$tab->class_name = "AdminProductReward";
	$tab->name = array();
	foreach (Language::getLanguages(true) as $lang)
		$tab->name[$lang['id_lang']] = 'AllinoneRewards Product Reward';
	$tab->id_parent = -1;
	$tab->module = $object->name;
	if (!$tab->add())
		$result = false;

	/* quick access */
	$qa = new QuickAccess();
	foreach (Language::getLanguages() AS $language)
		$qa->name[(int)$language['id_lang']] = "All-in-one Rewards";
	$qa->link = "index.php?controller=AdminModules&configure=allinone_rewards&tab_module=&module_name=allinone_rewards";
	$qa->new_window = 0;
	$qa->save();

	/* new option */
	Configuration::updateValue('RLOYALTY_TYPE', 0);
	Configuration::updateValue('RLOYALTY_PERCENTAGE', 5);
	Configuration::updateValue('RLOYALTY_DEFAULT_PRODUCT_REWARD', 0);
	Configuration::updateValue('RLOYALTY_DEFAULT_PRODUCT_TYPE', 0);
	Configuration::updateValue('RLOYALTY_MULTIPLIER', 1);
	Configuration::updateValue('RSPONSORSHIP_DURATION', 0);
	Configuration::updateValue('REWARDS_DURATION', 0);

	/* new hook */
	$object->registerHook('displayAdminProductsExtra');
	$object->registerHook('ActionAdminControllerSetMedia');

	/* new version */
	Configuration::updateValue('REWARDS_VERSION', $object->version);

	/* clear cache */
	if (version_compare(_PS_VERSION_, '1.5.5.0', '>='))
		Tools::clearSmartyCache();

	return $result;
}