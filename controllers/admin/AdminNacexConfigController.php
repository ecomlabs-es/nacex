<?php

class AdminNacexConfigController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();

        $module = Module::getInstanceByName('nacex');
        $content = $module->getContent();

        $this->context->smarty->assign('content', $content);
    }
}
