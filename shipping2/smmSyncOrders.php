<?php
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sbermegamarket/Order.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$log = new Log ('shipping2 - smmSyncOrders.log');

	$orderNumbers = json_decode(file_get_contents('php://input'), true);
	
	$log->write(__LINE__ . ' orderNumbers - ' . json_encode ($orderNumbers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	$smmOrderClass = new Classes\Sbermegamarket\Order();
	$msOrdersClass = new OrdersMS();

	foreach ($orderNumbers as $orderNumber)
	{
	    $order = $smmOrderClass->getOrders($orderNumber);
	    $log->write(__LINE__ . ' order - ' . json_encode ($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    if (isset($order['success']) && $order['success'] === 0) {
	        continue;
	    }
	    
	    if (!isset($order['data']['shipments'][0]) || $order['data']['shipments'][0]['items'][0]['status'] == 'CUSTOMER_CANCELED')
	    {
	        $msOrder = $msOrdersClass->findOrders(array('name' => $orderNumber));
	        $log->write(__LINE__ . ' msOrder - ' . json_encode ($msOrder, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	        if (isset($msOrder[0]['id']))
	        {
	            $data = array(
	                'attributes' => array(
	                    array(
	                        'meta' => APIMS::createMeta (MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_CUSTOMERORDER . MS_API_ATTRIBUTES . '/' . MS_CANCEL_ATTR, 'attributemetadata'),
	                        'value' => true
	                    )
	                )
	            );
	            $log->write(__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	            
	            $ret = $msOrdersClass->updateCustomerorder($msOrder[0]['id'], $data);
	            $log->write(__LINE__ . ' ret - ' . json_encode ($ret, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	            
	        }
	    }
	}

?>

