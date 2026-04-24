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

function upgrade_module_1_9($object)
{
	$result = true;

	try {
		// create table for rewards account (for reminder now, but + later)
		if (!@Db::getInstance()->Execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'rewards_account` (
				`id_customer` INT UNSIGNED NOT NULL,
				`date_last_remind` DATETIME DEFAULT NULL,
				`date_add` DATETIME NOT NULL,
				`date_upd` DATETIME NOT NULL,
				PRIMARY KEY (`id_customer`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;')) {
			$result = false;
		} else {
			// initialization of the rewards_account table with all customers who already got rewards
			if (!@Db::getInstance()->Execute('INSERT IGNORE INTO `'._DB_PREFIX_.'rewards_account` (id_customer, date_last_remind, date_add, date_upd) SELECT DISTINCT id_customer, NULL, date_add, NOW() FROM `'._DB_PREFIX_.'rewards` GROUP BY id_customer ORDER BY date_add ASC'))
				$result = false;
		}

		/* Add column 'plugin' */
		if (!@Db::getInstance()->Execute('ALTER TABLE `' . _DB_PREFIX_ . 'rewards` ADD `reason` VARCHAR(80) NULL DEFAULT NULL AFTER `plugin`'))
			$result = false;
	} catch (Exception $e) {}

	// new options
	Configuration::updateValue('REWARDS_USE_CRON', 0);
	Configuration::updateValue('REWARDS_CRON_SECURE_KEY', Tools::strtoupper(Tools::passwdGen(16)));
	Configuration::updateValue('REWARDS_REMINDER', 0);
	Configuration::updateValue('REWARDS_REMINDER_MINIMUM', 5);
	Configuration::updateValue('REWARDS_REMINDER_FREQUENCY', 30);

	// new hook managed for the module myaccountblockfooter
	$object->registerHook('displayMyAccountBlockFooter');

	if (version_compare(_PS_VERSION_, '1.5.5.0', '>='))
		Tools::clearSmartyCache();

	/* new version */
	Configuration::updateValue('REWARDS_VERSION', $object->version);

	return $result;
}