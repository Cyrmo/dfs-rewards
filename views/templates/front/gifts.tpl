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
{include file="$tpl_dir./errors.tpl"}
{capture name=path}{l s='Gift products' mod='allinone_rewards'}{/capture}
{if version_compare($smarty.const._PS_VERSION_,'1.6','>=')}
	<h1 class="gifts page-heading {if isset($products)} product-listing{/if}"><span class="cat-name">{l s='Gift products you can buy with your rewards' mod='allinone_rewards'}</span>{include file="$tpl_dir./category-count.tpl"}</h1>
	{if $products}
	<div class="content_sortPagiBar clearfix">
    	<div class="sortPagiBar clearfix">
    		{include file="$tpl_dir./product-sort.tpl"}
        	{include file="$tpl_dir./nbr-product-page.tpl"}
		</div>
        <div class="top-pagination-content clearfix">
        	{include file="$tpl_dir./product-compare.tpl"}
			{include file="$tpl_dir./pagination.tpl"}
        </div>
	</div>
	{include file="$tpl_dir./product-list.tpl" products=$products}
	<div class="content_sortPagiBar">
		<div class="bottom-pagination-content clearfix">
			{include file="$tpl_dir./product-compare.tpl" paginationId='bottom'}
            {include file="$tpl_dir./pagination.tpl" paginationId='bottom'}
		</div>
	</div>
	{/if}
{else}
	<h1>{l s='Gift products you can buy with your rewards' mod='allinone_rewards'}</h1>
	<div class="resumecat category-product-count">{include file="$tpl_dir./category-count.tpl"}</div>
	{if $products}
	<div class="content_sortPagiBar">
		{include file="$tpl_dir./pagination.tpl"}
		<div class="sortPagiBar clearfix">
			{include file="$tpl_dir./product-sort.tpl"}
			{include file="$tpl_dir./product-compare.tpl"}
			{include file="$tpl_dir./nbr-product-page.tpl"}
		</div>
	</div>
	{include file="$tpl_dir./product-list.tpl" products=$products}
	<div class="content_sortPagiBar">
		<div class="sortPagiBar clearfix">
			{include file="$tpl_dir./product-sort.tpl"}
			{include file="$tpl_dir./product-compare.tpl"}
			{include file="$tpl_dir./nbr-product-page.tpl"}
		</div>
		{include file="$tpl_dir./pagination.tpl"}
	</div>
	{/if}
{/if}