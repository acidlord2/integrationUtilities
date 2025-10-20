<?php
$docroot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2);
require_once($docroot . '/docker-config.php');
require_once($docroot . '/classes/Sportmaster/Product-v1.php');
require_once($docroot . '/classes/Common/Log.php');
require_once($docroot . '/classes/MS/productsMS.php');

// Generate unique transaction ID for this script execution
$scriptTransactionId = 'stock_update_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
putenv('SCRIPT_TRANSACTION_ID=' . $scriptTransactionId);

$hiddenProducts = [];

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
        if (in_array($product['code'], $hiddenProducts)) {
            $price = 0;
        } else {
            $price = $msProductClass->getPrice($product, MS_PRICE_SPORTMASTER);
        }
        $postStock[] = array(
            'offerId' => $product['code'],
            'warehouseStock' => $price == 0 ? 0 : $product['quantity'],
        );
    } else {
        $postStock[] = array(
            'offerId' => isset($stock['offerId']) ? $stock['offerId'] : null,
            'warehouseStock' => 0,
        );
    }
}
if ($productClass->stockUpdate($warehouseId, $postStock)) {
    echo count($postStock) . ' stocks updated successfully';
} else {
    echo 'Failed to update stocks';
}