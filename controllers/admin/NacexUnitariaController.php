<?php
class NacexUnitariaController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function initContent()
    {
        /*$configure = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->module->name.'&tab_module='.$this->module->tab.'&module_name='.$this->module->name.'&token='.Tools::getAdminTokenLite('AdminModules');
            Tools::redirectAdmin($configure);
            die();*/
        require_once  _PS_MODULE_DIR_.'/nacex/COunitaria.php';
        $_controller = new COunitaria();
        $_controller->controller();
    }
}
?>