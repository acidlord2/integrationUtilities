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
		
	    if (!$this->header) {
	        $this->log->write(__LINE__ . ' ' . __METHOD__ . ' header not set');
	        return false;
	    }

		try {
			$curl = $this->initCurl($url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
			$jsonOut = curl_exec($curl);
			$info = curl_getinfo($curl);
			curl_close($curl);

            if($info['http_code'] >= 400) {
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' error code: ' . $info['http_code']);
                $this->log->write(__LINE__ . ' ' . __METHOD__ . ' response: ' . $jsonOut);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' request: ' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' header: ' . json_encode($this->header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' url: ' . $url);
                return false;
            }
		}
		catch (Exception $e)
		{
            $this->log->write(__LINE__ . ' ' . __METHOD__ . ' exception: ' . $e->getMessage());
		    return false;
		}
		return $jsonOut;

		
	}
	
    public function getData($url)
	{
		if (!$this->header) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' header not set');
			return false;
		}

		try {
			$curl = $this->initCurl($url);
			$jsonOut = curl_exec($curl);
			$info = curl_getinfo($curl);
			curl_close($curl);

			if($info['http_code'] >= 400) {
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error code: ' . $info['http_code']);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' response: ' . $jsonOut);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' header: ' . json_encode($this->header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' url: ' . $url);
				return false;
			}

			return $jsonOut;
		}
		catch (Exception $e)
		{
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' exception: ' . $e->getMessage());
			return false;
		}
	}
    public function putData($url, $postdata)
	{
		if (!$this->header) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' header not set');
			return false;
		}

		try {
			$curl = $this->initCurl($url);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			$info = curl_getinfo($curl);
			curl_close($curl);

			if($info['http_code'] >= 400) {
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error code: ' . $info['http_code']);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' response: ' . $jsonOut);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' request: ' . json_encode($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' header: ' . json_encode($this->header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' url: ' . $url);
				return false;
			}

			return $jsonOut;
		}
		catch (Exception $e)
		{
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' exception: ' . $e->getMessage());
			return false;
		}
	}
	public function patchData($url, $postdata)
	{
		if (!$this->header) {
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' header not set');
			return false;
		}

		try {
			$curl = $this->initCurl($url);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			$info = curl_getinfo($curl);
			curl_close($curl);

			if($info['http_code'] >= 400) {
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' error code: ' . $info['http_code']);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' response: ' . $jsonOut);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' request: ' . json_encode($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' header: ' . json_encode($this->header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' url: ' . $url);
				return false;
			}

			return $jsonOut;
		}
		catch (Exception $e)
		{
			$this->log->write(__LINE__ . ' ' . __METHOD__ . ' exception: ' . $e->getMessage());
			return false;
		}
	}
}

?>