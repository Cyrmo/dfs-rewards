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

if (!defined('_PS_VERSION_'))
	exit;

abstract class RewardsGenericPlugin
{
	public $name;
	protected $module;
	protected $instance;
	protected $context;
	protected $id_template = 0;

	public function __construct($module)
	{
		$this->instance = $module;
		$this->context = Context::getContext();
	}

	public function checkWarning() {
	}

	protected function registerHook($hookName)
	{
		return $this->instance->registerHook($hookName);
	}

	public function l($string, $lang_id=null, $specific=null)
	{
		return $this->instance->l2($string, $lang_id, isset($specific) ? $specific : Tools::strtolower(get_class($this)));
	}

	protected function initTemplate()
	{
		// traitement global des actions sur le template, valable pour tous les plugins
		$this->id_template = (int)Tools::getValue('rewards_'.$this->name.'_template_id');
		$reward_action=Tools::getValue('rewards_template_action');
		if ($reward_action && $this->name==Tools::getValue('plugin')) {
			switch($reward_action) {
				case 'list_customer':
					die(Tools::jsonEncode(RewardsTemplateModel::getCustomersForFilter($this->name, version_compare(_PS_VERSION_, '1.6', '>=') ? Tools::getValue('q') : Tools::getValue('term'))));
				case 'add_customer':
					die(Tools::jsonEncode(RewardsTemplateModel::addCustomer($this->id_template, Tools::getValue('id_customer'))));
				case 'delete_customer':
					die(Tools::jsonEncode(RewardsTemplateModel::deleteCustomer($this->id_template, Tools::getValue('id_customer'))));
				case 'add_customers_from_group':
					die(Tools::jsonEncode(RewardsTemplateModel::addCustomersFromGroup($this->id_template, $this->name, Tools::getValue('add_from_group'))));
				case 'create':
					$template = new RewardsTemplateModel();
					$template->name = Tools::getValue('rewards_template_name');
					$template->plugin = $this->name;
					try {
						$template->add();
						$this->id_template = $template->id;
					} catch (Exception $e) {
						$this->instance->errors = $this->instance->displayError($this->l('That name is already used by another template', null, 'rewardsgenericplugin'));
					}
					break;
				case 'duplicate':
					if ($this->id_template != 0) {
						$template = new RewardsTemplateModel($this->id_template);
						try {
							$template->duplicate(Tools::getValue('rewards_template_name'));
							$this->id_template = $template->id;
						} catch (Exception $e) {
							$this->instance->errors = $this->instance->displayError($this->l('That name is already used by another template', null, 'rewardsgenericplugin'));
						}
					}
					break;
				case 'rename':
					if ($this->id_template != 0) {
						$template = new RewardsTemplateModel($this->id_template);
						$template->name = Tools::getValue('rewards_template_name');
						try {
							$template->save();
						} catch (Exception $e) {
							$this->instance->errors = $this->instance->displayError($this->l('That name is already used by another template', null, 'rewardsgenericplugin'));
						}
					}
					break;
				case 'delete':
					if ($this->id_template != 0) {
						$template = new RewardsTemplateModel($this->id_template);
						$template->delete();
						$this->id_template = 0;
					}
					break;
				default:
					break;
			}
		}
	}

	protected function getTemplateForm($id_template, $tab, $name) {
		$html = '
			<div style="padding-bottom: 10px">
				<form class="rewards_template" action="'.$this->instance->getCurrentPage($this->name).'" method="post">
					<input type="hidden" name="plugin" value="'.$tab.'" />
					<input type="hidden" name="rewards_template_action">
					<input type="hidden" name="rewards_template_name">
					<fieldset>
						<legend>'.$this->l('Choose a template to modify', null, 'rewardsgenericplugin').'</legend>'.
						$this->l('By default, all customers are using the same settings. If you need different profiles with different settings, you can create templates and then link the customers you want to those templates. Default settings will be overriden automatically. Be carefull, when you create a new template the default values are displayed but nothing is saved in database at this moment. So please save all the forms at least once if you want to register you own settings, else default settings will continue to be used for the unsaved forms.', null, 'rewardsgenericplugin').'<br><br>
						<div style="float: left">'.
							$this->l('You\'re currently working on', null, 'rewardsgenericplugin').'
							<select class="rewards_template" name="rewards_'.$tab.'_template_id">
								<option value="0">'.$name.' : '.$this->l('default template', null, 'rewardsgenericplugin').'</option>';
		$templates = RewardsTemplateModel::getList($tab);
		foreach($templates as $template) {
			$html .= '			<option '.($id_template==$template['id_template'] ? 'selected':'').' value="'.$template['id_template'].'">'.htmlentities($template['name'], ENT_NOQUOTES, 'utf-8').'</option>';
		}
		$html .= '
							</select>'.
		($id_template==0 ? '' : '
							<img src="../img/admin/edit.gif" width="16" height="16" alt="'.$this->l('Rename', null, 'rewardsgenericplugin').'" title="'.$this->l('Rename', null, 'rewardsgenericplugin').'" onClick="promptTemplate($(this), \'rename\', \''.$this->l('Name of the template ?', null, 'rewardsgenericplugin').'\', \'\', \''.$this->l('Rename the template', null, 'rewardsgenericplugin').'\')">
							<img src="../img/admin/delete.gif" width="16" height="16" alt="'.$this->l('Delete', null, 'rewardsgenericplugin').'" title="'.$this->l('Delete', null, 'rewardsgenericplugin').'" onClick="deleteTemplate($(this), \''.$this->l('Do you really want to delete this template and links with its customers ?', null, 'rewardsgenericplugin').'\', \''.$this->l('Delete the template', null, 'rewardsgenericplugin').'\')">
							<img src="../img/admin/duplicate.png" width="16" height="16" alt="'.$this->l('Duplicate', null, 'rewardsgenericplugin').'" title="'.$this->l('Duplicate', null, 'rewardsgenericplugin').'" onClick="promptTemplate($(this), \'duplicate\', \''.$this->l('Name of the new template ?', null, 'rewardsgenericplugin').'\', \'\', \''.$this->l('Duplicate the template', null, 'rewardsgenericplugin').'\')">').
		($tab==Tools::getValue('plugin') && $id_template ? '
							<img id="view_template_customers" width="16" height="16" src="'._MODULE_DIR_.'allinone_rewards/img/employee.gif" title="'.$this->l('List of customers using that template', null, 'rewardsgenericplugin').'">':'').'
						</div>
						<input style="float: right" type="button" class="button" value="'.$this->l('Or create a new template', null, 'rewardsgenericplugin').'" onClick="promptTemplate($(this), \'create\', \''.$this->l('Name of the new template ?', null, 'rewardsgenericplugin').'\', \'\', \''.$this->l('Create a new template', null, 'rewardsgenericplugin').'\')">';
		if ($tab==Tools::getValue('plugin') && $id_template) {
			$html .= '
						<div id="rewards_template_customers" class="clear"><b>'.
							$this->l('List of customers using that template', null, 'rewardsgenericplugin').'</b>
							<table class="tablesorter tablesorter-ice">
								<thead>
									<th class="id">'.$this->l('ID', null, 'rewardsgenericplugin').'</th>
									<th>'.$this->l('Firstname', null, 'rewardsgenericplugin').'</th>
									<th>'.$this->l('Lastname', null, 'rewardsgenericplugin').'</th>
									<th>'.$this->l('Email', null, 'rewardsgenericplugin').'</th>
									<th class="action filter-false sorter-false">&nbsp;</th>
								</thead>
								<tbody>';
			$customers = RewardsTemplateModel::getCustomers($id_template);
			if (is_array($customers)) {
				foreach ($customers as $customer) {
					$html .= '
									<tr id="'.$customer['id_customer'].'">
										<td class="id">'.$customer['id_customer'].'</td>
										<td>'.$customer['firstname'].'</td>
										<td>'.$customer['lastname'].'</td>
										<td>'.$customer['email'].'</td>
										<td><img src="../img/admin/delete.gif" class="delete"></td>
									</tr>';
				}
			}
			$html .= '
								</tbody>
							</table>
							<div class="pager">
						    	<img src="'._MODULE_DIR_.$this->instance->name.'/js/tablesorter/addons/pager/first.png" class="first"/>
						    	<img src="'._MODULE_DIR_.$this->instance->name.'/js/tablesorter/addons/pager/prev.png" class="prev"/>
						    	<span class="pagedisplay"></span> <!-- this can be any element, including an input -->
						    	<img src="'._MODULE_DIR_.$this->instance->name.'/js/tablesorter/addons/pager/next.png" class="next"/>
						    	<img src="'._MODULE_DIR_.$this->instance->name.'/js/tablesorter/addons/pager/last.png" class="last"/>
						    	<select class="pagesize">
						      		<option value="10">10</option>
						      		<option value="20">20</option>
						      		<option value="50">50</option>
						      		<option value="100">100</option>
						      		<option value="500">500</option>
						    	</select>
							</div>
							<div class="clear" style="padding-top: 20px">
								<b>'.$this->l('Add new customers to this template', null, 'rewardsgenericplugin').'</b><br/>
							 	<label>'.$this->l('Search for a customer', null, 'rewardsgenericplugin').'</label><input type="text" size="30" id="new_customer" value="" /> '.$this->l('Search will be applied on id_customer, firstname, lastname, email', null, 'rewardsgenericplugin').'<br/>
								<label>'.$this->l('Add from a group', null, 'rewardsgenericplugin').'</label>
								<script>
									var text1 = "'.$this->l('Do you really want to add customers from that group ?', null, 'rewardsgenericplugin').'";
									var text2 = "'.$this->l('Add from a group', null, 'rewardsgenericplugin').'";
								</script>
								<select class="add_from_group" name="add_from_group" onChange="addTemplateCustomersFromGroup(text1, text2)"/>
									<option value="0">'.$this->l('Choose a group', null, 'rewardsgenericplugin').'</option>';
			$groups = Group::getGroups((int)Configuration::get('PS_LANG_DEFAULT'));
			foreach ($groups AS $group)
				$html .= '<option value="'.$group['id_group'].'">'.$group['name'].'</option>';
			$html .= '
								</select> '.$this->l('All customers from that group already linked to another template will be moved to that one', null, 'rewardsgenericplugin').'
							</div>
							<script>
								var idText="'.$this->l('ID').'";
								var firstnameText="'.$this->l('Firstname').'";
								var lastnameText="'.$this->l('Lastname').'";
								var emailText="'.$this->l('Email').'";
  								var footer_pager = "'.$this->l('{startRow} to {endRow} of {totalRows} rows', null, 'rewardsgenericplugin').'";
  								initTemplate('.(version_compare(_PS_VERSION_, '1.6', '>=') ? 'true' : 'false').');
  							</script>
						</div>';
		}
		$html .= '
					</fieldset>
				</form>
			</div>';
		return $html;
	}

	protected function getCategoriesTree($categories) {
		$root = Category::getRootCategory();
		if (version_compare(_PS_VERSION_, '1.6', '>=')) {
			$tree = new HelperTreeCategories('categoryBox');
			$tree->setRootCategory($root->id)
				->setInputName('categoryBox')
				->setUseCheckBox(true)
				->setUseSearch(false)
				->setSelectedCategories($categories);
			return $tree->render();
		} else {
			$tab_root = array('id_category' => $root->id, 'name' => $root->name);
			$helper = new Helper();
			return $helper->renderCategoryTree($tab_root, $categories, 'categoryBox', false, false, array(), false, true);
		}
	}

	public function instanceDefaultStates() {
		$this->rewardStateDefault = new RewardsStateModel(RewardsStateModel::getDefaultId());
		$this->rewardStateValidation = new RewardsStateModel(RewardsStateModel::getValidationId());
		$this->rewardStateCancel = new RewardsStateModel(RewardsStateModel::getCancelId());
		$this->rewardStateConvert = new RewardsStateModel(RewardsStateModel::getConvertId());
		$this->rewardStateReturnPeriod = new RewardsStateModel(RewardsStateModel::getReturnPeriodId());
		$this->rewardStateWaitingPayment = new RewardsStateModel(RewardsStateModel::getWaitingPaymentId());
		$this->rewardStatePaid = new RewardsStateModel(RewardsStateModel::getPaidId());
	}

	abstract public function install();
	abstract public function uninstall();
	abstract protected function postProcess($params=null);
	abstract public function isActive();
	abstract public function isRewardsAccountVisible();
	abstract public function getTitle();
	abstract public function getDetails($reward, $admin);
	abstract public function displayForm();
}