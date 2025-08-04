<?php
namespace Classes\Ccd77\v2;

/**
 * Class ProductIterator
 * Iterates over an array or JSON string of CCD77 Product objects.
 */
class ProductIterator implements \Iterator, \IteratorAggregate {
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
        return $this;
    }

    public function current() {
        return $this->products[$this->position];
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        ++$this->position;
    }

    public function rewind() {
        $this->position = 0;
    }

    public function valid() {
        return isset($this->products[$this->position]);
    }
}
