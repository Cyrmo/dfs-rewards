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

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

if (version_compare(_PS_VERSION_, '1.7', '>=')) {
    class Allinone_rewardsGiftsModuleFrontController extends ProductListingFrontController
    {
        //public $php_self = 'gifts';
        public $module;

        public function __construct()
        {
            $this->module = Module::getInstanceByName(Tools::getValue('module'));
            if (!$this->module->active)
                Tools::redirect('index');

            $this->page_name = 'module-'.$this->module->name.'-'.Dispatcher::getInstance()->getController();
            parent::__construct();
            //$this->controller_type = 'modulefront';
        }

        public function init()
        {
            if (!$this->context->customer->isLogged())
                Tools::redirect('index.php?controller=authentication');
            else if (!RewardsModel::isCustomerAllowedForGiftProduct())
                die('This functionality is not available');

            parent::init();
            $this->doProductSearch('module:allinone_rewards/views/templates/front/presta-1.7/gifts.tpl');
        }

        public function setTemplate($template, $params = array(), $locale = null)
        {
            $this->template = $template;
        }

        protected function getProductSearchQuery()
        {
            $query = new ProductSearchQuery();
            $query->setSortOrder(new SortOrder('product', 'price', 'asc'));
            return $query;
        }

        protected function getDefaultProductSearchProvider()
        {
            require_once(_PS_MODULE_DIR_.'/allinone_rewards/classes/GiftsProductsProductSearchProvider.php');
            return new GiftsProductsProductSearchProvider($this->getTranslator());
        }

        public function getListingLabel()
        {
            return $this->module->l('Gift products you can buy with your rewards', 'gifts');
        }
    }
} else {
    class Allinone_rewardsGiftsModuleFrontController extends ModuleFrontController
    {
        public $nbProducts;
        public $products;
        public $id_template;

        public function init()
        {
            if (!$this->context->customer->isLogged())
                Tools::redirect('index.php?controller=authentication');
            else if (!RewardsModel::isCustomerAllowedForGiftProduct())
                die('This functionality is not available');
            parent::init();
        }

        public function setMedia()
        {
            parent::setMedia();

            $this->addCSS(array(
                _THEME_CSS_DIR_.'category.css'     => 'all',
                _THEME_CSS_DIR_.'product_list.css' => 'all',
            ));
            $this->addJS(_THEME_JS_DIR_.'category.js');
            $this->addJS($this->module->getPath().'js/gifts.js');
        }

        public function initContent()
        {
            parent::initContent();

            if (isset($this->context->cookie->id_compare)) {
                $this->context->smarty->assign('compareProducts', CompareProduct::getCompareProducts((int)$this->context->cookie->id_compare));
            }
            // Product sort must be called before assignProductList()
            $this->productSort();
            $this->assignProductList();

            $this->context->smarty->assign(array(
                'category'              => new Category(), // prestashop 1.5 tpl is looking for a category
                'products'              => (isset($this->cat_products) && $this->cat_products) ? $this->cat_products : null,
                'comparator_max_item'   => (int)Configuration::get('PS_COMPARATOR_MAX_ITEM'),
                'nb_products'           => $this->nbProducts
            ));
            $this->setTemplate('gifts.tpl');
        }

        public function assignProductList()
        {
            $this->orderBy  = Tools::strtolower(Tools::getValue('orderby', 'price'));
            $this->orderWay = Tools::strtolower(Tools::getValue('orderway', 'asc'));

            $this->nbProducts = RewardsGiftProductModel::getGiftsProducts(null, null, null, $this->orderBy, $this->orderWay, true);
            $this->pagination((int)$this->nbProducts); // Pagination must be call after "getProducts"
            $this->cat_products = RewardsGiftProductModel::getGiftsProducts($this->context->language->id, (int)$this->p, (int)$this->n, $this->orderBy, $this->orderWay);

            if (method_exists($this, 'addColorsToProductList'))
                $this->addColorsToProductList($this->cat_products);

            Hook::exec('actionProductListModifier', array(
                'nb_products'  => &$this->nbProducts,
                'cat_products' => &$this->cat_products,
            ));

            foreach ($this->cat_products as &$product) {
                if (isset($product['id_product_attribute']) && $product['id_product_attribute'] && isset($product['product_attribute_minimal_quantity'])) {
                    $product['minimal_quantity'] = $product['product_attribute_minimal_quantity'];
                }
            }
        }
    }
}