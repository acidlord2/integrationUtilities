<?php
/**
 *
 * @class APIWB
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class APIWB
{
	private static $x_auth_token = false;
	
    public static function postData($company, $service_url, $postdata, &$jsonOut, &$arrayOut)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		
		if (!self::$x_auth_token)
		{
			// Fetch parameter x_auth_token
			$result = Db::exec_query ("select value from settings where code = 'WBx-auth-token" . $company . "'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$x_auth_token = $row['value'];
			}
			else
				die("No settings parameter 'WBx-auth-token" . $company . "'");
			
			mysqli_free_result($result);
		}
		// REST Header
		$curl_post_header = array (
			'Content-type: application/json'
		);
		
		$postdata['token'] = self::$x_auth_token;
		
		try {
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_header);
			curl_setopt($curl, CURLOPT_POST, true);
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
	
    public static function getData($company, $service_url, &$jsonOut, &$arrayOut)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		
		if (!self::$x_auth_token)
		{
			// Fetch parameter x_auth_token
			$result = Db::exec_query ("select value from settings where code = 'WBx-auth-token" . $company . "'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$x_auth_token = $row['value'];
			}
			else
				die("No settings parameter 'WBx-auth-token" . $company . "'");
			
			mysqli_free_result($result);
		}
		// REST Header
		$curl_post_header = array (
			'Content-type: application/json',
			'X-Auth-Token: ' . self::$x_auth_token
		);
		
		
		try {
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_header);
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
    public static function putData($company, $service_url, $postdata, &$jsonOut, &$arrayOut)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		
		if (!self::$x_auth_token)
		{
			// Fetch parameter x_auth_token
			$result = Db::exec_query ("select value from settings where code = 'WBx-auth-token" . $company . "'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$x_auth_token = $row['value'];
			}
			else
				die("No settings parameter 'WBx-auth-token" . $company . "'");
			
			mysqli_free_result($result);
		}
		// REST Header
		$curl_post_header = array (
			'Content-type: application/json',
			'X-Auth-Token: ' . self::$x_auth_token
		);
		
		
		try {
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_header);
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