<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('shipping2 - getdata.log');

	$shippingDate = $_REQUEST["shippingDate"];
	$agent = $_REQUEST["agent"];
	$org = $_REQUEST["org"];
	if (isset ($_REQUEST["refresh"]))
		$refresh = (string)$_REQUEST["refresh"];
	else
		$refresh = '0';
	if (!isset ($_SESSION['orders'][$shippingDate . $agent . $org]) || $refresh == '1')
		$_SESSION['orders'][$shippingDate . $agent . $org] = Orders::getOrderList($shippingDate, $agent, $org);
	$logger->write(__LINE__ . ' orders - ' . json_encode ($_SESSION['orders'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	echo json_encode ($_SESSION['orders'][$shippingDate . $agent . $org], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

