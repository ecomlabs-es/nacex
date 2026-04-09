<?php

class hash
{
    private static $requestCache = [];

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
        if (isset(self::$requestCache[$order_id])) {
            return self::$requestCache[$order_id];
        }

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

        if (count($hashes) > 20) {
            $hashes = array_slice($hashes, -20);
        }

        self::saveStoredHashes($hashes);
        self::$requestCache[$order_id] = $rand;

        nacexutils::writeNacexLog('hash_form :: order_id: ' . $order_id . ' | hash: ' . $rand . ' | session_id: ' . session_id());

        return $rand;
    }

    public function validate_hash()
    {
        $order_id = Tools::getValue('order_id');
        $hash = Tools::getValue('hash');

        nacexutils::writeNacexLog('validate_hash :: order_id: ' . $order_id . ' | hash_recibido: ' . $hash . ' | session_id: ' . session_id());

        if (!$order_id || !$hash) {
            nacexutils::writeNacexLog('validate_hash :: FAIL - order_id o hash vacíos');
            return false;
        }

        $hashes = self::getStoredHashes();
        nacexutils::writeNacexLog('validate_hash :: hashes en sesión: ' . json_encode($hashes));

        foreach ($hashes as $key => $entry) {
            if ($entry['ORDER_ID'] == $order_id && $entry['HASH'] == $hash) {
                unset($hashes[$key]);
                self::saveStoredHashes(array_values($hashes));
                nacexutils::writeNacexLog('validate_hash :: OK');
                return true;
            }
        }

        nacexutils::writeNacexLog('validate_hash :: FAIL - hash no encontrado');
        return false;
    }
}
