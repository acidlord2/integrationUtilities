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
		require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');

		$token = Settings::getSettingsValues('ms_token');

		if ($token === '' || $token === false)
			die("No settings parameter 'ms_token'");

		// REST Header - Bearer, like every other MoySklad client in the app
		$curl_post_headerms = array (
				'Content-type: application/json',
				'Accept-Encoding: gzip',
				'Authorization: Bearer ' . $token
		);

		try {
			$curl_order = curl_init($service_url);
			curl_setopt($curl_order, CURLOPT_HTTPHEADER, $curl_post_headerms);
			// MoySklad's edge answers 415 to a request that does not advertise gzip, and
			// this call site has no gzdecode() of its own - let curl handle both ends.
			curl_setopt($curl_order, CURLOPT_ENCODING, '');
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