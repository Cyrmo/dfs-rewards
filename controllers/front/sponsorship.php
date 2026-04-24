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

class Allinone_rewardsSponsorshipModuleFrontController extends ModuleFrontController
{
	public $content_only = false;
	public $display_header = true;
	public $display_footer = true;
	private $_ajaxCall = false;

	public function init()
	{
		if (!Tools::getValue('checksponsor')) {
			$id_template = (int)MyConf::getIdTemplate('sponsorship', $this->context->customer->id);
			if (!MyConf::get('RSPONSORSHIP_ACTIVE', null, $id_template))
				die('This functionality is not available');

			if (!$this->context->customer->isLogged())
				Tools::redirect('index.php?controller=authentication');
			elseif (!RewardsSponsorshipModel::isCustomerAllowed($this->context->customer))
				Tools::redirect('index');
		}

		if (Tools::getValue('popup')) {
			// allow to not add the javascript at the end causing JS issue (presta 1.6)
			$this->controller_type = 'modulefront';
			$this->content_only = true;
			$this->display_header = false;
			$this->display_footer = false;
			$this->_ajaxCall = true;
		}

		parent::init();
	}

	// allow to not add the javascript at the end causing a loop on the popup when "defer javascript" is activated
	public function display()
	{
		if ($this->_ajaxCall) {
			$html = $this->context->smarty->fetch($this->template);
	        echo trim($html);
	        return true;
		} else
			return parent::display();
	}

	public function setMedia()
	{
		parent::setMedia();
		if (!Tools::getValue('checksponsor')) {
			$this->addJqueryPlugin(array('idTabs'));
		}
	}

	public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
        return $breadcrumb;
    }

	public function initContent()
	{
		parent::initContent();

		$id_template = (int)MyConf::getIdTemplate('sponsorship', $this->context->customer->id);
		$popup = Tools::getValue('popup');

		if (Tools::getValue('checksponsor')) {
			$sponsorship = trim(Tools::getValue('sponsorship'));
			$customer_email = trim(Tools::getValue('customer_email'));
			$sponsor = new Customer(RewardsSponsorshipModel::decodeSponsorshipLink($sponsorship));
			if (Validate::isLoadedObject($sponsor) && RewardsSponsorshipModel::isCustomerAllowed($sponsor) && $sponsor->email != $customer_email) {
				die('{"result":"1"}');
			} else if (Validate::isEmail($sponsorship)) {
				$sponsor = new Customer();
				$sponsor=$sponsor->getByEmail($sponsorship);
				if (Validate::isLoadedObject($sponsor) && RewardsSponsorshipModel::isCustomerAllowed($sponsor) && $sponsor->email != $customer_email){
					die('{"result":"1"}');
				}
			}
			die('{"result":"0"}');
		} else {
			$error = false;

			// get discount value for sponsored (ready to display)
			$nb_discount = (int)MyConf::get('RSPONSORSHIP_QUANTITY_GC', null, $id_template);
			$discount_gc = $this->module->getDiscountReadyForDisplay((int)MyConf::get('RSPONSORSHIP_DISCOUNT_TYPE_GC', null, $id_template), (int)MyConf::get('RSPONSORSHIP_FREESHIPPING_GC', null, $id_template), (float)MyConf::get('RSPONSORSHIP_VOUCHER_VALUE_GC_'.(int)$this->context->currency->id, null, $id_template), null, MyConf::get('RSPONSORSHIP_REAL_VOUCHER_GC', null, $id_template) ? MyConf::get('RSPONSORSHIP_REAL_DESC_GC', (int)$this->context->language->id, $id_template) : null);
			if (MyConf::get('RSPONSORSHIP_REAL_VOUCHER_GC', null, $id_template)) {
				$cart_rule = new CartRule((int)CartRule::getIdByCode(MyConf::get('RSPONSORSHIP_REAL_CODE_GC', null, $id_template)));
				if (Validate::isLoadedObject($cart_rule))
					$nb_discount = $cart_rule->quantity_per_user;
			}

			$template = 'sponsorship-invitation-novoucher';
			if ((int)MyConf::get('RSPONSORSHIP_DISCOUNT_GC', null, $id_template) == 1)
				$template = 'sponsorship-invitation';

			$activeTab = 'sponsor';

			// Mailing invitation to friend sponsor
			$invitation_sent = false;
			$sms_sent = false;
			$hook_sms = Hook::getIdByName('sendsms2Sponsorship');
			$sms_active = Module::isEnabled('sendsms2') && Configuration::get('SENDSMS2_ISACTIVE_'.$hook_sms);

			$nbInvitation = 0;
			$code = RewardsSponsorshipModel::getSponsorshipCode($this->context->customer, true);

			if (Tools::getValue('friendsEmail') && sizeof($friendsEmail = Tools::getValue('friendsEmail')) >= 1)
			{
				$activeTab = 'sponsor';

				$friendsLastName = Tools::getValue('friendsLastName');
				$friendsFirstName = Tools::getValue('friendsFirstName');
				$mails_exists = array();

				// 1ere boucle pour contrôle des erreurs
				foreach ($friendsEmail as $key => $friendEmail)
				{
					$friendEmail = $friendEmail;
					$friendLastName = isset($friendsLastName[$key]) ? $friendsLastName[$key] : '';
					$friendFirstName = isset($friendsFirstName[$key]) ? $friendsFirstName[$key] : '';

					if (empty($friendEmail) && empty($friendLastName) && empty($friendFirstName))
						continue;
					elseif (empty($friendEmail) || !Validate::isEmail($friendEmail)) {
						$error = 'email invalid';
						if (version_compare(_PS_VERSION_, '1.7', '>='))
							$this->errors[] = $this->l('At least one email address is invalid!', 'sponsorship');
					} elseif (Tools::isSubmit('submitSponsorFriends') && (empty($friendFirstName) || empty($friendLastName) || !Validate::isName($friendLastName) || !Validate::isName($friendFirstName))) {
						$error = 'name invalid';
						if (version_compare(_PS_VERSION_, '1.7', '>='))
							$this->errors[] = $this->l('At least one first name or last name is invalid!', 'sponsorship');
					}
					if ($error)
						break;
				}

				if (!$error) {
					// 2ème boucle pour envoie des invitations
					foreach ($friendsEmail as $key => $friendEmail)
					{
						$friendEmail = $friendEmail;
						$friendLastName = isset($friendsLastName[$key]) ? $friendsLastName[$key] : '';
						$friendFirstName = isset($friendsFirstName[$key]) ? $friendsFirstName[$key] : '';

						if (empty($friendEmail) && empty($friendLastName) && empty($friendFirstName))
							continue;

						if (RewardsSponsorshipModel::isEmailExists($friendEmail) || Customer::customerExists($friendEmail))	{
							$error = 'email exists';
							if (version_compare(_PS_VERSION_, '1.7', '>='))
								$this->errors[] = $this->l('Someone with this email address has already been sponsored', 'sponsorship');
							$mails_exists[] = $friendEmail;
							continue;
						}

						$sponsorship = new RewardsSponsorshipModel();
						$sponsorship->id_sponsor = (int)$this->context->customer->id;
						$sponsorship->firstname = $friendFirstName;
						$sponsorship->lastname = $friendLastName;
						$sponsorship->channel = 1;
						$sponsorship->email = $friendEmail;
						if ($sponsorship->save()) {
							$vars = array(
								'{message}' => Tools::nl2br(Tools::getValue('message')),
								'{email}' => $this->context->customer->email,
								'{lastname}' => $this->context->customer->lastname,
								'{firstname}' => $this->context->customer->firstname,
								'{sponsored_firstname}' => $sponsorship->firstname,
								'{sponsored_lastname}' => $sponsorship->lastname,
								'{link}' => $sponsorship->getSponsorshipMailLink(),
								'{nb_discount}' => $nb_discount,
								'{discount}' => $discount_gc);
							$this->module->sendMail((int)$this->context->language->id, $template, $this->module->getL('invitation'), $vars, $friendEmail, $friendFirstName.' '.$friendLastName);
							$invitation_sent = true;
							$nbInvitation++;
							$activeTab = 'pending';
						}
					}
				}
				if ($nbInvitation > 0) {
					$_POST = array();
					if (version_compare(_PS_VERSION_, '1.7', '>='))
						$this->success[] = $nbInvitation > 1 ? $this->l('Emails have been sent to your friends!', 'sponsorship') : $this->l('An email has been sent to your friend!', 'sponsorship');
				}
			} else if ($sms_active && Tools::isSubmit('submitSponsorSMS')) {
				$phone = Tools::getValue('phone');
				if (empty($phone) || !Validate::isPhoneNumber($phone)) {
					$error = 'bad phone';
					if (version_compare(_PS_VERSION_, '1.7', '>='))
						$this->errors[] = $this->l('The mobile phone is invalid', 'sponsorship');
				} else {
					$qry = '
						SELECT count(*)
						FROM `'._DB_PREFIX_.'sendsms_recipient`
						JOIN `'._DB_PREFIX_.'sendsms_campaign` AS sc USING(id_sendsms_campaign)
						WHERE `phone`=\''.pSQL($phone).'\'
						AND `event`=\'sendsms2Sponsorship\'
						AND sc.status=3
						AND TO_DAYS(NOW()) - TO_DAYS(sc.date_send) <= 10';
					$result = Db::getInstance()->getValue($qry);
					if ((int)$result > 0) {
						$error = 'sms already sent';
						if (version_compare(_PS_VERSION_, '1.7', '>='))
							$this->errors[] = $this->l('This mobile phone has already been invited during last 10 days, please retry later.', 'sponsorship');
					} else {
						// envoi du SMS
						$vars = array('phone' => $phone, 'customer' => $this->context->customer, 'code' => $code);
						if (!Hook::exec('sendsms2Sponsorship', $vars)) {
							$error = 'sms impossible';
							if (version_compare(_PS_VERSION_, '1.7', '>='))
								$this->errors[] = $this->l('An error occured, the SMS has not been sent', 'sponsorship');
						} else
							$sms_sent = true;
							if (version_compare(_PS_VERSION_, '1.7', '>='))
								$this->success[] = $this->l('A SMS has been sent to your friend!', 'sponsorship');
					}
				}
			}

			if (!$popup) {
				// Mailing revive
				$revive_sent = false;
				$nbRevive = 0;
				if (Tools::isSubmit('revive'))
				{
					$activeTab = 'pending';
					if (Tools::getValue('friendChecked') && sizeof($friendsChecked = Tools::getValue('friendChecked')) >= 1)
					{
						foreach ($friendsChecked as $key => $friendChecked)
						{
							$sponsorship = new RewardsSponsorshipModel((int)$key);
							$vars = array(
								'{message}' => '',
								'{email}' => $this->context->customer->email,
								'{lastname}' => $this->context->customer->lastname,
								'{firstname}' => $this->context->customer->firstname,
								'{sponsored_firstname}' => $sponsorship->firstname,
								'{sponsored_lastname}' => $sponsorship->lastname,
								'{email_friend}' => $sponsorship->email,
								'{link}' => $sponsorship->getSponsorshipMailLink(),
								'{nb_discount}' => $nb_discount,
								'{discount}' => $discount_gc
							);
							$sponsorship->save();
							$this->module->sendMail((int)$this->context->language->id, $template, $this->module->getL('invitation'), $vars, $sponsorship->email, $sponsorship->firstname.' '.$sponsorship->lastname);
							$nbRevive++;
						}
						$revive_sent = true;
						if (version_compare(_PS_VERSION_, '1.7', '>='))
							$this->success[] = $nbRevive > 1 ? $this->l('Reminder emails have been sent to your friends!', 'sponsorship') : $this->l('A reminder email has been sent to your friend!', 'sponsorship');
					}
					else {
						$error = 'no revive checked';
						if (version_compare(_PS_VERSION_, '1.7', '>='))
							$this->errors[] = $this->l('Please mark at least one checkbox', 'sponsorship');
					}
				}

				$stats = $this->context->customer->getStats();

				$orderQuantityS = (int)MyConf::get('RSPONSORSHIP_ORDER_QUANTITY_S', null, $id_template);

				$canSendInvitations = false;
				if ((int)($stats['nb_orders']) >= $orderQuantityS)
					$canSendInvitations = true;
			}

			// lien de parrainage
			$link_sponsorship = RewardsSponsorshipModel::getSponsorshipLink($this->context->customer);
			$link_sponsorship_fb = $link_sponsorship . '&c=3';
			$link_sponsorship_twitter = $link_sponsorship . '&c=4';
			$link_sponsorship_google = $link_sponsorship . '&c=5';

			// Smarty display
			$smarty_values = array(
				'url_sponsorship' => $this->context->link->getModuleLink('allinone_rewards', 'sponsorship', array(), true),
				'url_sponsorship_rules' => $this->context->link->getModuleLink('allinone_rewards', 'rules', !$popup ? array() : array('sback' => 1), true),
				'url_sponsorship_email' => $this->context->link->getModuleLink('allinone_rewards', 'email', !$popup ? array() : array('sback' => 1), true),
				'text' => !$popup ? MyConf::get('RSPONSORSHIP_ACCOUNT_TXT', $this->context->language->id, $id_template) : (Tools::getValue('scheduled') == 1 ? MyConf::get('RSPONSORSHIP_POPUP_TXT', $this->context->language->id, $id_template) : MyConf::get('RSPONSORSHIP_ORDER_TXT', $this->context->language->id, $id_template)),
				'link_sponsorship' => $link_sponsorship,
				'link_sponsorship_fb' => urlencode($link_sponsorship_fb),
				'link_sponsorship_twitter' => urlencode($link_sponsorship_twitter),
				'link_sponsorship_google' => urlencode($link_sponsorship_google),
				'email' => $this->context->customer->email,
				'code' => $code,
				'nbFriends' => (int)MyConf::get('RSPONSORSHIP_NB_FRIENDS', null, $id_template),
				'message' => Tools::getValue('message'),
				'friendsLastName' => Tools::getValue('friendsLastName'),
				'friendsFirstName' => Tools::getValue('friendsFirstName'),
				'friendsEmail' => Tools::getValue('friendsEmail'),
				'error' => $error,
				'invitation_sent' => $invitation_sent,
				'sms_sent' => $sms_sent,
				'nbInvitation' => $nbInvitation,
				'mails_exists' => (isset($mails_exists) ? $mails_exists : array()),
				'rewards_path' => $this->module->getPathUri(),
				'sms' => $sms_active
			);
			$this->context->smarty->assign($smarty_values);

			// si affichage normal, dans le compte du client
			if (!$popup) {
				$statistics = RewardsSponsorshipModel::getStatistics();
				$reward_order_allowed = (int)MyConf::get('RSPONSORSHIP_REWARD_ORDER', null, $id_template) || ($statistics['direct_rewards_orders1']+$statistics['direct_rewards_orders2']+$statistics['direct_rewards_orders3']+$statistics['direct_rewards_orders4']+$statistics['direct_rewards_orders5']+$statistics['indirect_rewards']) > 0;
				$reward_registration_allowed = (int)MyConf::get('RSPONSORSHIP_REWARD_REGISTRATION', null, $id_template) || $statistics['direct_rewards_registrations_value'] > 0;
				$smarty_values = array(
					'activeTab' => $activeTab,
					'orderQuantityS' => $orderQuantityS,
					'canSendInvitations' => $canSendInvitations,
					'pendingFriends' => RewardsSponsorshipModel::getSponsorFriends((int)$this->context->customer->id, 'pending'),
					'revive_sent' => $revive_sent,
					'nbRevive' => $nbRevive,
					'subscribeFriends' => RewardsSponsorshipModel::getSponsorFriends((int)$this->context->customer->id, 'subscribed'),
					'statistics' => $statistics,
					'reward_order_allowed' => $reward_order_allowed,
					'reward_registration_allowed' => $reward_registration_allowed
				);
				$this->context->smarty->assign($smarty_values);
			}
			// si popup
			else {
				$smarty_values = array(
					'canSendInvitations' => true,
					'aior_popup' => true,
					'afterSubmit' => Tools::getValue('conditionsValided')
				);
				$this->context->smarty->assign($smarty_values);
			}
		}

		if (version_compare(_PS_VERSION_, '1.7', '<'))
			$this->setTemplate('sponsorship.tpl');
		else {
			if (!$popup)
				$this->setTemplate('module:allinone_rewards/views/templates/front/presta-1.7/sponsorship-full.tpl');
			else
				$this->setTemplate('module:allinone_rewards/views/templates/front/presta-1.7/sponsorship.tpl');
		}
	}
}