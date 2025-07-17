<?php
/**
 *
 * @class Product
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace Classes\Sportmaster\v1;

class Order
{
	private $log;
	private $apiClass;
	private $limit = 500;
	private $labelLimit = 1;
	private $sleepTime = 2; // seconds
	private $warehouseId;
	
	public function __construct($cliendtId, $warehouseId)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/Api-v1.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
		
        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);
		$this->apiClass = new \Classes\Sportmaster\v1\Api($cliendtId);
		$this->warehouseId = $warehouseId;
	}	

	public function shipmentsList($shipmentStatuses)
	{
		$offset = 0;
	    $orders = array();
		$url = SPORTMASTER_BASE_URL . 'v1/fbs/shipments/list';
		$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' url - ' . $url);
	    while (true)
	    {
			$post_data = array(
				'warehouseId' => $this->warehouseId,
				'shipmentStatuses' => $shipmentStatuses,
				'limit' => $this->limit,
				'offset' => $offset
			);
			$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' post_data - ' . json_encode($post_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$response = $this->apiClass->postData($url, $post_data);
			$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' response - ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if ($response && isset($response['shipments']))
			{
				$orders = array_merge($orders, $response['shipments']);
				$offset += $this->limit;
				if ($offset >= $response['pagination']['total'])
					break;
			}
			else
			{
				$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Error fetching shipments list: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return false;
			}
			sleep($this->sleepTime); // Sleep to avoid hitting API rate limits
	    }
	    $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' total orders fetched - ' . count($orders));
	    return $orders;
	}

	public function shipmentGet($shipmentId)
	{
		$url = SPORTMASTER_BASE_URL . 'v1/fbs/shipments/' . $shipmentId;
		$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' url - ' . $url);
		$response = $this->apiClass->getData($url);
		$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' response - ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		if ($response)
		{
			return $response;
		}
		else
		{
			$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Error fetching shipment: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			return false;
		}
		sleep($this->sleepTime); // Sleep to avoid hitting API rate limits
	}

	public function shipmentChangePackages($shipmentId, $packages)
	{
		$url = SPORTMASTER_BASE_URL . 'v1/fbs/shipments/' . $shipmentId . '/change-packages';
		$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' url - ' . $url);
		$post_data = array(
			'packages' => $packages
		);
		$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' post_data - ' . json_encode($post_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$response = $this->apiClass->postData($url, $post_data);
		$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' response - ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		if ($response == null)
		{
			return true;
		}
		else
		{
			$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Error changing packages: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			return false;
		}
		sleep($this->sleepTime); // Sleep to avoid hitting API rate limits
	}

	public function shipmentGetLabel($shipmentIds)
	{
		$url = SPORTMASTER_BASE_URL . 'v1/fbs/shipments/get-package-labels';
		$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' url - ' . $url);
		$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' shipmentIds - ' . json_encode($shipmentIds, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		if (!is_array($shipmentIds)) {
			$shipmentIds = array($shipmentIds);
		}
		$labels = array();
		foreach(array_chunk($shipmentIds, $this->labelLimit) as $chunk) {
			$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' processing chunk - ' . json_encode($chunk, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$post_data = array(
				'shipmentIds' => $chunk
			);
			$response = $this->apiClass->postData($url, $post_data);
			if (isset($response['fileName']) && $response['fileName'] != null) {
				$labels = array_merge($labels, $response);
			} else {
				$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' No fileName in response: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Skipping chunk: ' . json_encode($chunk, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
			sleep($this->sleepTime); // Sleep to avoid hitting API rate limits
		}
		return $labels;
	}
}
