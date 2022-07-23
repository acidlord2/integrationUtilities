<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('print - changeStatuses.log');
	$data = json_decode(file_get_contents('php://input'), true);
	$logger -> write (__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	$postData = array();
	//$postData = array ('posting_number' => json_decode ($postingNumbers, true));
	foreach ($data['orders'] as $orderMS)
	{
		$order = array (
			'meta' => array (
				'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . '/' . $orderMS,
				'type' => 'customerorder',
				'mediaType' => 'application/json'
			),
			'state' => array (
				'meta' => array (
				    'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDERSTATE . '/' . ($data['agent'] == 'Curiers' ? MS_PACKEDDELIVERY_STATE_ID : MS_PACKEDMP_STATE),
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			)
		);
		array_push ($postData, $order);
	}
	$ordersClass = new OrdersMS();
	$logger -> write (__LINE__ . ' postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	$return = $ordersClass -> createCustomerorder ($postData);
	$logger -> write (__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	echo 'ok';
?>

