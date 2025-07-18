<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('packingGetdata.log');

	$shippingDate = $_REQUEST["shippingDate"];
	$agent = $_REQUEST["agent"];
	$org = $_REQUEST["org"];
	if (isset ($_REQUEST["refresh"]))
		$refresh = (string)$_REQUEST["refresh"];
	else
		$refresh = '0';

	if (!isset ($_SESSION['orders'][$shippingDate . $agent . $org]) || $refresh == '1')
	{
		$filter = '';
		
		if ($agent == 'Goods')
			$filter .= 'agent=' . MS_GOODS_AGENT . ';';
		if ($agent == 'Beru')
			$filter .= 'agent=' . MS_BERU_AGENT . ';';
		if ($agent == 'Ozon')
			$filter .= 'agent=' . MS_OZON_AGENT . ';';

		if ($org == 'ullo')
			$filter .= 'organization=' . MS_ULLO . ';';
		if ($org == '4cleaning')
			$filter .= 'organization=' . MS_4CLEANING . ';';
		if ($org == 'kaori')
			$filter .= 'organization=' . MS_KAORI . ';';

		$filter .= 'deliveryPlannedMoment%3E=' . $shippingDate . '%2000:00:00;';
		$filter .= 'deliveryPlannedMoment%3C=' . $shippingDate . '%2023:59:59;';
		
		$filter .= 'state=' . MS_CONFIRMGOODS_STATE . ';';
		
		//$_SESSION['orders'][$shippingDate . $agent . $org] = OrdersMS::findOrders($filter);

		$orders = OrdersMS::findOrders($filter);
		
		$productList = array();
		foreach ($orders as $order)
		{
			$positions = OrdersMS::getOrderPositions($order);
			foreach ($positions as $position)
			{
				if ($position['product']['productCode'] == 'product')
					if (isset($productList[$position['product']['productId']]['quantity']))
						$productList[$position['product']['productId']]['quantity'] += $position['quantity'];
					else
						$productList[$position['product']['productId']] = array (
							'name' => $position['product']['productName'],
							'code' => $position['product']['productCode'],
							'pathName' => $position['product']['productPathName'],
							'quantity' => $position['quantity']
						);					
			}
		}
		//$logger->write('orders - ' . json_encode ($_SESSION['orders'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
	}

	//$logger->write('shippingDate . agent . curier . org - ' . $shippingDate . $agent . $curier . $org);
/* 	if (!isset ($_SESSION['orders'][$shippingDate . $agent . $curier . $org]) || $refresh == '1')
		$_SESSION['orders'][$shippingDate . $agent . $curier . $org] = Orders::getOrderList($shippingDate, $agent, $curier, $org);
	$logger->write('orders - ' . json_encode ($_SESSION['orders'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); */
	
	echo json_encode (productList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

