<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/beruApi.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('readytoship.log');
	
	if (isset($_GET['date']))
		$date = $_GET['date'];
	else
		$date = date('d-m-Y', strtotime('yesterday'));
	$page = 1;
	$maxpage = 1;
	while ($page <= $maxpage) { 
	
		$service_url = 'https://api.partner.market.yandex.ru/v2/campaigns/21632670/orders.JSON?status=PROCESSING&page='.$page.'&fromDate=' . $date;
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
			if (isset($order['delivery']['shipments'][0]['id']) || $order['substatus'] == 'STARTED')
			{
				$count++;
				// compile boxes
				$boxes = array(
					'boxes' => array(
						0 => array (
							'fulfilmentId' => $order['id'] . '-1',
							'weight' => $order['delivery']['shipments'][0]['weight'] + 200,
							'width' => $order['delivery']['shipments'][0]['width'] + 10,
							'height' => $order['delivery']['shipments'][0]['height'] + 10,
							'depth' => $order['delivery']['shipments'][0]['depth'] + 10,
							'items' => $order['delivery']['shipments'][0]['items']
						)
					)
				);
				//echo json_encode($boxes);
				$service_url = 'https://api.partner.market.yandex.ru/v2/campaigns/21632670/orders/' . $order['id'] . '/delivery/shipments/' . $order['delivery']['shipments'][0]['id'] . '/boxes.JSON';
				BeruAPI::putBeruData ($service_url, $boxes, $backJson, $back);
				//echo $service_url;

				$status_data = array (
					'order' => array (
						'status' => 'PROCESSING',
						'substatus' => 'READY_TO_SHIP'
					)
				);

				$service_url = 'https://api.partner.market.yandex.ru/v2/campaigns/21632670/orders/' . $order['id'] . '/status.JSON';
				BeruAPI::putBeruData ($service_url, $status_data, $backJson, $back);
				// get labels
				$service_url = 'https://api.partner.market.yandex.ru/v2/campaigns/21632670/orders/' . $order['id'] . '/delivery/labels/data.JSON';
				BeruAPI::getBeruData ($service_url, $backJson, $back);
				$orderMS = Orders::findOrder($order['id']);
				if ($orderMS && isset ($back['result']['parcelBoxLabels'][0]['deliveryServiceId']))
				{
					$postData = array (
						'attributes' => array (
							0 => array (
								'id' => 'e8df1e60-b268-11ea-0a80-052600006e80',
								'value' => $back['result']['parcelBoxLabels'][0]['deliveryServiceId']
							)
						)
					);
					Orders::updateOrder($orderMS['id'], $postData);
				}
			}
		}
	}
	//echo '<br><br>';
	echo 'Обработано заказов ' . $count;
?>
