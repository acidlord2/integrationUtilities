<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/Product-v1.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');

$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
$logName .= '.log';
$log = new \Classes\Common\Log($logName);

$clientId = SPORTMASTER_ULLO_CLIENT_ID;
$warehouseId = SPORTMASTER_ULLO_WAREHOUSE_ID;
$productClass = new \Classes\Sportmaster\v1\Product($clientId);
$stocks = $productClass->stockList($warehouseId);
// If you want to use MS Products class, uncomment the following lines
$msProductClass = new ProductsMS();
$assortment = $msProductClass->getAssortment(array_column($stocks, 'offerId'));
$postPrices = array();
foreach ($stocks as $stock) {
    $matched = array_values(array_filter($assortment, function($item) use ($stock) {
        return isset($item['code']) && $item['code'] === $stock['offerId'];
    }));
    if (!empty($matched)) {
        $product = $matched[0];
        $price = $msProductClass->getPrice($product, MS_PRICE_SPORTMASTER);
        if ($price != 0) {
            $postPrices[] = array(
                'offerId' => $product['code'],
                'price' => (int)$price
            );
        }
    } else {
        $log->write(__LINE__ . ' No matching product found for offerId: ' . $stock['offerId']);
    }
}
if ($productClass->pricesUpdate($postPrices)) {
    echo count($postPrices) . ' prices of ' . count($stocks) . ' updated successfully';
} else {
    echo 'Failed to update prices';
}