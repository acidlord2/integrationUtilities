<?php

namespace MS\v2;

/**
 * Product class for MoySklad API v1.2
 * Represents a product (товар) entity in MoySklad
 * Based on MoySklad API documentation for товар entity
 */
class Product
{
    // Core identification fields
    private ?string $accountId = null;
    private ?string $id = null;
    private ?string $name = null;
    private ?string $code = null;
    private ?string $externalCode = null;
    private ?string $article = null;
    private ?string $pathName = null;
    
    // Status and metadata fields
    private ?bool $archived = null;
    private ?bool $shared = null;
    private ?object $meta = null;
    private ?object $owner = null;
    private ?object $group = null;
    private ?\DateTime $updated = null;
    
    // Product description and categorization
    private ?string $description = null;
    private ?object $productFolder = null;
    private ?object $country = null;
    private ?object $supplier = null;
    
    // Pricing and financial fields
    private ?object $buyPrice = null;
    private ?array $salePrices = null;
    private ?object $minPrice = null;
    private ?bool $discountProhibited = null;
    
    // Tax and accounting fields
    private ?int $vat = null;
    private ?bool $vatEnabled = null;
    private ?bool $useParentVat = null;
    private ?int $effectiveVat = null;
    private ?string $taxSystem = null;
    private ?string $paymentItemType = null;
    
    // Physical characteristics
    private ?float $weight = null;
    private ?float $volume = null;
    private ?object $uom = null;
    
    // Inventory management
    private ?float $minimumStock = null;
    private ?bool $partialDisposal = null;
    private ?string $trackingType = null;
    
    // Collections and related data
    private ?array $attributes = null;
    private ?array $barcodes = null;
    private ?array $images = null;
    private ?array $files = null;
    private ?array $packs = null;
    private ?array $things = null;
    
    // Other fields
    private ?string $tnved = null;
    private ?int $variantsCount = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize with default values
    }

    /**
     * Get account ID
     * @return string|null
     */
    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    /**
     * Set account ID
     * @param string|null $accountId
     * @return self
     */
    public function setAccountId(?string $accountId): self
    {
        $this->accountId = $accountId;
        return $this;
    }

    /**
     * Get product ID
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set product ID
     * @param string|null $id
     * @return self
     */
    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get product name
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set product name
     * @param string|null $name
     * @return self
     */
    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get product code
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Set product code
     * @param string|null $code
     * @return self
     */
    public function setCode(?string $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Get external code
     * @return string|null
     */
    public function getExternalCode(): ?string
    {
        return $this->externalCode;
    }

    /**
     * Set external code
     * @param string|null $externalCode
     * @return self
     */
    public function setExternalCode(?string $externalCode): self
    {
        $this->externalCode = $externalCode;
        return $this;
    }

    /**
     * Get article number
     * @return string|null
     */
    public function getArticle(): ?string
    {
        return $this->article;
    }

    /**
     * Set article number
     * @param string|null $article
     * @return self
     */
    public function setArticle(?string $article): self
    {
        $this->article = $article;
        return $this;
    }

    /**
     * Get path name
     * @return string|null
     */
    public function getPathName(): ?string
    {
        return $this->pathName;
    }

    /**
     * Set path name
     * @param string|null $pathName
     * @return self
     */
    public function setPathName(?string $pathName): self
    {
        $this->pathName = $pathName;
        return $this;
    }

    /**
     * Get archived status
     * @return bool|null
     */
    public function getArchived(): ?bool
    {
        return $this->archived;
    }

    /**
     * Set archived status
     * @param bool|null $archived
     * @return self
     */
    public function setArchived(?bool $archived): self
    {
        $this->archived = $archived;
        return $this;
    }

    /**
     * Get shared status
     * @return bool|null
     */
    public function getShared(): ?bool
    {
        return $this->shared;
    }

    /**
     * Set shared status
     * @param bool|null $shared
     * @return self
     */
    public function setShared(?bool $shared): self
    {
        $this->shared = $shared;
        return $this;
    }

    /**
     * Get meta information
     * @return object|null
     */
    public function getMeta(): ?object
    {
        return $this->meta;
    }

    /**
     * Set meta information
     * @param object|null $meta
     * @return self
     */
    public function setMeta(?object $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    /**
     * Get owner
     * @return object|null
     */
    public function getOwner(): ?object
    {
        return $this->owner;
    }

    /**
     * Set owner
     * @param object|null $owner
     * @return self
     */
    public function setOwner(?object $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * Get group
     * @return object|null
     */
    public function getGroup(): ?object
    {
        return $this->group;
    }

    /**
     * Set group
     * @param object|null $group
     * @return self
     */
    public function setGroup(?object $group): self
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Get updated timestamp
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * Set updated timestamp
     * @param \DateTime|null $updated
     * @return self
     */
    public function setUpdated(?\DateTime $updated): self
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * Get description
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set description
     * @param string|null $description
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get product folder
     * @return object|null
     */
    public function getProductFolder(): ?object
    {
        return $this->productFolder;
    }

    /**
     * Set product folder
     * @param object|null $productFolder
     * @return self
     */
    public function setProductFolder(?object $productFolder): self
    {
        $this->productFolder = $productFolder;
        return $this;
    }

    /**
     * Get country
     * @return object|null
     */
    public function getCountry(): ?object
    {
        return $this->country;
    }

    /**
     * Set country
     * @param object|null $country
     * @return self
     */
    public function setCountry(?object $country): self
    {
        $this->country = $country;
        return $this;
    }

    /**
     * Get supplier
     * @return object|null
     */
    public function getSupplier(): ?object
    {
        return $this->supplier;
    }

    /**
     * Set supplier
     * @param object|null $supplier
     * @return self
     */
    public function setSupplier(?object $supplier): self
    {
        $this->supplier = $supplier;
        return $this;
    }

    /**
     * Get buy price
     * @return object|null
     */
    public function getBuyPrice(): ?object
    {
        return $this->buyPrice;
    }

    /**
     * Set buy price
     * @param object|null $buyPrice
     * @return self
     */
    public function setBuyPrice(?object $buyPrice): self
    {
        $this->buyPrice = $buyPrice;
        return $this;
    }

    /**
     * Get sale prices
     * @return array|null
     */
    public function getSalePrices(): ?array
    {
        return $this->salePrices;
    }

    /**
     * Set sale prices
     * @param array|null $salePrices
     * @return self
     */
    public function setSalePrices(?array $salePrices): self
    {
        $this->salePrices = $salePrices;
        return $this;
    }

    /**
     * Get minimum price
     * @return object|null
     */
    public function getMinPrice(): ?object
    {
        return $this->minPrice;
    }

    /**
     * Set minimum price
     * @param object|null $minPrice
     * @return self
     */
    public function setMinPrice(?object $minPrice): self
    {
        $this->minPrice = $minPrice;
        return $this;
    }

    /**
     * Get discount prohibited flag
     * @return bool|null
     */
    public function getDiscountProhibited(): ?bool
    {
        return $this->discountProhibited;
    }

    /**
     * Set discount prohibited flag
     * @param bool|null $discountProhibited
     * @return self
     */
    public function setDiscountProhibited(?bool $discountProhibited): self
    {
        $this->discountProhibited = $discountProhibited;
        return $this;
    }

    /**
     * Get VAT rate
     * @return int|null
     */
    public function getVat(): ?int
    {
        return $this->vat;
    }

    /**
     * Set VAT rate
     * @param int|null $vat
     * @return self
     */
    public function setVat(?int $vat): self
    {
        $this->vat = $vat;
        return $this;
    }

    /**
     * Get VAT enabled flag
     * @return bool|null
     */
    public function getVatEnabled(): ?bool
    {
        return $this->vatEnabled;
    }

    /**
     * Set VAT enabled flag
     * @param bool|null $vatEnabled
     * @return self
     */
    public function setVatEnabled(?bool $vatEnabled): self
    {
        $this->vatEnabled = $vatEnabled;
        return $this;
    }

    /**
     * Get use parent VAT flag
     * @return bool|null
     */
    public function getUseParentVat(): ?bool
    {
        return $this->useParentVat;
    }

    /**
     * Set use parent VAT flag
     * @param bool|null $useParentVat
     * @return self
     */
    public function setUseParentVat(?bool $useParentVat): self
    {
        $this->useParentVat = $useParentVat;
        return $this;
    }

    /**
     * Get effective VAT
     * @return int|null
     */
    public function getEffectiveVat(): ?int
    {
        return $this->effectiveVat;
    }

    /**
     * Set effective VAT
     * @param int|null $effectiveVat
     * @return self
     */
    public function setEffectiveVat(?int $effectiveVat): self
    {
        $this->effectiveVat = $effectiveVat;
        return $this;
    }

    /**
     * Get tax system
     * @return string|null
     */
    public function getTaxSystem(): ?string
    {
        return $this->taxSystem;
    }

    /**
     * Set tax system
     * @param string|null $taxSystem
     * @return self
     */
    public function setTaxSystem(?string $taxSystem): self
    {
        $this->taxSystem = $taxSystem;
        return $this;
    }

    /**
     * Get payment item type
     * @return string|null
     */
    public function getPaymentItemType(): ?string
    {
        return $this->paymentItemType;
    }

    /**
     * Set payment item type
     * @param string|null $paymentItemType
     * @return self
     */
    public function setPaymentItemType(?string $paymentItemType): self
    {
        $this->paymentItemType = $paymentItemType;
        return $this;
    }

    /**
     * Get weight
     * @return float|null
     */
    public function getWeight(): ?float
    {
        return $this->weight;
    }

    /**
     * Set weight
     * @param float|null $weight
     * @return self
     */
    public function setWeight(?float $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * Get volume
     * @return float|null
     */
    public function getVolume(): ?float
    {
        return $this->volume;
    }

    /**
     * Set volume
     * @param float|null $volume
     * @return self
     */
    public function setVolume(?float $volume): self
    {
        $this->volume = $volume;
        return $this;
    }

    /**
     * Get unit of measure
     * @return object|null
     */
    public function getUom(): ?object
    {
        return $this->uom;
    }

    /**
     * Set unit of measure
     * @param object|null $uom
     * @return self
     */
    public function setUom(?object $uom): self
    {
        $this->uom = $uom;
        return $this;
    }

    /**
     * Get minimum stock
     * @return float|null
     */
    public function getMinimumStock(): ?float
    {
        return $this->minimumStock;
    }

    /**
     * Set minimum stock
     * @param float|null $minimumStock
     * @return self
     */
    public function setMinimumStock(?float $minimumStock): self
    {
        $this->minimumStock = $minimumStock;
        return $this;
    }

    /**
     * Get partial disposal flag
     * @return bool|null
     */
    public function getPartialDisposal(): ?bool
    {
        return $this->partialDisposal;
    }

    /**
     * Set partial disposal flag
     * @param bool|null $partialDisposal
     * @return self
     */
    public function setPartialDisposal(?bool $partialDisposal): self
    {
        $this->partialDisposal = $partialDisposal;
        return $this;
    }

    /**
     * Get tracking type
     * @return string|null
     */
    public function getTrackingType(): ?string
    {
        return $this->trackingType;
    }

    /**
     * Set tracking type
     * @param string|null $trackingType
     * @return self
     */
    public function setTrackingType(?string $trackingType): self
    {
        $this->trackingType = $trackingType;
        return $this;
    }

    /**
     * Get attributes
     * @return array|null
     */
    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    /**
     * Set attributes
     * @param array|null $attributes
     * @return self
     */
    public function setAttributes(?array $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Get barcodes
     * @return array|null
     */
    public function getBarcodes(): ?array
    {
        return $this->barcodes;
    }

    /**
     * Set barcodes
     * @param array|null $barcodes
     * @return self
     */
    public function setBarcodes(?array $barcodes): self
    {
        $this->barcodes = $barcodes;
        return $this;
    }

    /**
     * Get images
     * @return array|null
     */
    public function getImages(): ?array
    {
        return $this->images;
    }

    /**
     * Set images
     * @param array|null $images
     * @return self
     */
    public function setImages(?array $images): self
    {
        $this->images = $images;
        return $this;
    }

    /**
     * Get files
     * @return array|null
     */
    public function getFiles(): ?array
    {
        return $this->files;
    }

    /**
     * Set files
     * @param array|null $files
     * @return self
     */
    public function setFiles(?array $files): self
    {
        $this->files = $files;
        return $this;
    }

    /**
     * Get packs
     * @return array|null
     */
    public function getPacks(): ?array
    {
        return $this->packs;
    }

    /**
     * Set packs
     * @param array|null $packs
     * @return self
     */
    public function setPacks(?array $packs): self
    {
        $this->packs = $packs;
        return $this;
    }

    /**
     * Get things (serial numbers)
     * @return array|null
     */
    public function getThings(): ?array
    {
        return $this->things;
    }

    /**
     * Set things (serial numbers)
     * @param array|null $things
     * @return self
     */
    public function setThings(?array $things): self
    {
        $this->things = $things;
        return $this;
    }

    /**
     * Get TNVED code
     * @return string|null
     */
    public function getTnved(): ?string
    {
        return $this->tnved;
    }

    /**
     * Set TNVED code
     * @param string|null $tnved
     * @return self
     */
    public function setTnved(?string $tnved): self
    {
        $this->tnved = $tnved;
        return $this;
    }

    /**
     * Get variants count
     * @return int|null
     */
    public function getVariantsCount(): ?int
    {
        return $this->variantsCount;
    }

    /**
     * Set variants count
     * @param int|null $variantsCount
     * @return self
     */
    public function setVariantsCount(?int $variantsCount): self
    {
        $this->variantsCount = $variantsCount;
        return $this;
    }

    /**
     * Create product from JSON data
     * @param array $data
     * @return self
     */
    public static function fromJson(array $data): self
    {
        $product = new self();
        
        // Map all fields from JSON data
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($product, $method)) {
                if ($key === 'updated' && $value !== null) {
                    $product->$method(new \DateTime($value));
                } elseif (in_array($key, ['meta', 'owner', 'group', 'productFolder', 'country', 'supplier', 'buyPrice', 'minPrice', 'uom']) && $value !== null) {
                    $product->$method((object)$value);
                } else {
                    $product->$method($value);
                }
            }
        }
        
        return $product;
    }

    /**
     * JSON serialize for API insert operations
     * @return array
     */
    public function jsonSerializeForInsert(): array
    {
        $data = [];
        
        // Required field for insert
        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        
        // Optional fields commonly used in insert
        if ($this->code !== null) {
            $data['code'] = $this->code;
        }
        
        if ($this->externalCode !== null) {
            $data['externalCode'] = $this->externalCode;
        }
        
        if ($this->article !== null) {
            $data['article'] = $this->article;
        }
        
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        
        if ($this->productFolder !== null) {
            $data['productFolder'] = $this->productFolder;
        }
        
        if ($this->archived !== null) {
            $data['archived'] = $this->archived;
        }
        
        if ($this->shared !== null) {
            $data['shared'] = $this->shared;
        }
        
        if ($this->vat !== null) {
            $data['vat'] = $this->vat;
        }
        
        if ($this->vatEnabled !== null) {
            $data['vatEnabled'] = $this->vatEnabled;
        }
        
        if ($this->useParentVat !== null) {
            $data['useParentVat'] = $this->useParentVat;
        }
        
        if ($this->weight !== null) {
            $data['weight'] = $this->weight;
        }
        
        if ($this->volume !== null) {
            $data['volume'] = $this->volume;
        }
        
        if ($this->uom !== null) {
            $data['uom'] = $this->uom;
        }
        
        if ($this->minimumStock !== null) {
            $data['minimumStock'] = $this->minimumStock;
        }
        
        if ($this->partialDisposal !== null) {
            $data['partialDisposal'] = $this->partialDisposal;
        }
        
        if ($this->trackingType !== null) {
            $data['trackingType'] = $this->trackingType;
        }
        
        if ($this->buyPrice !== null) {
            $data['buyPrice'] = $this->buyPrice;
        }
        
        if ($this->salePrices !== null) {
            $data['salePrices'] = $this->salePrices;
        }
        
        if ($this->minPrice !== null) {
            $data['minPrice'] = $this->minPrice;
        }
        
        if ($this->discountProhibited !== null) {
            $data['discountProhibited'] = $this->discountProhibited;
        }
        
        if ($this->country !== null) {
            $data['country'] = $this->country;
        }
        
        if ($this->supplier !== null) {
            $data['supplier'] = $this->supplier;
        }
        
        if ($this->attributes !== null) {
            $data['attributes'] = $this->attributes;
        }
        
        if ($this->barcodes !== null) {
            $data['barcodes'] = $this->barcodes;
        }
        
        if ($this->packs !== null) {
            $data['packs'] = $this->packs;
        }
        
        if ($this->tnved !== null) {
            $data['tnved'] = $this->tnved;
        }
        
        return $data;
    }

    /**
     * JSON serialize for API update operations
     * @return array
     */
    public function jsonSerializeForUpdate(): array
    {
        $data = [];
        
        // Include ID for update
        if ($this->id !== null) {
            $data['id'] = $this->id;
        }
        
        // Include all modifiable fields from insert
        $insertData = $this->jsonSerializeForInsert();
        $data = array_merge($data, $insertData);
        
        return $data;
    }

    /**
     * JSON serialize for API read operations (full data)
     * @return array
     */
    public function jsonSerializeForRead(): array
    {
        $data = [];
        
        // Include all fields
        $properties = get_object_vars($this);
        foreach ($properties as $key => $value) {
            if ($value !== null) {
                if ($key === 'updated' && $value instanceof \DateTime) {
                    $data[$key] = $value->format('Y-m-d H:i:s.v');
                } else {
                    $data[$key] = $value;
                }
            }
        }
        
        return $data;
    }

    /**
     * Convert to array
     * @return array
     */
    public function toArray(): array
    {
        return $this->jsonSerializeForRead();
    }
}