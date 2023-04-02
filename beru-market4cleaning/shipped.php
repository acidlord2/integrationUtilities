<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/beruApi.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/msApi.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

	$page = 0;
	$count = 0;
	date_default_timezone_set('Europe/Moscow');
	while (true) {
	
	    $service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . '/?filter=deliveryPlannedMoment' . urlencode ('>') . '=' . date('Y-m-d', strtotime("now")) . ';state=' . MS_SHIPPED_MP_STATE . ';agent=' . MS_BERU_AGENT . ';organization=' . MS_KAORI . '&limit=100&offset=' . $page;
		MSAPI::getMSData ($service_url, $backJson, $back);
		
		if (!count ($back['rows']))
			break;
		
		$count += count ($back['rows']);
		$page += 100;
		foreach ($back['rows'] as $order) {

			$status_data = array (
				'order' => array (
					'status' => 'PROCESSING',
					'substatus' => 'SHIPPED'
				)
			);

			$service_url = 'https://api.partner.market.yandex.ru/v2/campaigns/22113023/orders/' . $order['name'] . '/status.JSON';
			BeruAPI::putBeruData ($service_url, $status_data, $backJson, $back);
		}
	}
	echo 'shipped orders ' . $count;
?>

