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

class RewardsSponsorshipModel extends ObjectModel
{
	public $id_sponsor;
	public $channel;
	public $email;
	public $lastname;
	public $firstname;
	public $id_customer;
	public $id_cart_rule;
	public $date_end = 0;
	public $date_add;
	public $date_upd;

	public static $definition = array(
		'table' => 'rewards_sponsorship',
		'primary' => 'id_sponsorship',
		'fields' => array(
			'id_sponsor' =>			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'channel' =>			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
			'email' =>				array('type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 255),
			'lastname' =>			array('type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 128),
			'firstname' =>			array('type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 128),
			'id_customer' =>		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
			'id_cart_rule' =>		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
			'date_end' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
			'date_add' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
			'date_upd' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
		),
	);

	static public function isNotEmpty() {
		Db::getInstance()->ExecuteS('SELECT 1 FROM `'._DB_PREFIX_.'rewards_sponsorship`');
		return (bool)Db::getInstance()->NumRows();
	}

	static public function importFromReferralProgram($bAdvanced=false) {
		@Db::getInstance()->Execute('
			INSERT INTO `'._DB_PREFIX_.'rewards_sponsorship` (id_sponsor, channel, email, lastname, firstname, id_customer, id_cart_rule, date_add, date_upd)
			SELECT id_sponsor, 1, email, lastname, firstname, id_customer, '.($bAdvanced ? 'id_discount':'id_cart_rule').', date_add, date_upd FROM `'._DB_PREFIX_.($bAdvanced ? 'adv':'').'referralprogram`');
	}

	public function registerDiscount($id_currency)
	{
		$context = Context::getContext();
		$id_template = (int)MyConf::getIdTemplate('sponsorship', $this->id_sponsor);

		if (MyConf::get('RSPONSORSHIP_REAL_VOUCHER_GC', null, $id_template)) {
			$id_cart_rule = (int)CartRule::getIdByCode(MyConf::get('RSPONSORSHIP_REAL_CODE_GC', null, $id_template));
			$cart_rule = new CartRule((int)$id_cart_rule);
			if (Validate::isLoadedObject($cart_rule)) {
				$cart_rule->id = null;
				$cart_rule->active = 1;
				$cart_rule->id_customer = (int)$this->id_customer;
				$cart_rule->description = MyConf::get('RSPONSORSHIP_VOUCHER_DETAILS', (int)$context->language->id, $id_template);
				$cart_rule->highlight = 1;
				$cart_rule->date_from = date('Y-m-d H:i:s', time());
				$cart_rule->date_to = date('Y-m-d H:i:s', time() + (int)MyConf::get('RSPONSORSHIP_VOUCHER_DURATION_GC', null, $id_template)*24*60*60);
				$languages = Language::getLanguages(true);
				$default_text = MyConf::get('RSPONSORSHIP_VOUCHER_DETAILS', (int)Configuration::get('PS_LANG_DEFAULT'), $id_template);
				foreach ($languages AS $language) {
					$text = MyConf::get('RSPONSORSHIP_VOUCHER_DETAILS', (int)$language['id_lang'], $id_template);
					$cart_rule->name[(int)$language['id_lang']] = $text ? $text : $default_text;
				}
				do $cart_rule->code = MyConf::get('RSPONSORSHIP_VOUCHER_PREFIX_GC', null, $id_template).Tools::passwdGen(6);
				while (CartRule::cartRuleExists($cart_rule->code));
				if ($cart_rule->add()) {
					CartRule::copyConditions($id_cart_rule, $cart_rule->id);
					$this->id_cart_rule = $cart_rule->id;
					$this->save();
					return true;
				}
			}
		} else {
			/* Generate a discount code */
			$code = NULL;
			do $code = MyConf::get('RSPONSORSHIP_VOUCHER_PREFIX_GC', null, $id_template).Tools::passwdGen(6);
			while (CartRule::cartRuleExists($code));

			/* Voucher creation and affectation to the customer */
			$cartRule = new CartRule();
			$cartRule->code = $code;
			$cartRule->active = 1;
			$cartRule->id_customer = (int)$this->id_customer;
			$cartRule->date_from = date('Y-m-d H:i:s', time());
			$cartRule->date_to = date('Y-m-d H:i:s', time() + (int)MyConf::get('RSPONSORSHIP_VOUCHER_DURATION_GC', null, $id_template)*24*60*60);
			$cartRule->description = MyConf::get('RSPONSORSHIP_VOUCHER_DETAILS', (int)$context->language->id, $id_template);
			$cartRule->quantity = (int)MyConf::get('RSPONSORSHIP_QUANTITY_GC', null, $id_template);
			$cartRule->quantity_per_user = (int)MyConf::get('RSPONSORSHIP_QUANTITY_GC', null, $id_template);
			$cartRule->highlight = 1;
			if ((int)MyConf::get('RSPONSORSHIP_DISCOUNT_TYPE_GC', null, $id_template) == 2)
				$cartRule->partial_use = (int)MyConf::get('RSPONSORSHIP_VOUCHER_BEHAVIOR', null, $id_template);
			else
				$cartRule->partial_use = 0;
			$cartRule->minimum_amount = (float)MyConf::get('RSPONSORSHIP_MINIMUM_VALUE_GC_'.$id_currency, null, $id_template);
			$cartRule->minimum_amount_tax = (int)MyConf::get('RSPONSORSHIP_MINIMAL_TAX_GC', null, $id_template);
			$cartRule->minimum_amount_currency = $id_currency;
			$cartRule->minimum_amount_shipping = 0;
			$cartRule->cart_rule_restriction = (int)(!(bool)MyConf::get('RSPONSORSHIP_CUMUL_GC', null, $id_template));

			if ((int)MyConf::get('RSPONSORSHIP_DISCOUNT_TYPE_GC', null, $id_template) == 1) {
				$cartRule->reduction_percent = (float)MyConf::get('RSPONSORSHIP_VOUCHER_VALUE_GC_'.$id_currency, null, $id_template);
			} else if ((int)MyConf::get('RSPONSORSHIP_DISCOUNT_TYPE_GC', null, $id_template) == 2) {
				$cartRule->reduction_amount = (float)MyConf::get('RSPONSORSHIP_VOUCHER_VALUE_GC_'.$id_currency, null, $id_template);
				$cartRule->reduction_currency = $id_currency;
				$cartRule->reduction_tax = 1;
			}
			if ((int)MyConf::get('RSPONSORSHIP_FREESHIPPING_GC', null, $id_template) == 1) {
				$cartRule->free_shipping = 1;
			}

			$languages = Language::getLanguages(true);
			$default_text = MyConf::get('RSPONSORSHIP_VOUCHER_DETAILS', (int)Configuration::get('PS_LANG_DEFAULT'), $id_template);
			foreach ($languages AS $language)
			{
				$text = MyConf::get('RSPONSORSHIP_VOUCHER_DETAILS', (int)$language['id_lang'], $id_template);
				$cartRule->name[(int)$language['id_lang']] = $text ? $text : $default_text;
			}

			$all_categories = (int)MyConf::get('RSPONSORSHIP_ALL_CATEGORIES', null, $id_template);
			$categories = explode(',', MyConf::get('RSPONSORSHIP_CATEGORIES_GC', null, $id_template));
			if (!$all_categories && is_array($categories) && count($categories) > 0 && (int)MyConf::get('RSPONSORSHIP_DISCOUNT_TYPE_GC', null, $id_template) != 0) {
				$cartRule->product_restriction = 1;
				if ((int)MyConf::get('RSPONSORSHIP_DISCOUNT_TYPE_GC', null, $id_template) == 1)
					$cartRule->reduction_product = -2;
			}

			if ($cartRule->add()) {
				$this->id_cart_rule = (int)$cartRule->id;
				$this->save();

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
				if ((int)MyConf::get('RSPONSORSHIP_CUMUL_GC', null, $id_template))
				{
					Db::getInstance()->execute('
					INSERT INTO `'._DB_PREFIX_.'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`) (
						SELECT id_cart_rule, '.(int)$cartRule->id.' FROM `'._DB_PREFIX_.'cart_rule` WHERE cart_rule_restriction = 1 AND id_customer IN (0, '.(int)$this->id_customer.')
					)');
				}
				return true;
			}
		}
		return false;
	}

	static public function getSponsorFriends($id_customer, $restriction = false)
	{
		if (!(int)($id_customer))
			return array();
		$query = '
			SELECT s.*
			FROM `'._DB_PREFIX_.'rewards_sponsorship` s
			WHERE s.`id_sponsor` = '.(int)$id_customer;
		if ($restriction)
		{
			if ($restriction == 'pending')
				$query.= ' AND s.`id_customer` = 0';
			elseif ($restriction == 'subscribed')
				$query.= ' AND s.`id_customer` != 0';
		}
		return Db::getInstance()->ExecuteS($query);
	}

	static public function isSponsorised($id_customer, $getId = false, $checkDate = false)
	{
		$result = Db::getInstance()->getRow('
		SELECT s.`id_sponsorship`
		FROM `'._DB_PREFIX_.'rewards_sponsorship` s
		WHERE s.`id_customer` = '.(int)$id_customer.
		($checkDate ? ' AND (s.`date_end`=0 OR s.`date_end` > NOW())' : ''));

		if (isset($result['id_sponsorship']) && $getId)
			return (int)$result['id_sponsorship'];
		return isset($result['id_sponsorship']);
	}

	static public function isEmailExists($email, $getId = false, $checkCustomer = true)
	{
		if (empty($email) || !Validate::isEmail($email))
			die (Tools::displayError('Email invalid.'));
		if ($checkCustomer === true && Customer::customerExists($email))
			return false;
		$result = Db::getInstance()->getRow('
			SELECT s.`id_sponsorship`
			FROM `'._DB_PREFIX_.'rewards_sponsorship` s
			WHERE s.`email` = \''.pSQL($email).'\'');
		if ($getId)
			return (int)$result['id_sponsorship'];
		return isset($result['id_sponsorship']);
	}

	static public function isMailSponsorised($idSponsor, $email, $getId=false)
	{
		if (!Validate::isEmail($email))
			die (Tools::displayError('Email invalid.'));
		$query = '
			SELECT s.`id_sponsorship`
			FROM `'._DB_PREFIX_.'rewards_sponsorship` s
			WHERE s.`email` = \''.pSQL($email).'\'
			AND s.`id_sponsor` = '.(int)$idSponsor;
		$result = Db::getInstance()->getRow($query);
		if ($getId)
			return (int)$result['id_sponsorship'];
		return isset($result['id_sponsorship']);
	}

	static public function deleteSponsoredByOther($email)
	{
		$query = 'DELETE FROM `'._DB_PREFIX_.'rewards_sponsorship` WHERE `email` = \''.pSQL($email).'\'';
		Db::getInstance()->Execute($query);
	}

	static public function getSponsorshipCode($customer, $custom=false)
	{
		if ($custom)
			$rewards_sponsorship_code = new RewardsSponsorshipCodeModel((int)$customer->id);
		return isset($rewards_sponsorship_code) && Validate::isLoadedObject($rewards_sponsorship_code) ? $rewards_sponsorship_code->code : date('d', strtotime($customer->date_add)).$customer->id.date('m', strtotime($customer->date_add));
	}

	static public function getSponsorshipLink($customer)
	{
		$context = Context::getContext();
		$code = self::getSponsorshipCode($customer, true);
		return $context->link->getPageLink('index', true, version_compare(_PS_VERSION_, '1.5.4.0', '>=') && defined('_PS_ADMIN_DIR_') ? $customer->id_lang : $context->language->id, 's='.$code);
	}

	public function getSponsorshipMailLink()
	{
		$context = Context::getContext();
		$code = 'm'.date('d', strtotime($this->date_add)).$this->id.date('m', strtotime($this->date_add));
		return $context->link->getPageLink('index', true, $context->language->id, 's='.$code);
	}

	static public function getSponsorshipProductLink($id_product)
	{
		$context = Context::getContext();
		$link = $context->link->getProductLink($id_product);
		return $link.(strpos($link, '?') !== false ? '&' : '?').'s='.self::getSponsorshipCode($context->customer, true);
	}

	static public function decodeSponsorshipLink($value) {
		if ($id_customer = RewardsSponsorshipCodeModel::getIdSponsorByCode($value))
			return (int)$id_customer;

		$id_customer = (int)Tools::substr($value, 2, -2);
		if ($id_customer) {
			$date_add = Tools::substr($value, -2) . '-' . Tools::substr($value, 0, 2);
			$query = '
				SELECT id_customer
				FROM `'._DB_PREFIX_.'customer`
				WHERE `id_customer` = '.(int)$id_customer.'
				AND `date_add` LIKE \'%'.pSQL($date_add) . '%\'';
			$result = Db::getInstance()->getRow($query);
			return (int)$result['id_customer'];
		} else
			return 0;
	}

	static public function decodeSponsorshipMailLink($value) {
		$id_sponsorship = Tools::substr($value, 3, -2);
		$date_add = Tools::substr($value, -2) . '-' . Tools::substr($value, 1, 2);
		$query = '
			SELECT id_sponsorship
			FROM `'._DB_PREFIX_.'rewards_sponsorship`
			WHERE `id_sponsorship` = '.(int)$id_sponsorship.'
			AND `date_add` LIKE \'%'.pSQL($date_add) . '%\'';
		$result = Db::getInstance()->getRow($query);
		return (int)$result['id_sponsorship'];
	}

	// check if customer is in a group which is allowed to use sponsorship or in an active template
	static public function isCustomerAllowed($customer)	{
		if (Validate::isLoadedObject($customer) && $customer->active) {
			$id_template = (int)MyConf::getIdTemplate('sponsorship', $customer->id);
			if ($id_template && MyConf::get('RSPONSORSHIP_ACTIVE', null, $id_template))
				return true;
			else if (!$id_template && Configuration::get('RSPONSORSHIP_ACTIVE')) {
				$allowed_groups = explode(',', Configuration::get('RSPONSORSHIP_GROUPS'));
				$customer_groups = $customer->getGroups();
				return sizeof(array_intersect($allowed_groups, $customer_groups)) > 0;
			}
		}
		return false;
	}

	// return the sponsor tree for a sponsored according to the settings
	static public function getSponsorshipAscendants($id_customer) {
		$sponsorships = array();
		$query = '
			SELECT *
			FROM `'._DB_PREFIX_.'rewards_sponsorship`
			WHERE `id_customer` = '.(int)$id_customer;
		if ($row = Db::getInstance()->getRow($query)) {
			$sponsorships[] = $row;
			$sponsorships = array_merge($sponsorships, self::getSponsorshipAscendants($row['id_sponsor']));
		}
		return $sponsorships;
	}

	// get all statistics for the given sponsor
	static public function getStatistics() {
		$context = Context::getContext();

		$result = array('maxlevel_reward' => 1, 'maxlevel_sponsorship' => 1, 'rewards1' => 0, 'direct_nb1' => 0, 'direct_nb2' => 0, 'direct_nb3' => 0, 'direct_nb4' => 0, 'direct_nb5' => 0, 'indirect_nb' => 0,
						'indirect_nb_orders' => 0, 'nb_orders_channel1' => 0, 'nb_orders_channel2' => 0, 'nb_orders_channel3' => 0, 'nb_orders_channel4' => 0, 'nb_orders_channel5' => 0,
						'direct_rewards_orders1' => 0, 'direct_rewards_orders2' => 0, 'direct_rewards_orders3' => 0, 'direct_rewards_orders4' => 0, 'direct_rewards_orders5' => 0, 'indirect_rewards' => 0,
						'direct_rewards_registrations1' => 0, 'direct_rewards_registrations2' => 0, 'direct_rewards_registrations3' => 0, 'direct_rewards_registrations4' => 0, 'direct_rewards_registrations5' => 0,
						'direct_rewards_registrations_value' => 0,
						'sponsored1' => array(), 'total_direct_rewards' => 0, 'total_indirect_rewards' => 0, 'total_direct_orders' => 0, 'total_indirect_orders' => 0,
						'total_orders' => 0, 'total_registrations' => 0, 'total_global' => 0);

		if ($context->customer->id) {
			$id_template = (int)MyConf::getIdTemplate('sponsorship', $context->customer->id);
			if (MyConf::get('RSPONSORSHIP_UNLIMITED_LEVELS', null, $id_template))
				$result['maxlevel_wanted'] = -1;
			else
				$result['maxlevel_wanted'] = count(explode(',', MyConf::get('RSPONSORSHIP_REWARD_TYPE_S', null, $id_template)));

			self::_getRewardsByChannel($result);
			self::_getRecursiveDescendants($context->customer->id, $result);
			self::_getStatsLevel1($result);

			if ($result['maxlevel_wanted']==-1)
				$result['maxlevel'] = $result['maxlevel_sponsorship'];
			else
				$result['maxlevel'] = $result['maxlevel_wanted'] > $result['maxlevel_reward'] ? $result['maxlevel_wanted'] : $result['maxlevel_reward'];
		}
		return $result;
	}

	static private function _getRecursiveDescendants($idSponsor, &$result, $level=1, $father=null) {
		$context = Context::getContext();

		$query = '
			SELECT rs.*
			FROM `'._DB_PREFIX_.'rewards_sponsorship` AS rs
			WHERE id_sponsor = '.(int)$idSponsor.'
			AND id_customer > 0';

		$module = new allinone_rewards();
		$rows = Db::getInstance()->ExecuteS($query);
		if (is_array($rows) && count($rows) > 0) {
			foreach ($rows AS $row)	{
				if ($level == 1) {
					$result['direct_nb'.$row['channel']]++;
					$father = $row['id_customer'];
				} else {
					if (!isset($result['rewards'.$level]))
						$result['rewards'.$level] = $module->getRewardReadyForDisplay(0, $context->currency->id);
					if ($level <= $result['maxlevel_reward'] || $level <= $result['maxlevel_wanted'] || $result['maxlevel_wanted']==-1)
						$result['indirect_nb']++;
					if ($level > $result['maxlevel_sponsorship'])
						$result['maxlevel_sponsorship'] = $level;
				}

				// nb direct or indirect friends for each level 1 sponsored
				if (!isset($result['direct_customer'.$idSponsor]))
					$result['direct_customer'.$idSponsor] = 0;
				$result['direct_customer'.$idSponsor]++;
				if (isset($father) && $level > 1 && $father != $idSponsor) {
					if (!isset($result['indirect_customer'.$father]))
						$result['indirect_customer'.$father] = 0;
					if ($level <= $result['maxlevel_reward'] || $level <= $result['maxlevel_wanted'] || $result['maxlevel_wanted']==-1)
						$result['indirect_customer'.$father]++;
				}
				// nb sponsored by level
				if (!isset($result['nb'.$level]))
					$result['nb'.$level] = 0;
				$result['nb'.$level]++;

				self::_getRecursiveDescendants($row['id_customer'], $result, $level+1, $father);
			}
		}
	}

	static private function _getRewardsByChannel(&$result) {
		$context = Context::getContext();

		$query = '
			SELECT rs.channel, rsd.level_sponsorship, SUM(IF(id_order != 0, r.credits, 0)) AS rewards_orders, SUM(IF(id_order != 0, 0, r.credits)) AS rewards_registrations, SUM(IF(id_order != 0, 1, 0)) AS nb_orders
			FROM `'._DB_PREFIX_.'rewards_sponsorship` AS rs
			JOIN `'._DB_PREFIX_.'rewards_sponsorship_detail` AS rsd USING (id_sponsorship)
			JOIN `'._DB_PREFIX_.'rewards` AS r USING (id_reward)
			WHERE id_sponsor = '.(int)$context->customer->id.'
			AND r.id_reward_state in ('.RewardsStateModel::getValidationId().','.RewardsStateModel::getConvertId().','.RewardsStateModel::getWaitingPaymentId().','.RewardsStateModel::getPaidId().')
			GROUP BY rs.channel, rsd.level_sponsorship';

		$module = new allinone_rewards();
		$rows = Db::getInstance()->ExecuteS($query);
		if (is_array($rows)) {
			foreach ($rows AS $row) {
				if ($row['level_sponsorship'] == 1)
					$result['nb_orders_channel'.$row['channel']] += $row['nb_orders'];
				else {
					if ($row['level_sponsorship'] > $result['maxlevel_reward'])
						$result['maxlevel_reward'] = $row['level_sponsorship'];
					$result['indirect_nb_orders'] += $row['nb_orders'];
				}
				if (!isset($result['nb_orders'.$row['level_sponsorship']]))
					$result['nb_orders'.$row['level_sponsorship']] = 0;
				$result['nb_orders'.$row['level_sponsorship']] += $row['nb_orders'];
				if (!isset($result['rewards'.$row['level_sponsorship']]))
					$result['rewards'.$row['level_sponsorship']] = 0;
				$result['rewards'.$row['level_sponsorship']] += $row['rewards_orders']+$row['rewards_registrations'];
				if ($row['level_sponsorship'] == 1) {
					$result['direct_rewards_orders'.$row['channel']] += $row['rewards_orders'];
					$result['direct_rewards_registrations'.$row['channel']] += $row['rewards_registrations'];
					$result['direct_rewards_registrations_value'] += $row['rewards_registrations'];
				} else
					$result['indirect_rewards'] += $row['rewards_orders'];
				$result['total_orders'] += $row['rewards_orders'];
				$result['total_registrations'] += $row['rewards_registrations'];
			}
			$result['total_global'] = $result['total_orders'] + $result['total_registrations'];

			for($channel=1; $channel<=5; $channel++) {
				$result['direct_rewards_orders'.$channel] = $module->getRewardReadyForDisplay($result['direct_rewards_orders'.$channel], $context->currency->id);
				$result['direct_rewards_registrations'.$channel] = $module->getRewardReadyForDisplay($result['direct_rewards_registrations'.$channel], $context->currency->id);
			}
			for($level=1; $level<=$result['maxlevel_reward']; $level++) {
				$result['rewards'.$level] = $module->getRewardReadyForDisplay($result['rewards'.$level], $context->currency->id);
			}

			$result['indirect_rewards'] = $module->getRewardReadyForDisplay($result['indirect_rewards'], $context->currency->id);
			$result['total_orders'] = $module->getRewardReadyForDisplay($result['total_orders'], $context->currency->id);
			$result['total_registrations'] = $module->getRewardReadyForDisplay($result['total_registrations'], $context->currency->id);
			$result['total_global'] = $module->getRewardReadyForDisplay($result['total_global'], $context->currency->id);
		}
	}

	static private function _getStatsLevel1(&$result) {
		$context = Context::getContext();

		$query = '
			SELECT id_customer, firstname, lastname, SUM(direct) AS direct, SUM(indirect) AS indirect, SUM(direct_orders) AS direct_orders, SUM(indirect_orders) AS indirect_orders
			FROM (
				/* les récompenses directes + nb de commandes directes */
				SELECT rs.id_customer AS id_customer, rs.firstname, rs.lastname, SUM(r.credits) AS direct, 0 AS indirect, SUM(IF(r.id_order > 0, 1, 0)) AS direct_orders, 0 AS indirect_orders
				FROM `'._DB_PREFIX_.'rewards_sponsorship` AS rs
				LEFT JOIN `'._DB_PREFIX_.'rewards_sponsorship_detail` AS rsd ON (rs.id_sponsorship=rsd.id_sponsorship AND rsd.level_sponsorship=1)
				LEFT JOIN `'._DB_PREFIX_.'rewards` AS r ON (rsd.id_reward=r.id_reward AND r.id_reward_state in ('.RewardsStateModel::getValidationId().','.RewardsStateModel::getConvertId().','.RewardsStateModel::getWaitingPaymentId().','.RewardsStateModel::getPaidId().'))
				WHERE rs.id_sponsor = '.(int)$context->customer->id.'
				AND rs.id_customer > 0
				GROUP BY id_customer
				UNION
				/* les récompenses indirectes + nb de commandes indirectes */
				SELECT rs.id_customer AS id_customer, rs.firstname, rs.lastname, 0 AS direct, SUM(r2.credits) AS indirect, 0 AS direct_orders, count(r2.id_reward) AS indirect_orders
				FROM `'._DB_PREFIX_.'rewards_sponsorship` AS rs
				LEFT JOIN `'._DB_PREFIX_.'rewards_sponsorship_detail` AS rsd2 ON (rs.id_sponsorship=rsd2.id_sponsorship AND rsd2.level_sponsorship!=1)
				LEFT JOIN `'._DB_PREFIX_.'rewards` AS r2 ON (rsd2.id_reward=r2.id_reward AND r2.id_reward_state in ('.RewardsStateModel::getValidationId().','.RewardsStateModel::getConvertId().','.RewardsStateModel::getWaitingPaymentId().','.RewardsStateModel::getPaidId().'))
				WHERE rs.id_sponsor = '.(int)$context->customer->id.'
				AND rs.id_customer > 0
				GROUP BY id_customer
			) AS sponsored
			GROUP BY id_customer
			ORDER BY lastname, firstname';

		$module = new allinone_rewards();
		$rows = Db::getInstance()->ExecuteS($query);
		if (is_array($rows)) {
			foreach ($rows AS $row) {
				$result['total_direct_rewards'] += $row['direct'];
				$result['total_indirect_rewards'] += $row['indirect'];
				$result['total_direct_orders'] += $row['direct_orders'];
				$result['total_indirect_orders'] += $row['indirect_orders'];

				$row['total'] = $module->getRewardReadyForDisplay($row['direct'] + $row['indirect'], $context->currency->id);
				$row['direct'] = $module->getRewardReadyForDisplay($row['direct'], $context->currency->id);
				$row['indirect'] = $module->getRewardReadyForDisplay($row['indirect'], $context->currency->id);
				$result['sponsored1'][] = $row;
			}

			$result['total_direct_rewards'] = $module->getRewardReadyForDisplay($result['total_direct_rewards'], $context->currency->id);
			$result['total_indirect_rewards'] = $module->getRewardReadyForDisplay($result['total_indirect_rewards'], $context->currency->id);
		}
	}

	// get all statistics for BO
	static public function getAdminStatistics($idSponsor=null) {
		$result = array('nb_sponsored' => 0, 'total_rewards_orders' => 0, 'total_rewards_registrations' => 0, 'nb_buyers' => 0, 'nb_orders' => 0, 'total_orders' => 0, 'direct_rewards_orders' => 0, 'direct_rewards_registrations' => 0, 'indirect_rewards' => 0,
						'nb_sponsored1' => 0, 'nb_buyers1' => 0, 'nb_orders1' => 0, 'total_orders1' => 0, 'total_rewards_orders_channel1' => 0, 'total_rewards_registrations_channel1' => 0,
						'nb_sponsored2' => 0, 'nb_buyers2' => 0, 'nb_orders2' => 0, 'total_orders2' => 0, 'total_rewards_orders_channel2' => 0, 'total_rewards_registrations_channel2' => 0,
						'nb_sponsored3' => 0, 'nb_buyers3' => 0, 'nb_orders3' => 0, 'total_orders3' => 0, 'total_rewards_orders_channel3' => 0, 'total_rewards_registrations_channel3' => 0,
						'nb_sponsored4' => 0, 'nb_buyers4' => 0, 'nb_orders4' => 0, 'total_orders4' => 0, 'total_rewards_orders_channel4' => 0, 'total_rewards_registrations_channel4' => 0,
						'nb_sponsored5' => 0, 'nb_buyers5' => 0, 'nb_orders5' => 0, 'total_orders5' => 0, 'total_rewards_orders_channel5' => 0, 'total_rewards_registrations_channel5' => 0,
						'sponsored' => array());
		if (isset($idSponsor)) {
			$result['sponsors'][$idSponsor]=array();
			$result['sponsored'][$idSponsor]=array();
		} else
			self::_getGeneralStatistics($result);
		self::_getSponsorshipsList($result, $idSponsor);
		return $result;
	}

	static private function _getGeneralStatistics(&$result) {
		// total sponsorship (invitations + without invitation)
		$query = '
			SELECT count(*) AS nb_sponsorships, count(distinct id_sponsor) AS nb_sponsors
			FROM `'._DB_PREFIX_.'rewards_sponsorship` AS rs
			JOIN `'._DB_PREFIX_.'customer` AS c ON (c.id_customer=rs.id_sponsor'.Shop::addSqlRestriction(false, 'c').')';
		$row = Db::getInstance()->getRow($query);
		$result['nb_sponsorships'] = (int)$row['nb_sponsorships'];
		$result['nb_sponsors'] = (int)$row['nb_sponsors'];

		// nb sponsored by channel
		$query = '
			SELECT channel, count(*) AS nb_sponsored
			FROM `'._DB_PREFIX_.'rewards_sponsorship` AS rs
			JOIN `'._DB_PREFIX_.'customer` AS c ON (c.id_customer=rs.id_customer'.Shop::addSqlRestriction(false, 'c').')
			GROUP BY channel';
		$rows = Db::getInstance()->ExecuteS($query);
		foreach ($rows AS $row) {
			$result['nb_sponsored'] += (int)$row['nb_sponsored'];
			$result['nb_sponsored' . $row['channel']] = (int)$row['nb_sponsored'];
		}
		$result['nb_pending'] = $result['nb_sponsorships'] - $result['nb_sponsored'];


		// total sponsored with orders, nb orders, and total orders amount by channel
		$query = '
			SELECT rs.channel, COUNT(DISTINCT o.id_customer) AS nb_buyers, COUNT(o.id_order) AS nb_orders, SUM(ROUND((o.total_paid-o.total_shipping) / o.conversion_rate, 2)) AS total_orders
			FROM `'._DB_PREFIX_.'orders` AS o
			JOIN `'._DB_PREFIX_.'rewards_sponsorship` AS rs USING(id_customer)
			JOIN `'._DB_PREFIX_.'customer` AS c ON (c.id_customer=rs.id_customer'.Shop::addSqlRestriction(false, 'c').')
			WHERE o.valid = 1
			GROUP BY rs.channel';
		$rows = Db::getInstance()->ExecuteS($query);
		foreach ($rows AS $row) {
			// by channel
			$result['nb_buyers' . $row['channel']] = $row['nb_buyers'];
			$result['nb_orders' . $row['channel']] = $row['nb_orders'];
			$result['total_orders' . $row['channel']] += $row['total_orders'];
			// global
			$result['nb_buyers'] += $row['nb_buyers'];
			$result['nb_orders'] += $row['nb_orders'];
			$result['total_orders'] += $row['total_orders'];
		}

		// Total rewards given by channel
		$query = '
			SELECT channel, SUM(IF(id_order, credits, 0)) AS total_rewards_orders, SUM(IF(id_order, 0, credits)) AS total_rewards_registrations
			FROM `'._DB_PREFIX_.'rewards` AS r
			JOIN `'._DB_PREFIX_.'rewards_sponsorship_detail` AS rsd USING(id_reward)
			JOIN `'._DB_PREFIX_.'rewards_sponsorship` AS rs USING(id_sponsorship)
			JOIN `'._DB_PREFIX_.'customer` AS c ON (c.id_customer=rs.id_customer'.Shop::addSqlRestriction(false, 'c').')
			WHERE id_reward_state in ('.RewardsStateModel::getValidationId().','.RewardsStateModel::getConvertId().','.RewardsStateModel::getWaitingPaymentId().','.RewardsStateModel::getPaidId().')
			GROUP BY channel';
		$rows = Db::getInstance()->ExecuteS($query);
		foreach ($rows AS $row) {
			$result['total_rewards_orders_channel'.$row['channel']] = (float)$row['total_rewards_orders'];
			$result['total_rewards_registrations_channel'.$row['channel']] = (float)$row['total_rewards_registrations'];
			$result['total_rewards_orders'] += (float)$row['total_rewards_orders'];
			$result['total_rewards_registrations'] += (float)$row['total_rewards_registrations'];
		}
	}

	static private function _getSponsorshipsList(&$result, $idSponsor=null) {
		// nb sponsorship, pending, buyers, orders, et total orders amount for each sponsor
		$query = '
			SELECT id_sponsor, c.firstname AS firstname, c.lastname AS lastname, SUM(nb_registered) AS nb_registered, SUM(nb_pending) AS nb_pending, SUM(nb_buyers) AS nb_buyers, SUM(nb_orders) AS nb_orders, SUM(total_orders) AS total_orders
			FROM (
				/* nombre de parrainages effectifs + nombre de filleuls ayant commandé */
				SELECT id_sponsor, COUNT(distinct rs.id_sponsorship) AS nb_registered, 0 AS nb_pending, count(distinct o.id_customer) AS nb_buyers, count(distinct o.id_order) AS nb_orders, SUM(ROUND((o.total_paid-o.total_shipping) / o.conversion_rate, 2)) AS total_orders
				FROM `'._DB_PREFIX_.'rewards_sponsorship` AS rs
				JOIN `'._DB_PREFIX_.'customer` AS c ON (c.id_customer=rs.id_customer'.Shop::addSqlRestriction(false, 'c').')
				LEFT JOIN `'._DB_PREFIX_.'orders` AS o ON(o.id_customer=rs.id_customer AND o.valid=1'.Shop::addSqlRestriction(false, 'o').')
				WHERE rs.id_customer > 0'.
				(isset($idSponsor) ? ' AND id_sponsor='.(int)$idSponsor:'').'
				GROUP BY id_sponsor
				UNION
				/* nombre d invitation en attente */
				SELECT id_sponsor, 0 AS nb_registered, count(*) AS nb_pending, 0 AS nb_buyers, 0 AS nb_orders, 0 AS total_orders
				FROM `'._DB_PREFIX_.'rewards_sponsorship` AS rs
				JOIN `'._DB_PREFIX_.'customer` AS c2 ON (c2.id_customer=rs.id_sponsor'.Shop::addSqlRestriction(false, 'c2').')
				WHERE rs.id_customer IS NULL OR rs.id_customer=0'.
				(isset($idSponsor) ? ' AND id_sponsor='.(int)$idSponsor:'').'
				GROUP BY id_sponsor
			) AS tab
			JOIN `'._DB_PREFIX_.'customer` AS c ON (c.id_customer=tab.id_sponsor'.Shop::addSqlRestriction(false, 'c').')
			GROUP BY id_sponsor
			ORDER BY lastname, firstname';
		$rows = Db::getInstance()->ExecuteS($query);
		foreach ($rows AS $row) {
			$result['sponsors'][$row['id_sponsor']] = $row;
			$result['sponsors'][$row['id_sponsor']]['total_orders'] = (float)$row['total_orders'];
			$result['sponsors'][$row['id_sponsor']]['direct_rewards_orders'] = 0;
			$result['sponsors'][$row['id_sponsor']]['direct_rewards_registrations'] = 0;
			$result['sponsors'][$row['id_sponsor']]['indirect_rewards'] = 0;
		}

		// Total rewards given by sponsor and channel
		$query = '
			SELECT r.id_customer AS id_sponsor, rsd.level_sponsorship, SUM(IF(r.id_order, r.credits, 0)) AS total_rewards_orders, SUM(IF(r.id_order, 0, r.credits)) AS total_rewards_registrations
			FROM `'._DB_PREFIX_.'rewards` AS r
			JOIN `'._DB_PREFIX_.'customer` AS c ON (c.id_customer=r.id_customer'.Shop::addSqlRestriction(false, 'c').')
			JOIN `'._DB_PREFIX_.'rewards_sponsorship_detail` AS rsd USING (id_reward)
			WHERE 1=1'.
			(isset($idSponsor) ? ' AND r.id_customer='.(int)$idSponsor:'').'
			AND r.id_reward_state IN ('.RewardsStateModel::getValidationId().','.RewardsStateModel::getConvertId().','.RewardsStateModel::getWaitingPaymentId().','.RewardsStateModel::getPaidId().')
			GROUP BY id_sponsor, rsd.level_sponsorship';
		$rows = Db::getInstance()->ExecuteS($query);
		foreach ($rows AS $row) {
			if ($row['level_sponsorship'] == 1) {
				$result['sponsors'][$row['id_sponsor']]['direct_rewards_orders'] = (float)$row['total_rewards_orders'];
				$result['sponsors'][$row['id_sponsor']]['direct_rewards_registrations'] = (float)$row['total_rewards_registrations'];
			} else
				$result['sponsors'][$row['id_sponsor']]['indirect_rewards'] += (float)$row['total_rewards_orders'];
		}

		// Rewards for each sponsor, grouped by sponsored
		$query = '
			SELECT rsp.id_sponsorship, rsp.date_end, IF(rsp.date_end=0 OR rsp.date_end > NOW(), 1, 0) AS active, r.id_customer AS id_sponsor, IF(o.id_customer, o.id_customer, rsp.id_customer) AS id_sponsored, rsp.channel, IF(o.id_customer, c.firstname, rsp.firstname) AS firstname, IF(o.id_customer, c.lastname, rsp.lastname) AS lastname, rsd.level_sponsorship, SUM(IF(r.id_reward_state IN ('.RewardsStateModel::getValidationId().','.RewardsStateModel::getConvertId().','.RewardsStateModel::getWaitingPaymentId().','.RewardsStateModel::getPaidId().'), r.credits, 0)) AS total_rewards, SUM(IF(o.valid=1, 1, 0)) AS nb_orders, ROUND(SUM(IF(o.valid=1, (o.total_paid-o.total_shipping) / o.conversion_rate, 0)), 2) AS total_orders
			/* les filleuls ayant donné une récompense */
			FROM `'._DB_PREFIX_.'rewards` AS r
			JOIN `'._DB_PREFIX_.'customer` AS c2 ON (c2.id_customer=r.id_customer'.Shop::addSqlRestriction(false, 'c2').')
			JOIN `'._DB_PREFIX_.'rewards_sponsorship_detail` AS rsd USING (id_reward)
			JOIN `'._DB_PREFIX_.'rewards_sponsorship` AS rsp USING (id_sponsorship)
			LEFT JOIN `'._DB_PREFIX_.'orders` AS o ON (o.id_order=r.id_order'.Shop::addSqlRestriction(false, 'o').')
			LEFT JOIN `'._DB_PREFIX_.'customer` AS c ON (o.id_customer=c.id_customer)'.
			(isset($idSponsor) ? ' WHERE r.id_customer='.(int)$idSponsor:'').'
			GROUP BY r.id_customer, id_sponsored
			UNION
			/* les filleuls n ayant pas donné de récompense directe */
			SELECT rs.id_sponsorship, rs.date_end, IF(rs.date_end=0 OR rs.date_end > NOW(), 1, 0) AS active, rs.id_sponsor, rs.id_customer AS id_sponsored, rs.channel, rs.firstname, rs.lastname, 1 AS level_sponsorship, 0, 0, 0
			FROM `'._DB_PREFIX_.'rewards_sponsorship` AS rs
			JOIN `'._DB_PREFIX_.'customer` AS c ON (c.id_customer=rs.id_customer'.Shop::addSqlRestriction(false, 'c').')
			WHERE 0 = (
				SELECT count(*)	FROM `'._DB_PREFIX_.'rewards_sponsorship_detail` AS rsd2
				JOIN `'._DB_PREFIX_.'rewards` AS r2 USING (id_reward)
				WHERE rsd2.level_sponsorship=1
				AND rsd2.id_sponsorship=rs.id_sponsorship/*
				AND r2.id_reward_state IN ('.RewardsStateModel::getValidationId().','.RewardsStateModel::getConvertId().','.RewardsStateModel::getWaitingPaymentId().','.RewardsStateModel::getPaidId().')*/
			)'.
			(isset($idSponsor) ? ' AND rs.id_sponsor='.(int)$idSponsor:'').'
			ORDER BY id_sponsor, level_sponsorship, lastname, firstname
			';
		$rows = Db::getInstance()->ExecuteS($query);
		if (is_array($rows)) {
			foreach ($rows AS $row) {
				$result['sponsored'][$row['id_sponsor']][] = $row;
			}
		}
	}

	static public function getAllSponsorshipRewardsByOrderId($id_order)
	{
		$context = Context::getContext();

		if (!Validate::isUnsignedId($id_order))
			return false;

		$result = Db::getInstance()->ExecuteS('
		SELECT c.id_customer, c.firstname, c.lastname, r.credits, rsd.level_sponsorship, rs.name AS state
		FROM `'._DB_PREFIX_.'rewards` r
		JOIN `'._DB_PREFIX_.'rewards_sponsorship_detail` rsd USING (id_reward)
		JOIN `'._DB_PREFIX_.'customer` c USING (id_customer)
		JOIN `'._DB_PREFIX_.'rewards_state_lang` rs ON (r.id_reward_state = rs.id_reward_state AND rs.id_lang = '.(int)$context->language->id.')
		WHERE r.id_order = '.(int)$id_order.'
		ORDER BY rsd.level_sponsorship ASC, r.id_reward DESC');
		return $result;
	}

	static public function isAlreadyRewarded($id_sponsorship)
	{
		$query = '
			SELECT rsd.id_sponsorship
			FROM `'._DB_PREFIX_.'rewards` r
			JOIN `'._DB_PREFIX_.'rewards_sponsorship_detail` rsd USING (id_reward)
			WHERE rsd.id_sponsorship = '.(int)$id_sponsorship.'
			AND r.id_reward_state != '.(int)RewardsStateModel::getCancelId().'
			AND r.id_order != 0
			AND credits > 0';
		$result = Db::getInstance()->getRow($query);
		return isset($result['id_sponsorship']);
	}

	static public function getRewardDetails($id_reward)
	{
		$query = 'SELECT rsd.level_sponsorship, c.id_customer, c.firstname, c.lastname, o.reference, c2.firstname AS order_firstname, c2.lastname AS order_lastname FROM `'._DB_PREFIX_.'rewards_sponsorship_detail` rsd
				JOIN `'._DB_PREFIX_.'rewards_sponsorship` rs USING (`id_sponsorship`)
				JOIN `'._DB_PREFIX_.'rewards` r USING (`id_reward`)
				JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer`=rs.`id_customer`)
				LEFT JOIN `'._DB_PREFIX_.'orders` o USING (`id_order`)
				LEFT JOIN `'._DB_PREFIX_.'customer` c2 ON (c2.`id_customer`=o.`id_customer`)
				WHERE `id_reward`='.(int)$id_reward;
		return Db::getInstance()->getRow($query);
	}

	static public function saveDetails($id_reward, $id_sponsorship, $level)
	{
		return Db::getInstance()->execute('
				INSERT INTO `'._DB_PREFIX_.'rewards_sponsorship_detail` (`id_reward`, `id_sponsorship`, `level_sponsorship`)
				VALUE ('.(int)$id_reward.','.(int)$id_sponsorship.','.(int)$level.')'
		);
	}

	static public function getByOrderId($id_order, $id_sponsorship)
	{
		if (!Validate::isUnsignedId($id_order))
			return false;

		return Db::getInstance()->ExecuteS('
			SELECT r.id_reward
			FROM `'._DB_PREFIX_.'rewards` r
			JOIN `'._DB_PREFIX_.'rewards_sponsorship_detail` rsd USING (id_reward)
			WHERE r.plugin=\'sponsorship\' AND r.id_order='.(int)$id_order.' AND rsd.id_sponsorship='.$id_sponsorship.'
			ORDER BY level_sponsorship, id_cart_rule, id_payment');
	}

	// get All descnedants IDs for a given sponsor
	static private function _getRecursiveDescendantsIds($id_sponsor, &$descendants) {
		$query = '
			SELECT rs.id_customer
			FROM `'._DB_PREFIX_.'rewards_sponsorship` AS rs
			WHERE id_sponsor = '.(int)$id_sponsor.'
			AND id_customer > 0';
		$rows = Db::getInstance()->ExecuteS($query);
		if (is_array($rows) && count($rows) > 0) {
			foreach ($rows AS $row)	{
				$descendants[] = $row['id_customer'];
				self::_getRecursiveDescendantsIds($row['id_customer'], $descendants);
			}
		}
	}


	static public function getCustomersForFilter($plugin, $filter) {
		$query = '	SELECT c.`id_customer`, c.`firstname`, c.`lastname`, c.`email`
					FROM `'._DB_PREFIX_.'customer` c
					WHERE `id_customer` NOT IN (
						SELECT `id_customer`
						FROM `'._DB_PREFIX_.'rewards_template_customer`
						JOIN `'._DB_PREFIX_.'rewards_template` USING (`id_template`)
						WHERE `plugin`=\''.$plugin.'\'
					)
					AND (
						c.`id_customer` = '.(int)$filter.'
						OR c.`firstname` LIKE "%'.pSQL($filter).'%"
						OR c.`lastname` LIKE "%'.pSQL($filter).'%"
						OR c.`email` LIKE "%'.pSQL($filter).'%"
					)';
		return Db::getInstance()->executeS($query);
	}

	// return all customers from the groups allowed to be sponsor, or from active template and which are not in the descendants tree of the customer
	static public function getAvailableSponsors($id_customer, $filter) {
		$result = array();
		$allowed_groups = Configuration::get('RSPONSORSHIP_GROUPS');
		$query = '
			SELECT DISTINCT c.`id_customer`, c.`firstname`, c.`lastname`, c.`email`
			FROM `'._DB_PREFIX_.'customer` AS c
			JOIN `'._DB_PREFIX_.'customer_group` AS cg USING (id_customer)
			WHERE c.deleted = 0
			AND id_customer != '.(int)$id_customer.'
			AND (
				c.`id_customer` = '.(int)$filter.'
				OR c.`firstname` LIKE "%'.pSQL($filter).'%"
				OR c.`lastname` LIKE "%'.pSQL($filter).'%"
				OR c.`email` LIKE "%'.pSQL($filter).'%"
			)
			AND ('.
				(!empty($allowed_groups) ? '
				(
					id_group IN ('.Configuration::get('RSPONSORSHIP_GROUPS').')
					AND '.Configuration::get('RSPONSORSHIP_ACTIVE').'=1
				) OR ' : '').'
				id_customer IN (
					SELECT DISTINCT id_customer FROM `'._DB_PREFIX_.'rewards_template_customer`
					JOIN `'._DB_PREFIX_.'rewards_template` USING (id_template)
					JOIN `'._DB_PREFIX_.'rewards_template_config` rtc USING (id_template)
					WHERE plugin=\'sponsorship\' AND rtc.name=\'RSPONSORSHIP_ACTIVE\' AND rtc.value=1
				)
			)';
		$rows = Db::getInstance()->ExecuteS($query);
		if (is_array($rows)) {
			$descendants = array();
			self::_getRecursiveDescendantsIds($id_customer, $descendants);
			foreach ($rows AS $row) {
				if (!in_array($row['id_customer'], $descendants))
					$result[] = $row;
			}
		}
		return $result;
	}
}