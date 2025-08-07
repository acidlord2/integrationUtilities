<?php
// Imports stock data from moySklad to the ccd77 database
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
// let's chunk the products into 200 items per chunk
$chunkSize = 200;
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
        $price = $msProductClass->getPrice($msProduct, $msPrice);
        $quantity = $price > 0 ? $msProduct['quantity'] : 0;
        $log->write(__LINE__ . ' Updating quantity for ' . $msProduct['code'] . ' - ' . $msProduct['quantity']);
        $updateQueries[] = "UPDATE wp_wc_product_meta_lookup SET stock_quantity = " . intval($msProduct['quantity']) .
            ", stock_status = '" . ($msProduct['quantity'] > 0 ? 'instock' : 'outofstock') . "'" .
            " WHERE sku = '" . $msProduct['code'] . "'";
        // fetch product_id from chunk
        $productIndex = array_search($msProduct['code'], array_column($chunk, 'sku'));
        $updateQueries[] = "UPDATE wp_postmeta SET meta_value = " . intval($msProduct['quantity']) .
            " WHERE meta_key = '_stock' AND post_id = " . intval($chunk[$productIndex]['product_id']);
        $updateQueries[] = "UPDATE wp_postmeta SET meta_value = '" . ($msProduct['quantity'] > 0 ? 'instock' : 'outofstock') .
            "' WHERE meta_key = '_stock_status' AND post_id = " . intval($chunk[$productIndex]['product_id']);
    }
    // execute all update queries
    foreach ($updateQueries as $updateQuery) {
        try {
            $db->execQuery($updateQuery);
            $updatedCount++;
        } catch (Exception $e) {
            $log->write(__LINE__ . ' Error executing query: ' . $updateQuery . ' - ' . $e->getMessage());
            continue;
        }
        $db->execQuery($updateQuery);
    }
}
echo "Stock update completed successfully. Updated " . $updatedCount / 3 . " products.";
