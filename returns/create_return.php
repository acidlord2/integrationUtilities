<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	//require_once('classes/log.php');
	$demandUrl = $_REQUEST["url"];
	$orderId = $_REQUEST["order"];
	//echo $demandUrl;
	if (Orders::checkDemand($demandUrl)) {
		echo ('Возврат для отгрузки по заказу уже создан');
		return;
	}
	
	Orders::createReturn($demandUrl);
	Orders::cancelOrder($orderId);
	
	//$logger = new Log ('tmp2.log');
	//$logger -> write ($blob);
	echo 'Ok';
?>

