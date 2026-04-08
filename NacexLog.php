<?php

function writeNacexLog($txt)
{
    if (Configuration::get('NACEX_SAVE_LOG') == 'SI') {
        $logname = Tools::getValue('nacex_path_log', Configuration::get('NACEX_PATH_LOG')) . 'nacex_' . date('Ymd') . '.log';
        $logfile = fopen($logname, 'a');
        fwrite($logfile, '[' . date('Y-m-d H:i:s') . ']<' . _PS_VERSION_ . '> ' . $txt . "\n");
    }
}
