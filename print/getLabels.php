<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('print - getLabels.log');
	$postingNumbers = file_get_contents('php://input');
	$logger -> write (__LINE__ . ' postingNumbers - ' . $postingNumbers);
	$org = $_REQUEST["org"];
	$count = $_REQUEST["count"];
	
	//$postData = array ('posting_number' => json_decode ($postingNumbers, true));
	if ($org == 'aruba') {
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/ordersYandex.php');
	    $orderClass = new OrdersYandex(BERU_API_ARUBA_CAMPAIGN);
	    echo $orderClass->getOrdersLabels(json_decode ($postingNumbers, true), $count);
	}
	elseif ($org == 'Ullo') {
		echo OrdersOzon::getOrderLabel (json_decode ($postingNumbers, true), $count, false);
	}
	else
	{
		echo OrdersOzon::getOrderLabel (json_decode ($postingNumbers, true), $count, true);
	}
	
?>

