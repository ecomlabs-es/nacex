<?php
class AdminNacexZonasController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function initContent()
    {
        /*$configure = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->module->name.'&tab_module='.$this->module->tab.'&module_name='.$this->module->name.'&token='.Tools::getAdminTokenLite('AdminModules');*/
        $configure = $this->context->link->getAdminLink('AdminZones', true);
        Tools::redirectAdmin($configure);
        die();
    }
}
?>