<?php
/**
 *
 * @class Users
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Curiers
{
    public static function getMSData($service_url, &$jsonOut, &$arrayOut)
	{
		require_once('config.php');
		
		// Create connection
		$conn = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		// Check connection
		if (!$conn) {
			die("Connection failed: " . mysqli_connect_error());
		}
		// Fetch parameter ms_user
		$sql = "select value from settings where code = 'ms_user'";
		$result = mysqli_query($conn, $sql);
		
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_assoc($result);
			$client_id = $row['value'];
		}
		else
			die("No settings parameter 'ms_user'");
		
		mysqli_free_result($result);
		// Fetch parameter ms_password
		$sql = "select value from settings where code = 'ms_password'";
		$result = mysqli_query($conn, $sql);

		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_assoc($result);
			$client_pass = $row['value'];
		}
		else
			die("No settings parameter 'ms_password'");
		
		mysqli_free_result($result);
		
		mysqli_close($conn);		

		// REST Header
		$curl_post_headerms = array (
				'Content-type: application/json', 
				'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);

		try {
			$curl_order = curl_init($service_url);
			curl_setopt($curl_order, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl_order, CURLOPT_RETURNTRANSFER, true); 
			$jsonOut = curl_exec($curl_order);
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl_order);
		}
		catch(Exception $e) {
			return false;
		}						
		return true;
	}

    public static function getUserCuriers($login)
	{
		require_once('config.php');
		
		// Create connection
		$conn = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		// Check connection
		if (!$conn) {
			die("Connection failed: " . mysqli_connect_error());
		}
		//require_once('classes/log.php');
		// get user roles
		$sql = 'select c.* from users u, users_to_curiers uc, curiers c where u.login = "' . $login . '" and u.user_id = uc.user_id and uc.curier_id = c.curier_id';
		//$logger = new Log('tmp.log');
		//$logger -> write($sql);
		$result = mysqli_query($conn, $sql);
		return $result;
	}
	
	
}

?>