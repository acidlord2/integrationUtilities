<?php
namespace MS\v2;

class CustomerorderIterator implements \IteratorAggregate 
{
    private $customerorders = [];

    /**
     * Get iterator for foreach loops
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator 
    {
        return new \ArrayIterator($this->customerorders);
    }

    /**
     * Constructor
     * @param string|array $customerorders JSON string or array of customer order objects
     */
    public function __construct($customerorders) 
    {

        require_once(__DIR__ . '/Customerorder.php');
        require_once(__DIR__ . '/CustomerorderAttribute.php');
        require_once(__DIR__ . '/CustomerOrderPosition.php');

        $items = [];
        if (is_string($customerorders)) {
            $decoded = json_decode($customerorders, true);
            if (is_array($decoded)) {
                // Handle API response format with 'rows' key
                if (isset($decoded['rows'])) {
                    $items = $decoded['rows'];
                } else {
                    $items = $decoded;
                }
            }
        } elseif (is_array($customerorders)) {
            // Handle API response format with 'rows' key
            if (isset($customerorders['rows'])) {
                $items = $customerorders['rows'];
            } else {
                $items = $customerorders;
            }
        }

        foreach ($items as $item) {
            // If item is already a Customerorder object, use it directly
            if ($item instanceof Customerorder) {
                $this->customerorders[] = $item;
            } else {
                // Otherwise, create new Customerorder from raw data
                $this->customerorders[] = new Customerorder($item);
            }
        }
    }

    /**
     * Get all customer orders as array
     * @return array
     */
    public function getCustomerorders(): array
    {
        return $this->customerorders;
    }

    /**
     * Get customer order by index
     * @param int $index
     * @return Customerorder|null
     */
    public function getCustomerorder(int $index): ?Customerorder
    {
        return $this->customerorders[$index] ?? null;
    }

    /**
     * Get customer order by ID
     * @param string $id
     * @return Customerorder|null
     */
    public function getCustomerorderById(string $id): ?Customerorder
    {
        foreach ($this->customerorders as $customerorder) {
            if ($customerorder->getId() === $id) {
                return $customerorder;
            }
        }
        return null;
    }

    /**
     * Get customer order by external code
     * @param string $externalCode
     * @return Customerorder|null
     */
    public function getCustomerorderByExternalCode(string $externalCode): ?Customerorder
    {
        foreach ($this->customerorders as $customerorder) {
            if ($customerorder->getExternalCode() === $externalCode) {
                return $customerorder;
            }
        }
        return null;
    }

    /**
     * Get customer order by name
     * @param string $name
     * @return Customerorder|null
     */
    public function getCustomerorderByName(string $name): ?Customerorder
    {
        foreach ($this->customerorders as $customerorder) {
            if ($customerorder->getName() === $name) {
                return $customerorder;
            }
        }
        return null;
    }

    /**
     * Get count of customer orders
     * @return int
     */
    public function count(): int
    {
        return count($this->customerorders);
    }

    /**
     * Check if iterator is empty
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->customerorders);
    }

    /**
     * Add a customer order to the iterator
     * @param Customerorder $customerorder
     * @return self
     */
    public function addCustomerorder(Customerorder $customerorder): self
    {
        $this->customerorders[] = $customerorder;
        return $this;
    }

    /**
     * Remove customer order by ID
     * @param string $id
     * @return bool True if removed, false if not found
     */
    public function removeCustomerorderById(string $id): bool
    {
        foreach ($this->customerorders as $key => $customerorder) {
            if ($customerorder->getId() === $id) {
                unset($this->customerorders[$key]);
                $this->customerorders = array_values($this->customerorders); // Re-index
                return true;
            }
        }
        return false;
    }

    /**
     * Filter customer orders by applicable status
     * @param bool $applicable
     * @return CustomerorderIterator
     */
    public function filterByApplicable(bool $applicable): CustomerorderIterator
    {
        $filtered = [];
        foreach ($this->customerorders as $customerorder) {
            if ($customerorder->getApplicable() === $applicable) {
                $filtered[] = $customerorder;
            }
        }
        return new CustomerorderIterator($filtered);
    }

    /**
     * Filter customer orders by state ID
     * @param string $stateId
     * @return CustomerorderIterator
     */
    public function filterByStateId(string $stateId): CustomerorderIterator
    {
        $filtered = [];
        foreach ($this->customerorders as $customerorder) {
            $state = $customerorder->getState();
            if (is_object($state) && isset($state->meta->href)) {
                $href = $state->meta->href;
                $parts = explode('/', $href);
                $currentStateId = end($parts);
                if ($currentStateId === $stateId) {
                    $filtered[] = $customerorder;
                }
            }
        }
        return new CustomerorderIterator($filtered);
    }

    /**
     * Filter customer orders by agent ID
     * @param string $agentId
     * @return CustomerorderIterator
     */
    public function filterByAgentId(string $agentId): CustomerorderIterator
    {
        $filtered = [];
        foreach ($this->customerorders as $customerorder) {
            $agent = $customerorder->getAgent();
            if (is_object($agent) && isset($agent->meta->href)) {
                $href = $agent->meta->href;
                $parts = explode('/', $href);
                $currentAgentId = end($parts);
                if ($currentAgentId === $agentId) {
                    $filtered[] = $customerorder;
                }
            }
        }
        return new CustomerorderIterator($filtered);
    }

    /**
     * Filter customer orders by date range
     * @param string $dateFrom Format: Y-m-d H:i:s
     * @param string $dateTo Format: Y-m-d H:i:s
     * @return CustomerorderIterator
     */
    public function filterByDateRange(string $dateFrom, string $dateTo): CustomerorderIterator
    {
        $filtered = [];
        foreach ($this->customerorders as $customerorder) {
            $moment = $customerorder->getMoment();
            if ($moment && $moment >= $dateFrom && $moment <= $dateTo) {
                $filtered[] = $customerorder;
            }
        }
        return new CustomerorderIterator($filtered);
    }

    /**
     * Get customer orders with specific attribute
     * @param string $attributeName
     * @return CustomerorderIterator
     */
    public function filterByAttributeName(string $attributeName): CustomerorderIterator
    {
        $filtered = [];
        foreach ($this->customerorders as $customerorder) {
            if ($customerorder->hasAttribute($attributeName)) {
                $filtered[] = $customerorder;
            }
        }
        return new CustomerorderIterator($filtered);
    }

    /**
     * Get customer orders with specific attribute value
     * @param string $attributeName
     * @param mixed $value
     * @return CustomerorderIterator
     */
    public function filterByAttributeValue(string $attributeName, $value): CustomerorderIterator
    {
        $filtered = [];
        foreach ($this->customerorders as $customerorder) {
            $attribute = $customerorder->getAttributeByName($attributeName);
            if ($attribute && $attribute->getValue() === $value) {
                $filtered[] = $customerorder;
            }
        }
        return new CustomerorderIterator($filtered);
    }

    /**
     * Sort customer orders by moment (date)
     * @param string $direction 'ASC' or 'DESC'
     * @return CustomerorderIterator
     */
    public function sortByMoment(string $direction = 'ASC'): CustomerorderIterator
    {
        $sorted = $this->customerorders;
        usort($sorted, function($a, $b) use ($direction) {
            $momentA = $a->getMoment();
            $momentB = $b->getMoment();
            
            if ($momentA === $momentB) {
                return 0;
            }
            
            $result = ($momentA < $momentB) ? -1 : 1;
            return ($direction === 'DESC') ? -$result : $result;
        });
        
        return new CustomerorderIterator($sorted);
    }

    /**
     * Sort customer orders by sum
     * @param string $direction 'ASC' or 'DESC'
     * @return CustomerorderIterator
     */
    public function sortBySum(string $direction = 'ASC'): CustomerorderIterator
    {
        $sorted = $this->customerorders;
        usort($sorted, function($a, $b) use ($direction) {
            $sumA = $a->getSum() ?? 0;
            $sumB = $b->getSum() ?? 0;
            
            if ($sumA === $sumB) {
                return 0;
            }
            
            $result = ($sumA < $sumB) ? -1 : 1;
            return ($direction === 'DESC') ? -$result : $result;
        });
        
        return new CustomerorderIterator($sorted);
    }

    /**
     * Convert to array for JSON serialization
     * @return array
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->customerorders as $customerorder) {
            $result[] = $customerorder->toArray();
        }
        return $result;
    }

    /**
     * Get JSON representation
     * @return string
     */
    public function jsonSerialize(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}