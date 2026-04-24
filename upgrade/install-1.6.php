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

function upgrade_module_1_6($object)
{
	$result = true;

	/* change the id_order_state to TEXT field to manage multiple values */
	$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'rewards_state` DROP INDEX index_rewards_state_order_state';
	if (!Db::getInstance()->Execute($sql))
		$result = false;

	$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'rewards_state` CHANGE `id_order_state` `id_order_state` TEXT NULL DEFAULT NULL';
	if (!Db::getInstance()->Execute($sql))
		$result = false;

	/* new options */
	$groups_config = '';
	$groups = Group::getGroups((int)(Configuration::get('PS_LANG_DEFAULT')));
	foreach ($groups AS $group)
		$groups_config .= (int)$group['id_group'].',';
	$groups_config = rtrim($groups_config, ',');
	Configuration::updateValue('RLOYALTY_GROUPS', $groups_config);

	$category_config = '';
	$categories = Category::getSimpleCategories((int)(Configuration::get('PS_LANG_DEFAULT')));
	foreach ($categories AS $category)
		$category_config .= (int)$category['id_category'].',';
	$category_config = rtrim($category_config, ',');
	Configuration::updateValue('RLOYALTY_CATEGORIES', $category_config);
	Configuration::updateValue('RSPONSORSHIP_CATEGORIES_GC', $category_config);

	Configuration::updateValue('REWARDS_VOUCHER_DURATION', 365);
	Configuration::updateValue('REWARDS_VOUCHER_BEHAVIOR', 1);
	Configuration::updateValue('REWARDS_VOUCHER_PREFIX', 'FID');
	Configuration::updateValue('RSPONSORSHIP_REDIRECT', 'home');
	Configuration::updateValue('RSPONSORSHIP_VOUCHER_DURATION_GC', 365);
	Configuration::updateValue('RSPONSORSHIP_VOUCHER_PREFIX_GC', 'REWARD');
	Configuration::updateValue('REWARDS_VOUCHER_PREFIX', 'FID');

	/* new version */
	Configuration::updateValue('REWARDS_VERSION', $object->version);

	return $result;
}