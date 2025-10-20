<?php
/**
 *
 * @class CustomerorderFilterBuilder
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace MS\v2;
require_once(__DIR__ . '/FilterBuilder.php');

/**
 * Class CustomerorderFilterBuilder
 * Specialized filter builder for customer order API with entity-specific value formatting.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */
class CustomerorderFilterBuilder extends FilterBuilder
{
	/**
	 * Format filter value with customer order specific rules
	 * @param mixed $value Raw value
	 * @param string $key Filter key
	 * @param string $operator Filter operator
	 * @return string Formatted value
	 */
	protected function formatFilterValue($value, $key, $operator)
	{
		// Handle boolean fields
		if (in_array($key, ['applicable', 'archived'])) {
			return $value ? 'true' : 'false';
		}
		
		// Handle entity fields (build full API URLs if not already URLs)
		if ($key === 'agent' && $value) {
			return $this->isUrl($value) ? $value : MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/counterparty/' . $value;
		}
		if ($key === 'organization' && $value) {
			return $this->isUrl($value) ? $value : MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/organization/' . $value;
		}
		if ($key === 'state' && $value) {
			return $this->isUrl($value) ? $value : MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDERSTATE . '/' . $value;
		}
		
		// Handle numeric fields (no URL encoding needed)
		if (in_array($key, ['sum', 'quantity', 'reserve', 'inTransit', 'waitSum'])) {
			return (string)$value;
		}
		
		// Use parent class formatting for all other cases
		return parent::formatFilterValue($value, $key, $operator);
	}
	
	/**
	 * Check if value is already a URL
	 * @param mixed $value Value to check
	 * @return bool True if value is a URL
	 */
	private function isUrl($value)
	{
		return is_string($value) && (strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0);
	}
}
?>