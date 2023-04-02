<?php
/**
 *
 * @class OrdersMS
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class OrdersMS
{
	private static $logFilename = 'classes - ordersMS.log';
	/**
	* function findOrders - function find ms orders by ms filter passed
	*
	* @filters string - ms filter 
	* @return array - result as array of orders
	*/
	public static function findOrders($filters)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		
		$orders = array();
		$offset = 0;
		$logger->write (__LINE__ . ' findOrders.filters - ' . json_encode ($filters, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		while (true)
		{
			$filter = '';
			if (is_array($filters))
				foreach ($filters as $key => $value)
					$filter .= $key . '=' . $value . ';';
			else
				$filter = $filters;
			$service_url = MS_COURL . '?filter=' . $filter . '&limit=' . MS_LIMIT . '&offset=' . $offset;
			$logger->write ('findOrders.service_url - ' . $service_url);
			APIMS::getMSData($service_url, $response_orderJson, $response_order);
			if (isset ($response_order['rows'][0]))
			{
				$offset += MS_LIMIT;
				$orders = array_merge ($orders, $response_order['rows']);
			}
			else
				break;			
		}

		$logger->write (__LINE__ . ' findOrders.orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $orders;
	}

    public static function findOrdersByNames($names)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		
		$logger->write (__LINE__ . ' findOrdersByNames.names - ' . json_encode ($names, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$orders = array();
		$filter = '';
		foreach ($names as $key => $value)
		{
			$filter .= 'name=' . $value . ';';
			if ($key + 1 == count ($names) || ($key + 1) % 50 == 0)
			{
				$service_url = MS_COURL . '?filter=' . $filter . '&limit=' . MS_LIMIT;
				$logger->write (__LINE__ . ' findOrdersByNames.service_url - ' . $service_url);
				APIMS::getMSData($service_url, $msOrdersJson, $msOrdersArray);
				if (isset ($msOrdersArray['rows'][0])) {
					$orders = array_merge ($orders, $msOrdersArray['rows']);
				}
				else 
					$logger->write ('findOrdersByNames.msOrdersArray - ' . json_encode ($msOrdersArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				
				$filter = '';
			}
		}

		$logger->write (__LINE__ . ' findOrdersByNames.orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $orders;
	}

	// update order
    public static function updateOrder($orderId, $data)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		$service_url = MS_COURL . $orderId;
		$logger -> write (__LINE__ . ' updateOrder.service_url - ' . $service_url);
		$logger -> write (__LINE__ . ' updateOrder.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		APIMS::putMSData($service_url, $data, $resultJson, $result);
		$logger -> write (__LINE__ . ' updateOrder.resultJson - ' . $resultJson);
		return $result;
	}
	// update order mass
    public static function updateOrderMass($data)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		$service_url = MS_COURL;
		$logger -> write (__LINE__ . ' updateOrderMass.service_url - ' . $service_url);
		$logger -> write (__LINE__ . ' updateOrderMass.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$result = array();
		foreach (array_chunk ($data, 50) as $chunkPostdata)
		{
			APIMS::postMSData($service_url, $chunkPostdata, $resultJson, $resultArray);
			$logger -> write (__LINE__ . ' updateOrderMass.resultJson - ' . $resultJson);
			$result = array_merge ($result, $resultArray);
		}
		return $result;
	}
	// create orders mass
    public static function createOrders($data)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER;
		$logger -> write (__LINE__ . ' createOrders.service_url - ' . $service_url);
		$logger -> write (__LINE__ . ' createOrders.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$result = array();
		foreach (array_chunk ($data, 50) as $chunkPostdata)
		{
			APIMS::postMSData($service_url, $chunkPostdata, $resultJson, $resultArray);
			$logger -> write ('createOrders.resultArray - ' . json_encode ($resultArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$result = array_merge ($result, $resultArray);
		}
		return $result;
	}
	/**
	* getsOrderPositions - function find ms orders by ms filter passed with position parsing
	*
	* @filters string - ms filter 
	* @return array - result as array of orders with positions
	*/
	public static function getOrderPositions($order)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		$logger -> write (__LINE__ . ' getOrderPositions.order - ' . json_encode ($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		APIMS::getMSData($order['positions']['meta']['href'], $posJson, $positions);
		$logger -> write (__LINE__ . ' getOrderPositions.positions - ' . json_encode ($positions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		if (!isset($positions['rows'][0]))
			return false;

		foreach ($positions['rows'] as $key => $position)
		{
			APIMS::getMSData($position['assortment']['meta']['href'], $productJson, $product);
			$positions['rows'][$key]['product'] = array (
				'productType' => $position['assortment']['meta']['type'],
				'productId' => $product['id'],
				'productName' => $product['name'],
				'productCode' => $product['code'],
				'productPathName' => $product['pathName']
			);
		}
		$logger -> write (__LINE__ . ' getOrderPositions.rows - ' . json_encode ($positions['rows'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $positions['rows'];
	}
}

?>