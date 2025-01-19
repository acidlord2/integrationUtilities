<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Products.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
$log = new \Classes\Common\Log ('wildberriesUllo - updateStock.log');

$productsWBclass = new \Classes\Wildberries\v1\Products('Ullo');
$productsWB = $productsWBclass->cardList();

$productCodes = array_column($productsWB, 'supplierVendorCode');
$log->write (__LINE__ . ' productCodes - ' . json_encode ($productCodes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
$productCodesMS = array_diff($productCodes, ['']);

if (!count($productCodes)){
    echo 'No products';
    return;
}

$productsMSClass = new \ProductsMS();
$productsMS = $productsMSClass->getAssortment($productCodes);
//$log->write (__LINE__ . ' productsMS - ' . json_encode ($productsMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

$data = array();
$processed = 0;
$notProcessed = 0;
foreach ($productsMS as $product)
{
    $productWBKey = array_search($product['code'], $productCodes);
    //$logger->write ('priceTypes - ' . json_encode ($priceTypes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    //$logger->write ('priceKey - ' . json_encode ($priceKey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    
    if (isset($productsWB[$productWBKey]['nomenclatures'][0]['variations'][0]['barcodes'][0])){
        $data[] = array (
            'barcode' => $productsWB[$productWBKey]['nomenclatures'][0]['variations'][0]['barcodes'][0],
            //'stock' => $product['quantity'] < 0 ? 0 : $product['quantity'],
            'stock' => 0,
            'warehouseId' => WB_WAREHOUSE_ULLO
        );
        $processed++;
    }
    else
    {
        $log->write(__LINE__ . ' barcode not set - ' . $productsWB[$productWBKey]['id']);
        $notProcessed++;
    }
}
if (count ($data))
{
    //$logger->write ('postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $productsWBclass->setStock($data);
}
echo 'Total: ' . count($productsMS) . ', updated: ' . $processed . ', not updated: ' . $notProcessed;
?>
