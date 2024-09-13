<?php
namespace Classes\Wildberries\v1;
/**
 *
 * @class Supplies
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Supplies
{
	private $log;
	private $apiWBClass;
	private $shop;
	
	public function __construct($shop)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Api.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

		$this->log = new \Classes\Common\Log('classes - Wildberries - Supplies.log');
		$this->apiWBClass = new \Classes\Wildberries\v1\Api($shop);
		$this->shop = $shop;
	}	

	// public function getNewOrders($startDate = null, $endDate = null)
	// {
	//     $startDateUrl = $startDate != NULL ? '&date_start=' . urlencode($startDate) : '';
	//     $endDateUrl = $endDate != NULL ? '&date_end=' . urlencode($endDate) : '';
	//     $skip = 0;
	// 	$return = array();
	// 	while (true){
	// 		$url = WB_API_MARKETPLACE_API . WB_API_ORDERS_NEW . '?' . $startDateUrl . $endDateUrl  . '&take=1000&skip=' . $skip;
	// 		$this->log->write(__LINE__ . ' getNewOrders.url - ' . $url);
	// 		$response = $this->apiWBClass->getData($url);
	// 		$this->log->write(__LINE__ . ' getNewOrders.response - ' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	// 		if (!isset($response['orders']) || !count($response['orders']) || !isset($response['next']))
	// 		{
	// 			break;
	// 		}
	// 		$return = array_merge($return, $response['orders']);
	// 		$skip = $response['next'];
	// 	}
	// 	return isset($return) ? $return : array();
	// }

	// public function orderList($startDate, $endDate, $status)
	// {
	//     $startDateUrl = '?date_start=' . urlencode($startDate);
	//     $endDateUrl = $endDate != NULL ? '&date_end=' . urlencode($endDate) : '';
	//     $statusUrl = $status != NULL ? '&status=' . $status : '';
	    
	//     $url = WB_API_BASE_URL . WB_API_ORDERS . $startDateUrl . $endDateUrl . $statusUrl . '&take=1000&skip=0';
	//     $this->log->write(__LINE__ . ' orderList.url - ' . $url);
	//     $return = $this->apiWBClass->getData($url);
	// 	$this->log->write(__LINE__ . ' orderList.return - ' . json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	// 	return isset($return['orders']) ? $return['orders'] : array();
	// }
	
	public function createSupply($name)
	{
	    $this->log->write(__LINE__ . ' createSupply.name - ' . $name);
	    $url = WB_API_MARKETPLACE_API . WB_API_SUPPLIES;
	    $payload = array(
	        'name' => $name
	    );
		$return = $this->apiWBClass->postData($url, $payload);
	    
	    $this->log->write(__LINE__ . ' createSupply.return - ' . json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    return $return;
	}

	public function addOrderToSupply($supplyId, $orderId)
	{
	    $this->log->write(__LINE__ . ' addOrderToSupply.supplyId - ' . $supplyId);
	    $this->log->write(__LINE__ . ' addOrderToSupply.orderId - ' . $orderId);
	    $url = WB_API_MARKETPLACE_API . WB_API_SUPPLIES . '/' . $supplyId . '/orders/' . $orderId;
		$return = $this->apiWBClass->patchData($url, array());
	    
	    $this->log->write(__LINE__ . ' addOrderToSupply.return - ' . json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    return $return;
	}
	
}

?>