<?php
/**
 *
 * @class Users
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Users
{
    public static function autentificateUser ($login, $password)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		// autentificate
		$sql = 'select * from users where login = "' . $login . '" and password = "' . $password . '"';
		$result = Db::exec_query_array($sql);
		
		require_once('log.php');
		//$logger = new Log ('tmp.log');
		//$logger->write (json_encode($result));
		return $result;
	}
	
    public static function getUserRoles($login)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		
		// Create connection
		$conn = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		// Check connection
		if (!$conn) {
			die("Connection failed: " . mysqli_connect_error());
		}
		//require_once('log.php');
		// get user roles
		$sql = 'select * from users_to_roles where user_id in (select user_id from users where login = "' . $login . '")';
		//$logger = new Log('tmp.log');
		//$logger -> write($sql);
		$result = mysqli_query($conn, $sql);
		return $result;
	}
	// get curier orders
    public static function getList($shipDate, $curier)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		//require_once('classes/log.php');
		static $orders;
		
		// get orders
		$shipDateStart = 'deliveryPlannedMoment%3E=' . $shipDate . '%2000:00:00;';
		$shipDateEnd = 'deliveryPlannedMoment%3C=' . $shipDate . '%2023:59:59;';
		
		//$logger = new Log('tmp.log');
		$offset = 0;
		$orders = array();
		$cont = true;
		while ($cont)
		{
			$service_url = MS_COURL . '?filter=' . $shipDateStart . $shipDateEnd . $goodsSt . $org . $agent . '&limit=' . MS_LIMIT . '&offset=' . $offset;
			//echo $service_url;
			//$logger->write ($service_url);
			Orders::getMSData($service_url, $response_ordersJson, $response_orders);
			$cont = isset ($response_orders['rows'][0]);
			if ($cont) {
				$offset += MS_LIMIT;
				$orders = array_merge ($orders, $response_orders['rows']);
			}
		}
		$i = 0;
		foreach ($orders as $order) {
			$orders[$i]['website'] = $order['organization']['meta']['href'] == MS_4CLEANING ? '4cleaning' : '10kids';
			$orders[$i]['goodsFlag'] = $order['agent']['meta']['href'] == MS_GOODS_AGENT ? utf8_encode("&#10004;") : utf8_encode("&#10008;") ;
			$i++;
		}
		
		return $orders;
 	}
}

?>