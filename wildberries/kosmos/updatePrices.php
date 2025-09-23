<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Products.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/wildberries/product.php');
$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
$logName .= '.log';
$log = new \Classes\Common\Log($logName);

$productsWBclass = new \Classes\Wildberries\v1\Products('Kosmos');
$productsWB = $productsWBclass->getCardsList();

$productCodes = array();
foreach ($productsWB as $product)
{
    if(isset($product['sizes'][0]['skus'][0]))
        $productCodes[$product['vendorCode']] = $product['nmID'];
}

$log->write (__LINE__ . ' productCodes - ' . json_encode ($productCodes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

if (!count($productCodes)){
    echo 'No products';
    return;
}

$productsMSClass = new \ProductsMS();
$updated = 0;
foreach(array_chunk(array_keys($productCodes), 100) as $chunk)
{
    $productsMS = $productsMSClass->getAssortment($chunk);

    $data = array();
    foreach ($productsMS as $product)
    {
        $productTransform = new \Wildberries\Product\ProductTransformation($product, $productCodes[$product['code']]);
        $data[] = $productTransform->transformMSToWildberriesPrice('Цена WB');
    }
    if (count ($data))
    {
        $updated += count($data);
        $postData = array(
            'data' => $data
        );
        $log->write (__LINE__ . ' postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $productsWBclass->setPrices($postData);
    }
}
echo 'Total: ' . count($productCodes) . ', updated: ' . $updated . ', not updated: ' . (count($productCodes) - $updated);
?>
