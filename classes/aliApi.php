<?php
/**
 *
 * @class BeruAPI
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class AliAPI
{
	private static $ali_appkey = false;
	private static $ali_secretKey = false;
	private static $ali_access_token = false;

    public static function getAliData($api, $params, &$jsonOut, &$arrayOut)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/aliapi/TopSdk.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/aliApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		
		$logger = new Log ('classes - aliApi.log');
		
		if (!self::$ali_appkey)
			!self::$ali_appkey = Settings::getSettingsValues ('ali_appkey');
		if (!self::$ali_secretKey)
			!self::$ali_secretKey = Settings::getSettingsValues ('ali_secretKey');
		if (!self::$ali_access_token)
			!self::$ali_access_token = Settings::getSettingsValues ('ali_access_token');

		//$logger -> write ('self::ali_appkey - ' . json_encode (self::$ali_appkey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		//$logger -> write ('self::ali_secretKey - ' . json_encode (self::$ali_secretKey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		//$logger -> write ('self::ali_access_token - ' . json_encode (self::$ali_access_token, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			
		$c = new TopClient; 
		$c->appkey = self::$ali_appkey; 
		$c->secretKey = self::$ali_secretKey; 
		$c->format = 'json';
		//$logger -> write ('c - ' . json_encode ($c, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$req = new $api;
		foreach ($params as $key => $param)
			$req->$key(json_encode($param));
		//$logger -> write ('req - ' . json_encode ($req, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		//$logger -> write ('ali_access_token - ' . json_encode (self::$ali_access_token, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$arrayOut = $c->execute($req, self::$ali_access_token);
		//$logger -> write ('arrayOut - ' . json_encode ($arrayOut, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$jsonOut = json_encode ($arrayOut, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		//$arrayOut = json_decode($jsonOut, true);
		return;
		
	}
}

?>