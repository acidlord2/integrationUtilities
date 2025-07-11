<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/Product-v1.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');

$clientId = SPORTMASTER_ULLO_CLIENT_ID;
$warehouseId = SPORTMASTER_ULLO_WAREHOUSE_ID;
$productClass = new \Classes\Sportmaster\v1\Product($clientId);
$stocks = $productClass->stockList($warehouseId);
// If you want to use MS Products class, uncomment the following lines
$msProductClass = new ProductsMS();
$assortment = $msProductClass->getAssortment(array_column($stocks, 'offerId'));
$postStock = array();
foreach ($stocks as $stock) {
    $matched = array_values(array_filter($assortment, function($item) use ($stock) {
        return isset($item['code']) && $item['code'] === $stock['offerId'];
    }));
    if (!empty($matched)) {
        $product = $matched[0];
        $postStock[] = array(
            'offerId' => $product['code'],
            'warehouseStock' => $product['quantity'],
        );
    } else {
        $postStock[] = array(
            'offerId' => isset($stock['offerId']) ? $stock['offerId'] : null,
            'warehouseStock' => 0,
        );
    }
}
if ($productClass->stockImport($warehouseId, $postStock)) {
    echo count($postStock) . ' stocks updated successfully';
} else {
    echo 'Failed to update stocks';
}