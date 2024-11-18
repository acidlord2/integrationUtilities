<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	//	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/OrdersOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	//require_once('classes/log.php');
	//$newOrdersOzon = Orders::getOzonOrders('2019-12-16T10:57:21Z', '2020-12-16T11:57:21Z', "awaiting_packaging");
	$log = new Log ('ozonKaori - getNewOrders.log');
	$ordersOzonClass = new OrdersOzon('kaori');
	
	$ordersOzon = $ordersOzonClass->findOrders(date ('Y-m-d', strtotime('-1 day')) . 'T00:00:00.000Z', date ('Y-m-d', strtotime('now')) . 'T23:59:59.999Z', "awaiting_packaging", OZON_WEARHOUSE1_ID);
	$log->write (__LINE__ . ' ordersOzon - ' . json_encode ($ordersOzon, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
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
		    $log -> write (__LINE__ . ' Order already exists - ' . $order['posting_number']);
		    $orderMS['id'] = $orderMS[0]['id'];
		}
		else
		{
			$data = array();
			$data['name'] = (string)$order['posting_number'];
			$data['organization'] = array(
				'meta' => array(
					'href' => MS_KAORI,
					'type' => 'organization',
					'mediaType' => 'application/json'
				)
			);
			$data['externalCode'] = (string)$order['order_id'];
			$date = DateTime::createFromFormat('Y-m-d\TH:i:sO', $order['in_process_at'])->setTimezone(new DateTimeZone('Europe/Moscow'));
			$data['moment'] = $date->format('Y-m-d H:i:s');
			
			$date = DateTime::createFromFormat('Y-m-d\TH:i:sO', $order['shipment_date'])->setTimezone(new DateTimeZone('Europe/Moscow'));
			$data['deliveryPlannedMoment'] = $date->format('Y-m-d H:i:s');
			$data['agent'] = array(
				'meta' => array(
					'href' => MS_OZON_AGENT,
					'type' => 'counterparty',
					'mediaType' => 'application/json'
				)
			);
			$data['state'] = array(
				'meta' => array(
					'href' => MS_CONFIRMBERU_STATE,
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			);
			$data['applicable'] = true;
			$data['vatEnabled'] = false;
			$data['store'] = array(
				'meta' => array(
					'href' => MS_STORE,
					'type' => 'store',
					'mediaType' => 'application/json'
				)
			);
			$data['project'] = array(
				'meta' => array(
					'href' => MS_PROJECT_OZON,
					'type' => 'project',
					'mediaType' => 'application/json'
				)
			);
			$data['group'] = array(
				'meta' => array(
					'href' => MS_GROUP,
					'type' => 'group',
					'mediaType' => 'application/json'
				)
			);
			
			$data['attributes'] = array ();
			// способ доставки
			$data['attributes'][] = array (
				'meta' => array (
					'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERY_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
				'value' => array(
					'meta' => array(
						'href' => MS_SHIPTYPE_OZON,
						'type' => 'customentity',
						'mediaType' => 'application/json'
					)
				)
			);
			// время доставки
			$data['attributes'][] = array (
				'meta' => array (
					'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERYTIME_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
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
				'meta' => array (
					'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_PAYMENTTYPE_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
				'value' => array(
					'meta' => array(
						'href' => MS_PAYMENTTYPE_SBERBANK,
						'type' => 'customentity',
						'mediaType' => 'application/json'
					)
				)
			);
			//ФИО
			$data['attributes'][] = array (
				'meta' => array (
					'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_BARCODE2_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
				'value' => (string)$order['barcodes']['lower_barcode']
			);
			
			$data['positions'] = array();
			$delivery = 0;
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
					'vat' => (int)0,
					'assortment' => array(
						'meta' => array(
							'href' => $productMS[0]['meta']['href'],
							'type' => $productMS[0]['meta']['type'],
							'mediaType' => 'application/json'
						)
					),
					'reserve' => $product['quantity']
				);
					
			}
			
			$orderMS = $ordersClass->createCustomerorder ($data);
			$log -> write (__LINE__ . ' orderMS - ' . json_encode ($orderMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		}
		
		if (isset ($orderMS['id']))
		{
			$ordersOzonClass->setExemplar($order);
			$i = 0;
			while (true)
			{
				$i++;
				$status = $ordersOzonClass->checkSetExemplarStatus($order['posting_number']);
				if($status['status'] == 'ship_available') break;
				if ($i >= 5) break;
				sleep(1);
			}
		    $ordersOzonClass->packOrder($order);
			//Orders::getPackageLabel($order, $ms_order['id']);
		}
		//break;
	}
		
	echo 'Processed ' . count ($ordersOzon) . ' orders';
?>

