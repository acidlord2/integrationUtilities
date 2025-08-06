<?php
/**
 *
 * @class OzonApiV2
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace Classes\Ozon\v2;

class Api
{
    private $log;
    private $clientId;
    private $apiKey;
    private $header;


    public function __construct($organization)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Settings.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');
		
        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);

		$clientId = new \Classes\Common\Settings('ozon_client_id_' . $organization);
		if ($clientId->isSettingExists()) {
		    $this->clientId = $clientId->getValue();
		}
		else {
		    $this->log->write (__LINE__ . ' '. __METHOD__ . ' setting not found (ozon_client_id_' . $organization . ')');
            return false;
		}

        $apiKey = new \Classes\Common\Settings('ozon_api_key_' . $organization);
        if ($apiKey->isSettingExists()) {
            $this->apiKey = $apiKey->getValue();
        } else {
            $this->log->write(__LINE__ . ' '. __METHOD__ . ' setting not found (ozon_api_key_' . $organization . ')');
            return false;
        }


		if (isset($this->clientId) && isset($this->apiKey)) {
    		$this->header = array (
    		    'Content-type: application/json',
    		    'Client-Id: ' . $this->clientId,
    		    'Api-Key: ' . $this->apiKey
    		);
		}
		else {
		    $this->header = false;
            return false;
		}
    }

	private function initCurl($url) 
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($curl, CURLOPT_ENCODING, ''); // Automatically handle gzip/deflate
		return $curl;
	}

    public function postData($url, $data)
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
		$this->log->write(__LINE__ . ' ' . __METHOD__ . ' response: ' . $jsonOut);
		return $jsonOut;
	}

}
