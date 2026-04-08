<?php
//SET ENVIRONMENT
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');
include_once dirname(__FILE__) . '/CheckVersion.php';

if($_POST && isset($_POST['action']) ) {

    if ($_POST['action'] == 'download_nacex_new_version') {
        $uploadedVersion = isset($_POST['version']) ? $_POST['version'] : '';

        $version = new CheckVersion();

        $filename = $version->get_new_version($uploadedVersion);
        $filePath = _MODULE_DIR_ . 'nacex/files/';

        header('Content-Type: application/json');
        die(json_encode(['url' => $filePath . $filename]));
    }
}