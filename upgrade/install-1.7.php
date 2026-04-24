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

function upgrade_module_1_7($object)
{
	$result = true;

	// Manual call to the upgrade function if upgrading from a version made for presta 1.4 and >= 1.7
	if (version_compare(Configuration::get('REWARDS_VERSION'), '1.7', '>=')) {
		try {
			/* Change field name */
			$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'rewards` CHANGE `id_discount` `id_cart_rule` INT( 10 ) UNSIGNED NULL DEFAULT NULL';
			Db::getInstance()->Execute($sql);

			/* Change index name */
			$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'rewards`  DROP INDEX `index_rewards_discount`, ADD INDEX `index_rewards_cart_rule` (`id_cart_rule`)';
			Db::getInstance()->Execute($sql);

			$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'rewards_sponsorship`  CHANGE `id_discount` `id_cart_rule` INT( 10 ) UNSIGNED NULL DEFAULT NULL';
			Db::getInstance()->Execute($sql);
		} catch (Exception $e) {}
	} else {
		/* Change field name */
		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'rewards` CHANGE `id_discount` `id_cart_rule` INT( 10 ) UNSIGNED NULL DEFAULT NULL';
		if (!Db::getInstance()->Execute($sql))
			return false;

		/* Change index name */
		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'rewards`  DROP INDEX `index_rewards_discount`, ADD INDEX `index_rewards_cart_rule` (`id_cart_rule`)';
		if (!Db::getInstance()->Execute($sql))
			return false;

		/* Change field name */
		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'rewards_sponsorship`  CHANGE `id_discount` `id_cart_rule` INT( 10 ) UNSIGNED NULL DEFAULT NULL';
		if (!Db::getInstance()->Execute($sql))
			$result = false;

		/* Default quantity for sponsored friend voucher is 1 */
		Configuration::updateValue('RSPONSORSHIP_QUANTITY_GC', 1);
	}

	/* Behavior for partial use is now just yes or no, so we convert old value if we are updating from prestashop 1.4.x */
	/* default value is "yes" */
	if (((int)Configuration::get('REWARDS_VOUCHER_BEHAVIOR')) == 1)
		Configuration::updateValue('REWARDS_VOUCHER_BEHAVIOR', 0);
	else
		Configuration::updateValue('REWARDS_VOUCHER_BEHAVIOR', 1);

	/* Behavior for partial use is now just yes or no, so we convert old value if we are updating from prestashop 1.4.x */
	/* default value is "no" */
	if (((int)Configuration::get('RSPONSORSHIP_VOUCHER_BEHAVIOR')) == 2)
		Configuration::updateValue('RSPONSORSHIP_VOUCHER_BEHAVIOR', 1);
	else
		Configuration::updateValue('RSPONSORSHIP_VOUCHER_BEHAVIOR', 0);

	/* activate the cart rules */
	Configuration::updateGlobalValue('PS_CART_RULE_FEATURE_ACTIVE', '1');

	/* Default reward minimum is shipping excluded */
	Configuration::updateValue('REWARDS_MINIMAL_SHIPPING', 0);

	/* New option to manage free shipping for sponsored friend */
	if (((int)Configuration::get('RSPONSORSHIP_DISCOUNT_TYPE_GC')) == 3) {
		Configuration::updateValue('RSPONSORSHIP_FREESHIPPING_GC', 1);
		Configuration::updateValue('RSPONSORSHIP_DISCOUNT_TYPE_GC', 0);
	} else
		Configuration::updateValue('RSPONSORSHIP_FREESHIPPING_GC', 0);

	/* delete useless key */
	Configuration::deleteByName('REWARDS_VOUCHER_CUMUL_REDUC_S');
	Configuration::deleteByName('RSPONSORSHIP_CUMUL_REDUC_GC');

	/* replace the sponsorship.xml by configuration objects */
	if (file_exists(dirname(__FILE__).'/../sponsorship.xml')) {
		if ($xml = @simplexml_load_file(dirname(__FILE__).'/../sponsorship.xml','SimpleXMLElement',LIBXML_NOCDATA)) {
			$account_txt = array();
			$order_txt = array();
			$popup_txt = array();
			$rules_txt = array();
			// index of language in prestashop 1.4.x
			$index = array('en' => 1, 'fr' => 2, 'es' => 3, 'de' => 4, 'it' => 5);
			foreach (Language::getLanguages() AS $language) {
				$tmp = array_key_exists($language['iso_code'], $index) ? $index[$language['iso_code']] : $index['en'];
				$account_txt[(int)$language['id_lang']] = $xml->body->{'account_'.$tmp};
				$order_txt[(int)$language['id_lang']] = $xml->body->{'order_'.$tmp};
				$popup_txt[(int)$language['id_lang']] = $xml->body->{'popup_'.$tmp};
				$rules_txt[(int)$language['id_lang']] = $xml->body->{'rules_'.$tmp};
			}
			Configuration::updateValue('RSPONSORSHIP_ACCOUNT_TXT', $account_txt, true);
			Configuration::updateValue('RSPONSORSHIP_ORDER_TXT', $order_txt, true);
			Configuration::updateValue('RSPONSORSHIP_POPUP_TXT', $popup_txt, true);
			Configuration::updateValue('RSPONSORSHIP_RULES_TXT', $rules_txt, true);
		}
		@unlink(dirname(__FILE__).'/../sponsorship.xml');
	}

	/* new version */
	Configuration::updateValue('REWARDS_VERSION', $object->version);

	return $result;
}