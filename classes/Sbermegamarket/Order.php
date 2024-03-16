<?php
/**
 *
 * @class Order
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace Classes\Sbermegamarket;

class Order
{
	private $log;
	private $apiSMMClass;

	public function __construct($shop = '4824')
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sbermegamarket/Api.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
        
		$this->log = new \Log('classes - Sbermegamarket - Orders.log');
		$this->apiSMMClass = new \Classes\Sbermegamarket\API($shop);
	}

	public function searchOrders($statuses, $dateFrom = null, $dateTo = null)
	{
	    $this->log->write(__LINE__ . ' searchOrders.statuses - ' . json_encode ($statuses, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SBMM_API_BASE_URL . SBMM_API_MARKET . SBMM_API_VERSION_V1 . SBMM_API_ORDERS_SEARCH;

	    $data = array (
	        'data' => array (
	            'statuses' => $statuses,
				'count' => 1000
	        )
	    );
		if($dateFrom !== null)
			$data['data']['dateFrom'] = $dateFrom;
		if($dateTo !== null)
			$data['data']['dateTo'] = $dateTo;
	    
	    $orders = $this->apiSMMClass->postData($url, $data);
	    $this->log->write(__LINE__ . ' searchOrders.orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    if (isset($orders['success']) && $orders['success'] == 1)
			return $orders['data'];
		else
			return array('shipments' => array());
	}

	public function getOrders($orderNumbers)
	{
	    $this->log->write(__LINE__ . ' getOrders.orderNumbers - ' . json_encode ($orderNumbers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SBMM_API_BASE_URL . SBMM_API_MARKET . SBMM_API_VERSION_V1 . SBMM_API_ORDERS_GET;
	    if (is_array($orderNumbers)) {
	        $ordersNumbersArray = $orderNumbers;
	    }
	    else {
	        $ordersNumbersArray = array ($orderNumbers);
	    }
	    $data = array (
	        'data' => array (
	            'shipments' => $ordersNumbersArray
	        )
	    );
	    
	    $orders = $this->apiSMMClass->postData($url, $data);
	    $this->log->write(__LINE__ . ' getOrders.orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    if (isset($orders['success']) && $orders['success'] == 1)
			return $orders['data'];
		else
			return array('shipments' => array());
	}
	
	public function packing($data)
	{
	    $this->log->write(__LINE__ . ' packing.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SBMM_API_BASE_URL . SBMM_API_MARKET . SBMM_API_VERSION_V1 . SBMM_API_ORDERS_PACKING;
	    $return = $this->apiSMMClass->postData($url, $data);
	    $this->log->write(__LINE__ . ' packing.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $return;
	}
	
	public function confirm($data)
	{
	    $this->log->write(__LINE__ . ' confirm.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SBMM_API_BASE_URL . SBMM_API_MARKET . SBMM_API_VERSION_V1 . SBMM_API_ORDERS_CONFIRM;
	    $return = $this->apiSMMClass->postData($url, $data);
	    $this->log->write(__LINE__ . ' confirm.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $return;
	}

	public function reject($data)
	{
	    $this->log->write(__LINE__ . ' reject.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SBMM_API_BASE_URL . SBMM_API_MARKET . SBMM_API_VERSION_V1 . SBMM_API_ORDERS_REJECT;
	    $return = $this->apiSMMClass->postData($url, $data);
	    $this->log->write(__LINE__ . ' reject.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $return;
	}
	
	public function cancelResult($data)
	{
	    $this->log->write(__LINE__ . ' cancelResult.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SBMM_API_BASE_URL . SBMM_API_MARKET . SBMM_API_VERSION_V1 . SBMM_API_ORDERS_CANCELRESULT;
	    $return = $this->apiSMMClass->postData($url, $data);
	    $this->log->write(__LINE__ . ' cancelResult.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $return;
	}
	
	public function close($data)
	{
	    $this->log->write(__LINE__ . ' close.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SBMM_API_BASE_URL . SBMM_API_MARKET . SBMM_API_VERSION_V1 . SBMM_API_ORDERS_CLOSE;
	    $return = $this->apiSMMClass->postData($url, $data);
	    $this->log->write(__LINE__ . ' close.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $return;
	}

	public function updatePrices($data)
	{
	    $this->log->write(__LINE__ . ' updatePrices.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SBMM_API_BASE_URL . SBMM_API_MERCHANTINTEGRATION . SBMM_API_VERSION_V1 . SBMM_API_PRICE_SAVE;
	    $return = $this->apiSMMClass->postData($url, $data);
	    $this->log->write(__LINE__ . ' updatePrices.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $return;
	}

	public function updateStock($data)
	{
	    $this->log->write(__LINE__ . ' updateStock.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = SBMM_API_BASE_URL . SBMM_API_MERCHANTINTEGRATION . SBMM_API_VERSION_V1 . SBMM_API_STOCK_UPDATE;
	    $return = $this->apiSMMClass->postData($url, $data);
	    $this->log->write(__LINE__ . ' updateStock.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $return;
	}
}

?>