<?php

include_once dirname(__FILE__) . '/../../config/config.inc.php';
include_once dirname(__FILE__) . '/../../init.php';
include_once dirname(__FILE__) . '/nacexDAO.php';
include_once dirname(__FILE__) . '/nacex.php';

// Verificar que el usuario es un admin logueado via cookie
$cookie = new Cookie('psAdmin');
if (!$cookie->id_employee) {
    die('<div class="alert alert-danger">Unauthorized</div>');
}

try {
    $nacex = new nacex();
    $result = nacexDAO::initNcxZones();
    if ($result) {
        echo '<div style="margin-top:10px">';
        echo '<div class="alert alert-success">' . $nacex->l('Zones created, countries assigned, and Nacex carriers configured successfully.') . '</div>';
        echo '<div class="alert alert-info">' . $nacex->l('Review your carrier zone assignments in Shipping > Carriers if you have other carriers configured.') . '</div>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-danger">' . $nacex->l('Error creating zones. Check the log for details.') . '</div>';
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    nacexutils::writeNacexLog('initZones :: EXCEPTION: ' . $e->getMessage());
}
