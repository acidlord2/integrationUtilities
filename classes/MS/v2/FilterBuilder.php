<?php
/**
 *
 * @class FilterBuilder
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace MS\v2;

/**
 * Class FilterBuilder
 * Builds MoySklad API filter strings from structured filter arrays.
 * Supports generic operator-based filtering for all MoySklad API endpoints.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */
class FilterBuilder
{
	/**
	 * Official MoySklad filter operators from API documentation
	 * @see https://dev.moysklad.ru/doc/api/remap/1.2/#mojsklad-json-api-obschie-swedeniq-fil-traciq-wyborki-s-pomosch-u-parametra-filter
	 */
	private const VALID_OPERATORS = ['=', '>', '<', '>=', '<=', '!=', '~', '~=', '=~'];
	
	/**
	 * Build filter string from filter array
	 * @param array $filters Array of filter parameters
	 * @return string URL-encoded filter string
	 */
	public function buildFilterString($filters = [])
	{
		if (empty($filters)) {
			return '';
		}
		
		$filterParts = [];
		
		foreach ($filters as $key => $value) {
			$this->addFilterParts($filterParts, $key, $value);
		}
		
		return empty($filterParts) ? '' : implode(';', $filterParts);
	}
	
	/**
	 * Add filter parts for a specific filter key and value
	 * @param array $filterParts Reference to filter parts array
	 * @param string $key Filter key
	 * @param mixed $value Filter value (can be array, object with operators, or single value)
	 */
	private function addFilterParts(&$filterParts, $key, $value)
	{
		// Handle new structure: {key: {operator: [values]}}
		if (is_array($value) && $this->isOperatorStructure($value)) {
			foreach ($value as $operator => $operatorValues) {
				$this->addFilterWithOperator($filterParts, $key, $operator, $operatorValues);
			}
		} else {
			// Default to equality for simple values
			$this->addFilterWithOperator($filterParts, $key, '=', $value);
		}
	}
	
	/**
	 * Check if array has operator structure {operator: values}
	 * @param array $value
	 * @return bool
	 */
	private function isOperatorStructure($value)
	{
		if (!is_array($value) || empty($value)) {
			return false;
		}
		
		// Check if all keys are valid MoySklad operators
		foreach (array_keys($value) as $key) {
			if (!in_array($key, self::VALID_OPERATORS, true)) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Add filter with specific operator
	 * @param array $filterParts Reference to filter parts array
	 * @param string $key Filter key
	 * @param string $operator Filter operator
	 * @param mixed $values Filter values (can be array or single value)
	 */
	private function addFilterWithOperator(&$filterParts, $key, $operator, $values)
	{
		// Normalize values to array
		$valueArray = is_array($values) ? $values : [$values];
		
		// URL encode the operator to handle characters like > in >=
		$encodedOperator = urlencode($operator);
		
		foreach ($valueArray as $value) {
			$formattedValue = $this->formatFilterValue($value, $key, $operator);
			$filterParts[] = $key . $encodedOperator . $formattedValue;
		}
	}
	
	/**
	 * Format filter value based on key and operator
	 * Override this method in specific API classes for entity-specific formatting
	 * @param mixed $value Raw value
	 * @param string $key Filter key
	 * @param string $operator Filter operator
	 * @return string Formatted value
	 */
	protected function formatFilterValue($value, $key, $operator)
	{
		// Handle empty values - for searching records with empty/null fields
		// Only for = and != operators, return empty string for null/empty field searches
		if (($value === '' || $value === null) && in_array($operator, ['=', '!='])) {
			return '';
		}
		
		// For most operators, URL encode the value
		if (in_array($operator, ['=', '!=', '~', '~=', '=~', '>', '<', '>=', '<='])) {
			// Handle array values by encoding each element
			if (is_array($value)) {
				return array_map('urlencode', $value);
			}
			return urlencode($value);
		}
		
		// Default: return as string
		return (string)$value;
	}
	
	/**
	 * Get list of valid operators
	 * @return array
	 */
	public function getValidOperators()
	{
		return self::VALID_OPERATORS;
	}
	
	/**
	 * Validate operator
	 * @param string $operator
	 * @return bool
	 */
	public function isValidOperator($operator)
	{
		return in_array($operator, self::VALID_OPERATORS, true);
	}
}
?>