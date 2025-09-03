<?php
/**
 * @class ProductApi
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 * Implements Sportmaster API for product management
 */
namespace Classes\Sportmaster\v2;

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/v2/ProductIterator.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/v2/Product.php');

class ProductApi
{
    private $log;
    private $api;
    private $clientId;
    private $warehouseId;

    private $limit = 500; // Default limit for API requests
    private $sleepTime = 1; // Sleep time between API requests to avoid rate limits

    public function __construct($organization)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/v2/Api.php');

        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);

        $clientName = 'SPORTMASTER_' . strtoupper($organization) . '_CLIENT_ID';
        $this->clientId = constant($clientName);
        $this->api = new \Classes\Sportmaster\v2\Api($this->clientId);

        $warehouseName = 'SPORTMASTER_' . strtoupper($organization) . '_WAREHOUSE_ID';
        $this->warehouseId = constant($warehouseName);
    }

    /**
     * Merges product stocks and prices data
     * @param array $stocks Array of stock data
     * @param array $prices Array of price data  
     * @return array Merged product data
     */
    private function mergeProductData($stocks, $prices)
    {
        if (is_string($stocks)) {
            $stocks = json_decode($stocks, true);
        }
        if (is_string($prices)) {
            $prices = json_decode($prices, true);
        }

        // Build a lookup for prices by offerId
        $priceMap = [];
        foreach ($prices as $price) {
            if (isset($price['offerId'])) {
                $priceMap[$price['offerId']] = $price;
            }
        }

        // Merge stocks and prices by offerId into a single array
        $mergedList = [];
        foreach ($stocks as $stock) {
            $offerId = $stock['offerId'] ?? null;
            $merged = $stock;
            
            if ($offerId && isset($priceMap[$offerId])) {
                // Merge price data into stock data
                $priceData = $priceMap[$offerId];
                foreach ($priceData as $key => $value) {
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
     * Fetches product stocks from Sportmaster API
     * @return array|false
     */
    private function fetchStocks()
    {
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Fetching stocks');

        $url = SPORTMASTER_BASE_URL . SPORTMASTER_API_STOCKS_LIST;
        $postData = array(
            'warehouseId' => $this->warehouseId,
            'limit' => $this->limit,
            'offset' => 0
        );
        $products = []; // Initialize products array
        do {
            $response = $this->api->postData($url, $postData);
            $response = json_decode($response, true);
			if ($response && isset($response['stocks']))
			{
				$products = array_merge($products, $response['stocks']);
				$postData['offset'] += $this->limit;
			}
			else
			{
				$this->log->write(__LINE__ . ' '. __METHOD__ . ' Error fetching product stocks: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return false;
			}
			sleep($this->sleepTime); // Sleep to avoid hitting API rate limits
        } while (isset($response['pagination']['total']) && $postData['offset'] < $response['pagination']['total']);
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Fetched ' . count($products) . ' stocks');
        return $products;
    }

    /**
     * Fetches product prices from Sportmaster API
     * @return array|false
     */
    private function fetchPrices()
    {
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Fetching prices');

        $url = SPORTMASTER_BASE_URL . SPORTMASTER_API_PRICES_LIST;
        $postData = array(
            'limit' => $this->limit,
            'offset' => 0
        );
        $products = [];
        do {
            $response = $this->api->postData($url, $postData);
            $response = json_decode($response, true);
            if ($response && isset($response['productPrices']))
            {
                $products = array_merge($products, $response['productPrices']);
                $postData['offset'] += $this->limit;
            }
            else
            {
                $this->log->write(__LINE__ . ' '. __METHOD__ . ' Error fetching product prices: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                return false;
            }
            sleep($this->sleepTime); // Sleep to avoid hitting API rate limits
        } while ($postData['offset'] < $response['pagination']['total']);

        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Fetched ' . count($products) . ' prices');
        return $products;
    }

    /**
     * Fetches products from Sportmaster API by merging stocks and prices
     * @return array|false
     * @throws \Exception
     */
    public function fetchProducts()
    {
        try {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Starting product fetch');
            
            $stocks = $this->fetchStocks();
            $prices = $this->fetchPrices();
            
            if (empty($stocks)) {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Error - No stocks found');
                return $prices;
            }
            
            // If no prices found, just return stocks data
            if (empty($prices)) {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Warning - No prices found, returning stocks only');
                return $stocks;
            }
            
            $mergedData = $this->mergeProductData($stocks, $prices);
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
     * Returns an iterator for products fetched from Sportmaster API
     * @return \Classes\Sportmaster\v2\ProductIterator|false
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

    /**
     * Update product stocks
     * @param array $stockUpdates Array of stock updates
     * @return array|false
     */
    public function updateStocks($stockUpdates)
    {
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Updating stocks');
        
        $url = SPORTMASTER_BASE_URL . 'v1/stocks';
        
        $response = $this->api->postData($url, $stockUpdates);
        if (is_string($response)) {
            $response = json_decode($response, true);
        }
        
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Stock update response: ' . json_encode($response));
        return $response;
    }

    /**
     * Update product prices
     * @param array $priceUpdates Array of price updates
     * @return array|false
     */
    public function updatePrices($priceUpdates)
    {
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Updating prices');
        
        $url = SPORTMASTER_BASE_URL . 'v1/prices';
        
        $response = $this->api->postData($url, $priceUpdates);
        if (is_string($response)) {
            $response = json_decode($response, true);
        }
        
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Price update response: ' . json_encode($response));
        return $response;
    }
}
