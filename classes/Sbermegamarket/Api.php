<?php
/**
 *
 * @class API
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace Classes\Sbermegamarket;

class API
{
	private $log;
	private $token;
	private $header;

	public function __construct($shop = '4824')
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
		
		$this->log = new \Log('classes - Sbermegamarket - Api.log');
		$this->log->write(__LINE__ . ' __construct.shop - ' . $shop);
		
		$this->token = SBMM_TESTMODE ? \Settings::getSettingsValues('sbermegamarket_test_token_' . $shop) : \Settings::getSettingsValues('sbermegamarket_token_' . $shop);
		
		if (!$this->token){
		    $this->log->write(__LINE__ . ' __construct. Не задан параметр sbermegamarket_token ' . $this->token);
		}
		
		$this->header = array (
		    'Content-type: application/json'
		);
	}	
	
    public  function postData($url, $data)
	{
	    //$this->log->write(__LINE__ . ' url - ' . $url);
	    //$this->log->write(__LINE__ . ' header - ' . json_encode($this->header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    //$this->log->write(__LINE__ . ' data - ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    
	    $data['data']['token'] = $this->token;
	    $data['meta'] = array();
	    
	    $count = 0;
	    while (true)
		{
            $count++;

            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
    		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($curl, CURLOPT_FAILONERROR, true);
    		curl_setopt($curl, CURLOPT_POST, true);
    		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    		$jsonOut = curl_exec($curl);
    		$arrayOut = json_decode ($jsonOut, true);
    		
    		if (curl_errno($curl))
    		{
    		    $this->log->write(__LINE__ . ' postData.Error No: ' . curl_errno($curl) . ' | Error msg: ' . curl_error($curl));
    		    if ($count < 3)
    		        continue;
    		}
    		curl_close($curl);
    		
    		return $arrayOut;
		}
	}
}

?>