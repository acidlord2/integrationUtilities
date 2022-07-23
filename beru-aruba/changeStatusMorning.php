<?php
	/**
	 * Creates new order
	 *
	 * @class ControllerExtensionBeruOrder
	 * @author GPOLYAN <acidlord@yandex.ru>
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/ordersYandex.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	$logger = new Log('beru-aruba-changeStatusMorning.log');
	$ordersMSClass = new OrdersMS();
	$ordersYandexClass = new OrdersYandex(BERU_API_ARUBA_CAMPAIGN);
	
	$filter = array (
		'project' => MS_PROJECT_2HRS,
		'agent' => MS_BERU_AGENT,
		'organization' => MS_IPGYUMYUSH,
		'state' => MS_MPNEW_STATE
	);
	
	$ordersMS = $ordersMSClass->findOrders($filter);
	$logger->write(__LINE__ . ' ordersMS - ' . json_encode ($ordersMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	$count = 0;
	$errorCount = 0;
	foreach ($ordersMS as $orderMS)
	{
		$orderDataYandex = $ordersYandexClass->getOrder ($orderMS['name']);
		$logger->write(__LINE__ . ' orderDataYandex - ' . json_encode ($orderDataYandex, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		if ($orderDataYandex['order']['status'] == 'PROCESSING' && $orderDataYandex['order']['substatus'] == 'STARTED')
		{
			$orderData['state'] = array(
				'meta' => array(
					'href' => MS_CONFIRMBERU_STATE,
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			);
			
			$updatedOrderMS = $ordersMSClass->updateCustomerorder ($orderMS['id'], $orderData);
			$logger->write(__LINE__ . ' updatedOrderMS - ' . json_encode ($updatedOrderMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$count ++;
			if (isset ($updatedOrderMS['errors']))
				$errorCount ++;
		}
	}
	echo 'Processed: ' . count ($ordersMS) . ', updated: ' . $count . ' , error: ' . $errorCount;

?>
