<?php
namespace Classes\Ccd77\v2;

/**
 * Class ProductIterator
 * Iterates over an array or JSON string of CCD77 Product objects.
 */
class ProductIterator implements \IteratorAggregate {
    private $products = [];
    private $position = 0;

    /**
     * @param string|array $json JSON string or array of product objects
     */
    public function __construct($json) {
        if (is_string($json)) {
            $data = json_decode($json, true);
        } else {
            $data = $json;
        }
        if (is_array($data)) {
            foreach ($data as $item) {
                $this->products[] = new Product($item);
            }
        }
        $this->position = 0;
    }

    public function getIterator() {
        return new \ArrayIterator($this->products);
    }

    // Removed Iterator methods
}
