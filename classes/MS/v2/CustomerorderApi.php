<?php
/**
 *
 * @class CustomerorderApi
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace MS\v2;

require_once(__DIR__ . '/BaseApi.php');

/**
 * Class CustomerorderApi
 * Handles MoySklad customer order API operations, filtering, and bulk create/update.
 *
 * @author Georgy Polyan <acidlord@yandex.ru>
 */
class CustomerorderApi extends BaseApi
{
	/**
	 * Chunk size for splitting customer order operations
	 */
	private const CHUNK_SIZE = 100;
	
	private $log;
	private $api;
	private $addToQueue;
	private $transactionId;
	
	/**
	 * CustomerorderApi constructor.
	 * Initializes logging and API client.
	 * @param bool $addToQueue Whether to add createupdate requests to queue instead of direct API calls (default: false)
	 * @param string $transactionId Transaction ID for queue operations (mandatory when $addToQueue is true)
	 */
	public function __construct($addToQueue = false, $transactionId = null)
	{
        $docroot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
		require_once($docroot . '/docker-config.php');
		require_once(__DIR__ . '/Api.php');
		require_once(__DIR__ . '/CustomerorderFilterBuilder.php');
		require_once($docroot . '/classes/Common/Log.php');
		require_once($docroot . '/classes/Queue/Queue.php');

		$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($docroot, '', __FILE__)), " -");
		$logName .= '.log';
		$this->log = new \Classes\Common\Log($logName);
		
		// Initialize API client
		$this->api = new Api();
		
		// Store queue preference and validate transaction ID
		$this->addToQueue = $addToQueue;
		
		if ($addToQueue && empty($transactionId)) {
			throw new \InvalidArgumentException('Transaction ID is mandatory when addToQueue is true');
		}
		
		$this->transactionId = $transactionId;
		
		// Initialize specialized filter builder
		$this->initializeFilterBuilder();
	}
	
	/**
	 * Override parent to initialize CustomerorderFilterBuilder
	 */
	protected function initializeFilterBuilder()
	{
		$this->filterBuilder = new CustomerorderFilterBuilder();
	}
	
	/**
	 * Search customer orders with filters
	 * @param array $filters Array of filter parameters
	 * @param int $limit Maximum number of results (default: 1000, max: 1000)
	 * @param int $offset Offset for pagination (default: 0)
	 * @return CustomerorderIterator|false
	 */
	public function search($filters = [], $limit = 1000, $offset = 0)
	{
		$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER;
		$params = [];
		
		// Add filter parameters using BaseApi method
		$filterParams = $this->buildFilterParams($filters);
		$params = array_merge($params, $filterParams);
		
		// Add standard parameters using BaseApi method
		$this->addStandardParams($params, $limit, $offset);
		
		// Build URL using BaseApi method
		$fullUrl = $this->buildUrl($url, $params);
		
		$this->log->write(__LINE__ . ' ' . __METHOD__ . ' search url - ' . $fullUrl);
		$this->log->write(__LINE__ . ' ' . __METHOD__ . ' filters - ' . json_encode($filters, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$response = $this->api->getData($fullUrl);
		if ($response === false) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - Failed to fetch customer orders');
			return false;
		}
		
		if (is_string($response)) {
			$response = json_decode($response, true);
		}
		
		if (!$response || !is_array($response) || !isset($response['rows'])) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - Invalid response format');
			return false;
		}
		
		$this->log->write(__LINE__ . ' ' . __METHOD__ . ' found ' . count($response['rows']) . ' customer orders');
		
		require_once(__DIR__ . '/CustomerorderIterator.php');
		return new CustomerorderIterator($response);
	}
	
	/**
	 * Get customer order by ID
	 * @param string $id Customer order ID
	 * @return Customerorder|false
	 */
	public function get($id)
	{
		$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . '/' . $id;
		
		$this->log->write(__LINE__ . ' ' . __METHOD__ . ' get url - ' . $url);
		
		$response = $this->api->getData($url);
		if ($response === false) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - Failed to fetch customer order');
			return false;
		}
		
		if (is_string($response)) {
			$response = json_decode($response, true);
		}
		
		if (!$response || !is_array($response)) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - Invalid response format');
			return false;
		}
		
		require_once(__DIR__ . '/Customerorder.php');
		return new Customerorder($response);
	}
	
	/**
	 * Bulk create and update customer orders
	 * According to MoySklad API: https://dev.moysklad.ru/doc/api/remap/1.2/documents/#dokumenty-zakaz-pokupatelq-massowoe-sozdanie-i-obnowlenie-zakazow-pokupatelej
	 * @param CustomerorderIterator $customerorderIterator Iterator with customer orders to create/update
	 * @return CustomerorderIterator|false Iterator with results or false on failure
	 */
	public function createupdate($customerorderIterator)
	{
		if ($customerorderIterator->isEmpty()) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - Empty customerorder iterator provided');
			return false;
		}

		$this->log->write(__LINE__ . ' ' . __METHOD__ . ' processing ' . $customerorderIterator->count() . ' customer orders');
		
		$allResults = [];
		
		// Split into chunks for bulk processing
		$chunks = array_chunk($customerorderIterator->getCustomerorders(), self::CHUNK_SIZE);
		
		foreach ($chunks as $chunkIndex => $chunk) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' processing chunk ' . ($chunkIndex + 1) . '/' . count($chunks) . ' with ' . count($chunk) . ' orders');
			
			$requestData = [];
			foreach ($chunk as $customerorder) {
				if ($customerorder->getId()) {
					// Update existing order - include ID and meta
					$orderData = $customerorder->jsonSerializeForUpdate();
				} else {
					// Create new order - exclude ID
					$orderData = $customerorder->jsonSerializeForInsert();
				}
				$requestData[] = $orderData;
			}
			
			$url = MS_API_CUSTOMERORDER_V2;
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' chunk url - ' . $url);
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' chunk request data count - ' . count($requestData));
			
			if ($this->addToQueue) {
				// Add request to queue using Queue class
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' adding chunk ' . ($chunkIndex + 1) . ' to queue with transaction ID: ' . $this->transactionId);
				
				// Load Queue class and create queue entry
				require_once(dirname(__DIR__, 2) . '/Queue/Queue.php');
				$queue = new \Queue\Queue();
				
				$queuePayload = [
					'api' => 'ms',
					'organization' => '', // Could be parameterized if needed
					'method' => 'POST',
					'url' => $url,
					'body' => $requestData
				];
				
				$queueId = $queue->create($this->transactionId, $queuePayload);
				if ($queueId !== false) {
					$this->log->write(__LINE__ . ' ' . __METHOD__ . ' chunk ' . ($chunkIndex + 1) . ' added to queue with ID: ' . $queueId . ', transaction: ' . $this->transactionId);
				} else {
					$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - Failed to add chunk ' . ($chunkIndex + 1) . ' to queue');
				}
				
				// For queued requests, create placeholder results
				$placeholderResults = [];
				foreach ($requestData as $index => $orderData) {
					$placeholderResults[] = [
						'id' => 'queued-' . time() . '-' . $chunkIndex . '-' . $index,
						'name' => $orderData['name'] ?? 'Queued Order',
						'externalCode' => $orderData['externalCode'] ?? 'queued-' . time(),
						'applicable' => $orderData['applicable'] ?? false,
						'moment' => $orderData['moment'] ?? date('Y-m-d H:i:s'),
						'queued' => true,
						'queueId' => $queueId,
						'transactionId' => $this->transactionId,
						'chunkNumber' => $chunkIndex + 1
					];
				}
				$allResults = array_merge($allResults, $placeholderResults);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' chunk ' . ($chunkIndex + 1) . ' added to queue with ' . count($placeholderResults) . ' placeholder results');
				
			} else {
				// Direct API call (existing behavior)
				$response = $this->api->postData($url, $requestData);
				
				if ($response === false) {
					$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - Failed to process chunk ' . ($chunkIndex + 1));
					continue; // Continue with next chunk
				}
				
				if (is_string($response)) {
					$response = json_decode($response, true);
				}
				
				if (!$response || !is_array($response)) {
					$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - Invalid response format for chunk ' . ($chunkIndex + 1));
					continue;
				}
				
				// MoySklad bulk operation returns array directly, not wrapped in 'rows'
				if (isset($response['rows'])) {
					$allResults = array_merge($allResults, $response['rows']);
				} else {
					$allResults = array_merge($allResults, $response);
				}
				
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' chunk ' . ($chunkIndex + 1) . ' processed successfully with ' . count($response) . ' results');
			}
		}
		
		if (empty($allResults)) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - No results from any chunk');
			return false;
		}
		
		$this->log->write(__LINE__ . ' ' . __METHOD__ . ' total results - ' . count($allResults));
		
		// Return iterator with results
		require_once(__DIR__ . '/CustomerorderIterator.php');
		return new CustomerorderIterator($allResults);
	}
	
	/**
	 * Search all customer orders matching filters with automatic pagination
	 * @param array $filters Array of filter parameters
	 * @return CustomerorderIterator|false
	 */
	public function searchAll($filters = [])
	{
		$allOrders = [];
		$offset = 0;
		$limit = 1000;
		
		do {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' fetching page with offset ' . $offset);
			$iterator = $this->search($filters, $limit, $offset);
			
			if ($iterator === false) {
				break;
			}
			
			$orders = $iterator->getCustomerorders();
			$allOrders = array_merge($allOrders, $orders);
			$offset += $limit;
			
			// Continue if we got a full page (1000 results)
		} while (count($orders) === $limit);
		
		if (empty($allOrders)) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - No customer orders found');
			return false;
		}
		
		$this->log->write(__LINE__ . ' ' . __METHOD__ . ' total fetched - ' . count($allOrders) . ' customer orders');
		
		require_once(__DIR__ . '/CustomerorderIterator.php');
		return new CustomerorderIterator($allOrders);
	}
	
	/**
	 * Get customer orders by external codes
	 * @param array $externalCodes Array of external codes
	 * @return CustomerorderIterator|false
	 */
	public function getByExternalCodes($externalCodes)
	{
		if (empty($externalCodes) || !is_array($externalCodes)) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - Empty or invalid external codes provided');
			return false;
		}
		
		$allOrders = [];
		
		// Split external codes into chunks to avoid URL length limits
		$chunks = array_chunk($externalCodes, self::CHUNK_SIZE);
		
		foreach ($chunks as $chunkIndex => $chunk) {
			// Use proper FilterBuilder format: {key: {operator: [values]}}
			$filters = [
				'externalCode' => ['=' => $chunk]
			];
			
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' processing chunk ' . ($chunkIndex + 1) . ' with codes - ' . json_encode($chunk, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			
			$iterator = $this->search($filters);
			if ($iterator !== false) {
				$orders = $iterator->getCustomerorders();
				$allOrders = array_merge($allOrders, $orders);
			}
		}
		
		if (empty($allOrders)) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - No customer orders found for provided external codes');
			return false;
		}
		
		$this->log->write(__LINE__ . ' ' . __METHOD__ . ' found ' . count($allOrders) . ' customer orders for external codes');
		
		require_once(__DIR__ . '/CustomerorderIterator.php');
		return new CustomerorderIterator($allOrders);
	}
	
	/**
	 * Delete customer order by ID
	 * @param string $id Customer order ID
	 * @return bool
	 */
	public function delete($id)
	{
		$url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . '/' . $id;
		
		$this->log->write(__LINE__ . ' ' . __METHOD__ . ' delete url - ' . $url);
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			'Content-type: application/json',
			'Accept-Encoding: gzip',
			'Authorization: Bearer ' . $this->api->getToken()
		]);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_ENCODING, '');
		
		$response = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		
		if ($httpCode >= 200 && $httpCode < 300) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' customer order deleted successfully');
			return true;
		} else {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error - Failed to delete customer order, HTTP code: ' . $httpCode);
			return false;
		}
	}
	

	
}

?>