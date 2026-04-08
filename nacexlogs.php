<?php

//20180620 mexpositop
include_once dirname(__FILE__) .'/nacexutils.php';
include_once dirname(__FILE__) .'/nacexDAO.php';
include_once dirname(__FILE__) .'/nacexDTO.php';
include_once dirname(__FILE__) .'/nacexVIEW.php';
include_once dirname(__FILE__) .'/nacexWS.php';

if (Configuration::get('NACEX_SHOW_ERRORS') == 'SI') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

class nacexlogs extends AdminController {
    private $_html = '';
    public function __construct()
    {
        $this->display = 'view';
        parent::__construct();
        $this->meta_title = $this->l('See logs');
        $this->displayName = $this->l('See logs');
        $this->title = $this->l('See logs');
        $this->description = $this->l('See logs');
        $this->bootstrap = true;

    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia();
        $this->addCSS(_MODULE_DIR_. 'nacex/css/nacex.css');
        $this->addJS(_MODULE_DIR_ . 'nacex/js/nacex.js');
        $this->addJS(_MODULE_DIR_ . 'nacex/js/nacexlogs.js');
        $this->addJS(_MODULE_DIR_ . 'nacex/js/jquery.printElement.min.js');
    }
    public function initContent() {
        $httpURL = Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http';
        // Buscamos el http y lo reemplazamos por https o viceversa
        $storeURL = strpos(_PS_BASE_URL_, $httpURL) === false ? str_replace(substr(_PS_BASE_URL_, 0, strpos(_PS_BASE_URL_, ':')), $httpURL, _PS_BASE_URL_) : _PS_BASE_URL_;

        $this->_html .= "<script  src='https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.6/dist/loadingoverlay.min.js'> </script>
                       <script>
//LOAD GLOBAL PARAMETERS & GET NACEXLOGS        
                            let Base_uri ='" . $storeURL . "';
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
