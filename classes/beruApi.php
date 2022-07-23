<?php
/**
 *
 * @class BeruAPI
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class BeruAPI
{
	private static $oauth_client_id = false;
	private static $oauth_token = false;

    public static function getBeruData($service_url, &$jsonOut, &$arrayOut)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		
		if (!self::$oauth_token || !self::$oauth_client_id)
		{
			// Fetch parameter beru_oauth_token
			$result = Db::exec_query ("select value from settings where code = 'beru_oauth_token'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$oauth_token = $row['value'];
			}
			else
				die("No settings parameter 'beru_oauth_token'");
			
			mysqli_free_result($result);

			// Fetch parameter beru_oauth_client_id
			$result = Db::exec_query ("select value from settings where code = 'beru_oauth_client_id'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$oauth_client_id = $row['value'];
			}
			else
				die("No settings parameter 'beru_oauth_client_id'");
			
			mysqli_free_result($result);
		}
		
		// REST Header
		$curl_post_headerberu = array (
				'Content-type: application/json', 
				'Authorization: OAuth oauth_token="' . self::$oauth_token . '",oauth_client_id="' . self::$oauth_client_id . '"'
		);

		try {
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerberu);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);
		}
		catch(Exception $e) {
			return false;
		}						
		return true;
	}
	
    public static function putBeruData($service_url, $postdata, &$jsonOut, &$arrayOut)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		
		if (!self::$oauth_token || !self::$oauth_client_id)
		{
			// Fetch parameter beru_oauth_token
			$result = Db::exec_query ("select value from settings where code = 'beru_oauth_token'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$oauth_token = $row['value'];
			}
			else
				die("No settings parameter 'beru_oauth_token'");
			
			mysqli_free_result($result);

			// Fetch parameter beru_oauth_client_id
			$result = Db::exec_query ("select value from settings where code = 'beru_oauth_client_id'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$oauth_client_id = $row['value'];
			}
			else
				die("No settings parameter 'beru_oauth_client_id'");
			
			mysqli_free_result($result);
		}
		
		// REST Header
		$curl_post_headerberu = array (
				'Content-type: application/json', 
				'Authorization: OAuth oauth_token="' . self::$oauth_token . '",oauth_client_id="' . self::$oauth_client_id . '"'
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