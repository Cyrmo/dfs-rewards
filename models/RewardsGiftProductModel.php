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

class RewardsGiftProductModel extends ObjectModel
{
	public $id_product;
	public $gift_allowed;
	private static $_cache = array();

	public static $definition = array(
		'table' => 'rewards_gift_product',
		'primary' => 'id_product',
		'fields' => array(
			'id_product'		=>	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'gift_allowed'		=>	array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
		)
	);

	static public function deleteCustomization($id_product)
	{
		Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'rewards_gift_product` WHERE id_product='.(int)$id_product);
		Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'rewards_gift_product_attribute` WHERE id_product='.(int)$id_product);
	}

	static public function getGiftsProducts($id_lang, $p, $n, $order_by = null, $order_way = null, $get_total = false)
	{
		$context = Context::getContext();
        $id_template = (int)MyConf::getIdTemplate('core', $context->customer->id);
        $all_categories = (int)MyConf::get('REWARDS_GIFT_ALL_CATEGORIES', null, $id_template);
        $gift_categories = MyConf::get('REWARDS_GIFT_CATEGORIES', null, $id_template);

        $where = '
            WHERE product_shop.`customizable` = 0
            AND product_shop.`minimal_quantity` = 1
            AND product_shop.`active` = 1
            AND product_shop.price > 0
            AND 0 = (
                SELECT count(*)
                FROM `'._DB_PREFIX_.'rewards_gift_product` rgp
                WHERE rgp.`id_product` = product_shop.`id_product`
                AND `gift_allowed` = 0
            )
            AND (
                0 != (
                    SELECT count(*)
                    FROM `'._DB_PREFIX_.'rewards_gift_product` rgp
                    WHERE rgp.`id_product` = product_shop.`id_product`
                    AND `gift_allowed` = 1
                )'.
                ($all_categories == 1 ? '
                OR TRUE' : ($all_categories == 0 ? '
                OR product_shop.`id_product` IN (
                    SELECT `id_product`
                    FROM `'._DB_PREFIX_.'category_product`
                    WHERE `id_category` IN (-1,'.ltrim($gift_categories, ',').'))' : '')
                ).'
            )
        ';

        if ($get_total) {
            $sql = 'SELECT COUNT(p.`id_product`) AS total
                    FROM `'._DB_PREFIX_.'product` p'.
                    Shop::addSqlAssociation('product', 'p').
                    $where;
            return (int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        }

        if ($p < 1)
            $p = 1;

        /** Tools::strtolower is a fix for all modules which are now using lowercase values for 'orderBy' parameter */
        $order_by  = Validate::isOrderBy($order_by)   ? Tools::strtolower($order_by)  : 'orderprice';
        $order_way = Validate::isOrderWay($order_way) ? Tools::strtoupper($order_way) : 'ASC';

        $order_by_prefix = false;
        if ($order_by == 'id_product' || $order_by == 'date_add' || $order_by == 'date_upd') {
            $order_by_prefix = 'p';
        } elseif ($order_by == 'name') {
            $order_by_prefix = 'pl';
        } elseif ($order_by == 'position' || $order_by == 'price') {
            $order_by = 'orderprice';
        }

        $nb_days_new_product = Configuration::get('PS_NB_DAYS_NEW_PRODUCT');
        if (!Validate::isUnsignedInt($nb_days_new_product))
            $nb_days_new_product = 20;

        if (version_compare(_PS_VERSION_, '1.6.1.4', '>=')) {
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) AS quantity'.(Combination::isFeatureActive() ? ', IFNULL(product_attribute_shop.id_product_attribute, 0) AS id_product_attribute,
                        product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity' : '').', pl.`description`, pl.`description_short`, pl.`available_now`,
                        pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, image_shop.`id_image` id_image,
                        il.`legend` as legend, m.`name` AS manufacturer_name, cl.`name` AS category_default,
                        DATEDIFF(product_shop.`date_add`, DATE_SUB("'.date('Y-m-d').' 00:00:00",
                        INTERVAL '.(int)$nb_days_new_product.' DAY)) > 0 AS new, product_shop.price AS orderprice
                    FROM `'._DB_PREFIX_.'product` p
                    '.Shop::addSqlAssociation('product', 'p').
                    (Combination::isFeatureActive() ? ' LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop
                    ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int)$context->shop->id.')':'').'
                    '.Product::sqlStock('p', 0).'
                    LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
                        ON (product_shop.`id_category_default` = cl.`id_category`
                        AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')
                    LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
                        ON (p.`id_product` = pl.`id_product`
                        AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').')
                    LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
                        ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int)$context->shop->id.')
                    LEFT JOIN `'._DB_PREFIX_.'image_lang` il
                        ON (image_shop.`id_image` = il.`id_image`
                        AND il.`id_lang` = '.(int)$id_lang.')
                    LEFT JOIN `'._DB_PREFIX_.'manufacturer` m
                        ON m.`id_manufacturer` = p.`id_manufacturer`'.
                    $where.'
                    ORDER BY '.(!empty($order_by_prefix) ? $order_by_prefix.'.' : '').'`'.bqSQL($order_by).'` '.pSQL($order_way).'
                    LIMIT '.(((int)$p - 1) * (int)$n).','.(int)$n;
        } else {
            $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(product_attribute_shop.id_product_attribute) id_product_attribute, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
                        pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(image_shop.`id_image`) id_image,
                        il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
                        DATEDIFF(product_shop.`date_add`, DATE_SUB(NOW(),
                        INTERVAL '.(int)$nb_days_new_product.' DAY)) > 0 AS new, product_shop.price AS orderprice
                    FROM `'._DB_PREFIX_.'product` p
                    '.Shop::addSqlAssociation('product', 'p').'
                    LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                    ON (p.`id_product` = pa.`id_product`)
                    '.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
                    '.Product::sqlStock('p', 'product_attribute_shop', false, $context->shop).'
                    LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
                        ON (product_shop.`id_category_default` = cl.`id_category`
                        AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').')
                    LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
                        ON (p.`id_product` = pl.`id_product`
                        AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').')
                    LEFT JOIN `'._DB_PREFIX_.'image` i
                        ON (i.`id_product` = p.`id_product`)'.
                    Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
                    LEFT JOIN `'._DB_PREFIX_.'image_lang` il
                        ON (image_shop.`id_image` = il.`id_image`
                        AND il.`id_lang` = '.(int)$id_lang.')
                    LEFT JOIN `'._DB_PREFIX_.'manufacturer` m
                        ON m.`id_manufacturer` = p.`id_manufacturer`'.
                    $where.'
                    GROUP BY product_shop.id_product
                    ORDER BY '.(!empty($order_by_prefix) ? $order_by_prefix.'.' : '').'`'.bqSQL($order_by).'` '.pSQL($order_way).'
                    LIMIT '.(((int)$p - 1) * (int)$n).','.(int)$n;
        }

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!$result)
            return array();

        if ($order_by == 'orderprice')
            Tools::orderbyPrice($result, $order_way);

        return Product::getProductsProperties($id_lang, $result);
	}
}