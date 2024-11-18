<?php
/**
 *
 * @class OrdersOzon
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class OrdersOzon
{
	private $log;
	private $apiOzonClass;
	private $organization;
	
	// organization: ullo kaori
	public function __construct($organization) {
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/ApiOzon.php');
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
        $this->organization = $organization;
        $this->log = new Log('classes - Ozon - OrderOzon.log');
        $this->apiOzonClass = new ApiOzon($organization);
	}
    /*
     * 
     */
	public function findOrders ($since, $to, $status, $wearhouse)
	{
	    
	    $postData = array ('dir' => 'ASC',
	        'filter' => array ('since' => $since, 'to' => $to, 'status' => $status, 'warehouse_id' => (is_array($wearhouse) ? $wearhouse : array ($wearhouse))),
	        'limit' => 50,
	        'offset' => 0,
	        'with' => array ('barcodes' => true));
	    $orders = array();
	    while (true)
	    {
	        $url = OZON_MAINURL . OZON_API_V3 . OZON_API_ORDERS_LIST;
	        $this->log->write(__LINE__ . " findOrders.url - " . json_encode ($url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	        $this->log->write(__LINE__ . " findOrders.postData - " . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	        $order_list = $this->apiOzonClass->postData($url, $postData);
	        if (!isset($order_list['result']) || count ($order_list['result']['postings']) == 0)
	            break;
	            $orders = array_merge($orders, $order_list['result']['postings']);
	            $postData['offset'] += 50;
	    }
	    $this->log->write(__LINE__ . " findOrders.orders - " . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $orders;
	}
	/*
	 *
	 */
	public function getOrder ($postingNumber, $barcodes = true, $analyticsData = false, $financialData = false)
	{
	    
	    $postData = array(
	        'posting_number' => $postingNumber,
	        'with' => array (
	            'analytics_data' => $analyticsData,
	            'barcodes' => $barcodes,
	            'financial_data' => $financialData
	        )
	    );
	    
        $url = OZON_MAINURL . OZON_API_V3 . OZON_API_ORDER_GET;
	        
        $this->log->write(__LINE__ . " getOrder.url - " . json_encode ($url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $this->log->write(__LINE__ . " getOrder.postData - " . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $order = $this->apiOzonClass->postData($url, $postData);
	    $this->log->write(__LINE__ . " getOrder.order - " . json_encode ($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $order['result'];
	}

	/*
	 * 
	 */
	public function setExemplar($data)
	{
	    $this->log->write(__LINE__ . ' setExemplar.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    
	    $postdata = array(
			array (
				'products' => array ()
			),
	        'posting_number' => $data['posting_number']
	    );
	    
	    if (isset($data['products']))
	        foreach ($data['products'] as $product)
				$exemplars = []; 
				for ($e = 1; $e <= $product['quantity']; $e++)
					$exemplars[] = array(
						"exemplar_id" => $e,
						"is_gtd_absent" => true
					);						;
		$postdata['packages'][0]['products'][] = array ('quantity' => $product['quantity'], 'product_id' => $product['sku'], 'exemplars' => $exemplars);
		
		$this->log->write(__LINE__ . ' setExemplar.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$url = OZON_MAINURL . OZON_API_V5 . OZON_API_EXEMPLAR_SET;
		$i = 0;
		while (true)
		{
			$i += 1;
			$return = $this->apiOzonClass->postData ($url, $postdata);
			$this->log->write(__LINE__ . ' setExemplar.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if (isset ($return['error']) && $i < 3)
			{
				usleep (500000);
				continue;
			}
			break;
		}
		return $return;
		//$logger->write("curl_response - " . $curl_response);
	}

	/*
	 * 
	 */
	public function checkSetExemplarStatus($postingNumber)
	{
	    $this->log->write(__LINE__ . ' checkSetExemplarStatus.postingNumber - ' . json_encode ($postingNumber, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    
	    $postdata = array(
	        'posting_number' => $postingNumber
	    );
	    
		$this->log->write(__LINE__ . ' checkSetExemplarStatus.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$url = OZON_MAINURL . OZON_API_V4 . OZON_API_EXEMPLAR_STATUS;
		$i = 0;
		while (true)
		{
			$i += 1;
			$return = $this->apiOzonClass->postData ($url, $postdata);
			$this->log->write(__LINE__ . ' checkSetExemplarStatus.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if (isset ($return['error']) && $i < 3)
			{
				usleep (500000);
				continue;
			}
			break;
		}
		return $return;
		//$logger->write("curl_response - " . $curl_response);
	}

	/*
	 * 
	 */
	public function packOrder($data)
	{
	    $this->log->write(__LINE__ . ' packOrder.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    
	    $postdata = array(
	        'packages' => array (
	            array (
	                'products' => array ()
	            )
	        ),
	        'posting_number' => $data['posting_number']
	    );
	    
	    if (isset($data['products']))
	        foreach ($data['products'] as $product)
	            $postdata['packages'][0]['products'][] = array ('quantity' => $product['quantity'], 'product_id' => $product['sku']);
	            
		$this->log->write(__LINE__ . ' packOrder.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$url = OZON_MAINURL . OZON_API_V4 . 'posting/fbs/ship';
		$i = 0;
		while (true)
		{
			$i += 1;
			$return = $this->apiOzonClass->postData ($url, $postdata);
			$this->log->write(__LINE__ . ' packOrder.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if (isset ($return['error']) && $i < 3)
			{
				usleep (500000);
				continue;
			}
			break;
		}
		return $return;
		//$logger->write("curl_response - " . $curl_response);
	}

	/*
	 *
	 */
	public function onDeliveryOrder($data)
	{
	    $this->log->write(__LINE__ . ' onDeliveryOrder.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    
	    $postdata = array ();
	    if (is_array($data)) {
	        $postdata['posting_number'] = $data;
	    }
	    else {
	        $postdata['posting_number'] = array($data);
	    }
	    
	    $this->log->write(__LINE__ . ' onDeliveryOrder.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = OZON_MAINURL . OZON_API_V2 . OZON_API_DELIVERING_STATUS;
        $i = 0;
        while (true)
        {
            $i += 1;
            $return = $this->apiOzonClass->postData ($url, $postdata);
            $this->log->write(__LINE__ . ' onDeliveryOrder.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            if (isset ($return['error']) && $i < 3)
            {
                usleep (500000);
                continue;
            }
            break;
        }
        return $return;
	            
	}
	/*
	 *
	 */
	public function lastMileOrder($data)
	{
	    $this->log->write(__LINE__ . ' lastMileOrder.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    
	    $postdata = array ();
	    if (is_array($data)) {
	        $postdata['posting_number'] = $data;
	    }
	    else {
	        $postdata['posting_number'] = array($data);
	    }
	    
	    $this->log->write(__LINE__ . ' lastMileOrder.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = OZON_MAINURL . OZON_API_V2 . OZON_API_LASTMILE_STATUS;
	    $i = 0;
	    while (true)
	    {
	        $i += 1;
	        $return = $this->apiOzonClass->postData ($url, $postdata);
	        $this->log->write(__LINE__ . ' lastMileOrder.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	        if (isset ($return['error']) && $i < 3)
	        {
	            usleep (500000);
	            continue;
	        }
	        break;
	    }
	    return $return;
	    
	}
	/*
	 *
	 */
	public function deliverOrder($data)
	{
	    $this->log->write(__LINE__ . ' deliverOrder.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    
	    $postdata = array ();
	    if (is_array($data)) {
	        $postdata['posting_number'] = $data;
	    }
	    else {
	        $postdata['posting_number'] = array($data);
	    }
	    
	    $this->log->write(__LINE__ . ' deliverOrder.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = OZON_MAINURL . OZON_API_V2 . OZON_API_DELIVERED_STATUS;
        $i = 0;
        while (true)
        {
            $i += 1;
            $return = $this->apiOzonClass->postData ($url, $postdata);
            $this->log->write(__LINE__ . ' deliverOrder.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            if (isset ($return['error']) && $i < 3)
            {
                usleep (500000);
                continue;
            }
            break;
        }
        return $return;
	            
	}
	/*
	 *
	 */
	public function cancelOrder($data)
	{
	    $this->log->write(__LINE__ . ' cancelOrder.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    
	    $url = OZON_MAINURL . OZON_API_V2 . OZON_API_CANCELLED_STATUS;
	    $i = 0;
	    while (true)
	    {
	        $i += 1;
	        $return = $this->apiOzonClass->postData ($url, $data);
	        $this->log->write(__LINE__ . ' cancelOrder.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	        if (isset ($return['error']) && $i < 3)
	        {
	            usleep (500000);
	            continue;
	        }
	        break;
	    }
	    return $return;
	    
	}
}

?>