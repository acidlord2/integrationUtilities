<?php
/**
 *
 * @class ApiOzon
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ApiOzon
{
	private $log;
	private $header;
	// organization: ullo kaori
	public function __construct($organization)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		
		date_default_timezone_set('Europe/Moscow');
		$this->log = new Log('classes - Ozon - ApiOzon.log');
		
		$clientIdDb = Db::exec_query_array ("select value from settings where code = 'ozon_client_id_" . $organization . "'");
		if (isset ($clientIdDb[0])) {
		    $clientId = $clientIdDb[0]['value'];
		}
		else {
		    $this->log->write(__LINE__ . ' не найден ozon_client_id для ' . $organization);
		}
		 
		$apiKeyDb = Db::exec_query_array ("select value from settings where code = 'ozon_api_key_" . $organization . "'");
		if (isset ($apiKeyDb[0])) {
		    $apiKey = $apiKeyDb[0]['value'];
		}
		else {
		    $this->log->write(__LINE__ . ' не найден ozon_api_key для  ' . $organization);
		}
		
		if (isset($clientId) && isset($apiKey)) {
    		$this->header = array (
    		    'Content-type: application/json',
    		    'Client-Id: ' . $clientId,
    		    'Api-Key: ' . $apiKey
    		);
		}
		else {
		    $this->header = false;
		}
	}	
		
    public function postData($url, $data)
	{
	    if (!$this->header) {
	        $this->log->write(__LINE__ . ' не настроен header');
	        return false;
		}
		
		//$logger->write ('getMSData.cache - ' . json_encode (self::$cache, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		try {
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			$info = curl_getinfo($curl);
			curl_close($curl);
			//$logger->write ('getMSData.arrayOut - ' . json_encode ($arrayOut, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		}
		catch (Exception $e)
		{
		    return false;
		}
		return $arrayOut;
	}
}

?>