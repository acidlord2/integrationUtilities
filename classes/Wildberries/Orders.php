<?php
namespace Classes\Wildberries\v1;
/**
 *
 * @class ProductsMS
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Orders
{
	private $log;
	private $apiWBClass;
	private $shop;
	
	public function __construct($shop)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Api.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

		$this->log = new \Classes\Common\Log('classes - Wildberries - Orders.log');
		$this->apiWBClass = new \Classes\Wildberries\v1\Api($shop);
		$this->shop = $shop;
	}	

	public function getNewOrders($startDate = null, $endDate = null)
	{
	    $startDateUrl = $startDate != NULL ? '&date_start=' . urlencode($startDate) : '';
	    $endDateUrl = $endDate != NULL ? '&date_end=' . urlencode($endDate) : '';
	    $skip = 0;
		$return = array();
		while (true){
			$url = WB_API_MARKETPLACE_API . WB_API_ORDERS_NEW . '?' . $startDateUrl . $endDateUrl  . '&take=1000&skip=' . $skip;
			$this->log->write(__LINE__ . ' getNewOrders.url - ' . $url);
			$response = $this->apiWBClass->getData($url);
			if (!isset($response['orders']) || !count($response['orders']))
				break;
			$return = array_merge($return, $response['orders']);
			if (!isset($response['next']))
				break;
			$skip = $response['next'];
		}
		$this->log->write(__LINE__ . ' getNewOrders.return - ' . json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		return $return;
	}
	
	public function changeOrdersStatus($data)
	{
	    $this->log->write(__LINE__ . ' changeOrdersStatus.data - ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    $url = WB_API_BASE_URL . WB_API_ORDERS;
	    $return = array();
	    foreach (array_chunk($data, 1000) as $chunk)
	    {
	        $arrayOut = $this->apiWBClass->putData($url, $chunk);
	        $return = array_merge($return, $arrayOut);
	    }
	    
	    $this->log->write(__LINE__ . ' changeOrdersStatus.return - ' . json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    return $return;
	}

	public function getStickers($ordersID)
	{
	    $url = WB_API_MARKETPLACE_API . WB_API_ORDERS . '/' . WB_API_STICKERS;
	    $this->log->write(__LINE__ . ' getStickers.url - ' . $url);
	    $this->log->write(__LINE__ . ' getStickers.ordersID - ' . json_encode($ordersID, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    $postData = array(
	        'orders' => $ordersID
	    );
	    $this->log->write(__LINE__ . ' getStickers.postData - ' . json_encode($postData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		$response = $this->apiWBClass->postData($url, $postData);
	    $this->log->write(__LINE__ . ' getStickers.response - ' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    return $response;
	}
	
}

?>