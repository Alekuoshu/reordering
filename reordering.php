<?php
/**
* 2007-2019 Farmalisto
*
*  @author    Farmalisto <alejandro.villegas@farmalisto.com.co>
*  @copyright 2007-2019 Farmalisto
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

header('Access-Control-Allow-Origin: *');

if (!defined('_PS_VERSION_')) {
    exit;
}

class Reordering extends Module
{
    protected $config_form = false;
    protected $REORDERING_LIVE_MODE;

    public function __construct()
    {
        $this->name = 'reordering';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Farmalisto';
        $this->need_instance = 1;
        $this->display = 'view';

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Reordering');
        $this->description = $this->l('This module is for detect the last customer order');

        $this->confirmUninstall = $this->l('Are you sure want you uninstall this module?');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        $this->REORDERING_LIVE_MODE = Configuration::get('REORDERING_LIVE_MODE', true);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('REORDERING_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('REORDERING_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitReorderingModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitReorderingModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'REORDERING_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'REORDERING_LIVE_MODE' => Configuration::get('REORDERING_LIVE_MODE', true),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }

        $output = '';
        // if (Tools::isSubmit('submit')) {
            $output = $this->displayConfirmation($this->l('Settings updated'));

        // }
        return $output;

    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        // $this->context->controller->addJS($this->_path.'/views/js/reorder.js');
        // $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/reorder.js');
        
        $id_customer = Context::getContext()->customer->id;

        if(!$this->REORDERING_LIVE_MODE){
            if($this->getLastOrderId($id_customer) == false) {
                var_dump('No hay cliente logueado o el cliente no tiene ninguna orden anteriormente asociada!');
            }else {
                var_dump($this->getLastOrderId($id_customer));
            }
        }else{
            $this->context->smarty->assign('id_last_order', $this->getLastOrderId($id_customer));

            return $this->context->smarty->fetch($this->local_path.'views/templates/front/reorder.tpl');
        }
        

        
    }

    /**
     * This method return the ID of the last customer order.
     * https://pediasure.farmalisto.com.co/index.php?controller=order&submitReorder=&id_order=320
     * @since 1.7
     *
     * @return ID int
     */
    public function getLastOrderId($id_customer)
    {
        $id_shop = Context::getContext()->shop->id;
        return Db::getInstance()->getValue('
            SELECT id_order
            FROM ' . _DB_PREFIX_ . 'orders
            WHERE id_customer = ' . $id_customer . '
            AND id_shop = ' . $id_shop . '
            ORDER BY id_order DESC');
    }
}
