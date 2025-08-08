<?php

namespace Classes\Wildberries\v2;

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/v2/ProductIterator.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/v2/Product.php');

/**
 * Class ProductApi
 * API wrapper for fetching and constructing Wildberries products.
 */
class ProductApi
{
    private $log;
    private $api;
    private $limit = 500; // Default limit for API requests
    private $chunkSize = 500;
    private $organization;

    public function __construct($organization)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/v2/Api.php');

        $this->organization = $organization;
        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);

        $this->api = new \Classes\Wildberries\v2\Api($organization);
    }

    /**
     * Merges two arrays of product data by nmID (Wildberries product ID)
     * @param array $priceData Main product data (e.g., prices)
     * @param array $stockData Extra product data (e.g., stocks)
     * @return array Merged product data
     */
    private function mergeProductData($priceData, $stockData)
    {
        if (is_string($priceData)) {
            $priceData = json_decode($priceData, true);
        }
        if (is_string($stockData)) {
            $stockData = json_decode($stockData, true);
        }

        $priceMap = [];
        foreach ($priceData as $item) {
            if (isset($item['vendorCode'])) {
                $priceMap[$item['vendorCode']] = $item;
            }
        }
        $mergedList = [];
        foreach ($stockData as $item) {
            $sku = $item['sku'] ?? null;
            $merged = $item;
            if ($sku && isset($priceMap[$sku])) {
                foreach ($priceMap[$sku] as $key => $value) {
                    $merged[$key] = $value;
                }
            }
            $mergedList[] = $merged;
        }
        return $mergedList;
    }

    /**
     * Simulates fetching products from two sources and merging them
     * @return array Merged product data
     */
    public function fetchProducts(): array
    {

        $offset = 0;
        $priceData = [];
        while (true) {
            $url = WB_API_PRICES_API . WB_API_LIST_GOODS . '?limit=' . $this->limit . '&offset=' . $offset;
            $response = $this->api->getData($url);

            $priceDataArray = json_decode($response, true);

            if (empty($priceDataArray['data']['listGoods'])) {
                break;
            }
            $priceData = array_merge($priceData, $priceDataArray['data']['listGoods']);
            $offset += $this->limit;
        }
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' fetched products count - ' . count($priceData));

        $stockData = [];
        $offset = 0;
        $chunks = array_chunk(array_column($priceData, 'vendorCode'), $this->chunkSize);
        $warehouseConst = 'WB_WAREHOUSE_' . strtoupper($this->organization);
        if (!defined($warehouseConst)) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Warning: Warehouse constant ' . $warehouseConst . ' is not defined');
            return false;
        }
        $warehouse = constant($warehouseConst);
        foreach ($chunks as $chunk) {
            $url = WB_API_MARKETPLACE_API . WB_API_STOCKS . '/' . $warehouse;
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' request: ' . json_encode(array('skus' => $chunk), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $response = $this->api->postData($url, array('skus' => $chunk));

            $stockDataArray = json_decode($response, true);

            $stockData = array_merge($stockData, $stockDataArray['stocks']);
        }
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' fetched stocks count - ' . count($stockData));

        $mergedData = $this->mergeProductData($priceData, $stockData);
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' merged products count - ' . count($mergedData));
        return $mergedData;
    }

    /**
     * Returns a ProductIterator for merged product data
     * @return ProductIterator
     */
    public function getProductIterator(): ProductIterator
    {
        $products = $this->fetchProducts();
        return new ProductIterator($products);
    }
}
