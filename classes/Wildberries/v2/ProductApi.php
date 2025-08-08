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
    private $productLimit = 100;
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
     * @param array $products Main product data (e.g., prices)
     * @param array $priceData Extra product data (e.g., stocks)
     * @param array $stockData
     * @return array Merged product data
     */
    private function mergeProductData($products, $priceData, $stockData)
    {
        if (is_string($products)) {
            $products = json_decode($products, true);
        }
        if (is_string($priceData)) {
            $priceData = json_decode($priceData, true);
        }
        if (is_string($stockData)) {
            $stockData = json_decode($stockData, true);
        }
        $productMap = [];
        foreach ($products as $item) {
            if (isset($item['vendorCode'])) {
                $productMap[$item['vendorCode']] = $item;
            }
        }

        $priceMap = [];
        foreach ($priceData as $item) {
            $vendorCode = $item['vendorCode'] ?? null;
            $merged = $item;
            if($vendorCode && isset($priceMap[$vendorCode])) {
                $merged['sku'] = $productMap[$vendorCode]['sku'] ?? null;
            }
            $priceMap[$merged['sku']] = $merged;
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
        $products = array();
	    $postData = array(
			'settings' => array(
				'cursor' => array(
					'limit' => 100
				),
				'filter' => array(
					'withPhoto' => -1
	            )
	        )
	    );
        $url = WB_API_CONTENT_API . WB_API_CARDS_LIST;
        while (true) {
            $response = $this->api->postData($url, $postData);
			if (!isset($response['cards']) || !count($response['cards']))
				break;
			$products = array_merge($products, $response['cards']);
			if ($response['cursor']['total'] < 100)
				break;
			$postData['settings']['cursor']['nmID'] = $response['cursor']['nmID'];
			$postData['settings']['cursor']['updatedAt'] = $response['cursor']['updatedAt'];
        }
        foreach ($products as &$product) {
            $product['sku'] = $product['sizes'][0]['skus'][0] ?? null;
        }
        unset($product); // break the reference
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' fetched products count - ' . count($products));

        $offset = 0;
        $priceData = [];
        while (true) {
            $url = WB_API_PRICES_API . WB_API_LIST_GOODS . '?limit=' . $this->limit . '&offset=' . $offset;
            $response = $this->api->getData($url);
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' response: ' . $response);
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
        $chunks = array_chunk(array_column($products, 'sku'), $this->chunkSize);
        $warehouseConst = 'WB_WAREHOUSE_' . strtoupper($this->organization);
        if (!defined($warehouseConst)) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Warning: Warehouse constant ' . $warehouseConst . ' is not defined');
            return false;
        }
        $warehouse = constant($warehouseConst);
        foreach ($chunks as $chunk) {
            $url = WB_API_MARKETPLACE_API . WB_API_STOCKS . '/' . $warehouse;
            $response = $this->api->postData($url, array('skus' => $chunk));
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' response: ' . $response);

            $stockDataArray = json_decode($response, true);

            $stockData = array_merge($stockData, $stockDataArray['stocks']);
        }
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' fetched stocks count - ' . count($stockData));

        $mergedData = $this->mergeProductData($products, $priceData, $stockData);
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
