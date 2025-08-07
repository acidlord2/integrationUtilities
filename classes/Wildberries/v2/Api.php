<?php
namespace Classes\Wildberries\v2;

/**
 *
 * @class APIWB
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Api
{
    private $token = NULL;
    private $shop = NULL;
    private $header = NULL;
	private $log;
    
	public function __construct($shop)
	{
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Settings.php');

		$this->shop = $shop;

		$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
		$logName .= '.log';
		$this->log = new \Classes\Common\Log($logName);
	   
	    $settingClass = new \Classes\Common\Settings('wb_api_token_' . $shop);
   	    $this->token = $settingClass->getValue();
   	    
   	    $this->header = array (
   	        'Content-type: application/json',
   	        'Authorization: ' . $this->token
   	    );
	}
	
	private function initCurl($url)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		return $curl;
	}

    public function postData($url, $postdata)
	{
		
		$curl = $this->initCurl($url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
		$jsonOut = curl_exec($curl);
		$info = curl_getinfo($curl);

		if (curl_errno($curl))
		{
		    $this->log->write(__LINE__ . ' postData.Error No: ' . curl_errno($curl) . ' | Error msg: ' . curl_error($curl));
		}
		curl_close($curl);
		
		return $arrayOut;
	}
	
    public function getData($url)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		$jsonOut = curl_exec($curl);
		$arrayOut = json_decode ($jsonOut, true);
		
		if (curl_errno($curl))
		{
		    $this->log->write(__LINE__ . ' postData.Error No: ' . curl_errno($curl) . ' | Error msg: ' . curl_error($curl));
		}
		curl_close($curl);
		
		return $arrayOut;
    }
    public function putData($url, $postdata)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
		$jsonOut = curl_exec($curl);
		$arrayOut = json_decode ($jsonOut, true);
		
		if (curl_errno($curl))
		{
		    $this->log->write(__LINE__ . ' postData.Error No: ' . curl_errno($curl) . ' | Error msg: ' . curl_error($curl));
		}
		curl_close($curl);
		
		return $arrayOut;
    }
	public function patchData($url, $postdata)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
		$jsonOut = curl_exec($curl);
		$arrayOut = json_decode ($jsonOut, true);

		if(curl_errno($curl))
		{
			$this->log->write(__LINE__ . ' patchData.Error No: ' . curl_errno($curl) . ' | Error msg: ' . curl_error($curl));
		}
		return $arrayOut;
	}
}

?>