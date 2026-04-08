<?php

include dirname(__FILE__) . '/../../config/config.inc.php';
include dirname(__FILE__) . '/../../init.php';
include_once _PS_MODULE_DIR_ . '/nacex/nacexWS.php';

$cp = preg_replace('/[^a-zA-Z0-9\-]/', '', Tools::getValue('cp', ''));
$shopCodSelected = pSQL(Tools::getValue('shopCod', ''));
$nWS = new nacexWS();

$shop_latlong = $nWS->get_Agencia3($cp);
$shop_latlong = $nWS->treatmentXML($shop_latlong, 'getAgencia3');

if (!is_array($shop_latlong) || empty($shop_latlong)) {
    die('<select id="cpPointsChoices" name="cpPointsChoices"><option value="">No se encontraron puntos</option></select>');
}

$shop_codigo = $shop_latlong[0];
$shop_lat = isset($shop_latlong[9]) ? $shop_latlong[9] : '';
$shop_lon = isset($shop_latlong[10]) ? $shop_latlong[10] : '';
$agencia = true;
$select_tiendas = $nWS->getSelectShopsValues($shop_codigo, $shop_lat, $shop_lon, $agencia, $shopCodSelected);

$response = '<select id="cpPointsChoices" name="cpPointsChoices">';
$response .= $select_tiendas;
$response .= '</select>';

echo $response;
