<?php
/**
 *
 * @class SkuYandex
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class SkuYandex
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
		$this->log = new Log('classes - Yandex - skuYandex.log');
		$this->apiYandexClass = new APIYandex($campaign);
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
    		$this->log->write(__LINE__ . ' offerMappingEntries.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
	    $logger->write("curl_response - " . $curl_response);
		
	}
	/**
	 * function offerMappingEntries - function gets yandex offers
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
	    
}

?>