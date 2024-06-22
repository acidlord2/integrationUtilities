<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/skuYandex.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

$skuYandexClass = new SkuYandex(BERU_API_VYSOTA_CAMPAIGN);
$productsClass = new ProductsMS();

$skus = $skuYandexClass->offerMappingEntries();

$shopSku = array();
foreach ($skus as $key => $sku)
{
    array_push($shopSku, $sku['offer']['shopSku']);
    if (count($shopSku) == 50 || $key + 1 == count($skus))
    {
        $assortments = $productsClass->getAssortment($shopSku);
        $data = array();
        $data['offers'] = array();
        foreach ($assortments as $assortment)
        {
            $price = $productsClass->getPrice($assortment, 'Цена СММ Альянс');
            $vat = $assortment['vat'] == 20 ? 7 : ($assortment['vat'] == 10 ? 2 : ($assortment['vat'] == 0 ? 5 : 6));
            $data['offers'][] = array(
                'offerId' => $assortment['code'],
                'price' => array(
                    'value' => $price,
                    'currencyId' => 'RUR',
                    'vat' => $vat,
                )
            );
        }
        $skuYandexClass->putPrices($data);
        $shopSku = array();
    }
}

echo count($skus) . ' Prices updated';


?>

