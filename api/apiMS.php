<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/MsThrottle.php');
/**
 *
 * @class MS Api
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class APIMS
{
	private $logger;
	
	private $token = false;
	private $header = false;
	
	private $cache = array ();

	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

		$this->logger = new Log('api - apiMS.log');
	}	
	
	private function getCache ($item)
	{
		if (!isset ($this->cache[$item]))
			return false;
		
		if (strtotime ('now') - $this->cache[$item]['date'] > 60)
		{
			unset ($this->cache[$item]);
			return false;
		}
		
		return $this->cache[$item]['value'];
	}
	
	private function setCache ($item, $value)
	{
		$cache[$item] = array (
			'date' => strtotime ('now'),
			'value' => $value
		);
	}
	
	private function getHeader ()
	{
		if ($this->header)
			return $this->header;

		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');

		$this->token = Settings::getSettingsValues('ms_token');

		if ($this->token === '' || $this->token === false)
			die("No settings parameter 'ms_token'");

		// REST Header
		$this->header = array (
			'Content-type: application/json',
			'Accept-Encoding: gzip',
			'Authorization: Bearer ' . $this->token
		);

		return $this->header;
	}

	/**
	 * Logs a MoySklad call that did not come back as usable JSON.
	 *
	 * Only the errors key used to be logged, so the two failures that actually take the app down
	 * were invisible: a transport failure (curl_exec false, http_code 0 - the caller then gets the
	 * null body back as if it were a real answer) and an HTTP >= 400 whose body is not JSON, such
	 * as an nginx error page. Silence on those is what let a MoySklad outage look like
	 * "0 new orders" for hours.
	 *
	 * Says nothing when the call succeeded.
	 *
	 * @param int $line - __LINE__ of the call site
	 * @param string $method - which api method
	 * @param string $url
	 * @param int $curlErrNo - curl_errno()
	 * @param string $curlErr - curl_error()
	 * @param array $info - curl_getinfo()
	 * @param string $body - raw response body
	 * @param mixed $decoded - json_decode() of the body
	 */
	private function logFailure ($line, $method, $url, $curlErrNo, $curlErr, $info, $body, $decoded)
	{
		$httpCode = isset($info['http_code']) ? (int)$info['http_code'] : 0;

		if ($curlErrNo)
		{
			$this->logger->write ($line . ' ' . $method . '.transportFailed - could not reach MoySklad'
				. ' | curl ' . $curlErrNo . ' ' . $curlErr
				. ' | connect ' . round($info['connect_time'] ?? 0, 2) . 's'
				. ' total ' . round($info['total_time'] ?? 0, 2) . 's'
				. ' | ip ' . ($info['primary_ip'] ?? '?')
				. ' | url ' . $url);
			return;
		}

		if (!is_array($decoded))
		{
			$this->logger->write ($line . ' ' . $method . '.notJson - http ' . $httpCode
				. ' | content-type ' . ($info['content_type'] ?? '?')
				. ' | ip ' . ($info['primary_ip'] ?? '?')
				. ' | url ' . $url
				. ' | body ' . substr(preg_replace('/\s+/', ' ', (string)$body), 0, 300));
			return;
		}

		// decoded fine but the server refused - the errors key is logged by the caller
		if ($httpCode >= 400 && !isset($decoded['errors']))
			$this->logger->write ($line . ' ' . $method . '.httpError - http ' . $httpCode
				. ' | url ' . $url
				. ' | body ' . substr(json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 0, 300));
	}

    public function getData($service_url)
	{
		$curl_post_headerms = $this->getHeader();

		//$logger->write ('getMSData.cache - ' . json_encode (self::$cache, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$cache = $this->getCache ($service_url);

		if ($cache)
			return $cache;

		// Bounded, so a struggling MoySklad cannot wedge a cron run forever. The rate-limit
		// retry below used to be an unbounded while(true) with a flat 1 s sleep, which hammers a
		// service that has just asked for less traffic.
		$attempt = 0;
		$delay = 1;

		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl, CURLOPT_ENCODING, '');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			\Classes\Common\MsThrottle::acquire(__METHOD__);
			$jsonOut = curl_exec($curl);
			$curlErrNo = curl_errno($curl);
			$curlErr = curl_error($curl);
			//$this->logger->write (__LINE__ . ' getMSData.jsonOut - ' . $jsonOut);
            $arrayOut = json_decode ($jsonOut, true);
			$info = curl_getinfo($curl);
			curl_close($curl);

			//$logger->write ('getMSData.arrayOut - ' . json_encode ($arrayOut, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			//$this->logger->write ('getMSData.info - ' . json_encode ($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			// A failure that carries no errors key used to be completely silent: getData returns
			// false/null, findOrders turns that into null, and getNewOrders reads null as
			// "already loaded" for every order. Name the cause here or it cannot be diagnosed.
			$this->logFailure(__LINE__, 'getData', $service_url, $curlErrNo, $curlErr, $info, $jsonOut, $arrayOut);

			if (isset($arrayOut['errors']))
			{
				$this->logger->write (__LINE__ . ' getData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$tmp = false;
				foreach ($arrayOut['errors'] as $error)
					if (isset($error['code']) ? ($error['code'] == 1049 || $error['code'] == 1073) : false)
						$tmp = true;

				if ($tmp && $attempt < MS_RETRY_ATTEMPTS)
				{
					$attempt++;
					sleep($delay);
					$delay = min($delay * 2, 8);
					continue;
				}
			}

			// A gateway error carries no errors key, so the block above never saw it: getData
			// returned false, findOrders turned that into null, and getNewOrders read null as
			// "already loaded" for every order - 19 orders skipped in silence on 2026-09-03.
			// MoySklad answers 504 often enough under load that this has to be retried.
			if ($info['http_code'] >= 500 && $attempt < MS_RETRY_ATTEMPTS)
			{
				$this->logger->write (__LINE__ . ' getData.retry http=' . $info['http_code']
					. ' attempt=' . ($attempt + 1) . ' in ' . $delay . 's - ' . $service_url);
				$attempt++;
				sleep($delay);
				$delay = min($delay * 2, 8);
				continue;
			}

			if ($info['http_code'] < 400)
			{
				$cache = $this->setCache ($service_url, $arrayOut);
				return $arrayOut;
			}
			else
				return false;
		}
	}
	
    public function postData($service_url, $postdata)
	{
		$curl_post_headerms = $this->getHeader();

		$attempt = 0;
		$delay = 1;

		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_ENCODING, '');
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			\Classes\Common\MsThrottle::acquire(__METHOD__);
			$jsonOut = curl_exec($curl);
			$curlErrNo = curl_errno($curl);
			$curlErr = curl_error($curl);
			$arrayOut = json_decode ($jsonOut, true);
			$info = curl_getinfo($curl);
			curl_close($curl);

			$this->logFailure(__LINE__, 'postData', $service_url, $curlErrNo, $curlErr, $info, $jsonOut, $arrayOut);

			if (isset($arrayOut['errors']))
			{
			    $this->logger->write (__LINE__ . ' postData.service_url - ' . $service_url);
			    $this->logger->write (__LINE__ . ' postData.postdata - ' . json_encode ($postdata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    $this->logger->write (__LINE__ . ' postData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$tmp = false;
				foreach ($arrayOut['errors'] as $error)
					if (isset($error['code']) ? ($error['code'] == 1049 || $error['code'] == 1073) : false)
					{
						$tmp = true;
					}
				// Was an unbounded retry with a 10 ms sleep: about a hundred rejected requests a
				// second. That is what got the account's JSON API access suspended on 2026-09-03
				// ("более 400 запросов за минуту, которые завершились ошибкой 429").
				if ($tmp && $attempt < MS_RETRY_ATTEMPTS)
				{
					$attempt++;
					sleep($delay);
					$delay = min($delay * 2, 8);
					continue;
				}
				if ($tmp)
					return false;
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
		$curl_post_headerms = $this->getHeader();

		$attempt = 0;
		$delay = 1;

		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($curl, CURLOPT_ENCODING, '');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			\Classes\Common\MsThrottle::acquire(__METHOD__);
			$jsonOut = curl_exec($curl);
			$curlErrNo = curl_errno($curl);
			$curlErr = curl_error($curl);
			$arrayOut = json_decode ($jsonOut, true);
			$info = curl_getinfo($curl);
			curl_close($curl);

			$this->logFailure(__LINE__, 'putData', $service_url, $curlErrNo, $curlErr, $info, $jsonOut, $arrayOut);
 			
			if (isset($arrayOut['errors']))
			{
			    $this->logger->write (__LINE__ . ' putData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$tmp = false;
				foreach ($arrayOut['errors'] as $error)
					if (isset($error['code']) ? ($error['code'] == 1049 || $error['code'] == 1073) : false)
					{
						$tmp = true;
					}
				// Was an unbounded retry with a 10 ms sleep: about a hundred rejected requests a
				// second. That is what got the account's JSON API access suspended on 2026-09-03
				// ("более 400 запросов за минуту, которые завершились ошибкой 429").
				if ($tmp && $attempt < MS_RETRY_ATTEMPTS)
				{
					$attempt++;
					sleep($delay);
					$delay = min($delay * 2, 8);
					continue;
				}
				if ($tmp)
					return false;
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
		$curl_post_headerms = $this->getHeader();

		$attempt = 0;
		$delay = 1;

		while (true)
		{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($curl, CURLOPT_ENCODING, '');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			\Classes\Common\MsThrottle::acquire(__METHOD__);
			$jsonOut = curl_exec($curl);
			$curlErrNo = curl_errno($curl);
			$curlErr = curl_error($curl);
			$arrayOut = json_decode ($jsonOut, true);
			$info = curl_getinfo($curl);
			curl_close($curl);

			$this->logFailure(__LINE__, 'deleteData', $service_url, $curlErrNo, $curlErr, $info, $jsonOut, $arrayOut);
 			
			if (isset($arrayOut['errors']))
			{
			    $this->logger->write (__LINE__ . ' deleteData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$tmp = false;
				foreach ($arrayOut['errors'] as $error)
					if (isset($error['code']) ? ($error['code'] == 1049 || $error['code'] == 1073) : false)
					{
						$tmp = true;
					}
				// Was an unbounded retry with a 10 ms sleep: about a hundred rejected requests a
				// second. That is what got the account's JSON API access suspended on 2026-09-03
				// ("более 400 запросов за минуту, которые завершились ошибкой 429").
				if ($tmp && $attempt < MS_RETRY_ATTEMPTS)
				{
					$attempt++;
					sleep($delay);
					$delay = min($delay * 2, 8);
					continue;
				}
				if ($tmp)
					return false;
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
		$curl_post_headerms = $this->getHeader();

		$attempt = 0;
		$delay = 1;

		while (true)
		{
		    $curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_ENCODING, '');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			\Classes\Common\MsThrottle::acquire(__METHOD__);
			$jsonOut = curl_exec($curl);
		    $curlErrNo = curl_errno($curl);
		    $curlErr = curl_error($curl);
		    $arrayOut = json_decode ($jsonOut, true);
		    $info = curl_getinfo($curl);
		    curl_close($curl);

		    $this->logFailure(__LINE__, 'postBlobData', $service_url, $curlErrNo, $curlErr, $info, $jsonOut, $arrayOut);

			if (isset($arrayOut['errors']))
			{
			    $this->logger->write (__LINE__ . ' postBlobData.arrayOut[errors] - ' . json_encode ($arrayOut['errors'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$tmp = false;
				foreach ($arrayOut['errors'] as $error)
					if (isset($error['code']) ? ($error['code'] == 1049 || $error['code'] == 1073) : false)
					{
						$tmp = true;
					}
				// Was an unbounded retry with a 10 ms sleep: about a hundred rejected requests a
				// second. That is what got the account's JSON API access suspended on 2026-09-03
				// ("более 400 запросов за минуту, которые завершились ошибкой 429").
				if ($tmp && $attempt < MS_RETRY_ATTEMPTS)
				{
					$attempt++;
					sleep($delay);
					$delay = min($delay * 2, 8);
					continue;
				}
				if ($tmp)
					return false;
				else
					return false;
			}
			else
				return $info ['redirect_url'];
		}
		return $info ['redirect_url'];
	}

	public function getRawData($service_url)
	{
		$curl_post_headerms = $this->getHeader();

		//$logger->write ('getMSData.cache - ' . json_encode (self::$cache, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$curl = curl_init($service_url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerms);
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // Follow redirects (302)
		\Classes\Common\MsThrottle::acquire(__METHOD__);
		$response = curl_exec($curl);
		$curlErrNo = curl_errno($curl);
		$curlErr = curl_error($curl);
		$info = curl_getinfo($curl);
		curl_close($curl);

		if ($curlErrNo)
		{
			$this->logger->write (__LINE__ . ' ' . __METHOD__ . '.transportFailed - could not reach MoySklad'
				. ' | curl ' . $curlErrNo . ' ' . $curlErr
				. ' | ip ' . ($info['primary_ip'] ?? '?')
				. ' | url ' . $service_url);
			return false;
		}

		$this->logger->write (__LINE__ . ' ' . __METHOD__ . ' $info[http_code] - ' . $info['http_code']);

		if ($info['http_code'] < 400)
			return $response;

		// was an unbraced else calling an undefined $logger, which fatals instead of returning false
		$this->logger->write (__LINE__ . ' ' . __METHOD__ . ' http ' . $info['http_code'] . ' response - '
			. substr(preg_replace('/\s+/', ' ', (string)$response), 0, 300));
		return false;
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