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
$result = nacexDAO::initNcxZones();
if ($result) {
    echo '<div class="bootstrap" style="margin-top:10px">';
    echo '<div class="alert alert-success conf" style="width:auto">' . $nacex->l('Zones and carriers created successfully.') . '</div>';
    echo '<div class="alert alert-info conf" style="width:auto">' . $nacex->l('Remember to assign countries and states to the new NCX zones from International > Locations > Zones in your back office.') . '</div>';
    echo '</div>';
} else {
    echo '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-danger conf" style="width:auto">' . $nacex->l('Error creating zones. Check the log for details.') . '</div></div>';
}
