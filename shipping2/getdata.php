<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('getdata.log');

	$shippingDate = $_REQUEST["shippingDate"];
	$agent = $_REQUEST["agent"];
	$curier = $_REQUEST["curier"];
	$org = $_REQUEST["org"];
	if (isset ($_REQUEST["refresh"]))
		$refresh = (string)$_REQUEST["refresh"];
	else
		$refresh = '0';
	//$logger->write('shippingDate . agent . curier . org - ' . $shippingDate . $agent . $curier . $org);
	if (!isset ($_SESSION['orders'][$shippingDate . $agent . $curier . $org]) || $refresh == '1')
		$_SESSION['orders'][$shippingDate . $agent . $curier . $org] = Orders::getOrderList($shippingDate, $agent, $curier, $org);
	$logger->write('orders - ' . json_encode ($_SESSION['orders'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	echo json_encode ($_SESSION['orders'][$shippingDate . $agent . $curier . $org], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

