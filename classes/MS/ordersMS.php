<?php
/**
 *
 * @class OrdersMS
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class OrdersMS
{
	private $log;
	private $apiMSClass;

	private $cache = array ();

	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

		$this->log = new Log('classes - MS - ordersMS.log');
		$this->apiMSClass = new APIMS();
	}	
	/**
	* function findOrders - function find ms orders by ms filter passed
	*
	* @filters string - ms filter 
	* @return array - result as array of orders
	*/
	public function findOrders($filters, $page = 0)
    {
		$orders = array();
		if ($page == 0){
    		$offset = 0;
		}
		else {
		    $offset = ($page - 1) * MS_LIMIT;
		}
		
		$this->log->write (__LINE__ . ' findOrders.filters - ' . json_encode ($filters, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		while (true)
		{
			$filter = '';
			if (is_array($filters))
				foreach ($filters as $key => $value)
					$filter .= $key . '=' . $value . ';';
			else
				$filter = $filters;
			$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . '?filter=' . $filter . '&limit=' . MS_LIMIT . '&offset=' . $offset;
			$this->log->write (__LINE__ . ' findOrders.service_url - ' . $service_url);
			$response_order = $this->apiMSClass->getData($service_url);
			$offset += MS_LIMIT;
			$orders = array_merge ($orders, $response_order['rows']);
			if ($offset >= $response_order['meta']['size'] || $page != 0)
			{
			    $size = $response_order['meta']['size'];
			    $limit = $response_order['meta']['limit'];
			    break;			
			}
		}
       
		if ($page == 0) {
		    $return = $orders;
		}
		else {
		    $return = array(
		        'orders' => $orders,
		        'size' => $size,
		        'limit' => $limit
		    );
		}
		
		$this->log->write (__LINE__ . ' findOrders.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
	}
	
	public function createCustomerorder($data)
	{
	    $this->log->write(__LINE__ . ' createCustomerorder.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER;
		$return = $this->apiMSClass->postData ($service_url, $data);
		$this->log->write(__LINE__ . ' createCustomerorder.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
		//$logger->write("curl_response - " . $curl_response);
		
	}
	public function updateCustomerorder($id, $data)
	{
	    $this->log->write(__LINE__ . ' updateCustomerorder.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . '/' . $id;
		$return = $this->apiMSClass->putData ($service_url, $data);
		$this->log->write(__LINE__ . ' updateCustomerorder.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
		//$logger->write("curl_response - " . $curl_response);
		
	}
	
	public function findOrdersByNames($names)
	{
	    $this->log->write (__LINE__ . ' findOrdersByNames.names - ' . json_encode ($names, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $orders = array();
	    $filter = '';
	    foreach ($names as $key => $value)
	    {
	        $filter .= 'name=' . $value . ';';
	        if ($key + 1 == count ($names) || ($key + 1) % 50 == 0)
	        {
	            $service_url = MS_COURL . '?filter=' . $filter . '&limit=' . MS_LIMIT;
	            $this->log->write (__LINE__ .  'findOrdersByNames.service_url - ' . $service_url);
	            $msOrdersArray = $this->apiMSClass->getData($service_url);
	            if (isset ($msOrdersArray['rows'][0])) {
	                $orders = array_merge ($orders, $msOrdersArray['rows']);
	            }
	            else
	                $this->log->write (__LINE__ . ' findOrdersByNames.msOrdersArray - ' . json_encode ($msOrdersArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	                
	                $filter = '';
	        }
	    }
	    
	    //$this->log->write ('findOrdersByNames.orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $orders;
	}
	public function getOrderPositions($order) 
	{
	    $this->log->write(__LINE__ . ' getOrderPositions.order - ' . $order);
	    $url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . '/' . $order . MS_API_CUSTOMERORDER_POSITIONS;
	    $this->log->write(__LINE__ . ' getOrderPositions.url - ' . $url);
	    $return = $this->apiMSClass->getData($url);
	    if (isset($return['rows'])) {
	        return $return['rows'];
	    }
	    else {
	        return false;
	    }
	}
}

?>