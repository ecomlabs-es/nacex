<?php

class hash
{
    private static function getStoredHash($order_id)
    {
        $sql = 'SELECT hash FROM ' . _DB_PREFIX_ . 'nacex_hash WHERE order_id = ' . (int)$order_id;
        return Db::getInstance()->getValue($sql);
    }

    private static function saveHash($order_id, $hash)
    {
        $db = Db::getInstance();
        $existing = self::getStoredHash($order_id);
        if ($existing) {
            $db->update('nacex_hash', ['hash' => pSQL($hash)], 'order_id = ' . (int)$order_id);
        } else {
            $db->insert('nacex_hash', ['order_id' => (int)$order_id, 'hash' => pSQL($hash)]);
        }
    }

    private static function deleteHash($order_id)
    {
        Db::getInstance()->delete('nacex_hash', 'order_id = ' . (int)$order_id);
    }

    public static function ensureTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'nacex_hash (
            order_id INT UNSIGNED NOT NULL PRIMARY KEY,
            hash VARCHAR(64) NOT NULL
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        Db::getInstance()->execute($sql);
    }

    public static function hash_form($order_id)
    {
        self::ensureTable();

        // Si ya existe un hash para este pedido, reutilizarlo
        $existing = self::getStoredHash($order_id);
        if ($existing) {
            return $existing;
        }

        // No existe, generar uno nuevo
        $rand = random_int(100000, PHP_INT_MAX);
        self::saveHash($order_id, $rand);
        return $rand;
    }

    public function validate_hash()
    {
        self::ensureTable();

        $order_id = Tools::getValue('order_id');
        $hash = Tools::getValue('hash');

        if (!$order_id || !$hash) {
            return false;
        }

        $stored = self::getStoredHash($order_id);
        if ($stored && $stored == $hash) {
            self::deleteHash($order_id);
            return true;
        }

        return false;
    }
}
