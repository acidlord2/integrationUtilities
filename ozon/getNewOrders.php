<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	//require_once('classes/log.php');
	//$newOrdersOzon = Orders::getOzonOrders('2019-12-16T10:57:21Z', '2020-12-16T11:57:21Z', "awaiting_packaging");
	$newOrdersOzon = Orders::getOzonOrders(date ('Y-m-d', strtotime('-1 day')) . 'T00:00:00Z', date ('Y-m-d', strtotime('now')) . 'T23:59:59Z', "awaiting_packaging");
	if (count ($newOrdersOzon) > 0)
		foreach ($newOrdersOzon as $order)
		{
			$order_data['organization'] = MS_ULLO;
			$orderMS = $order;
			$orderMS['project'] = MS_PROJECT_OZON;
			$ms_order = Orders::createMSOrder ($orderMS);
			if (isset ($ms_order['id']))
			{
				Orders::packOzonOrder($order);
				//Orders::getPackageLabel($order, $ms_order['id']);
				
			}
			//break;
		}
		
	echo 'Processed ' . count ($newOrdersOzon);
?>

