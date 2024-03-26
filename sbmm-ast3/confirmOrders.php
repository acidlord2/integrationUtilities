<?php
	/**
	 * Creates new order
	 *
	 * @author GPOLYAN <acidlord@yandex.ru>
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sbermegamarket/Order.php');

	$log = new Log('sbmm-ast3 - confirmOrders.log');
	
	$sbmmOrdersClass = new \Classes\Sbermegamarket\Order(SBMM_SHOP_AST3);
	$orders = $sbmmOrdersClass->searchOrders(['NEW']);
	
	if (count($orders['shipments']) == 0)
	{
		echo 'Confirmed: 0 orders';
		return;
	}
	
	$shipments = $sbmmOrdersClass->getOrders($orders['shipments']);
	foreach($shipments['shipments'] as $shipment)
	{
		if (isset($shipment['items']))
		{
			$items = array();
			foreach ($shipment['items'] as $item)
			{
				$items [] = array (
					'itemIndex' => $item['itemIndex'],
					'offerId' => $item['offerId']
				);
			}
		}
		$confirmData = array (
			'data' => array (
				'shipments' => array (
					0 => array (
					
						'shipmentId' => $shipment['shipmentId'],
						'orderCode' => $shipment['shipmentId'],
						'items' => $items
					)
				)
			)
		);
		$sbmmOrdersClass->confirm($confirmData);
	}
	echo 'Confirmed: ' . count($shipments['shipments']) . ' orders';

?>
