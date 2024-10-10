<?php
/**
 *
 * @class SkuYandex
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class SkuYandex2
{
	private $log;
	private $apiYandexClass;
	private $campaign;
	private $businessId;

	private $cache = array ();

	public function __construct($campaign, $businessId = '')
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/apiYandex2.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
        
		$this->campaign = $campaign;
		$this->log = new Log('classes - Yandex - skuYandex2.log');
		$this->apiYandexClass = new APIYandex2($campaign);
		$this->businessId = $businessId;
	}	
	/**
	* function offerMappingEntries - function gets yandex offers
	*
	* @return array - result as array of offers
	*/
	public function offerMappingEntries()
	{
	    $pageToken = '';
	    $offers = [];
	    while (true)
	    {
	        $url = BERU_API_BASE_URL . BERU_API_VERSION . BERU_API_CAMPAIGNS . $this->campaign . '/' . BERU_API_OFFER_MAPPING_ENTRIES . '.JSON' . ($pageToken == '' ? '' : '?page_token=' . $pageToken);
    		$this->log->write(__LINE__ . ' offerMappingEntries.url - ' . $url);
    		
    		$return = $this->apiYandexClass->getData($url);
    		//$this->log->write(__LINE__ . ' offerMappingEntries.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    		if (count($return['result']['offerMappingEntries']))
    		{
    		    $offers = array_merge ($offers, $return['result']['offerMappingEntries']);
    		}
    		if (isset($return['result']['paging']['nextPageToken']))
    		{
    		    $pageToken = $return['result']['paging']['nextPageToken'];
    		}
    		else
    		{
    		    break;
    		}
	    }
	    return $offers;
	    //$logger->write("curl_response - " . $curl_response);
		
	}

	/**
	* function offerMappings - function gets yandex offers new
	*
	* @return array - result as array of offers
	*/
	public function offerMappings()
	{
	    $pageToken = '';
	    $offers = [];
	    while (true)
	    {
	        $url = BERU_API_BASE_URL . BERU_API_BUSINESSES . $this->businessId . '/' . BERU_API_OFFER_MAPPINGS . ($pageToken == '' ? '' : '?page_token=' . $pageToken);
			$data = array (
				'limit' => 200
			);
			$this->log->write(__LINE__ . ' offerMappings.url - ' . $url);
			
			$return = $this->apiYandexClass->postData($url, $data);
			//$this->log->write(__LINE__ . ' offerMappings.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if (count($return['result']['offerMappings']))
			{
			    $offers = array_merge ($offers, $return['result']['offerMappings']);
			}
			if (isset($return['result']['paging']['nextPageToken']))
			{
			    $pageToken = $return['result']['paging']['nextPageToken'];
			}
			else
			{
			    break;
			}
	    }
	    return $offers;
	    //$logger->write("curl_response - " . $curl_response);
	}

	/**
	 * function putStocks - function saves yandex stocks
	 *
	 * @return array - result as array of offers
	 */
	public function putStocks($data)
	{
	    $this->log->write(__LINE__ . ' putStocks.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = BERU_API_BASE_URL . BERU_API_VERSION . BERU_API_CAMPAIGNS . $this->campaign . '/' . BERU_API_STOCKS;
	    $this->log->write(__LINE__ . ' putStocks.url - ' . $url);
	    $return = $this->apiYandexClass->putData($url, $data);
	    $this->log->write(__LINE__ . ' putStocks.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}

	/**
	 * function putPrices - function puts yandex prices
	 *
	 * @return status - status of operation
	 */
	public function putPrices($data)
	{
	    $this->log->write(__LINE__ . ' putPrices.data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $url = BERU_API_BASE_URL . BERU_API_BUSINESSES . $this->businessId . '/' . BERU_API_PRICES;
	    $this->log->write(__LINE__ . ' putPrices.url - ' . $url);
	    $return = $this->apiYandexClass->postData($url, $data);
	    $this->log->write(__LINE__ . ' putPrices.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}
}

?>