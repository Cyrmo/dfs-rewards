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

class RewardsProductModel extends ObjectModel
{
	public $id_product;
	public $type;
	public $value;
	public $date_from;
	public $date_to;
	public $plugin;
	public $level;
	private static $_cache = array();

	public static $definition = array(
		'table' => 'rewards_product',
		'primary' => 'id_reward_product',
		'fields' => array(
			'id_product' 	=>	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'type' 			=>	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
			'value' 		=>	array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
			'date_from' 	=>	array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
			'date_to' 		=>	array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
			'plugin' 		=>	array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 20),
			'level' 		=>	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
		)
	);

	public function validateDates()
	{
		$where = ' AND ';
		if (!$this->date_from && !$this->date_to)
			$where .= '1=1';
		else if (!$this->date_from)
			$where .= '(date_from = 0 OR date_from IS NULL OR date_from <= \''.pSQL($this->date_to).'\')';
		else if (!$this->date_to)
			$where .= '(date_to = 0 OR date_to IS NULL OR date_to >= \''.pSQL($this->date_from).'\')';
		else
			$where .= '((\''.pSQL($this->date_from).'\' >= date_from AND (\''.pSQL($this->date_from).'\' <= date_to OR date_to=0 OR date_to IS NULL))
					OR (\''.pSQL($this->date_to).'\' >= date_from AND (\''.pSQL($this->date_to).'\' <= date_to OR date_to=0 OR date_to IS NULL)))';

		$query = '
				SELECT 1 FROM `'._DB_PREFIX_.'rewards_product`
				WHERE id_product='.(int)$this->id_product.'
				AND level='.(int)$this->level.'
				AND plugin=\''.pSQL($this->plugin).'\''.$where.
				($this->id ? ' AND id_reward_product != '.(int)$this->id : '');
		$row = Db::getInstance()->getRow($query);
		if ($row)
			return false;
		return true;
	}

	static public function isProductRewarded($id_product, $id_template, $plugin, $level=1)
	{
		if ($plugin == 'loyalty') {
			$default_value = (float)MyConf::get('RLOYALTY_DEFAULT_PRODUCT_REWARD', null, $id_template);
			$default_type = (int)MyConf::get('RLOYALTY_DEFAULT_PRODUCT_TYPE', null, $id_template);
		} else {
			$default_value = explode(',', MyConf::get('RSPONSORSHIP_DEF_PRODUCT_REWARD', null, $id_template));
			$default_type = explode(',', MyConf::get('RSPONSORSHIP_DEF_PRODUCT_TYPE', null, $id_template));
			$default_value = isset($default_value[$level-1]) ? (float)$default_value[$level-1] : 0;
			$default_type = isset($default_type[$level-1]) ? (int)$default_type[$level-1] : 0;
		}

		if (!isset(self::$_cache[$plugin.'_'.$level.'_'.$id_product])) {
			self::$_cache[$plugin.'_'.$level.'_'.$id_product] = array();

			$row = Db::getInstance()->getRow('
				SELECT id_product, type, value FROM `'._DB_PREFIX_.'rewards_product`
				WHERE id_product='.(int)$id_product.'
				AND plugin=\''.pSQL($plugin).'\'
				AND level='.(int)$level.'
				AND (date_from=0 OR date_from IS NULL OR date_from < NOW())
				AND (date_to=0 OR date_to IS NULL OR date_to > NOW())
			');
			if ($row)
				self::$_cache[$plugin.'_'.$level.'_'.$id_product] = $row;
			else {
				self::$_cache[$plugin.'_'.$level.'_'.$id_product] = array(
					'id_product' => $id_product,
					'type' => $default_type,
					'value' => $default_value,
				);
			}
		}
		return self::$_cache[$plugin.'_'.$level.'_'.$id_product]['value'] > 0;
	}

	// renvoie la récompense attribuée pour ce produit dans la devise du panier
	static public function getProductReward($id_product, $price, $quantity, $id_currency, $id_template, $plugin, $level=1)
	{
		if (self::isProductRewarded($id_product, $id_template, $plugin, $level)) {
			$multiplier = 1;
			if ($plugin == 'loyalty')
				$multiplier = (float)MyConf::get('RLOYALTY_MULTIPLIER', null, $id_template);
			if (self::$_cache[$plugin.'_'.$level.'_'.$id_product]['type']==0)
				return round($price * $multiplier * self::$_cache[$plugin.'_'.$level.'_'.$id_product]['value'] / 100, 2) * $quantity;
			else
				return RewardsModel::getCurrencyValue($quantity * $multiplier * self::$_cache[$plugin.'_'.$level.'_'.$id_product]['value'], $id_currency);
		}
		return 0;
	}

	static public function getProductRewardsList($id_product, $plugin)
	{
		$query = 'SELECT *
				FROM `'._DB_PREFIX_.'rewards_product`
				WHERE `id_product`='.(int)$id_product.'
				AND `plugin`=\''.pSQL($plugin).'\'
				ORDER BY level, date_from ASC';
		return Db::getInstance()->executeS($query);
	}
}
