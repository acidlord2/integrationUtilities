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
	
	// check auth-token
	if (isset($_GET['auth-token']) ? (string)$_GET['auth-token'] != Settings::getSettingsValues('4cleaning-auth-token') : true)
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
	$logger = new Log('orderberu.log'); //just passed the file name as file_name.log
	$logger->write("data - " . json_encode ($data));
	
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
	
	// prepare data order
	$order_data = array();

	$order_data['posting_number'] = $data['order']['id'];
	$order_data['order_id'] = '';
	date_default_timezone_set('Europe/Moscow');
	$order_data['created_at'] = date('Y-m-d\TH:i:sO', strtotime('now'));
	$order_data['shipment_date'] = DateTime::createFromFormat('d-m-Y', $data['order']['delivery']['shipments'][0]['shipmentDate'])->format('Y-m-d\TH:i:sO');
	$order_data['barcodes']['upper_barcode'] = '';
	

	$return = array();
	$fakeOrder = isset ($data['order']['fake']) ? (bool)$data['order']['fake'] : false;
	
	$order_data['applicable'] = !(bool)$fakeOrder;
	$order_data['description'] = $fakeOrder ? 'ТЕСТ ЗАКАЗ' : '';
	$order_data['products'] = array();
	$order_data['agent'] = 'BERU';
	$order_data['organization'] = MS_4CLEANING;
	$products = Products::getMSStock (array_column ($data['order']['items'], 'offerId'));
	foreach ($data['order']['items'] as $item)
	{
		$pkey = array_search ($item['offerId'], array_column ($data['order']['items'], 'offerId'));
		if ($pkey !== false)
		{
			$order_data['products'][] = array(
				'offer_id' => $item['offerId'],
				'quantity' => $item['count'],
				'price' => $item['price'] + $item['subsidy']
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
		$order = Orders::createMSOrder($order_data);
		$logger->write("order - " . json_encode ($order));
		$return ['order']['accepted'] = true;
		$return ['order']['id'] = (string)$order['name'];
		
		header('Content-Type: application/json');
		echo json_encode($return);
	}

	return;
?>
