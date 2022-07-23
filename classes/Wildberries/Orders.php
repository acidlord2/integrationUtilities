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

	public function orderList($startDate, $endDate, $status)
	{
	    $startDateUrl = '?date_start=' . urlencode($startDate);
	    $endDateUrl = $endDate != NULL ? '&date_end=' . urlencode($endDate) : '';
	    $statusUrl = $status != NULL ? '&status=' . $status : '';
	    
	    $url = WB_API_BASE_URL . WB_API_ORDERS . $startDateUrl . $endDateUrl . $statusUrl . '&take=1000&skip=0';
	    $this->log->write(__LINE__ . ' orderList.url - ' . $url);
	    $return = $this->apiWBClass->getData($url);
		$this->log->write(__LINE__ . ' orderList.return - ' . json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		return isset($return['orders']) ? $return['orders'] : array();
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
	
}

?>