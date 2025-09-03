<?php
/**
 * @class ProductApi
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 * Implements Ozon v4/product/info/stocks and v5/product/info/prices
 */
namespace Classes\Ozon\v2;

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/v2/ProductIterator.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/v2/Product.php');

class ProductApi
{
    private $log;
    private $api;
    private $limit = 1000; // Default limit for API requests

    public function __construct($organization)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/v2/Api.php');

        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);
        $this->api = new \Classes\Ozon\v2\Api($organization);
    }

    /**
     * Fetches product prices and stocks from Ozon API
     * @param array $filter Filter parameters for the API request
     * @return array|false
     * @throws \Exception
     * */
    private function mergePricesAndStocks($prices, $stocks)
    {
        if (is_string($prices)) {
            $prices = json_decode($prices, true);
        }
        if (is_string($stocks)) {
            $stocks = json_decode($stocks, true);
        }

        // Build a lookup for stocks by product_id
        $stockMap = [];
        foreach ($stocks as $stock) {
            if (isset($stock['product_id'])) {
                $stockMap[$stock['product_id']] = $stock;
            }
        }

        // Merge prices and stocks by product_id into a single array
        $mergedList = [];
        foreach ($prices as $price) {
            $productId = $price['product_id'] ?? null;
            $merged = $price;
            if ($productId && isset($stockMap[$productId])) {
                // Merge stock fields into price fields, stocks array takes precedence
                foreach ($stockMap[$productId] as $key => $value) {
                    $merged[$key] = $value;
                }
            }
            $mergedList[] = $merged;
        }
        return $mergedList;
    }

    /**
     * Fetches product prices and stocks from Ozon API
     * @param array $filter Filter parameters for the API request
     * @return \Classes\Ozon\v2\ProductIterator|false
     * @throws \Exception
     */
    public function fetchProducts()
    {
        $url = OZON_MAINURL . OZON_API_V4 . OZON_API_PRODUCT_STOCKS;
        $stocks = [];
        $cursor = null;
        $filter = new \stdClass(); // Initialize filter as an empty object
        while (true) {
            $postdata = array(
                'filter' => $filter,
                'limit' => $this->limit,
                'cursor' => $cursor
            );
            $response = $this->api->postData($url, $postdata);
            if (is_string($response)) {
                $response = json_decode($response, true);
            }

            if ($response && isset ($response['items']) && is_array($response['items'])) {
                $stocks = array_merge($stocks, $response['items']);
            }

            if (!empty($response['cursor'])) {
                $cursor = $response['cursor'];
            } else {
                break;
            }
        }
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' stocks count - ' . count($stocks));
        $url = OZON_MAINURL . OZON_API_V5 . OZON_API_PRODUCT_PRICES;
        $prices = [];
        $cursor = null;
        while (true) {
            $postdata = array(
                'filter' => $filter,
                'limit' => $this->limit,
                'cursor' => $cursor
            );
            $response = $this->api->postData($url, $postdata);
            if (is_string($response)) {
                $response = json_decode($response, true);
            }

            if ($response && isset ($response['items']) && is_array($response['items'])) {
                $prices = array_merge($prices, $response['items']);
            }

            if (!empty($response['cursor'])) {
                $cursor = $response['cursor'];
            } else {
                break;
            }
        }
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' prices count - ' . count($prices));
        $mergedData = $this->mergePricesAndStocks($prices, $stocks);
        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' mergedData count - ' . count($mergedData));
        if (empty($mergedData)) {
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - No products found');
            return false;
        }
        return $mergedData;

    }

    /**
     * Returns an iterator for products fetched from Ozon API
     * @return \Classes\Ozon\v2\ProductIterator
     * @throws \Exception
     * @throws \JsonException
     */
    public function getProductIterator()
    {
        $products = $this->fetchProducts();
        return new ProductIterator($products);
    }
}
