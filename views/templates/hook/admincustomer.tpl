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
<div class="{if version_compare($smarty.const._PS_VERSION_,'1.6','>=')}col-lg-12{else}clear{/if}" id="admincustomer">
{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}
	<h2>{l s='Rewards account' mod='allinone_rewards'}</h2>
	{if $msg}{$msg|escape:'string':'UTF-8'}<br>{/if}
{else if version_compare($smarty.const._PS_VERSION_,'1.7.6','<')}
	<div class="panel">
		<div class="panel-heading">{l s='Rewards account' mod='allinone_rewards'}</div>
		{if $msg}{$msg|escape:'string':'UTF-8'}{/if}
{else}
	<div class="card">
		<h3 class="card-header">{l s='Rewards account' mod='allinone_rewards'}</h3>
		{if $msg}{$msg|escape:'string':'UTF-8'}{/if}
{/if}
		<div class="{if version_compare($smarty.const._PS_VERSION_,'1.7.6','>=')}card-body{/if}">
			<form id="template_change" method="post">
				<input type="hidden" name="action" />
				{l s='Template used for "Rewards account"' mod='allinone_rewards'}&nbsp;
				<select class="change_template {if version_compare($smarty.const._PS_VERSION_,'1.7.6','>=')}form-control{/if}" name="core_template" style="display: inline; width: auto;">
					<option value='0'>{l s='Default template' mod='allinone_rewards'}</option>
					{foreach from=$core_templates item=template}
						<option {if $template['id_template']==$core_template_id}selected{/if} value='{$template['id_template']|intval}'>{$template['name']|escape:'htmlall':'UTF-8'}</option>
					{/foreach}
				</select>
				<span style="padding-left: 50px">
					{l s='Template used for "Loyalty program"' mod='allinone_rewards'}&nbsp;
					<select class="change_template {if version_compare($smarty.const._PS_VERSION_,'1.7.6','>=')}form-control{/if}" name="loyalty_template" style="display: inline; width: auto;">
						<option value='0'>{l s='Default template' mod='allinone_rewards'}</option>
						{foreach from=$loyalty_templates item=template}
							<option {if $template['id_template']==$loyalty_template_id}selected{/if} value='{$template['id_template']|intval}'>{$template['name']|escape:'htmlall':'UTF-8'}</option>
						{/foreach}
					</select>
				</span>
			</form>
			<br/><br/>
{if $rewards|@count > 0}
			<form id="rewards_reminder" method="post">
	{if $rewards_account->remind_active}
				<input class="{if version_compare($smarty.const._PS_VERSION_,'1.7.6','>=')}btn btn-primary{else}button{/if}" name="submitRewardReminderOff" type="submit" value="{l s='Disable reminder email' mod='allinone_rewards'}" />
		{if (float)$totals[RewardsStateModel::getValidationId()] > 0}
				<input class="{if version_compare($smarty.const._PS_VERSION_,'1.7.6','>=')}btn btn-primary{else}button{/if}" name="submitRewardReminder" type="submit" value="{l s='Send an email with account balance :' mod='allinone_rewards'} {displayPrice price=$totals[RewardsStateModel::getValidationId()]}" /> {if $rewards_account->date_last_remind && $rewards_account->date_last_remind != '0000-00-00 00:00:00'} ({l s='last email :' mod='allinone_rewards'} {dateFormat date=$rewards_account->date_last_remind full=1}){/if}
		{/if}
	{else}
				<input class="{if version_compare($smarty.const._PS_VERSION_,'1.7.6','>=')}btn btn-primary{else}button{/if}" name="submitRewardReminderOn" type="submit" value="{l s='Enable reminder email' mod='allinone_rewards'}" />
	{/if}
			</form><br>
			<table cellspacing="0" cellpadding="0" class="table">
				<thead>
					<tr style="background-color: #EEEEEE">
						<th style='text-align: center'>{l s='Total rewards' mod='allinone_rewards'}</th>
						<th style='text-align: center'>{l s='Already converted' mod='allinone_rewards'}</th>
						<th style='text-align: center'>{l s='Paid' mod='allinone_rewards'}</th>
						<th style='text-align: center'>{l s='Available' mod='allinone_rewards'}</th>
						<th style='text-align: center'>{l s='Awaiting validation' mod='allinone_rewards'}</th>
						<th style='text-align: center'>{l s='Awaiting payment' mod='allinone_rewards'}</th>
					</tr>
				</thead>
				<tr>
					<td class="center">{displayPrice price=$totals['total']}</td>
					<td class="center">{displayPrice price=$totals[RewardsStateModel::getConvertId()]}</td>
					<td class="center">{displayPrice price=$totals[RewardsStateModel::getPaidId()]}</td>
					<td class="center">{displayPrice price=$totals[RewardsStateModel::getValidationId()]}</td>
					<td class="center">{displayPrice price=$totals[RewardsStateModel::getDefaultId()] + $totals[RewardsStateModel::getReturnPeriodId()]}</td>
					<td class="center">{displayPrice price=$totals[RewardsStateModel::getWaitingPaymentId()]}</td>
				</tr>
			</table>
{else}
			{l s='This customer has no reward' mod='allinone_rewards'}
{/if}

			<form id="rewards_history" method="post">
{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}
			<h3>{l s='Add a new reward' mod='allinone_rewards'}</h3>
{else if version_compare($smarty.const._PS_VERSION_,'1.7.6','<')}
			<div class="panel-heading" style="margin-top: 30px">{l s='Add a new reward' mod='allinone_rewards'}</div>
{else}
			<div class="card-header" style="margin-top: 30px">{l s='Add a new reward' mod='allinone_rewards'}</div>
{/if}
			{l s='Value' mod='allinone_rewards'} <input name="new_reward_value" type="text" class="{if version_compare($smarty.const._PS_VERSION_,'1.7.6','>=')}form-control{/if}" size="6" value="{$new_reward_value|floatval}" style="text-align: right; display: inline; width: auto"/> {$sign|escape:'html':'UTF-8'}&nbsp;&nbsp;&nbsp;&nbsp;
			{l s='Status' mod='allinone_rewards'} <select name="new_reward_state" class="{if version_compare($smarty.const._PS_VERSION_,'1.7.6','>=')}form-control{/if}" style="display: inline; width: auto">
				<option {if $new_reward_state == RewardsStateModel::getDefaultId()}selected{/if} value="{RewardsStateModel::getDefaultId()|intval}">{$rewardStateDefault|escape:'htmlall':'UTF-8'}</option>
				<option {if $new_reward_state == RewardsStateModel::getValidationId()}selected{/if} value="{RewardsStateModel::getValidationId()|intval}">{$rewardStateValidation|escape:'htmlall':'UTF-8'}</option>
				<option {if $new_reward_state == RewardsStateModel::getCancelId()}selected{/if} value="{RewardsStateModel::getCancelId()|intval}">{$rewardStateCancel|escape:'htmlall':'UTF-8'}</option>
			</select>&nbsp;&nbsp;&nbsp;&nbsp;
			{l s='Reason' mod='allinone_rewards'} <input name="new_reward_reason" type="text" class="{if version_compare($smarty.const._PS_VERSION_,'1.7.6','>=')}form-control{/if}" size="40" maxlength="80" value="{$new_reward_reason|escape:'htmlall':'UTF-8'}" style="display: inline; width: auto"/>
			<input class="{if version_compare($smarty.const._PS_VERSION_,'1.7.6','>=')}btn btn-primary{else}button{/if}" name="submitNewReward" type="submit" value="{l s='Save settings' mod='allinone_rewards'}"/>
{if $rewards|@count > 0}
	{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}
			<h3>{l s='Rewards history' mod='allinone_rewards'}</h3>
	{else if version_compare($smarty.const._PS_VERSION_,'1.7.6','<')}
			<div class="panel-heading" style="margin-top: 30px;">{l s='Rewards history' mod='allinone_rewards'}</div>
	{else}
			<div class="card-header" style="margin-top: 30px">{l s='Rewards history' mod='allinone_rewards'}</div>
	{/if}
			<input type="hidden" id="id_reward_to_update" name="id_reward_to_update" />
			<table cellspacing="0" cellpadding="0" class="tablesorter tablesorter-ice" id="rewards_list">
				<thead>
					<tr style="background-color: #EEEEEE">
						<th>{l s='Event' mod='allinone_rewards'}</th>
						<th>{l s='Date' mod='allinone_rewards'}</th>
						<th>{l s='Validity' mod='allinone_rewards'}</th>
						<th>{l s='Total (without shipping)' mod='allinone_rewards'}</th>
						<th>{l s='Reward' mod='allinone_rewards'}</th>
						<th>{l s='Status' mod='allinone_rewards'}</th>
						<th class='filter-false sorter-false'>{l s='Action' mod='allinone_rewards'}</th>
					</tr>
				</thead>
				<tbody>
	{foreach from=$rewards item=reward name=myLoop}
		{assign var="bUpdate" value="{in_array($reward['id_reward_state'], $states_for_update)|intval}"}
					<tr class="{if ($smarty.foreach.myLoop.iteration % 2) == 0}alt_row{/if}">
						<td>{if ($bUpdate && $reward['plugin'] == "free")}<input name="reward_reason_{$reward['id_reward']|intval}" type="text" size="30" maxlength="80" value="{$reward['detail']|escape:'html':'UTF-8'}" />{else}{$reward['detail']|escape:'string':'UTF-8'}{/if}</td>
						<td style="text-align: center">{$reward['date']|escape:'html':'UTF-8'}</td>
						<td style="text-align: center">{if $reward['id_reward_state']==RewardsStateModel::getValidationId() && $reward['date_end']!='0000-00-00 00:00:00'}{$reward['date_end']|escape:'html':'UTF-8'}{else}-{/if}</td>
						<td align="right" class="price">{if (int)$reward['id_order'] > 0}{displayPrice price=$reward['total_without_shipping'] currency=$reward['id_currency']}{else}-{/if}</td>
						<td align="right">
		{if $bUpdate}
							<input name="reward_value_{$reward['id_reward']|intval}" type="text" size="6" value="{$reward['credits']|string_format:'%.2f'}" style="text-align: right; display: inline; width: auto"/> {$sign|escape:'html':'UTF-8'}
		{else}
							{displayPrice price=$reward['credits']}
		{/if}
						</td>
						<td>
		{if $bUpdate}
							<select name="reward_state_{$reward['id_reward']|intval}" style="display: inline; width: auto">
								<option {if $reward['id_reward_state'] == RewardsStateModel::getDefaultId()}selected{/if} value="{RewardsStateModel::getDefaultId()|intval}">{$rewardStateDefault|escape:'htmlall':'UTF-8'}</option>
								<option {if $reward['id_reward_state'] == RewardsStateModel::getValidationId()}selected{/if} value="{RewardsStateModel::getValidationId()|intval}">{$rewardStateValidation|escape:'htmlall':'UTF-8'}</option>
								<option {if $reward['id_reward_state'] == RewardsStateModel::getCancelId()}selected{/if} value="{RewardsStateModel::getCancelId()|intval}">{$rewardStateCancel|escape:'htmlall':'UTF-8'}</option>
			{if ($reward['id_reward_state'] == RewardsStateModel::getReturnPeriodId() || ((int)$reward['id_order'] > 0 && Configuration::get('REWARDS_WAIT_RETURN_PERIOD') && Configuration::get('PS_ORDER_RETURN') && (int)Configuration::get('PS_ORDER_RETURN_NB_DAYS') > 0))}
								<option {if $reward['id_reward_state'] == RewardsStateModel::getReturnPeriodId()}selected{/if} value="{RewardsStateModel::getReturnPeriodId()|intval}">{$rewardStateReturnPeriod|escape:'htmlall':'UTF-8'} {l s='(Return period)' mod='allinone_rewards'}</option>
			{/if}
							</select>
		{else}
			{if $reward['id_reward_state']==RewardsStateModel::getConvertId()}
				{if $reward['order_cart_rule']}
							{l s='Used in order' mod='allinone_rewards'} <a href="index.php?tab=AdminOrders&id_order={$reward['order_cart_rule']}&vieworder&token={getAdminToken tab='AdminOrders'}">{$reward['reference']}</a>{if $reward['code']} (<a href="index.php?tab=AdminCartRules&id_cart_rule={$reward['id_cart_rule']}&addcart_rule&token={getAdminToken tab='AdminCartRules'}">{$reward['code']}</a>){/if}
				{else if $reward['date_cart_rule'] < date('Y-m-d H:i:s')}
							{l s='Expired voucher' mod='allinone_rewards'}{if $reward['code']} (<a href="index.php?tab=AdminCartRules&id_cart_rule={$reward['id_cart_rule']}&addcart_rule&token={getAdminToken tab='AdminCartRules'}">{$reward['code']}</a>){/if}
				{else}
							{$reward['state']|escape:'htmlall':'UTF-8'}{if $reward['code']} (<a href="index.php?tab=AdminCartRules&id_cart_rule={$reward['id_cart_rule']}&addcart_rule&token={getAdminToken tab='AdminCartRules'}">{$reward['code']}</a> {l s='is available' mod='allinone_rewards'}){/if}
				{/if}
			{else}
							{$reward['state']|escape:'htmlall':'UTF-8'}
			{/if}
		{/if}
						</td>
						<td style="text-align: center">{if $bUpdate}<input class="button" name="submitRewardUpdate" type="submit" value="{l s='Save settings' mod='allinone_rewards'}" onClick="$('#id_reward_to_update').val({$reward['id_reward']|intval})">{/if}</td>
					</tr>
	{/foreach}
				</tbody>
			</table>
			<div class="pager">
		    	<img src="{$module_template_dir|escape:'html':'UTF-8'}js/tablesorter/addons/pager/first.png" class="first"/>
		    	<img src="{$module_template_dir|escape:'html':'UTF-8'}js/tablesorter/addons/pager/prev.png" class="prev"/>
		    	<span class="pagedisplay"></span> <!-- this can be any element, including an input -->
		    	<img src="{$module_template_dir|escape:'html':'UTF-8'}js/tablesorter/addons/pager/next.png" class="next"/>
		    	<img src="{$module_template_dir|escape:'html':'UTF-8'}js/tablesorter/addons/pager/last.png" class="last"/>
		    	<select class="pagesize" style="width: auto; display: inline">
		      		<option value='10'>10</option>
		      		<option value='20'>20</option>
		      		<option value='50'>50</option>
		      		<option value='100'>100</option>
		      		<option value='500'>500</option>
		    	</select>
			</div>

	{if $payment_authorized}
		{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}
			<h3>{l s='Payments history' mod='allinone_rewards'}</h3>
		{else if version_compare($smarty.const._PS_VERSION_,'1.7.6','<')}
			<div class="panel-heading" style="margin-top: 30px;">{l s='Payments history' mod='allinone_rewards'}</div>
		{else}
			<div class="card-header" style="margin-top: 30px">{l s='Payments history' mod='allinone_rewards'}</div>
		{/if}
		{if ($payments|@count)}
			<table cellspacing="0" cellpadding="0" class="tablesorter tablesorter-ice" id="payments_list">
				<thead>
					<tr style="background-color: #EEEEEE">
						<th>{l s='Request date' mod='allinone_rewards'}</th>
						<th>{l s='Payment date' mod='allinone_rewards'}</th>
						<th>{l s='Value' mod='allinone_rewards'}</th>
						<th>{l s='Details' mod='allinone_rewards'}</th>
						<th class='filter-false sorter-false'>{l s='Invoice' mod='allinone_rewards'}</th>
						<th class='filter-false sorter-false'>{l s='Action' mod='allinone_rewards'}</th>
					</tr>
				</thead>
				<tbody>
			{foreach from=$payments item=payment name=myLoop}
					<tr class="{if ($smarty.foreach.myLoop.iteration % 2) == 0}alt_row{/if}">
						<td>{$payment['date_add']|escape:'html':'UTF-8'}</td>
						<td>{if $payment['paid']}{$payment['date_upd']|escape:'html':'UTF-8'}{else}-{/if}</td>
						<td style="text-align: right">{displayPrice price=$payment['credits']}</td>
						<td>{$payment['detail']|escape:'htmlall':'UTF-8'|nl2br}</td>
						<td style="text-align: center">{if $payment['invoice']}<a href="{$module_template_dir|escape:'html':'UTF-8'}uploads/{$payment['invoice']|escape:'html':'UTF-8'}" download="Invoice.pdf">{l s='View' mod='allinone_rewards'}</a>{else}-{/if}</td>
						<td style="text-align: center">{if !$payment['paid']}<a href="index.php?tab=AdminCustomers&id_customer={$customer->id|intval}&viewcustomer&token={getAdminToken tab='AdminCustomers'}&accept_payment={$payment['id_payment']|intval}">{l s='Mark as paid' mod='allinone_rewards'}</a>{else}-{/if}</td>
					</tr>
			{/foreach}
				</tbody>
			</table>
			<div class="pager">
		    	<img src="{$module_template_dir|escape:'html':'UTF-8'}js/tablesorter/addons/pager/first.png" class="first"/>
		    	<img src="{$module_template_dir|escape:'html':'UTF-8'}js/tablesorter/addons/pager/prev.png" class="prev"/>
		    	<span class="pagedisplay"></span> <!-- this can be any element, including an input -->
		    	<img src="{$module_template_dir|escape:'html':'UTF-8'}js/tablesorter/addons/pager/next.png" class="next"/>
		    	<img src="{$module_template_dir|escape:'html':'UTF-8'}js/tablesorter/addons/pager/last.png" class="last"/>
		    	<select class="pagesize" style="width: auto; display: inline">
		      		<option value='10'>10</option>
		      		<option value='20'>20</option>
		      		<option value='50'>50</option>
		      		<option value='100'>100</option>
		      		<option value='500'>500</option>
		    	</select>
			</div>
		{else}
				{l s='No payment request found' mod='allinone_rewards'}
		{/if}
	{/if}
{/if}

{if version_compare($smarty.const._PS_VERSION_,'1.6','>=')}
		</div>
{/if}
		<script>
			var footer_pager = "{l s='{startRow} to {endRow} of {totalRows} rows' mod='allinone_rewards'}";
		</script>
		</form>
	</div>
</div>
<!-- END : MODULE allinone_rewards -->