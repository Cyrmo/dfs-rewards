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

require_once(dirname(__FILE__).'/RewardsModel.php');
require_once(dirname(__FILE__).'/RewardsStateModel.php');

class RewardsFacebookModel extends ObjectModel
{
	public $id_guest;
	public $id_customer;
	public $id_cart_rule;
	public $id_reward;
	public $ip_address;
	public $date_add;
	public $date_upd;

	public static $definition = array(
		'table' => 'rewards_facebook',
		'primary' => 'id_facebook',
		'fields' => array(
			'id_guest' =>			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'id_customer' =>		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'id_cart_rule' =>		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
			'id_reward' =>			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
			'ip_address' =>			array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
			'date_add' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
			'date_upd' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
		),
	);

	static public function isNotEmpty() {
		Db::getInstance()->ExecuteS('SELECT 1 FROM `'._DB_PREFIX_.'rewards_facebook`');
		return (bool)Db::getInstance()->NumRows();
	}

	static public function importFromFbpromote() {
		@Db::getInstance()->Execute('
			INSERT INTO `'._DB_PREFIX_.'rewards_facebook` (id_guest, id_customer, id_cart_rule, id_reward, ip_address, date_add, date_upd)
			SELECT id_guest, id_customer, id_discount, 0, ip_address, NOW(), NOW() FROM `'._DB_PREFIX_.'fb_promote`');
	}

	// Attention pour que id_guest soit rempli, le module "récupération des données statistiques" doit être activé
	// sinon seulement le 1er like sera récompensé, ensuite le plugin ne fonctionnera plus...
	// Il faut trouver une autre solution, et au minimum distinguer user avec id_customer de guest pour qu'au moins ça fonctionne avec les customers
	// pour les guests, peut-être ne tester finalement que l'IP ? Si pas d'IP, pas de bon
	public static function getAlreadyLiked() {
		$context = Context::getContext();

		// check if that guest already liked the page
		$sql = "SELECT id_facebook, id_cart_rule, id_reward FROM "._DB_PREFIX_."rewards_facebook WHERE id_guest=".(int)$context->cookie->id_guest;
		// if the user is not connected, try to find a "like" from the same IP, because he could have unliked from its facebook account
		// or removed his cookies before unlike, so we won't find it and will give him another reward...
		$ip_address = $_SERVER['REMOTE_ADDR'];
    	if (filter_var($ip_address, FILTER_VALIDATE_IP) !== false)
			$sql .= " OR ip_address='".$ip_address."'";
		return Db::getInstance()->getRow($sql);
	}

	public function like() {
		$context = Context::getContext();

		// send the mail to the admin
		if (Configuration::get('RFACEBOOK_MAIL_LIKE'))
			$this->_sendMail('Like');

		// try to get the values (id_cart_rule and id_reward) from a previous like
		$values = self::getAlreadyLiked();
		if ($values !== false)
			die();

		$ip_address = $_SERVER['REMOTE_ADDR'];

		// try to retrieve the id_customer
		$cart_rule = $reward = false;
		if ($context->customer->isLogged())
			$id_customer = (int)$context->customer->id;
		else
			$id_customer = (int)Db::getInstance()->getValue("SELECT id_customer FROM `"._DB_PREFIX_."guest` WHERE id_guest=".(int)$context->cookie->id_guest);

		if ($id_customer > 0 || Configuration::get('RFACEBOOK_GUEST')) {
			if ($id_customer > 0) {
				$reward = $this->_createReward();
			} else if (Configuration::get('RFACEBOOK_GUEST')) {
				if (filter_var($ip_address, FILTER_VALIDATE_IP) === false)
					die();
				$cart_rule = $this->_createDiscount();
			}
			$this->id_guest = (int)$context->cookie->id_guest;
			$this->id_customer = (int)$id_customer;
			$this->id_cart_rule = ($cart_rule == false ? 0 : $cart_rule->id);
			$this->id_reward = ($reward == false ? 0 : $reward->id);
			$this->ip_address = $ip_address;
			$this->save();

			die('{"result":"1"' . ($cart_rule != false ? ',"code":"'.$cart_rule->code.'"' : '') . '}');
		}
	}

	public function unlike() {
		// send the mail to the admin
		if (Configuration::get('RFACEBOOK_MAIL_UNLIKE'))
			$this->_sendMail('Unlike');

		// try to get the values (id_cart_rule and id_reward) from a previous like
		$values = self::getAlreadyLiked();
		if ($values === false)
			die();

		$bContinue = false;
		if ($values['id_cart_rule'] != 0) {
			Db::getInstance()->ExecuteS("SELECT 1 FROM `"._DB_PREFIX_."order_cart_rule` WHERE id_cart_rule=".(int)$values['id_cart_rule']);
			if (Db::getInstance()->NumRows() == 0) {
				$cart_rule = new CartRule((int)$values['id_cart_rule']);
				$cart_rule->delete();
				$bContinue = true;
			}
		} else {
			$reward = new RewardsModel((int)$values['id_reward']);
			if ($reward->id_reward_state == RewardsStateModel::getValidationId()) {
				//$rewards->delete();
				$reward->id_reward_state = RewardsStateModel::getCancelId();
				$reward->save();
				$bContinue = true;
			}
		}
		if ($bContinue) {
			$sql = "DELETE FROM `"._DB_PREFIX_."rewards_facebook` WHERE id_facebook=".(int)$values['id_facebook'];
			if ((bool)Db::getInstance()->Execute($sql))
				return true;
		}
		return false;
	}

	private function _createDiscount() {
		$context = Context::getContext();

		$configurations = Configuration::getMultiple(array(
			'RFACEBOOK_VOUCHER_TYPE',
			'RFACEBOOK_VOUCHER_FREESHIPPING',
			'RFACEBOOK_VOUCHER_CUMUL',
			'RFACEBOOK_VOUCHER_BEHAVIOR',
			'RFACEBOOK_VOUCHER_QUANTITY',
			'RFACEBOOK_VOUCHER_MIN_TAX',
			'RFACEBOOK_VOUCHER_PREFIX',
			'RFACEBOOK_VOUCHER_DURATION',
			'RFACEBOOK_VOUCHER_VALUE_'.(int)$context->currency->id,
			'RFACEBOOK_VOUCHER_MIN_VALUE_'.(int)$context->currency->id,
			'RFACEBOOK_ALL_CATEGORIES',
			'RFACEBOOK_VOUCHER_CATEGORIES'
		));

		/* Generate a discount code */
		$code = NULL;
		do $code = $configurations['RFACEBOOK_VOUCHER_PREFIX'].Tools::passwdGen(6);
		while (CartRule::cartRuleExists($code));

		/* Voucher creation */
		$cartRule = new CartRule();
		$cartRule->code = $code;
		$cartRule->active = 1;
		$cartRule->date_from = date('Y-m-d H:i:s', time());
		$cartRule->date_to = date('Y-m-d H:i:s', time() + (int)$configurations['RFACEBOOK_VOUCHER_DURATION']*24*60*60);
		$cartRule->description = Configuration::get('RFACEBOOK_VOUCHER_DETAILS', (int)$context->language->id);
		$cartRule->quantity = (int)$configurations['RFACEBOOK_VOUCHER_QUANTITY'];
		$cartRule->quantity_per_user = (int)$configurations['RFACEBOOK_VOUCHER_QUANTITY'];
		$cartRule->highlight = 0;
		$cartRule->partial_use = (int)$configurations['RFACEBOOK_VOUCHER_BEHAVIOR'];
		$cartRule->minimum_amount = (float)$configurations['RFACEBOOK_VOUCHER_MIN_VALUE_'.(int)$context->currency->id];
		$cartRule->minimum_amount_tax = (int)$configurations['RFACEBOOK_VOUCHER_MIN_TAX'];
		$cartRule->minimum_amount_currency = (int)$context->currency->id;
		$cartRule->minimum_amount_shipping = 0;
		$cartRule->cart_rule_restriction = (int)(!(bool)$configurations['RFACEBOOK_VOUCHER_CUMUL']);

		if ((int)$configurations['RFACEBOOK_VOUCHER_TYPE'] == 1) {
			$cartRule->reduction_percent = (float)$configurations['RFACEBOOK_VOUCHER_VALUE_'.(int)$context->currency->id];
		} elseif ((int)$configurations['RFACEBOOK_VOUCHER_TYPE'] == 2) {
			$cartRule->reduction_amount = (float)$configurations['RFACEBOOK_VOUCHER_VALUE_'.(int)$context->currency->id];
			$cartRule->reduction_currency = (int)$context->currency->id;
			$cartRule->reduction_tax = 1;
		}
		if ((int)$configurations['RFACEBOOK_VOUCHER_FREESHIPPING'] == 1) {
			$cartRule->free_shipping = 1;
		}

		$languages = Language::getLanguages(true);
		$default_text = Configuration::get('RFACEBOOK_VOUCHER_DETAILS', (int)Configuration::get('PS_LANG_DEFAULT'));
		foreach ($languages AS $language)
		{
			$text = Configuration::get('RFACEBOOK_VOUCHER_DETAILS', (int)$language['id_lang']);
			$cartRule->name[(int)$language['id_lang']] = $text ? $text : $default_text;
		}

		$categories = explode(',', $configurations['RFACEBOOK_VOUCHER_CATEGORIES']);
		if (!$configurations['RFACEBOOK_ALL_CATEGORIES'] && is_array($categories) && count($categories) > 0 && (int)$configurations['RFACEBOOK_VOUCHER_TYPE'] != 0) {
			$cartRule->product_restriction = 1;
			if ((int)$configurations['RFACEBOOK_VOUCHER_TYPE'] == 1)
				$cartRule->reduction_product = -2;
		}

		if ($cartRule->add()) {
			/* if this discount is only available for a list of categories */
			if ($cartRule->product_restriction) {
				/* cart must contain 1 product from 1 of the selected categories */
				Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'cart_rule_product_rule_group` (`id_cart_rule`, `quantity`) VALUES ('.(int)$cartRule->id.', 1)');
				$id_product_rule_group = Db::getInstance()->Insert_ID();

				/* create the category rule */
				Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'cart_rule_product_rule` (`id_product_rule_group`, `type`) VALUES ('.(int)$id_product_rule_group.', \'categories\')');
				$id_product_rule = Db::getInstance()->Insert_ID();

				/* insert the list of categories */
				$values = array();
				foreach($categories as $category)
					$values[] = '('.(int)$id_product_rule.','.(int)$category.')';
				$values = array_unique($values);
				if (count($values))
					Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'cart_rule_product_rule_value` (`id_product_rule`, `id_item`) VALUES '.implode(',', $values));
			}

			// If the discount has no cart rule restriction, then it must be added to the white list of the other cart rules that have restrictions
			if ((int)$configurations['RFACEBOOK_VOUCHER_CUMUL'])
			{
				Db::getInstance()->execute('
				INSERT INTO `'._DB_PREFIX_.'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`) (
					SELECT id_cart_rule, '.(int)$cartRule->id.' FROM `'._DB_PREFIX_.'cart_rule` WHERE cart_rule_restriction = 1
				)');
			}

			// try to add the discount to the cart
			/*$isValid = $context->cart->checkDiscountValidity($discount, $context->cart->getDiscounts(), $context->cart->getOrderTotal(), $context->cart->getProducts(), true);
			if ($isValid === false) {
				if (!$context->cart->id)
					$context->cart->add();
				$context->cart->addDiscount($discount->id);
			}*/

			return $cartRule;
		}
		return false;
	}

	private function _createReward() {
		$context = Context::getContext();

		$reward = new RewardsModel();
		$reward->id_customer = (int)$context->customer->id;
		$reward->credits = (float)Configuration::get('RFACEBOOK_REWARD_VALUE_'.(int)$context->currency->id);
		$reward->id_reward_state = RewardsStateModel::getValidationId();
		if (Configuration::get('REWARDS_DURATION'))
			$reward->date_end = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d') + Configuration::get('REWARDS_DURATION'), date('Y')));
		$reward->plugin = 'facebook';
		if ($reward->save())
			return $reward;
		return false;
	}

	private function _sendMail($action) {
		include_once(dirname(__FILE__).'/../allinone_rewards.php');

		// DO NOT REMOVE
		// $this->l('Like')
		// $this->l('Unlike')
		// $this->l('Someone liked your shop on Facebook')
		// $this->l('Someone unliked your shop on Facebook')
		// $this->l('Not identified')

		$context = Context::getContext();

		$module = new allinone_rewards();
		$title = ($action == 'Like' ? $module->l2('Someone liked your shop on Facebook', (int)Configuration::get('PS_LANG_DEFAULT'), Tools::strtolower(get_class($this))) : $module->l2('Someone unliked your shop on Facebook', (int)Configuration::get('PS_LANG_DEFAULT'), Tools::strtolower(get_class($this))));
		$data =  array(
			'{action}'		=> $module->l2($action, (int)Configuration::get('PS_LANG_DEFAULT'), Tools::strtolower(get_class($this))),
			'{title}'		=> $title,
			'{customer}'	=> $module->l2('Not identified', (int)Configuration::get('PS_LANG_DEFAULT'), Tools::strtolower(get_class($this)))
		);

		if ($context->customer->isLogged())
			$id_customer = (int)$context->customer->id;
		else
			$id_customer = (int)Db::getInstance()->getValue("SELECT id_customer FROM `"._DB_PREFIX_."guest` WHERE id_guest=".(int)$context->cookie->id_guest);

		if ($id_customer > 0) {
			$customer = new Customer($id_customer);
			$data['{customer}'] = $customer->firstname . ' ' . $customer->lastname . ' (ID : ' . $id_customer . ')';
		}
		$module->sendMail((int)Configuration::get('PS_LANG_DEFAULT'), 'facebook-admin', $title, $data, Configuration::get('PS_SHOP_EMAIL'), NULL);
	}
}