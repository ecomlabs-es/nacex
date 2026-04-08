<?php
/**
 * File: /upgrade/install-2.4.3.php
 */


function upgrade_module_2_4_3($object)
{
    $object->uninstallTab();
    $object->install();

    /* Si existen en la BBDD rellenos los campos de:
        - external_module_name = nacex
        - shipping_external = 1
    en la tabla Carriers,los "borramos" */

    $query = Db::getInstance()->ExecuteS('SELECT id_carrier FROM ' . _DB_PREFIX_ . 'carrier WHERE ncx IS NOT NULL AND shipping_external = 1');

    if (!is_null($query) || $query != '') Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'carrier SET shipping_external=0, external_module_name="" WHERE ncx IS NOT NULL');

    return true;
}