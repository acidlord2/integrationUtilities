<?php
namespace Classes\MS\v2;
/**
 *
 * @class MS Api
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Api
{
	private $logger;
	
	private $token;
	private $header;

	private $dbClass;
	
	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Settings.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');
		
        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->logger = new \Classes\Common\Log($logName);
		
		$tokenClass = new \Classes\Common\Settings('ms_token');
		if ($tokenClass->isSettingExists()) {
		    $this->token = $tokenClass->getValue();
		}
		else {
		    $this->logger->write (__LINE__ . ' '. __METHOD__ . ' token not found (ms_token)');
		}
		
		// REST Header
		$this->header = array (
		    'Content-type: application/json',
		    'Accept-Encoding: gzip',
		    'Authorization: Bearer ' . $this->token
		);
		
		$this->dbClass = new \Classes\Common\Db();
		$this->logger->write (__LINE__ . ' '. __METHOD__ . ' header - ' . json_encode ($this->header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}

	private function check_for_errors($json)
	{
		$array = json_decode($json, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->logger->write(__LINE__ . ' '. __METHOD__ . ' JSON decode error: ' . json_last_error_msg());
			return 'error';
		}

		if (isset($array['errors']))
		{
			$this->logger->write (__LINE__ . ' '. __METHOD__ . ' errors - ' . json_encode ($array['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$tmp = false;
			foreach ($array['errors'] as $error)
			{
				if (isset($error['code']) ? ($error['code'] == 1049 || $error['code'] == 1073) : false)
				{
					usleep(100000);
					$tmp = true;
					return 'retry';
				}
			}
			return 'error';
		}
	    return 'success';
	}

	private function initCurl($url) 
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_ENCODING, ''); // Automatically handle gzip/deflate
		return $curl;
	}

	private function getCache ($item)
	{
	    $sql = 'select * from web_cache where cache_code = "' . $item . '"';
	    $json = $this->dbClass->execQueryArray($sql);
	    
	    if (count($json)) {
    	    $assortment = json_decode($json[0]['cache_value'], true);
	    }
	    else {
	        $assortment = false;
	    }
	    
		return $assortment;
	}
	
	public function setCache ($item, $value)
	{
	    $this->dbClass->execQuery('delete from web_cache where cache_code = "' . $item . '"');
	    $this->dbClass->insert('web_cache', array('cache_code', 'cache_value'), array($item, $value));
	}
	
    public function getData($url)
	{

	    $this->logger->write (__LINE__ . ' '. __METHOD__ . ' url - ' . $url);
	    // $cache = $this->getCache ($url);
		// if ($cache)
		// 	return $cache;
		
		while (true)
		{
		    $curl = $this->initCurl($url);
			$jsonOut = curl_exec($curl);
			$info = curl_getinfo($curl);			
			curl_close($curl);

			if ($info['http_code'] >= 400)
			{
				$this->logger->write (__LINE__ . ' '. __METHOD__ . ' info - ' . json_encode ($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return false;
			}

			$check = $this->check_for_errors($jsonOut);
			if ($check === 'error')
			{
				$this->logger->write (__LINE__ . ' '. __METHOD__ . ' Error in response: ' . $jsonOut);
				return false;
			}
			else if ($check === 'retry')
			{
				usleep(100000);
				continue;
			}

			//$cache = $this->setCache ($url, $jsonOut);
			return $jsonOut;
		}
	}
	
    public function postData($url, $postdata)
	{
		$this->logger->write (__LINE__ . ' '. __METHOD__ . ' url - ' . $url);
		while (true)
		{
			$curl = $this->initCurl($url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			$info = curl_getinfo($curl);
			curl_close($curl);

			if ($info['http_code'] >= 400)
			{
				$this->logger->write (__LINE__ . ' '. __METHOD__ . ' info - ' . json_encode ($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$this->logger->write (__LINE__ . ' '. __METHOD__ . ' postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return false;
			}

			$check = $this->check_for_errors($jsonOut);
			if ($check === 'error')
			{
				$this->logger->write (__LINE__ . ' '. __METHOD__ . ' Error in response: ' . $jsonOut);
				return false;
			}
			else if ($check === 'retry')
			{
				usleep(100000);
				continue;
			}

			return $jsonOut;
		}
	}

    public function putData($url, $postdata)
	{
		$this->logger->write (__LINE__ . ' '. __METHOD__ . ' url - ' . $url);
		while (true)
		{
			$curl = $this->initCurl($url);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			$info = curl_getinfo($curl);
			curl_close($curl);

			if ($info['http_code'] >= 400)
			{
				$this->logger->write (__LINE__ . ' '. __METHOD__ . ' info - ' . json_encode ($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$this->logger->write (__LINE__ . ' '. __METHOD__ . ' postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return false;
			}

			$check = $this->check_for_errors($jsonOut);
			if ($check === 'error')
			{
				$this->logger->write (__LINE__ . ' '. __METHOD__ . ' Error in response: ' . $jsonOut);
				return false;
			}
			else if ($check === 'retry')
			{
				usleep(100000);
				continue;
			}

			return $jsonOut;
		}						
	}
	
	public function deleteData($url)
	{
		$this->logger->write (__LINE__ . ' '. __METHOD__ . ' url - ' . $url);
		while (true)
		{
			$curl = $this->initCurl($url);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
			$jsonOut = curl_exec($curl);
			$info = curl_getinfo($curl);
			curl_close($curl);

			if ($info['http_code'] >= 400)
			{
				$this->logger->write (__LINE__ . ' '. __METHOD__ . ' info - ' . json_encode ($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return false;
			}
			
			$check = $this->check_for_errors($jsonOut);
			if ($check === 'error')
			{
				$this->logger->write (__LINE__ . ' '. __METHOD__ . ' Error in response: ' . $jsonOut);
				return false;
			}
			else if ($check === 'retry')
			{
				usleep(100000);
				continue;
			}

			return $jsonOut;
		}
	}
	
    public function postBlobData($url, $postdata)
	{
		$this->logger->write (__LINE__ . ' '. __METHOD__ . ' url - ' . $url);
		while (true)
		{
			$curl = $this->initCurl($url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			$info = curl_getinfo($curl);
			curl_close($curl);

			if ($info['http_code'] >= 400)
			{
				$this->logger->write (__LINE__ . ' '. __METHOD__ . ' info - ' . json_encode ($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$this->logger->write (__LINE__ . ' '. __METHOD__ . ' postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return false;
			}

			$check = $this->check_for_errors($jsonOut);
			if ($check === 'error')
			{
				$this->logger->write (__LINE__ . ' '. __METHOD__ . ' Error in response: ' . $jsonOut);
				return false;
			}
			else if ($check === 'retry')
			{
				usleep(100000);
				continue;
			}

			return $info['redirect_url'];
		}
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