<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
//	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/OrdersOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	//require_once('classes/log.php');
	//$newOrdersOzon = Orders::getOzonOrders('2019-12-16T10:57:21Z', '2020-12-16T11:57:21Z', "awaiting_packaging");
	$log = new Log ('ozonUlloDbs - getNewOrders.log');
	$ordersOzonClass = new OrdersOzon('ullo');
	
	$ordersOzon = $ordersOzonClass->findOrders(date ('Y-m-d', strtotime('-1 day')) . 'T00:00:00Z', date ('Y-m-d', strtotime('now')) . 'T23:59:59Z', 'awaiting_packaging', OZON_ULLO_WEARHOUSE1_ID);
	$log->write(__LINE__ . ' ordersOzon - ' . json_encode ($ordersOzon, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	if (!count ($ordersOzon))
	{
		echo 'Processed 0 orders';
		return;
	}
	
	$ordersClass = new OrdersMS();
	$productsClass = new ProductsMS();
	
	foreach ($ordersOzon as $order)
	{
		$orderMS = $ordersClass->findOrders('name=' . $order['posting_number']);
		if (count ($orderMS))
		{
		    $log -> write (__LINE__ . ' order already exists - ' . $order['posting_number']);
		    $ordersOzonClass->packOrder($order);
		    continue;
		}
		
		$data = array();
		$data['name'] = (string)$order['posting_number'];
		$data['organization'] = array(
		    'meta' => APIMS::createMeta (MS_API_BASE_URL. MS_API_VERSION_1_2. MS_API_ORGANIZATION . '/' . MS_ULLO_ID, 'organization')
		);
		$data['externalCode'] = (string)$order['order_id'];
		$date = DateTime::createFromFormat('Y-m-d\TH:i:sO', $order['in_process_at'])->setTimezone(new DateTimeZone('Europe/Moscow'));
		$data['moment'] = $date->format('Y-m-d H:i:s');
		
		$date = DateTime::createFromFormat('Y-m-d\TH:i:sO', $order['shipment_date'])->setTimezone(new DateTimeZone('Europe/Moscow'));
		$data['deliveryPlannedMoment'] = $date->format('Y-m-d H:i:s');
		$data['store'] = array(
		    'meta' => APIMS::createMeta (MS_API_BASE_URL. MS_API_VERSION_1_2. MS_API_STORE . '/' . MS_API_STORE_ID, 'store') 
		);
		$data['agent'] = array(
		    'meta' => APIMS::createMeta (MS_API_BASE_URL. MS_API_VERSION_1_2. MS_API_COUNTERPARTY . '/' . MS_OZON_AGENT_ID, 'counterparty')
		);
		$data['state'] = array(
		    'meta' => APIMS::createMeta (MS_API_BASE_URL. MS_API_VERSION_1_2. MS_API_STATE . '/' . MS_NEW_STATE_ID, 'state')
		);
		$data['applicable'] = true;
		$data['vatEnabled'] = true;
		$data['project'] = array(
		    'meta' => APIMS::createMeta (MS_API_BASE_URL. MS_API_VERSION_1_2. MS_API_PROJECT . '/' . MS_PROJECT_OZON_ULLO_DBS_ID, 'project')
		);
		$data['group'] = array(
		    'meta' => APIMS::createMeta (MS_GROUP, 'group')
		);
		if (isset($order['customer']['address']['comment'])) {
		    $data['description'] = $order['customer']['address']['comment'];
		}
		
		$data['attributes'] = array ();
		// вид доставки
		$data['attributes'][] = array (
		    'meta' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERY_ATTR, 'attributemetadata'),
			'value' => array(
				'meta' => array(
				    'href' => MS_SHIPTYPE_CURIER0,
					'type' => 'customentity',
					'mediaType' => 'application/json'
				)
			)
		);
		// время доставки
		$data['attributes'][] = array (
		    'meta' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERYTIME_ATTR, 'attributemetadata'),
			'value' => array(
				'meta' => array(
					'href' => MS_DELIVERYTIME_VALUE1,
					'type' => 'customentity',
					'mediaType' => 'application/json'
				)
			)
		);
		// тип оплаты
		$data['attributes'][] = array (
		    'meta' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_PAYMENTTYPE_ATTR, 'attributemetadata'),
			'value' => array(
				'meta' => array(
					'href' => MS_PAYMENTTYPE_SBERBANK,
					'type' => 'customentity',
					'mediaType' => 'application/json'
				)
			)
		);
		// штрихкод
		if (isset($order['barcodes']['upper_barcode']))
		{
    		$data['attributes'][] = array (
    		    'meta' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_BARCODE2_ATTR, 'attributemetadata'),
    			'value' => (string)$order['barcodes']['upper_barcode']
    		);
		}
		// ФИО
		$data['attributes'][] = array (
		    'meta' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_FIO_ATTR, 'attributemetadata'),
		    'value' => isset($order['addressee']['name']) ? (string)$order['addressee']['name'] : (string)$order['customer']['name']
		);
		// телефон
		$data['attributes'][] = array (
		    'meta' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_PHONE_ATTR, 'attributemetadata'),
		    'value' => isset($order['addressee']['phone']) ? (string)$order['addressee']['phone'] : (string)$order['customer']['phone']
		);
		// адрес
		$data['attributes'][] = array (
		    'meta' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_ADDRESS_ATTR, 'attributemetadata'),
		    'value' => $order['customer']['address']['address_tail'] . ' ' . $order['customer']['address']['city']
		);
		
		$data['positions'] = array();
		//$delivery = 0;
		$productMS0 = $productsClass->findProductsByCode('000-0000');
		foreach ($order ['products'] as $product)
		{
			$productMS = $productsClass->findProductsByCode($product['offer_id']);
			if (!count ($productMS))
				$productMS = $productMS0;

			$data['positions'][] = array (
				'quantity' => (int)$product['quantity'],
				'price' => (int)($product['price'] * 100),
				'discount' => (int)0,
			    'vat' => $productMS[0]['effectiveVat'],
				'assortment' => array(
				    'meta' => $productMS[0]['meta']
				),
				'reserve' => $product['quantity']
			);
		}
		
		$orderMS = $ordersClass->createCustomerorder ($data);
		$log -> write (__LINE__ . ' orderMS - ' . json_encode ($orderMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		if (isset ($orderMS['id']))
		{
		    $ordersOzonClass->packOrder($order);
			//Orders::getPackageLabel($order, $ms_order['id']);
		}
		//break;
	}
		
	echo 'Processed ' . count ($ordersOzon) . ' orders';
?>

