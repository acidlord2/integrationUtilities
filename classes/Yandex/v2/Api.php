<?php
namespace Classes\Yandex\v2;
/**
 *
 * @class APIYandex
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Api
{
	private $log;
	
	private $oauth_token;
	private $campaign;
	private $businessId;
	private $header;

	public function __construct($campaign)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Settings.php');
		
        $this->campaign = $campaign;
		$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
		$logName .= '.log';
		$this->log = new \Classes\Common\Log($logName);

		$settingToken = new \Classes\Common\Settings('beru_oauth_token_' . $campaign);
		$this->oauth_token = $settingToken->getValue();

		$this->header = array(
			'Content-type: application/json',
			'Api-Key: ' . $this->oauth_token
		);
	}

	private function initCurl($url)
	{
	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_FAILONERROR, true);
	    return $curl;
	}

    public  function getData($url)
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
			if ($info['http_code'] !== 200) {
			    $this->log->write(__LINE__ . ' ' . __METHOD__ . ' HTTP Error: ' . $info['http_code']);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' Response: ' . $jsonOut);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' Header: ' . json_encode($this->header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' URL: ' . $url);
			    return false;
			}
		} catch (\Exception $e) {
		    $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Exception: ' . $e->getMessage());
		    return false;
		}

	    if ($info['http_code'] !== 200) {
	        $this->log->write(__LINE__ . ' getData. HTTP Error: ' . $info['http_code']);
	        return false;
	    }

	    return $jsonOut;
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
			if ($info['http_code'] !== 200) {
			    $this->log->write(__LINE__ . ' ' . __METHOD__ . ' HTTP Error: ' . $info['http_code']);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' Response: ' . $jsonOut);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' Header: ' . json_encode($this->header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' URL: ' . $url);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' PostData: ' . json_encode($postdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			    return false;
			}
		} catch (\Exception $e) {
		    $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Exception: ' . $e->getMessage());
		    return false;
		}

	    return $jsonOut;
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
		    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
		    $jsonOut = curl_exec($curl);
		    $info = curl_getinfo($curl);
		    curl_close($curl);
			if ($info['http_code'] !== 200) {
			    $this->log->write(__LINE__ . ' ' . __METHOD__ . ' HTTP Error: ' . $info['http_code']);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' Response: ' . $jsonOut);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' Header: ' . json_encode($this->header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' URL: ' . $url);
				$this->log->write(__LINE__ . ' ' . __METHOD__ . ' PostData: ' . json_encode($postdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			    return false;
			}
		} catch (\Exception $e) {
		    $this->log->write(__LINE__ . ' ' . __METHOD__ . ' Exception: ' . $e->getMessage());
		    return false;
		}

	    return $jsonOut;
	}
}

?>