<?php
namespace MS\Cusomerorder\v1;

class Position {
    public $id;
    public $accountId;
    public $quantity;
    public $price;
    public $discount;
    public $vat;
    public $vatEnabled;
    public $assortment;
    public $shipped;
    public $reserve;

    public function __construct($json = null) {
        if ($json) {
            if (is_string($json)) {
                $data = json_decode($json, true);
            } else {
                $data = $json;
            }
            $this->id = $data['id'] ?? null;
            $this->accountId = $data['accountId'] ?? null;
            $this->quantity = $data['quantity'] ?? null;
            $this->price = $data['price'] ?? null;
            $this->discount = $data['discount'] ?? null;
            $this->vat = $data['vat'] ?? null;
            $this->vatEnabled = $data['vatEnabled'] ?? null;
            $this->assortment = $data['assortment'] ?? null;
            $this->shipped = $data['shipped'] ?? null;
            $this->reserve = $data['reserve'] ?? null;
        } else {
            $this->id = null;
            $this->accountId = null;
            $this->quantity = null;
            $this->price = null;
            $this->discount = null;
            $this->vat = null;
            $this->vatEnabled = null;
            $this->assortment = null;
            $this->shipped = null;
            $this->reserve = null;
        }
    }
}

class CustomerOrderPosition {
    public $positions = [];

    public function __construct($json = null) {
        if ($json) {
            if (is_string($json)) {
                $data = json_decode($json, true);
            } else {
                $data = $json;
            }
            if (isset($data['rows']) && is_array($data['rows'])) {
                foreach ($data['rows'] as $row) {
                    $this->positions[] = new Position($row);
                }
            }
        }
    }
}
