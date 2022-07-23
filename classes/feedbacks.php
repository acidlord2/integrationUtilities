<?php
/**
 *
 * @class Orders
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Feedbacks
{
	private static $oauth_token = false;
	private static $oauth_client_id = false;
		
    public static function getMSData($service_url, &$jsonOut, &$arrayOut)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		
		if (!self::$oauth_token || !self::$oauth_client_id)
		{
			// Fetch parameter ms_user
			$result = Db::exec_query ("select value from settings where code = '4cleaning_oauth_token'");
			
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$oauth_token = $row['value'];
			}
			else
				die("No settings parameter 'oauth_token'");
			
			mysqli_free_result($result);

			// Fetch parameter ms_password
			$result = Db::exec_query ("select value from settings where code = '4cleaning_oauth_client_id'");

			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_assoc($result);
				self::$oauth_client_id = $row['value'];
			}
			else
				die("No settings parameter 'oauth_client_id'");
			
			mysqli_free_result($result);
		}
		// REST Header
		$curl_get_header = array (
				'Content-type: application/json', 
				'Authorization: OAuth oauth_token="' . self::$oauth_token . '",oauth_client_id="' . self::$oauth_client_id . '"'
		);

		try {
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_get_header);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
			$jsonOut = curl_exec($curl);
			$arrayOut = json_decode ($jsonOut, true);
			curl_close($curl);
		}
		catch(Exception $e) {
			return false;
		}						
		return true;
	}
	

    public static function getYandexFeedbacks($campaing, $from_date)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('feedbacks.log');
		
		// get feedbacks
		
		$return = array();
		$nextPageToken = '';
		while (true)
		{
			$service_url = YA_FEEDBACKURL . $campaing . '/feedback/updates.json?limit=100&from_date=' . $from_date . ($nextPageToken == ''? '' : '&page_token=' . $nextPageToken);
			$logger -> write ('getYandexFeedbacks.service_url - ' . $service_url);
			
			self::getMSData ($service_url, $json, $data);
			$logger -> write ('getYandexFeedbacks.data - ' . json_encode ($data));
			if (isset ($data['result']['feedbackList']) ? count ($data['result']['feedbackList']) : false)
			{
				$nextPageToken = $data['result']['paging']['nextPageToken'];
				$return = array_merge ($return, $data['result']['feedbackList']);
			}
			else
				break;
		}
		return $return;
	}
	
    public static function updateFeedbacks($feedbacks)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		
		// get feedbacks
		if (!$result = Db::exec_query_array ('select * from yandex_feedbacks'))
			$result = array ();
		
		foreach ($feedbacks as $feedback)
		{
			if (array_search($feedback['id'], array_column($result, 'feedback_id')))
				Db::exec_query ('update yandex_feedbacks set deleted = "' . ($feedback['state'] == 'DELETED' ? 1 : 0)  . '",name = "' . $feedback['author']['name']	 . '",shop="' . $feedback['shop']['name'] . '",shopOrderId = "' . $feedback['order']['shopOrderId'] . '",grade = ' . $feedback['grades']['average'] . ',recommend = ' . (int)$feedback['recommend'] . ',verified = ' . (int)$feedback['verified'] . ' where feedback_id = ' . $feedback['id']);
			else
				Db::exec_query ('insert into yandex_feedbacks values (' . $feedback['id'] . ',' . 0 . ',"' . $feedback['author']['name'] . '","' . $feedback['shop']['name'] . '","' . $feedback['order']['shopOrderId'] . '",' . $feedback['grades']['average'] . ',' . (int)$feedback['recommend'] . ',' . (int)$feedback['verified'] . ',' . ($feedback['state'] == 'DELETED' ? 1 : 0) . ')');
		}
		$return = Db::exec_query_array ('select * from yandex_feedbacks');
		return $return;
		
	}
    public static function searchFeedbacks($orderNumbers)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		
		$logger = new Log ('feedbacks.log');
		$logger -> write ('searchFeedbacks.orderNumbers - ' . json_encode ($orderNumbers));
		
		// get feedbacks
		if (!$result = Db::exec_query_array ('select * from yandex_feedbacks'))
			$result = array ();
		
		$logger -> write ('searchFeedbacks.result - ' . json_encode ($result));
		
		$return = array();
		
		foreach (explode(",", $orderNumbers) as $orderNumber)
		{
			$search = array ();
			foreach (array_column($result, 'shopOrderId') as $key => $feedback)
			if (strpos($feedback, $orderNumber) !== false)
				$search[] = $key;
			//$search = array_keys(array_column($result, 'shopOrderId'),trim($orderNumber));
			$logger -> write ('searchFeedbacks.search - ' . json_encode ($search));
			if ($search)
				foreach ($search as $key) 
					$return[] = $result[$key];
			else
				$return[] = array ('shopOrderId' => $orderNumber, 'error' => 'Отзыв не найден');
		}
		
		return $return;
	}
    public static function updateFeedbackStatus($feedback)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$result = Db::exec_query ('select status from yandex_feedbacks where feedback_id = ' . $feedback);
		$row = mysqli_fetch_assoc($result);
		Db::exec_query ('update yandex_feedbacks set status = ' . (int)(!$row['status']) . ' where feedback_id = ' . $feedback);
	}
}

?>