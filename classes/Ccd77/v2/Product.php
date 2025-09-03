<?php
namespace Classes\Ccd77\v2;

/**
 * Class Product
 * Represents a product for CCD77 with fields matching wp_wc_product_meta_lookup table.
 */
class Product implements \JsonSerializable {
    // Getters
    private $product_id;
    private $sku;
    private $virtual;
    private $downloadable;
    private $min_price;
    private $max_price;
    private $onsale;
    private $stock_quantity;
    private $stock_status;
    private $rating_count;
    private $average_rating;
    private $total_sales;
    private $tax_status;
    private $tax_class;
    private $global_unique_id;

    public function getProductId() { return $this->product_id; }
    public function getSku() { return $this->sku; }
    public function getVirtual() { return $this->virtual; }
    public function getDownloadable() { return $this->downloadable; }
    public function getMinPrice() { return $this->min_price; }
    public function getMaxPrice() { return $this->max_price; }
    public function getOnsale() { return $this->onsale; }
    public function getStockQuantity() { return $this->stock_quantity; }
    public function getStockStatus() { return $this->stock_status; }
    public function getRatingCount() { return $this->rating_count; }
    public function getAverageRating() { return $this->average_rating; }
    public function getTotalSales() { return $this->total_sales; }
    public function getTaxStatus() { return $this->tax_status; }
    public function getTaxClass() { return $this->tax_class; }
    public function getGlobalUniqueId() { return $this->global_unique_id; }

    // Setters
    public function setVirtual($value) { $this->virtual = $value; }
    public function setDownloadable($value) { $this->downloadable = $value; }
    public function setMinPrice($value) { $this->min_price = $value; }
    public function setMaxPrice($value) { $this->max_price = $value; }
    public function setOnsale($value) { $this->onsale = $value; }
    public function setStockQuantity($value) { $this->stock_quantity = $value; }
    public function setStockStatus($value) { $this->stock_status = $value; }
    public function setRatingCount($value) { $this->rating_count = $value; }
    public function setAverageRating($value) { $this->average_rating = $value; }
    public function setTotalSales($value) { $this->total_sales = $value; }
    public function setTaxStatus($value) { $this->tax_status = $value; }
    public function setTaxClass($value) { $this->tax_class = $value; }
    public function setGlobalUniqueId($value) { $this->global_unique_id = $value; }

    /**
     * Product constructor.
     * @param array $data Associative array with product fields
     */
    public function jsonSerialize() {
        return [
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'virtual' => $this->virtual,
            'downloadable' => $this->downloadable,
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'onsale' => $this->onsale,
            'stock_quantity' => $this->stock_quantity,
            'stock_status' => $this->stock_status,
            'rating_count' => $this->rating_count,
            'average_rating' => $this->average_rating,
            'total_sales' => $this->total_sales,
            'tax_status' => $this->tax_status,
            'tax_class' => $this->tax_class,
            'global_unique_id' => $this->global_unique_id
        ];
    }
    public function __construct(array $data = []) {
        $this->product_id = $data['product_id'] ?? null;
        $this->sku = $data['sku'] ?? null;
        $this->virtual = $data['virtual'] ?? 0;
        $this->downloadable = $data['downloadable'] ?? 0;
        $this->min_price = $data['min_price'] ?? null;
        $this->max_price = $data['max_price'] ?? null;
        $this->onsale = $data['onsale'] ?? 0;
        $this->stock_quantity = $data['stock_quantity'] ?? null;
        $this->stock_status = $data['stock_status'] ?? null;
        $this->rating_count = $data['rating_count'] ?? 0;
        $this->average_rating = $data['average_rating'] ?? 0.00;
        $this->total_sales = $data['total_sales'] ?? 0;
        $this->tax_status = $data['tax_status'] ?? 'taxable';
        $this->tax_class = $data['tax_class'] ?? '';
        $this->global_unique_id = $data['global_unique_id'] ?? '';
    }
}
