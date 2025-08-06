<?php
header('Content-Type: application/json; charset=utf-8');
$type = $_GET['type'] ?? '';
$marketplace = $_GET['marketplace'] ?? '';
$organization = $_GET['organization'] ?? '';

function getAssortmentData($skuList, $type){
    // MS assortment API by SKU list
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/AssortmentApi.php');
    $msApi = new \Classes\MS\v2\AssortmentApi();
    $msAssortment = $msApi->fetchAssortment($skuList);
    $msData = [];
    if (is_iterable($msAssortment)) {
        foreach ($msAssortment as $item) {
            $msData[] = [
                'code' => $item->getCode(),
                'price' => $type === 'prices' ? $item->getPriceSale() : null,
                'quantity' => $type === 'prices' ? null : $item->getQuantity()
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
        $item1 = $msMap[$code] ?? ['price' => null, 'quantity' => null];
        $item2 = $mpMap[$code] ?? ['price' => null, 'quantity' => null];
        $msValue = $type === 'prices'
            ? (array_key_exists('price', $item1) ? $item1['price'] : null)
            : (array_key_exists('quantity', $item1) ? $item1['quantity'] : null);
        $mpValue = $type === 'prices'
            ? (array_key_exists('price', $item2) ? $item2['price'] : null)
            : (array_key_exists('quantity', $item2) ? $item2['quantity'] : null);
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
    $msData = getAssortmentData($skuList, $type);

    // Merge MS and CCD data by code
    $data = mergeArraysByCode($msData, $ccdData, $type);
} elseif ($marketplace === 'ozon') {
    // Ozon product API
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/v2/ProductApi.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/v2/ProductIterator.php');
    $ozonApi = new \Classes\Ozon\v2\ProductApi($organization);
    $ozonProducts = $ozonApi->getProductIterator();
    $ozonData = [];
    $skuList = [];
    foreach ($ozonProducts as $product) {
        $sku = $product->getSku();
        $ozonData[] = [
            'code' => $sku,
            'price' => $type === 'prices' ? $product->getPrice() : null,
            'quantity' => $type === 'prices' ? null : $product->getPresent()
        ];
        if ($sku) {
            $skuList[] = $sku;
        }
    }

    // MS assortment API by SKU list
    $msData = getAssortmentData($skuList, $type);

    // Merge MS and Ozon data by code
    $data = mergeArraysByCode($msData, $ozonData, $type);


} else {
    // ...existing code...
    $data = [
        [ 'code' => '12345', 'ms' => [ 'price' => $type === 'prices' ? 1000 : null, 'quantity' => $type === 'prices' ? null : 50 ], 'mp' => [ 'price' => $type === 'prices' ? 950 : null, 'quantity' => $type === 'prices' ? null : 45 ], 'attributes' => [] ],
        [ 'code' => '67890', 'ms' => [ 'price' => $type === 'prices' ? 2000 : null, 'quantity' => $type === 'prices' ? null : 30 ], 'mp' => [ 'price' => $type === 'prices' ? 2100 : null, 'quantity' => $type === 'prices' ? null : 28 ], 'attributes' => [] ]
    ];
}
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
