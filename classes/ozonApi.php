<?php
/**
 *
 * @class OzonAPI
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class OzonAPI
{
    public static function postOzonData ($service_url, $postdata, &$dataOut, $clientId, $apiKey)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		
		// REST Header
		$curl_post_header = array (
				'Content-type: application/json', 
				'Client-Id: ' . $clientId,
				'Api-Key: ' . $apiKey
		);

		for ($i=0;$i<3;$i++)
		{
			try {
				$curl = curl_init($service_url);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_header);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
				$jsonOut = curl_exec($curl);
				curl_close($curl);
				$dataOut = json_decode ($jsonOut, true);
			}
			catch(Exception $e) {
				continue;
			}						
			if 
			return true;
		}
	}
}

?>