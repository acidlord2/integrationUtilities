<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/OrdersOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	$log = new Log ('ozonKaoriDbs - cancelOrders.log');
	
	$ordersOzonClass = new OrdersOzon('kaori');
	
	if (isset($_GET['period']))
		$paramPeriod = ($_GET['period']);
	else
		$paramPeriod = 20;
	
	$ordersOzon = $ordersOzonClass->findOrders(date('Y-m-d', strtotime('now -' . $paramPeriod . ' days')) . 'T00:00:00Z', date ('Y-m-d', strtotime('now')) . 'T23:59:59Z', 'cancelled', OZON_WEARHOUSE2_ID);
	$log->write(__LINE__ . ' ordersOzon - ' . json_encode ($ordersOzon, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	//$canceledOrdersOzon = OrdersOzon::orderList($from, $to, 'cancelled', true);
	
	if (!count ($ordersOzon))
	{
	    echo 'No cancelled orders';
	    return;
	}
	
	$ordersClass = new OrdersMS();
	$cancelled = 0;
	$marked = 0;
	$notFound = 0;
	$notTouched = 0;
	
    $postingNumbers = array_column ($ordersOzon, 'posting_number');
    $msOrders = $ordersClass->findOrdersByNames($postingNumbers);
	$namesMS = array_column ($msOrders, 'name');
	foreach ($postingNumbers as $postingNumber)
		if (!in_array ($postingNumber, $namesMS))
		{
		    $log->write (__LINE__ . ' notFound postingNumber - ' . $postingNumber);
			$notFound++;
		}
	$postdata = array();
	foreach ($msOrders as $key => $msOrder)
	{
		$cancelKey = array_search (MS_CANCEL_ATTR, array_column ($msOrder['attributes'], 'id'));
		if ($msOrder['state']['meta']['href'] != MS_CANCEL_STATE || ($cancelKey !== false ? !$msOrder['attributes'][$cancelKey]['value'] : true))
		{
		    $data = array(
		        'meta' => $msOrder['meta'],
		        'attributes' => array(
		            array(
		                'id' => MS_CANCEL_ATTR,
		                'value' => true
		            )
		        )
		    );
		    if ($msOrder['state']['meta']['href'] == MS_NEW_STATE || $msOrder['state']['meta']['href'] == MS_MPNEW_STATE || $msOrder['state']['meta']['href'] == MS_CONFIRM_STATE || $msOrder['state']['meta']['href'] == MS_CONFIRMGOODS_STATE)
			{
			    $data['state'] = array(
			        'meta' => APIMS::createMeta (MS_API_BASE_URL. MS_API_VERSION_1_2. MS_API_STATE . '/' . MS_CANCEL_STATE_ID, 'state')
			    );
				$cancelled++;
			}	
			else
			{
				$marked++;
			}
			$postdata[] = $data;
		}
		else {
			$notTouched++;
		}
			
		
	}
	
	if (count ($postdata))
		foreach (array_chunk ($postdata, 50) as $chunkPostdata)
		    $ordersClass->createCustomerorder ($chunkPostdata);

	echo 'cancelled: ' . $cancelled . ' marked: ' . $marked . ' not found: ' . $notFound . ' already cancelled: ' . $notTouched;
?>

