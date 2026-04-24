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

<div id="rewards_sponsorship" class="rewards">
	{if $error && isset($aior_popup)}
	<p class="alert alert-danger">
		{if $error == 'email invalid'}
			{l s='At least one email address is invalid!' mod='allinone_rewards'}
		{elseif $error == 'name invalid'}
			{l s='At least one first name or last name is invalid!' mod='allinone_rewards'}
		{elseif $error == 'email exists'}
			{l s='Someone with this email address has already been sponsored' mod='allinone_rewards'}: {foreach from=$mails_exists item=mail}{$mail|escape:'html':'UTF-8'} {/foreach}<br>
		{elseif $error == 'no revive checked'}
			{l s='Please mark at least one checkbox' mod='allinone_rewards'}
		{elseif $error == 'bad phone'}
			{l s='The mobile phone is invalid' mod='allinone_rewards'}
		{elseif $error == 'sms already sent'}
			{l s='This mobile phone has already been invited during last 10 days, please retry later.' mod='allinone_rewards'}
		{elseif $error == 'sms impossible'}
			{l s='An error occured, the SMS has not been sent' mod='allinone_rewards'}
		{/if}
	</p>
	{/if}
	{if ($invitation_sent||$sms_sent) && isset($aior_popup)}
	<p class="popup">
		{if $sms_sent}
		{l s='A SMS has been sent to your friend!' mod='allinone_rewards'}
		{else if $nbInvitation > 1}
		{l s='Emails have been sent to your friends!' mod='allinone_rewards'}
		{else}
		{l s='An email has been sent to your friend!' mod='allinone_rewards'}
		{/if}
	</p>
	{else}
		{if !isset($aior_popup)}
	<ul class="idTabs">
		<li class="col-xs-12 col-sm-3"><a href="#idTab1" {if $activeTab=='sponsor'}class="selected"{/if}>{l s='Sponsor my friends' mod='allinone_rewards'}</a></li>
		<li class="col-xs-12 col-sm-3"><a href="#idTab2" {if $activeTab=='pending'}class="selected"{/if}>{l s='Pending friends' mod='allinone_rewards'}</a></li>
		<li class="col-xs-12 col-sm-3"><a href="#idTab3" {if $activeTab=='subscribed'}class="selected"{/if}>{l s='Friends I sponsored' mod='allinone_rewards'}</a></li>
			{if $reward_order_allowed || $reward_registration_allowed}
		<li class="col-xs-12 col-sm-3"><a href="#idTab4" {if $activeTab=='statistics'}class="selected"{/if}>{l s='Statistics' mod='allinone_rewards'}</a></li>
			{/if}
	</ul>
	<div class="sheets table-responsive">
		<div id="idTab1" class="sponsorshipBlock">
		{else}
		<div class="sponsorshipBlock sponsorshipPopup">
		{/if}

		{if isset($text)}
			<div id="sponsorship_text" {if isset($aior_popup) && $afterSubmit}style="display: none"{/if}>
				{$text nofilter}
			{if isset($aior_popup)}
				<div align="center">
					<input id="invite" type="button" class="btn btn-primary" value="{l s='Invite my friends' mod='allinone_rewards'}" />
					<input id="noinvite" type="button" class="btn btn-primary" value="{l s='No, thanks' mod='allinone_rewards'}" />
				</div>
			{/if}
			</div>
		{/if}

		{if $canSendInvitations || isset($aior_popup)}
			<div id="sponsorship_form"  {if isset($aior_popup) && !$afterSubmit}style="display: none"{/if}>
				<div>
				{l s='Sponsorship is quick and easy. You can invite your friends in different ways :' mod='allinone_rewards'}
				<ul>
					<li>{l s='Propose your sponsorship on the social networks, by clicking the following links' mod='allinone_rewards'}<br>
						&nbsp;<a href="https://www.facebook.com/sharer.php?u={$link_sponsorship_fb}" target="_blank" title="{l s='Facebook' mod='allinone_rewards'}"><img src='{$rewards_path}img/facebook.png' height='20'></a>
						&nbsp;<a href="https://twitter.com/share?url={$link_sponsorship_twitter}" target="_blank" title="{l s='Twitter' mod='allinone_rewards'}"><img src='{$rewards_path}img/twitter.png' height='20'></a>
					</li>
					<li>{l s='Give this sponsorship link to your friends, or post it on internet (forums, blog...)' mod='allinone_rewards'}<br>{$link_sponsorship}</li>
					<li>{l s='Give them your mail' mod='allinone_rewards'} <b>{$email}</b> {l s='or your sponsor code' mod='allinone_rewards'} <b>{$code}</b> {l s='to enter in the registration form.' mod='allinone_rewards'}</li>
			{if $sms}
					<li>
						<form id="sms_form" method="post" action="{$url_sponsorship}" style="display: inline">{l s='Enter their mobile phone (international format) and send them a SMS' mod='allinone_rewards'} <input id="phone" name="phone" maxlength="16" type="text" placeholder="{l s='e.g. +33612345678' mod='allinone_rewards'}" />
							<input type="image" src="{$rewards_path}img/sendsms.gif" id="submitSponsorSMS" name="submitSponsorSMS" alt="{l s='Send SMS' mod='allinone_rewards'}" title="{l s='Send SMS' mod='allinone_rewards'}" align="absmiddle" />
						</form>
					</li>
			{/if}
					<li>{l s='Fill in the following form and they will receive an mail.' mod='allinone_rewards'}</li>
				</ul>
				</div>
				<div>
					<form id="list_contacts_form" method="post" action="{$url_sponsorship}">
						<label>{l s='Your message (optional)' mod='allinone_rewards'}</label><br/>
						<textarea name="message" class="form-control">{if isset($message)}{$message}{/if}</textarea>
						<table class="table table-bordered">
							<thead class="thead-default">
								<tr>
									<th>&nbsp;</th>
									<th>{l s='Last name' mod='allinone_rewards'}</th>
									<th>{l s='First name' mod='allinone_rewards'}</th>
									<th>{l s='Email' mod='allinone_rewards'}</th>
								</tr>
							</thead>
							<tbody>
								{section name=friends start=0 loop=$nbFriends step=1}
								<tr>
									<td class="align_right">{$smarty.section.friends.iteration}</td>
									<td><input type="text" class="form-control" name="friendsLastName[{$smarty.section.friends.index}]" size="20" value="{if isset($friendsLastName[$smarty.section.friends.index])}{$friendsLastName[$smarty.section.friends.index]}{/if}" /></td>
									<td><input type="text" class="form-control" name="friendsFirstName[{$smarty.section.friends.index}]" size="20" value="{if isset($friendsFirstName[$smarty.section.friends.index])}{$friendsFirstName[$smarty.section.friends.index]}{/if}" /></td>
									<td><input type="text" class="form-control" name="friendsEmail[{$smarty.section.friends.index}]" size="20" value="{if isset($friendsEmail[$smarty.section.friends.index])}{$friendsEmail[$smarty.section.friends.index]}{/if}" /></td>
								</tr>
								{/section}
							</tbody>
						</table>
						<p>
							{l s='Important: Your friends\' email addresses will only be used in the sponsorship program. They will never be used for other purposes.' mod='allinone_rewards'}
						</p>
						<p class="checkbox">
							<input class="cgv" type="checkbox" name="conditionsValided" id="conditionsValided" value="1" {if isset($smarty.post.conditionsValided) AND $smarty.post.conditionsValided eq 1}checked="checked"{/if} />&nbsp;
							<label for="conditionsValided">{l s='I agree to the terms of service and adhere to them unconditionally.' mod='allinone_rewards'}</label>
							<a href="{$url_sponsorship_rules}" class="fancybox rules" title="{l s='Conditions of the sponsorship program' mod='allinone_rewards'}">{l s='Read conditions' mod='allinone_rewards'}</a>
						</p>
						<p>
							{l s='Preview' mod='allinone_rewards'} <a href="{$url_sponsorship_email}" class="fancybox mail" title="{l s='Invitation email' mod='allinone_rewards'}">{l s='the default email' mod='allinone_rewards'}</a> {l s='that will be sent to your friends.' mod='allinone_rewards'}
						</p>

						<footer class="form-footer clearfix">
        					<button class="btn btn-primary" id="submitSponsorFriends" name="submitSponsorFriends" type="submit">{l s='Send invitations' mod='allinone_rewards'}</button>
    					</footer>
					</form>
				</div>
			</div>
		{else}
			<div>
				{l s='To become a sponsor, you need to have completed at least' mod='allinone_rewards'} {$orderQuantityS} {if $orderQuantityS > 1}{l s='orders' mod='allinone_rewards'}{else}{l s='order' mod='allinone_rewards'}{/if}.
			</div>
		{/if}
		</div>

		{if !isset($aior_popup)}
		<div id="idTab2" class="sponsorshipBlock">
			{if $pendingFriends AND $pendingFriends|@count > 0}
			<div>
				{l s='These friends have not yet registered on this website since you sponsored them, but you can try again! To do so, mark the checkboxes of the friend(s) you want to remind, then click on the button "Remind my friends".' mod='allinone_rewards'}
			</div>
			<div>
				<form method="post" action="{$url_sponsorship}" class="std">
					<table class="table table-bordered">
					<thead class="thead-default">
						<tr>
							<th>&nbsp;</th>
							<th>{l s='Last name' mod='allinone_rewards'}</th>
							<th>{l s='First name' mod='allinone_rewards'}</th>
							<th>{l s='Email' mod='allinone_rewards'}</th>
							<th>{l s='Last invitation' mod='allinone_rewards'}</th>
						</tr>
					</thead>
					<tbody>
					{foreach from=$pendingFriends item=pendingFriend name=myLoop}
						<tr>
							<td><input type="checkbox" name="friendChecked[{$pendingFriend.id_sponsorship}]" id="friendChecked[{$pendingFriend.id_sponsorship}]" value="1" /></td>
							<td>{$pendingFriend.lastname}</td>
							<td>{$pendingFriend.firstname}</td>
							<td>{$pendingFriend.email}</td>
							<td>{dateFormat date=$pendingFriend.date_upd full=0}</td>
						</tr>
					{/foreach}
					</tbody>
					</table>
					<footer class="form-footer clearfix">
    					<button class="btn btn-primary" id="revive" name="revive" type="submit">{l s='Remind my friends' mod='allinone_rewards'}</button>
					</footer>
				</form>
			</div>
			{else}
			<div>
				{l s='You have not sponsored any friends.' mod='allinone_rewards'}
			</div>
			{/if}
		</div>

		<div id="idTab3" class="sponsorshipBlock">
			{if $subscribeFriends AND $subscribeFriends|@count > 0}
			<div>
				{l s='Here are sponsored friends who have accepted your invitation:' mod='allinone_rewards'}
			</div>
			<div>
				<table class="table table-bordered">
				<thead class="thead-default">
					<tr>
						<th>&nbsp;</th>
						<th>{l s='Last name' mod='allinone_rewards'}</th>
						<th>{l s='First name' mod='allinone_rewards'}</th>
						<th>{l s='Email' mod='allinone_rewards'}</th>
						<th>{l s='Channel' mod='allinone_rewards'}</th>
						<th>{l s='Inscription date' mod='allinone_rewards'}</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$subscribeFriends item=subscribeFriend name=myLoop}
					<tr class="{if ($smarty.foreach.myLoop.iteration % 2) == 0}item{else}alternate_item{/if}">
						<td>{$smarty.foreach.myLoop.iteration}.</td>
						<td>{$subscribeFriend.lastname}</td>
						<td>{$subscribeFriend.firstname}</td>
						<td>{$subscribeFriend.email}</td>
						<td>{if $subscribeFriend.channel==1}{l s='Email invitation' mod='allinone_rewards'}{elseif $subscribeFriend.channel==2}{l s='Sponsorship link' mod='allinone_rewards'}{elseif $subscribeFriend.channel==3}{l s='Facebook' mod='allinone_rewards'}{elseif $subscribeFriend.channel==4}{l s='Twitter' mod='allinone_rewards'}{elseif $subscribeFriend.channel==5}{l s='Google +1' mod='allinone_rewards'}{/if}</td>
						<td>{dateFormat date=$subscribeFriend.date_upd full=0}</td>
					</tr>
					{/foreach}
				</tbody>
				</table>
			</div>
			{else}
			<div>
				{l s='No sponsored friends have accepted your invitation yet.' mod='allinone_rewards'}
			</div>
			{/if}
		</div>
			{if $reward_order_allowed || $reward_registration_allowed}
		<div id="idTab4" class="sponsorshipBlock">
			<div class="title">{l s='Details by registration channel' mod='allinone_rewards'}</div>
			<div>
				<table class="table table-bordered">
					<thead class="thead-default">
						<tr>
							<th colspan="2" class="left">{l s='Channels' mod='allinone_rewards'}</th>
							<th class="center">{l s='Friends' mod='allinone_rewards'}</th>
							<th class="center">{l s='Orders' mod='allinone_rewards'}</th>
							{if $reward_order_allowed}<th class="center">{l s='Rewards for orders' mod='allinone_rewards'}</th>{/if}
							{if $reward_registration_allowed}<th class="center">{l s='Rewards for registrations' mod='allinone_rewards'}</th>{/if}
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="left" rowspan="{if $statistics.direct_nb5 > 0}5{else}4{/if}">{l s='My direct friends' mod='allinone_rewards'}</td>
							<td class="left">{l s='Email invitation' mod='allinone_rewards'}</td>
							<td class="center">{$statistics.direct_nb1|intval}</td>
							<td class="center">{$statistics.nb_orders_channel1|intval}</td>
							{if $reward_order_allowed}<td class="right">{$statistics.direct_rewards_orders1}</td>{/if}
							{if $reward_registration_allowed}<td class="right">{$statistics.direct_rewards_registrations1}</td>{/if}
						</tr>
						<tr>
							<td class="left">{l s='Sponsorship link' mod='allinone_rewards'}</td>
							<td class="center">{$statistics.direct_nb2|intval}</td>
							<td class="center">{$statistics.nb_orders_channel2|intval}</td>
							{if $reward_order_allowed}<td class="right">{$statistics.direct_rewards_orders2}</td>{/if}
							{if $reward_registration_allowed}<td class="right">{$statistics.direct_rewards_registrations2}</td>{/if}
						</tr>
						<tr>
							<td class="left">{l s='Facebook' mod='allinone_rewards'}</td>
							<td class="center">{$statistics.direct_nb3|intval}</td>
							<td class="center">{$statistics.nb_orders_channel3|intval}</td>
							{if $reward_order_allowed}<td class="right">{$statistics.direct_rewards_orders3}</td>{/if}
							{if $reward_registration_allowed}<td class="right">{$statistics.direct_rewards_registrations3}</td>{/if}
						</tr>
						<tr>
							<td class="left">{l s='Twitter' mod='allinone_rewards'}</td>
							<td class="center">{$statistics.direct_nb4|intval}</td>
							<td class="center">{$statistics.nb_orders_channel4|intval}</td>
							{if $reward_order_allowed}<td class="right">{$statistics.direct_rewards_orders4}</td>{/if}
							{if $reward_registration_allowed}<td class="right">{$statistics.direct_rewards_registrations4}</td>{/if}
						</tr>
				{if $statistics.direct_nb5 > 0}
						<tr>
							<td class="left">{l s='Google +1' mod='allinone_rewards'}</td>
							<td class="center">{$statistics.direct_nb5|intval}</td>
							<td class="center">{$statistics.nb_orders_channel5|intval}</td>
							{if $reward_order_allowed}<td class="right">{$statistics.direct_rewards_orders5}</td>{/if}
							{if $reward_registration_allowed}<td class="right">{$statistics.direct_rewards_registrations5}</td>{/if}
						</tr>
				{/if}
				{if $statistics.maxlevel > 1}
						<tr>
							<td class="left" colspan="2">{l s='Indirect friends' mod='allinone_rewards'}</td>
							<td class="center">{$statistics.indirect_nb|intval}</td>
							<td class="center">{$statistics.indirect_nb_orders|intval}</td>
							{if $reward_order_allowed}<td class="right">{$statistics.indirect_rewards}</td>{/if}
							{if $reward_registration_allowed}<td class="right">-</td>{/if}
						</tr>
				{/if}
						<tr class="total">
							<td class="left" colspan="2">{l s='Total' mod='allinone_rewards'}</td>
							<td class="center">{$statistics.direct_nb1+$statistics.direct_nb2+$statistics.direct_nb3+$statistics.direct_nb4+$statistics.direct_nb5+$statistics.indirect_nb|intval}</td>
							<td class="center">{$statistics.nb_orders_channel1+$statistics.nb_orders_channel2+$statistics.nb_orders_channel3+$statistics.nb_orders_channel4+$statistics.nb_orders_channel5+$statistics.indirect_nb_orders|intval}</td>
							{if $reward_order_allowed}<td class="right">{$statistics.total_orders}</td>{/if}
							{if $reward_registration_allowed}<td class="right">{$statistics.total_registrations}</td>{/if}
						</tr>
					</tbody>
				</table>
			</div>

				{if $statistics.maxlevel > 1 && $statistics.sponsored1}
			<div class="title">{l s='Details by sponsorship level' mod='allinone_rewards'}</div>
			<table class="table table-bordered">
				<thead class="thead-default">
					<tr>
						<th class="left">{l s='Level' mod='allinone_rewards'}</th>
						<th class="center">{l s='Friends' mod='allinone_rewards'}</th>
						<th class="center">{l s='Orders' mod='allinone_rewards'}</th>
						<th class="center">{l s='Rewards' mod='allinone_rewards'}</th>
					</tr>
				</thead>
				<tbody>
					{section name=levels start=0 loop=$statistics.maxlevel step=1}
						{assign var="indiceFriends" value="nb`$smarty.section.levels.iteration`"}
						{assign var="indiceOrders" value="nb_orders`$smarty.section.levels.iteration`"}
						{assign var="indiceRewards" value="rewards`$smarty.section.levels.iteration`"}
					<tr>
						<td class="left">{l s='Level' mod='allinone_rewards'} {$smarty.section.levels.iteration}</td>
						<td class="center">{if isset($statistics[$indiceFriends])}{$statistics[$indiceFriends]|intval}{else}0{/if}</td>
						<td class="center">{if isset($statistics[$indiceOrders])}{$statistics[$indiceOrders]|intval}{else}0{/if}</td>
						<td class="right">{$statistics[$indiceRewards]}</td>
					</tr>
					{/section}
					<tr class="total">
						<td class="left">{l s='Total' mod='allinone_rewards'}</td>
						<td class="center">{$statistics.direct_nb1+$statistics.direct_nb2+$statistics.direct_nb3+$statistics.direct_nb4+$statistics.direct_nb5+$statistics.indirect_nb|intval}</td>
						<td class="center">{$statistics.nb_orders_channel1+$statistics.nb_orders_channel2+$statistics.nb_orders_channel3+$statistics.nb_orders_channel4+$statistics.nb_orders_channel5+$statistics.indirect_nb_orders|intval}</td>
						<td class="right">{$statistics.total_global}</td>
					</tr>
				</tbody>
			</table>
				{/if}

				{if $statistics.sponsored1}
			<div class="title">{l s='Details for my direct friends' mod='allinone_rewards'}</div>
			<table class="table table-bordered">
				<thead class="thead-default">
					<tr>
						<th class="left">{l s='Name' mod='allinone_rewards'}</th>
						<th class="center">{l s='Orders' mod='allinone_rewards'}</th>
						<th class="center">{l s='Rewards' mod='allinone_rewards'}</th>
					{if $statistics.maxlevel > 1}
						<th class="center">{l s='Friends' mod='allinone_rewards'}</th>
						<th class="center">{l s='Friends\' orders' mod='allinone_rewards'}</th>
						<th class="center">{l s='Rewards' mod='allinone_rewards'}</th>
						<th class="center">{l s='Total' mod='allinone_rewards'}</th>
					{/if}
					</tr>
				</thead>
				<tbody>
					{foreach from=$statistics.sponsored1 item=sponsored name=myLoop}
						{assign var="indiceDirect" value="direct_customer`$sponsored.id_customer`"}
						{assign var="indiceIndirect" value="indirect_customer`$sponsored.id_customer`"}
						{if isset($statistics[$indiceDirect])}
							{assign var="valueDirect" value=$statistics[$indiceDirect]}
						{else}
							{assign var="valueDirect" value=0}
						{/if}
						{if isset($statistics[$indiceIndirect])}
							{assign var="valueIndirect" value=$statistics[$indiceIndirect]}
						{else}
							{assign var="valueIndirect" value=0}
						{/if}
					<tr>
						<td class="left">{$sponsored.lastname} {$sponsored.firstname}</td>
						<td class="center">{$sponsored.direct_orders|intval}</td>
						<td class="right">{$sponsored.direct}</td>
						{if $statistics.maxlevel > 1}
						<td class="center">{$valueDirect+$valueIndirect|intval}</td>
						<td class="center">{$sponsored.indirect_orders|intval}</td>
						<td class="right">{$sponsored.indirect}</td>
						<td class="total right">{$sponsored.total}</td>
						{/if}
					</tr>
					{/foreach}
					<tr class="total">
						<td class="left">{l s='Total' mod='allinone_rewards'}</td>
						<td class="center">{$statistics.total_direct_orders|intval}</td>
						<td class="right">{$statistics.total_direct_rewards}</td>
						{if $statistics.maxlevel > 1}
						<td class="center">{$statistics.indirect_nb|intval}</td>
						<td class="center">{$statistics.total_indirect_orders|intval}</td>
						<td class="right">{$statistics.total_indirect_rewards}</td>
						<td class="right">{$statistics.total_global}</td>
						{/if}
					</tr>
				</tbody>
			</table>
				{/if}
		</div>
			{/if}
	</div>
		{/if}
	{/if}
</div>