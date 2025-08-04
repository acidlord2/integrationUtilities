<?php
namespace Classes\MS\v2;

class AssortmentIterator implements \IteratorAggregate {
    public function getIterator() {
        return new \ArrayIterator($this->assortments);
    }
    private $assortments = [];
    private $position = 0;

    /**
     * @param string|array $json JSON string or array of assortment objects
     */
    public function __construct($json) {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/Assortment.php');

        if (is_string($json)) {
            $data = json_decode($json, true);
        } else {
            $data = $json;
        }
        // Always treat $data as a pure array of assortments
        if (is_array($data)) {
            foreach ($data as $item) {
                $this->assortments[] = new Assortment($item);
            }
        }
        $this->position = 0;
    }

    // Removed Iterator methods
}
