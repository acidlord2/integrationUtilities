<?php
/**
 * @class ProductApi
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 * Implements Yandex Market API for product management
 */
namespace Classes\Yandex\v2;

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/v2/ProductIterator.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/v2/Product.php');

class ProductApi
{
    private $log;
    private $api;
    private $campaignId;
    private $businessId;

    public function __construct($organization)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/v2/Api.php');

        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);

        $campaignMap = 'BERU_API_' . strtoupper($organization) . '_CAMPAIGN';
        $this->campaignId = constant($campaignMap);
        $businessMap = 'BERU_API_' . strtoupper($organization) . '_BUSINESS_ID';
        $this->businessId = constant($businessMap);

        $this->api = new \Classes\Yandex\v2\Api($this->campaignId);
    }

    /**
     * Merges product prices and stocks data
     * @param array $prices Array of price data
     * @param array $stocks Array of stock data  
     * @return array Merged product data
     */
    private function mergeProductData($prices, $stocks)
    {
        if (is_string($prices)) {
            $prices = json_decode($prices, true);
        }
        if (is_string($stocks)) {
            $stocks = json_decode($stocks, true);
        }

        // Build a lookup for stocks by offerId
        $stockMap = [];
        foreach ($stocks as $stock) {
            if (isset($stock['offerId'])) {
                $stockMap[$stock['offerId']] = $stock;
            }
        }

        // Merge prices and stocks by offerId into a single array
        $mergedList = [];
        foreach ($prices as $price) {
            $offerId = $price['offerId'] ?? null;
            $merged = $price;

            if ($offerId && isset($stockMap[$offerId])) {
                // Merge stock data into price data
                $stockData = $stockMap[$offerId] ?? [];
                foreach ($stockData as $key => $value) {
                    if (!isset($merged[$key])) {
                        $merged[$key] = $value;
                    }
                }
            }
            $mergedList[] = $merged;
        }
        return $mergedList;
    }

    /**
     * Fetches product prices from Yandex Market API
     * @return array|false
     */
    private function fetchPrices()
    {
        $baseUrl = BERU_API_BASE_URL . BERU_API_CAMPAIGNS . '/' . $this->campaignId . '/' . BERU_API_OFFERS;

        $allPrices = [];
        $nextPageToken = null;
        
        do {
            $url = $baseUrl;
            if ($nextPageToken) {
                $url .= '?page_token=' . $nextPageToken;
            }
            
            $response = $this->api->getData($url);
            if (is_string($response)) {
                $response = json_decode($response, true);
            }
            
            if ($response && isset($response['result']['offers']) && is_array($response['result']['offers'])) {
                $allPrices = array_merge($allPrices, $response['result']['offers']);
            }
            
            $nextPageToken = $response['result']['paging']['nextPageToken'] ?? null;
            
        } while ($nextPageToken);
        
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Fetched ' . count($allPrices) . ' prices');
        return $allPrices;
    }

    /**
     * Fetches product stocks from Yandex Market API
     * @return array|false
     */
    private function fetchStocks()
    {
        $baseUrl = BERU_API_BASE_URL . BERU_API_CAMPAIGNS . '/' . $this->campaignId . '/' . BERU_API_STOCKS;
        $allStocks = [];
        $nextPageToken = null;
        
        do {
            $url = $baseUrl;
            if ($nextPageToken) {
                $url .= '?page_token=' . urlencode($nextPageToken);
            }
            
            $response = $this->api->getData($url);
            if (is_string($response)) {
                $response = json_decode($response, true);
            }

            if ($response && isset($response['result']['warehouses'][0]['offers']) && is_array($response['result']['warehouses'][0]['offers'])) {
                $allStocks = array_merge($allStocks, $response['result']['warehouses'][0]['offers']);
            }
            
            $nextPageToken = $response['result']['paging']['nextPageToken'] ?? null;
            
        } while ($nextPageToken);
        
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Fetched ' . count($allStocks) . ' stocks');
        foreach ($allStocks as &$stock) {
            foreach ($stock['stocks'] as $warehouse) {
                if($warehouse['type'] == 'AVAILABLE') {
                    $stock['quantity'] = $warehouse['count'] ?? null;
                    break;
                }
            }
        }
        unset($stock);
        return $allStocks;
    }

    /**
     * Fetches products from Yandex Market API by merging prices and stocks
     * @return array|false
     * @throws \Exception
     */
    public function fetchProducts()
    {
        try {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Starting product fetch');
            
            $prices = $this->fetchPrices();
            $stocks = $this->fetchStocks();
            
            if (empty($prices) || empty($stocks)) {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Error - No prices or stocks found');
                return false;
            }
            
            $mergedData = $this->mergeProductData($prices, $stocks);
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Merged ' . count($mergedData) . ' products');
            
            if (empty($mergedData)) {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Error - No merged products found');
                return false;
            }
            
            return $mergedData;
            
        } catch (\Exception $e) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Returns an iterator for products fetched from Yandex Market API
     * @return \Classes\Yandex\v2\ProductIterator|false
     * @throws \Exception
     */
    public function getProductIterator()
    {
        $products = $this->fetchProducts();
        if ($products === false) {
            return false;
        }
        return new ProductIterator($products);
    }
}
