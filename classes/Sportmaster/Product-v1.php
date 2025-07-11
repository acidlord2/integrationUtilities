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
	
	public function __construct($cliendtId)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/Api-v1.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
		
        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), '-');
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);
		$this->apiClass = new \Classes\Sportmaster\v1\Api($cliendtId);
	}	

	public function stockList($warehouseId)
	{
		$offset = 0;
	    $products = array();
		$url = SPORTMASTER_BASE_URL . 'v1/fbs/stocks/list';
		$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' url - ' . $url);
	    while (true)
	    {
			$post_data = array(
				'warehouseId' => $warehouseId,
				'limit' => $this->limit,
				'offset' => $offset
			);
			$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' post_data - ' . json_encode($post_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$response = $this->apiClass->postData($url, $post_data);
			$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' response - ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if ($response && isset($response['stocks']))
			{
				$products = array_merge($products, $response['stocks']);
				$offset += $this->limit;
				if ($offset >= $response['pagination']['total'])
					break;
			}
			else
			{
				$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Error fetching stock list: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				break;
			}
	    }
	    $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' total products fetched - ' . count($products));
	    return $products;
	}

	public function stockImport($warehouseId, $stocks)
	{
		$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' warehouseId - ' . $warehouseId);
		$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' stocks - ' . json_encode($stocks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SPORTMASTER_BASE_URL . 'v1/fbs/stocks/create-import-task';
	    $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' url - ' . $url);
		foreach(array_chunk($stocks, $this->limit) as $chunk) 
		{
			$post_data = array(
				'warehouseId' => $warehouseId,
				'stocks' => $chunk
			);
			$response = $this->apiClass->postData($url, $post_data);
			$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' response - ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if (!$response || !isset($response['taskId'])) {
				$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Error importing stocks: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return false;
			}
			$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Import task created with ID: ' . $response['taskId']);
			sleep(1); // Sleep to avoid hitting API rate limits
		}
		return true;
	}

	public function pricesImport($prices)
	{
		$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' prices - ' . json_encode($prices, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SPORTMASTER_BASE_URL . 'v1/product/prices/create-import-task';
	    $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' url - ' . $url);
		foreach(array_chunk($prices, $this->limit) as $chunk) 
		{
			$post_data = array(
				'prices' => $chunk
			);
			$response = $this->apiClass->postData($url, $post_data);
			$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' response - ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if (!$response || !isset($response['taskId'])) {
				$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Error importing prices: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return false;
			}
			$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Import task created with ID: ' . $response['taskId']);
			sleep(1); // Sleep to avoid hitting API rate limits
		}
		return true;
	}
}