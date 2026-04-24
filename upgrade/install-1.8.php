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

function upgrade_module_1_8($object)
{
	$result = true;

	/* Remove autoincrement from rewards_state */
	if (!Db::getInstance()->Execute('ALTER TABLE `' . _DB_PREFIX_ . 'rewards_state` CHANGE `id_reward_state` `id_reward_state` INT( 10 ) UNSIGNED NOT NULL'))
		$result = false;
	if (!Db::getInstance()->Execute('ALTER TABLE `' . _DB_PREFIX_ . 'rewards_state_lang` CHANGE `id_reward_state` `id_reward_state` INT( 10 ) UNSIGNED NOT NULL'))
		$result = false;

	/* delete the states created because of autoincrement */
	if (!Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_state_lang` WHERE id_reward_state > 6'))
		$result = false;
	if (!Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'rewards_state` WHERE id_reward_state > 6'))
		$result = false;
	/* add the new states for payment */
	RewardsStateModel::insertDefaultData();

	/* Add column 'id_payment' */
	if (!Db::getInstance()->Execute('ALTER TABLE `' . _DB_PREFIX_ . 'rewards` ADD `id_payment` INT( 10 ) UNSIGNED NULL DEFAULT NULL AFTER `id_cart_rule`'))
		$result = false;

	/* Add column 'plugin' */
	if (!Db::getInstance()->Execute('ALTER TABLE `' . _DB_PREFIX_ . 'rewards` ADD `plugin` VARCHAR( 20 ) NOT NULL DEFAULT \'loyalty\' AFTER `credits`'))
		$result = false;

	/* Init column 'plugin' */
	if (!Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'rewards` SET `plugin`=\'sponsorship\' WHERE `id_sponsorship` != 0'))
		$result = false;

	/* Create table for details about sponsorship rewards */
	if (!Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_sponsorship_detail` (
			`id_reward` INT UNSIGNED NOT NULL,
			`id_sponsorship` INT UNSIGNED DEFAULT \'0\',
			`level_sponsorship` INT UNSIGNED DEFAULT \'0\',
			PRIMARY KEY (`id_reward`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;'))
		$result = false;

	/* Init rewards_sponsorship_detail */
	if (!Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'rewards_sponsorship_detail` SELECT `id_reward`, `id_sponsorship`, `level_sponsorship` FROM `'._DB_PREFIX_.'rewards` WHERE `id_sponsorship` > 0'))
		$result = false;

	/* Delete unused columns */
	if (!Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'rewards` DROP `id_sponsorship`, DROP `level_sponsorship`'))
		$result = false;

	/* Create table for rewards payments */
	if (!Db::getInstance()->Execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_payment` (
			`id_payment` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`credits` DECIMAL(20,2) NOT NULL DEFAULT \'0.00\',
			`detail` TEXT,
			`invoice` VARCHAR(100) DEFAULT NULL,
			`paid` TINYINT(1) NOT NULL DEFAULT \'0\',
			`date_add` DATETIME NOT NULL,
			`date_upd` DATETIME NOT NULL,
			PRIMARY KEY (`id_payment`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;'))
		$result = false;

	/* Install facebook plugin */
	if (!$object->facebook->install())
		$result = false;

	/* new option for rewards */
	Configuration::updateValue('REWARDS_PAYMENT', 0);
	Configuration::updateValue('REWARDS_PAYMENT_INVOICE',  1);
	Configuration::updateValue('REWARDS_PAYMENT_RATIO',  100);
	Configuration::updateValue('REWARDS_VOUCHER', 1);

	$groups_config = '';
	$groups = Group::getGroups((int)Configuration::get('PS_LANG_DEFAULT'));
	foreach ($groups AS $group)
		$groups_config .= (int)$group['id_group'].',';
	$groups_config = rtrim($groups_config, ',');
	Configuration::updateValue('REWARDS_VOUCHER_GROUPS', $groups_config);

	foreach (Currency::getCurrencies() as $currency) {
		Configuration::updateValue('REWARDS_PAYMENT_MIN_VALUE_'.(int)($currency['id_currency']), 0);
		Configuration::updateValue('REWARDS_VOUCHER_MIN_VALUE_'.(int)($currency['id_currency']), 0);
	}

	$idEn = Language::getIdByIso('en');
	$rewards_payment_txt = array();
	foreach (Language::getLanguages() AS $language) {
		$tmp = $object->l2('rewards_payment_txt', (int)$language['id_lang']);
		$rewards_payment_txt[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $object->l2('rewards_payment_txt', $idEn);
	}
	Configuration::updateValue('REWARDS_PAYMENT_TXT', $rewards_payment_txt);

	/* delete hotmail + live fromOpenInviter */
	@unlink(dirname(__FILE__).'/../OpenInviter/plugins/hotmail.plg.php');
	@unlink(dirname(__FILE__).'/../OpenInviter/plugins/livejournal.plg.php');
	@unlink(dirname(__FILE__).'/../libraries/OpenInviter/plugins/hotmail.plg.php');
	@unlink(dirname(__FILE__).'/../libraries/OpenInviter/plugins/livejournal.plg.php');

	/* new version */
	Configuration::updateValue('REWARDS_VERSION', $object->version);

	return $result;
}