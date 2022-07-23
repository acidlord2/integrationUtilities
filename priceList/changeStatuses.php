<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('print-changeStatuses.log');
	$ordersMS = file_get_contents('php://input');
	$logger -> write ('01-ordersMS - ' . json_encode ($ordersMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	$postData = array();
	//$postData = array ('posting_number' => json_decode ($postingNumbers, true));
	foreach (json_decode($ordersMS, true) as $orderMS)
	{
		$order = array (
			'meta' => array (
				'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . '/' . $orderMS,
				'type' => 'customerorder',
				'mediaType' => 'application/json'
			),
			'state' => array (
				'meta' => array (
					'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDERSTATE . '/' . MS_PACKEDMP_STATE,
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			)
		);
		array_push ($postData, $order);
	}
	$ordersClass = new OrdersMS();
	$logger -> write ('02-postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	$return = $ordersClass -> createCustomerorder ($postData);
	$logger -> write ('03-return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	echo 'ok';
?>

