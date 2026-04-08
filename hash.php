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
            if (in_array($order_id, $valor_id)) {
                $order_idpost = Tools::getValue('order_id');
                $clave = array_search($order_idpost, $valor_id);
                if ($clave !== false) {
                    $_SESSION['rand'][$clave]['HASH'] = $rand;
                }
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

        // Validar que hash y order_id coincidan como par
        foreach ($_SESSION['rand'] as $entry) {
            if ($entry['ORDER_ID'] == $order_id && $entry['HASH'] == $hash) {
                return true;
            }
        }

        return false;
    }
}
