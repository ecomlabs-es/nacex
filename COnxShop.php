<?php
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');
?>
    <html>
    <head>
        <meta http-equiv='content-type' content='text/html;charset=iso-8859-1'>
    </head>
    <h1 id="cabecerashop" style="color:orange;"><?php echo $this->l('Nacexshop delivery points') ?></h1>
    </body>
    <link type='text/css' rel='stylesheet'
          href='<?php echo _PS_BASE_URL_ . __PS_BASE_URI__ ?>modules/nacex/css/nacex.css'/>
    <link type='text/css' rel='stylesheet'
          href='<?php echo _PS_BASE_URL_ . __PS_BASE_URI__ ?>modules/nacex/css/shop_list.css'/>
    <?php
    require_once(dirname(__FILE__) . '/ROnacexshop.php');
require_once(dirname(__FILE__) . '/nacexWS.php');
$_nacex_shop = new nacexshop();
$_ws = new nacexWS();
//GET AGENCIA COORDENADAS
$_coordenadas = $_ws->get_Agencia3($_GET['cp']);
$_coordenadas = $_ws->treatmentXML($_coordenadas, 'getAgencia3');

/** Añadimos funcionalidad WS sin conexión **/

$conn = true;
if ($_coordenadas[0] != '500ERROR') {
    $_lat = floatval($_coordenadas[9]);
    $_long = floatval($_coordenadas[10]);
    //GET LIST OF NACEXSHOPS
    $_agencias = $_ws->getPuntoEntregaGPS($_lat, $_long);
    $_agencias = $_ws->treatmentXML($_agencias, 'getPuntoEntregaGPS');
    //GET & ADD SHOP NAME
    $_nacex_shop->add_nacex_shop_name($_agencias);
} else { // Si no hay conexión WS
    $conn = false;
    $return = [$_GET['cp']];
    $_agencias = $_nacex_shop->getAgenciasTratadas($return);
}

// IE adaptation - Get browser
$u_agent = $_SERVER['HTTP_USER_AGENT'];
$bname = null;

//First get the platform?
if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
    $bname = 'IE';
    $ub = 'MSIE';
} elseif (preg_match('/Trident/i', $u_agent)) {
    $bname = 'IE';
    $ub = 'MSIE';
}

//MAP OR LIST
if (Configuration::get('NACEX_GOOGLE_API') != '' && is_null($bname)) {
    echo $_nacex_shop->nacex_shop_map($_agencias, $_GET['cp'], $conn);
} else {
    echo $_nacex_shop->nacex_shop_list($_agencias, $_GET['cp'], $conn);
}
/*if (Configuration::get('NACEX_GOOGLE_API') != "") {
    echo $_nacex_shop->nacex_shop_map($_lat, $_long, $_agencias, $_GET['cp']);
} else {
    echo $_nacex_shop->nacex_shop_list($_agencias, $_GET['cp']);
}*/
?>
    <script type='text/javascript'>

    </script>
    </html>
<?php

?>