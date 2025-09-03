<?php
namespace Classes\MS\v2;

class AssortmentIterator implements \IteratorAggregate {
    public function getIterator() {
        return new \ArrayIterator($this->assortments);
    }
    private $assortments = [];

    /**
     * @param string|array $json JSON string or array of assortment objects
     */
    public function __construct($assortments) {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/Assortment.php');

        $items = [];
        if (is_string($assortments)) {
            $decoded = json_decode($assortments, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        } elseif (is_array($assortments)) {
            $items = $assortments;
        }
        foreach ($items as $item) {
            $this->assortments[] = new Assortment($item);
        }
    }
}
