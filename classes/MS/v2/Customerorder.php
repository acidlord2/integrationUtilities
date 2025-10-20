<?php
namespace MS\v2;

use MS\v2\CustomerOrderPosition;
require_once(__DIR__ . '/CustomerorderAttribute.php');
require_once(__DIR__ . '/CustomerOrderPosition.php');

class Customerorder implements \JsonSerializable {
    private $meta;
    private $id;
    private $accountId;
    private $owner;
    private $shared;
    private $group;
    private $updated;
    private $name;
    private $externalCode;
    private $moment;
    private $applicable;
    private $rate;
    private $sum;
    private $store;
    private $project;
    private $agent;
    private $organization;
    private $organizationAccount;
    private $state;
    private $attributes; // Array of CustomerorderAttribute objects
    private $created;
    private $printed;
    private $published;
    private $files;
    private $positions;
    private $vatEnabled;
    private $deliveryPlannedMoment;
    private $payedSum;
    private $shippedSum;
    private $invoicedSum;
    private $reservedSum;
        
    private $originalJson;

    // Getters
    public function getMeta() { return $this->meta; }
    public function getId() { return $this->id; }
    public function getAccountId() { return $this->accountId; }
    public function getOwner() { return $this->owner; }
    public function getShared() { return $this->shared; }
    public function getGroup() { return $this->group; }
    public function getUpdated() { return $this->updated; }
    public function getName() { return $this->name; }
    public function getExternalCode() { return $this->externalCode; }
    public function getMoment() { return $this->moment; }
    public function getApplicable() { return $this->applicable; }
    public function getRate() { return $this->rate; }
    public function getSum() { return $this->sum; }
    public function getStore() { return $this->store; }
    public function getProject() { return $this->project; }
    public function getAgent() { return $this->agent; }
    public function getOrganization() { return $this->organization; }
    public function getOrganizationAccount() { return $this->organizationAccount; }
    public function getState() { return $this->state; }
    public function getAttributes() { return $this->attributes; }
    public function getCreated() { return $this->created; }
    public function getPrinted() { return $this->printed; }
    public function getPublished() { return $this->published; }
    public function getFiles() { return $this->files; }
    public function getPositions() { return $this->positions; }
    public function getVatEnabled() { return $this->vatEnabled; }
    public function getDeliveryPlannedMoment() { return $this->deliveryPlannedMoment; }
    public function getPayedSum() { return $this->payedSum; }
    public function getShippedSum() { return $this->shippedSum; }
    public function getInvoicedSum() { return $this->invoicedSum; }
    public function getReservedSum() { return $this->reservedSum; }
    
    public function getOriginalJson() { return $this->originalJson; }
    
    // Additional methods for working with attributes
    public function getAttributeByName($name) {
        foreach ($this->attributes as $attribute) {
            if ($attribute->getName() === $name) {
                return $attribute;
            }
        }
        return null;
    }
    
    public function getAttributeById($id) {
        foreach ($this->attributes as $attribute) {
            if ($attribute->getId() === $id) {
                return $attribute;
            }
        }
        return null;
    }
    
    public function getAttributesCount() {
        return count($this->attributes);
    }
    
    public function hasAttribute($name) {
        return $this->getAttributeByName($name) !== null;
    }
    
    public function addAttribute(CustomerorderAttribute $attribute) {
        $this->attributes[] = $attribute;
    }
    
    public function removeAttributeByName($name) {
        foreach ($this->attributes as $key => $attribute) {
            if ($attribute->getName() === $name) {
                unset($this->attributes[$key]);
                $this->attributes = array_values($this->attributes); // Re-index array
                return true;
            }
        }
        return false;
    }
    
    // Methods for working with positions
    public function getPositionsCount() {
        return is_array($this->positions) ? count($this->positions) : 0;
    }
    
    public function addPosition(CustomerOrderPosition $position) {
        if (!is_array($this->positions)) {
            $this->positions = [];
        }
        $this->positions[] = $position;
    }
    
    public function removePositionById($id) {
        if (!is_array($this->positions)) {
            return false;
        }
        
        foreach ($this->positions as $key => $position) {
            if ($position instanceof CustomerOrderPosition && $position->getId() === $id) {
                unset($this->positions[$key]);
                $this->positions = array_values($this->positions); // Re-index array
                return true;
            }
        }
        return false;
    }
    
    public function getPositionById($id) {
        if (!is_array($this->positions)) {
            return null;
        }
        
        foreach ($this->positions as $position) {
            if ($position instanceof CustomerOrderPosition && $position->getId() === $id) {
                return $position;
            }
        }
        return null;
    }
    
    public function clearPositions() {
        $this->positions = [];
    }
    
    public function setPositions(array $positions) {
        $this->positions = [];
        foreach ($positions as $position) {
            if ($position instanceof CustomerOrderPosition) {
                $this->positions[] = $position;
            }
        }
    }
    
    /**
     * Parse positions from JSON data into CustomerOrderPosition objects
     * @param array $positionsData
     * @return array
     */
    private function parsePositions($positionsData) {
        $positions = [];
        if (!is_array($positionsData)) {
            return $positions;
        }
        
        foreach ($positionsData as $positionData) {
            if (is_array($positionData)) {
                $positions[] = new CustomerOrderPosition($positionData);
            }
        }
        
        return $positions;
    }

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
            $this->externalCode = $data['externalCode'] ?? null;
            $this->moment = $data['moment'] ?? null;
            $this->applicable = $data['applicable'] ?? null;
            $this->rate = $data['rate'] ?? null;
            $this->sum = $data['sum'] ?? null;
            $this->store = $data['store'] ?? null;
            $this->project = $data['project'] ?? null;
            $this->agent = $data['agent'] ?? null;
            $this->organization = $data['organization'] ?? null;
            $this->organizationAccount = $data['organizationAccount'] ?? null;
            $this->state = $data['state'] ?? null;
            $this->attributes = $this->parseAttributes($data['attributes'] ?? []);
            $this->created = $data['created'] ?? null;
            $this->printed = $data['printed'] ?? null;
            $this->published = $data['published'] ?? null;
            $this->files = $data['files'] ?? null;
            $this->positions = $this->parsePositions($data['positions'] ?? []);
            $this->vatEnabled = $data['vatEnabled'] ?? null;
            $this->deliveryPlannedMoment = $data['deliveryPlannedMoment'] ?? null;
            $this->payedSum = $data['payedSum'] ?? null;
            $this->shippedSum = $data['shippedSum'] ?? null;
            $this->invoicedSum = $data['invoicedSum'] ?? null;
            $this->reservedSum = $data['reservedSum'] ?? null;
            
            $this->originalJson = $json;
        } else {
            // Initialize empty properties
            $this->meta = null;
            $this->id = null;
            $this->accountId = null;
            $this->owner = null;
            $this->shared = null;
            $this->group = null;
            $this->updated = null;
            $this->name = null;
            $this->externalCode = null;
            $this->moment = null;
            $this->applicable = null;
            $this->rate = null;
            $this->sum = null;
            $this->store = null;
            $this->project = null;
            $this->agent = null;
            $this->organization = null;
            $this->organizationAccount = null;
            $this->state = null;
            $this->attributes = [];
            $this->created = null;
            $this->printed = null;
            $this->published = null;
            $this->files = null;
            $this->positions = null;
            $this->vatEnabled = null;
            $this->deliveryPlannedMoment = null;
            $this->payedSum = null;
            $this->shippedSum = null;
            $this->invoicedSum = null;
            $this->reservedSum = null;
            
            $this->originalJson = null;
        }
    }
    
    private function parseAttributes($attributesData) {
        $attributes = [];
        if (!is_array($attributesData)) {
            return $attributes;
        }
        
        foreach ($attributesData as $attributeData) {
            $attributes[] = new CustomerorderAttribute($attributeData);
        }
        
        return $attributes;
    }
    
    public function jsonSerialize() {
        return $this->jsonSerializeForRead();
    }
    
    public function jsonSerializeForInsert() {
        // For insert operations - exclude system fields and IDs
        $data = [
            'name' => $this->name,
            'externalCode' => $this->externalCode,
            'moment' => $this->moment,
            'applicable' => $this->applicable,
            'vatEnabled' => $this->vatEnabled,
            'deliveryPlannedMoment' => $this->deliveryPlannedMoment,
            'organization' => $this->organization,
            'agent' => $this->agent,
            'state' => $this->state,
        ];
        
        // Add optional references if they exist
        if ($this->rate) $data['rate'] = $this->rate;
        if ($this->store) $data['store'] = $this->store;
        if ($this->project) $data['project'] = $this->project;
        if ($this->organizationAccount) $data['organizationAccount'] = $this->organizationAccount;
        
        // Add positions for insert
        if (!empty($this->positions) && is_array($this->positions)) {
            $data['positions'] = [];
            foreach ($this->positions as $position) {
                if ($position instanceof CustomerOrderPosition) {
                    $data['positions'][] = $position->jsonSerializeForInsert();
                }
            }
        }
        
        // Add attributes for insert (let attributes serialize themselves)
        if (!empty($this->attributes)) {
            $data['attributes'] = [];
            foreach ($this->attributes as $attribute) {
                if ($attribute instanceof CustomerorderAttribute) {
                    $data['attributes'][] = $attribute->jsonSerializeForInsert();
                }
            }
        }
        
        return $data;
    }
    
    public function jsonSerializeForUpdate() {
        // For update operations - include ID but exclude system-generated fields
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'externalCode' => $this->externalCode,
            'moment' => $this->moment,
            'applicable' => $this->applicable,
            'vatEnabled' => $this->vatEnabled,
            'deliveryPlannedMoment' => $this->deliveryPlannedMoment,
            'organization' => $this->organization,
            'agent' => $this->agent,
            'state' => $this->state,
        ];
        
        // Add optional references if they exist
        if ($this->rate) $data['rate'] = $this->rate;
        if ($this->store) $data['store'] = $this->store;
        if ($this->project) $data['project'] = $this->project;
        if ($this->organizationAccount) $data['organizationAccount'] = $this->organizationAccount;
        
        // Add positions for update
        if (!empty($this->positions) && is_array($this->positions)) {
            $data['positions'] = [];
            foreach ($this->positions as $position) {
                if ($position instanceof CustomerOrderPosition) {
                    $data['positions'][] = $position->jsonSerializeForUpdate();
                }
            }
        }
        
        // Add attributes for update (let attributes serialize themselves)
        if (!empty($this->attributes)) {
            $data['attributes'] = [];
            foreach ($this->attributes as $attribute) {
                if ($attribute instanceof CustomerorderAttribute) {
                    $data['attributes'][] = $attribute->jsonSerializeForUpdate();
                }
            }
        }
        
        return $data;
    }
    
    public function jsonSerializeForRead() {
        // For read operations - include all fields as received from API
        $data = [
            'meta' => $this->meta,
            'id' => $this->id,
            'accountId' => $this->accountId,
            'owner' => $this->owner,
            'shared' => $this->shared,
            'group' => $this->group,
            'updated' => $this->updated,
            'name' => $this->name,
            'externalCode' => $this->externalCode,
            'moment' => $this->moment,
            'applicable' => $this->applicable,
            'rate' => $this->rate,
            'sum' => $this->sum,
            'store' => $this->store,
            'project' => $this->project,
            'agent' => $this->agent,
            'organization' => $this->organization,
            'organizationAccount' => $this->organizationAccount,
            'state' => $this->state,
            'created' => $this->created,
            'printed' => $this->printed,
            'published' => $this->published,
            'files' => $this->files,
            'vatEnabled' => $this->vatEnabled,
            'deliveryPlannedMoment' => $this->deliveryPlannedMoment,
            'payedSum' => $this->payedSum,
            'shippedSum' => $this->shippedSum,
            'invoicedSum' => $this->invoicedSum,
            'reservedSum' => $this->reservedSum,
        ];
        
        // Serialize attributes for read
        if (!empty($this->attributes)) {
            $data['attributes'] = [];
            foreach ($this->attributes as $attribute) {
                if ($attribute instanceof CustomerorderAttribute) {
                    $data['attributes'][] = $attribute->jsonSerializeForRead();
                }
            }
        }
        
        // Serialize positions for read
        if (!empty($this->positions) && is_array($this->positions)) {
            $data['positions'] = [];
            foreach ($this->positions as $position) {
                if ($position instanceof CustomerOrderPosition) {
                    $data['positions'][] = $position->jsonSerializeForRead();
                }
            }
        }
        
        return $data;
    }
}
