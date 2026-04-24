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

function upgrade_module_4_1_0($object)
{
	$result = true;

	/* new hooks */
	if (version_compare(_PS_VERSION_, '1.7', '>=')) {
		$object->registerHook('additionalCustomerFormFields');
		$object->registerHook('displayProductAdditionalInfo');
		$object->registerHook('displayNav1');
		$object->registerHook('displayNav2');
	}
	if (version_compare(_PS_VERSION_, '1.6.0.12', '>='))
		$object->registerHook('displayNav');

	/* new version */
	Configuration::updateValue('REWARDS_VERSION', $object->version);

	/* clear cache */
	if (version_compare(_PS_VERSION_, '1.5.5.0', '>='))
		Tools::clearSmartyCache();

	return $result;
}