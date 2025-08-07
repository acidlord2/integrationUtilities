<?php
// Imports prices data from moySklad to the ccd77 database
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');

$msPrice = 'Цена продажи';
$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
$logName .= '.log';
$log = new \Classes\Common\Log($logName);
$db = new \Classes\Common\Db(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE_CCD77);
$msProductClass = new ProductsMS();
// get the list of products from ccd77
$ccd77Products = $db->execQueryArray("select * FROM wp_wc_product_meta_lookup");
$log->write(__LINE__ . ' ccd77Products.count - ' . count($ccd77Products));
// let's chunk the products into 500 items per chunk
$chunkSize = 500;
$chunks = array_chunk($ccd77Products, $chunkSize);
$updatedCount = 0;
foreach ($chunks as $chunk) {
    $skus = array_column($chunk, 'sku');
    $log->write(__LINE__ . ' skus - ' . json_encode($skus, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    // get the product data from moySklad
    $msAssortment = $msProductClass->getAssortment($skus);
    // create an update query for each product
    $updateQueries = [];
    foreach ($msAssortment as $msProduct) {
        // fetch price data "Цена продажи" from each product
        $price = $msProductClass->getPrice($msProduct, $msPrice);
        if ($price > 0) {
            $log->write(__LINE__ . ' Updating price for ' . $msProduct['code'] . ' - ' . $price);
            $updateQueries[] = "UPDATE wp_wc_product_meta_lookup SET min_price = " . intval($price) .
                ", max_price = " . intval($price) .
            " WHERE sku = '" . $msProduct['code'] . "'";
            // fetch product_id from chunk
            $productIndex = array_search($msProduct['code'], array_column($chunk, 'sku'));
            $updateQueries[] = "UPDATE wp_postmeta SET meta_value = " . intval($price) .
                " WHERE meta_key = '_price' AND post_id = " . intval($chunk[$productIndex]['product_id']);
            $updateQueries[] = "UPDATE wp_postmeta SET meta_value = " . intval($price) .
                " WHERE meta_key = '_regular_price' AND post_id = " . intval($chunk[$productIndex]['product_id']);
        }
    }
    // execute all update queries
    foreach ($updateQueries as $updateQuery) {
        try {
            $result = $db->execQuery($updateQuery);
            $updatedCount++;
        } catch (Exception $e) {
            $log->write(__LINE__ . ' Error executing query: ' . $updateQuery . ' - ' . $e->getMessage());
            continue;
        }
        $db->execQuery($updateQuery);
    }
}
echo "Price update completed successfully. Updated " . $updatedCount / 3 . " products.";
