<?php
namespace Classes\Ozon\v2;

class ProductIterator implements \IteratorAggregate {
    private $products = [];

    /**
     * @param string|array $prices Array of prices objects or JSON string
     * @param string|array $stocks Array of stock objects or JSON string
     */
    public function __construct($products = []) {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/v2/Product.php');

        foreach ($products as $product) {
            $this->products[] = new Product($product);
        }
    }

    /**
     * Fetches products from Ozon API and returns an iterator
     * @return ProductIterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->products);
    }

    public function jsonSerialize() {
        return $this->products;
    }
}
