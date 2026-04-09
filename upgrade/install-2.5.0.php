<?php
/**
 * Upgrade to 2.5.0
 * - Register new hooks (PS 1.7.8+)
 * - Unregister legacy hooks
 * - Update tabs
 */

function upgrade_module_2_5_0($object)
{
    // Hooks actuales que deben estar registrados
    $hooksToRegister = [
        'actionCarrierUpdate',
        'displayBeforeCarrier',
        'displayOrderConfirmation',
        'displayOrderDetail',
        'displayPDFInvoice',
        'displayHeader',
        'displayBackOfficeHeader',
        'displayBeforeBodyClosingTag',
        'actionValidateOrder',
        'displayAdminOrderMainBottom',
        'actionOrderGridQueryBuilderModifier',
    ];

    // Hooks legacy que hay que eliminar
    $hooksToUnregister = [
        'adminOrder',
        'ActionAdminOrdersListingFieldsModifier',
        'displayAdminOrder',
        'orderDetailDisplayed',
    ];

    // Registrar hooks nuevos (registerHook ignora si ya existe)
    foreach ($hooksToRegister as $hook) {
        $object->registerHook($hook);
    }

    // Eliminar hooks legacy
    foreach ($hooksToUnregister as $hook) {
        $object->unregisterHook($hook);
    }

    // Actualizar tabs
    $object->uninstallTab();
    $object->installTab();

    return true;
}
