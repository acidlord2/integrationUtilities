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
	
	public function getSupplies()
	{
	    $url = WB_API_MARKETPLACE_API . WB_API_SUPPLIES;
		$return = $this->apiWBClass->getData($url);
	    $skip = 0;
		$return = array();
		while (true){
			$url = WB_API_MARKETPLACE_API . WB_API_SUPPLIES . '?limit=1000&skip=' . $skip;
			$this->log->write(__LINE__ . ' getSupplies.url - ' . $url);
			$response = $this->apiWBClass->getData($url);
			if (!isset($response['supplies']) || !count($response['supplies']))
				break;
			$return = array_merge($return, $response['supplies']);
			if (!isset($response['next']))
				break;
			$skip = $response['next'];
		}	    
	    $this->log->write(__LINE__ . ' getSupplies.return - ' . json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    return $return;
	}

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