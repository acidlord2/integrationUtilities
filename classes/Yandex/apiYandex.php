<?php
/**
 *
 * @class APIYandex
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class APIYandex
{
	private $log;
	
	private $oauth_client_id;
	private $oauth_token;
	private $campaign;

	public function __construct($campaign)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		
        $this->campaign = $campaign;
		$this->log = new Log('classes - Yandex - apiYandex.log');
		
		if (!$this->oauth_token || !$this->oauth_client_id)
		{
		    // Fetch parameter beru_oauth_token
		    $result = Db::exec_query_array ("select value from settings where code = 'beru_oauth_token_" . $campaign . "'");
		    //$this->log->write(__LINE__ . ' result - ' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		    
		    if (count ($result)) {
		        $this->oauth_token = $result[0]['value'];
		    }
		    else {
		        $this->log->write(__LINE__ . 'No settings parameter beru_oauth_token_' . $campaign);
		    }
	
		    // Fetch parameter beru_oauth_client_id
		    $result = Db::exec_query_array ("select value from settings where code = 'beru_oauth_client_id_" . $campaign . "'");
		    //$this->log->write(__LINE__ . ' result - ' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		    
		    if (count ($result)) {
		        $this->oauth_client_id = $result[0]['value'];
		    }
		    else {
		        $this->log->write(__LINE__ . 'No settings parameter beru_oauth_client_id_' . $campaign);
		    }
		}
	}	
	
	
    public  function getData($url)
	{
	    $this->log->write(__LINE__ . ' url - ' . $url);
	    // REST Header
	    $curl_post_headerberu = array (
	        'Content-type: application/json',
	        'Authorization: OAuth oauth_token="' . $this->oauth_token . '",oauth_client_id="' . $this->oauth_client_id . '"'
	    );
	    
	    $this->log->write(__LINE__ . ' getData.curl_post_headerberu - ' . json_encode($curl_post_headerberu, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    $count = 0;
	    while (true)
		{
            $count++;

            $curl = curl_init($url);
    		curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerberu);
    		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($curl, CURLOPT_FAILONERROR, true);
    		$jsonOut = curl_exec($curl);
    		$arrayOut = json_decode ($jsonOut, true);
    		
    		if (curl_errno($curl))
    		{
    		    $this->log->write(__LINE__ . ' Error No: ' . curl_errno($curl) . ' | Error msg: ' . curl_error($curl));
    		    if ($count < 3)
    		        continue;
    		}
    		curl_close($curl);
    		
    		return $arrayOut;
		}
	}
	
	public  function getDataBlob($url)
	{
	    $this->log->write(__LINE__ . ' url - ' . $url);
	    // REST Header
	    $curl_post_headerberu = array (
	        'Content-type: application/json',
	        'Authorization: OAuth oauth_token="' . $this->oauth_token . '",oauth_client_id="' . $this->oauth_client_id . '"'
	    );
	    
	    $this->log->write(__LINE__ . ' getData.curl_post_headerberu - ' . json_encode($curl_post_headerberu, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	    $count = 0;
	    while (true)
	    {
	        $count++;
	        
	        $curl = curl_init($url);
	        curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerberu);
	        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($curl, CURLOPT_FAILONERROR, true);
	        $jsonOut = curl_exec($curl);
	        
	        if (curl_errno($curl))
	        {
	            $this->log->write(__LINE__ . ' Error No: ' . curl_errno($curl) . ' | Error msg: ' . curl_error($curl));
	            if ($count < 3)
	                continue;
	        }
	        curl_close($curl);
	        
	        return $jsonOut;
	    }
	}
	
	public function putData($url, $postdata)
	{
	    $this->log->write(__LINE__ . ' url - ' . $url);
	    // REST Header
		$curl_post_headerberu = array (
				'Content-type: application/json', 
				'Authorization: OAuth oauth_token="' . $this->oauth_token . '",oauth_client_id="' . $this->oauth_client_id . '"'
		);

		$this->log->write(__LINE__ . ' putData.curl_post_headerberu - ' . json_encode($curl_post_headerberu, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		$this->log->write(__LINE__ . ' putData.postdata - ' . json_encode($postdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		
		$count = 0;
		while (true)
		{
		    $count++;
		    
		    $curl = curl_init($url);
    		curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_post_headerberu);
    		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
    		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
    		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
    		curl_setopt($curl, CURLOPT_FAILONERROR, true);
    		$jsonOut = curl_exec($curl);
    		$arrayOut = json_decode ($jsonOut, true);
    		
    		if (curl_errno($curl))
    		{
    		    $this->log->write(__LINE__ . ' Error No: ' . curl_errno($curl) . ' | Error msg: ' . curl_error($curl));
    		    if ($count < 3)
    		        continue;
    		}
    		curl_close($curl);
    		
    		return $arrayOut;
		}
		
	}
}

?>