<?php
/**
 *
 * @class APIYandex
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class APIYandex
{
	private $logger;
	
	private $oauth_client_id = false;
	private $oauth_token = false;

	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

		date_default_timezone_set('Europe/Moscow');
		$this->logger = new Log('api-apiYandex.log');
	}	

    public  function getData($campaign, $service_url)
	{
		if (!$this->oauth_token || !$this->oauth_client_id)
		{
			// Fetch parameter beru_oauth_token
			$result = Db::exec_query ("select value from settings where code = 'beru_oauth_token_" . $campaign . "'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->oauth_token = $row['value'];
			}
			else
				die("No settings parameter 'beru_oauth_token'");
			
			mysqli_free_result($result);

			// Fetch parameter beru_oauth_client_id
			$result = Db::exec_query ("select value from settings where code = 'beru_oauth_client_id_" . $campaign . "'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->oauth_client_id = $row['value'];
			}
			else
				die("No settings parameter 'beru_oauth_client_id'");
			
			mysqli_free_result($result);
		}
		
		// REST Header
		$curl_post_headerberu = array (
				'Content-type: application/json', 
				'Authorization: OAuth oauth_token="' . $this->oauth_token . '",oauth_client_id="' . $this->oauth_client_id . '"'
		);

		try {
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerberu);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);
		}
		catch(Exception $e) {
			return false;
		}						
		
		return $arrayOut;
	}
	
    public function putData($campaign, $service_url, $postdata)
	{
		if (!$this->oauth_token || !$this->oauth_client_id)
		{
			// Fetch parameter beru_oauth_token
			$result = Db::exec_query ("select value from settings where code = 'beru_oauth_token_" . $campaign . "'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->oauth_token = $row['value'];
			}
			else
				die("No settings parameter 'beru_oauth_token'");
			
			mysqli_free_result($result);

			// Fetch parameter beru_oauth_client_id
			$result = Db::exec_query ("select value from settings where code = 'beru_oauth_client_id_" . $campaign . "'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				$this->oauth_client_id = $row['value'];
			}
			else
				die("No settings parameter 'beru_oauth_client_id'");
			
			mysqli_free_result($result);
		}
		
		// REST Header
		$curl_post_headerberu = array (
				'Content-type: application/json', 
				'Authorization: OAuth oauth_token="' . $this->oauth_token . '",oauth_client_id="' . $this->oauth_client_id . '"'
		);

		try {
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerberu);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);
		}
		catch(Exception $e) {
			return false;
		}						
		return $arrayOut;
	}
}

?>