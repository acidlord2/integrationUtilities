<?php
/**
 *
 * @class OrdersMS
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class OrdersWB
{
	private static $logFilename = 'ordersWB.log';

	// get wb orders
	public static function getOrders($startDate)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiWB.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		
		$logger->write ('getOrders.startDate - ' . $startDate);
		$service_url = WB_ORDERS . '?date_start=' . $startDate;
		$logger->write ('getOrders.service_url - ' . $service_url);
		APIWB::getData('Kaori', $service_url, $response_orderJson, $response_order);
		$logger->write ('getOrders.response_orderJson - ' . json_encode ($response_orderJson, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $response_order;
	}
    public static function getWarehouse($warehouseId)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		
		$logger->write ('getWarehouse.warehouseId - ' . $warehouseId);
		$wh = Settings::getSettingsValues ('WBWareHouse');
		$whArray = json_decode($wh, true);
		$logger->write ('getWarehouse.whArray - ' . json_encode ($whArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		if (isset ($whArray[$warehouseId]))
			return $whArray[$warehouseId];
		else
			return $warehouseId;

	}
	public static function changeOrdersStatus($changeStatus)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiWB.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		
		$logger->write ('changeOrdersStatus.changeStatus - ' . json_encode ($changeStatus, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$service_url = WB_ORDERS;
		$logger->write ('changeOrdersStatus.service_url - ' . $service_url);
		APIWB::putData('Kaori', $service_url, $changeStatus, $response_orderJson, $response_order);
		$logger->write ('changeOrdersStatus.response_order - ' . json_encode ($response_order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $response_order;
	}
}

?>