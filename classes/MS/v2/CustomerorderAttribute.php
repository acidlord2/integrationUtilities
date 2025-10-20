<?php
namespace MS\v2;

class CustomerorderAttribute implements \JsonSerializable {
    private $meta;
    private $id;
    private $name;
    private $type;
    private $value;
    private $download;
    private $originalJson;

    // Getters
    public function getMeta() { return $this->meta; }
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getType() { return $this->type; }
    public function getValue() { 
        // Return properly typed value based on attribute type
        switch ($this->type) {
            case 'customentity':
                return is_array($this->value) ? $this->value : null;
                
            case 'string':
                return is_string($this->value) ? $this->value : null;
                
            case 'long':
            case 'double':
                return is_numeric($this->value) ? $this->value : null;
                
            case 'boolean':
                return is_bool($this->value) ? $this->value : null;
                
            case 'file':
                return  [isset($this->value) ? $this->value : null, isset($this->download['href']) ? $this->getDownloadHref() : null]; // Return file name and download URL
                
            default:
                return isset($this->value) ? $this->value : null;
        }
    }

    public function getOriginalJson() { return $this->originalJson; }

    // Setters
    public function setMeta($meta) { $this->meta = $meta; }
    public function setId($id) { $this->id = $id; }
    public function setName($name) { $this->name = $name; }
    public function setType($type) { $this->type = $type; }
    public function setValue($value) { $this->value = $value; }
    public function setDownload($download) { $this->download = $download; }

    // Helper methods for specific value extraction (kept for backward compatibility)
    public function getCustomEntityName() {
        if (is_array($this->value) && isset($this->value['name'])) {
            return $this->value['name'];
        }
        return null;
    }

    public function getCustomEntityId() {
        if (is_array($this->value) && isset($this->value['meta']['href'])) {
            $href = $this->value['meta']['href'];
            $parts = explode('/', $href);
            return end($parts);
        }
        return null;
    }

    public function getCustomEntityObject() {
        if (is_array($this->value) && isset($this->value['meta'])) {
            return $this->value;
        }
        return null;
    }

    public function isCustomEntity() {
        return $this->type === 'customentity';
    }

    public function isString() {
        return $this->type === 'string';
    }

    public function isLong() {
        return $this->type === 'long';
    }

    public function isDouble() {
        return $this->type === 'double';
    }

    public function isBoolean() {
        return $this->type === 'boolean';
    }

    public function isFile() {
        return $this->type === 'file';
    }

    // File-specific helper methods
    public function getFileName() {
        return $this->isFile() ? $this->value : null;
    }

    public function getDownloadHref() {
        return isset($this->download['href']) ? $this->download['href'] : null;
    }

    public function hasDownload() {
        return $this->isFile() && !empty($this->download);
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
            $this->name = $data['name'] ?? null;
            $this->type = $data['type'] ?? null;
            $this->value = $data['value'] ?? null;
            $this->download = $data['download'] ?? null;
            
            $this->originalJson = $json;
        } else {
            // Initialize empty properties
            $this->meta = null;
            $this->id = null;
            $this->name = null;
            $this->type = null;
            $this->value = null;
            $this->download = null;
 
            $this->originalJson = null;
        }
    }

    public function jsonSerialize() {
        return $this->jsonSerializeForRead();
    }
    
    public function jsonSerializeForInsert() {
        // For insert operations - include meta and value as they are
        $data = [];
        
        // Meta is required for attributes (points to attribute definition)
        if ($this->meta) {
            $data['meta'] = $this->meta;
        }
        
        // Value is required and may have its own meta for custom entities
        if (isset($this->value)) {
            $data['value'] = $this->value;
        }
        
        // Add download info for file attributes
        if ($this->isFile() && $this->hasDownload()) {
            $data['download'] = $this->download;
        }
        
        return $data;
    }
    
    public function jsonSerializeForUpdate() {
        // For update operations - include meta and value as they are
        $data = [];
        
        // Meta is required for attributes (points to attribute definition)
        if ($this->meta) {
            $data['meta'] = $this->meta;
        }
        
        // Value is required and may have its own meta for custom entities
        if (isset($this->value)) {
            $data['value'] = $this->value;
        }
        
        // Add download info for file attributes
        if ($this->isFile() && $this->hasDownload()) {
            $data['download'] = $this->download;
        }
        
        return $data;
    }
    
    public function jsonSerializeForRead() {
        // For read operations - include all fields as received from API
        return [
            'meta' => $this->meta,
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
            'download' => $this->download,
            'originalJson' => $this->originalJson
        ];
    }
}