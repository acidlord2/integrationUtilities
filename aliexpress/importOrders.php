<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/aliApi.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	
	$logger = new Log ('aliexpress-importOrders.log');
	$ordersClass = new OrdersMS();
	$productsClass = new ProductsMS();
	$aliOrders = array();
	
	$params['setParam0'] = array (
	    'page_size' => 50,
	    'current_page' => 1,
	    'create_date_start' => '2021-10-01 00:00:00',
	    'create_date_end' => date('Y-m-d H:i:s', strtotime('now')),
	    'order_status_list' => array (0 => 'WAIT_SELLER_SEND_GOODS', 1 => 'SELLER_PART_SEND_GOODS')
	);
	
	while (true)
	{
    	AliAPI::getAliData('AliexpressSolutionOrderGetRequest', $params, $jsonOut, $arrayOut);
    	$orders = json_decode($jsonOut, true);
    	
    	if (!isset($orders['result']['target_list']['order_dto']))
    	{
    	    $logger->write(__LINE__ . ' orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    	    break;
    	}
    	
    	$aliOrders = array_merge($aliOrders, $orders['result']['target_list']['order_dto']);
    	if (count ($orders['result']['target_list']['order_dto']) == 0 || $orders['result']['total_page'] == $orders['result']['current_page'])
    	    break;
    	else 
    	    $params['setParam0']['current_page']++;
	}
	
	//$aliOrders = json_decode($jsonOut, true);
	$logger->write(__LINE__ . ' aliOrders - ' . json_encode ($aliOrders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	if (count($aliOrders))
	{
		foreach ($aliOrders as $aliOrder)
		{
			$msOrder = $ordersClass->findOrders('name=' . $aliOrder['order_id']);
			if (count ($msOrder))
			{
			    $logger->write(__LINE__ . ' Order already exists - ' . $aliOrder['order_id']);
				continue;
			}

			$params = array();
			$params['setParam1'] = array (
				'order_id' => $aliOrder['order_id']
			);
			
			AliAPI::getAliData('AliexpressSolutionOrderReceiptinfoGetRequest', $params, $jsonOut, $arrayOut);
			$orderInfo = json_decode($jsonOut, true);
			$logger->write (__LINE__ . ' orderInfo - ' . json_encode ($arrayOut, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			
			$data = array();
			$data['name'] = (string)$aliOrder['order_id'];
			$data['organization'] = array(
				'meta' => array(
					'href' => MS_ULLO,
					'type' => 'organization',
					'mediaType' => 'application/json'
				)
			);
			$data['moment'] = $aliOrder['gmt_create'];
			
			$deliveryPlannedMoment = (isset ($aliOrder['left_send_good_day']) ? $aliOrder['left_send_good_day'] : '0') . ' days ' . (isset ($aliOrder['left_send_good_hour']) ? $aliOrder['left_send_good_hour'] : '0') . ' hours ' . (isset ($aliOrder['left_send_good_min']) ? $aliOrder['left_send_good_min'] : '0') . ' minutes';
			
			$data['deliveryPlannedMoment'] = date ('Y-m-d H:i:s', strtotime ($deliveryPlannedMoment));
			$data['agent'] = array(
				'meta' => array(
					'href' => MS_ALI_AGENT,
					'type' => 'counterparty',
					'mediaType' => 'application/json'
				)
			);
			$data['state'] = array(
				'meta' => array(
					'href' => MS_NEW_STATE,
					'type' => 'state',
					'mediaType' => 'application/json'
				)
			);
			$data['applicable'] = true;
			$data['store'] = array(
				'meta' => array(
					'href' => MS_STORE,
					'type' => 'store',
					'mediaType' => 'application/json'
				)
			);
			$data['project'] = array(
				'meta' => array(
					'href' => MS_PROJECT_ALI,
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
			// вид доставки
			$data['attributes'][] = array (
				'meta' => array (
					'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERY_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
				'value' => array(
					'meta' => array(
						'href' => MS_DELIVERY_VALUEALI,
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
						'href' => MS_PAYMENTTYPE_VALUE1,
						'type' => 'customentity',
						'mediaType' => 'application/json'
					)
				)
			);
			//ФИО
			$data['attributes'][] = array (
				'meta' => array (
					'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_FIO_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
				'value' => $orderInfo['result']['contact_person']
			);
			// телефон
			$data['attributes'][] = array (
				'meta' => array (
					'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_PHONE_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
				'value' => $orderInfo['result']['phone_country'] . ' ' . $orderInfo['result']['mobile_no']
			);
			// адрес
			$data['attributes'][] = array (
				'meta' => array (
					'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_ADDRESS_ATTR,
					'type' => 'attributemetadata',
					'mediaType' => 'application/json'
				),
				'value' => $orderInfo['result']['detail_address'] . ', ' . $orderInfo['result']['province'] . ', ' . $orderInfo['result']['city'] . ', ' . $orderInfo['result']['country_name'] . ', ' . $orderInfo['result']['zip']
			);
			$data['positions'] = array();
			$delivery = 0;
			$productMS0 = $productsClass->findProductsByCode('000-0000');
			foreach ($aliOrder ['product_list']['order_product_dto'] as $product)
			{
				$productMS = $productsClass->findProductsByCode($product['sku_code']);
				if (!count ($productMS))
					$productMS = $productMS0;

				$data['positions'][] = array (
					'quantity' => (int)$product['product_count'],
					'price' => (int)($product['product_unit_price']['amount'] * 100),
					'discount' => (int)0,
					'vat' => (int)0,
					'assortment' => array(
						'meta' => array(
							'href' => $productMS[0]['meta']['href'],
							'type' => $productMS[0]['meta']['type'],
							'mediaType' => 'application/json'
						)
					),
					'reserve' => $product['product_count']
				);
					
				$delivery += isset ($product['logistics_amount']['amount']) ? $product['logistics_amount']['amount'] : 0;
			}
			// add delivery
			if ($delivery)
				$data['positions'][] = array (
					'quantity' => (int)1,
					'price' => (int)($delivery * 100),
					'discount' => (int)0,
					'vat' => (int)0,
					'assortment' => array(
						'meta' => array(
							'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_SERVICE . '/' . MS_DELIVERY_SERVICE_ID,
							'type' => 'service',
							'mediaType' => 'application/json'
						)
					),
				);
			
			$return = $ordersClass->createCustomerorder ($data);
			$logger->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		}
		
		//echo $jsonOut;
		echo 'Processed ' . count ($aliOrders) . ' orders';
	}
	else
		echo 'No orders';
?>

