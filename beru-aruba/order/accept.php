<?php
	/**
	 * Creates new order
	 *
	 * @class ControllerExtensionBeruOrder
	 * @author GPOLYAN <acidlord@yandex.ru>
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/products.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	$logger = new Log('beru-aruba - order - accept.log'); //just passed the file name as file_name.log
	$logger->write(__LINE__ . " _GET - " . json_encode ($_GET, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	// check auth-token
	if (isset($_GET['auth-token']) ? (string)$_GET['auth-token'] != Settings::getSettingsValues('beru_auth_token_21994237') : true)
	{
		header('HTTP/1.0 403 Forbidden');
		echo 'You are forbidden!';
		return;
	}
	// check fake
	$fake = isset($_GET['fake']) ? (bool)$_GET['fake'] : false;
	
	if ($_SERVER['REQUEST_METHOD'] != 'POST')
	{
		header('HTTP/1.0 400 Bad Request');
		echo 'Request must be POST';
		return;
	}
	
	$data = json_decode (file_get_contents('php://input'), true);
	$logger->write(__LINE__ . " data - " . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	if (!isset ($data['order']))
	{
		header('HTTP/1.0 400 Bad Request');
		echo 'Missing required parameter "order"';
		return;
	}

	if (!isset ($data['order']['items']))
	{
		header('HTTP/1.0 400 Bad Request');
		echo 'Missing required parameter "items"';
		return;
	}
	
	$ok = true;
	
	$order = Orders::findOrder($data['order']['id']);
	if ($order)
	{
	    $return ['order']['accepted'] = true;
	    $return ['order']['id'] = (string)$order['name'];
	    
	    header('Content-Type: application/json');
	    echo json_encode($return);
	    return;
	}
	// prepare data order
	$order_data = array();

	$order_data['name'] = $data['order']['id'];
	$order_data['order_id'] = '';
	date_default_timezone_set('Europe/Moscow');
	$order_data['moment'] = date('Y-m-d H:i:s', strtotime('now'));
	$order_data['deliveryPlannedMoment'] = DateTime::createFromFormat('d-m-Y', $data['order']['delivery']['shipments'][0]['shipmentDate'])->format('Y-m-d H:i:s');
	$order_data['barcodes']['upper_barcode'] = '';
	

	$return = array();
	$fakeOrder = isset ($data['order']['fake']) ? (bool)$data['order']['fake'] : false;
	
	$order_data['state'] = MS_MPNEW_STATE; //MS_CONFIRMBERU_STATE;
	$order_data['applicable'] = !(bool)$fakeOrder;
	$order_data['description'] = $fakeOrder ? 'ТЕСТ ЗАКАЗ' : '';
	$order_data['products'] = array();
	$order_data['agent'] = MS_BERU_AGENT;
	$order_data['organization'] = MS_IPGYUMYUSH;
	$order_data['project'] = MS_PROJECT_2HRS;
	$order_data['vatEnabled'] = true;
	$order_data['vatIncluded'] = true;
	$order_data['attributes'] = array();
	$order_data['attributes'][MS_PAYMENTTYPE_ATTR] = 'https://online.moysklad.ru/api/remap/1.1/entity/customentity/e0430541-d622-11e8-9109-f8fc00212299/27155816-dd0b-11e8-9109-f8fc0015616b';
	$order_data['attributes'][MS_DELIVERYTIME_ATTR] = MS_DELIVERYTIME_VALUE1;
	$order_data['attributes'][MS_DELIVERY_ATTR] = MS_SHIPTYPE_BERU;
	
	//$order_data['taxSystem'] = 'GENERAL_TAX_SYSTEM';
	$products = Products::getMSStock (array_column ($data['order']['items'], 'offerId'));
	foreach ($data['order']['items'] as $item)
	{
		$pkey = array_search ($item['offerId'], array_column ($data['order']['items'], 'offerId'));
		if ($pkey !== false)
		{
			if ($item['vat'] == 'VAT_10' || $item['vat'] == 'VAT_10_110')
				$vat = 10;
			else if ($item['vat'] == 'VAT_20' || $item['vat'] == 'VAT_20_120')
				$vat = 20;
			else $vat = 0;
			$order_data['products'][] = array(
				'sku' => $item['offerId'],
				'quantity' => $item['count'],
				'price' => $item['price'] + $item['subsidy'],
				'vat' => $vat
			);
		}
		else
		{
			if ($ok)
			{
				$ok = false;
				header('HTTP/1.0 400 Bad Request');
			}
			echo 'Product sku ' . $item['offerId'] . ' did not found';
			return;
		}
	}
	
	if ($ok)
	{
	    $logger->write(__LINE__ . " order_data - " . json_encode ($order_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$order = Orders::createMSOrder2($order_data);
		$logger->write(__LINE__ . " order - " . json_encode ($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$return ['order']['accepted'] = true;
		// если повторное добавление заказа - отдаем обратно номер заказа
		if (isset ($order['errors'][0]) ? ($order['errors'][0]['code'] == 3006 && $order['errors'][0]['parameter'] == 'name') : false)
		    $order['name'] = $data['order']['id'];
		$return ['order']['id'] = (string)$order['name'];
		
		header('Content-Type: application/json');
		echo json_encode($return);
	}
?>
