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
        $this->addJS('https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.6/dist/loadingoverlay.min.js');
        parent::setMedia();
    }

    public function initContent()
    {
        parent::initContent();

        $html = "
            <script>
                var Base_uri = '" . __PS_BASE_URI__ . "';
                $(document).ready(function() {
                    nacexlogs.get('init', Base_uri);
                });
            </script>
            <div id='cabecera'></div>
            <div id='resultado'></div>";

        $this->context->smarty->assign('content', $html);
    }
}
