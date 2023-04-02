<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('ozonUlloDbs - updateShipmentDate.log');
	//$newOrdersOzon = Orders::getOzonOrders('2019-12-16T10:57:21Z', '2020-12-16T11:57:21Z', "awaiting_packaging");
	$ordersOzon = Orders::getOzonOrders(date ('Y-m-d', strtotime('-2 day')) . 'T00:00:00Z', date ('Y-m-d', strtotime('now')) . 'T23:59:59Z', "awaiting_deliver", true);
	$logger -> write (__LINE__ . ' ordersOzon: ' . json_encode($ordersOzon, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	if (count ($ordersOzon) > 0)
		foreach ($ordersOzon as $order)
		{
			$ms_order = Orders::findOrder ($order['posting_number']);
			$logger -> write (__LINE__ . ' ms_order: ' . json_encode($ms_order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$ms_order['deliveryPlannedMoment'] = DateTime::createFromFormat('Y-m-d\TH:i:sO', $order['shipment_date'])->format('Y-m-d H:i:s');
			
			Orders::updateOrder($ms_order['id'], $ms_order);
		}
	echo 'Updated '. count ($ordersOzon) . ' orders';
?>

