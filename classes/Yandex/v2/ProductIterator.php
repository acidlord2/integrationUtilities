<?php
/**
 * @class ProductIterator
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 * Iterator for Yandex products
 */
namespace Classes\Yandex\v2;

class ProductIterator implements \IteratorAggregate, \JsonSerializable
{
    private $products = [];

    /**
     * @param array $products Array of product objects
     */
    public function __construct($products = [])
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/v2/Product.php');

        if(is_string($products)) {
            $products = json_decode($products, true);
        }

        foreach ($products as $product) {
            $this->products[] = new Product($product);
        }
    }

    /**
     * Returns an iterator for products
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->products);
    }

    /**
     * Returns JSON serializable array of products
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->products;
    }

    /**
     * Get all products as array
     * @return array
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Add a product to the collection
     * @param Product $product
     */
    public function addProduct(Product $product)
    {
        $this->products[] = $product;
    }

    /**
     * Get count of products
     * @return int
     */
    public function count()
    {
        return count($this->products);
    }
}
