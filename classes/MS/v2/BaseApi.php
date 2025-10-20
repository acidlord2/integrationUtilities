<?php
/**
 *
 * @class BaseApi
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace MS\v2;

require_once(__DIR__ . '/FilterBuilder.php');

/**
 * Class BaseApi
 * Base class for MoySklad API classes with common filtering functionality.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */
abstract class BaseApi
{
	protected $filterBuilder;
	
	/**
	 * Initialize filter builder
	 * Can be overridden in child classes to use specialized filter builders
	 */
	protected function initializeFilterBuilder()
	{
		$this->filterBuilder = new FilterBuilder();
	}
	
	/**
	 * Build filter URL parameters from filter array
	 * @param array $filters
	 * @return array URL parameters array
	 */
	protected function buildFilterParams($filters = [])
	{
		$params = [];
		$filterString = $this->filterBuilder->buildFilterString($filters);
		
		if (!empty($filterString)) {
			$params[] = 'filter=' . $filterString;
		}
		
		return $params;
	}
	
	/**
	 * Get the filter builder instance
	 * @return FilterBuilder
	 */
	public function getFilterBuilder()
	{
		return $this->filterBuilder;
	}
	
	/**
	 * Add standard API parameters (limit, offset) to params array
	 * @param array $params Reference to parameters array
	 * @param int $limit
	 * @param int $offset
	 */
	protected function addStandardParams(&$params, $limit = 0, $offset = 0)
	{
		if ($limit > 0) {
			$params[] = 'limit=' . min($limit, 1000);
		}
		if ($offset > 0) {
			$params[] = 'offset=' . $offset;
		}
	}
	
	/**
	 * Build final URL with parameters
	 * @param string $baseUrl
	 * @param array $params
	 * @return string
	 */
	protected function buildUrl($baseUrl, $params = [])
	{
		if (!empty($params)) {
			$baseUrl .= '?' . implode('&', $params);
		}
		
		return $baseUrl;
	}
}
?>