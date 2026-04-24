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

var aior_id_product_attribute = 0;
var aior_combination_quantity = 0;
var aior_combination_minimal_quantity = 0;
var aior_allow_oosp = 0;
var functions_to_load = new Array();

jQuery(function($){
	if (window.prestashop != undefined) {
		// presta 1.7
		prestashop.on('updatedProduct', function (event) {
			getAttributeForReward();
		});
	} else {
		// presta 1.5 to 1.6
		$(document).on('click', '.color_pick', function(e){
			getAttributeForReward();
		});

		$(document).on('change', '.attribute_select', function(e){
			getAttributeForReward();
		});

		$(document).on('click', '.attribute_radio', function(e){
			getAttributeForReward();
		});
	}
	getAttributeForReward();
});

function getAttributeForReward() {
	// presta 1.7
	if (window.prestashop != undefined) {
		if ($('#product-details').attr('data-product')) {
			var result = JSON.parse($('#product-details').attr('data-product'));
			aior_id_product_attribute = result.id_product_attribute;
			aior_combination_quantity = result.quantity;
			aior_combination_minimal_quantity = result.minimal_quantity;
			aior_allow_oosp = result.allow_oosp;
		} else
			console.log("All-in-one Rewards : ERROR IN YOUR THEME, attribute data-product doesn't exist on element #product-details");
	} else {
		//create a temporary 'choice' array containing the choices of the customer
		aior_allow_oosp = allowBuyWhenOutOfStock;
		aior_id_product_attribute = 0;
		aior_combination_quantity = 0;
		aior_combination_minimal_quantity = 0;
		var choice = [];
		var radio_inputs = parseInt($('#attributes .checked > input[type=radio]').length);
		if (radio_inputs)
			radio_inputs = '#attributes .checked > input[type=radio]';
		else
			radio_inputs = '#attributes input[type=radio]:checked';

		$('#attributes select, #attributes input[type=hidden], ' + radio_inputs).each(function(){
			choice.push(parseInt($(this).val()));
		});

		if (typeof combinations == 'undefined' || !combinations)
			combinations = [];

		//testing every combination to find the combination's ID choosen by the user
		for (var combination = 0; combination < combinations.length; ++combination){
			//verify if this combinaison is the same that the user's choice
			var combinationMatchForm = true;
			$.each(combinations[combination]['idsAttributes'], function(key, value){
				if (!in_array(parseInt(value), choice)) {
					combinationMatchForm = false;
					return;
				}
			});

			if (combinationMatchForm) {
				aior_id_product_attribute = combinations[combination]['idCombination'];
				aior_combination_quantity = combinations[combination]['quantity'];
				aior_combination_minimal_quantity = combinations[combination]['minimal_quantity'];
				break;
			}
		}
	}

	for (var i = 0; i < functions_to_load.length; i++)
		eval(functions_to_load[i]);
}