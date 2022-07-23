<?php
/**
 *
 * @class Demands
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Demands
{
	//return template
    public static function getDemandTemplate($order)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('demands.log');
		$service_url = MS_DEMANDURL . 'new';
		// get template
		$logger->write ('getDemandTemplate.service_url - ' . $service_url);
		$postdata = array (
			'customerOrder' => array (
				'meta' => $order['meta']
			)
		);
		$logger->write ('getDemandTemplate.postdata - ' . $postdata);
		MSAPI::putMSData($service_url, $postdata, $response_demandJson, $response_demand);
		$logger -> write ('getDemandTemplate.response_demand - ' . json_encode($response_demand, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $response_demand;
 	}
	//return template
    public static function createDemand($demand)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('demands.log');
		$service_url = MS_DEMANDURL;
		// get template
		$logger->write ('createDemand.service_url - ' . $service_url);
		$logger->write ('createDemand.demand - ' . $demand);
		MSAPI::postMSData($service_url, $demand, $response_demandJson, $response_demand);
		$logger -> write ('createDemand.response_demand - ' . json_encode($response_demand, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $response_demand;
 	}
    public static function deleteDemandId($demandUrl)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log ('demands.log');
		$service_url = $demandUrl;
		// get template
		$logger->write ('deleteDemandId.service_url - ' . $service_url);
		MSAPI::deleteMSData($service_url, $response_demandJson, $response_demand);
		$logger -> write ('deleteDemandId.response_demand - ' . json_encode($response_demand, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $response_demand;
 	}


}

?>