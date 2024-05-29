<?php
	/**
	 * Creates new order
	 *
	 * @author GPOLYAN <acidlord@yandex.ru>
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	
	$data = json_decode(file_get_contents('php://input'), true);
	$log = new Log('sbmm-ast6 - cancel.log'); //just passed the file name as file_name.log
	$log->write(__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	if (!isset ($data['data']['shipments']))
	{
		header('HTTP/1.0 400 Bad Request');
		echo 'Missing required parameter "shipments"';
		return;
	}
	
	$ordersClass = new OrdersMS();
	
	$ok = true;
	$error = '';
	
	foreach ($data['data']['shipments'] as $shipment)
	{
	    $order = $ordersClass->findOrders(array('name' => $shipment['shipmentId']));
	    if (!count($order))
	    {
	        $log->write(__LINE__ . ' Заказ ' . $shipment['shipmentId'] . ' не найден');
	        continue;
	    }
	    
	    $state = APIMS::getIdFromHref($order[0]['state']['meta']['href']);
	    
	    if (in_array($state, [MS_NEW_STATE_ID, MS_CONFIRM_STATE_ID, MS_MPNEW_STATE_ID, MS_CONFIRMBERU_STATE_ID])) {
	        $order_data = array();
	        $order_data['state'] = array('meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDERSTATE . '/' . MS_CANCEL_STATE_ID, 'state'));
	        $order_data['attributes'] = array();
	        // отмена маркетплейс
	        $order_data['attributes'][] = array(
	            'meta' => APIMS::createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_MPCANCEL_ATTR_ID, 'attributemetadata'),
	            'value' => true
	        );
	        
	    }
	    $return = $ordersClass->updateCustomerorder($order[0]['id'], $order_data);
	    $log->write(__LINE__ . ' order - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}
	
	if ($ok)
	{
		header('Content-Type: application/json');
		echo '{"success":1,"meta":{"source":"appolon"}}';
	}
	else
	{
	    header('HTTP/1.0 400 Bad Request');
	    echo $error;
	    return;
	}

	return;
?>
