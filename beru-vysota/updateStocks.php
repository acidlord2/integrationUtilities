<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/skuYandex.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

$skuYandexClass = new SkuYandex(BERU_API_VYSOTA_CAMPAIGN, BERU_API_VYSOTA_BUSINESS_ID);
$productsClass = new ProductsMS();

$skus = $skuYandexClass->offerMappings();

$shopSku = array();
foreach ($skus as $key => $sku)
{
    array_push($shopSku, $sku['offer']['offerId']);
    if (count($shopSku) == 50 || $key + 1 == count($skus))
    {
        $assortments = $productsClass->getAssortment($shopSku);
        $data = array();
        $data['skus'] = array();
        foreach ($assortments as $assortment)
        {
            $data['skus'][] = array(
                'sku' => $assortment['code'],
                'warehouseId' => BERU_API_VYSOTA_WAREHOUSE,
                'items' => array(
                    0 => array(
                        'type' => 'FIT',
                        'count' => $assortment['quantity'] < 0 ? 0 : $assortment['quantity'],
                        'updatedAt' => date('Y-m-d\TH:i:sP')
                    )
                )
            );
        }
        $skuYandexClass->putStocks($data);
        $shopSku = array();
    }
}
echo count($skus) . ' Stocks updated';

?>

