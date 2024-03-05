<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/demands.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('shipping2 - createShipping.log');
	
	$shippingDate = $_REQUEST["shippingDate"];
	$agent = $_REQUEST["agent"];
	$curier = $_REQUEST["curier"];
	$org = $_REQUEST["org"];

	if (isset ($_REQUEST["order"]))
	{
		$orderKey = array_search ($_REQUEST["order"], array_column ($_SESSION['orders'][$shippingDate . $agent . $curier . $org], 'id'));
		if ($orderKey !== false)
			$orders = array(0 => $_SESSION['orders'][$shippingDate . $agent . $curier . $org][$orderKey]);
		else
			$orders = array(0 => Orders::getOrder ($_REQUEST["order"]));
	}
	else
		$orders = $_SESSION['orders'][$shippingDate . $agent . $curier . $org];
	
		$logger -> write (__LINE__ . ' orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	foreach ($orders as $key => $order)
	{
		if (isset ($order['mpcancelFlag']) ? $order['mpcancelFlag'] : false)
			continue;
		
		//$demandTemplate = Demands::getDemandTemplate ($order);
		//$logger -> write (__LINE__ . ' demandTemplate - ' . json_encode ($demandTemplate, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		//$backDemand = Demands::createDemand ($demandTemplate);
		//$logger -> write (__LINE__ . ' backDemand - ' . json_encode ($backDemand, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		$orderData = array (
			'state' => array (
				'meta' => array (
					'href' => $order["shiptype"] == 'mpship' ? MS_SHIPPED_MP_STATE : MS_SHIPPED_STATE,
					'type' => 'state'
				)
			)
		);
		$backUpdateOrder = Orders::updateOrder ($order['id'], $orderData);
		$logger -> write (__LINE__ . ' backUpdateOrder - ' . json_encode ($backUpdateOrder, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		if (isset ($_REQUEST["order"]) && isset ($orderKey) ? $orderKey !== false : false)
			$orderKey2 = $orderKey;
		else
			$orderKey2 = $key;
		
		$_SESSION['orders'][$shippingDate . $agent . $curier . $org][$orderKey2]['shipped'] = true;
		$_SESSION['orders'][$shippingDate . $agent . $curier . $org][$orderKey2]['checked'] = false;
	}
	
	echo json_encode ($_SESSION['orders'][$shippingDate . $agent . $curier . $org], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

