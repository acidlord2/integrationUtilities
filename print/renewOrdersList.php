<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

	$shippingDate = $_REQUEST["shippingDate"];
	$agent = $_REQUEST["agent"];
	$org = $_REQUEST["org"];

//	else
	$orders = $_SESSION['orders'][$shippingDate . $agent . $org];
	$orderClass = false;
	foreach ($orders as $order)
	{
		if ($order['class'] !== $orderClass)
		{
?>			
		<tr>
			<td class = "midHeader" colspan=<?php if ($agent == 'Ozon' || $org == 'aruba' || $agent == 'WB' || $agent == 'SM') { ?>11<?php } else { ?>10<?php } ?> style="vertical-align: middle;">
				<?php
					if ($order['class'] == 1)
						echo 'Заказы с косметикой';
					if ($order['class'] == 2)
					    echo 'Подгузники';
					    //echo 'Подгузники с 1 шт.';
					if ($order['class'] == 3)
						echo 'Подгузники от 2 до 4 шт.';
					if ($order['class'] == 4)
						echo 'Подгузники свыше 4 шт.';
					if ($order['class'] == 0)
						echo 'Прочее';
				?>
				<span style="float:right;">
					<?php if ($agent == 'Ozon' || $org == 'aruba' || $agent == 'WB' || $agent == 'SM') { ?>
						<button type="button" id = "printStickerButton" onclick="printSticker(<?php echo $order['class']; ?>)">Печать стикеров (по 10 шт)</button>
					<?php } ?>
					<button type="button" id = "printInvoiceButton" onclick="printInvoice(<?php echo $order['class']; ?>)">Печать вкладышей (по 10 шт)</button>
					<button type="button" id = "changeStatus" onclick="changeStatus(<?php echo $order['class']; ?>)">Изменить статус</button>
				</span>
			</td>
		</tr>
<?php
			$orderClass = $order['class'];
		}
?>
		<tr id = "<?php echo $order['id']; ?>">
			<?php if ($agent == 'Ozon' || $org == 'aruba' || $agent == 'WB' || $agent == 'SM') { ?>
				<td style = "text-align: center"><input type="checkbox" orderclass=<?php echo $order['class']; ?> id="oz<?php echo $order['name']; ?>" name = "ozonCheckbox<?php echo $order['class']; ?>" onChange="changeOzon()" disabled></td>
			<?php } ?>
			<td style = "text-align: center"><input type="checkbox" orderclass=<?php echo $order['class']; ?> id="ms<?php echo $order['id']; ?>" name = "msCheckbox<?php echo $order['class']; ?>" onChange="changeMS()" disabled></td>
			<td><?php echo $order['name']; ?></td>
			<td><?php echo (isset($order['barcode']) ? $order['barcode'] : ''); ?></td>
			<td><?php echo $order['moment']; ?></td>
			<td><?php echo $order['deliveryPlannedMoment']; ?></td>
			<td style = "text-align: right"><?php echo $order['sum'] / 100; ?></td>
			<td><?php echo $order['agent']['name']; ?></td>
			<td> <?php echo $order['organization']['name']; ?></td>
			<td><?php echo $order['state']['name']; ?></td>
			<td style = "text-align: center"><?php echo $order['mpcancel']; ?></td>
		</tr>
<?php	
	}
?>

