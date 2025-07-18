<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('shipping2 - cancelOrder.log');

	$shippingDate = $_REQUEST["shippingDate"];
	$agent = $_REQUEST["agent"];
	$org = $_REQUEST["org"];
	
	if (isset ($_REQUEST["order"]))
	{
		$orderKey = array_search ($_REQUEST["order"], array_column ($_SESSION['orders'][$shippingDate . $agent . $org], 'id'));
		if ($orderKey !== false)
			$orders = array(0 => $_SESSION['orders'][$shippingDate . $agent . $org][$orderKey]);
		else
			$orders = array(0 => Orders::getOrder ($_REQUEST["order"]));
	}
	else
		$orders = $_SESSION['orders'][$shippingDate . $agent . $org];
	//$logger -> write ('orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	foreach ($orders as $key => $order)
	{
		
		$orderData = array (
			'state' => array (
				'meta' => array (
					'href' => MS_CANCEL_STATE,
					'type' => 'state'
				)
			)
		);
		$backUpdateOrder = Orders::updateOrder ($order['id'], $orderData);
		//$logger -> write ('backUpdateOrder - ' . json_encode ($backUpdateOrder, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		if (isset ($_REQUEST["order"]) && isset ($orderKey) ? $orderKey !== false : false)
			$orderKey2 = $orderKey;
		else
			$orderKey2 = $key;

		$_SESSION['orders'][$shippingDate . $agent . $org][$orderKey2]['cancelled'] = true;
		$_SESSION['orders'][$shippingDate . $agent . $org][$orderKey2]['checked'] = false;
	}

	echo json_encode ($_SESSION['orders'][$shippingDate . $agent . $org], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

