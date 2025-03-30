<?php
	/**
	 * Creates new order
	 *
	 * @class ControllerExtensionBeruOrder
	 * @author GPOLYAN <acidlord@yandex.ru>
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/products.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiOrderCache.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/ordersYandex2.php');

	if (($_SERVER['REQUEST_METHOD'] != 'POST'))
	{
		header('HTTP/1.0 400 Bad Request');
		echo 'Request must be POST';
		return;
	}

	$return = array(
		'name' => 'beru-kosmos',
		'version' => '1.0.0',
		'time' => (new DateTime())->format('Y-m-d\TH:i:s.u\Z')
	);

	$data = json_decode (file_get_contents('php://input'), true);
	$logger = new Log('beru-kosmos - order - notification.log'); //just passed the file name as file_name.log
	$logger->write(__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	$orderId = $data['orderId'] . 'double';
	if ($data['notificationType'] == 'ORDER_STATUS_UPDATED' && $data['status'] == 'PROCESSING' && $data['substatus'] == 'READY_TO_SHIP') {
		$ordersYandexClass = new Yandex\v2\OrdersYandex($data['campaignId']);
		$orderDataYandex = $ordersYandexClass->getOrder ($data['orderId']); 
		$logger->write(__LINE__ . ' orderDataYandex - ' . json_encode ($orderDataYandex, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		$cacheOrder = APIOrderCache::getOrderCache($orderId);

		if (count($cacheOrder)) {
			$logger->write(__LINE__ . ' order ' . $orderId . ' is still processing. Returned OK');
		
			header('Content-Type: application/json');
			header('HTTP/1.0 200 OK');
			echo json_encode($return);
			return;
		}
		
		APIOrderCache::saveOrderCache($orderId, 'processing');
		
		$ok = true;
		$orderClass = new OrdersMS();
		$order = $orderClass->findOrders('name=' . $orderId);
		if (isset($order[0]))
		{
			$logger->write(__LINE__ . ' order ' . $orderId . ' is already created. Returned OK');
			
			header('Content-Type: application/json');
			header('HTTP/1.0 200 OK');
			echo json_encode($return);
			return;
		}
		$order_data = array();

		$order_data['name'] = (string)$orderId;
		$order_data['organization'] = array(
			'meta' => array(
				'href' => MS_KOSMOS,
				'type' => 'organization',
				'mediaType' => 'application/json'
			)
		);
		$order_data['externalCode'] = (string)$orderId;
		$order_data['moment'] = date('Y-m-d H:i:s', strtotime('now'));

		if (isset($orderDataYandex['order']['delivery']['shipments'][0]['shipmentDate']))
		{
			$order_data['deliveryPlannedMoment'] = DateTime::createFromFormat('d-m-Y', $orderDataYandex['order']['delivery']['shipments'][0]['shipmentDate'])->format
			('Y-m-d H:i:s');
		}
		else
		{
			// tommorow
			$order_data['deliveryPlannedMoment'] = date('Y-m-d H:i:s', strtotime('now +1 day'));
			
		}

		$order_data['agent'] = array(
			'meta' => array(
				'href' => MS_BERU_AGENT,
				'type' => 'counterparty',
				'mediaType' => 'application/json'
			)
		);
		
		$order_data['state'] = array(
			'meta' => array(
				'href' => MS_CONFIRMBERU_STATE,
				'type' => 'state',
				'mediaType' => 'application/json'
			)
		);
		
		$order_data['applicable'] = true;
		$order_data['store'] = array(
			'meta' => array(
				'href' => MS_STORE,
				'type' => 'store',
				'mediaType' => 'application/json'
			)
		);
		
		$order_data['project'] = array(
			'meta' => array(
				'href' => MS_PROJECT_YANDEX_KOSMOS,
				'type' => 'project',
				'mediaType' => 'application/json'
			)
		);
		
		$order_data['vatEnabled'] = false;
		$order_data['vatIncluded'] = false;
		$order_data['attributes'] = array();
		$order_data['attributes'][] = array (
			'meta' => array (
				'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERY_ATTR,
				'type' => 'attributemetadata',
				'mediaType' => 'application/json'
			),
			'value' => array(
				'meta' => array(
					'href' => MS_SHIPTYPE_BERU,
					'type' => 'customentity',
					'mediaType' => 'application/json'
				)
			)
		);
		// время доставки
		$order_data['attributes'][] = array (
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
		$order_data['attributes'][] = array (
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
		
		$order_data['positions'] = array();
		$productClass = new ProductsMS();
		foreach ($orderDataYandex['order']['items'] as $item)
		{
			$vat = 0;
			$product = $productClass->findProductsByCode($item['offerId']);
			$logger->write(__LINE__ . ' product - ' . json_encode ($product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			if (isset($product[0]))
			{
				$order_data['positions'][] = array(
					'assortment' => array(
						'meta' => array(
							'href' => $product[0]['meta']['href'],
							'type' => $product[0]['meta']['type'],
							'mediaType' => 'application/json'
						)
					),
					'quantity' => $item['count'],
					'price' => (int)(($item['price'] + (isset($item['subsidy']) ? $item['subsidy'] : 0)) * 100),
					'vat' => $vat,
					'discount' => (int)0,
					'reserve' => $item['count']
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

		$logger->write(__LINE__ . ' order_data - ' . json_encode ($order_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$order = $orderClass->createCustomerorder ($order_data);
		$logger->write(__LINE__ . ' order - ' . json_encode ($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		if (isset ($order['errors'][0]) ? ($order['errors'][0]['code'] == 3006 && $order['errors'][0]['parameter'] == 'name') : false) {
			$order['name'] = $orderDataYandex['order']['id'];
		}				
	}
	
	$return = array(
		'name' => 'beru-kosmos',
		'version' => '1.0.0',
		'time' => (new DateTime())->format('Y-m-d\TH:i:s.u\Z')
	);

	header('Content-Type: application/json');
	header('HTTP/1.0 200 OK');
	echo json_encode($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	return;
?>
