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

function upgrade_module_4_1_6($object)
{
	$result = true;

	if (version_compare(_PS_VERSION_, '1.7', '>='))
		$object->registerHook('displayBeforeBodyClosingTag');

	/* new option */
	Configuration::updateValue('REWARDS_MAILS_IGNORED', '@marketplace.amazon,@alerts-shopping-flux');

	/* new version */
	Configuration::updateValue('REWARDS_VERSION', $object->version);

	/* clear cache */
	if (version_compare(_PS_VERSION_, '1.5.5.0', '>='))
		Tools::clearSmartyCache();

	return $result;
}