<?php
/**
 *
 * @class ProductFilterBuilder
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace MS\v2;

require_once(__DIR__ . '/FilterBuilder.php');

/**
 * Class ProductFilterBuilder
 * Specialized filter builder for product API with entity-specific value formatting.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */
class ProductFilterBuilder extends FilterBuilder
{
	/**
	 * Format filter value with product specific rules
	 * @param mixed $value Raw value
	 * @param string $key Filter key
	 * @param string $operator Filter operator
	 * @return string Formatted value
	 */
	protected function formatFilterValue($value, $key, $operator)
	{
		// Handle boolean fields specific to products
		if (in_array($key, ['archived', 'weighed', 'isSerialTrackable'])) {
			return $value ? 'true' : 'false';
		}
		
		// Handle entity fields (build full API URLs)
		if ($key === 'group') {
			return MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/productfolder/' . $value;
		}
		if ($key === 'uom') {
			return MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/uom/' . $value;
		}
		if ($key === 'country') {
			return MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/country/' . $value;
		}
		
		// Handle numeric fields (no URL encoding needed)
		if (in_array($key, ['weight', 'volume', 'minPrice'])) {
			return (string)$value;
		}
		
		// Use parent class formatting for all other cases
		return parent::formatFilterValue($value, $key, $operator);
	}
}
?>