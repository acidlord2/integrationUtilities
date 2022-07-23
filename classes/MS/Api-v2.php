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
		
		$this->logger = new \Classes\Common\Log('classes - MS - Api-v2.log');
		
		$tokenClass = new \Classes\Common\Settings('ms_token');
		if ($tokenClass->isSettiingExists()) {
		    $this->token = $tokenClass->getValue();
		}
		else {
		    $this->logger->write (__LINE__ . ' token not found (ms_token)');
		}
		
		// REST Header
		$this->header = array (
		    'Content-type: application/json',
		    'Authorization: Bearer ' . $this->token
		);
		
		$this->dbClass = new \Classes\Common\Db();
		$this->logger->write (__LINE__ . ' token - ' . $this->token);
		$this->logger->write (__LINE__ . ' header - ' . json_encode ($this->header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}	
	
	public function getCache ($item)
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
	    $this->dbClass->insert('web_cache', array('cache_code', 'cache_value'), array($item, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
	}
	
    public function getData($url)
	{

	    $this->logger->write (__LINE__ . ' getData.url - ' . $url);
		
	    $cache = $this->getCache ($url);
		
		if ($cache)
			return $cache;
		
		while (true)
		{
		    $curl = curl_init($url);
			//$this->logger->write (__LINE__ . ' header - ' . json_encode ($this->header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			$info = curl_getinfo($curl);			
			curl_close($curl);
			
			//$this->logger->write ('getMSData.arrayOut - ' . json_encode ($arrayOut, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			//$this->logger->write ('getMSData.info - ' . json_encode ($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			if (isset($arrayOut['errors']))
			{
				$this->logger->write (__LINE__ . ' getData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
			    $cache = $this->setCache ($url, $arrayOut);
				return $arrayOut;
			}
			else
				return false;
		}
	}
	
    public function postData($service_url, $postdata)
	{
		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);

			if (isset($arrayOut['errors']))
			{
			    $this->logger->write (__LINE__ . ' postData.service_url - ' . $service_url);
			    $this->logger->write (__LINE__ . ' postData.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    $this->logger->write (__LINE__ . ' postData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);
 			
			if (isset($arrayOut['errors']))
			{
			    $this->logger->write (__LINE__ . ' putData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);
 			
			if (isset($arrayOut['errors']))
			{
			    $this->logger->write (__LINE__ . ' deleteData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
		while (true)
		{
			$curl_order = curl_init($service_url);
			curl_setopt($curl_order, CURLOPT_HTTPHEADER, $this->header);
			curl_setopt($curl_order, CURLOPT_POST, true);
			curl_setopt($curl_order, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl_order, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl_order);
			$arrayOut = json_decode ($jsonOut, true);
			$info = curl_getinfo($curl_order);
			curl_close($curl_order);
			
			if (isset($arrayOut['errors']))
			{
			    $this->logger->write (__LINE__ . ' postBlobData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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