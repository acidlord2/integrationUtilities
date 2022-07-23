<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	//$logger = new Log ('getOrder.log');
	//$shippingDate = $_REQUEST["shippingDate"];
	$shippingDate = $_REQUEST["shippingDate"];
	$agent = $_REQUEST["agent"];
	$curier = $_REQUEST["curier"];
	$org = $_REQUEST["org"];
	//$logger -> write ('_REQUEST - ' . json_encode ($_REQUEST, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	//$logger -> write ('_SESSION - ' . json_encode ($_SESSION['orders'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	$nameKey = array_search ($_REQUEST["order"], array_column($_SESSION['orders'][$shippingDate . $agent . $curier . $org], 'name'));
	$barcodeKey = array_search ($_REQUEST["order"], array_column($_SESSION['orders'][$shippingDate . $agent . $curier . $org], 'barcode'));
	//$logger -> write ('nameKey - ' . json_encode ($nameKey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	//$logger -> write ('barcodeKey - ' . json_encode ($barcodeKey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

	
	if ($nameKey !== false || $barcodeKey !== false)
		if (isset ($_REQUEST["select"]))
			echo $nameKey !== false ? $_SESSION['orders'][$shippingDate . $agent . $curier . $org][$nameKey][$_REQUEST["select"]] : $_SESSION['orders'][$shippingDate . $agent . $curier . $org][$barcodeKey][$_REQUEST["select"]];
		else
			echo $nameKey !== false ? $_SESSION['orders'][$shippingDate . $agent . $curier . $org][$nameKey]['id'] : $_SESSION['orders'][$shippingDate . $agent . $curier . $org][$barcodeKey]['id'];
	//echo $_SESSION['orders'][$shippingDate . $agent . $curier . $org];
	//foreach (Orders::getOrderList($shippingDate, $agent, $curier) as $order)
?>

