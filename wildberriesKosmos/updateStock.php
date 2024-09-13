<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Products.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
$log = new \Classes\Common\Log ('wildberriesKosmos - updateStock.log');

$productsWBclass = new \Classes\Wildberries\v1\Products('Kosmos');
$productsWB = $productsWBclass->getCardsList();

$productCodes = array();
foreach ($productsWB as $product)
{
    if(isset($product['sizes'][0]['skus'][0]))
        $productCodes[$product['vendorCode']] = $product['sizes'][0]['skus'][0];
}

$log->write (__LINE__ . ' productCodes - ' . json_encode ($productCodes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

if (!count($productCodes)){
    echo 'No products';
    return;
}

$log->write (__LINE__ . ' array_keys - ' . json_encode (array_keys($productCodes), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

$productsMSClass = new \ProductsMS();
$productsMS = $productsMSClass->getAssortment(array_keys($productCodes));

$data = array();
foreach ($productsMS as $product)
{
    //$log->write (__LINE__ . ' product - ' . json_encode ($product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $data[] = array (
        'sku' => $productCodes[$product['code']],
        'amount' => $product['quantity'] - 3 < 0 ? 0 : $product['quantity'] - 3
        //'stock' => 0,
    );
    $log->write (__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}
if (count ($data))
{
    //$logger->write ('postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $postData = array(
        'stocks' => $data
    );
    $log->write (__LINE__ . ' postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $productsWBclass->setStock($postData);
}
echo 'Total: ' . count($productCodes) . ', updated: ' . count($productsMS) . ', not updated: ' . count($productCodes) - count($productsMS);
?>