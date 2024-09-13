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
        $productCodes[] = array($product['vendorCode'] => $product['sizes'][0]['skus'][0]);
}

$log->write (__LINE__ . ' productCodes - ' . json_encode ($productCodes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

if (!count($productCodes)){
    echo 'No products';
    return;
}

$productsMSClass = new \ProductsMS();
$productsMS = $productsMSClass->getAssortment(array_keys($productCodes));
//$log->write (__LINE__ . ' productsMS - ' . json_encode ($productsMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

$data = array();
foreach ($productsMS as $product)
{
    //$logger->write ('priceTypes - ' . json_encode ($priceTypes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    //$logger->write ('priceKey - ' . json_encode ($priceKey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    
    $data[] = array (
        'sku' => $productCodes[$product['code']],
        'stock' => $product['quantity'] - 3 < 0 ? 0 : $product['quantity'] - 3
        //'stock' => 0,
    );
}
if (count ($data))
{
    //$logger->write ('postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $data = array(
        'stocks' => $data
    );
    $productsWBclass->setStock($data);
}
echo 'Total: ' . count($productCodes) . ', updated: ' . count($productsMS) . ', not updated: ' . count($productCodes) - count($productsMS);
?>
