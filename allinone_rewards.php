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

require_once(_PS_MODULE_DIR_.'/allinone_rewards/plugins/RewardsCorePlugin.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/plugins/RewardsLoyaltyPlugin.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/plugins/RewardsSponsorshipPlugin.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/plugins/RewardsFacebookPlugin.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/models/RewardsStateModel.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/models/RewardsModel.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/models/RewardsSponsorshipModel.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/models/RewardsSponsorshipCodeModel.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/models/RewardsPaymentModel.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/models/RewardsFacebookModel.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/models/RewardsAccountModel.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/models/RewardsTemplateModel.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/models/RewardsProductModel.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/models/RewardsGiftProductModel.php');
require_once(_PS_MODULE_DIR_.'/allinone_rewards/models/RewardsGiftProductAttributeModel.php');

class allinone_rewards extends Module
{
	public $addons = false;
	public $html = '';
	public $confirmation = '';
	public $errors = '';
	public $path = __FILE__;
	private static $_categories;

	public function __construct($init=true)
	{
		$this->name = 'allinone_rewards';
		$this->tab = 'advertising_marketing';
		$this->version = '4.1.7';
		$this->author = 'Prestaplugins';
		$this->need_instance = 1;
		$this->ps_versions_compliancy['min'] = '1.5.0.1';
		$this->module_key = 'a5f535f18bd0a7a74d44b578250baca1';
		$this->controllers = array('rewards', 'sponsorship', 'gifts');

		// Plugins to install : loyalty, sponsorship, facebook, sendtoafriend...
		$this->core = new RewardsCorePlugin($this);
		$this->loyalty = new RewardsLoyaltyPlugin($this);
		$this->sponsorship = new RewardsSponsorshipPlugin($this);
		$this->facebook = new RewardsFacebookPlugin($this);
		$this->plugins = array($this->core, $this->loyalty, $this->sponsorship, $this->facebook);

		parent::__construct();

		$this->displayName = $this->l('All-in-one Rewards : loyalty, multi levels sponsorship, affiliation, Facebook...');
		$this->description = $this->l('This module allows your customers to earn rewards while developing SEO and reputation of your shop: loyalty program, sponsorship program (multi-level, self-promotional),... In addition, the rewards are all grouped into a single account!');
		$this->confirmUninstall = $this->l('Do you really want to remove this module and all of its settings (customer\'s rewards and sponsorship won\'t be removed) ?');

		// add the warnings for each plugin
		if ($this->active)
			foreach($this->plugins as $plugin)
				$plugin->checkWarning();

		// Can happen if upgraded from a version of the module made for presta 1.4 and > 1.7 (so update 1.7 of the module made for 1.5 has not been executed)
		// so we have to launch the update 1.7 manually
		if ($init && Configuration::get('REWARDS_VERSION') && version_compare(Configuration::get('REWARDS_VERSION'), '1.7', '>=') && Configuration::get('REWARDS_MINIMAL_SHIPPING')==NULL) {
			include(_PS_MODULE_DIR_.'/allinone_rewards/upgrade/install-1.7.php');
			upgrade_module_1_7($this);
		}

		// Bug in the first prestashop version 1.5.3.1, upgrades are not executed
		if ($init && version_compare(_PS_VERSION_, '1.5.3.1', '=') && Configuration::get('REWARDS_VERSION') && version_compare($this->version, Configuration::get('REWARDS_VERSION'), '>') && Module::needUpgrade($this) == null) {
			$this->installed = true;
			Module::initUpgradeModule($this);
			Module::$modules_cache[$this->name]['upgrade']['upgraded_from'] = Configuration::get('REWARDS_VERSION');
			Module::loadUpgradeVersionList($this->name, $this->version, Configuration::get('REWARDS_VERSION'));
			$this->runUpgradeModule();
		}
	}

	public function install() {
		if (!parent::install() || !$this->_installConf() || !$this->_installPlugins() || !$this->_installQuickAccess())
			return false;
		return true;
	}

	private function _installConf() {
		if (!Configuration::updateValue('REWARDS_VERSION', $this->version)
		|| !Configuration::updateValue('REWARDS_INITIAL_CONDITIONS', 0)
		|| !Configuration::updateGlobalValue('PS_CART_RULE_FEATURE_ACTIVE', 1))
			return false;
		return true;
	}

	public function uninstall() {
		if (!parent::uninstall() || !$this->_uninstallPlugins() || !$this->_uninstallQuickAccess())
			return false;
		// reload configuration cache
		Configuration::loadConfiguration();
		return true;
	}

	private function _installPlugins() {
		foreach($this->plugins as $plugin) {
			if (!$plugin->install()) {
				return false;
			}
		}
		return true;
	}

	private function _uninstallPlugins() {
		foreach($this->plugins as $plugin) {
			if (!$plugin->uninstall()) {
				return false;
			}
		}
		return true;
	}

	private function _installQuickAccess() {
		$qa = new QuickAccess();
		foreach (Language::getLanguages() AS $language)
			$qa->name[(int)$language['id_lang']] = "All-in-one Rewards";
		$qa->link = "index.php?controller=AdminModules&configure=allinone_rewards&tab_module=&module_name=allinone_rewards";
		$qa->new_window = 0;
		$qa->save();
		return true;
	}

	private function _uninstallQuickAccess() {
		$qa = Db::getInstance()->getValue('
			SELECT id_quick_access FROM `'._DB_PREFIX_.'quick_access_lang`
			WHERE `name`=\'All-in-one Rewards\'');
		if ((int)$qa > 0) {
			Db::getInstance()->Execute('
				DELETE FROM `'._DB_PREFIX_.'quick_access`
				WHERE `id_quick_access`='.(int)$qa);
			Db::getInstance()->Execute('
				DELETE FROM `'._DB_PREFIX_.'quick_access_lang`
				WHERE `id_quick_access`='.(int)$qa);
		}
		return true;
	}

	public function getContent() {
		// ajax call to the tabs
		if (Tools::getValue('ajaxtab')) {
			foreach($this->plugins as $plugin) {
				if (Tools::getValue('plugin')==$plugin->name) {
					echo $plugin->displayForm();
					exit;
				}
			}
		}

		if (!Configuration::get('REWARDS_INITIAL_CONDITIONS') &&
			($result=$this->_checkRequiredConditions()) !== true) {
				return $result;
		}
		$this->_postProcess();

		$this->context->controller->addCSS($this->getPath() . 'css/jqueryui/flick/jquery-ui-1.8.16.custom.css', 'all');
		$this->context->controller->addCSS($this->getPath() . 'js/tablesorter/css/theme.ice.css', 'all');
		$this->context->controller->addCSS($this->getPath() . 'js/tablesorter/addons/pager/jquery.tablesorter.pager.css', 'all');
		$this->context->controller->addCSS($this->getPath() . 'js/multiselect/jquery.multiselect.css', 'all');
		$this->context->controller->addCSS($this->getPath() . 'css/admin.css', 'all');

		// categories tree
		if (version_compare(_PS_VERSION_, '1.6', '<')) {
			$this->context->controller->addJS(array(
				_PS_JS_DIR_.'jquery/plugins/treeview-categories/jquery.treeview-categories.js',
				_PS_JS_DIR_.'jquery/plugins/treeview-categories/jquery.treeview-categories.async.js',
				_PS_JS_DIR_.'jquery/plugins/treeview-categories/jquery.treeview-categories.edit.js',
				_PS_JS_DIR_.'admin-categories-tree.js',
			));

			$this->context->controller->addCSS(array(
				_PS_JS_DIR_.'jquery/plugins/treeview-categories/jquery.treeview-categories.css',
			));
		} else {
			$admin_webpath = str_ireplace(_PS_CORE_DIR_, '', _PS_ADMIN_DIR_);
			$admin_webpath = preg_replace('/^'.preg_quote(DIRECTORY_SEPARATOR, '/').'/', '', $admin_webpath);
			$bo_theme = ((Validate::isLoadedObject($this->context->employee) && $this->context->employee->bo_theme) ? $this->context->employee->bo_theme : 'default');
			if (!file_exists(_PS_BO_ALL_THEMES_DIR_.$bo_theme.DIRECTORY_SEPARATOR.'template'))
				$bo_theme = 'default';
			$this->context->controller->addJS(__PS_BASE_URI__.$admin_webpath.'/themes/'.$bo_theme.'/js/tree.js');
		}

		if (version_compare(_PS_VERSION_, '1.6.0.12', '>='))
			$this->context->controller->addJS(_PS_JS_DIR_.'admin/tinymce.inc.js');
		else
			$this->context->controller->addJS(_PS_JS_DIR_.'tinymce.inc.js');
		$this->context->controller->addJS(_PS_JS_DIR_.'tiny_mce/tiny_mce.js');
		if (version_compare(_PS_VERSION_, '1.6', '>=')) {
			$this->context->controller->addJqueryPlugin('ui.tabs.min', _PS_JS_DIR_.'jquery/ui/');
			// si idtabs est déjà ajouté par un autre module, utiliser plutôt ceci (semble ne pas marcher sur 1.6.1.4, à vérifier)
			//$this->context->controller->addJqueryPlugin(array('idTabs'));
		} else
			$this->context->controller->addJS($this->getPath() . 'js/jquery-ui-1.8.16.custom.min.js');
		$this->context->controller->addJS($this->getPath() . 'js/admin.js');
		$this->context->controller->addJS($this->getPath() . 'js/tablesorter/jquery.tablesorter.min.js');
		$this->context->controller->addJS($this->getPath() . 'js/tablesorter/jquery.tablesorter.widgets.js');
		$this->context->controller->addJS($this->getPath() . 'js/tablesorter/addons/pager/jquery.tablesorter.pager.js');
		$this->context->controller->addJS($this->getPath() . 'js/multiselect/jquery.multiselect.js');

		$iso = Language::getIsoById((int)$this->context->language->id);
		$isoTinyMCE = file_exists(_PS_ROOT_DIR_.'/js/tiny_mce/langs/'.$iso.'.js') ? $iso : 'en';
		$defaultLanguage = (int)Configuration::get('PS_LANG_DEFAULT');

		$this->html .= '
		<div class="tabs general" style="display: none; margin-bottom: 50px">
			<ul>
				<li id="li-news"><a id="a-news" href="#tabs-news">'.$this->l('About / News').'</a></li>';
		foreach($this->plugins as $plugin)
			$this->html .= '<li id="li-'.$plugin->name.'"><a id="a-'.$plugin->name.'" href="'.$this->getCurrentPage($plugin->name, true).'">'.$plugin->getTitle().'</a></li>';
		$this->html .= '
			</ul>';
		foreach($this->plugins as $plugin) {
			if (Tools::getValue('plugin')==$plugin->name) {
				$this->html .= '
				<div class="tabcontent ui-tabs-panel ui-widget-content ui-corner-bottom">'.$plugin->displayForm().'</div>';
				break;
			}
		}

		$suffix = ($this->context->language->iso_code == 'fr') ? '_fr' : '_en';
		$this->html .= '
			<div id="tabs-news">
				<fieldset>
					<legend>'.$this->l('Information').'</legend>'.
					$this->l('This module has been created by').' <b>Yann BONNAILLIE - '.(!$this->addons ? '<a href="https://www.prestaplugins.com" target="_blank">Prestaplugins</a>' : 'Prestaplugins').'</b> '.(file_exists(dirname(__FILE__).'/readme_'.$this->context->language->iso_code.'.pdf') ? '<a style="margin-left: 20px" href="'._MODULE_DIR_.'allinone_rewards/readme_'.$this->context->language->iso_code.'.pdf" download="readme_'.$this->context->language->iso_code.'.pdf"><img src="'._MODULE_DIR_.'allinone_rewards/img/pdf.gif"></a><a href="'._MODULE_DIR_.'allinone_rewards/readme_'.$this->context->language->iso_code.'.pdf" download="readme_'.$this->context->language->iso_code.'.pdf">'.$this->l('Installation guide').'</a>' : '<a style="margin-left: 20px" href="'._MODULE_DIR_.'allinone_rewards/readme_en.pdf" download="readme_en.pdf"><img src="'._MODULE_DIR_.'allinone_rewards/img/pdf.gif"></a><a href="'._MODULE_DIR_.'allinone_rewards/readme_en.pdf" download="readme_en.pdf">'.$this->l('Installation guide').'</a>').
					(!$this->addons ? '<br/>'.$this->l('Contact me if you need an upgrade, custom development or bug fix on your shop.').'<br/><br/>'.
					$this->l('Please report any bug to').' <a href="mailto:contact@prestaplugins.com">contact@prestaplugins.com</a>
					<a href="https://www.facebook.com/Prestaplugins" target="_blank"><img id="facebook" src="'._MODULE_DIR_.'allinone_rewards/img/follow_facebook'.$suffix.'.png"/></a>' :
					'<br/>'.$this->l('If you have any questions about this module, please contact me using').' <a href="https://addons.prestashop.com/en/write-to-developper?id_product=4414" target="_blank">'.$this->l('Addons contact form').'</a>
					<br/><br/>'.$this->l('If you bought this module on Addons and you like it, help me to improve it by giving 5 stars in your').' <a href="http://addons.prestashop.com/en/ratings.php" target="_blank">'.$this->l('Addons account').'</a>').'
				</fieldset>
				<fieldset>
					<legend>'.$this->l('News').'</legend>'.
					$this->_getXmlRss().'
				</fieldset>
			</div>
		</div>
		<script>
			var checkAllText =  "'.$this->l('Check all').'";
			var uncheckAllText = "'.$this->l('Uncheck all').'";
			var selectedText = "'.$this->l('# value(s) checked').'";
			var noneSelectedText = "'.$this->l('Choose the values').'";
			var virtual_value = new Array();';

		$id_template = (int)Tools::getValue('rewards_core_template_id');
		$currencies = $this->getCurrencies();
		foreach ($currencies as $currency)
			$this->html .= 'virtual_value['.(int)$currency['id_currency'].'] = '.(float)MyConf::get('REWARDS_VIRTUAL_VALUE_'.(int)$currency['id_currency'], $id_template).';';
		$this->html .= '
			var virtual_name = "'.MyConf::get('REWARDS_VIRTUAL_NAME', (int)$this->context->language->id, $id_template).'";

			var languages = new Array();
			var id_language = Number('.$defaultLanguage.');
			var iso = \''.$isoTinyMCE.'\';
			var ad = \''.dirname($_SERVER["PHP_SELF"]).'\';
			var pathCSS = \''._THEME_CSS_DIR_.'\';';
		foreach (Language::getLanguages() AS $key => $language) {
			$this->html .= '
			languages['.$key.'] = {
				id_lang: '.$language['id_lang'].',
				iso_code: "'.$language['iso_code'].'",
				name: "'.$language['name'].'",
				is_default: '.($language['id_lang'] == $defaultLanguage ? 'true' : 'false').'
			};';
		}

		$this->html .= '
			var current_tab = "'.Tools::getValue('plugin').'";
			var current_subtab = "'.Tools::getValue('tabs-' . Tools::getValue('plugin')).'";
			var version = "'.(version_compare(_PS_VERSION_, '1.6', '<') ? '1.5' : '1.6').'";
		</script>';

		$this->html = '<br/>'.$this->confirmation.$this->errors.$this->html;

		return $this->html;
	}

	private function _postProcess()	{
		if (Tools::isSubmit('submitInitialConditions') && !Configuration::get('REWARDS_INITIAL_CONDITIONS')) {
			// import existing loyalty
			if (Tools::getValue('loyalty_import'))
				RewardsModel::importFromLoyalty();
			// import existing sponsorship
			if (Tools::getValue('advancedreferralprogram_import'))
				RewardsSponsorshipModel::importFromReferralProgram(true);
			else if (Tools::getValue('referralprogram_import'))
				RewardsSponsorshipModel::importFromReferralProgram();
			// import existing fancoupon
			if (Tools::getValue('fbpromote_import'))
				RewardsFacebookModel::importFromFbpromote();

			// inactive old modules
			$modules = array('loyalty', 'advancedreferralprogram', 'referralprogram', 'fbpromote');
			foreach($modules as $tmpmod) {
				if (Module::isInstalled($tmpmod) && $mod=Module::getInstanceByName($tmpmod))
					$mod->disable();
			}
			Configuration::updateValue('REWARDS_INITIAL_CONDITIONS', 1);
			$this->confirmation = $this->displayConfirmation($this->l('The module has been initialized.'));
		} else if (Tools::isSubmit('submitRegistration') && Tools::getValue('rewards_registration')) {
			Configuration::updateValue('REWARDS_REGISTRATION', Tools::getValue('rewards_registration'));
		} else if (Tools::isSubmit('submitNewVersion') && Configuration::get('REWARDS_REGISTRATION')) {
			$data = array('{registration}' => Configuration::get('REWARDS_REGISTRATION'), '{module_version}' => $this->version, '{ps_version}' => _PS_VERSION_);
			$this->sendMail($this->context->language->id, 'asknewversion', $this->l('New version of the module'), $data, 'contact@prestaplugins.com', 'Prestaplugins');
		}
	}

	private function _checkRequiredConditions() {
		if (Tools::isSubmit('submitInitialConditions')) {
			$this->_postProcess();
			return true;
		}

		// Are rewards, sponsorships or facebook empty in database ?
		// Could contains datas, if not removed by the uninstall action
		// If not empty, skip that step.
		if (RewardsModel::isNotEmpty() || RewardsSponsorshipModel::isNotEmpty() || RewardsFacebookModel::isNotEmpty())
			return true;

		// Loyalty installed ?
		$bContinue = false;
		$nbLoyalty = 0;
		$bLoyalty = false;
		if (Module::isInstalled('loyalty')) {
			$loyalty = Module::getInstanceByName('loyalty');
			$bLoyalty = (bool)$loyalty->active;
			if ((float)Configuration::get('PS_LOYALTY_POINT_VALUE') > 0)
				$nbLoyalty = Db::getInstance()->getValue('SELECT count(*) AS nb FROM `'._DB_PREFIX_.'loyalty`');
			if ($bLoyalty || $nbLoyalty > 0)
				$bContinue = true;
		}
		// Advancedreferralprogram or referralprogram installed ?
		$nbAdvancedReferralProgram = 0;
		$nbReferralProgram = 0;
		$bAdvancedReferralProgram = false;
		$bReferralProgram = false;
		if (Module::isInstalled('advancedreferralprogram')) {
			$referralprogram = Module::getInstanceByName('advancedreferralprogram');
			$bAdvancedReferralProgram = (bool)$referralprogram->active;
			$nbAdvancedReferralProgram = Db::getInstance()->getValue('SELECT count(*) AS nb FROM `'._DB_PREFIX_.'advreferralprogram`');
			if ($bAdvancedReferralProgram || $nbAdvancedReferralProgram > 0)
				$bContinue = true;
		} else if (Module::isInstalled('referralprogram')) {
			$referralprogram = Module::getInstanceByName('referralprogram');
			$bReferralProgram = (bool)$referralprogram->active;
			$nbReferralProgram = Db::getInstance()->getValue('SELECT count(*) AS nb FROM `'._DB_PREFIX_.'referralprogram`');
			if ($bReferralProgram || $nbReferralProgram > 0)
				$bContinue = true;
		}
		// Fbpromote installed ?
		$nbFbpromote = 0;
		$bFbpromote = false;
		if (Module::isInstalled('fbpromote')) {
			$fbpromote = Module::getInstanceByName('fbpromote');
			$bFbpromote = (bool)$fbpromote->active;
			$nbFbpromote = Db::getInstance()->getValue('SELECT count(*) AS nb FROM `'._DB_PREFIX_.'fb_promote`');
			if ($bFbpromote || $nbFbpromote > 0)
				$bContinue = true;
		}
		if (!$bContinue)
			return true;

		$this->html .= '
		<form style="margin-bottom: 50px" action="'.$this->getCurrentPage().'" method="post">
			<fieldset>
				<legend>'.$this->l('Initial conditions').'</legend>
				<div align="center" style="color: red; font-weight: bold; padding-bottom: 10px">'.$this->l('Since this is the first time you install this module, it must be initialized.').'</div>'.
				((int) $nbLoyalty > 0 && (float)Configuration::get('PS_LOYALTY_POINT_VALUE') > 0 ?
					'<div class="clear" style="padding-top: 10px"></div>
					<label>'.$this->l('Import the existing accounts from').' "'.$loyalty->displayName.'" </label>
					<div class="margin-form">
						<label class="t" for="loyalty_import_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="loyalty_import_on" name="loyalty_import" value="1" checked /> <label class="t" for="loyalty_import_on">' . $this->l('Yes') . '</label>
						<label class="t" for="loyalty_import_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="loyalty_import_off" name="loyalty_import" value="0" /> <label class="t" for="loyalty_import_off">' . $this->l('No') . '</label>
					</div>' : '').
				($bLoyalty === true ? '<div class="clear" style="font-weight: bold; font-style: italic; padding-bottom: 10px">'.$this->l('The module').' "'.$loyalty->displayName.'" '.$this->l('is actually active, it will be disabled automatically').'</div>' : '').
				((int) $nbAdvancedReferralProgram > 0 ?
					'<div class="clear" style="padding-top: 10px"></div>
					<label>'.$this->l('Import the existing sponsorships from').' "'.$referralprogram->displayName.'" </label>
					<div class="margin-form">
						<label class="t" for="advancedreferralprogram_import_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="advancedreferralprogram_import_on" name="advancedreferralprogram_import" value="1" checked /> <label class="t" for="advancedreferralprogram_import_on">' . $this->l('Yes') . '</label>
						<label class="t" for="advancedreferralprogram_import_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="advancedreferralprogram_import_off" name="advancedreferralprogram_import" value="0" /> <label class="t" for="advancedreferralprogram_import_off">' . $this->l('No') . '</label>
					</div>' : '').
				($bAdvancedReferralProgram === true ? '<div class="clear" style="font-weight: bold; font-style: italic; padding-bottom: 10px">'.$this->l('The module').' "'.$referralprogram->displayName.'" '.$this->l('is actually active, it will be disabled automatically').'</div>' : '').
				((int) $nbReferralProgram > 0 ?
					'<div class="clear" style="padding-top: 10px"></div>
					<label>'.$this->l('Import the existing sponsorships from').' "'.$referralprogram->displayName.'" </label>
					<div class="margin-form">
						<label class="t" for="referralprogram_import_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="referralprogram_import_on" name="referralprogram_import" value="1" checked /> <label class="t" for="referralprogram_import_on">' . $this->l('Yes') . '</label>
						<label class="t" for="referralprogram_import_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="referralprogram_import_off" name="referralprogram_import" value="0" /> <label class="t" for="referralprogram_import_off">' . $this->l('No') . '</label>
					</div>' : '').
				($bReferralProgram === true ? '<div class="clear" style="font-weight: bold; font-style: italic">'.$this->l('The module').' "'.$referralprogram->displayName.'" '.$this->l('is actually active, it will be disabled automatically').'</div>' : '').
				((int) $nbFbpromote > 0 ?
					'<div class="clear" style="padding-top: 10px"></div>
					<label>'.$this->l('Import the existing Facebook "Like" from').' "'.$fbpromote->displayName.'" </label>
					<div class="margin-form">
						<label class="t" for="fbpromote_import_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Yes').'" /></label>
						<input type="radio" id="fbpromote_import_on" name="fbpromote_import" value="1" checked /> <label class="t" for="fbpromote_import_on">' . $this->l('Yes') . '</label>
						<label class="t" for="fbpromote_import_off" style="margin-left: 10px"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('No').'" /></label>
						<input type="radio" id="fbpromote_import_off" name="fbpromote_import" value="0" /> <label class="t" for="fbpromote_import_off">' . $this->l('No') . '</label>
					</div>' : '').
				($bFbpromote === true ? '<div class="clear" style="font-weight: bold; font-style: italic; padding-bottom: 10px">'.$this->l('The module').' "'.$fbpromote->displayName.'" '.$this->l('is actually active, it will be disabled automatically').'</div>' : '').'
			</fieldset>
			<div class="clear center"><input type="submit" name="submitInitialConditions" id="submitInitialConditions" value="'.$this->l('   Initialize the module   ').'" class="button" /></div>
		</form>';

		return $this->html;
	}

	public function getCategories() {
		if (!self::$_categories)
			self::$_categories = Category::getCategories((int)$this->context->language->id, false);
		return self::$_categories;
	}

	// display news and check if a new version is available
	private function _getXmlRss() {
		$html = '';
		$bError = false;
		$data = array('version' => $this->version, 'registration' => Configuration::get('REWARDS_REGISTRATION'), 'email' => Configuration::get('PS_SHOP_EMAIL'), 'asknewversion' => Tools::isSubmit('submitNewVersion') ? 1 : 0);
		$request = http_build_query($data, '', '&');
		if (function_exists('curl_init') && $ch = @curl_init('www.prestaplugins.com/news/allinone_rewards.php')) {
			curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
			curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
            $response = @curl_exec($ch);
            @curl_close($ch);
		} else if (ini_get('allow_url_fopen')) {
			if ($fp = @fsockopen('http://www.prestaplugins.com', 80, $errno, $errstr, 50)){
				fputs($fp, "GET /news/allinone_rewards.php HTTP/1.0\r\n");
				fputs($fp, "Host: www.prestaplugins.com\r\n");
				fputs($fp, "Referer: ".$_SERVER['HTTP_HOST']."\r\n");
				fputs($fp, "Connection: close\r\n");
				$response = '';
				while (!feof($fp))
					$response .= fgets($fp, 1024);
				fclose($fp);
			} else
				$bError = true;
		} else
			$bError = true;

		if ($bError) {
			$html .= '<div style="font-weight: bold; color: red">'.$this->l('You need to enable CURL extension or fsockopen, to be informed about new version of the module').'</div>';
		} else if (!empty($response)) {
			$doc = new DOMDocument('1.0', 'UTF-8');
			@$doc->loadXML($response);
			$version = $doc->getElementsByTagName('version')->item(0)->nodeValue;
			$registered = (int)$doc->getElementsByTagName('registered')->item(0)->nodeValue;
			$asknewversion = (int)$doc->getElementsByTagName('asknewversion')->item(0)->nodeValue;
			$newslist = $doc->getElementsByTagName('news');

			if ($registered == -1) {
				Configuration::updateValue('REWARDS_REGISTRATION', '');
				$html .= '<div style="margin-left: 10%; font-weight: bold; color: red">'.$this->l('Your order reference was not valid, please try again.').'</div>';
			}
			if ($registered < 1) {
				if (Configuration::get('REWARDS_REGISTRATION') != '') {
					$html .= '<div style="margin-left: 10%; margin-bottom: 20px; font-weight: bold">'.$this->l('Thanks for your patience, your license is being validated.').'</div>';
				} else {
					$html .= '
						<div style="margin-left: 10%; margin-bottom: 20px;">'.
							$this->l('Please register by entering your order reference. This way, if you are eligible for further updates you will be able to claim for them automatically.').'<br/>
							<form id="rewards_registration_form" method="post">
							<input type="text" id="rewards_registration" name="rewards_registration" value="'.Configuration::get('REWARDS_REGISTRATION').'">
							<input type="submit" name="submitRegistration" class="button" value="'.$this->l('Register').'">
							</form>
						</div>';
				}
			} else
				$html .= '<div style="margin-left: 10%; font-weight: bold">'.$this->l('Congratulation, your license is registered.').'</div>';

			$html .= '<div style="margin-left: 10%; margin-bottom: 20px"><b>'.$this->l('Your version :').' '.$this->version.'</b>';
			if (version_compare($this->version, $version, '>='))
				$html .= '<span style="color: green; font-weight: bold; display: inline-block; margin-left: 20px">'.$this->l('You are currently using the last version of this module').'</span>';
			else {
				$html .= '<span style="color: red; font-weight: bold; display: inline-block; margin-left: 20px">'.$this->l('A new version of this module is available').' - '.$this->l('Version').' '.$version.'</span>';
				if ($registered && !$asknewversion) {
					$html .= '
						<form method="post" style="display: inline; padding-left: 10px">
							<input class="button" type="submit" name="submitNewVersion" value="'.$this->l('Ask for the new version').'">
						</form>';
				}
			}
			$html .= '
					</div>
					<div id="news_list" style="height: 500px; overflow: auto; clear: both">';
			$i = 0;
			$suffix = ($this->context->language->iso_code == 'fr') ? '_fr' : '_en';
			foreach($newslist as $news) {
				$date = $news->getElementsByTagName('date')->item(0)->nodeValue;
				$localDate = ($this->context->language->iso_code == 'fr') ? date('d/m/Y', strtotime($date)) : date('Y-m-d', strtotime($date));
				$title = $news->getElementsByTagName('title'.$suffix)->item(0)->nodeValue;
				$text = $news->getElementsByTagName('text'.$suffix)->item(0)->nodeValue;

				$new = '';
				if (empty($this->context->cookie->rewards_news) || $this->context->cookie->rewards_news <= $date) {
					$new .= '<img src="'._MODULE_DIR_.'allinone_rewards/img/new.gif"> ';
				}
				$html .= '
					<div style="float: left; width: 10%; font-weight: bold">'.$localDate.'</div>
					<div style="float: left; width: 90%">
						<div style="font-weight: bold">'.$new.$title.'</div>
						<div style="text-align: justify">'.nl2br($text).'</div>
					</div>
					<div class="clear" style="padding-bottom: 20px"></div>';
				if ($i == 0) {
					$max_date = $date;
					$i++;
				}
			}
			$html .= '</div>';
			$this->context->cookie->rewards_news = $max_date . '00:00:00';
		}
		return $html;
	}

	/**
     * idem than Module::l but with $id_lang
     **/
    public function l2($string, $id_lang=null, $specific=false)
    {
        global $_MODULE, $_MODULES;

        if (!isset($id_lang))
        	$id_lang = Context::getContext()->language->id;

        $_MODULEStmp = $_MODULES;
        $_MODULES = array();

		$filesByPriority = array(
			// Translations in theme
			_PS_THEME_DIR_.'modules/'.$this->name.'/translations/'.Language::getIsoById((int)$id_lang).'.php',
			_PS_MODULE_DIR_.$this->name.'/translations/'.Language::getIsoById((int)$id_lang).'.php',
		);

		foreach ($filesByPriority as $file) {
			if (Tools::file_exists_cache($file) && include($file)) {
				$_MODULES = !empty($_MODULES) ? array_merge($_MODULES, $_MODULE) : $_MODULE;
			}
		}

		$source = Tools::strtolower($specific ? $specific : $this->name);
		$key = md5(str_replace('\'', '\\\'', $string));

		$ret = $string;
		$current_key = Tools::strtolower('<{'.$this->name.'}'._THEME_NAME_.'>'.$source).'_'.$key;
		$default_key = Tools::strtolower('<{'.$this->name.'}prestashop>'.$source).'_'.$key;
		if (isset($_MODULES[$current_key]))
			$ret = stripslashes($_MODULES[$current_key]);
		elseif (isset($_MODULES[$default_key]))
			$ret = stripslashes($_MODULES[$default_key]);

		$ret = str_replace('"', '&quot;', $ret);
        $_MODULES = $_MODULEStmp;
        return $ret;
    }

	public function getL($key, $id_lang=null) {
		$translations = array(
		'awaiting_validation' => $this->l2('Awaiting validation', $id_lang), // $this->l('Awaiting validation')
		'available' => $this->l2('Available', $id_lang), // $this->l('Available')
		'cancelled' => $this->l2('Cancelled', $id_lang), // $this->l('Cancelled')
		'already_converted' => $this->l2('Already converted', $id_lang), // $this->l('Already converted')
		'unavailable_on_discounts' => $this->l2('Unavailable on discounts', $id_lang), // $this->l('Unavailable on discounts')
		'return_period' => $this->l2('Waiting for return period exceeded', $id_lang), // $this->l('Waiting for return period exceeded')
		'awaiting_payment' => $this->l2('Awaiting payment', $id_lang), // $this->l('Awaiting payment')
		'paid' => $this->l2('Paid', $id_lang), // $this->l('Paid')
		'invitation' => $this->l2('Invitation from your friend', $id_lang), // $this->l('Invitation from your friend')
		'reminder' => $this->l2('Don\'t forget your rewards', $id_lang)); // $this->l('Don\'t forget your rewards')
		return (array_key_exists($key, $translations)) ? $translations[$key] : $key;
	}

	public function sendMail($id_lang, $template, $subject, $data, $mail, $name, $attachment=null) {
		if (!empty(Configuration::get('REWARDS_MAILS_IGNORED'))) {
			$ignore_list = explode(',', Configuration::get('REWARDS_MAILS_IGNORED'));
			if (is_array($ignore_list)) {
				$ignore_list = array_map('trim', $ignore_list);
				foreach($ignore_list as $ignore) {
					if (strpos($mail, $ignore) !== false)
						return false;
				}
			}
		}

		if (version_compare(_PS_VERSION_, '1.6', '>='))
			$template = '16-'.$template;
		$iso = Language::getIsoById((int)$id_lang);
		if (file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.txt') && file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.html'))
			return Mail::Send((int)$id_lang, $template, $subject, $data, $mail, $name, Configuration::get('PS_SHOP_EMAIL'), Configuration::get('PS_SHOP_NAME'), $attachment, NULL, dirname(__FILE__).'/mails/');
		else if (file_exists(dirname(__FILE__).'/mails/en/'.$template.'.txt') && file_exists(dirname(__FILE__).'/mails/en/'.$template.'.html')) {
			$id_lang = Language::getIdByIso('en');
			if ($id_lang)
				return Mail::Send((int)$id_lang, $template, $subject, $data, $mail, $name, Configuration::get('PS_SHOP_EMAIL'), Configuration::get('PS_SHOP_NAME'), $attachment, NULL, dirname(__FILE__).'/mails/');
		}
		return false;
	}

	public function getDiscountReadyForDisplay($type, $freeshipping, $value, $id_currency=null, $text=null) {
		if (!is_null($text)) {
			return $text;
		} else {
			$discount = '';
			if ((float)$value > 0) {
				if ((int)$type == 1)
					$discount = (float)$value.chr(37);
				elseif ((int)$type == 2) {
					// when sponsorship generated from back-end, id_currency is provided
					if (is_null($id_currency))
						$id_currency = $this->context->currency;
					$discount = Tools::displayPrice((float)$value, $id_currency);
				}
				$discount .= ' '.$this->l('discount on your order');
			}
			if ((int)$freeshipping == 1)
				$discount = (empty($discount) ? '' : $discount.' + ').$this->l('Free shipping');
			return $discount;
		}
	}

	// TODO : Il vaudrait mieux passer systématiquement l'id_lang, pour éviter les bugs de changement de langue sur le front qui ne sont pris en compte qu'au refresh suivant
	public function getRewardReadyForDisplay($reward, $id_currency, $id_lang=NULL, $convert=true, $id_template=NULL) {
		$context = Context::getContext();
		$currency = Currency::getCurrency((int)$id_currency);

		if (isset($context->customer)) {
			// si on a pas passé d'id_template, on va le chercher
			if (is_null($id_template))
				$id_template = (int)MyConf::getIdTemplate('core', $context->customer->id);
			if (is_null($id_lang) && version_compare(_PS_VERSION_, '1.5.4.0', '>=') && !empty($context->customer->id_lang))
				$id_lang = $context->customer->id_lang;
		}

		if (is_null($id_template))
			$id_template = 0;
		if (is_null($id_lang))
			$id_lang = $context->language->id;

		if (MyConf::get('REWARDS_VIRTUAL', null, $id_template)) {
			$reward = round($reward*(float)MyConf::get('REWARDS_VIRTUAL_VALUE_'.$currency['id_currency'], null, $id_template), 2);
			// on ajoute les décimales que si ce n'est pas un entier
			if ($reward != (int)$reward)
				$reward = number_format($reward, 2, '.', '');
			return $reward.' '.MyConf::get('REWARDS_VIRTUAL_NAME', $id_lang, $id_template);
		} else {
			if ($convert)
				return Tools::displayPrice(round(Tools::convertPrice((float)$reward, $currency), 2), (int)$currency['id_currency']);
			else
				return Tools::displayPrice(round((float)$reward, 2), (int)$currency['id_currency']);
		}
	}

	public function getPath() {
		return $this->_path;
	}

	public function getCurrentPage($plugin=NULL, $ajax=false) {
		$token = Tools::getAdminToken('AdminModules'.(int)Tab::getIdFromClassName('AdminModules').(int)$this->context->employee->id);
		$url = "index.php?controller=AdminModules&configure=allinone_rewards&tab_module=&module_name=allinone_rewards&token=".$token;
		return $url.($ajax ? '&ajaxtab=1' : '').(isset($plugin) ? '&plugin='.$plugin : '');
	}

	public function getCurrencies() {
		if (version_compare(_PS_VERSION_, '1.6.0.7', '>='))
			$currencies = Currency::getCurrencies(false, true, true);
		else if (version_compare(_PS_VERSION_, '1.6.0.2', '>=')) {
			$temp = Currency::getCurrencies();
			$old = null;
			foreach($temp as $currency) {
				if ($currency['id_currency'] != $old) {
					$old = $currency['id_currency'];
					$currencies[] = $currency;
				}
			}
		} else
			$currencies = Currency::getCurrencies();
		return $currencies;
	}


	/*********/
	/* HOOKS */
	/*********/
	public function __call($method, $arguments) {
		return $this->_genericHook($method, isset($arguments[0]) ? $arguments[0] : null);
	}

	private function _genericHook($method, $arguments=NULL) {
		$result = '';
		$temp = NULL;
		foreach($this->plugins as $plugin) {
			// verify isActive only for FrontController, admin hooks are always executed
			// hookActionCustomerAccountAdd need to be executed for sponsorship in case the default template is inactive and another template is active, because isActive return false (customer already logged).
			if ((defined('_PS_ADMIN_DIR_') || $plugin->isActive() || ($plugin->name=='sponsorship' && strtolower($method)=='hookactioncustomeraccountadd')) && method_exists($plugin, $method)) {
				$temp = $plugin->$method($arguments);
				// hookadditionalcustomerformfields ne renvoit pas un string
				if (strtolower($method)=='hookadditionalcustomerformfields')
					return $temp;
				else if ($temp !== false && $temp !== true)
					$result .= $temp;
			}
		}
		if (!empty($result))
			return $result;
		// cas où le parrainage est inactif, il faut absolument renvoyer un tableau vide
		else if (strtolower($method)=='hookadditionalcustomerformfields')
			return array();
		return false;
	}
}