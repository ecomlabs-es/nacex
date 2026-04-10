<?php

include dirname(__FILE__) . '/../../config/config.inc.php';
include dirname(__FILE__) . '/../../init.php';

// Verificar que el usuario es un empleado autenticado
$cookie = new Cookie('psAdmin');
if (!$cookie->id_employee) {
    http_response_code(403);
    die(json_encode([['cod_response' => '403', 'header' => '', 'result' => 'Unauthorized']]));
}

// Forzar idioma del empleado para traducciones
if ($cookie->id_lang) {
    Context::getContext()->language = new Language((int) $cookie->id_lang);
}

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
        echo json_encode([['cod_response' => '404', 'header' => '', 'result' => '<div class="alert alert-danger">Error: Method not found</div>']]);
}
