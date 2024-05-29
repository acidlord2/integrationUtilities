<?php
	/**
	 * pack order
	 *
	 * @author GPOLYAN <acidlord@yandex.ru>
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

	$log = new Log('sbmm-ast5 - packing.log'); //just passed the file name as file_name.log
	
	$data = json_decode(file_get_contents('php://input'), true);
	$log->write(__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	if (!isset ($data['data']['shipments']))
	{
		header('HTTP/1.0 400 Bad Request');
		echo 'Missing required parameter "shipments"';
		return;
	}
	
	$ordersClass = new OrdersMS();
	$productsClass = new ProductsMS();
	
	$ok = true;
	$error = '';
	
	foreach ($data['data']['shipments'] as $shipment)
	{
	    $order = $ordersClass->findOrders(array('name' => $shipment['shipmentId']));
	    if (count($order))
	    {
	        $log->write(__LINE__ . ' Заказ ' . $shipment['shipmentId'] . ' уже создан');
	        continue;
	    }
	    // prepare data order
	    $order_data = array();
	    $order_data['applicable'] = true;
	    $order_data['vatEnabled'] = true;
	    if ($shipment['customer']['address']['access']['comment'] != null){
	        $order_data['description'] = $shipment['customer']['address']['access']['comment'];
	    }
	    
	    $order_data['name'] = $shipment['shipmentId'];
	    $order_data['moment'] = DateTime::createFromFormat('Y-m-d\TH:i:sP', $shipment['shipmentDate'])->format('Y-m-d H:i:s');
	    $order_data['deliveryPlannedMoment'] = date('Y-m-d H:i:s', strtotime('+1 day'));
	    $order_data['organization'] = array('meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_ORGANIZATION . '/' . MS_PLUTON_ID, 'organization'));
	    $order_data['agent'] = array('meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_COUNTERPARTY . '/' . MS_GOODS_AGENT_ID, 'counterparty'));
	    $order_data['state'] = array('meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDERSTATE . '/' . MS_CONFIRMBERU_STATE_ID, 'state'));
	    $order_data['store'] = array('meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_STORE . '/' . MS_API_STORE_ID, 'store'));
	    $order_data['group'] = array('meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_GROUP . '/' . MS_GROUP_ID, 'group'));
	    $order_data['project'] = array('meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PROJECT . '/' . MS_PROJECT_SBMM_AST5_ID, 'project'));
	    $order_data['attributes'] = array();
	    // способ доставки
	    $order_data['attributes'][] = array(
	        'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERY_ATTR, 'attributemetadata'),
	        'value' => array (
	            'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERENTITY . '/' . MS_API_ATTRIBUTE_CURIER . '/' . MS_SHIPTYPE_CURIER0_ID, 'customentity')
	        )
	    );
	    // время доставки
	    $order_data['attributes'][] = array(
	        'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_DELIVERYTIME_ATTR, 'attributemetadata'),
	        'value' => array (
	            'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERENTITY . '/' . MS_API_ATTRIBUTE_DELIVERYTIME . '/' . MS_DELIVERYTIME_9_21_ID, 'customentity')
	        )
	    );
	    // адрес доставки
	    $order_data['attributes'][] = array(
	        'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_ADDRESS_ATTR, 'attributemetadata'),
	        'value' => $shipment['customer']['address']['source'] . ($shipment['customer']['address']['access']['entrance'] != null ? ' под. ' . $shipment['customer']['address']['access']['entrance'] : '') . ($shipment['customer']['address']['access']['floor'] != null ? ' эт. ' . $shipment['customer']['address']['access']['floor'] : '') . ($shipment['customer']['address']['access']['intercom'] != null ? ' код домофона ' . $shipment['customer']['address']['access']['intercom'] : '')
	    );
	    
	    // ФИО
	    $order_data['attributes'][] = array(
	        'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_FIO_ATTR, 'attributemetadata'),
	        'value' => $shipment['customer']['customerFullName']
	    );
	    // телефон
	    $order_data['attributes'][] = array(
	        'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_PHONE_ATTR, 'attributemetadata'),
	        'value' => $shipment['customer']['phone']
	    );
	    // оплачено маркетплейсом
	    if ($shipment['handover']['depositedAmount']) {
    	    $order_data['attributes'][] = array(
    	        'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_PAIDBYMP_ATTR, 'attributemetadata'),
    	        'value' => $shipment['handover']['depositedAmount']
    	    );
	    }
	    
	    // позиции
	    $order_data['positions'] = array();
	    $mpamount = 0;
	    foreach ($shipment['items'] as $item)
	    {
	        if ($item['itemName'] == 'Доставка') {
	            $product = $productsClass->findServicesByCode(MS_SELFDELIVERY_SERVICE);
	        }
	        else {
	            $product = $productsClass->findProductsByCode($item['offerId']);
	        };
	        
	        if (count($product))
	        {
	            $order_data['positions'][] = array (
	                'quantity' => $item['quantity'],
	                'price' => $item['price'] * 100,
	                'discount' => 0,
	                'vat' => $item['taxRate'] != null ? (int)$item['taxRate'] : $product[0]['effectiveVat'],
	                'assortment' => array (
	                    'meta' => $product[0]['meta']
	                ),
	                'reserve' => $item['quantity']
	            );
	            $mpamount += $item['finalPrice'] * $item['quantity'];
	        }
	        else 
	        {
	            $ok = false;
	            $error .= 'Продукт ' . $item['offerId'] . ' не найден. Позиция ' . $item['itemIndex'];
	            $log->write(__LINE__ . ' Продукт ' . $item['offerId'] . ' не найден. Позиция ' . $item['itemIndex']);
	        }
	    }
	    // тип оплаты
	    $order_data['attributes'][] = array(
	        'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_PAYMENTTYPE_ATTR, 'attributemetadata'),
	        'value' => array (
	            'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERENTITY . '/' . MS_API_ATTRIBUTE_PAYMENTTYPE . '/' . ($shipment['handover']['depositedAmount'] == $mpamount ? MS_PAYMENTTYPE_SBERBANK_ID : MS_PAYMENTTYPE_CASH_ID), 'customentity')
	        )
	    );
	    // сумма маркетплейс
	    $order_data['attributes'][] = array(
	        'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_MPAMOUNT_ATTR, 'attributemetadata'),
	        'value' => $mpamount
	    );
	    
	    $order = $ordersClass->createCustomerorder($order_data);
	    $log->write(__LINE__ . ' order - ' . json_encode ($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}
	
	if ($ok)
	{
		header('Content-Type: application/json');
		echo json_encode('{"success":1,"meta":{"source":"10kids.ru (ДСМ)"}}');
	}
	else
	{
	    header('HTTP/1.0 400 Bad Request');
	    echo $error;
	    return;
	}

	return;
?>
