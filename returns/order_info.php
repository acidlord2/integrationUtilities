<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	$orderNumber = $_REQUEST["orderNumber"];
	$order = Orders::findOrder($orderNumber);
	//$logger = new Log ('tmp2.log');
	//$logger -> write ($blob);
	echo (json_encode ($order, JSON_UNESCAPED_UNICODE));
?>

