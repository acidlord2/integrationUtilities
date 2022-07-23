<?php
/**
 *
 * @class MS Api
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class APIMS
{
	private $logger;
	
	private $client_id = false;
	private $client_pass = false;
	
	private $cache = array ();

	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

		date_default_timezone_set('Europe/Moscow');
		$this->logger = new Log('api-apiMS.log');
	}	
	
	private function getCache ($item)
	{
		if (!isset ($this->cache[$item]))
			return false;
		
		if (strtotime ('now') - $this->cache[$item]['date'] > 60)
		{
			unset ($this->cache[$item]);
			return false;
		}
		
		return $this->cache[$item]['value'];
	}
	
	private function setCache ($item, $value)
	{
		$cache[$item] = array (
			'date' => strtotime ('now'),
			'value' => $value
		);
	}
	
    public function getData($service_url)
	{
		if (!$this->client_pass || !$this->client_id)
		{
			// Fetch parameter ms_user
			$result = Db::exec_query ("select value from settings where code = 'ms_user'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->client_id = $row['value'];
			}
			else
				die("No settings parameter 'ms_user'");
			
			mysqli_free_result($result);

			// Fetch parameter ms_password
			$result = Db::exec_query ("select value from settings where code = 'ms_password'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->client_pass = $row['value'];
			}
			else
				die("No settings parameter 'ms_password'");
			
			mysqli_free_result($result);
		}
		
		$client_id = $this->client_id;
		$client_pass = $this->client_pass;
		// REST Header
		$curl_post_headerms = array (
				'Content-type: application/json', 
				'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);

		//$logger->write ('getMSData.cache - ' . json_encode (self::$cache, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$cache = $this->getCache ($service_url);
		
		if ($cache)
			return $cache;
		
		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			$info = curl_getinfo($curl);			
			curl_close($curl);
			
			//$logger->write ('getMSData.arrayOut - ' . json_encode ($arrayOut, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			//$this->logger->write ('getMSData.info - ' . json_encode ($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			if (isset($arrayOut['errors']))
			{
				$this->logger->write ('01-getData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
				$cache = $this->setCache ($service_url, $arrayOut);
				return $arrayOut;
			}
			else
				return false;
		}
	}
	
    public function postData($service_url, $postdata)
	{
		if (!$this->client_pass || !$this->client_id)
		{
			// Fetch parameter ms_user
			$result = Db::exec_query ("select value from settings where code = 'ms_user'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->client_id = $row['value'];
			}
			else
				die("No settings parameter 'ms_user'");
			
			mysqli_free_result($result);

			// Fetch parameter ms_password
			$result = Db::exec_query ("select value from settings where code = 'ms_password'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->client_pass = $row['value'];
			}
			else
				die("No settings parameter 'ms_password'");
			
			mysqli_free_result($result);
		}

		$client_id = $this->client_id;
		$client_pass = $this->client_pass;
		// REST Header
		$curl_post_headerms = array (
				'Content-type: application/json', 
				'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);

		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);

			if (isset($arrayOut['errors']))
			{
				$this->logger->write ('01-postData.service_url - ' . $service_url);
				$this->logger->write ('02-postData.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$this->logger->write ('03-postData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
				return $arrayOut;
		}						
		return $arrayOut;
	}

    public function putData($service_url, $postdata)
	{
		if (!$this->client_pass || !$this->client_id)
		{
			// Fetch parameter ms_user
			$result = Db::exec_query ("select value from settings where code = 'ms_user'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->client_id = $row['value'];
			}
			else
				die("No settings parameter 'ms_user'");
			
			mysqli_free_result($result);

			// Fetch parameter ms_password
			$result = Db::exec_query ("select value from settings where code = 'ms_password'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->client_pass = $row['value'];
			}
			else
				die("No settings parameter 'ms_password'");
			
			mysqli_free_result($result);
		}
		
		$client_id = $this->client_id;
		$client_pass = $this->client_pass;
		// REST Header
		$curl_post_headerms = array (
				'Content-type: application/json', 
				'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);

		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);
 			
			if (isset($arrayOut['errors']))
			{
				$this->logger->write ('01-putData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
				return $arrayOut;
		}						
		return $arrayOut;
	}
	
	public function deleteData($service_url)
	{
		if (!$this->client_pass || !$this->client_id)
		{
			// Fetch parameter ms_user
			$result = Db::exec_query ("select value from settings where code = 'ms_user'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->client_id = $row['value'];
			}
			else
				die("No settings parameter 'ms_user'");
			
			mysqli_free_result($result);

			// Fetch parameter ms_password
			$result = Db::exec_query ("select value from settings where code = 'ms_password'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->client_pass = $row['value'];
			}
			else
				die("No settings parameter 'ms_password'");
			
			mysqli_free_result($result);
		}
		
		$client_id = $this->client_id;
		$client_pass = $this->client_pass;
		// REST Header
		$curl_post_headerms = array (
				'Content-type: application/json', 
				'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);

		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);
 			
			if (isset($arrayOut['errors']))
			{
				$this->logger->write ('01-deleteData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
				return $arrayOut;
		}						
		return $arrayOut;
	}
	
    public function postBlobData($service_url, $postdata)
	{
		if (!$this->client_pass || !$this->client_id)
		{
			// Fetch parameter ms_user
			$result = Db::exec_query ("select value from settings where code = 'ms_user'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->client_id = $row['value'];
			}
			else
				die("No settings parameter 'ms_user'");
			
			mysqli_free_result($result);

			// Fetch parameter ms_password
			$result = Db::exec_query ("select value from settings where code = 'ms_password'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->client_pass = $row['value'];
			}
			else
				die("No settings parameter 'ms_password'");
			
			mysqli_free_result($result);
		}
		
		$client_id = $this->client_id;
		$client_pass = $this->client_pass;
		// REST Header
		$curl_post_headerms = array (
				'Content-type: application/json', 
				'Authorization: Basic ' . base64_encode("$client_id:$client_pass")
		);

		while (true)
		{
			$curl_order = curl_init($service_url);
			curl_setopt($curl_order, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl_order, CURLOPT_POST, true);
			curl_setopt($curl_order, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl_order, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl_order);
			$arrayOut = json_decode ($jsonOut, true);
			$info = curl_getinfo($curl_order);
			curl_close($curl_order);
			
			if (isset($arrayOut['errors']))
			{
				$this->logger->write ('01-postBlobData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
				return $info ['redirect_url'];
		}
		return $info ['redirect_url'];
	}
    public static function getIdFromHref ($url)
	{
		$array = explode('/', $url);
		return end($array);
	}

    public static function createMeta ($url, $type)
	{
		return array (
			'href' => $url,
			'type' => $type,
			'mediaType' => 'application/json'
		);
	}
}

?>