<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('shipping2 - getOrder.log');
	//$shippingDate = $_REQUEST["shippingDate"];
	$shippingDate = $_REQUEST["shippingDate"];
	$agent = $_REQUEST["agent"];
	$org = $_REQUEST["org"];
	$logger -> write (__LINE__ . '_REQUEST - ' . json_encode ($_REQUEST, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	$nameKey = array_search ($_REQUEST["order"], array_column($_SESSION['orders'][$shippingDate . $agent . $org], 'name'));
	$barcodeKey = array_search ($_REQUEST["order"], array_column($_SESSION['orders'][$shippingDate . $agent . $org], 'barcode'));
	//$logger -> write ('nameKey - ' . json_encode ($nameKey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	//$logger -> write ('barcodeKey - ' . json_encode ($barcodeKey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

	
	if ($nameKey !== false || $barcodeKey !== false)
		if (isset ($_REQUEST["select"]))
			echo $nameKey !== false ? $_SESSION['orders'][$shippingDate . $agent . $org][$nameKey][$_REQUEST["select"]] : $_SESSION['orders'][$shippingDate . $agent . $org][$barcodeKey][$_REQUEST["select"]];
		else
			echo $nameKey !== false ? $_SESSION['orders'][$shippingDate . $agent . $org][$nameKey]['id'] : $_SESSION['orders'][$shippingDate . $agent . $org][$barcodeKey]['id'];
	//echo $_SESSION['orders'][$shippingDate . $agent . $org];
	//foreach (Orders::getOrderList($shippingDate, $agent, $org) as $order)
?>

