<?php

namespace Classes\Wildberries\v2;

/**
 * Class Product
 * Represents a Wildberries product with main attributes and prices.
 */
class Product implements \JsonSerializable
{
    private int $nmID;
    private string $vendorCode;
    // Removed sizes array
    private int $price;
    private int $discountedPrice;
    private int $clubDiscountedPrice;
    private string $currencyCode;
    private int $discount;
    private int $clubDiscount;
    private bool $editableSizePrice;
    private int $amount;
    private string $sku;

    /**
     * Product constructor.
     * @param array|string $productData Array or JSON string with product data
     */
    public function __construct($productData)
    {
        if (is_string($productData)) {
            $productData = json_decode($productData, true) ?? [];
        }
        $this->nmID = $productData['nmID'] ?? 0;
        $this->vendorCode = $productData['vendorCode'] ?? '';
        $this->price = isset($productData['sizes'][0]['price']) ? $productData['sizes'][0]['price'] : null;
        $this->discountedPrice = isset($productData['sizes'][0]['discountedPrice']) ? $productData['sizes'][0]['discountedPrice'] : null;
        $this->clubDiscountedPrice = isset($productData['sizes'][0]['clubDiscountedPrice']) ? $productData['sizes'][0]['clubDiscountedPrice'] : null;
        $this->currencyCode = $productData['currencyIsoCode4217'] ?? '';
        $this->discount = $productData['discount'] ?? 0;
        $this->clubDiscount = $productData['clubDiscount'] ?? 0;
        $this->editableSizePrice = $productData['editableSizePrice'] ?? false;
        $this->amount = $productData['amount'] ?? 0;
        $this->sku = $productData['sku'] ?? null;
    }

    // Getters
    public function getNmID(): int { return $this->nmID; }
    public function getVendorCode(): string { return $this->vendorCode; }
    public function getPrice(): int { return $this->price; }
    public function getDiscountedPrice(): int { return $this->discountedPrice; }
    public function getClubDiscountedPrice(): int { return $this->clubDiscountedPrice; }
    public function getCurrencyCode(): string { return $this->currencyCode; }
    public function getDiscount(): int { return $this->discount; }
    public function getClubDiscount(): int { return $this->clubDiscount; }
    public function getEditableSizePrice(): bool { return $this->editableSizePrice; }
    public function getAmount(): int { return $this->amount; }
    public function getSku(): string { return $this->sku; }

    // Setters
    public function setVendorCode(string $vendorCode): void { $this->vendorCode = $vendorCode; }
    public function setPrice(int $price): void { $this->price = $price; }
    public function setDiscountedPrice(int $discountedPrice): void { $this->discountedPrice = $discountedPrice; }
    public function setClubDiscountedPrice(int $clubDiscountedPrice): void { $this->clubDiscountedPrice = $clubDiscountedPrice; }
    public function setCurrencyCode(string $code): void { $this->currencyCode = $code; }
    public function setDiscount(int $discount): void { $this->discount = $discount; }
    public function setClubDiscount(int $clubDiscount): void { $this->clubDiscount = $clubDiscount; }
    public function setEditableSizePrice(bool $editable): void { $this->editableSizePrice = $editable; }
    public function setAmount(int $amount): void { $this->amount = $amount; }
    public function setSku(string $sku): void { $this->sku = $sku; }

    /**
     * Specify data for JSON serialization
     * @return array
     */
    public function jsonSerialize() {
        return [
            'nmID' => $this->nmID,
            'vendorCode' => $this->vendorCode,
            'price' => $this->price,
            'discountedPrice' => $this->discountedPrice,
            'clubDiscountedPrice' => $this->clubDiscountedPrice,
            'currencyCode' => $this->currencyCode,
            'discount' => $this->discount,
            'clubDiscount' => $this->clubDiscount,
            'editableSizePrice' => $this->editableSizePrice,
            'amount' => $this->amount,
            'sku' => $this->sku,
        ];
    }
}