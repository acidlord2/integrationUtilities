<?php
	require_once('classes/orders.php');
	require_once('classes/log.php');
	$shippingDate = $_REQUEST["shippingDate"];
	$website = $_REQUEST["website"];
	$goodsFlag = $_REQUEST["goodsFlag"];
	$beruFlag = $_REQUEST["beruFlag"];
?>
<tr id = "teble_header">
	<th>Номер заказа</th>
	<th>Дата заказа</th>
	<th>Сумма заказа</th>
	<th>Сайт</th>
	<th>GOODS</th>
	<th>Беру</th>
	<th>Комплектация</th>
</tr>
<?php
	foreach (Orders::getList($shippingDate, $website, $goodsFlag, $beruFlag) as $order)
	{ ?>
		<tr>
		<td> <?php echo $order['name']; ?></td>
		<td> <?php echo $order['moment']; ?></td>
		<td> <?php echo $order['sum']; ?></td>
		<td> <?php echo $order['website']; ?></td>
		<td> <?php echo $order['goodsFlag']; ?></td>
		<td> <?php echo $order['beruFlag']; ?></td>
		<td><button type="button" id = "<?php echo $order['name']; ?>" onclick="openOrder('<?php echo $order['id']; ?>')">Скомплектовать</button></td>
		</tr>
<?php	}
?>

