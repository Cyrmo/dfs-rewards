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

jQuery(function($){
	$('#product-tab-content-ModuleAllinone_rewards, #module_allinone_rewards').delegate('.delete_reward', 'click', function(){
		var row = $(this).parents('tr');
		if (_PS_VERSION_.startsWith('1.7')) {
			modalConfirmation.create(delete_reward_label, delete_reward_title, {
			 	onContinue: function() {
				    deleteReward(row);
			 	}
			}).show();
		} else {
			jConfirm(delete_reward_label, delete_reward_title, function(r) {
			    if (r)
					deleteReward(row);
			});
		}
	});

	$('#product-tab-content-ModuleAllinone_rewards, #module_allinone_rewards').delegate('.edit_reward', 'click', function(){
		$('#new_reward').hide();
		$('#update_reward').show();
		var row = $(this).parents('tr');
		$('#reward_product_id').val(row.attr('id'));
		$('#reward_product_value').val(row.find('.reward_value').html());
		$('#reward_product_from').val(row.find('.reward_from').html());
		$('#reward_product_to').val(row.find('.reward_to').html());
		if (row.parents('table').attr('id') == 'reward_product_loyalty') {
			$('#level').hide();
			$('#reward_product_level').val(1);
			$('#reward_product_plugin').val('loyalty');
		} else {
			$('#reward_product_level').val(row.find('.reward_level').html());
			$('#level').show();
			$('#reward_product_plugin').val('sponsorship');
		}
		if (row.find('.reward_type').html() == '%')
			$('#reward_product_type').val(0);
		else
			$('#reward_product_type').val(1);
		showVirtualValue();
	});

	$('#product-tab-content-ModuleAllinone_rewards, #module_allinone_rewards').delegate('#submitRewardProduct', 'click', function(){
		$.ajax({
			type	: 'POST',
			cache	: false,
			url		: product_rewards_url,
			data 	: 'action=submit_reward&reward_product_id='+$('#reward_product_id').val()+'&reward_product_value='+$('#reward_product_value').val()+'&reward_product_type='+$('#reward_product_type').val()+'&reward_product_from='+$('#reward_product_from').val()+'&reward_product_to='+$('#reward_product_to').val()+'&reward_product_plugin='+$('#reward_product_plugin').val()+'&reward_product_level='+$('#reward_product_level').val(),
			dataType: 'json',
			success : function(data) {
				if (data.error)
					alert(data.error);
				else {
					$('.reward_product_list tr[id='+data.reward_product.id+']').remove().fadeIn(1000);
					var r = '<tr style="display: none" id="'+data.reward_product.id+'">'+($('#reward_product_plugin').val() == 'sponsorship' ? '<td class="reward_level">'+data.reward_product.level+'</td>' : '')+'<td><span class="reward_value">'+(data.reward_product.value * 1).toFixed(2)+'</span> <span class="reward_type">'+(data.reward_product.type==0 ? '%' : currency_sign)+'</span></td><td class="reward_from">'+$('#reward_product_from').val()+'</td><td class="reward_to">'+$('#reward_product_to').val()+'</td><td>'+(_PS_VERSION_.startsWith('1.7') ? '<i class="material-icons edit_reward">edit</i><i class="material-icons delete_reward">delete</i>' : '<img style="cursor: pointer" class="edit_reward" src="../img/admin/edit.gif"><img style="cursor: pointer" class="delete_reward" src="../img/admin/delete.gif">')+'</td></tr>';
					$row = $(r);
					$row.prependTo('#reward_product_'+$('#reward_product_plugin').val()+' tbody').fadeIn(1000);
					resetRewardProduct();
				}
				manageEmptyRow();
			}
		});
	});

	$('#product-tab-content-ModuleAllinone_rewards, #module_allinone_rewards').delegate('#cancelRewardProduct', 'click', function(){
		resetRewardProduct();
	});

	$('#product-tab-content-ModuleAllinone_rewards, #module_allinone_rewards').delegate('#reward_product_type', 'click', function(){
		showVirtualValue();
	});

	$('#product-tab-content-ModuleAllinone_rewards, #module_allinone_rewards').delegate('#reward_product_value', 'blur', function(){
		showVirtualValue();
	});

	$('#product-tab-content-ModuleAllinone_rewards, #module_allinone_rewards').delegate('#reward_product_plugin', 'click', function(){
		if ($(this).val() == 'loyalty')
			$('#level').hide();
		else
			$('#level').show();
	});

	$('#product-tab-content-ModuleAllinone_rewards, #module_allinone_rewards').delegate('input[name="rewards_gift_behavior"]', 'click', function(){
		if ($(this).val() <= 0)
			$('#rewards_gift_combinations').hide();
		else
			$('#rewards_gift_combinations').show();
	});

	$('#product-tab-content-ModuleAllinone_rewards, #module_allinone_rewards').delegate('input[name^="rewards_gift_allowed"]', 'click', function(){
		if ($(this).is(':checked'))
			$(this).parents('tr').find('input[name^="rewards_purchase_allowed"]').show();
		else
			$(this).parents('tr').find('input[name^="rewards_purchase_allowed"]').hide().attr('checked', true);
	});

	$('#product-tab-content-ModuleAllinone_rewards, #module_allinone_rewards').delegate('#submitRewardGift', 'click', function(){
		$.ajax({
			type	: 'POST',
			cache	: false,
			url		: product_rewards_url,
			data 	: 'action=submit_reward_gift&'+$('#product_form, #form').serialize(),
			dataType: 'json',
			success : function(data) {
				if (_PS_VERSION_.startsWith('1.5')) {
					displayMsg(data.msg);
				} else {
					$.growl.notice({
		                 title: "",
		                 size: "large",
		                 message: data.msg
		            });
				}
			}
		});
	});
});

function deleteReward(row) {
	$.ajax({
		type	: 'POST',
		cache	: false,
		url		: product_rewards_url,
		data 	: 'action=delete_reward&reward_product_id='+row.attr('id'),
		dataType: 'json',
		success : function(data) {
			if (data && data.error)
				alert(data.error);
			else
				row.remove().fadeIn(1000);
			manageEmptyRow();
		}
	});
}

function showVirtualValue() {
	if ($('#reward_product_type').val() == 1 && !isNaN($('#reward_product_value').val()))
		$('#virtualvalue').html('('+($('#reward_product_value').val() * virtual_value).toFixed(2)+' '+virtual_name+')').show();
	else
		$('#virtualvalue').html('');
}

function manageEmptyRow() {
	$('.reward_product_list').each(function() {
		if ($('tbody tr', $(this)).length == 1)
			$('tbody tr[id=0]', $(this)).fadeIn(1000);
		else
			$('tbody tr[id=0]', $(this)).hide();
	});
}

function resetRewardProduct() {
	$('#update_reward').hide();
	$('#level').hide();
	$('#new_reward').show();
	$('#reward_product_id').val('');
	$('#reward_product_value').val('');
	$('#reward_product_type').val(0);
	$('#reward_product_from').val('');
	$('#reward_product_to').val('');
	$('#reward_product_plugin').val('loyalty');
	$('#reward_product_level').val(1);
	showVirtualValue();
}

function displayMsg(msg) {
	if (!!$.prototype.fancybox) {
		$.fancybox(
			[
				{
					'content' : '<p class="fancybox-success">' + msg + '</p>'
				}
			]
		);
	} else
		alert(msg);
}