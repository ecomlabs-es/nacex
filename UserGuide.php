<?php

//require_once ('CheckVersion.php');

class UserGuide
{
    //const USER_GUIDE_FILENAME = 'Manual_NXPrestashop_';
    public const USER_GUIDE_FILENAME = 'Manual_NXPrestashop';
    protected $_version;

    public function __construct() {
        //        $this->_version = new CheckVersion();
    }

    /*public function getUserGuideURL() {
        //$html = $this->_version->get_nacex_file('download-ncx-user-guide',self::USER_GUIDE_FILENAME.$this->getLocale().'.pdf');
        $html = $this->_version->get_nacex_file('download-ncx-user-guide',self::USER_GUIDE_FILENAME.'.pdf');
        $html .= "<div id='loader-download-ncx-user-guide' style='display: none;'>
                <img src='" . _MODULE_DIR_ . "nacex/images/loading.gif'>
            </div>";

        return $html;
    }*/

    //    private function getLocale() {
    //        global $cookie;
    //        $lang = Language::getIsoById( (int)$cookie->id_lang );
    //        return $lang == 'es' ? 'ES' : 'EN';
    //    }
}
