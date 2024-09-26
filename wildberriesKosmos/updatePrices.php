<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Products.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
$log = new \Classes\Common\Log ('wildberriesKaori - updatePrices.log');

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
$productsMS = $productsMSClass->getAssortment(array_keys($productCodes));

$data = array();
foreach ($productsMS as $product)
{
    $priceTypes = array_column($product['salePrices'], 'priceType');
    $priceKey = array_search('Цена WB', array_column($priceTypes, 'name'));
	
    if ((int)($product['salePrices'][$priceKey]['value'])){
        $data[] = array (
            'nmId' => $productCodes[$product['code']],
            'price' => $product['salePrices'][$priceKey]['value'] / 100
        );
    }
	else
	    $log->write(__LINE__ . ' price not set - ' . $product['code']);
}
if (count ($data))
{
    $postData = array(
        'data' => $data
    );
    $log->write (__LINE__ . ' postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $productsWBclass->setPrices($postData);
}
echo 'Total: ' . count($productCodes) . ', updated: ' . count($data) . ', not updated: ' . (count($productCodes) - count($data));
?>
