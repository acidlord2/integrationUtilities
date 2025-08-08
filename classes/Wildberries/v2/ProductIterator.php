<?php

namespace Classes\Wildberries\v2;

/**
 * Class ProductIterator
 * Iterator and serializer for a collection of Wildberries products.
 */
class ProductIterator implements \IteratorAggregate, \JsonSerializable
{
    private array $products = [];

    /**
     * ProductIterator constructor.
     * @param array $products Array of product data
     */
    public function __construct($products = [])
    {
        foreach ($products as $product) {
            $this->products[] = new Product($product);
        }
    }

    /**
     * Returns an iterator for the products collection
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->products);
    }

    /**
     * Specify data for JSON serialization
     * @return array
     */
    public function jsonSerialize(): mixed
    {
        return $this->products;
    }
}
