<?php
/**
 *
 * @class OrdersMS
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace MS\Cusomerorder\Position;

class Position
{
	private $logger;
	private $apiMSClass;

	private $cache = array ();

	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

		date_default_timezone_set('Europe/Moscow');
		$this->logger = new Log('classes-ms-ordersMS.log');
		$this->apiMSClass = new APIMS();
	}	
	/**
	* function findOrders - function find ms orders by ms filter passed
	*
	* @filters string - ms filter 
	* @return array - result as array of orders
	*/
	public function findOrders($filters)
    {
		$orders = array();
		$offset = 0;
		$this->logger->write ('01-findOrders.filters - ' . json_encode ($filters, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		while (true)
		{
			$filter = '';
			if (is_array($filters))
				foreach ($filters as $key => $value)
					$filter .= $key . '=' . $value . ';';
			else
				$filter = $filters;
			$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . '?filter=' . $filter . '&limit=' . MS_LIMIT . '&offset=' . $offset;
			$this->logger->write ('02-findOrders.service_url - ' . $service_url);
			$response_order = $this->apiMSClass->getData($service_url);
			$offset += MS_LIMIT;
			$orders = array_merge ($orders, $response_order['rows']);
			if ($offset >= $response_order['meta']['size'])
				break;			
		}

		$this->logger->write ('03-findOrders.orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $orders;
	}
	
	public function createCustomerorder($data)
	{
		$this->logger->write("01-createCustomerorder.data - " . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER;
		$return = $this->apiMSClass->postData ($service_url, $data);
		$this->logger->write("02-createCustomerorder.return - " . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
		//$logger->write("curl_response - " . $curl_response);
		
	}
	public function updateCustomerorder($id, $data)
	{
		$this->logger->write("01-updateCustomerorder.data - " . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . '/' . $id;
		$return = $this->apiMSClass->putData ($service_url, $data);
		$this->logger->write("02-updateCustomerorder.return - " . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
		//$logger->write("curl_response - " . $curl_response);
		
	}
}

?>