<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('packingsGetData.log');
	
	
	$shippingDate = $_REQUEST["shippingDate"];
	$agent = $_REQUEST["agent"];
	$org = $_REQUEST["org"];
	$goodstype = $_REQUEST["goodstype"];
	
	$index = $shippingDate . $agent . $org . $goodstype;
	$_SESSION['products'][$index] = array();
	
	function addProduct($product, $quantity, $index)
	{
		if (isset ($_SESSION['products'][$index][$product['id']]))
			$_SESSION['products'][$index][$product['id']]['quantity'] += $quantity;
		else
		{
			$_SESSION['products'][$index][$product['id']] = $product;
			$_SESSION['products'][$index][$product['id']]['quantity'] = $quantity;
		}
		//$logger->write('_SESSION[products] - ' . json_encode ($_SESSION['products'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}

	$logger->write('shippingDate . agent . org . goodstype - ' . $shippingDate . $agent . $org . $goodstype);
	//if (!isset ($_SESSION['products'][$index]) || $refresh == '1')
	if (true)
	{
		$filter = '';
		
		if ($agent == 'Goods')
			$filter .= 'agent=' . MS_GOODS_AGENT . ';';
		if ($agent == 'Beru')
			$filter .= 'agent=' . MS_BERU_AGENT . ';';
		if ($agent == 'Ozon')
			$filter .= 'agent=' . MS_OZON_AGENT . ';';
		if ($agent == 'Aliexpress')
			$filter .= 'agent=' . MS_ALI_AGENT . ';';
		if ($agent == 'Wildberries')
			$filter .= 'agent=' . MS_WB_AGENT . ';';
		if ($agent == 'Curiers')
			$filter .= 'project=' . MS_PROJECT_4CLEANING . ';project=' . MS_PROJECT_10KIDS . ';';

		if ($org == 'Ullo')
			$filter .= 'organization=' . MS_ULLO . ';';
		if ($org == '4cleaning')
			$filter .= 'organization=' . MS_4CLEANING . ';';
		if ($org == 'Kaori')
			$filter .= 'organization=' . MS_KAORI . ';';
		if ($org == 'IPGyumyush')
			$filter .= 'organization=' . MS_IPGYUMYUSH . ';';

		$filter .= 'deliveryPlannedMoment%3E=' . $shippingDate . '%2000:00:00;';
		$filter .= 'deliveryPlannedMoment%3C=' . $shippingDate . '%2023:59:59;';
		
		$filter .= 'state=' . MS_CONFIRMGOODS_STATE . ';';
		if ($agent == 'Curiers')
			$filter .= 'state=' . MS_CONFIRM_STATE . ';';
		
		$orders = OrdersMS::findOrders($filter);

		$logger->write('orders - ' . json_encode (array_column ($orders, 'name'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		foreach ($orders as $key => $order)
		{
			APIMS::getMSData($order['positions']['meta']['href'], $positionsJson, $positions);
			foreach ($positions['rows'] as $position)
			{
				APIMS::getMSData($position['assortment']['meta']['href'], $assortmentJson, $assortment);
				$gategoryKey = array_search (MS_PRODUCT_CATEGORY_ATTR, array_column ($assortment['attributes'], 'id'));

				if ($gategoryKey === false && $goodstype == 'Others')
				{
					addProduct ($assortment, $position['quantity'], $index);
					continue;
				}
				$gategoryAttrArray = explode('/', $assortment['attributes'][$gategoryKey]['value']['meta']['href']);
				$gategoryAttrValue = end($gategoryAttrArray);
				
				$logger->write('gategoryAttrValue - ' . json_encode ($gategoryAttrValue, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

				if (in_array ($gategoryAttrValue, MS_PRODUCT_CATEGORY_OTHERS) && $goodstype == 'Others')
					addProduct ($assortment, $position['quantity'], $index);
				else if (in_array ($gategoryAttrValue, MS_PRODUCT_CATEGORY_COSMETICS) && $goodstype == 'Cosmetics')
					addProduct ($assortment, $position['quantity'], $index);
				else if (in_array ($gategoryAttrValue, MS_PRODUCT_CATEGORY_DIAPERS) && $goodstype == 'Diapers')
					addProduct ($assortment, $position['quantity'], $index);
			}
			//$logger->write('_SESSION - ' . json_encode ($_SESSION, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		}
		usort  ($_SESSION['products'][$index], function($a, $b) {
			if($a['name'] != $b['name'])return ($a['name'] < $b['name']) ? -1 : 1;
			return 0;}
		);
	}
	$logger->write('products - ' . json_encode ($_SESSION['products'][$index], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	echo json_encode ($_SESSION['products'][$index], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

