<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	//require_once('classes/log.php');
	if (isset($_GET['period']))
		$paramPeriod = ($_GET['period']);
	else
		$paramPeriod = 20;
	
	date_default_timezone_set('Europe/Moscow');
	$from = date ('Y-m-d', strtotime('now-' . $paramPeriod . 'days')) . 'T00:00:00Z';
	$to = date ('Y-m-d', strtotime('now')) . 'T23:59:59Z';
	
	$canceledOrdersOzon = Orders::getOzonOrders($from, $to, 'cancelled');
	$cancelled = 0;
	$marked = 0;
	if (count ($canceledOrdersOzon) > 0)
		foreach ($canceledOrdersOzon as $order)
		{
			$ms_order = Orders::cancelMSOrder ($order);
			//break;
			$cancelled += $ms_order['cancelled'];
			$marked  += $ms_order['marked'];
		}
	echo date ('Y-m-d', strtotime('now')) . 'T00:00:00Z' . '  cancelled: ' . $cancelled . ' marked: ' . $marked;
?>

