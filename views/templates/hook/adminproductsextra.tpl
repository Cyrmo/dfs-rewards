{*
* All-in-one Rewards Module
*
* @category  Prestashop
* @category  Module
* @author    Yann BONNAILLIE - ByWEB
* @copyright 2012-2020 Yann BONNAILLIE - ByWEB (http://www.prestaplugins.com)
* @license   Commercial license see license.txt
* Support by mail  : contact@prestaplugins.com
* Support on forum : Patanock
* Support on Skype : Patanock13
*}
<!-- MODULE allinone_rewards -->
<div id="aior_panel">
	<div class="panel">
	{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}
		<h4>{l s='Can this product be purchased with rewards ?' mod='allinone_rewards'}</h4>
		<div>
		{if !isset($gift_allowed)}
			{l s='Customizable products or products with minimal quantity > 1 are not allowed' mod='allinone_rewards'}
		{else}
			<table>
				<tr>
					<td class="col-left">
						<label>{l s='Behavior' mod='allinone_rewards'}</label>
					</td>
					<td>
						<ul class="listForm">
							<li>
								<label class="t" for="rewards_gift_behavior_default">
									<input {if $gift_allowed == -1}checked{/if} type="radio" id="rewards_gift_behavior_default" name="rewards_gift_behavior" value="-1" /> {l s='Default (If the product is in a category selected in the settings of the module)' mod='allinone_rewards'}
								</label>
							</li>
							<li>
								<label class="t" for="rewards_gift_behavior_off">
									<input {if $gift_allowed == 0}checked{/if} type="radio" id="rewards_gift_behavior_off" name="rewards_gift_behavior" value="0" /> {l s='No' mod='allinone_rewards'}
								</label>
							</li>
							<li>
								<label class="t" for="rewards_gift_behavior_on">
									<input {if $gift_allowed == 1}checked{/if} type="radio" id="rewards_gift_behavior_on" name="rewards_gift_behavior" value="1" /> {l s='Yes, and I can choose which combinations' mod='allinone_rewards'}
								</label>
							</li>
						</ul>
					</td>
				</tr>
			</table>
		{/if}
	{else}
		<div class="panel-heading">{l s='Can this product be purchased with rewards ?' mod='allinone_rewards'}</div>
		<div class="panel-body">
			<div class="form-group">
				{if !isset($gift_allowed)}
				{l s='Customizable products or products with minimal quantity > 1 are not allowed' mod='allinone_rewards'}
				{else}
				<label class="control-label col-lg-3">{l s='Behavior' mod='allinone_rewards'}</label>
				<div class="col-lg-9">
					<p class="radio">
						<label for="rewards_gift_behavior_default">
							<input {if $gift_allowed == -1}checked{/if} type="radio" id="rewards_gift_behavior_default" name="rewards_gift_behavior" value="-1" />{l s='Default (If the product is in a category selected in the settings of the module)' mod='allinone_rewards'}
						</label>
					</p>
					<p class="radio">
						<label for="rewards_gift_behavior_off">
							<input {if $gift_allowed == 0}checked{/if} type="radio" id="rewards_gift_behavior_off" name="rewards_gift_behavior" value="0" />{l s='No' mod='allinone_rewards'}
						</label>
					</p>
					<p class="radio">
						<label for="rewards_gift_behavior_on">
							<input {if $gift_allowed == 1}checked{/if} type="radio" id="rewards_gift_behavior_on" name="rewards_gift_behavior" value="1" />{l s='Yes, and I can choose which combinations' mod='allinone_rewards'}
						</label>
					</p>
				</div>
				{/if}
			</div>
	{/if}
			<div id="rewards_gift_combinations" {if !isset($gift_allowed) || $gift_allowed <= 0}style="display: none"{/if}>
				{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}
				<h4>{l s='List of combinations' mod='allinone_rewards'}</h4>
				{else if version_compare($smarty.const._PS_VERSION_,'1.7','<')}
				<h3>{l s='List of combinations' mod='allinone_rewards'}</h3>
				{else}
				<h2>{l s='List of combinations' mod='allinone_rewards'}</h2>
				{/if}
				<table class="table">
					<thead>
						<tr>
							<th><span class="title_box">{l s='Product name' mod='allinone_rewards'}</span></th>
							<th style="text-align: center"><span class="title_box">{l s='Can be bought with rewards ?' mod='allinone_rewards'}</span></th>
							<th style="text-align: center"><span class="title_box">{l s='Can be bought normally ?' mod='allinone_rewards'}</span></th>
						</tr>
					</thead>
					<tbody>
				{foreach $product_combinations as $id_product_attribute => $product_combination}
						<tr>
							<td>{$product_combination.name|escape:'htmlall':'UTF-8'}</td>
							<td style="text-align: center"><input {if $product_combination.gift_allowed}checked{/if} type="checkbox" name="rewards_gift_allowed[]" value="{$id_product_attribute|intval}"></td>
							<td style="text-align: center"><input {if $product_combination.purchase_allowed}checked{/if} type="checkbox" name="rewards_purchase_allowed[]" value="{$id_product_attribute|intval}" {if !$product_combination.gift_allowed}style="display: none"{/if}></td>
						</tr>
				{/foreach}
					</tbody>
				</table>
			</div>
			<div class="panel-footer-right panel-footer {if version_compare($smarty.const._PS_VERSION_,'1.6','<')}margin-form{/if}">
				<br/><button class="button btn btn-default pull-right" id="submitRewardGift" type="button"><i class="process-icon-save"></i> {l s='Save' mod='allinone_rewards'}</button>
			</div>
		</div>
	</div>
	{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}
	<div class="separation"></div>
	{/if}

	<div class="panel" style="margin-top: 50px">
		{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}
		<h4>{l s='Rewards granted when purchasing this product normally' mod='allinone_rewards'}</h4>
		{else}
		<div class="panel-heading">{l s='Rewards granted when purchasing this product normally' mod='allinone_rewards'}</div>
		{/if}
		<div class="panel-body">
			<div class="panel">
			{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}
				<h4 id="new_reward">{l s='Create a new reward' mod='allinone_rewards'}</h4>
				<h4 id="update_reward" style="display: none">{l s='Update a reward' mod='allinone_rewards'}</h4>
			{else}
				<div class="panel-heading" id="new_reward">{l s='Create a new reward' mod='allinone_rewards'}</div>
				<div class="panel-heading" id="update_reward" style="display: none">{l s='Update a reward' mod='allinone_rewards'}</div>
			{/if}
				<div class="panel-body">
					<div class="form-group">
						<label class="control-label col-lg-3">{l s='Type of reward' mod='allinone_rewards'}</label>
						<div class="{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}margin-form{/if}">
							<input type="hidden" name="reward_product_id" id="reward_product_id">
							<select id="reward_product_plugin" name="reward_product_plugin" style="width: 200px" class="form-control">
								<option value="loyalty">{l s='Loyalty' mod='allinone_rewards'}</option>
								<option value="sponsorship">{l s='Sponsorship' mod='allinone_rewards'}</option>
							</select>
						</div>
					</div>
					<div class="form-group" id="level" style="display: none">
						<label class="control-label col-lg-3">{l s='Level of the sponsor' mod='allinone_rewards'}</label>
						<div class="{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}margin-form{/if}">
							<select id="reward_product_level" name="reward_product_level" style="width: 60px" class="form-control">
								{for $val=1 to 100}
								<option value="{$val|intval}">{$val|intval}</option>
								{/for}
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-lg-3">{l s='Value' mod='allinone_rewards'}</label>
						<div class="{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}margin-form{/if}">
							<input type="text" name="reward_product_value" id="reward_product_value" style="width: 80px; margin-right: 5px; display: inline-block" class="form-control">
							<select id="reward_product_type" name="reward_product_type" style="width: 200px; display: inline-block" class="form-control">
								<option value="0">% {l s='of its own price' mod='allinone_rewards'}</option>
								<option value="1">{$currency->sign|escape:'html':'UTF-8'}</option>
							</select>
							&nbsp;<span id="virtualvalue"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-lg-3">{l s='Dates' mod='allinone_rewards'}</label>
						<div class="input-group {if version_compare($smarty.const._PS_VERSION_,'1.6','<')}margin-form{/if}">
							<div>
								<div style="display: inline-block; padding-right: 5px">
									<div class="input-group">
										<span class="input-group-addon">{l s='from' mod='allinone_rewards'}</span>
										<input type="text" id="reward_product_from" style="text-align: center" name="reward_product_from" class="datetimepicker form-control">
										{if version_compare($smarty.const._PS_VERSION_,'1.7','<')}
										<span class="input-group-addon"><i class="icon-calendar-empty"></i></span>
										{else}
										<span class="input-group-addon"><i class="material-icons">date_range</i></span>
										{/if}
									</div>
								</div>
								<div style="display: inline-block">
									<div class="input-group">
										<span class="input-group-addon">{l s='to' mod='allinone_rewards'}</span>
										<input type="text" id="reward_product_to" style="text-align: center" name="reward_product_to" class="datetimepicker form-control">
										{if version_compare($smarty.const._PS_VERSION_,'1.7','<')}
										<span class="input-group-addon"><i class="icon-calendar-empty"></i></span>
										{else}
										<span class="input-group-addon"><i class="material-icons">date_range</i></span>
										{/if}
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="panel-footer {if version_compare($smarty.const._PS_VERSION_,'1.6','<')}margin-form{/if}">
						<button class="button btn btn-default" id="cancelRewardProduct" type="button"><i class="process-icon-save"></i> {l s='Cancel' mod='allinone_rewards'}</button>
						<button class="button btn btn-default pull-right" id="submitRewardProduct" type="button"><i class="process-icon-save"></i> {l s='Save' mod='allinone_rewards'}</button>
					</div>
				</div>
			</div>

			<div class="panel" style="margin-top: 50px">
				{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}
				<h4>{l s='List of rewards for the loyalty program' mod='allinone_rewards'}</h4>
				{else}
				<div class="panel-heading">{l s='List of rewards for the loyalty program' mod='allinone_rewards'}</div>
				{/if}
				<div class="panel-body">
					<table class="table reward_product_list" id="reward_product_loyalty" {if version_compare($smarty.const._PS_VERSION_,'1.6','<')}style="width: 100%"{/if}>
						<thead>
							<tr>
								<th>{l s='Reward value' mod='allinone_rewards'}</th>
								<th>{l s='Reward date from' mod='allinone_rewards'}</th>
								<th>{l s='Reward date to' mod='allinone_rewards'}</th>
								<th>{l s='Action' mod='allinone_rewards'}</th>
							</tr>
						</thead>
						<tbody>
			{if $product_loyalty_rewards|@count > 0}
				{foreach from=$product_loyalty_rewards item=product_reward name=myLoop}
							<tr id="{$product_reward.id_reward_product|intval}">
								<td><span class="reward_value">{$product_reward.value|floatval}</span> <span class="reward_type">{if $product_reward.type==0}%{else}{$currency->sign|escape:'html':'UTF-8'}{/if}</span></td>
								<td class="reward_from">{if isset($product_reward.date_from) && $product_reward.date_from != 0}{$product_reward.date_from|escape:'html':'UTF-8'}{/if}</td>
								<td class="reward_to">{if isset($product_reward.date_to) && $product_reward.date_to != 0}{$product_reward.date_to|escape:'html':'UTF-8'}{/if}</td>
								<td>
									{if version_compare($smarty.const._PS_VERSION_,'1.7','<')}
										<img style="cursor: pointer" class="edit_reward" src="../img/admin/edit.gif">
										<img style="cursor: pointer" class="delete_reward" src="../img/admin/delete.gif">
									{else}
										<i class="material-icons edit_reward">edit</i>
										<i class="material-icons delete_reward">delete</i>
									{/if}
								</td>
							</tr>
				{/foreach}
			{/if}
							<tr id="0" style="display: none">
								<td colspan="4" align="center">{l s='No reward is defined for that product' mod='allinone_rewards'}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<div class="panel" style="margin-top: 50px">
				{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}
				<h4>{l s='List of rewards for the sponsorship program' mod='allinone_rewards'}</h4>
				{else}
				<div class="panel-heading">{l s='List of rewards for the sponsorship program' mod='allinone_rewards'}</div>
				{/if}
				<div class="panel-body">
					<table class="table reward_product_list" id="reward_product_sponsorship" {if version_compare($smarty.const._PS_VERSION_,'1.6','<')}style="width: 100%"{/if}>
						<thead>
							<tr>
								<th>{l s='Level of the sponsor' mod='allinone_rewards'}</th>
								<th>{l s='Reward value' mod='allinone_rewards'}</th>
								<th>{l s='Reward date from' mod='allinone_rewards'}</th>
								<th>{l s='Reward date to' mod='allinone_rewards'}</th>
								<th>{l s='Action' mod='allinone_rewards'}</th>
							</tr>
						</thead>
						<tbody>
			{if $product_sponsorship_rewards|@count > 0}
				{foreach from=$product_sponsorship_rewards item=product_reward name=myLoop}
							<tr id="{$product_reward.id_reward_product|intval}">
								<td class="reward_level">{$product_reward.level|intval}</td>
								<td><span class="reward_value">{$product_reward.value|floatval}</span> <span class="reward_type">{if $product_reward.type==0}%{else}{$currency->sign|escape:'html':'UTF-8'}{/if}</span></td>
								<td class="reward_from">{if isset($product_reward.date_from) && $product_reward.date_from != 0}{$product_reward.date_from|escape:'html':'UTF-8'}{/if}</td>
								<td class="reward_to">{if isset($product_reward.date_to) &&  $product_reward.date_to != 0}{$product_reward.date_to|escape:'html':'UTF-8'}{/if}</td>
								<td>
									{if version_compare($smarty.const._PS_VERSION_,'1.7','<')}
										<img style="cursor: pointer" class="edit_reward" src="../img/admin/edit.gif">
										<img style="cursor: pointer" class="delete_reward" src="../img/admin/delete.gif">
									{else}
										<i class="material-icons edit_reward">edit</i>
										<i class="material-icons delete_reward">delete</i>
									{/if}
								</td>
							</tr>
				{/foreach}
			{/if}
							<tr id="0" style="display: none">
								<td colspan="5" align="center">{l s='No reward is defined for that product' mod='allinone_rewards'}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	var product_rewards_url = "{$product_rewards_url|escape:'javascript':'UTF-8'}";
	var delete_reward_label = "{l s='Do you really want to delete this reward ?' mod='allinone_rewards'}";
	var delete_reward_title = "{l s='Delete reward' mod='allinone_rewards'}";
	var currency_sign = '{$currency->sign|escape:'html':'UTF-8'}';

	var virtual_value = {$virtual_value|floatval};
	var virtual_name = "{$virtual_name|escape:'html':'UTF-8'}";

	manageEmptyRow();

{if version_compare($smarty.const._PS_VERSION_,'1.7','<')}
	$('#aior_panel .datetimepicker').datetimepicker({
		prevText: '',
		nextText: '',
		dateFormat: 'yy-mm-dd',
		// Define a custom regional settings in order to use PrestaShop translation tools
		currentText: '{l s='Now' mod='allinone_rewards'}',
		closeText: '{l s='Done' mod='allinone_rewards'}',
		ampm: false,
		amNames: ['AM', 'A'],
		pmNames: ['PM', 'P'],
		timeFormat: 'hh:mm:ss tt',
		timeSuffix: '',
		timeOnlyTitle: "{l s='Choose Time' mod='allinone_rewards'}",
		timeText: '{l s='Time' mod='allinone_rewards'}',
		hourText: '{l s='Hour' mod='allinone_rewards'}',
		minuteText: '{l s='Minute' mod='allinone_rewards'}',
		secondText: '{l s='Second' mod='allinone_rewards'}',
		showSecond: true
	});
{else}
	$('#aior_panel .datetimepicker').datetimepicker({
      	locale: iso_user,
		format: 'YYYY-MM-DD HH:mm:ss',
		sideBySide: true,
		icons: {
            time: 'fa fa-clock-o',
            date: 'fa fa-calendar',
            up: 'fa fa-arrow-up',
        	down: 'fa fa-arrow-down'
        },
        tooltips: {
		    selectMonth: '',
		    prevMonth: '',
		    nextMonth: '',
		    selectYear: '',
		    prevYear: '',
		    nextYear: '',
		    incrementHour: '',
		    decrementHour:'',
		    incrementMinute: '',
		    decrementMinute:'',
		    incrementSecond: '',
		    decrementSecond:'',
		    pickHour: '{l s='Hour' mod='allinone_rewards'}',
		    pickMinute: '{l s='Minute' mod='allinone_rewards'}',
		    pickSecond: '{l s='Second' mod='allinone_rewards'}',
		}
    });
{/if}
</script>
<!-- END : MODULE allinone_rewards -->