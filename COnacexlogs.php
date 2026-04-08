<?php

//SET ENVIRONMENT
include dirname(__FILE__) . '/../../config/config.inc.php';
include dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ROnacexlogs.php';
$_router = new ROnacexlogs();

$method = Tools::getValue('method', '');
$file = Tools::getValue('file', '*');

switch ($method) {
    case 'init':
    case 'refresh':
        $_router->init_delete_refresh();
        break;
    case 'delete':
    case 'delete_all':
        $_router->init_delete_refresh($file);
        break;
    case 'read':
        $_router->read($file);
        break;
    default:
        $_response = [];
        $_response[] = [
            'cod_response' => '404',
            'header' => '',
            'result' => '<center><h1>Error: Method not found</h1></center>'
        ];
        echo json_encode($_response);
}
