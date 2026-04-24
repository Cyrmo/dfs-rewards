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

class Allinone_rewardsProduct_PurchaseModuleFrontController extends ModuleFrontController
{
	public $content_only = true;
	public $display_header = false;
	public $display_footer = false;

	public function initContent()
	{
		parent::initContent();
		if (Tools::getValue('id_product')) {
			if (Tools::getValue('action')=='purchase')
				echo $this->module->core->purchaseProductFromRewards((int)Tools::getValue('id_product'), (int)Tools::getValue('id_product_attribute'));
			else
				echo $this->module->core->displayPurchaseButtonOnProductPage((int)Tools::getValue('id_product'), (int)Tools::getValue('id_product_attribute'));
		}
		exit;
	}
}