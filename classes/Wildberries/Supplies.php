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
	    $next = 0;
		$return = array();
		while (true){
			$url = WB_API_MARKETPLACE_API . WB_API_SUPPLIES . '?limit=1000&next=' . $next;
			$this->log->write(__LINE__ . ' getSupplies.url - ' . $url);
			$response = $this->apiWBClass->getData($url);
			$httpCode = (int)$this->apiWBClass->getLastHttpCode();
			// an expired token answers 401 and used to look exactly like "no supplies"
			if ($httpCode !== 200)
				$this->log->write(__LINE__ . ' getSupplies.FAILED shop=' . $this->shop . ' http=' . $httpCode . ' response - ' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			if (!isset($response['supplies']) || !count($response['supplies']))
				break;
			$return = array_merge($return, $response['supplies']);
			if (!isset($response['next']))
				break;
			$next = $response['next'];
		}	    
	    $this->log->write(__LINE__ . ' getSupplies.return - ' . json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    return $return;
	}

	/**
	 * Returns every supply that is still open, B2B ones included.
	 * Orders cannot be added to a B2B supply - WB fills those itself - but their orders
	 * are in status confirm and do have stickers, so they still matter for the sticker pass.
	 *
	 * @return array - list of supplies
	 */
	public function getOpenSupplies()
	{
	    $return = array();
	    foreach ($this->getSupplies() as $supply)
	    {
	        if ($supply['closedAt'] !== null)
	            continue;

	        $this->log->write(__LINE__ . ' getOpenSupplies.open - ' . $supply['id'] . ' isB2b=' . json_encode(!empty($supply['isB2b'])) . ' ' . $supply['name']);
	        $return[] = $supply;
	    }

	    if (!count($return))
	        $this->log->write(__LINE__ . ' getOpenSupplies.none');

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

	    // WB answers with the id only - keep the shape of a supply from getSupplies()
	    if (isset($return['id']))
	        $return = array_merge(array('name' => $name, 'closedAt' => null, 'done' => false), $return);

	    return $return;
	}

	/**
	 * Returns ids of the assembly tasks attached to the supply.
	 * /orders/new does not return an order once it is in a supply, so this is how
	 * an order whose sticker was not saved earlier can still be found.
	 *
	 * @param string $supplyId
	 * @return array - list of order ids
	 */
	public function getSupplyOrderIds($supplyId)
	{
	    $url = WB_API_MARKETPLACE_API . WB_API_SUPPLIES_MARKETPLACE . '/' . $supplyId . '/order-ids';
	    $this->log->write(__LINE__ . ' getSupplyOrderIds.url - ' . $url);
	    $response = $this->apiWBClass->getData($url);
	    $return = isset($response['orderIds']) && is_array($response['orderIds']) ? $response['orderIds'] : array();
	    $this->log->write(__LINE__ . ' getSupplyOrderIds.count - ' . count($return));
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

	/**
	 * Attaches assembly tasks to the supply, 100 per request as WB allows.
	 * This is what moves an order from status new to confirm - without it WB has no
	 * sticker for the order at all, so a failure here must not pass unnoticed.
	 *
	 * @param string $supplyId
	 * @param array $orders - WB order ids
	 * @return array - ids WB accepted
	 */
	public function addOrdersToSupply($supplyId, $orders)
	{
	    $this->log->write(__LINE__ . ' addOrdersToSupply.supplyId - ' . $supplyId);
	    $url = WB_API_MARKETPLACE_API . WB_API_SUPPLIES_MARKETPLACE . '/' . $supplyId . '/orders';
		$added = array();
		foreach (array_chunk($orders, 100) as $orders_chunk){
		    $payload = array(
		        'orders' => $orders_chunk
		    );
		    $this->log->write(__LINE__ . ' addOrdersToSupply.payload - ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		    $return_temp = $this->apiWBClass->patchData($url, $payload);
		    $httpCode = (int)$this->apiWBClass->getLastHttpCode();

		    if ($httpCode === 204)
		    {
		        $added = array_merge($added, $orders_chunk);
		        $this->log->write(__LINE__ . ' addOrdersToSupply.ok - ' . count($orders_chunk) . ' orders');
		    }
		    else
		    {
		        $this->log->write(__LINE__ . ' addOrdersToSupply.FAILED http=' . $httpCode
		            . ' supplyId=' . $supplyId
		            . ' orders=' . count($orders_chunk)
		            . ' response - ' . json_encode($return_temp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		    }

			usleep(500000);
		}
	    return $added;
	}
}

?>