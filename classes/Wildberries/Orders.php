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
			$httpCode = (int)$this->apiWBClass->getLastHttpCode();
			// an expired token answers 401 and used to look exactly like "no new orders"
			if ($httpCode !== 200)
				$this->log->write(__LINE__ . ' getNewOrders.FAILED shop=' . $this->shop . ' http=' . $httpCode . ' response - ' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
		$response = $this->apiWBClass->postData($url, $postData);
	    $this->log->write(__LINE__ . ' getStickers.response - ' . json_encode($this->stripStickerFiles($response), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    return $response;
	}

	/**
	 * Requests stickers for a list of assembly tasks and returns them indexed by orderId.
	 * WB accepts up to 100 orders per request, so ids are sent in batches instead of
	 * one request per order (one request per order burns the seller rate limit and gets 429).
	 * Orders that WB does not return a sticker for are retried with an increasing delay:
	 * right after an order is added to a supply the sticker may not be generated yet.
	 *
	 * @param array $ordersID - WB order ids
	 * @param int $maxAttempts - how many passes over the still missing ids
	 * @return array - map orderId => sticker
	 */
	public function getStickersMap($ordersID, $maxAttempts = 4)
	{
	    $stickers = array();
	    $pending = array();
	    foreach ($ordersID as $orderID)
	        $pending[(int)$orderID] = (int)$orderID;
	    $pending = array_values($pending);

	    if (!count($pending))
	        return $stickers;

	    $delayUs = 2000000;
	    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++)
	    {
	        foreach (array_chunk($pending, 100) as $chunkNumber => $chunk)
	        {
	            if ($chunkNumber > 0)
	                usleep(500000);

	            $response = $this->getStickers($chunk);

	            if (isset($response['stickers']) && is_array($response['stickers']))
	                foreach ($response['stickers'] as $sticker)
	                    if (isset($sticker['orderId']) && isset($sticker['file']))
	                        $stickers[(int)$sticker['orderId']] = $sticker;

	            if (isset($response['status']) && (int)$response['status'] === 429)
	                $this->log->write(__LINE__ . ' getStickersMap.rateLimit attempt=' . $attempt . ' orders=' . count($chunk));
	        }

	        $pending = array_values(array_diff($pending, array_keys($stickers)));
	        if (!count($pending))
	            break;

	        if ($attempt < $maxAttempts)
	        {
	            $this->log->write(__LINE__ . ' getStickersMap.missing attempt=' . $attempt . ' delayUs=' . $delayUs . ' orders - ' . json_encode($pending, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	            usleep($delayUs);
	            $delayUs = min($delayUs * 2, 15000000);
	        }
	    }

	    if (count($pending))
	        $this->log->write(__LINE__ . ' getStickersMap.failed orders - ' . json_encode($pending, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

	    $this->log->write(__LINE__ . ' getStickersMap.received - ' . count($stickers) . ' of ' . (count($stickers) + count($pending)));
	    return $stickers;
	}

	/**
	 * Sticker png is base64 in the response - replace it with its size so the log stays readable.
	 */
	private function stripStickerFiles($response)
	{
	    if (!is_array($response) || !isset($response['stickers']) || !is_array($response['stickers']))
	        return $response;

	    foreach ($response['stickers'] as &$sticker)
	        if (isset($sticker['file']))
	            $sticker['file'] = 'base64:' . strlen($sticker['file']);
	    unset($sticker);

	    return $response;
	}

}

?>