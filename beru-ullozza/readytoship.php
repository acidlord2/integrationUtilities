<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersBeru2.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('beru-ullozza - readytoship.log');
	
	if (isset($_GET['date']))
		$date = $_GET['date'];
	else
		$date = date('d-m-Y', strtotime('yesterday'));
	
	$filters = array (
		'status' => 'PROCESSING',
		'substatus' => 'STARTED',
		'fromDate' => $date
	);
	$ordersBeru = OrdersBeru2::getOrders (BERU_API_ULLOZZA_CAMPAIGN, $filters);
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

		$statusData = array (
			'order' => array (
				'status' => 'PROCESSING',
				'substatus' => 'READY_TO_SHIP'
			)
		);
		OrdersBeru2::updateOrderStatus (BERU_API_ULLOZZA_CAMPAIGN, $orderBeru['id'], $statusData);
		$orderLabels = OrdersBeru2::getOrdersLabels (BERU_API_ULLOZZA_CAMPAIGN, $orderBeru['id']);
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
