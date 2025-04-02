<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersBeru2.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('beru-kosmos - readytoship.log');
	
	if (isset($_GET['date']))
		$date = $_GET['date'];
	else
		$date = date('d-m-Y', strtotime('yesterday'));
	
	$filters = array (
		'status' => 'PROCESSING',
		'substatus' => 'STARTED',
		'fromDate' => $date
	);
	$ordersBeru = OrdersBeru2::getOrders (BERU_API_KOSMOS_CAMPAIGN, $filters);
	$logger->write (__LINE__ . ' orderBeru - ' . json_encode ($ordersBeru, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	if (is_array ($ordersBeru) && !count ($ordersBeru))
	{
		echo 'Processed 0 orders';
		return;

	}

	$notFound = 0;
	$readyToShip = 0;
	$notUpdated = 0;
	
	$idsBeru = array_column ($ordersBeru, 'id');
	$ordersMS = OrdersMS::findOrdersByNames($idsBeru);
	$ordersMSUpdate = array();
	foreach ($ordersBeru as $orderBeru)
	{
		$orderMSKey = array_search ($orderBeru['id'], array_column ($ordersMS, 'name'));
		if ($orderMSKey === false)
		{
			$logger->write (__LINE__ . ' orderBeru[id] doesn\'t exists in MS - ' . json_encode ($orderBeru['id'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			continue;
		}
		// $items = array();
		// foreach ($orderBeru['items'] as $item)
		// {
		//     $items[] = array ('id' => $item['id'], 'count' => $item['count']);
		// }
		// // compile boxes
		// $boxes = array(
		// 	'boxes' => array(
		// 		0 => array (
		// 			'fulfilmentId' => $orderBeru['id'] . '-1',
		// 		    'weight' => isset($orderBeru['delivery']['shipments'][0]['weight']) ? $orderBeru['delivery']['shipments'][0]['weight'] + 200 : 1000,
		// 		    'width' => isset($orderBeru['delivery']['shipments'][0]['width']) ? $orderBeru['delivery']['shipments'][0]['width'] + 10 : 20,
		// 		    'height' => isset($orderBeru['delivery']['shipments'][0]['height']) ? $orderBeru['delivery']['shipments'][0]['height'] + 10 : 20,
		// 		    'depth' => isset($orderBeru['delivery']['shipments'][0]['depth']) ? $orderBeru['delivery']['shipments'][0]['depth'] + 10 : 20,
		// 		    'items' => isset($orderBeru['delivery']['shipments'][0]['items']) ? $orderBeru['delivery']['shipments'][0]['items'] : $items
		// 		)
		// 	)
		// );
		// OrdersBeru2::packOrder (BERU_API_KOSMOS_CAMPAIGN, $orderBeru['id'], $orderBeru['delivery']['shipments'][0]['id'], $boxes);
		
		$statusData = array (
			'order' => array (
				'status' => 'PROCESSING',
				'substatus' => 'READY_TO_SHIP'
			)
		);
		OrdersBeru2::updateOrderStatus (BERU_API_KOSMOS_CAMPAIGN, $orderBeru['id'], $statusData);
		$orderLabels = OrdersBeru2::getOrdersLebels (BERU_API_KOSMOS_CAMPAIGN, $orderBeru['id']);
		if (isset ($orderLabels['result']['parcelBoxLabels'][0]['deliveryServiceId']))
		{
			$ordersMSUpdate[] = array (
				'meta' => $ordersMS[$orderMSKey]['meta'],
				'attributes' => array (
					0 => array (
					    'meta' => array(
					        'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERYSERVICE_ATTR,
					        'type' => 'attributemetadata',
					        'mediaType' => 'application/json'),
					    'value' => $orderLabels['result']['parcelBoxLabels'][0]['deliveryServiceId']
					)
				)
			);
			$readyToShip++;
		}
		else
			$notUpdated++;
	}
	// update ms orders
	OrdersMS::updateOrderMass($ordersMSUpdate);

	echo 'Packed: ' . $readyToShip . ' not found: ' . $notFound . ' not updated: ' . $notUpdated;
?>
