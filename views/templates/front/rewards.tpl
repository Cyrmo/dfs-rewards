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
{capture name=path}<a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">{l s='My account' mod='allinone_rewards'}</a><span class="navigation-pipe">{$navigationPipe|escape:'html':'UTF-8'}</span>{l s='My rewards account' mod='allinone_rewards'}{/capture}
{if version_compare($smarty.const._PS_VERSION_,'1.6','<')}
{include file="$tpl_dir./breadcrumb.tpl"}
{/if}

<div id="rewards_account" class="rewards">
	<h1 class="page-heading">{l s='My rewards account' mod='allinone_rewards'}</h1>

{if $error}
	<p class="error">{$error}</p>
{/if}

{if isset($payment_error)}
	{if $payment_error==1}
	<p class="error">{l s='Please fill all the required fields' mod='allinone_rewards'}</p>
	{elseif $payment_error==2}
	<p class="error">{l s='An error occured during the treatment of your request' mod='allinone_rewards'}</p>
	{/if}
{/if}

	<ul class="idTabs">
		<li class="col-xs-12 col-sm-4"><a href="#idTab1" {if $activeTab!='history'}class="selected"{/if}>{l s='My account' mod='allinone_rewards'}</a></li>
		<li class="col-xs-12 col-sm-4"><a href="#idTab2" {if $activeTab=='history'}class="selected"{/if}>{l s='Rewards history' mod='allinone_rewards'}</a></li>
		<li class="col-xs-12 col-sm-4"><a href="#idTab3">{l s='Vouchers history' mod='allinone_rewards'}</a></li>
	</ul>

	<div class="sheets table-responsive">
		<div id="idTab1" class="rewardsBlock">
			<div id="general_txt" style="padding-bottom: 20px">{$general_txt|escape:'string':'UTF-8'}</div>

{if $return_days > 0}
			<p>{l s='Rewards will be available %s days after the validation of each order.'  sprintf={$return_days|intval} mod='allinone_rewards'}</p>
{/if}
			<table class="std">
				<thead>
					<tr>
						<th style="text-align: center" class="first_item">{l s='Total rewards' mod='allinone_rewards'}</th>
						{if $convertColumns}
						<th style="text-align: center" class="item">{l s='Already converted' mod='allinone_rewards'}</th>
						{/if}
						{if $paymentColumns}
						<th style="text-align: center" class="item">{l s='Paid' mod='allinone_rewards'}</th>
						{/if}
						<th style="text-align: center" class="item">{l s='Available' mod='allinone_rewards'}</th>
						<th style="text-align: center" class="last_item">{l s='Awaiting validation' mod='allinone_rewards'}</th>
						{if $paymentColumns}
						<th style="text-align: center" class="last_item">{l s='Awaiting payment' mod='allinone_rewards'}</th>
						{/if}
					</tr>
				</thead>
				<tr class="alternate_item">
					<td style="text-align: center">{$totalGlobal|escape:'html':'UTF-8'}</td>
					{if $convertColumns}
					<td style="text-align: center">{$totalConverted|escape:'html':'UTF-8'}</td>
					{/if}
					{if $paymentColumns}
					<td style="text-align: center">{$totalPaid|escape:'html':'UTF-8'}</td>
					{/if}
					<td style="text-align: center">{$totalAvailable|escape:'html':'UTF-8'}</td>
					<td style="text-align: center">{$totalPending|escape:'html':'UTF-8'}</td>
					{if $paymentColumns}
					<td style="text-align: center">{$totalWaitingPayment|escape:'html':'UTF-8'}</td>
					{/if}
				</tr>
			</table>
{if $rewards}
	{if $voucher_minimum_allowed}
			<div id="min_transform" style="clear: both">{l s='The minimum required to be able to transform your rewards into vouchers is' mod='allinone_rewards'} <b>{$voucherMinimum|escape:'html':'UTF-8'}</b></div>
	{/if}
	{if $payment_minimum_allowed}
			<div id="min_payment" style="clear: both">{l s='The minimum required to be able to ask for a payment is' mod='allinone_rewards'} <b>{$paymentMinimum|escape:'html':'UTF-8'}</b></div>
	{/if}

			<div id="aior_buttons">
	{if $show_link}
				<div id="gift_list">
					<a href="{$link->getModuleLink('allinone_rewards', 'gifts', [], true)|escape:'html':'UTF-8'}">{l s='View the list of available gift products' mod='allinone_rewards'}</a>
				</div>
	{else if $voucher_button_allowed}
				<div id="transform">
					<a href="{$pagination_link|escape:'htmlall':'UTF-8'}transform-credits=true" onclick="return confirm('{l s='Are you sure you want to transform your rewards into vouchers ?' mod='allinone_rewards' js=1}');">{l s='Transform my rewards into a voucher worth' mod='allinone_rewards'} <span>{$totalAvailableCurrency|escape:'htmlall':'UTF-8'}</span></a>
				</div>
	{/if}
	{if $payment_button_allowed}
				<div id="payment">
					<a onClick="$('#payment_form').toggle()">{l s='Ask for the payment of your available rewards :' mod='allinone_rewards'} <span>{$totalForPaymentDefaultCurrency|escape:'html':'UTF-8'}</span></a>
				</div>
				<form id="payment_form" class="std" method="post" action="{$pagination_link|escape:'htmlall':'UTF-8'}" enctype="multipart/form-data" style="display: {if isset($smarty.post.payment_details)}block{else}none{/if}">
					<fieldset>
						<div id="payment_txt">{$payment_txt|escape:'string':'UTF-8'}</div>
						<p class="required textarea">
							<label for="payment_details">{l s='Bank account, paypal address, address, details...' mod='allinone_rewards'} <sup>*</sup></label>
							<textarea id="payment_details" name="payment_details" rows="3" cols="40">{if isset($payment_details)}{$payment_details|escape:'html':'UTF-8'}{/if}</textarea>
						</p>
						<p class="{if $payment_invoice}required{/if} text">
							<label for="payment_invoice">{l s='Invoice' mod='allinone_rewards'} ({$totalForPaymentDefaultCurrency|escape:'html':'UTF-8'}) {if $payment_invoice}<sup>*</sup>{/if}</label>
							<input id="payment_invoice" name="payment_invoice" type="file">
						</p>
						<input class="button" type="submit" value="{l s='Save' mod='allinone_rewards'}" name="submitPayment" id="submitPayment">
						<p class="required"><sup>*</sup>{l s='Required field' mod='allinone_rewards'}</p>
					</fieldset>
				</form>
	{/if}
			</div>

	{if $cart_rules_available}
			<h2 id="cart_rules_available" class="page-subheading">{l s='Available vouchers' mod='allinone_rewards'}</h2>
			<table class="std">
				<thead>
					<tr>
						<th class="first_item">{l s='Date' mod='allinone_rewards'}</th>
						<th class="item">{l s='Code' mod='allinone_rewards'}</th>
						<th class="item">{l s='Description' mod='allinone_rewards'}</th>
						<th class="item">{l s='Value' mod='allinone_rewards'}</th>
						<th class="item">{l s='Minimum' mod='allinone_rewards'}</th>
						<th class="item">{l s='Validity' mod='allinone_rewards'}</th>
						<th class="last_item">{l s='Action' mod='allinone_rewards'}</th>
					</tr>
				</thead>
				<tbody>
		{foreach from=$cart_rules_available item=cart_rule name=myLoop}
					<tr class="{if ($smarty.foreach.myLoop.iteration % 2) == 0}item{else}alternate_item{/if}">
						<td>{dateFormat date=$cart_rule.date full=1}</td>
						<td>{$cart_rule.code|escape:'htmlall':'UTF-8'}</td>
			{if $cart_rule.gift_product}
						<td>{l s='Gift product: %s' sprintf={$cart_rule.product|escape:'string':'UTF-8'} mod='allinone_rewards'}</td>
			{else}
						<td>{$cart_rule.description|escape:'htmlall':'UTF-8'}</td>
			{/if}
						<td>{$cart_rule.credits|escape:'htmlall':'UTF-8'} {if $cart_rule.reduction_tax}{l s='VAT Incl.' mod='allinone_rewards'}{else}{l s='VAT Excl.' mod='allinone_rewards'}{/if} {if $rewards_virtual}({$cart_rule.virtual_credits|escape:'htmlall':'UTF-8'}){/if}</td>
						<td>{if isset($cart_rule.minimal)}{$cart_rule.minimal} {if $cart_rule.minimum_amount_tax}{l s='VAT Incl.' mod='allinone_rewards'}{else}{l s='VAT Excl.' mod='allinone_rewards'}{/if}{/if}</td>
						<td>{dateFormat date=$cart_rule.date_to full=1}</td>
						<td><a class="btn btn-default button button-small" href="{$pagination_link|escape:'htmlall':'UTF-8'}discount={$cart_rule.code|escape:'htmlall':'UTF-8'}"><span>{l s='Add to cart' mod='allinone_rewards'}</span></a></td>
					</tr>
		{/foreach}
				</tbody>
			</table>
	{/if}
{/if}
			<h2 class="page-subheading">{l s='Options' mod='allinone_rewards'}</h2>
			<form class="std" id="rewards_options" method="POST">
			<p>
				{l s='Do you agree to receive periodic reminder emails about your rewards account?' mod='allinone_rewards'}<input {if $rewards_reminder}checked{/if} name="rewards_reminder" id="rewards_reminder_on" type="radio" value="1" onclick="$(this).parents('form').submit()"><label for="rewards_reminder_on">{l s='Yes' mod='allinone_rewards'}</label> <input name="rewards_reminder" {if !$rewards_reminder}checked{/if} id="rewards_reminder_off" type="radio" value="0" onclick="$(this).parents('form').submit()"><label for="rewards_reminder_off">{l s='No' mod='allinone_rewards'}</label>
			</p>
			</form>
		</div>
		<div id="idTab2" class="rewardsBlock">
{if $rewards}
			<table class="std">
				<thead>
					<tr>
						<th class="first_item">{l s='Event' mod='allinone_rewards'}</th>
						<th class="item">{l s='Date' mod='allinone_rewards'}</th>
						<th class="item">{l s='Reward' mod='allinone_rewards'}</th>
						<th class="item">{l s='Status' mod='allinone_rewards'}</th>
						<th class="last_item">{l s='Validity' mod='allinone_rewards'}</th>
					</tr>
				</thead>
				<tbody>
	{foreach from=$displayrewards item=reward name=myLoop}
					<tr class="{if ($smarty.foreach.myLoop.iteration % 2) == 0}item{else}alternate_item{/if}">
						<td>{$reward.detail|escape:'htmlall':'UTF-8'}</td>
						<td>{dateFormat date=$reward.date full=1}</td>
						<td align="right">{$reward.credits|escape:'html':'UTF-8'}</td>
						<td>{$reward.state|escape:'htmlall':'UTF-8'}</td>
						<td>{if $reward.id_reward_state==RewardsStateModel::getValidationId() && $reward.date_end!='0000-00-00 00:00:00'}{dateFormat date=$reward.date_end full=1}{else}-{/if}</td>
					</tr>
	{/foreach}
				</tbody>
			</table>

	{if $max_page > 1}
			<div id="pagination" class="pagination">
				<ul class="pagination">
			{if $pagination != 1}
					<li id="pagination_previous"><a href="{$pagination_link|escape:'html':'UTF-8'}page={$pagination-1|escape:'html':'UTF-8'}">
						&laquo;&nbsp;{l s='Previous' mod='allinone_rewards'}</a></li>
			{else}
					<li id="pagination_previous" class="disabled"><span>&laquo;&nbsp;{l s='Previous' mod='allinone_rewards'}</span></li>
			{/if}
			{section name=pagination start=1 loop=$max_page+1 step=1}
				{if $pagination == $smarty.section.pagination.index}
					<li class="current"><span>{$pagination|escape:'html':'UTF-8'}</span></li>
				{else}
					<li><a href="{$pagination_link|escape:'html':'UTF-8'}page={$smarty.section.pagination.index|escape:'html':'UTF-8'}">{$smarty.section.pagination.index|escape:'html':'UTF-8'}</a></li>
				{/if}
			{/section}
			{if $pagination < $max_page}
					<li id="pagination_next"><a href="{$pagination_link|escape:'html':'UTF-8'}page={$pagination+1|escape:'html':'UTF-8'}">{l s='Next' mod='allinone_rewards'}&nbsp;&raquo;</a></li>
			{else}
					<li id="pagination_next" class="disabled"><span>{l s='Next' mod='allinone_rewards'}&nbsp;&raquo;</span></li>
			{/if}
				</ul>
			</div>
	{/if}
{/if}
		</div>
		<div id="idTab3" class="rewardsBlock">
{if $cart_rules}
			<table class="std">
				<thead>
					<tr>
						<th class="first_item">{l s='Date' mod='allinone_rewards'}</th>
						<th class="item">{l s='Code' mod='allinone_rewards'}</th>
						<th class="item">{l s='Status' mod='allinone_rewards'}</th>
						<th class="item">{l s='Description' mod='allinone_rewards'}</th>
						<th class="item">{l s='Value' mod='allinone_rewards'}</th>
						<th class="item">{l s='Minimum' mod='allinone_rewards'}</th>
						<th class="last_item">{l s='Validity' mod='allinone_rewards'}</th>
					</tr>
				</thead>
				<tbody>
	{foreach from=$cart_rules item=cart_rule name=myLoop}
					<tr class="{if ($smarty.foreach.myLoop.iteration % 2) == 0}item{else}alternate_item{/if}">
						<td>{dateFormat date=$cart_rule.date full=1}</td>
						<td>{$cart_rule.code|escape:'htmlall':'UTF-8'}</td>
						<td>
		{if $cart_rule.id_order==0}
			{if $cart_rule.active}
				{if $cart_rule.date_to < date('Y-m-d H:i:s')}
							{l s='Expired' mod='allinone_rewards'}
				{else}
							{l s='Available' mod='allinone_rewards'}
				{/if}
			{else}
							{l s='Canceled' mod='allinone_rewards'}
			{/if}
		{else}
							{l s='Used in order %s' sprintf={$cart_rule.reference|escape:'string':'UTF-8'} mod='allinone_rewards'}
		{/if}
						</td>
		{if $cart_rule.gift_product}
						<td>{l s='Gift product: %s' sprintf={$cart_rule.product|escape:'string':'UTF-8'} mod='allinone_rewards'}</td>
		{else}
						<td>{$cart_rule.description|escape:'htmlall':'UTF-8'}</td>
		{/if}
						<td>{$cart_rule.credits|escape:'htmlall':'UTF-8'} {if $cart_rule.reduction_tax}{l s='VAT Incl.' mod='allinone_rewards'}{else}{l s='VAT Excl.' mod='allinone_rewards'}{/if} {if $rewards_virtual}({$cart_rule.virtual_credits|escape:'htmlall':'UTF-8'}){/if}</td>
						<td>{if isset($cart_rule.minimal)}{$cart_rule.minimal} {if $cart_rule.minimum_amount_tax}{l s='VAT Incl.' mod='allinone_rewards'}{else}{l s='VAT Excl.' mod='allinone_rewards'}{/if}{/if}</td>
						<td>{dateFormat date=$cart_rule.date_to full=1}</td>
					</tr>
	{/foreach}
				</tbody>
			</table>
{/if}
		</div>
	</div>
</div>
{if version_compare($smarty.const._PS_VERSION_,'1.6','>=')}
<ul class="footer_links clearfix">
	<li><a class="btn btn-default button button-small" href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}"><span><i class="icon-chevron-left"></i> {l s='Back to your account' mod='allinone_rewards'}</span></a></li>
	<li><a class="btn btn-default button button-small" href="{if isset($force_ssl) && $force_ssl}{$base_dir_ssl|escape:'htmlall':'UTF-8'}{else}{$base_dir|escape:'htmlall':'UTF-8'}{/if}"><span><i class="icon-chevron-left"></i> {l s='Home' mod='allinone_rewards'}</span></a></li>
</ul>
{else}
<ul class="footer_links clearfix">
	<li><a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}"><img src="{$img_dir|escape:'htmlall':'UTF-8'}icon/my-account.gif" alt="" class="icon" /> {l s='Back to your account' mod='allinone_rewards'}</a></li>
	<li class="f_right"><a href="{$base_dir|escape:'html':'UTF-8'}"><img src="{$img_dir|escape:'html':'UTF-8'}icon/home.gif" alt="" class="icon" /> {l s='Home' mod='allinone_rewards'}</a></li>
</ul>
{/if}
<!-- END : MODULE allinone_rewards -->