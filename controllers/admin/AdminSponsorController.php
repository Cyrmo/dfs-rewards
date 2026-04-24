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

class AdminSponsorController extends ModuleAdminController
{
	public function postProcess()
	{
		die(Tools::jsonEncode(RewardsSponsorshipModel::getAvailableSponsors(Tools::getValue('id_customer'), version_compare(_PS_VERSION_, '1.6', '>=') ? Tools::getValue('q') : Tools::getValue('term'))));
	}
}