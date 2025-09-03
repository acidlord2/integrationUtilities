<?php
/**
 * @class Product
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 * Consumes Sportmaster product JSON as optional input
 */
namespace Classes\Sportmaster\v2;

class Product implements \JsonSerializable
{
    private $offer_id;
    private $barcode;
    private $name;
    private $warehouse_stock;
    private $retail_stock;
    private $price;
    private $retail_price;
    private $currency_code;

    public function __construct($product = null)
    {
        if (is_string($product)) {
            $product = json_decode($product, true);
        }
        
        if ($product) {
            $this->offer_id = $product['offerId'] ?? null;
            $this->barcode = $product['barcode'] ?? null;
            $this->name = $product['name'] ?? null;
            $this->warehouse_stock = $product['warehouseStock'] ?? null;
            $this->retail_stock = $product['retailStock'] ?? null;
            $this->price = $product['price'] ?? null;
            $this->retail_price = $product['retailPrice'] ?? null;
            $this->currency_code = $product['currencyCode'] ?? null;
        }
    }

    public function getOfferId() { return $this->offer_id; }
    public function setOfferId($v) { $this->offer_id = $v; }

    public function getBarcode() { return $this->barcode; }
    public function setBarcode($v) { $this->barcode = $v; }

    public function getName() { return $this->name; }
    public function setName($v) { $this->name = $v; }

    public function getWarehouseStock() { return $this->warehouse_stock; }
    public function setWarehouseStock($v) { $this->warehouse_stock = $v; }

    public function getRetailStock() { return $this->retail_stock; }
    public function setRetailStock($v) { $this->retail_stock = $v; }

    public function getPrice() { return $this->price; }
    public function setPrice($v) { $this->price = $v; }

    public function getRetailPrice() { return $this->retail_price; }
    public function setRetailPrice($v) { $this->retail_price = $v; }

    public function getCurrencyCode() { return $this->currency_code; }
    public function setCurrencyCode($v) { $this->currency_code = $v; }

    public function jsonSerialize()
    {
        return [
            'offerId' => $this->offer_id,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'warehouseStock' => $this->warehouse_stock,
            'retailStock' => $this->retail_stock,
            'price' => $this->price,
            'retailPrice' => $this->retail_price,
            'currencyCode' => $this->currency_code
        ];
    }
}
