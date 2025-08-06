<?php

namespace Classes\Sportmaster\v1;
/**
 *
 * @class Sportmaster Api
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Api
{
	private $log;
	
	private $apiKey;
	private $header;
	private $token;

	private $dbClass;
	
	public function __construct($clientId)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Settings.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');
		
		$this->clientId = $clientId;
        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->logger = new \Classes\Common\Log($logName);
		
		$apiKeyClass = new \Classes\Common\Settings('sportmaster_api_key_' . $clientId);
		if ($apiKeyClass->isSettingExists()) {
		    $this->apiKey = $apiKeyClass->getValue();
		}
		else {
		    $this->logger->write (__LINE__ . ' API key not found (' . $clientId . ')');
		}
		if ($this->getToken())
		{
			$this->header = array (
				'Content-type: application/json',
				'Client-ID: ' . $clientId,
				'Authorization: Bearer ' . $this->token
			);
		}
		else
		{
		    $this->logger->write (__LINE__ . ' Failed to get token');
		    return false;
		}
		
		$this->dbClass = new \Classes\Common\Db();
		$this->logger->write (__LINE__ . ' token - ' . $this->token);
		$this->logger->write (__LINE__ . ' header - ' . json_encode ($this->header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}	
	
	private function getToken()
	{
		$url = SPORTMASTER_BASE_URL . 'v1/auth/token';
		$header = array (
		    'Content-type: application/json',
		    'Client-ID: ' . $this->clientId
		);
		$postdata = array('apiKey' => $this->apiKey);
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
		$jsonOut = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($httpCode >= 400) {
			$this->logger->write(__LINE__ . " " . __METHOD__ . " - HTTP error: $httpCode, response: " . $jsonOut);
			return false;
		}
		$arrayOut = json_decode ($jsonOut, true);
		curl_close($curl);
		$this->token = $arrayOut['accessToken'];
		return true;
	}
			

    public function getData($url)
	{
		$curl = curl_init($url);
		//$this->logger->write (__LINE__ . ' header - ' . json_encode ($this->header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		$jsonOut = curl_exec($curl);
		$info = curl_getinfo($curl);			
		curl_close($curl);
		
		if ($info['http_code'] >= 400)
		{
			$this->logger->write (__LINE__ . ' ' . __METHOD__ . ' - URL: ' . $url);
			$this->logger->write (__LINE__ . ' ' . __METHOD__ . ' - HTTP error: ' . $info['http_code'] . ', response: ' . $jsonOut);
			$this->logger->write (__LINE__ . ' ' . __METHOD__ . ' - Header: ' . json_encode ($this->header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$this->logger->write (__LINE__ . ' ' . __METHOD__ . ' - JSON response: ' . $jsonOut);
			return false;
		}
		return json_decode ($jsonOut, true);
	}
	
    public function postData($url, $postdata)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
		$jsonOut = curl_exec($curl);
		$info = curl_getinfo($curl);			
		curl_close($curl);

		if ($info['http_code'] >= 400)
		{
			$this->logger->write (__LINE__ . ' '. __METHOD__ . ' - URL: ' . $url);
			$this->logger->write (__LINE__ . ' '. __METHOD__ . ' - postdata: ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$this->logger->write (__LINE__ . ' ' . __METHOD__ . ' - HTTP error: ' . $info['http_code'] . ', response: ' . $jsonOut);
			$this->logger->write (__LINE__ . ' ' . __METHOD__ . ' - Header: ' . json_encode ($this->header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$this->logger->write (__LINE__ . ' ' . __METHOD__ . ' - JSON response: ' . $jsonOut);
			return false;
		}
		return json_decode ($jsonOut, true);
	}
}