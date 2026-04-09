<?php

class hash
{
    private static function getStoredHashes()
    {
        $cookie = Context::getContext()->cookie;
        $data = $cookie->__get('nacex_hashes');
        if ($data) {
            $decoded = json_decode($data, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    private static function saveStoredHashes($hashes)
    {
        $cookie = Context::getContext()->cookie;
        $cookie->__set('nacex_hashes', json_encode($hashes));
        $cookie->write();
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

        // Limitar a 20 entradas para no desbordar la cookie
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
