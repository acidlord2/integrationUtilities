<?php
/**
 *
 * @class OrdersYandex
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class OrdersYandex
{
	private $log;
	private $apiYandexClass;
	private $campaign;

	private $cache = array ();

	public function __construct($campaign)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/apiYandex.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
        
		$this->campaign = $campaign;
		$this->log = new Log('classes - Yandex - ordersYandex.log');
		$this->apiYandexClass = new APIYandex($campaign);
	}	
	/**
	* function updateStatus - function find ms orders by ms filter passed
	*
	* @filters string - ms filter 
	* @return array - result as array of orders
	*/
	public function updateStatus($orderId, $status, $substatus = '')
	{
	    $url = BERU_API_BASE_URL . BERU_API_VERSION . BERU_API_CAMPAIGNS . $this->campaign . '/' . BERU_API_ORDERS . '/' . $orderId . '/' . 'status';
		$this->log->write(__LINE__ . ' updateStatus.service_url - ' . $url);
		
		$data = array (
			'order' => array (
				'status' => $status
			)
		);
		
		if ($substatus != '') {
			$data['order']['substatus'] = $substatus;
		}
		
		if ($status == 'DELIVERED' || $status == 'PICKUP') {
		    $data['order']['delivery'] = array(
		        'dates' => array(
		            'realDeliveryDate' => date('d-m-Y', strtotime('now'))
		        )
		    );
		}
		$this->log->write(__LINE__ . ' updateStatus.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$return = $this->apiYandexClass->putData($url, $data);
		$this->log->write(__LINE__ . ' updateStatus.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
		//$logger->write("curl_response - " . $curl_response);
		
	}
	/**
	* function getOrder - function get order data
	*
	* @campaign string - yandex campaign id 
	* @orderId string - yandex order id 
	* @return array - result as order object
	*/
	public function getOrder($orderId)
	{
	    $url = BERU_API_BASE_URL . BERU_API_VERSION . BERU_API_CAMPAIGNS . $this->campaign . '/' . BERU_API_ORDERS . '/' . $orderId . '.JSON';
		$this->log->write(__LINE__ . ' getOrder.url - ' . $url);
		
		$this->log->write(__LINE__ . ' getOrder.orderId - ' . $orderId);
		
		$return = $this->apiYandexClass->getData($url);
		$this->log->write(__LINE__ . ' getOrder.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
		//$logger->write("curl_response - " . $curl_response);
		
	}
	/**
	 * function getOrderBuyer - function get order buyer data
	 *
	 * @orderId string - yandex order id
	 * @return array - result as order buyer object
	 */
	public function getOrderBuyer($orderId)
	{
	    $url = BERU_API_BASE_URL . BERU_API_VERSION . BERU_API_CAMPAIGNS . $this->campaign . '/' . BERU_API_ORDERS . '/' . $orderId . '/buyer.JSON';
	    $this->log->write(__LINE__ . ' getOrderBuyer.url - ' . $url);
	    
	    $this->log->write(__LINE__ . ' getOrderBuyer.orderId - ' . $orderId);
	    
	    $return = $this->apiYandexClass->getData($url);
	    $this->log->write(__LINE__ . ' getOrderBuyer.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $return['result'];
	    //$logger->write("curl_response - " . $curl_response);
	    
	}
	/**
	* function packOrder - function pack order
	*
	* @campaign string - yandex campaign id 
	* @orderId string - yandex order id 
	* @delivery array - yandex delivary struct 
	* @return array - result as json
	*/
	public function packOrder($orderId, $delivery)
	{
		$this->log->write(__LINE__ . ' packOrder.orderId - ' . $orderId);
		
		if (isset ($delivery['shipments'][0]['id']))
		{
		    $url = BERU_API_BASE_URL . BERU_API_VERSION . BERU_API_CAMPAIGNS . $this->campaign . '/' . BERU_API_ORDERS . '/' . $orderId . '/' . BERU_API_SHIPMENTS . $delivery['shipments'][0]['id'] . '/' . BERU_API_BOXES . '.JSON';
			$this->log->write(__LINE__ . " packOrder.url - " . $url);
			$data = array (
				'boxes' => array (
					array (
						'fulfilmentId' => $orderId . '-1',
						'weight' => $delivery['shipments'][0]['weight'],
						'width' => $delivery['shipments'][0]['width'],
						'height' => $delivery['shipments'][0]['height'],
						'depth' => $delivery['shipments'][0]['depth']
					)
				)
			);
			$this->log->write(__LINE__ . ' packOrder.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			
			$return = $this->apiYandexClass->putData($url, $data);
			$this->log->write(__LINE__ . ' packOrder.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			return $return;
			//$logger->write("curl_response - " . $curl_response);
		}
	}
	/**
	 * function getOrdersLabels - function get order labels
	 *
	 * @campaign string - yandex campaign id
	 * @orderId string - yandex order id
	 * @delivery array - yandex delivary struct
	 * @return array - result as json
	 */
	public function getOrdersLabels($orders, $count)
	{
	    $this->log->write(__LINE__ . ' getOrdersLabels.orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    
	    $files = array();

	    foreach ($orders as $key => $orderId)
	    {
	        $url = BERU_API_BASE_URL . BERU_API_VERSION . BERU_API_CAMPAIGNS . $this->campaign . '/' . BERU_API_ORDERS . '/' . $orderId . '/' . BERU_API_LABELS2 . '.JSON';
	        $this->log->write(__LINE__ . " getOrdersLabels.url - " . $url);
	        $pdf = $this->apiYandexClass->getDataBlob($url);
	        $this->log->write(__LINE__ . ' orderLabel.pdf - ' . $pdf);
	        file_put_contents("files/labelsData" . $key . ".pdf", $pdf);
	        array_push ($files, "files/labelsData" . $key . ".pdf");
	    }
	    
	    $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=files/labelsData.pdf ";
	    //Add each pdf file to the end of the command
	    foreach($files as $file) {
	        $cmd .= $file . " ";
	    }
	    $result = shell_exec($cmd);
	    
	    foreach ($files as $file) {
	        unlink($file);
	    }
	 
	    return "files/labelsData.pdf";
	}
}

?>