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
	private $limit = 500;
	private $sleepTime = 1; // seconds
	
	public function __construct($cliendtId)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/Api-v1.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
		
        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);
		$this->apiClass = new \Classes\Sportmaster\v1\Api($cliendtId);
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
	    $url = SPORTMASTER_BASE_URL . 'v1/fbs/stocks/create-import-task';
	    $this->log->write(__LINE__ . ' '. __METHOD__ . ' url - ' . $url);
		foreach(array_chunk($stocks, $this->limit) as $chunk) 
		{
			$post_data = array(
				'warehouseId' => $warehouseId,
				'stocks' => $chunk
			);
			$response = $this->apiClass->postData($url, $post_data);
			$this->log->write(__LINE__ . ' '. __METHOD__ . ' response - ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if (!$response || !isset($response['taskId'])) {
				$this->log->write(__LINE__ . ' '. __METHOD__ . ' Error importing stocks: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return false;
			}
			$this->log->write(__LINE__ . ' '. __METHOD__ . ' Import task created with ID: ' . $response['taskId']);
			sleep($this->sleepTime); // Sleep to avoid hitting API rate limits
		}
		return true;
	}

	public function pricesUpdate($prices)
	{
		$this->log->write(__LINE__ . ' '. __METHOD__ . ' prices - ' . json_encode($prices, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SPORTMASTER_BASE_URL . 'v1/product/prices/create-import-task';
	    $this->log->write(__LINE__ . ' '. __METHOD__ . ' url - ' . $url);
		foreach(array_chunk($prices, $this->limit) as $chunk) 
		{
			$post_data = array(
				'productPrices' => $chunk
			);
			$response = $this->apiClass->postData($url, $post_data);
			$this->log->write(__LINE__ . ' '. __METHOD__ . ' response - ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if (!$response || !isset($response['taskId'])) {
				$this->log->write(__LINE__ . ' '. __METHOD__ . ' Error importing prices: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return false;
			}
			$this->log->write(__LINE__ . ' '. __METHOD__ . ' Import task created with ID: ' . $response['taskId']);
			sleep($this->sleepTime); // Sleep to avoid hitting API rate limits
		}
		return true;
	}
}