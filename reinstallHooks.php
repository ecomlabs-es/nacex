<?php

include_once dirname(__FILE__) . '/../../config/config.inc.php';
include_once dirname(__FILE__) . '/../../init.php';

// Verificar admin
$cookie = new Cookie('psAdmin');
if (!$cookie->id_employee) {
    die('<div class="alert alert-danger">Unauthorized</div>');
}

try {
    $nacex = Module::getInstanceByName('nacex');

    // Hooks que deben estar registrados (PS 1.7.8+)
    $hooksToRegister = [
        'actionCarrierUpdate',
        'displayBeforeCarrier',
        'displayOrderConfirmation',
        'displayOrderDetail',
        'displayHeader',
        'displayBackOfficeHeader',
        'displayBeforeBodyClosingTag',
        'actionValidateOrder',
        'displayAdminOrderMainBottom',
        'actionOrderGridQueryBuilderModifier',
    ];

    // Hooks legacy a eliminar
    $hooksToUnregister = [
        'adminOrder',
        'ActionAdminOrdersListingFieldsModifier',
        'displayAdminOrder',
        'orderDetailDisplayed',
        'displayPDFInvoice',
    ];

    foreach ($hooksToRegister as $hook) {
        $nacex->registerHook($hook);
    }

    foreach ($hooksToUnregister as $hook) {
        $nacex->unregisterHook($hook);
    }

    echo '<div class="alert alert-success">' . $nacex->l('Hooks reinstalled successfully.') . ' (' . count($hooksToRegister) . ' registered, ' . count($hooksToUnregister) . ' legacy removed)</div>';
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
