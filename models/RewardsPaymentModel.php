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

class RewardsPaymentModel extends ObjectModel
{
	public $credits;
	public $detail;
	public $invoice;
	public $paid;
	public $date_add;
	public $date_upd;

	public static $definition = array(
		'table' => 'rewards_payment',
		'primary' => 'id_payment',
		'fields' => array(
			'credits' =>			array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
			'detail' =>				array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
			'invoice' =>			array('type' => self::TYPE_STRING, 'validate' => 'isFileName'),
			'paid' =>				array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
			'date_add' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
			'date_upd' =>			array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
		),
	);

	public static function askForPayment($credits, $ratio, $detail, $file) {
		$context = Context::getContext();

		$filename = null;
		$fileAttachment = null;
		if (isset($file['name']) && !empty($file['tmp_name'])) {
			@mkdir(dirname(__FILE__).'/../uploads/', 0750);
			$filename = md5(uniqid(rand(), true)).'.'.pathinfo($file['name'], PATHINFO_EXTENSION);
			$path = dirname(__FILE__).'/../uploads/'.$filename;
			if (!move_uploaded_file($file['tmp_name'], $path))
				return false;

			$fileAttachment['content'] = Tools::file_get_contents($path);
			$fileAttachment['name'] = $filename;
			$fileAttachment['mime'] = $file['type'];
		}

		$payment_value = (float)round($credits*$ratio/100, 2);

		$payment = new RewardsPaymentModel();
		$payment->credits = $payment_value;
		$payment->detail = $detail;
		$payment->invoice = $filename;
		$payment->paid = 0;
		$payment->save();
		RewardsModel::registerPayment($payment, $credits);

		// DO NOT REMOVE
		// $this->l('Payment request')
		$customer = new Customer((int)$context->customer->id);
		$data =  array(
			'{text}'		=> nl2br($detail),
			'{value}'		=> Tools::displayPrice($payment_value, (int)Configuration::get('PS_CURRENCY_DEFAULT')),
			'{customer}'	=> $customer->firstname . ' ' . $customer->lastname . ' (ID : ' . $customer->id . ')'
		);

		$module = new allinone_rewards();
		$module->sendMail((int)Configuration::get('PS_LANG_DEFAULT'), 'payment-request', $module->l2('Payment request', (int)Configuration::get('PS_LANG_DEFAULT'), 'rewardspaymentmodel'), $data, Configuration::get('PS_SHOP_EMAIL'), NULL, $fileAttachment);
		return true;
	}

	public static function getAllByIdCustomer($id_customer)
	{
		$query = '
			SELECT rp.*
			FROM `'._DB_PREFIX_.'rewards_payment` rp
			JOIN `'._DB_PREFIX_.'rewards` r USING (id_payment)
			WHERE id_customer='.(int)$id_customer.'
			GROUP BY rp.id_payment
			ORDER BY rp.date_add ASC';
		return Db::getInstance()->ExecuteS($query);
	}

	public static function getPendingList()
	{
		$query = '
			SELECT rp.*, c.id_customer, c.firstname, c.lastname
			FROM `'._DB_PREFIX_.'rewards_payment` rp
			JOIN `'._DB_PREFIX_.'rewards` r USING (id_payment)
			JOIN `'._DB_PREFIX_.'customer` AS c ON (c.id_customer=r.id_customer'.Shop::addSqlRestriction(false, 'c').')
			WHERE paid=0
			GROUP BY rp.id_payment
			ORDER BY rp.date_add ASC';
		return Db::getInstance()->ExecuteS($query);
	}

	public static function acceptPayment($id_payment)
	{
		$context = Context::getContext();

		$payment = new RewardsPaymentModel($id_payment);
		if (!Validate::isLoadedObject($payment))
			die(Tools::displayError('Incorrect object RewardsPaymentModel.'));
		else if ($payment->paid == 1)
			return;
		$payment->paid = 1;
		$payment->save();

		$id_customer = RewardsModel::acceptPayment($id_payment);

		// DO NOT REMOVE
		// $this->l('Your payment request has been processed')
		$customer = new Customer($id_customer);
		$data =  array(
			'{firstname}'	=> $customer->firstname,
			'{lastname}'	=> $customer->lastname,
			'{value}'		=> Tools::displayPrice((float)$payment->credits, (int)Configuration::get('PS_CURRENCY_DEFAULT')),
			'{date_add}'	=> Tools::displayDate($payment->date_add, null, true),
			'{link_rewards}' => $context->link->getModuleLink('allinone_rewards', 'rewards', array(), true)
		);
		$module = new allinone_rewards();
		$module->sendMail((int)$customer->id_lang, 'payment-accepted', $module->l2('Your payment request has been processed', (int)$customer->id_lang, 'rewardspaymentmodel'), $data, $customer->email, $customer->firstname.' '.$customer->lastname, NULL);
	}
}