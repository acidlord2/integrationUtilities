<?php
/**
 * @class Product
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 * Consumes Ozon product and stock JSONs as optional input
 */
namespace Classes\Ozon\v2;


class Product implements \JsonSerializable
{
    private $product_id;
    private $offer_id;
    private $sku;
    private $warehouse_ids = [];
    private $present;
    private $reserved;
    private $volume_weight;
    private $old_price;
    private $price;

    public function __construct($product = null)
    {
        if ($product) {
            $this->product_id = $product['product_id'] ?? null;
            $this->offer_id = $product['offer_id'] ?? null;
            $this->volume_weight = $product['volume_weight'] ?? null;
            $this->old_price = isset($product['price']) && isset($product['price']['old_price']) ? $product['price']['old_price'] : null;
            $this->price = isset($product['price']) && isset($product['price']['price']) ? $product['price']['price'] : null;
            if (isset($product['stocks']) && is_array($product['stocks']) && count($product['stocks']) > 0) {
                $fbs = array_search('fbs', array_column($product['stocks'], 'type'));
                if ($fbs !== false) {
                    $stock = $product['stocks'][$fbs];
                } else {
                    $stock = null;
                }
            }
            $this->sku = $stock['sku'] ?? null;
            $this->warehouse_ids = $stock['warehouse_ids'] ?? [];
            $this->present = $stock['present'] ?? null;
            $this->reserved = $stock['reserved'] ?? null;
        }
    }

    public function getProductId() { return $this->product_id; }
    public function setProductId($v) { $this->product_id = $v; }

    public function getOfferId() { return $this->offer_id; }
    public function setOfferId($v) { $this->offer_id = $v; }

    public function getSku() { return $this->sku; }
    public function setSku($v) { $this->sku = $v; }

    public function getWarehouseIds() { return $this->warehouse_ids; }
    public function setWarehouseIds($v) { $this->warehouse_ids = $v; }

    public function getPresent() { return $this->present; }
    public function setPresent($v) { $this->present = $v; }

    public function getReserved() { return $this->reserved; }
    public function setReserved($v) { $this->reserved = $v; }

    public function getVolumeWeight() { return $this->volume_weight; }
    public function setVolumeWeight($v) { $this->volume_weight = $v; }

    public function getOldPrice() { return $this->old_price; }
    public function setOldPrice($v) { $this->old_price = $v; }

    public function getPrice() { return $this->price; }
    public function setPrice($v) { $this->price = $v; }

    public function jsonSerialize() {
        return [
            'product_id' => $this->product_id,
            'offer_id' => $this->offer_id,
            'sku' => $this->sku,
            'warehouse_ids' => $this->warehouse_ids,
            'present' => $this->present,
            'reserved' => $this->reserved,
            'volume_weight' => $this->volume_weight,
            'old_price' => $this->old_price,
            'price' => $this->price
        ];
    }
}
