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

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/allinone_rewards.php');

$hooks = array(
	'displayHeader', 'displayTop', 'displayFooter', 'displayLeftColumn', 'displayRightColumn', 'displayShoppingCartFooter',
	'displayCustomerAccount', 'displayCustomerAccountForm', 'displayCustomerAccountFormTop', 'displayMyAccountBlock', 'displayMyAccountBlockFooter',
	'displayProductButtons', 'displayProductPriceBlock', 'displayRightColumnProduct', 'displayLeftColumnProduct',
	'displayOrderConfirmation',
	'actionCartSave', 'actionValidateOrder', 'actionOrderStatusUpdate', 'actionCustomerAccountAdd',
	'displayAdminCustomers', 'displayAdminProductsExtra', 'displayAdminOrder',
	'displayPDFInvoice',
	'ActionAdminControllerSetMedia',
	'actionObjectCustomerDeleteAfter', 'actionObjectProductDeleteAfter', 'actionObjectOrderDetailAddAfter', 'actionObjectOrderDetailUpdateAfter', 'actionObjectOrderDetailDeleteAfter'
);
if (version_compare(_PS_VERSION_, '1.6.1.0', '<='))
	$hooks[] = 'displayProductListReviews';
if (version_compare(_PS_VERSION_, '1.6.0.12', '>='))
	$hooks[] = 'displayNav';
if (version_compare(_PS_VERSION_, '1.7', '>=')) {
	$hooks[] = 'additionalCustomerFormFields';
	$hooks[] = 'displayNav1';
	$hooks[] = 'displayNav2';
	$hooks[] = 'actionFrontControllerSetMedia';
	$hooks[] = 'displayBeforeBodyClosingTag';
}
if (version_compare(_PS_VERSION_, '1.7.1.0', '>='))
	$hooks[] = 'displayProductAdditionalInfo';

$result = array();
$module = new allinone_rewards();

if (Tools::getValue('fix') == 1) {
	foreach($hooks as $hook) {
		if (!$module->isRegisteredInHook($hook)) {
			$result[] = $hook.' is missing';
			if ($module->registerHook($hook))
				$result[] = $hook.' has been added';
		}
	}
} else {
	foreach($hooks as $hook) {
		if (!$module->isRegisteredInHook($hook))
			$result[] = $hook.' est manquant';
	}
}
if (count($result) > 0)
	echo implode('<br>', $result);
else
	echo "tout est ok";