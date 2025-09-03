<?php
namespace Classes\MS\v2;

class Assortment implements \JsonSerializable {
    private $meta;
    private $id;
    private $accountId;
    private $owner;
    private $shared;
    private $group;
    private $updated;
    private $name;
    private $description;
    private $code;
    private $externalCode;
    private $archived;
    private $pathName;
    private $productFolder;
    private $effectiveVat;
    private $effectiveVatEnabled;
    private $vat;
    private $vatEnabled;
    private $useParentVat;
    private $taxSystem;
    private $uom;
    private $images;
    private $minPrice;
    private $salePrices;
    private $buyPrice;
    // Horizontal price properties
    private $priceRetail;
    private $priceWholesale;
    private $priceSmallWholesale;
    private $priceLargeWholesale;
    private $pricePurchase;
    private $priceOldRetail;
    private $priceOldWholesale;
    private $priceOldSmallWholesale;
    private $priceOldLargeWholesale;
    private $priceOldPurchase;
    private $priceSale;
    private $pricePromo;
    private $priceSmmAlliance;
    private $priceSmmUllo;
    private $price10kidsGoods;
    private $pricePromo10kidsGoods;
    private $priceBeru;
    private $priceBeruUllo;
    private $priceOzon;
    private $priceOzonKaori;
    private $priceSportmaster;
    private $priceWB;
    private $priceWBUllo;
    private $priceDBS10kids;
    // Horizontal attribute properties
    private $attrName;
    private $attrCategory1;
    private $attrCategory2;
    private $attrCategory3;
    private $attrBrand;
    private $attrLength;
    private $attrWidth;
    private $attrHeight;
    private $attrSize;
    private $attrHalal;
    private $barcodes;
    private $supplier;
    private $attributes;
    private $paymentItemType;
    private $discountProhibited;
    private $country;
    private $article;
    private $weight;
    private $volume;
    private $minimumBalance;
    private $variantsCount;
    private $isSerialTrackable;
    private $files;
    private $trackingType;
    private $stock;
    private $reserve;
    private $inTransit;
    private $quantity;
    private $originalJson;
    // Getters and setters
    public function getMeta() { return $this->meta; }
    public function getId() { return $this->id; }
    public function getAccountId() { return $this->accountId; }
    public function getOwner() { return $this->owner; }
    public function getShared() { return $this->shared; }
    public function getGroup() { return $this->group; }
    public function getUpdated() { return $this->updated; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getCode() { return $this->code; }
    public function getExternalCode() { return $this->externalCode; }
    public function getArchived() { return $this->archived; }
    public function getPathName() { return $this->pathName; }
    public function getProductFolder() { return $this->productFolder; }
    public function getEffectiveVat() { return $this->effectiveVat; }
    public function getEffectiveVatEnabled() { return $this->effectiveVatEnabled; }
    public function getVat() { return $this->vat; }
    public function getVatEnabled() { return $this->vatEnabled; }
    public function getUseParentVat() { return $this->useParentVat; }
    public function getTaxSystem() { return $this->taxSystem; }
    public function getUom() { return $this->uom; }
    public function getImages() { return $this->images; }
    public function getMinPrice() { return $this->minPrice; }
    public function getSalePrices() { return $this->salePrices; }
    public function getBuyPrice() { return $this->buyPrice; }
    
    private function kopeeksToRoubles($value) {
        return isset($value) ? $value / 100 : null;
    }

    public function getPriceRetail() { return $this->kopeeksToRoubles($this->priceRetail); }
    public function getPriceWholesale() { return $this->kopeeksToRoubles($this->priceWholesale); }
    public function getPriceSmallWholesale() { return $this->kopeeksToRoubles($this->priceSmallWholesale); }
    public function getPriceLargeWholesale() { return $this->kopeeksToRoubles($this->priceLargeWholesale); }
    public function getPricePurchase() { return $this->kopeeksToRoubles($this->pricePurchase); }
    public function getPriceOldRetail() { return $this->kopeeksToRoubles($this->priceOldRetail); }
    public function getPriceOldWholesale() { return $this->kopeeksToRoubles($this->priceOldWholesale); }
    public function getPriceOldSmallWholesale() { return $this->kopeeksToRoubles($this->priceOldSmallWholesale); }
    public function getPriceOldLargeWholesale() { return $this->kopeeksToRoubles($this->priceOldLargeWholesale); }
    public function getPriceOldPurchase() { return $this->kopeeksToRoubles($this->priceOldPurchase); }
    public function getPriceSale() { return $this->kopeeksToRoubles($this->priceSale); }
    public function getPricePromo() { return $this->kopeeksToRoubles($this->pricePromo); }
    public function getPriceSmmAlliance() { return $this->kopeeksToRoubles($this->priceSmmAlliance); }
    public function getPriceSmmUllo() { return $this->kopeeksToRoubles($this->priceSmmUllo); }
    public function getPrice10kidsGoods() { return $this->kopeeksToRoubles($this->price10kidsGoods); }
    public function getPricePromo10kidsGoods() { return $this->kopeeksToRoubles($this->pricePromo10kidsGoods); }
    public function getPriceBeru() { return $this->kopeeksToRoubles($this->priceBeru); }
    public function getPriceBeruUllo() { return $this->kopeeksToRoubles($this->priceBeruUllo); }
    public function getPriceOzon() { return $this->kopeeksToRoubles($this->priceOzon); }
    public function getPriceOzonKaori() { return $this->kopeeksToRoubles($this->priceOzonKaori); }
    public function getPriceSportmaster() { return $this->kopeeksToRoubles($this->priceSportmaster); }
    public function getPriceWB() { return $this->kopeeksToRoubles($this->priceWB); }
    public function getPriceWBUllo() { return $this->kopeeksToRoubles($this->priceWBUllo); }
    public function getPriceDBS10kids() { return $this->kopeeksToRoubles($this->priceDBS10kids); }
    public function getAttrName() { return $this->attrName; }
    public function getAttrCategory1() { return $this->attrCategory1; }
    public function getAttrCategory2() { return $this->attrCategory2; }
    public function getAttrCategory3() { return $this->attrCategory3; }
    public function getAttrBrand() { return $this->attrBrand; }
    public function getAttrLength() { return $this->attrLength; }
    public function getAttrWidth() { return $this->attrWidth; }
    public function getAttrHeight() { return $this->attrHeight; }
    public function getAttrSize() { return $this->attrSize; }
    public function getAttrHalal() { return $this->attrHalal; }
    public function getBarcodes() { return $this->barcodes; }
    public function getSupplier() { return $this->supplier; }
    public function getAttributes() { return $this->attributes; }
    public function getPaymentItemType() { return $this->paymentItemType; }
    public function getDiscountProhibited() { return $this->discountProhibited; }
    public function getCountry() { return $this->country; }
    public function getArticle() { return $this->article; }
    public function getWeight() { return $this->weight; }
    public function getVolume() { return $this->volume; }
    public function getMinimumBalance() { return $this->minimumBalance; }
    public function getVariantsCount() { return $this->variantsCount; }
    public function getIsSerialTrackable() { return $this->isSerialTrackable; }
    public function getFiles() { return $this->files; }
    public function getTrackingType() { return $this->trackingType; }
    public function getStock() { return $this->stock; }
    public function getReserve() { return $this->reserve; }
    public function getInTransit() { return $this->inTransit; }
    public function getQuantity() { return $this->quantity; }
    public function getOriginalJson() { return $this->originalJson; }

    public function __construct($json = null) {
        if ($json) {
            if (is_string($json)) {
                $data = json_decode($json, true);
            } else {
                $data = $json;
            }
            $this->meta = $data['meta'] ?? null;
            $this->id = $data['id'] ?? null;
            $this->accountId = $data['accountId'] ?? null;
            $this->owner = $data['owner'] ?? null;
            $this->shared = $data['shared'] ?? null;
            $this->group = $data['group'] ?? null;
            $this->updated = $data['updated'] ?? null;
            $this->name = $data['name'] ?? null;
            $this->description = $data['description'] ?? null;
            $this->code = $data['code'] ?? null;
            $this->externalCode = $data['externalCode'] ?? null;
            $this->archived = $data['archived'] ?? null;
            $this->pathName = $data['pathName'] ?? null;
            $this->productFolder = $data['productFolder'] ?? null;
            $this->effectiveVat = $data['effectiveVat'] ?? null;
            $this->effectiveVatEnabled = $data['effectiveVatEnabled'] ?? null;
            $this->vat = $data['vat'] ?? null;
            $this->vatEnabled = $data['vatEnabled'] ?? null;
            $this->useParentVat = $data['useParentVat'] ?? null;
            $this->taxSystem = $data['taxSystem'] ?? null;
            $this->uom = $data['uom'] ?? null;
            $this->images = $data['images'] ?? null;
            $this->minPrice = $data['minPrice'] ?? null;
            $this->salePrices = $data['salePrices'] ?? null;
            $this->buyPrice = $data['buyPrice'] ?? null;
            $this->barcodes = $data['barcodes'] ?? null;
            $this->supplier = $data['supplier'] ?? null;
            $this->attributes = $data['attributes'] ?? null;
            $this->paymentItemType = $data['paymentItemType'] ?? null;
            $this->discountProhibited = $data['discountProhibited'] ?? null;
            $this->country = $data['country'] ?? null;
            $this->article = $data['article'] ?? null;
            $this->weight = $data['weight'] ?? null;
            $this->volume = $data['volume'] ?? null;
            $this->minimumBalance = $data['minimumBalance'] ?? null;
            $this->variantsCount = $data['variantsCount'] ?? null;
            $this->isSerialTrackable = $data['isSerialTrackable'] ?? null;
            $this->files = $data['files'] ?? null;
            $this->trackingType = $data['trackingType'] ?? null;
            $this->stock = $data['stock'] ?? null;
            $this->reserve = $data['reserve'] ?? null;
            $this->inTransit = $data['inTransit'] ?? null;
            $this->quantity = $data['quantity'] ?? null;
            // Map salePrices array to horizontal price properties
            if (isset($data['salePrices']) && is_array($data['salePrices'])) {
                foreach ($data['salePrices'] as $price) {
                    if (isset($price['priceType']['name'])) {
                        $priceName = $price['priceType']['name'];
                        $value = $price['value'] ?? null;
                        switch ($priceName) {
                            case 'Цена продажи':
                                $this->priceSale = $value;
                                break;
                            case 'Цена по акции':
                                $this->pricePromo = $value;
                                break;
                            case 'Цена СММ Альянс':
                                $this->priceSmmAlliance = $value;
                                break;
                            case 'Цена СММ для Юлло':
                                $this->priceSmmUllo = $value;
                                break;
                            case 'Цена 10kids/GOODS':
                                $this->price10kidsGoods = $value;
                                break;
                            case 'Цена по акции для 10kids/GOODS':
                                $this->pricePromo10kidsGoods = $value;
                                break;
                            case 'Цена Беру.ру':
                                $this->priceBeru = $value;
                                break;
                            case 'Цена Беру ullo':
                                $this->priceBeruUllo = $value;
                                break;
                            case 'Цена Ozon':
                                $this->priceOzon = $value;
                                break;
                            case 'Цена Ozon Каори !':
                                $this->priceOzonKaori = $value;
                                break;
                            case 'Цена СпортМастер':
                                $this->priceSportmaster = $value;
                                break;
                            case 'Цена WB':
                                $this->priceWB = $value;
                                break;
                            case 'Цена WB ULLO':
                                $this->priceWBUllo = $value;
                                break;
                            case 'Цена DBS - I0kids':
                                $this->priceDBS10kids = $value;
                                break;
                        }
                    }
                }
            }
            // Map attributes array to horizontal attribute properties using real names
            if (isset($data['attributes']) && is_array($data['attributes'])) {
                foreach ($data['attributes'] as $attr) {
                    if (isset($attr['name'])) {
                        $name = $attr['name'];
                        $value = $attr['value'] ?? null;
                        switch ($name) {
                            case 'CCD77 Наименование':
                                $this->attrName = $value;
                                break;
                            case 'CCD77 Категория 1':
                                $this->attrCategory1 = is_array($value) && isset($value['name']) ? $value['name'] : $value;
                                break;
                            case 'CCD77 Категория 2':
                                $this->attrCategory2 = is_array($value) && isset($value['name']) ? $value['name'] : $value;
                                break;
                            case 'CCD77 Категория 3':
                                $this->attrCategory3 = is_array($value) && isset($value['name']) ? $value['name'] : $value;
                                break;
                            case 'CCD77 Бренд':
                                $this->attrBrand = is_array($value) && isset($value['name']) ? $value['name'] : $value;
                                break;
                            case 'Весогабариты, длина':
                                $this->attrLength = $value;
                                break;
                            case 'Весогабариты, ширина':
                                $this->attrWidth = $value;
                                break;
                            case 'Весогабариты, высота':
                                $this->attrHeight = $value;
                                break;
                            case 'CCD77 Размер':
                                $this->attrSize = $value;
                                break;
                            case 'CCD77 Halal':
                                $this->attrHalal = $value;
                                break;
                        }
                    }
                }
            }
            $this->originalJson = $data;
        } else {
            $this->meta = null;
            $this->id = null;
            $this->accountId = null;
            $this->owner = null;
            $this->shared = null;
            $this->group = null;
            $this->updated = null;
            $this->name = null;
            $this->description = null;
            $this->code = null;
            $this->externalCode = null;
            $this->archived = null;
            $this->pathName = null;
            $this->productFolder = null;
            $this->effectiveVat = null;
            $this->effectiveVatEnabled = null;
            $this->vat = null;
            $this->vatEnabled = null;
            $this->useParentVat = null;
            $this->taxSystem = null;
            $this->uom = null;
            $this->images = null;
            $this->minPrice = null;
            $this->salePrices = null;
            $this->buyPrice = null;
            $this->barcodes = null;
            $this->supplier = null;
            $this->attributes = null;
            $this->paymentItemType = null;
            $this->discountProhibited = null;
            $this->country = null;
            $this->article = null;
            $this->weight = null;
            $this->volume = null;
            $this->minimumBalance = null;
            $this->variantsCount = null;
            $this->isSerialTrackable = null;
            $this->files = null;
            $this->trackingType = null;
            $this->stock = null;
            $this->reserve = null;
            $this->inTransit = null;
            $this->quantity = null;
            // Horizontal price properties
            $this->priceRetail = null;
            $this->priceWholesale = null;
            $this->priceSmallWholesale = null;
            $this->priceLargeWholesale = null;
            $this->pricePurchase = null;
            $this->priceOldRetail = null;
            $this->priceOldWholesale = null;
            $this->priceOldSmallWholesale = null;
            $this->priceOldLargeWholesale = null;
            $this->priceOldPurchase = null;
            // Horizontal attribute properties (aligned to constructor mapping)
            $this->attrName = null;
            $this->attrCategory1 = null;
            $this->attrCategory2 = null;
            $this->attrCategory3 = null;
            $this->attrBrand = null;
            $this->attrLength = null;
            $this->attrWidth = null;
            $this->attrHeight = null;
            $this->attrSize = null;
            $this->attrHalal = null;
            $this->originalJson = null;
        }
    }

    public function jsonSerialize() {
        return [
            'meta' => $this->meta,
            'id' => $this->id,
            'accountId' => $this->accountId,
            'owner' => $this->owner,
            'shared' => $this->shared,
            'group' => $this->group,
            'updated' => $this->updated,
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'externalCode' => $this->externalCode,
            'archived' => $this->archived,
            'pathName' => $this->pathName,
            'productFolder' => $this->productFolder,
            'effectiveVat' => $this->effectiveVat,
            'effectiveVatEnabled' => $this->effectiveVatEnabled,
            'vat' => $this->vat,
            'vatEnabled' => $this->vatEnabled,
            'useParentVat' => $this->useParentVat,
            'taxSystem' => $this->taxSystem,
            'uom' => $this->uom,
            'images' => $this->images,
            'minPrice' => $this->minPrice,
            'salePrices' => $this->salePrices,
            'buyPrice' => $this->buyPrice,
            'barcodes' => $this->barcodes,
            'supplier' => $this->supplier,
            'attributes' => $this->attributes,
            'paymentItemType' => $this->paymentItemType,
            'discountProhibited' => $this->discountProhibited,
            'country' => $this->country,
            'article' => $this->article,
            'weight' => $this->weight,
            'volume' => $this->volume,
            'minimumBalance' => $this->minimumBalance,
            'variantsCount' => $this->variantsCount,
            'isSerialTrackable' => $this->isSerialTrackable,
            'files' => $this->files,
            'trackingType' => $this->trackingType,
            'stock' => $this->stock,
            'reserve' => $this->reserve,
            'inTransit' => $this->inTransit,
            'quantity' => $this->quantity,
            'originalJson' => $this->originalJson
        ];
    }
}
