<?php
/**
 *
 * @class Order
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace Classes\MS\v2;

class Assortment
{
	private $log;
	private $apiMSClass;
	private $mem;
	
	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/Api-v2.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

		$this->log = new \Classes\Common\Log('classes - MS - Assortment-v2.log');
		$this->apiMSClass = new \Classes\MS\v2\Api();
		$this->mem  = new \Memcached();
	}	

	public function updateAssortment()
	{
	    
	    $offset = 0;
	    $products = array();
	    while (true)
	    {
	        $url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_ASSORTMENT . '?stockMode=all;quantityMode=all;&offset=' . $offset;
	        $assortment = $this->apiMSClass->getData($url);
	        $this->log->write(__LINE__ . ' updateAssortment.url - ' . $url);
	        $products = array_merge($products, $assortment['rows']);
	        $offset += $assortment['meta']['limit'];
	        
	        if ($offset > $assortment['meta']['size'] || !isset ($assortment['meta']['size']))
	            break;
	    }
	    
	    //$this->apiMSClass->setCache ('assortment', $products);
	    $return = $this->mem->add('assortment', $products);
	    $this->log->write(__LINE__ . ' updateAssortment.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $return;
	}
	public function getAssortment($codes = false)
	{
	    $this->log->write(__LINE__ . ' getAssortment.codes - ' . json_encode ($codes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $assortment = $this->mem->get('assortment');
		
	    $this->log->write(__LINE__ . ' getAssortment.assortment - ' . json_encode ($assortment, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $assortment;
		//$logger->write("curl_response - " . $curl_response);
		
	}
}

?>