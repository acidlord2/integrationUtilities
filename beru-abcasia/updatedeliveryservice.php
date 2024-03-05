<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/beruApi.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	$logger = new Log ('beru-abcasia - updatedeliveryservice.log');
	
	if (isset($_GET['date']))
		$date = $_GET['date'];
	else
		$date = date('d-m-Y', strtotime('-10days'));
	$page = 1;
	$maxpage = 1;
	while ($page <= $maxpage) { 
	
		$service_url = 'https://api.partner.market.yandex.ru/v2/campaigns/21632670/orders.JSON?status=PROCESSING&substatus=READY_TO_SHIP&page='.$page.'&fromDate=' . $date;
		$logger->write ('service_url - ' . $service_url);
		BeruAPI::getBeruData ($service_url, $backJson, $back);
		$logger->write ('backJson - ' . $backJson);
		
		if (isset ($back['error']))
		{
			echo $backJson;
			return;
		}
		
		$maxpage = $back ['pager']['pagesCount'];
		$page++;
		$count = 0;
		foreach ($back['orders'] as $order) {
			$count++;
			// get labels
			$service_url = 'https://api.partner.market.yandex.ru/v2/campaigns/21632670/orders/' . $order['id'] . '/delivery/labels/data.JSON';
			BeruAPI::getBeruData ($service_url, $backJson, $back);
			$orderMS = Orders::findOrder($order['id']);
			if ($orderMS && isset ($back['result']['parcelBoxLabels'][0]['deliveryServiceId']))
			{
				$postData = array (
					'attributes' => array (
						0 => array (
						    'meta' => array(
						        'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDERSTATE . '/' . MS_DELIVERYSERVICE_ATTR,
						        'type' => 'state',
						        'mediaType' => 'application/json'
						    ),
						    'value' => $back['result']['parcelBoxLabels'][0]['deliveryServiceId']
						)
					)
				);
				Orders::updateOrder($orderMS['id'], $postData);
			}
		}
	}
	//echo '<br><br>';
	echo 'Обработано заказов ' . $count;
?>
