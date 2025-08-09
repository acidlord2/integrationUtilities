<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/Product-v1.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');

$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
$logName .= '.log';
$log = new \Classes\Common\Log($logName);

$clientId = SPORTMASTER_ULLO_CLIENT_ID;
$productClass = new \Classes\Sportmaster\v1\Product($clientId);
$prices = $productClass->pricesList();
// If you want to use MS Products class, uncomment the following lines
$msProductClass = new ProductsMS();
$assortment = $msProductClass->getAssortment(array_column($prices, 'offerId'));
$postPrices = array();
foreach ($prices as $price) {
    $matched = array_values(array_filter($assortment, function($item) use ($price) {
        return isset($item['code']) && $item['code'] === $price['offerId'];
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
        $log->write(__LINE__ . ' No matching product found for offerId: ' . $price['offerId']);
    }
}
if ($productClass->pricesUpdate($postPrices)) {
    echo count($postPrices) . ' prices of ' . count($prices) . ' updated successfully';
} else {
    echo 'Failed to update prices';
}