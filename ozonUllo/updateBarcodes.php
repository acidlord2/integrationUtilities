<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('updateBarcodes.log');
	$filters = array (
		'agent' => MS_OZON_AGENT,
		'organization' => MS_ULLO,
		'state' => MS_PACKED_STATE,
		MS_BARCODE_ATTR => ''
	);
	

	$ordersMS = OrdersMS::findOrders($filters);
	//$logger->write ('findOrders.ordersMS - ' . json_encode ($ordersMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	if (count ($ordersMS))
	{
		foreach ($ordersMS as $orderMS)
		$parameters = array (
			'posting_number' => $orderMS['name'],
			'with' => array (
				'analytics_data' => false,
				'barcodes' => true,
				'financial_data' => false
			)
		);

		$orderOzon = OrdersOzon::getOrder($parameters);
		
		if (isset ($orderOzon['result']['barcodes']['upper_barcode']))
			OrdersMS::updateOrder ($orderMS['id'], array (
				'attributes' => array (
					0 => array(
						'id' => '51ec2167-e895-11e8-9ff4-31500000db84',
						'value' => (string)$orderOzon['result']['barcodes']['upper_barcode']
					)
				)
			));
		else
			$logger->write ('Order ' . $orderMS	['name'] . ' has no barcodes');
		echo 'Processed ' . count ($ordersMS) . ' orders';
	}
	else
		echo 'No orders w/o barcodes';
	
?>

