<?php

namespace MS\v2;

/**
 * Customer Order Position class for MoySklad API v1.2
 * Represents a position (line item) in a customer order
 * Based on MoySklad API documentation for customer order positions
 */
class CustomerOrderPosition
{
    // Core fields
    private ?string $accountId;
    private ?object $assortment;
    private ?float $discount;
    private ?string $id;
    private ?object $pack;
    private ?float $price;
    private ?float $quantity;
    private ?float $reserve;
    private ?float $shipped;
    private ?string $taxSystem;
    private ?int $vat;
    private ?bool $vatEnabled;
    private ?array $originalJson;

    /**
     * Constructor
     */
    public function __construct($json = null)
    {
        // Initialize with default values
        if ($json){
            if (is_string($json)) {
                $data = json_decode($json, true);
            } else {
                $data = $json;
            }
            $this->accountId = $data['accountId'] ?? null;
            $this->assortment = isset($data['assortment']) ? (object)$data['assortment'] : null;
            $this->discount = $data['discount'] ?? null;
            $this->id = $data['id'] ?? null;
            $this->pack = isset($data['pack']) ? (object)$data['pack'] : null;
            $this->price = $data['price'] ?? null;
            $this->quantity = $data['quantity'] ?? null;
            $this->reserve = $data['reserve'] ?? null;
            $this->shipped = $data['shipped'] ?? null;
            $this->taxSystem = $data['taxSystem'] ?? null;
            $this->vat = $data['vat'] ?? null;
            $this->vatEnabled = $data['vatEnabled'] ?? null;

            $this->originalJson = $data;
        } else {
            $this->accountId = null;
            $this->assortment = null;
            $this->discount = null;
            $this->id = null;
            $this->pack = null;
            $this->price = null;
            $this->quantity = null;
            $this->reserve = null;
            $this->shipped = null;
            $this->taxSystem = null;
            $this->vat = null;
            $this->vatEnabled = null;

            $this->originalJson = null;
        }
    }

    // Getters
    public function getAccountId() { return $this->accountId; }
    public function getAssortment() { return $this->assortment; }
    public function getDiscount() { return $this->discount; }
    public function getId() { return $this->id; }
    public function getPack() { return $this->pack; }
    public function getPrice() { return $this->price; }
    public function getQuantity() { return $this->quantity; }
    public function getReserve() { return $this->reserve; }
    public function getShipped() { return $this->shipped; }
    public function getTaxSystem() { return $this->taxSystem; }
    public function getVat() { return $this->vat; }
    public function getVatEnabled() { return $this->vatEnabled; }

    // Setters
    public function setAssortment(?object $assortment) { $this->assortment = $assortment; }
    public function setAccountId(?string $accountId) { $this->accountId = $accountId; }
    public function setDiscount(?float $discount) { $this->discount = $discount; }
    public function setPack(?object $pack) { $this->pack = $pack; }
    public function setPrice(?float $price) { $this->price = $price; }
    public function setQuantity(?float $quantity) { $this->quantity = $quantity; }
    public function setReserve(?float $reserve) { $this->reserve = $reserve; }
    public function setShipped(?float $shipped) { $this->shipped = $shipped; }
    public function setTaxSystem(?string $taxSystem) { $this->taxSystem = $taxSystem; }
    public function setVat(?int $vat) { $this->vat = $vat; }
    public function setVatEnabled(?bool $vatEnabled) { $this->vatEnabled = $vatEnabled; }

    /**
     * JSON serialize for API insert operations
     * @return array
     */
    public function jsonSerializeForInsert(): array
    {
        $data = [
            'assortment' => $this->assortment,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'reserve' => isset($this->reserve) && is_numeric($this->reserve) ? $this->reserve : 0, // Reserve is mandatory, default to 0
        ];
        
        // Only include optional fields if they have valid values
        if (isset($this->discount) && is_numeric($this->discount)) {
            $data['discount'] = $this->discount;
        }
        
        if (isset($this->shipped) && is_numeric($this->shipped)) {
            $data['shipped'] = $this->shipped;
        }
        
        if (isset($this->taxSystem)) {
            $data['taxSystem'] = $this->taxSystem;
        }
        
        if (isset($this->vat) && is_numeric($this->vat)) {
            $data['vat'] = $this->vat;
        }
        
        if (isset($this->vatEnabled) && is_bool($this->vatEnabled)) {
            $data['vatEnabled'] = $this->vatEnabled;
        }
        
        return $data;
    }

    /**
     * JSON serialize for API update operations
     * @return array
     */
    public function jsonSerializeForUpdate(): array
    {
        $data = [
            'id' => $this->id,
            'assortment' => $this->assortment,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'reserve' => isset($this->reserve) && is_numeric($this->reserve) ? $this->reserve : 0, // Reserve is mandatory, default to 0
        ];
        
        // Only include optional fields if they have valid values
        if (isset($this->discount) && is_numeric($this->discount)) {
            $data['discount'] = $this->discount;
        }
        
        if (isset($this->shipped) && is_numeric($this->shipped)) {
            $data['shipped'] = $this->shipped;
        }
        
        if (isset($this->taxSystem)) {
            $data['taxSystem'] = $this->taxSystem;
        }
        
        if (isset($this->vat) && is_numeric($this->vat)) {
            $data['vat'] = $this->vat;
        }
        
        if (isset($this->vatEnabled) && is_bool($this->vatEnabled)) {
            $data['vatEnabled'] = $this->vatEnabled;
        }
        
        return $data;
    }

    /**
     * JSON serialize for API read operations (full data)
     * @return array
     */
    public function jsonSerializeForRead(): array
    {
        $data = [
            'accountId' => $this->accountId,
            'assortment' => $this->assortment,
            'discount' => $this->discount,
            'id' => $this->id,
            'pack' => $this->pack,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'reserve' => $this->reserve,
            'shipped' => $this->shipped,
            'taxSystem' => $this->taxSystem,
            'vat' => $this->vat,
            'vatEnabled' => $this->vatEnabled,
            'originalJson' => $this->originalJson
        ];
        return $data;
    }

    /**
     * Convert to array
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->jsonSerializeForRead();
    }
}