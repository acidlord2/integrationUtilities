<?php
/**
 *
 * @class Payments
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class MSAPI
{
	private static $client_id = false;
	private static $client_pass = false;
	private static $cache = array ();

    public static function getMSData($service_url, &$jsonOut, &$arrayOut)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log('classes - msApi.log');
		
		foreach (self::$cache as $key => $cacheItem)
			if (strtotime ('now') - $cacheItem['time'] > 60)
				unset (self::$cache[$key]);
		
		if (!self::$client_pass || !self::$client_id)
		{
			// Fetch parameter ms_user
			$result = Db::exec_query ("select value from settings where code = 'ms_user'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$client_id = $row['value'];
			}
			else
				die("No settings parameter 'ms_user'");
			
			mysqli_free_result($result);

			// Fetch parameter ms_password
			$result = Db::exec_query ("select value from settings where code = 'ms_password'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$client_pass = $row['value'];
			}
			else
				die("No settings parameter 'ms_password'");
			
			mysqli_free_result($result);
		}
		
		$client_id = self::$client_id;
		$client_pass = self::$client_pass;
		// REST Header
		$curl_post_headerms = array (
			'Content-type: application/json', 
		    'Accept-Encoding: gzip',
		    'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);

		//$logger->write ('getMSData.cache - ' . json_encode (self::$cache, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$cacheKey =	array_search ($service_url, array_column (self::$cache, 'service_url'));
		if ($cacheKey !== false)
		{
			$arrayOut = self::$cache[$cacheKey]['arrayOut'];
			$jsonOut = self::$cache[$cacheKey]['jsonOut'];
			return true;
		}
		
		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_ENCODING, '');
			$gzipped = curl_exec($curl);
			//$jsonOut = gzdecode($gzipped);
			$jsonOut = $gzipped;
			$arrayOut = json_decode ($jsonOut, true);
			$info = curl_getinfo($curl);			
			curl_close($curl);
			
			//$logger->write ('getMSData.arrayOut - ' . json_encode ($arrayOut, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			//$logger->write ('getMSData.info - ' . json_encode ($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			if (isset($arrayOut['errors']))
			{
			    $logger->write ('getMSData.service_url[errors] - ' . $service_url);
			    $logger->write ('getMSData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
			if (isset($arrayOut['errors']))
			{
				$tmp = false;
				foreach ($arrayOut['errors'] as $error)
					if (isset($error['code']) ? ($error['code'] == 1049 || $error['code'] == 1073) : false)
					{
						usleep(10000);
						$tmp = true;
						continue;
					}
				if ($tmp)
					continue;
			}
			
			if ($info['http_code'] < 400)
			{
				self::$cache[] = array (
					'service_url' => $service_url,
					'arrayOut' => $arrayOut,
					'jsonOut' => $jsonOut,
					'time' => strtotime("now")
				);
				return true;
			}
			else
				return false;
		}
	}
	
    public static function postMSData($service_url, $postdata, &$jsonOut, &$arrayOut)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log('classes - msApi.log');
		
		if (!self::$client_pass || !self::$client_id)
		{
			// Fetch parameter ms_user
			$result = Db::exec_query ("select value from settings where code = 'ms_user'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$client_id = $row['value'];
			}
			else
				die("No settings parameter 'ms_user'");
			
			mysqli_free_result($result);

			// Fetch parameter ms_password
			$result = Db::exec_query ("select value from settings where code = 'ms_password'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$client_pass = $row['value'];
			}
			else
				die("No settings parameter 'ms_password'");
			
			mysqli_free_result($result);
		}

		$client_id = self::$client_id;
		$client_pass = self::$client_pass;
		// REST Header
		$curl_post_headerms = array (
			'Content-type: application/json', 
		    'Accept-Encoding: gzip',
		    'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);

		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = gzdecode(curl_exec($curl));
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);

			if (isset($arrayOut['errors']))
				$logger->write ('postMSData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			if (isset($arrayOut['errors']))
			{
				$tmp = false;
				foreach ($arrayOut['errors'] as $error)
					if (isset($error['code']) ? ($error['code'] == 1049 || $error['code'] == 1073) : false)
					{
						usleep(10000);
						$tmp = true;
						continue;
					}
				if ($tmp)
					continue;
				else
					return false;
			}
			else
				return true;
		}						
		return true;
	}

    public static function putMSData($service_url, $postdata, &$jsonOut, &$arrayOut)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log('classes - msApi.log');
		
		if (!self::$client_pass || !self::$client_id)
		{
			// Fetch parameter ms_user
			$result = Db::exec_query ("select value from settings where code = 'ms_user'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$client_id = $row['value'];
			}
			else
				die("No settings parameter 'ms_user'");
			
			mysqli_free_result($result);

			// Fetch parameter ms_password
			$result = Db::exec_query ("select value from settings where code = 'ms_password'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$client_pass = $row['value'];
			}
			else
				die("No settings parameter 'ms_password'");
			
			mysqli_free_result($result);
		}
		
		$client_id = self::$client_id;
		$client_pass = self::$client_pass;
		// REST Header
		$curl_post_headerms = array (
			'Content-type: application/json', 
		    'Accept-Encoding: gzip',
		    'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);

		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = gzdecode(curl_exec($curl));
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);
 			
			if (isset($arrayOut['errors']))
				$logger->write ('postMSData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			if (isset($arrayOut['errors']))
			{
				$tmp = false;
				foreach ($arrayOut['errors'] as $error)
					if (isset($error['code']) ? ($error['code'] == 1049 || $error['code'] == 1073) : false)
					{
						usleep(10000);
						$tmp = true;
						continue;
					}
				if ($tmp)
					continue;
				else
					return false;
			}
			else
				return true;
		}						
		return true;
	}
	
	public static function deleteMSData($service_url)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log('classes - msApi.log');
		
		if (!self::$client_pass || !self::$client_id)
		{
			// Fetch parameter ms_user
			$result = Db::exec_query ("select value from settings where code = 'ms_user'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$client_id = $row['value'];
			}
			else
				die("No settings parameter 'ms_user'");
			
			mysqli_free_result($result);

			// Fetch parameter ms_password
			$result = Db::exec_query ("select value from settings where code = 'ms_password'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$client_pass = $row['value'];
			}
			else
				die("No settings parameter 'ms_password'");
			
			mysqli_free_result($result);
		}
		
		$client_id = self::$client_id;
		$client_pass = self::$client_pass;
		// REST Header
		$curl_post_headerms = array (
			'Content-type: application/json', 
		    'Accept-Encoding: gzip',
		    'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);

		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			$return = gzdecode(curl_exec($curl));
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);
 			
			if (isset($arrayOut['errors']))
				$logger->write (__LINE__ . ' postMSData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			if (isset($arrayOut['errors']))
			{
				$tmp = false;
				foreach ($arrayOut['errors'] as $error)
					if (isset($error['code']) ? ($error['code'] == 1049 || $error['code'] == 1073) : false)
					{
						usleep(10000);
						$tmp = true;
						continue;
					}
				if ($tmp)
					continue;
				else
					return false;
			}
			else
				return $return;
		}						
		return $return;
	}
	
    public static function postMSDataBlob($service_url, $postdata, &$jsonOut="", &$blobOut)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log('classes - msApi.log');
		
		if (!self::$client_pass || !self::$client_id)
		{
			// Fetch parameter ms_user
			$result = Db::exec_query ("select value from settings where code = 'ms_user'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$client_id = $row['value'];
			}
			else
				die("No settings parameter 'ms_user'");
			
			mysqli_free_result($result);

			// Fetch parameter ms_password
			$result = Db::exec_query ("select value from settings where code = 'ms_password'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$client_pass = $row['value'];
			}
			else
				die("No settings parameter 'ms_password'");
			
			mysqli_free_result($result);
		}
		$client_id = self::$client_id;
		$client_pass = self::$client_pass;
		// REST Header
		$curl_post_headerms = array (
			'Content-type: application/json', 
		    'Accept-Encoding: gzip',
		    'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);

		while (true)
		{
			$curl_order = curl_init($service_url);
			curl_setopt($curl_order, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl_order, CURLOPT_POST, true);
			curl_setopt($curl_order, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl_order, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = gzdecode(curl_exec($curl_order));
			$info = curl_getinfo($curl_order);
			curl_close($curl_order);
			$blobOut = $info ['redirect_url'];
 			
			if (isset($arrayOut['errors']))
				$logger->write ('postMSData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			if (isset($arrayOut['errors']))
			{
				$tmp = false;
				foreach ($arrayOut['errors'] as $error)
					if (isset($error['code']) ? ($error['code'] == 1049 || $error['code'] == 1073) : false)
					{
						usleep(10000);
						$tmp = true;
						continue;
					}
				if ($tmp)
					continue;
				else
					return false;
			}
			else
				return true;
		}						
		return true;
	}

}

?>