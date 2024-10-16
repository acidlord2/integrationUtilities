<?php
/**
 *
 * @class APIBeru
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class APIBeru2
{
	private static $oauth_client_id = false;
	private static $oauth_token = false;
	private static $logFilename = 'classes - apiBeru.log';

    public static function getBeruData($campaign, $service_url, &$jsonOut, &$arrayOut)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$log = new Log (self::$logFilename);
		
		if (!self::$oauth_token || !self::$oauth_client_id)
		{
			// Fetch parameter beru_oauth_token
			$result = Db::exec_query ("select value from settings where code = 'beru_oauth_token_" . $campaign . "'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$oauth_token = $row['value'];
			}
			else
				die("No settings parameter 'beru_oauth_token'");
			
			mysqli_free_result($result);
		}
		
		// REST Header
		$curl_post_headerberu = array (
				'Content-type: application/json', 
				'Api-Key: ' . self::$oauth_token
		);

		try {
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerberu);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);

    		if (curl_errno($curl))
    		{
    		    $log->write(__LINE__ . ' Error No: ' . curl_errno($curl) . ' | Error msg: ' . curl_error($curl));
    		}
			curl_close($curl);

		}
		catch(Exception $e) {
			return false;
		}						
		return true;
	}
	
    public static function putBeruData($capaign, $service_url, $postdata, &$jsonOut, &$arrayOut)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		
		if (!self::$oauth_token || !self::$oauth_client_id)
		{
			// Fetch parameter beru_oauth_token
			$result = Db::exec_query ("select value from settings where code = 'beru_oauth_token_" . $campaign . "'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$oauth_token = $row['value'];
			}
			else
				die("No settings parameter 'beru_oauth_token'");
			
			mysqli_free_result($result);
		}
		
		// REST Header
		$curl_post_headerberu = array (
				'Content-type: application/json', 
				'Api-Key: ' . self::$oauth_token
		);

		try {
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerberu);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);
		}
		catch(Exception $e) {
			return false;
		}						
		return true;
	}
}

?>