<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$orderNumber = $_REQUEST['order'];
	$logger = new Log ('renew_orders.log');
	$logger->write ('orderNumber - ' . $orderNumber);
?>
<tr id = "teble_header">
	<th>Номер заказа</th>
	<th>Дата заказа</th>
	<th>Дата отгрузки план</th>
	<th>Сумма заказа</th>
	<th>Организация</th>
	<th>Контрагент</th>
	<th>Статус</th>
	<th>Отменен маркетплейс?</th>
</tr>
<?php
	$orders = Orders::findOrders2($orderNumber);
	if ($orders)
		foreach ($orders as $order)
		{ ?>
			<tr>
				<td> <?php echo $order['name']; ?></td>
				<td> <?php echo $order['moment']; ?></td>
				<td> <?php echo $order['deliveryPlannedMoment']; ?></td>
				<td> <?php echo $order['sum'] / 100; ?></td>
				<td> <?php echo $order['organization']['name']; ?></td>
				<td> <?php echo $order['agent']['name']; ?></td>
				<td> <?php echo $order['state']['name']; ?></td>
				<td> <?php echo $order['cancelFlag']; ?></td>
			</tr>
<?php	}
?>

