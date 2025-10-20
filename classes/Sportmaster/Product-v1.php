<?php
/**
 *
 * @class Product
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace Classes\Sportmaster\v1;

class Product
{
	private $log;
	private $apiClass;
	private $queue;
	private $clientId;
	private $limit = 500;
	private $transactionId;
	private $sleepTime = 1; // seconds
	
	public function __construct($clientId)
	{
		$docroot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2);
		require_once($docroot . '/docker-config.php');
		require_once($docroot . '/classes/Sportmaster/Api-v1.php');
		require_once($docroot . '/classes/Common/Log.php');
		require_once($docroot . '/classes/Queue/Queue.php');

		$this->clientId = $clientId;
        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($docroot, '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);
		$this->apiClass = new \Classes\Sportmaster\v1\Api($clientId);
		$this->queue = new \Queue\Queue();
		$this->transactionId = $this->getTransactionId();
	}
	
	/**
	 * Get client ID
	 * @return string Client ID
	 */
	private function getClientId()
	{
		return $this->clientId;
	}
	
	/**
	 * Get unique transaction ID for this script execution
	 * @return string Unique transaction ID
	 */
	private function getTransactionId()
	{
		// For CLI: Check if script set a transaction ID
		$scriptId = getenv('SCRIPT_TRANSACTION_ID');
		if ($scriptId) {
			return $scriptId;
		}
		
		// For web: Use session if available
		if (isset($_SERVER['HTTP_HOST']) && !headers_sent()) {
			@session_start();
			$sessionId = session_id();
			if ($sessionId) {
				return 'web_' . $sessionId . '_' . round(microtime(true) * 1000);
			}
		}
		
		// Fallback: Generate unique ID with timestamp + random
		return 'auto_' . round(microtime(true) * 1000) . '_' . bin2hex(random_bytes(4));
	}	
	public function pricesList()
	{
		$offset = 0;
	    $prices = array();
		$url = SPORTMASTER_BASE_URL . 'v1/product/prices/list';
		$this->log->write(__LINE__ . ' '. __METHOD__ . ' url - ' . $url);
	    while (true)
	    {
			$post_data = array(
				'limit' => $this->limit,
				'offset' => $offset
			);
			$this->log->write(__LINE__ . ' '. __METHOD__ . ' post_data - ' . json_encode($post_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$response = $this->apiClass->postData($url, $post_data);
			if ($response && isset($response['productPrices']))
			{
				$prices = array_merge($prices, $response['productPrices']);
				$offset += $this->limit;
				if ($offset >= $response['pagination']['total'])
					break;
			}
			else
			{
				$this->log->write(__LINE__ . ' '. __METHOD__ . ' Error fetching product prices: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				break;
			}
			sleep($this->sleepTime); // Sleep to avoid hitting API rate limits
	    }
	    $this->log->write(__LINE__ . ' '. __METHOD__ . ' total product prices fetched - ' . count($prices));
		$this->log->write(__LINE__ . ' '. __METHOD__ . ' prices - ' . json_encode($prices, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $prices;
	}

	public function stockList($warehouseId)
	{
		$offset = 0;
	    $products = array();
		$url = SPORTMASTER_BASE_URL . 'v1/fbs/stocks/list';
		$this->log->write(__LINE__ . ' '. __METHOD__ . ' url - ' . $url);
	    while (true)
	    {
			$post_data = array(
				'warehouseId' => $warehouseId,
				'limit' => $this->limit,
				'offset' => $offset
			);
			$this->log->write(__LINE__ . ' '. __METHOD__ . ' post_data - ' . json_encode($post_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$response = $this->apiClass->postData($url, $post_data);
			$this->log->write(__LINE__ . ' '. __METHOD__ . ' response - ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if ($response && isset($response['stocks']))
			{
				$products = array_merge($products, $response['stocks']);
				$offset += $this->limit;
				if ($offset >= $response['pagination']['total'])
					break;
			}
			else
			{
				$this->log->write(__LINE__ . ' '. __METHOD__ . ' Error fetching stock list: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				break;
			}
			sleep($this->sleepTime); // Sleep to avoid hitting API rate limits
	    }
	    $this->log->write(__LINE__ . ' '. __METHOD__ . ' total products fetched - ' . count($products));
	    return $products;
	}

	public function stockUpdate($warehouseId, $stocks)
	{
		$this->log->write(__LINE__ . ' '. __METHOD__ . ' warehouseId - ' . $warehouseId);
		$this->log->write(__LINE__ . ' '. __METHOD__ . ' stocks - ' . json_encode($stocks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SPORTMASTER_BASE_URL . SPORTMASTER_API_UPDATE_STOCKS;
	    $this->log->write(__LINE__ . ' '. __METHOD__ . ' url - ' . $url);
		
		// Use process ID as transaction ID for the entire stock update operation
		$transactionId = $this->getTransactionId();
		$this->log->write(__LINE__ . ' '. __METHOD__ . ' Transaction ID (Process): ' . $transactionId);
		
		$queuedTasks = 0;
		foreach(array_chunk($stocks, $this->limit) as $chunkIndex => $chunk) 
		{
			// Create queue message for each chunk
			$payload = [
				'api' => 'sportmaster',
				'organization' => $this->getClientId(),
				'method' => 'POST',
				'url' => $url,
				'body' => [
					'warehouseId' => $warehouseId,
					'stocks' => $chunk
				]
			];
			
			$queueId = $this->queue->create($transactionId, $payload);
			if ($queueId) {
				$this->log->write(__LINE__ . ' '. __METHOD__ . ' Stock update queued with ID: ' . $queueId . ', chunk ' . ($chunkIndex + 1) . ', size: ' . count($chunk));
				$queuedTasks++;
			} else {
				$this->log->write(__LINE__ . ' '. __METHOD__ . ' Error queueing stock update for chunk ' . ($chunkIndex + 1));
			}
		}
		
		$this->log->write(__LINE__ . ' '. __METHOD__ . ' Total queued tasks: ' . $queuedTasks . ' for process: ' . $transactionId);
		return $queuedTasks > 0;
	}

	public function pricesUpdate($prices)
	{
		$this->log->write(__LINE__ . ' '. __METHOD__ . ' prices - ' . json_encode($prices, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SPORTMASTER_BASE_URL . SPORTMASTER_API_UPDATE_PRICES;
	    $this->log->write(__LINE__ . ' '. __METHOD__ . ' url - ' . $url);
		
		// Use process ID as transaction ID for the entire prices update operation
		$transactionId = $this->transactionId;
		$this->log->write(__LINE__ . ' '. __METHOD__ . ' Transaction ID (Process): ' . $transactionId);
		
		$queuedTasks = 0;
		foreach(array_chunk($prices, $this->limit) as $chunkIndex => $chunk) 
		{
			// Create queue message for each chunk
			$payload = [
				'api' => 'sportmaster',
				'organization' => $this->getClientId(),
				'method' => 'POST',
				'url' => $url,
				'body' => [
					'productPrices' => $chunk
				]
			];
			
			$queueId = $this->queue->create($transactionId, $payload);
			if ($queueId) {
				$this->log->write(__LINE__ . ' '. __METHOD__ . ' Price update queued with ID: ' . $queueId . ', chunk ' . ($chunkIndex + 1) . ', size: ' . count($chunk));
				$queuedTasks++;
			} else {
				$this->log->write(__LINE__ . ' '. __METHOD__ . ' Error queueing price update for chunk ' . ($chunkIndex + 1));
			}
		}
		
		$this->log->write(__LINE__ . ' '. __METHOD__ . ' Total queued tasks: ' . $queuedTasks . ' for process: ' . $transactionId);
		return $queuedTasks > 0;
	}
}