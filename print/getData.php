<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('print - getData.log');

	$shippingDate = $_REQUEST["shippingDate"];
	$agent = $_REQUEST["agent"];
	$org = $_REQUEST["org"];

	$index = $shippingDate . $agent . $org;
	$_SESSION['products'][$index] = array();

	///if (!isset ($_SESSION['orders'][$shippingDate . $agent . $org]) || $refresh == '1')
	if (true)
	{
		$filter = '';
		
		if ($agent == 'Goods')
			$filter .= 'agent=' . MS_GOODS_AGENT . ';';
		if ($agent == 'Beru')
			$filter .= 'agent=' . MS_BERU_AGENT . ';';
		if ($agent == 'Ozon')
			$filter .= 'agent=' . MS_OZON_AGENT . ';';
		if ($agent == 'Ali')
			$filter .= 'agent=' . MS_ALI_AGENT . ';';
		if ($agent == 'WB')
			$filter .= 'agent=' . MS_WB_AGENT . ';';
		if ($agent == 'Curiers')
		    $filter .= 'project=' . MS_PROJECT_4CLEANING . ';project=' . MS_PROJECT_10KIDS . ';project=' . MS_PROJECT_YANDEX_DBS . ';project=' . MS_PROJECT_YANDEX_DBS . ';project=' . MS_PROJECT_MSKOREA . ';';

		if ($org == 'Ullo')
			$filter .= 'organization=' . MS_ULLO . ';';
		if ($org == '4cleaning')
			$filter .= 'organization=' . MS_4CLEANING . ';';
		if ($org == 'Kaori')
			$filter .= 'organization=' . MS_KAORI . ';';
		if ($org == 'IPGyumyush')
		    $filter .= 'organization=' . MS_IPGYUMYUSH . ';';
		if ($org == 'aruba')
	        $filter .= 'project=' . MS_PROJECT_2HRS . ';';
		if ($org == 'AST1')
	        $filter .= 'project=' . MS_PROJECT_SBMM_AST1 . ';';
		if ($org == 'AST2')
	        $filter .= 'project=' . MS_PROJECT_SBMM_AST2 . ';';
		if ($org == 'AST3')
	        $filter .= 'project=' . MS_PROJECT_SBMM_AST3 . ';';
		if ($org == 'AST4')
	        $filter .= 'project=' . MS_PROJECT_SBMM_AST4 . ';';
		if ($org == 'AST5')
	        $filter .= 'project=' . MS_PROJECT_SBMM_AST5 . ';';
		if ($org == 'AST6')
	        $filter .= 'project=' . MS_PROJECT_SBMM_AST6 . ';';
		if ($org == 'alians')
	        $filter .= 'project=' . MS_PROJECT_YANDEX_SUMMIT . ';';
		if ($org == 'vysota')
	        $filter .= 'project=' . MS_PROJECT_YANDEX_VYSOTA . ';';
		if ($org == 'Kosmos')
	        $filter .= 'project=' . MS_PROJECT_WB . ';';
			        
		$filter .= 'deliveryPlannedMoment%3E=' . $shippingDate . '%2000:00:00;';
		$filter .= 'deliveryPlannedMoment%3C=' . $shippingDate . '%2023:59:59;';
		
		if ($agent == 'Curiers')
		{
		    $filter .= 'state=' . MS_CONFIRM_STATE . ';';
		    
		} else {
		    $filter .= 'state=' . MS_CONFIRMGOODS_STATE . ';';
		}
		
		$_SESSION['orders'][$shippingDate . $agent . $org] = OrdersMS::findOrders($filter);
		
		foreach ($_SESSION['orders'][$shippingDate . $agent . $org] as $key => $order)
		{
			$vclass = 0;
			$vcount = 0;
			APIMS::getMSData($order['positions']['meta']['href'], $positionsJson, $positions);
			foreach ($positions['rows'] as $position)
			{
				APIMS::getMSData($position['assortment']['meta']['href'], $assortmentJson, $assortment);
				if (strpos($assortment['pathName'], 'Гиена и Косметика'))
				{
					$vclass = 1;
				}
				if (strpos($assortment['pathName'], '0 - ИЛЬЯ (ПОДГ+ПИТАНИЕ)'))
				{
				    $vclass = 2;
				}
				break;
				//if (strpos($assortment['pathName'], '0 - ИЛЬЯ (ПОДГ+ПИТАНИЕ)'))
				//	$vcount += $position['quantity'];
					
				//if ($vcount == 1)
				//	$vclass = 2;
				//else if ($vcount > 1 && $vcount <= 4)
				//	$vclass = 3;
				//else if ($vcount > 4)
				//	$vclass = 4;
			}

			$_SESSION['orders'][$shippingDate . $agent . $org][$key]['class'] = $vclass;
			$_SESSION['orders'][$shippingDate . $agent . $org][$key]['product'] = $assortment['name'];
			
			APIMS::getMSData($order['state']['meta']['href'], $response_Json, $response);
			$_SESSION['orders'][$shippingDate . $agent . $org][$key]['state']['name'] = $response['name'];

			APIMS::getMSData($order['agent']['meta']['href'], $response_Json, $response);
			$_SESSION['orders'][$shippingDate . $agent . $org][$key]['agent']['name'] = $response['name'];

			APIMS::getMSData($order['organization']['meta']['href'], $response_Json, $response);
			$_SESSION['orders'][$shippingDate . $agent . $org][$key]['organization']['name'] = $response['name'];

			APIMS::getMSData($order['organization']['meta']['href'], $response_Json, $response);
			$_SESSION['orders'][$shippingDate . $agent . $org][$key]['organization']['name'] = $response['name'];

			$_SESSION['orders'][$shippingDate . $agent . $org][$key]['mpcancel'] = MY_FALSE;
			$_SESSION['orders'][$shippingDate . $agent . $org][$key]['mpcancelFlag'] = false;

			foreach ($order['attributes'] as $arrtKey => $attribute)
			{
				if ($attribute['meta']['href'] == MS_BARCODE_ATTR)
					$_SESSION['orders'][$shippingDate . $agent . $org][$key]['barcode'] = $attribute['value'];
				if ($attribute['meta']['href'] == MS_MPCANCEL_ATTR)
				{
					$_SESSION['orders'][$shippingDate . $agent . $org][$key]['mpcancel'] = $attribute['value'] ? MY_TRUE : MY_FALSE;
					$_SESSION['orders'][$shippingDate . $agent . $org][$key]['mpcancelFlag'] = $attribute['value'];
				}
			}
		}
		uasort  ($_SESSION['orders'][$shippingDate . $agent . $org], function($a, $b) {
		    if ($a['class'] === $b['class'])
		        return $a['product'] <=> $b['product'];
	        return $a['class'] <=> $b['class'];
		});
	}
	$logger->write(__LINE__ . ' orders - ' . json_encode ($_SESSION['orders'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	echo json_encode ($_SESSION['orders'][$shippingDate . $agent . $org], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

