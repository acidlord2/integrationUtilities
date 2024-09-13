<?php
namespace Classes\Wildberries\v1;
/**
 *
 * @class ProductsMS
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Products
{
	private $log;
	private $apiWBClass;
	private $shop;
	
	public function __construct($shop)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Api.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

		$this->log = new \Classes\Common\Log('classes - Wildberries - Products.log');
		$this->apiWBClass = new \Classes\Wildberries\v1\Api($shop);
		$this->shop = $shop;
	}	

	public function getCardsList()
	{
	    $postData = array(
			'settings' => array(
				'cursor' => array(
					'limit' => 100
				),
				'filter' => array(
					'withPhoto' => -1
	            )
	        )
	    );
		$return = array();
	    $url = WB_API_CONTENT_API . WB_API_CARDS_LIST;
	    while(true)
		{
			$response = $this->apiWBClass->postData($url, $postData);
			$this->log->write(__LINE__ . ' getCardsList.response - ' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			if (!isset($response['result']['cards']) || !count($response['result']['cards']) || !isset($response['result']['cursor']['next']))
			{
				break;
			}
		}

		$return = $this->apiWBClass->postData($url, $postData);
		return isset($return['result']['cards'][0]) ? $return['result']['cards'] : array();
	}
	
	public function setPrices($data)
	{
	    $this->log->write(__LINE__ . ' setPrices.data - ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    $url = WB_API_BASE_URL . WB_API_PRICES;
	    $return = array();
	    foreach (array_chunk($data, 1000) as $chunk)
	    {
	        $arrayOut = $this->apiWBClass->postData($url, $chunk);
	        $return = array_merge($return, $arrayOut);
	    }
	    
	    $this->log->write(__LINE__ . ' setPrices.return - ' . json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    return $return;
	}

	public function setStock($data)
	{
	    $this->log->write(__LINE__ . ' setStock.data - ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    $url = WB_API_BASE_URL . WB_API_STOCK;
	    $return = array();
	    foreach (array_chunk($data, 1000) as $chunk)
	    {
	        $arrayOut = $this->apiWBClass->postData($url, $chunk);
	        $return = array_merge($return, $arrayOut);
	    }
	    
	    $this->log->write(__LINE__ . ' setStock.return - ' . json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    return $return;
	}

	public function setDiscounts($data)
	{
	    $this->log->write(__LINE__ . ' setDiscounts.data - ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    $url = WB_API_BASE_URL . WB_API_DISCOUNTS;
	    $return = array();
	    foreach (array_chunk($data, 1000) as $chunk)
	    {
	        $arrayOut = $this->apiWBClass->postData($url, $chunk);
	        $return = array_merge($return, $arrayOut);
	    }
	    
	    $this->log->write(__LINE__ . ' setDiscounts.return - ' . json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    return $return;
	}
	
}

?>