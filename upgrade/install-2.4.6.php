<?php
/**
 * File: /upgrade/install-2.4.6.php
 */


function upgrade_module_2_4_6($object)
{
    $object->uninstallTab();
    $object->install();

    $query = Db::getInstance()->ExecuteS('SELECT id_carrier FROM ' . _DB_PREFIX_ . 'carrier WHERE ncx IS NOT NULL');

    if (!is_null($query) || $query != '') {
        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'carrier SET shipping_external=1, external_module_name="nacex" WHERE ncx IS NOT NULL');
    }

    return true;
}