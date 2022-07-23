<?php
/**
 *
 * @class OrdersBeru
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class OrdersBeru
{
	private static $logFilename = 'classes - ordersBeru.log';
	// get orders by filter
    public static function getOrders($campaign, $filters)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiBeru.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		$logger->write (__LINE__ . ' getOrders.filters - ' . json_encode ($filters, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$filter = '';
		foreach ($filters as $par => $value)
			$filter .= '&' . $par . '=' . $value;
		$filter = substr ($filter, 1);
		
		$orders = array();
		$page = 1;
		$maxpage = 1;
		while ($page <= $maxpage) { 
		
			$service_url = BERU_API_BASE_URL . BERU_API_VERSION . BERU_API_CAMPAIGNS . $campaign . '/' . BERU_API_ORDERS . '.JSON' . '?' . $filter . '&page=' . $page;
			$logger->write (__LINE__ . ' getOrders.service_url - ' . $service_url);
			APIBeru::getBeruData ($campaign, $service_url, $ordersJson, $ordersArray);
			
			if (isset ($ordersArray['error']))
			{
			    $logger->write (__LINE__ . ' getOrders.ordersJson - ' . $ordersJson);
				return;
			}
			$orders = array_merge ($orders, $ordersArray['orders']);
			$maxpage = $ordersArray ['pager']['pagesCount'];
			$page++;
		}

		$logger->write (__LINE__ . ' getOrders.orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $orders;
	}
	// pack order
    public static function packOrder($campaign, $orderId, $shipmentId, $boxes)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiBeru.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		$logger->write (__LINE__ . ' packOrder.orderId - ' . $orderId);
		$logger->write (__LINE__ . ' packOrder.shipmentId - ' . $shipmentId);
		$logger->write (__LINE__ . ' packOrder.boxes - ' . json_encode ($boxes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$service_url = BERU_API_BASE_URL . BERU_API_VERSION . BERU_API_CAMPAIGNS . $campaign . '/' . BERU_API_ORDERS . '/' . $orderId . '/' . BERU_API_SHIPMENTS . $shipmentId . '/' . BERU_API_BOXES . '.JSON';
		
		APIBeru::putBeruData ($campaign, $service_url, $boxes, $returnJson, $return);

		$logger->write (__LINE__ . ' packOrder.returnJson - ' . $returnJson);
		return $return;
	}
	// set order status
    public static function updateOrderStatus($campaign, $orderId, $data)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiBeru.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		$logger->write (__LINE__ . ' updateOrder.orderId - ' . $orderId);
		$logger->write (__LINE__ . ' updateOrder.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$service_url = BERU_API_BASE_URL . BERU_API_VERSION . BERU_API_CAMPAIGNS . $campaign . '/' . BERU_API_ORDERS . '/' . $orderId . '/' . BERU_API_STATUS . '.JSON';
		
		APIBeru::putBeruData ($campaign, $service_url, $data, $returnJson, $return);

		$logger->write (__LINE__ . ' updateOrder.returnJson - ' . $returnJson);
		return $return;
	}
	// get order labels
    public static function getOrdersLebels($campaign, $orderId)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiBeru.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		$logger->write (__LINE__ . ' getOrdersLebels.orderId - ' . $orderId);
		//$logger->write ('getOrdersLebels.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$service_url = BERU_API_BASE_URL . BERU_API_VERSION . BERU_API_CAMPAIGNS . $campaign . '/' . BERU_API_ORDERS . '/' . $orderId . '/' . BERU_API_LABELS . '.JSON';
		
		APIBeru::getBeruData ($campaign, $service_url, $returnJson, $return);

		$logger->write (__LINE__ . ' getOrdersLebels.returnJson - ' . $returnJson);
		return $return;
	}
}

?>