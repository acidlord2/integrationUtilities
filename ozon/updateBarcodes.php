<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('ozon - updateBarcodes.log');
	$filters = array (
		'agent' => MS_OZON_AGENT,
		'organization' => MS_ULLO,
		'state' => MS_PACKED_STATE,
		MS_BARCODE_ATTR => '',
	    MS_BARCODE_ATTR => urlencode('%101%0')
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
		
		if (isset ($orderOzon['result']['barcodes']['lower_barcode']))
			OrdersMS::updateOrder ($orderMS['id'], array (
				'attributes' => array (
					0 => array(
					    'meta' => array (
					        'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_BARCODE_ATTR_ID,
					        'type' => 'attributemetadata',
					        'mediaType' => 'application/json'
					    ),
					    'value' => (string)$orderOzon['result']['barcodes']['lower_barcode']
					)
				)
			));
		else
			$logger->write (__LINE__ . ' Order ' . $orderMS	['name'] . ' has no barcodes');
		echo 'Processed ' . count ($ordersMS) . ' orders';
	}
	else
		echo 'No orders w/o barcodes';
	
?>

