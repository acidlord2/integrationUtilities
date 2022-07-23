<?php
/**
 *
 * @class APIAli
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class APIAli
{
	private $filename = 'api-apiAli.log';
	
	private $ali_appkey = false;
	private $ali_secretKey = false;
	private $ali_access_token = false;

    public function getData($api, $params)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/aliapi/TopSdk.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/aliApi.php');
		
		$logger = new Log (self::$filename);
		
		if (!$this->ali_appkey)
		    !$this->ali_appkey = Settings::getSettingsValues ('ali_appkey');
		if (!$this->ali_secretKey)
			!$this->ali_secretKey = Settings::getSettingsValues ('ali_secretKey');
		if (!$this->ali_access_token)
			!$this->ali_access_token = Settings::getSettingsValues ('ali_access_token');

		//$logger -> write ('params - ' . json_encode ($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$c = new TopClient; 
		$c->appkey = $this->ali_appkey; 
		$c->secretKey = $this->ali_secretKey; 
		$c->format = 'json';
		//$logger -> write ('c - ' . json_encode ($c, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$req = new $api;
		foreach ($params as $key => $param)
			$req->$key(json_encode($param));
		//$logger -> write ('req - ' . json_encode ($req, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		//$logger -> write ('ali_access_token - ' . json_encode (this->$ali_access_token, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$arrayOut = $c->execute($req, $this->ali_access_token);
		
		
		if (isset ($arrayOut['error_response']))
			$logger -> write (__LINE__ . ' getData.arrayOut - ' . json_encode ($arrayOut, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		return $arrayOut;
		
	}
}

?>