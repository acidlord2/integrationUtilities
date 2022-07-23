<?php
namespace Classes\Wildberries\v1;
use Classes;

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
    
	public function __construct($shop)
	{
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Settings.php');
	    
	    $this->log = new \Classes\Common\Log('classes - Wildberries - Api.log');
	   
	    $settingClass = new \Classes\Common\Settings('WBauthToken' . $shop);
   	    $this->token = $settingClass->getValue();
   	    $this->shop = $shop;
   	    
   	    $this->header = array (
   	        'Content-type: application/json',
   	        'Authorization: ' . $this->token
   	    );
	}
	
    public function postData($url, $postdata)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
		curl_setopt($curl, CURLOPT_POST, true);
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
}

?>