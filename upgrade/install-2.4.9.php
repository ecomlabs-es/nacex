<?php
/**
 * File: /upgrade/install-2.4.9.php
 */


function upgrade_module_2_4_9($object)
{
    $object->uninstallTab();
    $object->install();

    return true;
}