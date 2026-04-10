<?php

//SET ENVIRONMENT
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');

// Forzar idioma del empleado para traducciones
$cookie = new Cookie('psAdmin');
if ($cookie->id_lang) {
    Context::getContext()->language = new Language((int) $cookie->id_lang);
}

include_once dirname(__FILE__) . '/VIunitaria.php';
$_response = [];
$_resultcodresponse = [];
$_header = VIunitaria::cabecera();

// Añadimos la URL de admin con el token, que siempre se lo pasaremos
$oToken = Tools::getValue('oToken');
$_resultcodresponse = $oToken ? router($oToken) : [];

//IF WE GET RESPONSE FROM SEARCH OR CABECERA ELSE FROM PUTEXPEDICION
if ((!$_resultcodresponse) || (isset($_resultcodresponse[0]['cod_response']) || (Tools::getValue('method') == 'unitaria'))) {
    $_response[] = [
        'cod_response' => $_resultcodresponse[0]['cod_response'],
        'header' => $_header,
        'result' => $_resultcodresponse[0]['result']
    ];
    echo json_encode($_response);
} else {
    $viunit = new VIunitaria();
    $id_pedido = (int)Tools::getValue('id_pedido');
    echo $viunit->printTable($id_pedido, $oToken);
}

function router($url)
{

    $_response = [];
    $_response_put_expedicion = '';
    if ((isset($_GET['method'])) && ($_GET['method'] == 'search' || $_GET['method'] == 'unitaria')) {
        include_once dirname(__FILE__) . '/MOunitaria.php';
        // Cojo la URL de administrador con el token para el enlace
        $_result = MOunitaria::select_order($url);
        return $_result;

    } elseif (isset($_POST['method']) && $_POST['method'] == 'crear_expedicion') {
        include_once dirname(__FILE__) . '/nacexDAO.php';
        include_once dirname(__FILE__) . '/nacexWS.php';
        $_datospedido = nacexDAO::getDatosPedido($_POST['id_pedido']);
        $_result = nacexWS::putExpedicion($_POST['id_pedido'], $_datospedido, null, Tools::isSubmit('submitcambioexpedicion'), true);
        //IF WE GET ERROR FROM PUTEXPEDICION ELSE OK
        return $_result;
        //CONTROLLER INIT
    } elseif (isset($_POST['method']) && $_POST['method'] == 'printDevolucion') {
        include_once dirname(__FILE__) . '/nacexDAO.php';
        include_once dirname(__FILE__) . '/nacexWS.php';
        $_datospedido = nacexDAO::getDatosPedido($_POST['id_pedido']);
        $_result = nacexWS::putExpedicionDev($_POST['id_pedido'], $_datospedido, null, Tools::isSubmit('submitcambioexpedicion'), true);
        //IF WE GET ERROR FROM PUTEXPEDICION ELSE OK
        return $_result;
        //CONTROLLER INIT
    } else {
        array_push($_response, ['cod_response' => '100',
            'result' => ''
        ]);
    }
    return $_response;
}
