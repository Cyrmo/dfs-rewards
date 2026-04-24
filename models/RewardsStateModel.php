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

class RewardsStateModel extends ObjectModel
{
	public $id_reward_state;
	public $id_order_state;
	public $name;

	public static $definition = array(
		'table' => 'rewards_state',
		'primary' => 'id_reward_state',
		'multilang' => true,
		'fields' => array(
			'id_reward_state' 	=> array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
			'id_order_state' 	=> array('type' => self::TYPE_STRING, 'validate' => 'isString'),
			'name' 				=> array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true, 'size' => 64),
		),
	);

	public static function getDefaultId() { return 1; }
	public static function getValidationId() { return 2; }
	public static function getCancelId() { return 3; }
	public static function getConvertId() { return 4; }
	public static function getReturnPeriodId() { return 6; }
	public static function getWaitingPaymentId() { return 7; }
	public static function getPaidId() { return 8; }

	public static function insertDefaultData()
	{
		$module = new allinone_rewards(false);
		$idEn = Language::getIdByIso('en');

		$defaultTranslations = array();
		$defaultTranslations['default'] = array('id_reward_state' => (int)RewardsStateModel::getDefaultId(), 'key' => 'awaiting_validation');
		$defaultTranslations['validated'] = array('id_reward_state' => (int)RewardsStateModel::getValidationId(), 'id_order_state' => Configuration::get('PS_OS_DELIVERED'), 'key' => 'available');
		$defaultTranslations['cancelled'] = array('id_reward_state' => (int)RewardsStateModel::getCancelId(), 'id_order_state' => Configuration::get('PS_OS_CANCELED'), 'key' => 'cancelled');
		$defaultTranslations['converted'] = array('id_reward_state' => (int)RewardsStateModel::getConvertId(), 'key' => 'already_converted');
		$defaultTranslations['returnperiod'] = array('id_reward_state' => (int)RewardsStateModel::getReturnPeriodId(), 'key' => 'return_period');
		$defaultTranslations['waitingpayment'] = array('id_reward_state' => (int)RewardsStateModel::getWaitingPaymentId(), 'key' => 'awaiting_payment');
		$defaultTranslations['paid'] = array('id_reward_state' => (int)RewardsStateModel::getPaidId(), 'key' => 'paid');

		foreach ($defaultTranslations AS $rewardState)
		{
			$state = new RewardsStateModel((int)$rewardState['id_reward_state']);
			if (!Validate::isLoadedObject($state)) {
				$state->id_reward_state = (int)$rewardState['id_reward_state'];
				if (isset($rewardState['id_order_state']))
					$state->id_order_state = $rewardState['id_order_state'];

				foreach (Language::getLanguages() AS $language) {
					$tmp = $module->getL($rewardState['key'], (int)$language['id_lang']);
					$state->name[(int)$language['id_lang']] = isset($tmp) && !empty($tmp) ? $tmp : $module->getL($rewardState['key'], $idEn);
				}
				$state->save();
			}
		}

		return true;
	}

	public function getValues()
	{
		return explode(',', $this->id_order_state);
	}

	public function getFieldsLang() {
		// allow to have a lang table without having an autoincrement ID
		$this->id = $this->id_reward_state;
		return parent::getFieldsLang();
	}
}
