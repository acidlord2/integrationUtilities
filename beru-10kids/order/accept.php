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
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/ordersYandex.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	$logger = new Log('beru-10kids - order - accept.log'); //just passed the file name as file_name.log
	$ordersYandex = new OrdersYandex(BERU_API_10KIDS_CAMPAIGN);
	$logger->write(__LINE__ . ' _GET - ' . json_encode ($_GET, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	// check auth-token
	if (isset($_GET['auth-token']) ? (string)$_GET['auth-token'] != Settings::getSettingsValues('beru_auth_token_22064982') : true)
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
	$logger->write(__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
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
	
	$orderData = $ordersYandex->getOrder($data['order']['id']);
	$logger->write(__LINE__ . ' orderData - ' . json_encode ($orderData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

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
	$order_data['moment'] = date('Y-m-d H:i:s', strtotime('now'));
	if (isset ($data['order']['delivery']['shipments'][0]['shipmentDate'])) {
	    $deliveryPlannedMoment = DateTime::createFromFormat('d-m-Y', $data['order']['delivery']['shipments'][0]['shipmentDate']);
	    $order_data['deliveryPlannedMoment'] = $deliveryPlannedMoment->format('Y-m-d H:i:s');
	}
	else if (isset ($data['order']['delivery']['dates']['toDate'])) {
	    $deliveryPlannedMoment = DateTime::createFromFormat('d-m-Y', $data['order']['delivery']['dates']['toDate']);
	    $order_data['deliveryPlannedMoment'] = $deliveryPlannedMoment->format('Y-m-d H:i:s');
	}
	$order_data['barcodes']['lower_barcode'] = '';
	
	$return = array();
	$fakeOrder = isset ($data['order']['fake']) ? (bool)$data['order']['fake'] : false;
	
	$order_data['state'] = MS_MPNEW_STATE;
	$order_data['applicable'] = !(bool)$fakeOrder;
	$order_data['description'] = (isset ($data['order']['notes']) ? $data['order']['notes'] . ' ' : '') . ($fakeOrder ? 'ТЕСТ ЗАКАЗ' : '');
	$order_data['products'] = array();
	$order_data['agent'] = MS_BERU_AGENT;
	$order_data['organization'] = MS_10KIDS;
	$order_data['project'] = MS_PROJECT_YANDEX_DBS;
	$order_data['vatEnabled'] = true;
	$order_data['vatIncluded'] = true;
	$order_data['attributes'] = array();
	$order_data['attributes'][MS_PAYMENTTYPE_ATTR] = $data['order']['paymentMethod'] == 'CASH_ON_DELIVERY' ? MS_PAYMENTTYPE_CASH : MS_PAYMENTTYPE_SBERBANK;
	$order_data['attributes'][MS_DELIVERYTIME_ATTR] = MS_DELIVERYTIME_VALUE1;
	$order_data['attributes'][MS_DELIVERY_ATTR] = $data['order']['delivery']['type'] == 'DELIVERY' ? MS_DELIVERY_VALUE0 : MS_SHIPTYPE_PICKUP;
	$orderBuyer = $ordersYandex->getOrderBuyer($data['order']['id']);
	// фио
	if (isset($orderBuyer['lastName']) || isset ($orderBuyer['firstName']) || isset ($orderBuyer['middleName'])){
	    $order_data['attributes'][MS_FIO_ATTR] = (isset($orderBuyer['lastName']) ? $orderBuyer['lastName'] : '') . (isset ($orderBuyer['firstName']) ? ' ' . $orderBuyer['firstName'] : '') . (isset ($orderBuyer['middleName']) ? ' ' . $orderBuyer['middleName'] : '');
	}
	// телефон
	if (isset ($orderBuyer['phone'])){
	    $order_data['attributes'][MS_PHONE_ATTR] = $orderBuyer['phone'];
	}
	
	$order_data['attributes'][MS_ADDRESS_ATTR] = (isset ($data['order']['delivery']['address']['postcode']) ? $data['order']['delivery']['address']['postcode'] : '') . (isset ($data['order']['delivery']['address']['city']) ? ' ' . $data['order']['delivery']['address']['city'] : '') . (isset ($data['order']['delivery']['address']['street']) ? ' ' . $data['order']['delivery']['address']['street'] : '') . (isset ($data['order']['delivery']['address']['house']) ? ' ' . $data['order']['delivery']['address']['house'] : '') . (isset ($data['order']['delivery']['address']['block']) ? ' ' . $data['order']['delivery']['address']['block'] : '');
	
	//$order_data['taxSystem'] = 'GENERAL_TAX_SYSTEM';
	$products = Products::getMSStock (array_column ($data['order']['items'], 'offerId'));
	
	$amount = 0;
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
				'price' => $item['price'] + (isset ($item['subsidy']) ? $item['subsidy'] : 0),
				'vat' => $vat
			);
			$amount += $item['price'] * $item['count'];
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
	
	if (isset ($data['order']['delivery']['price']))
	{
		$order_data['products'][] = array(
			'sku' => $data['order']['delivery']['type'] == 'DELIVERY' ? '00001' : '00002',
			'quantity' => 1,
			'price' => $data['order']['delivery']['price'] + (isset ($data['order']['delivery']['subsidy']) ? $data['order']['delivery']['subsidy'] : 0),
			'vat' => 20
		);
		$amount += $data['order']['delivery']['price'];
		
	}
	$order_data['attributes'][MS_MPAMOUNT_ATTR] = $amount;
	
	if ($ok)
	{
	    $logger->write(__LINE__ . ' order_data - ' . json_encode ($order_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$order = Orders::createMSOrder2($order_data);
		$logger->write(__LINE__ . ' order - ' . json_encode ($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$return ['order']['accepted'] = true;
		$return ['order']['id'] = (string)$data['order']['id'];
		
		if (isset($deliveryPlannedMoment)) {
		    $return['order']['shipmentDate'] = $deliveryPlannedMoment->format('d-m-Y');
		}
		
		header('Content-Type: application/json');
		echo json_encode($return);
	}
?>
