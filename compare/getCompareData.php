<?php
header('Content-Type: application/json; charset=utf-8');
$type = $_GET['type'] ?? '';
$marketplace = $_GET['marketplace'] ?? '';
$organization = $_GET['organization'] ?? '';

function getAssortmentData($skuList, $type, $getter){
    // MS assortment API by SKU list
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/AssortmentApi.php');
    $msApi = new \Classes\MS\v2\AssortmentApi();
    $msAssortment = $msApi->fetchAssortment($skuList);
    $msData = [];
    if (is_iterable($msAssortment)) {
        foreach ($msAssortment as $item) {
            $msData[] = [
                'code' => $item->getCode(),
                'price' => $type === 'prices' ? $item->$getter() : null,
                'quantity' => $type === 'prices' ? null : ($item->$getter() > 0 ? $item->getQuantity() : 0)
            ];
        }
    }

    return $msData;
}

function mergeArraysByCode($array1, $array2, $type) {
    $merged = [];
    $msMap = [];
    $mpMap = [];
    // Build maps for quick lookup
    foreach ($array1 as $item1) {
        $msMap[$item1['code']] = $item1;
    }
    foreach ($array2 as $item2) {
        $mpMap[$item2['code']] = $item2;
    }
    // Get all unique codes
    $allCodes = array_unique(array_merge(array_keys($msMap), array_keys($mpMap)));
    foreach ($allCodes as $code) {
        $letMs = isset($msMap[$code]);
        $letMp = isset($mpMap[$code]);
        $item1 = $msMap[$code] ?? ['price' => null, 'quantity' => null];
        $item2 = $mpMap[$code] ?? ['price' => null, 'quantity' => null];
        $msValue = $type === 'prices'
            ? ($letMs ? (int)$item1['price'] : null)
            : ($letMs ? (int)$item1['quantity'] : null);
        $mpValue = $type === 'prices'
            ? ($letMp ? (int)$item2['price'] : null)
            : ($letMp ? (int)$item2['quantity'] : null);
        $merged[] = [
            'code' => $code,
            'ms' => $msValue,
            'mp' => $mpValue
        ];
    }
    return $merged;
}

$data = [];
if ($marketplace === 'ccd') {
    // CCD product API
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ccd77/v2/ProductApi.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ccd77/v2/ProductIterator.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
    
    $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
    $logName .= '.log';
    $log = new \Classes\Common\Log($logName);

    $ccdApi = new \Classes\Ccd77\v2\ProductApi();
    $ccdProducts = $ccdApi->getProductIterator();
    $ccdData = [];
    $skuList = [];
    foreach ($ccdProducts as $product) {
        $sku = $product->getSku();
        $ccdData[] = [
            'code' => $sku,
            'price' => $type === 'prices' ? $product->getMinPrice() : null,
            'quantity' => $type === 'prices' ? null : $product->getStockQuantity()
        ];
        if ($sku) {
            $skuList[] = $sku;
        }
    }

    // MS assortment API by SKU list
    $msData = getAssortmentData($skuList, $type, 'getPriceSale');
    
    // Merge MS and CCD data by code
    $data = mergeArraysByCode($msData, $ccdData, $type);
} elseif ($marketplace === 'ozon') {
    // Ozon product API
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/v2/ProductApi.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/v2/ProductIterator.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

    $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
    $logName .= '.log';
    $log = new \Classes\Common\Log($logName);

    $ozonApi = new \Classes\Ozon\v2\ProductApi($organization);
    $ozonProducts = $ozonApi->getProductIterator();
    $log->write(__LINE__ . ' ' . __METHOD__ . ' $ozonProducts - ' . json_encode($ozonProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $ozonData = [];
    $skuList = [];
    foreach ($ozonProducts as $product) {
        $offerId = $product->getOfferId();
        $ozonData[] = [
            'code' => $offerId,
            'price' => $type === 'prices' ? $product->getPrice() : null,
            'quantity' => $type === 'prices' ? null : $product->getPresent()
        ];
        if ($offerId) {
            $skuList[] = $offerId;
        }
    }

    // MS assortment API by SKU list
    $msData = getAssortmentData($skuList, $type, $organization === 'ullo' ? 'getPriceOzon' : 'getPriceOzonKaori');

    // Merge MS and Ozon data by code
    $data = mergeArraysByCode($msData, $ozonData, $type);

} elseif ($marketplace === 'wb') {
    // Wildberries product API
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/v2/ProductApi.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/v2/ProductIterator.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

    $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
    $logName .= '.log';
    $log = new \Classes\Common\Log($logName);

    $wbApi = new \Classes\Wildberries\v2\ProductApi();
    $wbProducts = $wbApi->getProductIterator();
    $log->write(__LINE__ . ' ' . __METHOD__ . ' $wbProducts - ' . json_encode($wbProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $wbData = [];
    $skuList = [];
    foreach ($wbProducts as $product) {
        $sku = $product->getVendorCode();
        $wbData[] = [
            'code' => $sku,
            'price' => $type === 'prices' ? $product->getPrice() : null,
            'quantity' => $type === 'prices' ? null : $product->getAmount()
        ];
        if ($sku) {
            $skuList[] = $sku;
        }
    }

    // MS assortment API by SKU list
    $msData = getAssortmentData($skuList, $type, $organization === 'ullo' ? 'getPriceWbUllo' : 'getPriceWb');

    // Merge MS and Wildberries data by code
    $data = mergeArraysByCode($msData, $wbData, $type);

} else {
    // ...existing code...
    $data = [
        [ 'code' => '12345', 'ms' => [ 'price' => $type === 'prices' ? 1000 : null, 'quantity' => $type === 'prices' ? null : 50 ], 'mp' => [ 'price' => $type === 'prices' ? 950 : null, 'quantity' => $type === 'prices' ? null : 45 ], 'attributes' => [] ],
        [ 'code' => '67890', 'ms' => [ 'price' => $type === 'prices' ? 2000 : null, 'quantity' => $type === 'prices' ? null : 30 ], 'mp' => [ 'price' => $type === 'prices' ? 2100 : null, 'quantity' => $type === 'prices' ? null : 28 ], 'attributes' => [] ]
    ];
}
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
