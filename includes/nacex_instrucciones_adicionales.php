<?php

/** @var string $text */
/** @var string $order_id */

$order = wc_get_order($order_id);
$items_info = [];

foreach ($order->get_items() as $item) {
    /** @var WC_Order_Item $item */
    $item_name = $item->get_name();
    $item_quantity = $item->get_quantity();

    $items_info[] = "$item_quantity # $item_name";
}

$text = implode(' ** ', $items_info);
