<?php

class hash
{
    public static function hash_form($order_id)
    {
        $rand = random_int(100000, PHP_INT_MAX);
        $push_data = ['HASH' => $rand, 'ORDER_ID' => $order_id];
        if (!isset($_SESSION['rand'])) {
            $_SESSION['rand'] = [$push_data];
        } else {
            $valor_id = array_column($_SESSION['rand'], 'ORDER_ID');
            $clave = array_search($order_id, $valor_id);
            if ($clave !== false) {
                $_SESSION['rand'][$clave]['HASH'] = $rand;
            } else {
                $_SESSION['rand'][] = $push_data;
            }
        }
        return $rand;
    }

    public function validate_hash()
    {
        if (!isset($_SESSION['rand'])) {
            return false;
        }

        $order_id = Tools::getValue('order_id');
        $hash = Tools::getValue('hash');

        if (!$order_id || !$hash) {
            return false;
        }

        // Validar que hash y order_id coincidan como par y consumir el token
        foreach ($_SESSION['rand'] as $key => $entry) {
            if ($entry['ORDER_ID'] == $order_id && $entry['HASH'] == $hash) {
                // Invalidar el hash para evitar reenvíos (F5)
                unset($_SESSION['rand'][$key]);
                $_SESSION['rand'] = array_values($_SESSION['rand']);
                return true;
            }
        }

        return false;
    }
}
