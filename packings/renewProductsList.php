<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

	$shippingDate = $_REQUEST["shippingDate"];
	$agent = $_REQUEST["agent"];
	$org = $_REQUEST["org"];
	$goodstype = $_REQUEST["goodstype"];

//	else
	$products = $_SESSION['products'][$shippingDate . $agent . $org . $goodstype];
	//echo $_SESSION['orders'][$shippingDate . $agent . $curier . $org];
	//foreach (Orders::getOrderList($shippingDate, $agent, $curier) as $order)
	foreach ($products as $product)
	{
?>
		<tr id = "<?php echo $product['code']; ?>">
			<td class="tableBig"><?php echo $product['name']; ?></td>
			<td class="tableBig"><?php echo $product['code']; ?></td>
			<td class="tableBig"><?php echo (isset($product['barcodes']) ? implode(',', $product['barcodes']) : ''); ?></td>
			<td class="tableBig"><?php echo $product['quantity']; ?></td>
		</tr>
<?php	
	}
?>

