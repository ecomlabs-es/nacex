<?php

class MOunitaria
{
    public static function select_order($url)
    {
        include_once dirname(__FILE__) . '/VIunitaria.php';
        $id_pedido = (int) (isset($_GET['pedido']) ? $_GET['pedido'] : Tools::getValue('id_pedido'));
        $_query = Db::getInstance()->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = ' . $id_pedido
        );

        $_viunitaria = new VIunitaria();
        $_return = $_viunitaria->table($_query, $url, $id_pedido);
        return $_return;
    }

    public static function select_expedition($id_order)
    {
        $id_order = (int) $id_order;
        $_query = Db::getInstance()->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'nacex_expediciones WHERE id_envio_order = ' . $id_order
        );
        return $_query;
    }
}
