<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Products.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
$log = new \Classes\Common\Log ('wildberriesKaori - updatePrices.log');

$productsWBclass = new \Classes\Wildberries\v1\Products('Kaori');
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
$dataDiscounts = array();
$processed = 0;
$notProcessed = 0;
foreach ($productsMS as $product)
{
    $productWBKey = array_search($product['code'], $productCodes);
    $priceTypes = array_column($product['salePrices'], 'priceType');
	//$logger->write ('priceTypes - ' . json_encode ($priceTypes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $priceKey = array_search('Цена WB', array_column($priceTypes, 'name'));
	//$logger->write ('priceKey - ' . json_encode ($priceKey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
    if ((int)($product['salePrices'][$priceKey]['value'])){
        if (isset($productsWB[$productWBKey]['nomenclatures'][0]['nmId'])){
            $data[] = array (
                'nmId' => $productsWB[$productWBKey]['nomenclatures'][0]['nmId'],
                'price' => $product['salePrices'][$priceKey]['value'] / 100
            );
            $dataDiscounts[] = array(
                'discount' => 40,
                'nmId' => $productsWB[$productWBKey]['nomenclatures'][0]['nmId']
            );
            $processed++;
        }
        else
        {
            $log->write(__LINE__ . ' nmId not set - ' . $productsWB[$productWBKey]['id']);
            $notProcessed++;
        }
    }
	else
	{
	    $log->write (__LINE__ . ' price not set - ' . $productsWB[$productWBKey]['id']);
		$notProcessed++;
	}
}
if (count ($data))
{
	//$logger->write ('postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $productsWBclass->setPrices($data);
    $productsWBclass->setDiscounts($dataDiscounts);
}
echo 'Total: ' . count($productsMS) . ', updated: ' . $processed . ', not updated: ' . $notProcessed;
?>
