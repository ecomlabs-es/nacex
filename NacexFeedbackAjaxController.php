<?php
//SET ENVIRONMENT
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');
include_once dirname(__FILE__) . '/nacexFeedback.php';
include_once dirname(__FILE__) . '/nacexWS.php';

$fb = new NacexFeedback();
$nws = new NacexWS();

$filename = isset($_POST['filename']) ? $_POST['filename'] : '';
if($filename)
    $fb->deleteEmlFile($filename);

if(isset($_POST['tipo']) && isset($_POST['nombre']) && isset($_POST['email'])) {
    $tipo       = $_POST['tipo'];
    $tipoId     = $_POST['tipoId'];
    $nombre     = $_POST['nombre'];
    $company   = $_POST['company'];
    $email      = $_POST['email'];
    $telf       = $_POST['telf'];
    $consulta   = nl2br($_POST['consulta']);    // El nl2br es para que mantenga los saltos de línea
    $copia = isset($_POST['copia']) ? $_POST['copia'] : null;


    $fb->createEmlFile($tipo, $tipoId, $nombre, $company, $telf, $consulta, $copia, $email);

    $receiverInfo = $fb->getReceiverDetail($tipoId);

    $to = $receiverInfo;
    $subject = 'Feedback Ecommerce | ' . $tipo . ' de la tienda ' . Configuration::get('PS_SHOP_NAME');

    !is_null($copia) ? $bcc = $email : $bcc = null;
    Configuration::get('NACEX_FEEDBACK_SMTP') == 'SI' ? $smtp = true : $smtp = null;

    $templateData = array(
        '{tienda}' => Configuration::get('PS_SHOP_NAME'),
        '{system_info}' => $nws->getSystemInfo(),
        '{usuarioWs}' => Configuration::get('NACEX_WSUSERNAME'),
        '{url_tienda}' => _PS_BASE_URL_ ,
        '{abonado}' => Configuration::get('NACEX_AGCLI'),
        '{tipo}' => $tipo,
        '{tipoId}' => $tipoId,
        '{nombre}' => $nombre,
        '{company}' => $company,
        '{email}' => $email,
        '{telf}' => $telf,
        '{consulta}' => $consulta
    );

    $success = Mail::Send(
        (int)(Configuration::get('PS_LANG_DEFAULT')),
        'feedback', //need to change the template directory to point to custom module
        $subject,
        $templateData,
        $to,
        null,
        Configuration::get('NACEX_FEEDBACK_SENDER'),
        null,
        null,
        $smtp,   // Opción de configuración
        dirname(__FILE__) . '/mails/', // 11th parameter is template path
        false,
        null,
        $bcc,
        null,
        null
    );

    // Si se ejecuta el mensaje de éxito es que se ha enviado el mail
    if ($success)
        $fb->deleteEmlFile();

    //return json_encode(array('success' => $success));
    echo json_encode(array('success' => $success));
}