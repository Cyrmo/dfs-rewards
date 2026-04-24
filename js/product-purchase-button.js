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

var aior_loading = false;
if (typeof functions_to_load != 'undefined')
	functions_to_load.push('loadProductButton()');

jQuery(function($){
	// Pour les listes de produits
	if ($('#aior_add_to_cart_available_real').length > 0) {
		$(document).off('click', '.aior_add_to_cart').on('click', '.aior_add_to_cart', function(e) {
			e.preventDefault();
			var idProduct =  parseInt($(this).data('id-product'));
			var idProductAttribute =  parseInt($(this).data('id-product-attribute'));
			var price = $(this).data('aior-product-price-display');
			aior_add_to_cart(idProduct, idProductAttribute, price, false);
		});
	}

	// Pour la fiche produit
	$(document).off('click', '#aior_add_to_cart').on('click', '#product #aior_add_to_cart', function(e) {
		e.preventDefault();
		aior_add_to_cart($('#product_page_product_id').val(), aior_id_product_attribute, $('#aior_add_to_cart_price').html(), true);
	});
});

function aior_add_to_cart(idProduct, idProductAttribute, rewards, addedFromProductPage) {
	if (!aior_loading) {
		aior_loading = true;
		message = aior_purchase_confirm_message0+'\n\n'+aior_purchase_confirm_message1+' '+$('#aior_add_to_cart_available_display').html()+'.\n\n'+rewards+' '+aior_purchase_confirm_message2+(addedFromProductPage ? '\n'+aior_purchase_confirm_message3+' '+$('#aior_add_to_cart_available_after').html()+'.' : '')+'\n\n'+aior_purchase_confirm_message4;
		if (confirm(message.replace(/&nbsp;/g, ' '))) {
			$.ajax({
				type	: 'POST',
				cache	: false,
				url		: aior_product_purchase_url,
				dataType: 'json',
				data 	: 'ajax=true&action=purchase&id_product='+idProduct+'&id_product_attribute='+idProductAttribute,
				success: function(jsonData,textStatus,jqXHR) {
					if (addedFromProductPage)
						loadProductButton();
					else {
						$('#aior_add_to_cart_available_display').html(jsonData.aior_total_available_display);
						$('#aior_add_to_cart_available_real').html(jsonData.aior_total_available_real);
						$('.aior_add_to_cart').each(function() {
							if ($(this).data('aior-product-price-real') > jsonData.aior_total_available_real)
								$(this).css('display', 'none');
						});
					}

					if (!jsonData.has_error) {
						if (window.ajaxCart != undefined) {
							//$('#cart_block_list dl.products, .cart_block_list dl.products').remove();
							ajaxCart.refresh();
						}

						// presta 1.7
						if (window.prestashop != undefined) {
							prestashop.emit('updateCart', {
								reason: {
					              	idProduct: idProduct,
					              	idProductAttribute: idProductAttribute,
					              	linkAction: 'add-to-cart',
					              	cart: prestashop.cart
					            }
	      					});
						} else
							$("#header #cart_block, .shopping_cart .cart_block").stop(true, true).slideDown(450);

						displayMsg(aior_success_message+'<br><br>'+aior_success_message2+' '+jsonData.aior_total_available_display+'.');
					} else
						displayMsg(jsonData.error_msg);
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					var error = "Impossible to add the product to the cart.<br/>textStatus: '" + textStatus + "'<br/>errorThrown: '" + errorThrown + "'<br/>responseText:<br/>" + XMLHttpRequest.responseText;
					displayMsg(error);
				}
			});
		}
		aior_loading = false;
	}
}

function loadProductButton() {
	// if combination doesnt exist in back-end or quantity==0 and order out of stock is not allowed
	// or if product without combination and quantity==0 and order out of stock is not allowed

	// presta 1.7
	if (window.prestashop != undefined) {
		var hide = aior_combination_minimal_quantity > 1 || (!aior_allow_oosp && aior_combination_quantity==0);
	} else {
		var hide = (combinations.length > 0 && (aior_id_product_attribute==0 || aior_combination_minimal_quantity > 1 || (!aior_allow_oosp && aior_combination_quantity==0))) || (combinations.length == 0 && $('#add_to_cart:visible').length == 0 && !aior_allow_oosp);
	}

	if (hide)
		$('#aior_product_button').hide();
	else {
		$.ajax({
			type	: 'POST',
			cache	: false,
			url		: aior_product_purchase_url,
			dataType: 'json',
			data 	: 'ajax=true&id_product='+$('#product_page_product_id').val()+'&id_product_attribute='+aior_id_product_attribute,
			success: function(jsonData,textStatus,jqXHR) {
				if (!jsonData.has_error) {
					$('#aior_add_to_cart_price').html(jsonData.aior_product_price_display);
					$('#aior_add_to_cart_available_display').html(jsonData.aior_total_available_display);
					$('#aior_add_to_cart_available_real').html(jsonData.aior_total_available_real);
					$('#aior_add_to_cart_available_after').html(jsonData.aior_total_available_after);
					$('#aior_product_button').show();
				} else
					$('#aior_product_button').hide();
				if (!jsonData.aior_show_buy_button) {
					$('#loyalty').addClass('aior_unvisible');
					// presta 1.5 and 1.6
					$('#quantity_wanted_p').addClass('aior_unvisible');
					$('#add_to_cart').addClass('aior_unvisible');
					// presta 1.7
					$('.product-add-to-cart').addClass('aior_unvisible');
				} else {
					$('#loyalty').removeClass('aior_unvisible');
					// presta 1.5 and 1.6
					$('#quantity_wanted_p').removeClass('aior_unvisible');
					$('#add_to_cart').removeClass('aior_unvisible');
					// presta 1.7
					$('.product-add-to-cart').removeClass('aior_unvisible');
				}
			}
		});
	}
}

function displayMsg(msg) {
	if (!!$.prototype.fancybox) {
		$.fancybox(
			[
				{
					'content' : '<p class="fancybox-error">' + msg + '</p>'
				}
			]
		);
	} else
		alert(msg.replace(/&nbsp;/g, ' ').replace(/<br>/g, '\n'));
}