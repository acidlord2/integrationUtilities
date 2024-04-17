<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/ordersYandex.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sbermegamarket/Order.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/OrdersOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	// logger class
	$log = new Log ('mswh - whChangeOrder.log');
	// msapi class
	$apiMSClass = new APIMS();
	// yandex class
	
	$content = json_decode (file_get_contents('php://input'), true);
	$log->write(__LINE__ . ' content - ' . file_get_contents('php://input'));

 	foreach ($content['events'] as $event)
	{
		$orderData = $apiMSClass->getData ($event['meta']['href']);
		$state = APIMS::getIdFromHref($orderData['state']['meta']['href']);
	    $log->write(__LINE__ . ' state - ' . $state);
	    $log->write(__LINE__ . ' project - ' . (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) : ''));
		//$log->write(__LINE__ . ' orderData - ' . json_encode ($orderData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		// заказ на отмене
		if  ($state == MS_CANCEL_STATE_ID)
		{
			$positions = $orderData['positions'];
			foreach ($positions as $position)
				$position['reserve'] = 0;
			$return = $apiMSClass->putData ($event['meta']['href'], $positions);
			$log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_MARKET_ID : false)
			{
			    $campaign = BERU_API_ABCASIA_CAMPAIGN;
			    
			    $log->write(__LINE__ . ' campaign - ' . $campaign);
			    
			    $ordersYandexClass = new OrdersYandex($campaign);
			    $orderDataYandex = $ordersYandexClass->getOrder ($orderData['name']);
			    $log->write(__LINE__ . ' orderDataYandex - ' . json_encode ($orderDataYandex, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    
			    if ($orderDataYandex['order']['status'] != 'CANCELLED')
			    {
			        //$return = $ordersYandexClass->updateStatus ($orderData['name'], 'CANCELLED', 'USER_CHANGED_MIND');
			        $return = $ordersYandexClass->updateStatus ($campaign, $orderData['name'], 'CANCELLED', 'SHOP_FAILED');
			        $log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    }
			}
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_YANDEX_SUMMIT_ID : false)
			{
			    $campaign = BERU_API_SUMMIT_CAMPAIGN;
			    
			    $log->write(__LINE__ . ' campaign - ' . $campaign);
			    
			    $ordersYandexClass = new OrdersYandex($campaign);
			    $orderDataYandex = $ordersYandexClass->getOrder ($orderData['name']);
			    $log->write(__LINE__ . ' orderDataYandex - ' . json_encode ($orderDataYandex, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    
			    if ($orderDataYandex['order']['status'] != 'CANCELLED')
			    {
			        //$return = $ordersYandexClass->updateStatus ($orderData['name'], 'CANCELLED', 'USER_CHANGED_MIND');
			        $return = $ordersYandexClass->updateStatus ($campaign, $orderData['name'], 'CANCELLED', 'SHOP_FAILED');
			        $log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    }
			}
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_14DAYS_ALIANS : false)
			{
			    $campaign = BERU_API_ALIANS_CAMPAIGN;
			    
			    $log->write(__LINE__ . ' campaign - ' . $campaign);
			    
			    $ordersYandexClass = new OrdersYandex($campaign);
			    $orderDataYandex = $ordersYandexClass->getOrder ($orderData['name']);
			    $log->write(__LINE__ . ' orderDataYandex - ' . json_encode ($orderDataYandex, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    
			    if ($orderDataYandex['order']['status'] != 'CANCELLED')
			    {
			        //$return = $ordersYandexClass->updateStatus ($orderData['name'], 'CANCELLED', 'USER_CHANGED_MIND');
			        $return = $ordersYandexClass->updateStatus ($campaign, $orderData['name'], 'CANCELLED', 'SHOP_FAILED');
			        $log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    }
			}
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_YANDEX_DBS_ID : false)
			{
				$organizationId = APIMS::getIdFromHref($orderData['organization']['meta']['href']);
				
				$campaign = $organizationId == MS_10KIDS_ID ? BERU_API_10KIDS_CAMPAIGN : BERU_API_MARKET4CLEANING_CAMPAIGN;
				
				$log->write(__LINE__ . ' campaign - ' . $campaign);
				
				$ordersYandexClass = new OrdersYandex($campaign);
				$orderDataYandex = $ordersYandexClass->getOrder ($orderData['name']);
				$log->write(__LINE__ . ' orderDataYandex - ' . json_encode ($orderDataYandex, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				
				if ($orderDataYandex['order']['status'] != 'CANCELLED')
				{
					$return = $ordersYandexClass->updateStatus ($orderData['name'], 'CANCELLED', 'USER_CHANGED_MIND');
					//$return = $ordersYandexClass->updateStatus ($campaign, $orderData['name'], 'CANCELLED', 'SHOP_FAILED');
					$log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				}
			}
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_OZON_DBS_ID : false)
			{
			    $organizationId = APIMS::getIdFromHref($orderData['organization']['meta']['href']);
			    $organization = $organizationId == MS_KAORI_ID ? 'kaori' : 'ullo';
			    $log->write(__LINE__ . ' organization - ' . $organization);
			    
			    $ordersOzonClass = new OrdersOzon($organization);
			    $orderInfo = $ordersOzonClass->getOrder($orderData['name'], false);
			    if ($orderInfo['status'] != 'cancelled')
			    {
    			    $postData = array(
    			        'cancel_reason_id' => 402,
    			        'cancel_reason_message' => 'Покупатель не принял заказ (в том числе недозвон)',
    			        'posting_number' => $orderData['name']
    			    );
    			    $log->write(__LINE__ . ' postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    			    $return = $ordersOzonClass->cancelOrder($postData);
    			    $log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    }
			}
			// Сбермегамаркет ДСМ
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_SBMM_DSM_ID : false)
			{
			    $orderSBMMClass = new \Classes\Sbermegamarket\Order(SBMM_SHOP_DSM);
			    $mpCancelKey = array_search (MS_MPCANCEL_ATTR_ID, array_column ($orderData['attributes'], 'id'));
			    
			    if ($mpCancelKey ==! false && $orderData['attributes'][$mpCancelKey]['value'])
			    {
    			    $data = array(
    			        'data' => array(
    			            'shipments' => array(
    			                array(
    			                    'shipmentId' => (int)$orderData['name'],
    			                    'items' => array()
    			                )
    			            )
    			        ),
    			        'meta' => array(
    			            'source' => 'test'
    			        )
    			    );
    			    $ordersMSClass = new OrdersMS();
    			    $positions = $ordersMSClass->getOrderPositions($orderData['id']);
    			    foreach ($positions as $key => $position)
    			    {
    			        $data['data']['shipments'][0]['items'][] = array(
    			            'itemIndex' => (string)($key + 1),
    			            'canceled' => true
    			        );
    			    }
    			    $log->write(__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    			    $return = $orderSBMMClass->cancelResult($data);
    			    $log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    }
			    else 
			    {
			        //reject
			        $dataReject = array(
			            'data' => array(
			                'shipments' => array(
			                    array(
			                        'shipmentId' => (int)$orderData['name'],
			                        'items' => array()
			                    )
			                )
			            ),
			            'meta' => array()
			        );
			        $ordersMSClass = new OrdersMS();
			        $positions = $ordersMSClass->getOrderPositions($orderData['id']);
			        $productClass = new ProductsMS();
			        foreach ($positions as $key => $position)
			        {
			            $product = $productClass->getProduct(APIMS::getIdFromHref($position['assortment']['meta']['href']));
			            
			            $dataReject['data']['shipments'][0]['items'][] = array(
			                'itemIndex' => (string)($key + 1),
			                'offerId' => isset($product['code']) ? $product['code'] : null
			            );
			        }
			        $log->write(__LINE__ . ' dataReject - ' . json_encode ($dataReject, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			        $returnReject = $orderSBMMClass->reject($dataReject);
			        $log->write(__LINE__ . ' returnReject - ' . json_encode ($returnReject, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			        //close
			        $dataClose = array(
			            'data' => array(
			                'shipments' => array(
			                    array(
			                        'shipmentId' => (int)$orderData['name'],
			                        'closeDate' => date('Y-m-d\TH:i:sP'),
			                        'items' => array()
			                    )
			                )
			            ),
			            'meta' => array()
			        );
			        foreach ($positions as $key => $position)
			        {
			            $dataClose['data']['shipments'][0]['items'][] = array(
			                'itemIndex' => $key + 1,
			                'handoverResult' => FALSE,
			                'reason' => array (
			                    'type' => 'CANCEL_BY_CUSTOMER',
			                    'comment' => 'Отказ покупателя от товара'
			                )
			            );
			        }
			        $log->write(__LINE__ . ' dataClose - ' . json_encode ($dataClose, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			        $returnClose = $orderSBMMClass->close($dataClose);
			        $log->write(__LINE__ . ' returnClose - ' . json_encode ($returnClose, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    }
			}
			//wildberries
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_WB_ID : false)
			{
			    $ordersWBClass = new \Classes\Wildberries\v1\Orders('Kaori');
			    
			    $data = array(
			        'orderId' => substr($orderData['name'], 2),
			        'status' => 7
			    );
		        $return = $ordersWBClass->changeOrdersStatus($data);
		        $log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
		}
		// заказ подтвержден
		else if ($state == MS_CONFIRM_STATE_ID)
		{
			// яндекс ДБС
		    if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_YANDEX_DBS_ID : false)
			{
				$deliveryKey = array_search (MS_DELIVERY_ATTR, array_column ($orderData['attributes'], 'id'));
				
				if ($deliveryKey === false)
					break;
				
				if (APIMS::getIdFromHref($orderData['attributes'][$deliveryKey]['value']['meta']['href']) != MS_SHIPTYPE_PICKUP_ID)
					break;
				
				$organizationId = APIMS::getIdFromHref($orderData['organization']['meta']['href']);
				$campaign = $organizationId == MS_10KIDS_ID ? BERU_API_10KIDS_CAMPAIGN : BERU_API_MARKET4CLEANING_CAMPAIGN;
				$log->write(__LINE__ . ' campaign - ' . $campaign);
				
				$ordersYandexClass = new OrdersYandex($campaign);
				$return = $ordersYandexClass->updateStatus ($orderData['name'], 'PICKUP');
				$log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
			// Сбермегамаркет ДСМ
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_SBMM_DSM_ID : false)
			{			    
			    $orderSBMMClass = new \Classes\Sbermegamarket\Order(SBMM_SHOP_DSM);
			    $dataPacking = array(
			        'data' => array(
			            'shipments' => array(
			                array(
			                    'shipmentId' => (int)$orderData['name'],
			                    'orderCode' => $orderData['name'],
			                    'items' => array()
			                )
			            )
			        ),
			        'meta' => array()
			    );
			    $dataReject = array(
		            'data' => array(
		                'shipments' => array(
		                    array(
		                        'shipmentId' => (int)$orderData['name'],
		                        'items' => array()
		                    )
		                )
		            ),
		            'meta' => array()
		        );
		        $ordersMSClass = new OrdersMS();
			    $positions = $ordersMSClass->getOrderPositions($orderData['id']);
			    $productClass = new ProductsMS();
			    foreach ($positions as $key => $position)
			    {
			        if ($position['price'])
			        {
			            $dataPacking['data']['shipments'][0]['items'][] = array(
    			            'itemIndex' => $key + 1,
    			            'quantity' => $position['quantity']
    			        );
			        }
			        else
			        {
			            $product = $productClass->getProduct(APIMS::getIdFromHref($position['assortment']['meta']['href']));
			            
			            $dataReject['data']['shipments'][0]['items'][] = array(
			                'itemIndex' => (string)($key + 1),
			                'offerId' => isset($product['code']) ? $product['code'] : null
			            );
			        }
			    }
			    $log->write(__LINE__ . ' dataPacking - ' . json_encode ($dataPacking, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    $log->write(__LINE__ . ' dataReject - ' . json_encode ($dataReject, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    if (count($dataPacking['data']['shipments'][0]['items']))
			    {
			        $returnPacking = $orderSBMMClass->packing($dataPacking);
			        $log->write(__LINE__ . ' returnPacking - ' . json_encode ($returnPacking, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    }
			    if (count($dataReject['data']['shipments'][0]['items']))
			    {
			        $returnReject = $orderSBMMClass->reject($dataReject);
			        $log->write(__LINE__ . ' returnReject - ' . json_encode ($returnReject, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    }
			}
			//wildberries
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_WB_ID : false)
			{
			    $ordersWBClass = new \Classes\Wildberries\v1\Orders('Kaori');
			    
			    $data = array(
			        'orderId' => substr($orderData['name'], 2),
			        'status' => 1
			    );
			    $return = $ordersWBClass->changeOrdersStatus($data);
			    $log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
		}
		// заказ подтвержден маркетплейс
		else if ($state == MS_CONFIRMBERU_STATE_ID)
		{
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_2HRS_ID : false)
			{
				//$organizationId = APIMS::getIdFromHref($orderData['organization']['meta']['href']);
				$campaign = BERU_API_ARUBA_CAMPAIGN;
				$log->write(__LINE__ . ' campaign - ' . $campaign);

				$ordersYandexClass = new OrdersYandex($campaign);
				$orderInfo = $ordersYandexClass->getOrder($orderData['name']);
				$log->write(__LINE__ . ' orderInfo - ' . json_encode ($orderInfo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				
				// pack order
				$packData = $ordersYandexClass->packOrder ($orderData['name'], $orderInfo['order']['delivery']);
				$log->write(__LINE__ . ' packData - ' . json_encode ($packData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				
				//get label data
				$labelData = $ordersYandexClass->getOrderLabelData($orderData['name']);
				
				//update ms order data
				$ordersMSClass = new OrdersMS();
				$data = array(
				    'attributes' => array(
				        // адрес
				        array(
				            'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_ADDRESS_ATTR, 'attributemetadata'),
				            'value' => (string)$labelData['result']['parcelBoxLabels'][0]['deliveryAddress']
				        ),
				        // ФИО
				        array(
				            'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_FIO_ATTR, 'attributemetadata'),
				            'value' => (string)$labelData['result']['parcelBoxLabels'][0]['recipientName']
				        ),
				        // delivery number
				        array(
				            'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERYNUMBER_ATTR, 'attributemetadata'),
				            'value' => (string)$labelData['result']['parcelBoxLabels'][0]['fulfilmentId']
				        )
				    )
				);
				$updateOrderResult = $ordersMSClass->updateCustomerorder($orderData['id'], $data);
				$log->write(__LINE__ . ' updateOrderResult - ' . json_encode ($updateOrderResult, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				
				//update status
				$return = $ordersYandexClass->updateStatus ($orderData['name'], 'PROCESSING', 'READY_TO_SHIP');
				$log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
		}
		// заказ собран (доставка)
		else if ($state == MS_PACKEDDELIVERY_STATE_ID || $state == MS_PACKEDMP_STATE)
		{
		    // озон дбс
		    if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_OZON_DBS_ID : false)
		    {
		        $organizationId = APIMS::getIdFromHref($orderData['organization']['meta']['href']);
		        $organization = $organizationId == MS_KAORI_ID ? 'kaori' : 'ullo';
		        $log->write(__LINE__ . ' organization - ' . $organization);
		        
		        $ordersOzonClass = new OrdersOzon($organization);
		        $return = $ordersOzonClass->onDeliveryOrder($orderData['name']);
		        $log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		    }
		}
		// заказ отгружен
		else if ($state == MS_SHIPPED_STATE_ID)
		{
			// яндекс дбс
		    if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_YANDEX_DBS_ID : false)
			{
				$organizationId = APIMS::getIdFromHref($orderData['organization']['meta']['href']);
				$campaign = $organizationId == MS_10KIDS_ID ? BERU_API_10KIDS_CAMPAIGN : BERU_API_MARKET4CLEANING_CAMPAIGN;
				$log->write(__LINE__ . ' campaign - ' . $campaign);
				
				$ordersYandexClass = new OrdersYandex($campaign);
				$return = $ordersYandexClass->updateStatus ($orderData['name'], 'DELIVERY');
				$log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
			// озон дбс
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_OZON_DBS_ID : false)
			{
			    $organizationId = APIMS::getIdFromHref($orderData['organization']['meta']['href']);
			    $organization = $organizationId == MS_KAORI_ID ? 'kaori' : 'ullo';
			    $log->write(__LINE__ . ' organization - ' . $organization);
			    
			    $ordersOzonClass = new OrdersOzon($organization);
			    $return = $ordersOzonClass->lastMileOrder($orderData['name']);
			    $log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
			//wildberries
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_WB_ID : false)
			{
			    $ordersWBClass = new \Classes\Wildberries\v1\Orders('Kaori');
			    
			    $data = array(
			        'orderId' => substr($orderData['name'], 2),
			        'status' => 5
			    );
			    $return = $ordersWBClass->changeOrdersStatus($data);
			    $log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
		}
		// заказ доставлен
		else if ($state == MS_DELIVERED_STATE_ID)
		{
		    // яндекс дбс
		    if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_YANDEX_DBS_ID : false)
			{
				$organizationId = APIMS::getIdFromHref($orderData['organization']['meta']['href']);
				$campaign = $organizationId == MS_10KIDS_ID ? BERU_API_10KIDS_CAMPAIGN : BERU_API_MARKET4CLEANING_CAMPAIGN;
				$log->write(__LINE__ . ' campaign - ' . $campaign);
				
				$ordersYandexClass = new OrdersYandex($campaign);
				$return = $ordersYandexClass->updateStatus ($orderData['name'], 'DELIVERED');
				$log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
			// озон дбс
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_OZON_DBS_ID : false)
			{
			    $organizationId = APIMS::getIdFromHref($orderData['organization']['meta']['href']);
			    $organization = $organizationId == MS_KAORI_ID ? 'kaori' : 'ullo';
			    $log->write(__LINE__ . ' organization - ' . $organization);
			    
			    $ordersOzonClass = new OrdersOzon($organization);
			    $return = $ordersOzonClass->deliverOrder ($orderData['name']);
			    $log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
			// Сбермегамаркет ДСМ
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_SBMM_DSM_ID : false)
			{
			    $orderSBMMClass = new \Classes\Sbermegamarket\Order(SBMM_SHOP_DSM);
			    $data = array(
			        'data' => array(
			            'shipments' => array(
			                array(
			                    'shipmentId' => (int)$orderData['name'],
			                    'closeDate' => date('Y-m-d\TH:i:sP'),
			                    'items' => array()
			                )
			            )
			        ),
			        'meta' => array()
			    );
			    $ordersMSClass = new OrdersMS();
			    $positions = $ordersMSClass->getOrderPositions($orderData['id']);
			    foreach ($positions as $key => $position)
			    {
			        if ($position['price'])
			        {
    			        $data['data']['shipments'][0]['items'][] = array(
    			            'itemIndex' => $key + 1,
    			            'handoverResult' => true
    			        );
			        }
			        else
			        {
			            $data['data']['shipments'][0]['items'][] = array(
			                'itemIndex' => $key + 1,
			                'handoverResult' => FALSE,
			                'reason' => array (
			                    'type' => 'CANCEL_BY_CUSTOMER',
			                    'comment' => 'Отказ покупателя от товара'
			                )
			            );
			        }
			    }
			    $log->write(__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			    $return = $orderSBMMClass->close($data);
			    $log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			}
			//wildberries
			if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_WB_ID : false)
			{
			    $ordersWBClass = new \Classes\Wildberries\v1\Orders('Kaori');
			    
			    $data = array(
			        'orderId' => substr($orderData['name'], 2),
			        'status' => 6
			    );
			    $return = $ordersWBClass->changeOrdersStatus($data);
			    $log->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			}
		}
		// заказ на уточнении
		else if ($state == MS_DEFFERED_STATE_ID)
		{
		    // Сбермегамаркет ДСМ
		    if (isset ($orderData['project']['meta']['href']) ? APIMS::getIdFromHref($orderData['project']['meta']['href']) == MS_PROJECT_SBMM_DSM_ID : false)
		    {
		        $orderSBMMClass = new \Classes\Sbermegamarket\Order(SBMM_SHOP_DSM);
		        $dataConfirm = array(
		            'data' => array(
		                'shipments' => array(
		                    array(
		                        'shipmentId' => $orderData['name'],
		                        'orderCode' => $orderData['name'],
		                        'items' => array()
		                    )
		                )
		            ),
		            'meta' => array()
		        );
		        $ordersMSClass = new OrdersMS();
		        $positions = $ordersMSClass->getOrderPositions($orderData['id']);
		        $productClass = new ProductsMS();
		        $log->write(__LINE__ . ' deliveryPlannedMoment - ' . $orderData['deliveryPlannedMoment']);
		        $shippingDate = DateTime::createFromFormat('Y-m-d H:i:s.v', $orderData['deliveryPlannedMoment'])->format('Y-m-d\TH:i:sP');
		        foreach ($positions as $key => $position)
		        {
		            $product = $productClass->getProduct(APIMS::getIdFromHref($position['assortment']['meta']['href']));
		            if ($position['price'] && isset($product['code']))
		            {
		                $dataConfirm['data']['shipments'][0]['items'][] = array(
		                    'itemIndex' => $key + 1,
		                    'offerId' => isset($product['code']) ? $product['code'] : null,
		                    'shippingDate' => $shippingDate
		                );
		            }
		        }
		        $log->write(__LINE__ . ' dataConfirm - ' . json_encode ($dataConfirm, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		        $returnConfirm = $orderSBMMClass->confirm($dataConfirm);
		        $log->write(__LINE__ . ' returnConfirm - ' . json_encode ($returnConfirm, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		        
		    }
		}
	}
?>