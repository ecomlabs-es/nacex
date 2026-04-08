<?php

//SET ENVIRONMENT
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');

include_once dirname(__FILE__) . '/VIunitaria.php';
$_response = [];
$_resultcodresponse = [];
$_header = VIunitaria::cabecera();

// Añadimos la URL de admin con el token, que siempre se lo pasaremos
if (isset($_GET['oToken'])) { $_resultcodresponse = router($_GET['oToken']); } else { $_resultcodresponse = router($_POST['oToken']); }

//IF WE GET RESPONSE FROM SEARCH OR CABECERA ELSE FROM PUTEXPEDICION
if ((!$_resultcodresponse) || (isset($_resultcodresponse[0]['cod_response']) || ($_GET['method'] == 'unitaria'))) {
    array_push($_response, ['cod_response' => $_resultcodresponse[0]['cod_response'],
        'header' => $_header,
        'result' => $_resultcodresponse[0]['result']
    ]);
    echo json_encode($_response);
} else {
    $viunit = new VIunitaria();
    $id_pedido = (isset($_POST['id_pedido'])) ? $_POST['id_pedido'] : $_GET['id_pedido'];
    $oToken = (isset($_POST['oToken'])) ? $_POST['oToken'] : $_GET['oToken'];
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
