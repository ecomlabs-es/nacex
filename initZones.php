<?php

include_once dirname(__FILE__) . '/../../config/config.inc.php';
include_once dirname(__FILE__) . '/../../init.php';
include_once dirname(__FILE__) . '/nacexDAO.php';
include_once dirname(__FILE__) . '/nacex.php';

// Verificar que el usuario es un admin logueado
$context = Context::getContext();
if (!$context->employee || !$context->employee->id) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

$nacex = new nacex();
nacexDAO::initNcxZones();
echo '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-success conf" style="width:auto">' . $nacex->l('Zones created and initialised') . '</div></div>';
