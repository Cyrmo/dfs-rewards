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

class RewardsGiftProductAttributeModel extends ObjectModel
{
	public $id_product;
	public $id_product_attribute;
	public $purchase_allowed = 0;
	private static $_cache = array();

	public static $definition = array(
		'table' => 'rewards_gift_product_attribute',
		'primary' => 'id_reward_gift_product_attribute',
		'fields' => array(
			'id_product' 			=>	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'id_product_attribute' 	=>	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'purchase_allowed'		=>	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
		)
	);

	static private function _loadCache($id_product, $id_product_attribute)
	{
		if (!isset(self::$_cache[$id_product.'_'.$id_product_attribute])) {
			self::$_cache[$id_product.'_'.$id_product_attribute] = array();

			$row = Db::getInstance()->getRow('
				SELECT gift_allowed, id_product_attribute, COALESCE(purchase_allowed, 1) AS purchase_allowed
				FROM `'._DB_PREFIX_.'rewards_gift_product` AS rgp
				LEFT JOIN `'._DB_PREFIX_.'rewards_gift_product_attribute` AS rgpa ON (rgp.id_product=rgpa.id_product AND rgpa.id_product_attribute='.(int)$id_product_attribute.')
				WHERE rgp.id_product='.(int)$id_product
			);
			if ($row) {
				self::$_cache[$id_product.'_'.$id_product_attribute] = array(
					'gift_allowed' => !$row['gift_allowed'] ? 0 : (isset($row['id_product_attribute']) ? 1 : 0),
					'purchase_allowed' => (int)$row['purchase_allowed'],
				);
			} else {
				self::$_cache[$id_product.'_'.$id_product_attribute] = array(
					'gift_allowed' => -1,
					'purchase_allowed' => -1,
				);
			}
		}
	}

	static public function getGiftProductAttributeAllowed($id_product, $id_product_attribute)
	{
		if (!isset(self::$_cache[$id_product.'_'.$id_product_attribute]))
			self::_loadCache($id_product, $id_product_attribute);
		return self::$_cache[$id_product.'_'.$id_product_attribute]['gift_allowed'];
	}

	static public function getGiftProductAttributePurchaseAllowed($id_product, $id_product_attribute)
	{
		if (!isset(self::$_cache[$id_product.'_'.$id_product_attribute]))
			self::_loadCache($id_product, $id_product_attribute);
		return self::$_cache[$id_product.'_'.$id_product_attribute]['purchase_allowed'];
	}

	static public function getGiftProductAttribute($id_product, $id_product_attribute)
	{
		if (!isset(self::$_cache[$id_product.'_'.$id_product_attribute]))
			self::_loadCache($id_product, $id_product_attribute);
		return self::$_cache[$id_product.'_'.$id_product_attribute];
	}
}