<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	//require_once('classes/log.php');
	//$newOrdersOzon = Orders::getOzonOrders('2019-12-16T10:57:21Z', '2020-12-16T11:57:21Z', "awaiting_packaging");
	$logger = new Log ('ozonUllo-getNewOrders.log');

	$ordersOzon = Orders::getOzonOrders(date ('Y-m-d', strtotime('-1 day')) . 'T00:00:00Z', date ('Y-m-d', strtotime('now')) . 'T23:59:59Z', "awaiting_packaging");
	$logger->write ('01-ordersOzon - ' . json_encode ($ordersOzon, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
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
			$logger -> write ('02-Order already exists - ' . $order['posting_number']);
		}
		else
		{
			$data = array();
			$data['name'] = (string)$order['posting_number'];
			$data['organization'] = array(
				'meta' => array(
					'href' => MS_ULLO,
					'type' => 'organization',
					'mediaType' => 'application/json'
				)
			);
			$data['externalCode'] = (string)$order['order_id'];
			
			$date = DateTime::createFromFormat('Y-m-d\TH:i:sO', $order['created_at'])->setTimezone(new DateTimeZone('Europe/Moscow'));
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
			$logger -> write ('03-orderMS - ' . json_encode ($orderMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		}
		
		if (isset ($orderMS['id']))
		{
			Orders::packOzonOrder($order);
			//Orders::getPackageLabel($order, $ms_order['id']);
		}
		//break;
	}
		
	echo 'Processed ' . count ($ordersOzon) . ' orders';
?>

