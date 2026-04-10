<?php

class AdminNacexLogsController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function setMedia($isNewTheme = false)
    {
        $this->addCSS(_MODULE_DIR_ . 'nacex/css/nacex.css', 'all', null, true);
        $this->addJS(_MODULE_DIR_ . 'nacex/js/nacexlogs.js');
        parent::setMedia();
    }

    public function initContent()
    {
        parent::initContent();

        $html = "
            <div id='ncx-loading' style='display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.7);z-index:9999;'>
                <div style='position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);'>
                    <i class='material-icons' style='font-size:48px;color:#25b9d7;animation:spin 1s linear infinite;'>autorenew</i>
                </div>
            </div>
            <style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>
            <script>
                var Base_uri = '" . __PS_BASE_URI__ . "';
                $(document).ready(function() {
                    nacexlogs.get('init', Base_uri);
                });
            </script>
            <div class='panel'>
                <div id='cabecera'></div>
                <div id='resultado'></div>
            </div>";

        $this->context->smarty->assign('content', $html);
    }
}
