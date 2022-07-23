<?php
/**
 *
 * @class DemandsMS
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class DemandsMS
{
	private $log;
	private $apiMSClass;

	private $cache = array ();

	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

		$this->log = new Log('classes - MS - demandsMS.log');
		$this->apiMSClass = new APIMS();
	}	
	/**
	* function findDemands - function find ms demands by ms filter passed
	*
	* @filters string - ms filter 
	* @return array - result as array of demands
	*/
	public function findDemands($filters, $page = 0)
    {
		$demands = array();
		if ($page == 0){
    		$offset = 0;
		}
		else {
		    $offset = ($page - 1) * MS_LIMIT;
		}
		
		$this->log->write (__LINE__ . ' findDemands.filters - ' . json_encode ($filters, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		while (true)
		{
			$filter = '';
			if (is_array($filters))
				foreach ($filters as $key => $value)
					$filter .= $key . '=' . $value . ';';
			else
				$filter = $filters;
				$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_DEMAND . '?filter=' . $filter . '&limit=' . MS_LIMIT . '&offset=' . $offset;
			$this->log->write (__LINE__ . ' findDemands.service_url - ' . $service_url);
			$response = $this->apiMSClass->getData($service_url);
			$offset += MS_LIMIT;
			$demands = array_merge ($demands, $response['rows']);
			if ($offset >= $response['meta']['size'] || $page != 0)
			{
			    $size = $response['meta']['size'];
			    $limit = $response['meta']['limit'];
			    break;			
			}
		}
       
		if ($page == 0) {
		    $return = $demands;
		}
		else {
		    $return = array(
		        'demands' => $demands,
		        'size' => $size,
		        'limit' => $limit
		    );
		}
		
		$this->log->write (__LINE__ . ' findDemands.return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $return;
	}
}

?>