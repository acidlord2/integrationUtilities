<?php
/**
 * @class Product
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 * Consumes Yandex product JSON as optional input
 */
namespace Classes\Yandex\v2;

class Product implements \JsonSerializable
{
    private $offer_id;
    private $basic_price_value;
    private $basic_price_currency_id;
    private $campaign_price_vat;
    private $status;
    private $quantity;

    public function __construct($product = null)
    {
        if(is_string($product)) {
            $product = json_decode($product, true);
        }

        if ($product) {
            $this->offer_id = $product['offerId'] ?? null;
            
            // Basic price handling
            if (isset($product['basicPrice']) && is_array($product['basicPrice'])) {
                $this->basic_price_value = $product['basicPrice']['value'] ?? null;
                $this->basic_price_currency_id = $product['basicPrice']['currencyId'] ?? null;
            }
            
            // Campaign price handling
            if (isset($product['campaignPrice']) && is_array($product['campaignPrice'])) {
                $this->campaign_price_vat = $product['campaignPrice']['vat'] ?? null;
            }
            
            $this->status = $product['status'] ?? null;
            $this->quantity = $product['quantity'] ?? null;
        }
    }

    public function getOfferId() { return $this->offer_id; }
    public function setOfferId($v) { $this->offer_id = $v; }

    public function getBasicPriceValue() { return $this->basic_price_value; }
    public function setBasicPriceValue($v) { $this->basic_price_value = $v; }

    public function getBasicPriceCurrencyId() { return $this->basic_price_currency_id; }
    public function setBasicPriceCurrencyId($v) { $this->basic_price_currency_id = $v; }

    public function getCampaignPriceVat() { return $this->campaign_price_vat; }
    public function setCampaignPriceVat($v) { $this->campaign_price_vat = $v; }

    public function getStatus() { return $this->status; }
    public function setStatus($v) { $this->status = $v; }

    public function getQuantity() { return $this->quantity; }
    public function setQuantity($v) { $this->quantity = $v; }

    public function jsonSerialize()
    {
        return [
            'offerId' => $this->offer_id,
            'basicPriceValue' => $this->basic_price_value,
            'basicPriceCurrencyId' => $this->basic_price_currency_id,
            'campaignPriceVat' => $this->campaign_price_vat,
            'status' => $this->status,
            'quantity' => $this->quantity
        ];
    }
}
