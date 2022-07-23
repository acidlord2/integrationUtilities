<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$dateFrom = $_REQUEST["dateFrom"];
	$dateTo = $_REQUEST["dateTo"];
	$agent = $_REQUEST["goodsFlag"];
	$organization = $_REQUEST["organization"];
?>
<tr id = "teble_header">
	<th>Номер заказа</th>
	<th>Дата заказа</th>
	<th>Дата отгрузки</th>
	<th>Сумма заказа</th>
	<th>Оплачено</th>
	<th>Товар</th>
	<th>Код</th>
	<th>Количество</th>
	<th>Цена</th>
	<th>Сумма</th>
</tr>
<?php
	foreach (Orders::getClaimList($dateFrom, $dateTo, $goodsFlag, $beruFlag) as $order)
	{ ?>
		<tr>
			<td> <?php echo $order['order']['name']; ?></td>
			<td> <?php echo $order['order']['moment']; ?></td>
			<td> <?php echo $order['order']['deliveryPlannedMoment']; ?></td>
			<td> <?php echo $order['order']['sum']/100; ?></td>
			<td> <?php echo $order['order']['payedSum']/100; ?></td>
			<td> <?php echo $order['product']['name']; ?></td>
			<td> <?php echo $order['product']['code']; ?></td>
			<td> <?php echo $order['position']['quantity']; ?></td>
			<td> <?php echo $order['position']['price']/100; ?></td>
			<td> <?php echo $order['position']['quantity'] * $order['position']['price']/100; ?></td>
		</tr>
<?php	}
?>

