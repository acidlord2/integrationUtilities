<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Products.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
$log = new \Classes\Common\Log ('wildberriesKosmos - updatePrices.log');

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
        $priceTypes = array_column($product['salePrices'], 'priceType');
        $priceKey = array_search('Цена WB', array_column($priceTypes, 'name'));
        
        if ((int)($product['salePrices'][$priceKey]['value'])){
            $data[] = array (
                'nmId' => $productCodes[$product['code']],
                'price' => round($product['salePrices'][$priceKey]['value'] / 100),
                'discount' => 0
            );
        }
        else
            $log->write(__LINE__ . ' price not set - ' . $product['code']);
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
