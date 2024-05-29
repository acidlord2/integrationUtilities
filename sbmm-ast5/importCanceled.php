<?php
	/**
	 * Creates new order
	 *
	 * @author GPOLYAN <acidlord@yandex.ru>
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sbermegamarket/Order.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');

	$log = new Log('sbmm-ast5 - importCanceled.log');
	
	$sbmmOrdersClass = new \Classes\Sbermegamarket\Order(SBMM_SHOP_AST5);
	$dateFrom = date("Y-m-d", strtotime ("-30 days")) . 'T00:00:00+03:00';
	$dateTo = date("Y-m-d", strtotime ("now")) . 'T23:59:59+03:00';
	
	$orders = $sbmmOrdersClass->searchOrders(['MERCHANT_CANCELED', 'CUSTOMER_CANCELED'], $dateFrom, $dateTo);

	if (count($orders['shipments']) == 0)
	{
		echo 'Imported: 0 orders';
		return;
	}
	$shipments = $sbmmOrdersClass->getOrders($orders['shipments']);

	$conn = Db::get_connection();
	$cancelled = 0;
	$cancelledMarked = 0;
	$alreadyCancelled = 0;
	$noOrderFound = 0;
	$ordersMSClass = new OrdersMS(); 
	foreach($shipments['shipments'] as $shipment)
	{
		$sql = 'select orderId from cancelled_orders where orderId = "' . $shipment['shipmentId'] . '"';
		$result = Db::exec_query_array($sql);
		if ($result)
		{
			$alreadyCancelled++;
			continue;
		}
		$sql = 'insert into cancelled_orders (orderId) values ("' . $shipment['shipmentId'] . '")';
		Db::exec_query($sql);
		$orderData = $ordersMSClass->findOrders(array('name' => $shipment['shipmentId']));
		if(count($orderData) === 0)
		{
			$noOrderFound++;
			continue;
		}
		$updateData = array (
			'attributes' => array(
				0 => array (
					'meta' => array(
						'href' => MS_MPCANCEL_ATTR,
						'type' => 'attributemetadata',
						'mediaType' => 'application/json'
					),
					'value' => true
				)
			)
		);
		if(in_array($orderData[0]['state']['meta']['href'], [MS_NEW_STATE,MS_MPNEW_STATE,MS_CONFIRM_STATE,MS_CONFIRMBERU_STATE]))
		{
			$updateData['state'] = array (
				'meta' => array(
					'href' => MS_CANCEL_STATE,
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			);
			$cancelled++;
		}
		else $cancelledMarked++;
		$ordersMSClass->updateCustomerorder($orderData[0]['id'], $updateData);
	}
	echo 'Orders cancelled: ' . $cancelled . ', marked as cancelled: ' . $cancelled . ', already processed: ' . $alreadyCancelled . ', not found: '. $noOrderFound;
?>
