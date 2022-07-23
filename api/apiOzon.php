<?php
/**
 *
 * @class apiOzon
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ApiOzon
{
	private static $logFilename = 'apiOzon.log';
    public static function postOzonData($service_url, $postdata, $kaori = false)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		//$logger = new Log (self::$logFilename);
		//$logger = new Log('orderozon.log'); //just passed the file name as file_name.log
		
		// REST Header
		$curl_post_header = array (
				'Content-type: application/json', 
				'Client-Id: ' . ($kaori ? OZON_CLIENT_ID_KAORI : OZON_CLIENT_ID),
				'Api-Key: ' . ($kaori ? OZON_API_KEY_KAORI : OZON_API_KEY)
		);

		try {
			//$logger->write("postOzonData.service_url - " . json_encode ($service_url));
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_header);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			//$logger->write("postOzonData.jsonOut - " . $jsonOut);
			curl_close($curl);
			$dataOut = json_decode ($jsonOut, true);
		}
		catch(Exception $e) {
			return false;
		}						
		return $dataOut;
	}

    public static function postOzonDataBlob($service_url, $postdata, $kaori = false)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		//$logger = new Log('orderozon.log'); //just passed the file name as file_name.log
		
		// REST Header
		$curl_post_header = array (
				'Content-type: application/json', 
				'Client-Id: ' . ($kaori ? OZON_CLIENT_ID_KAORI : OZON_CLIENT_ID),
				'Api-Key: ' . ($kaori ? OZON_API_KEY_KAORI : OZON_API_KEY)
		);

		try {
			//$logger->write("postOzonData.service_url - " . json_encode ($service_url));
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_header);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$out = curl_exec($curl);
			//$logger->write("postOzonData.jsonOut - " . json_encode ($jsonOut));
			curl_close($curl);
		}
		catch(Exception $e) {
			return false;
		}						
		return $out;
	}
}

?>