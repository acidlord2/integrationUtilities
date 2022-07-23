<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersBeru.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('readytoshipAruba.log');
	
	if (isset($_GET['date']))
		$date = $_GET['date'];
	else
		$date = date('d-m-Y', strtotime('yesterday'));
	
	$filters = array (
		'status' => 'PROCESSING',
		'substatus' => 'STARTED',
		'fromDate' => $date
	);
	$ordersBeru = OrdersBeru::getOrders (BERU_API_ARUBA_CAMPAIGN, $filters);

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
			$logger->write ('orderBeru[id] doesn\'t exists in MS - ' . json_encode ($orderBeru['id'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			continue;
		}
		// compile boxes
		$boxes = array(
			'boxes' => array(
				0 => array (
					'fulfilmentId' => $orderBeru['id'] . '-1',
					'weight' => $orderBeru['delivery']['shipments'][0]['weight'] + 200,
					'width' => $orderBeru['delivery']['shipments'][0]['width'] + 10,
					'height' => $orderBeru['delivery']['shipments'][0]['height'] + 10,
					'depth' => $orderBeru['delivery']['shipments'][0]['depth'] + 10,
					'items' => $orderBeru['delivery']['shipments'][0]['items']
				)
			)
		);
		OrdersBeru::packOrder (BERU_API_ARUBA_CAMPAIGN, $orderBeru['id'], $orderBeru['delivery']['shipments'][0]['id'], $boxes);
		
		$statusData = array (
			'order' => array (
				'status' => 'PROCESSING',
				'substatus' => 'READY_TO_SHIP'
			)
		);
		OrdersBeru::updateOrderStatus (BERU_API_ARUBA_CAMPAIGN, $orderBeru['id'], $statusData);
		$orderLabels = OrdersBeru::getOrdersLebels (BERU_API_ARUBA_CAMPAIGN, $orderBeru['id']);
		if (isset ($orderLabels['result']['parcelBoxLabels'][0]['deliveryServiceId']))
		{
			$ordersMSUpdate[] = array (
				'meta' => $ordersMS[$orderMSKey]['meta'],
				'attributes' => array (
					0 => array (
						'id' => MS_DELIVERYSERVICE_ATTR,
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
