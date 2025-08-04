<?php
header('Content-Type: application/json; charset=utf-8');
$type = $_GET['type'] ?? '';
$marketplace = $_GET['marketplace'] ?? '';
$organization = $_GET['organization'] ?? '';

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
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/AssortmentApi.php');
    $msApi = new \Classes\MS\v2\AssortmentApi();
    $msAssortment = $msApi->getAssortmentIterator(['organization' => $organization, 'code' => $skuList]);
    $msData = [];
    foreach ($msAssortment as $item) {
        $msData[] = [
            'code' => $item->getCode(),
            'price' => $type === 'prices' ? (method_exists($item, 'getPriceSale') ? $item->getPriceSale() : null) : null,
            'quantity' => $type === 'prices' ? null : $item->getQuantity()
        ];
    }

    // Merge MS and CCD data by code
    $result = [];
    // Index MS data by code for fast lookup
    $msByCode = [];
    foreach ($msData as $ms) {
        $msByCode[$ms['code']] = $ms;
    }
    foreach ($ccdData as $ccd) {
        $code = $ccd['code'];
        $ms = $msByCode[$code] ?? ['price' => null, 'quantity' => null];
        $result[] = [
            'code' => $code,
            'ms' => [
                'price' => $ms['price'],
                'quantity' => $ms['quantity']
            ],
            'mp' => [
                'price' => $ccd['price'],
                'quantity' => $ccd['quantity']
            ],
            'attributes' => [] // Add attributes here if needed
        ];
    }
    $data = $result;
} else {
    // ...existing code...
    $data = [
        [ 'code' => '12345', 'ms' => [ 'price' => $type === 'prices' ? 1000 : null, 'quantity' => $type === 'prices' ? null : 50 ], 'mp' => [ 'price' => $type === 'prices' ? 950 : null, 'quantity' => $type === 'prices' ? null : 45 ], 'attributes' => [] ],
        [ 'code' => '67890', 'ms' => [ 'price' => $type === 'prices' ? 2000 : null, 'quantity' => $type === 'prices' ? null : 30 ], 'mp' => [ 'price' => $type === 'prices' ? 2100 : null, 'quantity' => $type === 'prices' ? null : 28 ], 'attributes' => [] ]
    ];
}
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
