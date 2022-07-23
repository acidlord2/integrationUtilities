<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	//require_once('classes/log.php');
	$orderId = $_REQUEST["orderId"];
	$blob = Orders::ship($orderId);
	//$logger = new Log ('tmp2.log');
	//$logger -> write ($blob);
	echo ($blob);
s?>

