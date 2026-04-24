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

function upgrade_module_1_8_1($object)
{
	$result = true;

	/* forgotten column in the installation script */
	try {
		@Db::getInstance()->Execute('ALTER TABLE `' . _DB_PREFIX_ . 'rewards` ADD `id_payment` INT( 10 ) UNSIGNED NULL DEFAULT NULL AFTER `id_cart_rule`');
	} catch (Exception $e) {}

	/* new version */
	Configuration::updateValue('REWARDS_VERSION', $object->version);

	return $result;
}