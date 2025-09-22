<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Products.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
$minQuantity = 1;
$log = new \Classes\Common\Log ('wildberriesUllo - updateStock.log');

$productsWBclass = new \Classes\Wildberries\v1\Products('Ullo');
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
$updated = 0;
foreach(array_chunk(array_keys($productCodes), 100) as $chunk)
{
    # if local current time between 2024-12-29 08:00:00 and 2025-01-01 13:00:00 then set quantity = 0
    $currentDate = date('Y-m-d H:i:s');
    $currentDate = strtotime($currentDate);
    $startDate = strtotime('2024-12-29 08:00:00');
    $endDate = strtotime('2025-01-01 13:00:00');

    // if ($currentDate >= $startDate && $currentDate <= $endDate)
    // {
    //     $log->write (__LINE__ . ' current date - ' . $currentDate);
    //     $log->write (__LINE__ . ' start date - ' . $startDate);
    //     $log->write (__LINE__ . ' end date - ' . $endDate);
    //     $log->write (__LINE__ . ' current date between start and end date');
    //     $log->write (__LINE__ . ' chunk - ' . json_encode ($chunk, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        
    //     $data = array();
    //     foreach ($chunk as $productCode)
    //     {
    //         $data[] = array (
    //             'sku' => $productCodes[$productCode],
    //             'amount' => 0
    //         );
    //     }
    // }
    // else
    // {
        $productsMS = $productsMSClass->getAssortment($chunk);

        $data = array();
        foreach ($productsMS as $product)
        {
            $priceTypes = array_column($product['salePrices'], 'priceType');
            $priceKey = array_search('Цена WB ULLO', array_column($priceTypes, 'name'));
            $price = 0;
            if ((int)($product['salePrices'][$priceKey]['value']))
                $price = ($product['salePrices'][$priceKey]['value'] / 100);
            //$log->write (__LINE__ . ' product - ' . json_encode ($product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $data[] = array (
                'sku' => $productCodes[$product['code']],
                'amount' => $product['quantity'] - $minQuantity < 0 ? 0 : ($price === 0 ? 0 : $product['quantity'] - $minQuantity)
                //'stock' => 0,
            );
            $log->write (__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
    // }
    if (count ($data))
    {
        $updated += count($data);
        //$logger->write ('postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $postData = array(
            'stocks' => $data
        );
        $log->write (__LINE__ . ' postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $productsWBclass->setStock($postData, (string)WB_WAREHOUSE_ULLO);
    }
}
echo 'Total: ' . count($productCodes) . ', updated: ' . $updated . ', not updated: ' . (count($productCodes) - $updated);
?>
