<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

	$shippingDate = $_REQUEST["shippingDate"];
	$agent = $_REQUEST["agent"];
	$org = $_REQUEST["org"];

	if (isset ($_REQUEST["order"]))
	{
		$nameKey = array_search ($_REQUEST["order"], array_column($_SESSION['orders'][$shippingDate . $agent . $org], 'name'));
		$barcodeKey = array_search ($_REQUEST["order"], array_column($_SESSION['orders'][$shippingDate . $agent . $org], 'barcode'));
		if ($nameKey !== false || $barcodeKey !== false)
		{
			$key = $nameKey !== false ? $nameKey : $barcodeKey;

			$_SESSION['orders'][$shippingDate . $agent . $org][$key]['checked'] = $_SESSION['orders'][$shippingDate . $agent . $org][$key]['shipped'] !== true && $_SESSION['orders'][$shippingDate . $agent . $org][$key]['cancelled'] !== true;
			$_SESSION['orders'][$shippingDate . $agent . $org][$key]['scanCount']++;
			$_SESSION['currentOrder'] = $_SESSION['orders'][$shippingDate . $agent . $org][$key];
		}
		else
			$_SESSION['currentOrder'] = null;
	}
	$orders = $_SESSION['orders'][$shippingDate . $agent . $org];
	foreach ($orders as $order)
	{
?>
		<?php 
			$showButton1 = !$order['mpcancelFlag'];
			$showButton2 = $order['mpcancelFlag'];
			$showButton3 = false;
			$color = '#ffffff';
			if ($order['cancelled'] || $order['shipped'])
			{
				$color = $order['cancelled'] === true ? '#ff7a7a' : '#79fc9c';
				$showButton1 = false;
				$showButton2 = false;
				$showButton3 = true;
			}
			else if ($order['checked'])
				$color = $order['mpcancelFlag'] === true ? '#FADBD8' : '#EAFAF1';
		?>
		<tr id = "<?php echo $order['id']; ?>" style = "background-color: <?php echo $color ?>">
			<td style = "text-align: center"><input type="checkbox" id="ch<?php echo $order['id']; ?>" name = "orderCheckbox" disabled <?php echo $order['checked'] ? 'checked' : '' ?>><span class="count" id="count<?php echo $order['id']; ?>"><?php echo $order['scanCount']; ?></span></td>
			<td name="orderNumber"><?php echo $order['name']; ?></td>
			<td><?php echo (isset($order['barcode']) ? $order['barcode'] : ''); ?></td>
			<td><?php echo $order['moment']; ?></td>
			<td><?php echo $order['deliveryPlannedMoment']; ?></td>
			<td style = "text-align: right"><?php echo $order['sum'] / 100; ?></td>
			<td><?php echo $order['agent']['name']; ?></td>
			<td> <?php echo $order['organization']['name']; ?></td>
			<td><?php echo $order['state']['name']; ?></td>
			<td style = "text-align: center"><?php echo $order['mpcancel']; ?></td>
			<td style = "text-align: center">
				<button class="changed-ok" type="button" id = "b1<?php echo $order['id']; ?>" onclick="shipOrder('<?php echo $order['id']; ?>')" <?php echo $showButton1 ? '' : 'style = "display:none"' ?>>Отгрузить</button>
				<button class="changed-error" type="button" id = "b2<?php echo $order['id']; ?>" onclick="cancelOrder('<?php echo $order['id']; ?>')" <?php echo $showButton2 ? '' : 'style = "display:none"' ?>>Отменить</button> 
				<button class="changed-done" type="button" id = "b3<?php echo $order['id']; ?>" onclick="resetOrder('<?php echo $order['id']; ?>' )" <?php echo $showButton3 ? '' : 'style = "display:none"' ?>>Сбросить</button>
			</td>
		</tr>
<?php	}
?>

