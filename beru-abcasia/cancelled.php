<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/ordersYandex.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
$ordersClass = new OrdersMS();
$ordersYandexClass = new OrdersYandex(BERU_API_ABCASIA_CAMPAIGN);

$filter = 'agent=' . MS_BERU_AGENT . ';state=' . MS_CANCEL_STATE . ';moment%3E=' . date ('Y-m-d%20H:i:s', strtotime('-1 day')) . ';' . MS_MPCANCEL_ATTR . '!=true';
$orders = $ordersClass->findOrders ($filter);

if (!count ($orders))
{
	echo 'Processed orders: 0';
	return;
}

foreach ($orders as $order)
{
	$ordersYandex = $ordersYandexClass->updateStatus ($order['name'], 	'CANCELLED', 'SHOP_FAILED');
}

echo 'Processed orders: ' . count ($order);

?>