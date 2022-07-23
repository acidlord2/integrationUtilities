<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/beruApi.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('updatedeliveryserviceAruba.log');
	
	if (isset($_GET['date']))
		$date = $_GET['date'];
	else
		$date = date('d-m-Y', strtotime('-10days'));
	$page = 1;
	$maxpage = 1;
	while ($page <= $maxpage) { 
	
		$service_url = 'https://api.partner.market.yandex.ru/v2/campaigns/' . BERU_API_ARUBA_CAMPAIGN . '/orders.JSON?status=PROCESSING&substatus=READY_TO_SHIP&page='.$page.'&fromDate=' . $date;
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
			$service_url = 'https://api.partner.market.yandex.ru/v2/campaigns/' . BERU_API_ARUBA_CAMPAIGN . '/orders/' . $order['id'] . '/delivery/labels/data.JSON';
			BeruAPI::getBeruData ($service_url, $backJson, $back);
			$orderMS = Orders::findOrder($order['id']);
			if ($orderMS && isset ($back['result']['parcelBoxLabels'][0]['deliveryServiceId']))
			{
				$postData = array (
					'attributes' => array (
						0 => array (
							'id' => MS_DELIVERYSERVICE_ATTR,
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
