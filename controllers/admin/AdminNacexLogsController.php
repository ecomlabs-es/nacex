<?php

class AdminNacexLogsController extends ModuleAdminController
{
    private $_html = '';

    public function __construct()
    {
        parent::__construct();
    }

    public function setMedia($isNewTheme = false)
    {
        $this->addCSS(_MODULE_DIR_ . 'nacex/css/nacex.css', 'all', NULL, true);
        $this->context->controller->addJS(_MODULE_DIR_ . 'nacex/js/nacexlogs.js');
        parent::setMedia();
    }

    public function init() {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();

        $httpURL = Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http';
        // Buscamos el http y lo reemplazamos por https o viceversa
        $storeURL = strpos(_PS_BASE_URL_, $httpURL) === false ? str_replace(substr(_PS_BASE_URL_, 0, strpos(_PS_BASE_URL_, ':')), $httpURL, _PS_BASE_URL_) : _PS_BASE_URL_;

        $this->_html .= "<script  src='https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.6/dist/loadingoverlay.min.js'> </script>
                       <script>
//LOAD GLOBAL PARAMETERS & GET NACEXLOGS        
                            //let Base_uri ='" . $storeURL . "';
                            let Base_uri ='" . __PS_BASE_URI__ . "';
                            function ready (){
                                nacexlogs.get('init',Base_uri);
                            }
                          $(document).ready(ready);
                       </script>     
                    <div id='cabecera'></div>
                    <div id='resultado'></div>
        ";
        $this->context->smarty->assign('content', $this->_html);
    }
}
?>