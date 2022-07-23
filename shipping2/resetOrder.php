<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/demands.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('cancelOrder.log');

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
	//$logger -> write ('orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	foreach ($orders as $key => $order)
	{
		if ($order['cancelled'] || $order['shipped'])
		{	
			$orderData = array (
				'state' => array (
					'meta' => array (
						'href' => $order['shiptype'] == 'pickup' ? MS_SHIPPICKUP_STATE : ($order['shiptype'] == 'mpship' ? MS_SHIPGOODS_STATE : MS_SHIP_STATE),
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
			
			if ($order['shipped'])
			{
				$orderTemp = Orders::getOrder ($order['id']);
				if (isset ($orderTemp['demands']))
					foreach ($orderTemp['demands'] as $demand)
						Demands::deleteDemandId ($demand['meta']['href']);
				$_SESSION['orders'][$shippingDate . $agent . $curier . $org][$orderKey2]['shipped'] = false;
			}
			else
				$_SESSION['orders'][$shippingDate . $agent . $curier . $org][$orderKey2]['cancelled'] = false;
		}
	}
	
	echo json_encode ($_SESSION['orders'][$shippingDate . $agent . $curier . $org], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

