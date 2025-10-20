<?php
/**
 *
 * @class ProductApi
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace MS\v2;

require_once(__DIR__ . '/BaseApi.php');
require_once(__DIR__ . '/ProductFilterBuilder.php');

/**
 * Class ProductApi
 * API wrapper for MoySklad product operations using FilterBuilder architecture.
 *
 * Example usage:
 * $api = new ProductApi();
 * 
 * // Simple filter
 * $products = $api->search(['name' => 'Widget']);
 * 
 * // Advanced filter with operators
 * $products = $api->search([
 *     'name' => ['~' => ['Widget', 'Gadget']],
 *     'archived' => ['=' => [false]],
 *     'weight' => ['>' => [100]]
 * ]);
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */
class ProductApi extends BaseApi
{
	/** @var ProductFilterBuilder Filter builder instance */
	private $filterBuilder;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->filterBuilder = new ProductFilterBuilder();
	}
	
	/**
	 * Search products with filtering support
	 * @param array $filters Filters in format ['key' => 'value'] or ['key' => ['operator' => ['values']]]
	 * @param int $limit Results limit (default 100)
	 * @param int $offset Results offset (default 0)
	 * @return array API response
	 */
	public function search($filters = [], $limit = 100, $offset = 0)
	{
		$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/product';
		$params = [];
		
		// Add filter parameters using FilterBuilder
		$filterParams = $this->buildFilterParams($filters, $this->filterBuilder);
		$params = array_merge($params, $filterParams);
		
		// Add standard parameters
		$this->addStandardParams($params, $limit, $offset);
		
		$fullUrl = $this->buildUrl($url, $params);
		
		return $this->makeRequest($fullUrl, 'GET');
	}
	
	/**
	 * Create new product
	 * @param array $productData Product data
	 * @return array API response
	 */
	public function create($productData)
	{
		$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/product';
		
		return $this->makeRequest($url, 'POST', json_encode($productData));
	}
	
	/**
	 * Update existing product
	 * @param string $productId Product ID
	 * @param array $productData Updated product data
	 * @return array API response
	 */
	public function update($productId, $productData)
	{
		$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/product/' . $productId;
		
		return $this->makeRequest($url, 'PUT', json_encode($productData));
	}
	
	/**
	 * Get product by ID
	 * @param string $productId Product ID
	 * @return array API response
	 */
	public function get($productId)
	{
		$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/product/' . $productId;
		
		return $this->makeRequest($url, 'GET');
	}
	
	/**
	 * Delete product
	 * @param string $productId Product ID
	 * @return array API response
	 */
	public function delete($productId)
	{
		$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/product/' . $productId;
		
		return $this->makeRequest($url, 'DELETE');
	}
	
	/**
	 * Bulk create products
	 * @param array $products Array of product data
	 * @return array API response
	 */
	public function bulkCreate($products)
	{
		$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/product';
		
		return $this->makeRequest($url, 'POST', json_encode($products));
	}
	
	/**
	 * Bulk update products
	 * @param array $products Array of product data with IDs
	 * @return array API response
	 */
	public function bulkUpdate($products)
	{
		$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/product';
		
		return $this->makeRequest($url, 'POST', json_encode($products));
	}
	
	/**
	 * Get filter builder instance for advanced filtering
	 * @return ProductFilterBuilder Filter builder instance
	 */
	public function getFilterBuilder()
	{
		return $this->filterBuilder;
	}
	
	/**
	 * Make HTTP request to API
	 * @param string $url Request URL
	 * @param string $method HTTP method
	 * @param string $data Request body
	 * @return array API response
	 */
	private function makeRequest($url, $method, $data = null)
	{
		// Implementation would use your existing HTTP client
		// This is a placeholder for the actual implementation
		$curl = curl_init();
		
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Basic ' . base64_encode(MS_LOGIN . ':' . MS_PASSWORD)
			],
		]);
		
		if ($data !== null) {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		
		$response = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		
		return json_decode($response, true);
	}
}
?>