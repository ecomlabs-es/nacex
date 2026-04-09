<?php

class hash
{
    private static function getStoredHashes()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['nacex_hashes']) && is_array($_SESSION['nacex_hashes'])
            ? $_SESSION['nacex_hashes']
            : [];
    }

    private static function saveStoredHashes($hashes)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['nacex_hashes'] = $hashes;
    }

    public static function hash_form($order_id)
    {
        $rand = random_int(100000, PHP_INT_MAX);
        $hashes = self::getStoredHashes();

        $found = false;
        foreach ($hashes as $key => $entry) {
            if ($entry['ORDER_ID'] == $order_id) {
                $hashes[$key]['HASH'] = $rand;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $hashes[] = ['HASH' => $rand, 'ORDER_ID' => $order_id];
        }

        // Limitar a 20 entradas para no desbordar la sesión
        if (count($hashes) > 20) {
            $hashes = array_slice($hashes, -20);
        }

        self::saveStoredHashes($hashes);
        return $rand;
    }

    public function validate_hash()
    {
        $order_id = Tools::getValue('order_id');
        $hash = Tools::getValue('hash');

        if (!$order_id || !$hash) {
            return false;
        }

        $hashes = self::getStoredHashes();

        foreach ($hashes as $key => $entry) {
            if ($entry['ORDER_ID'] == $order_id && $entry['HASH'] == $hash) {
                unset($hashes[$key]);
                self::saveStoredHashes(array_values($hashes));
                return true;
            }
        }

        return false;
    }
}
