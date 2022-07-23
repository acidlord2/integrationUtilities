<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ordersMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('cancelOrdersUllo.log');

	if (isset($_GET['period']))
		$paramPeriod = ($_GET['period']);
	else
		$paramPeriod = 20;
	
	date_default_timezone_set('Europe/Moscow');
	$from = date ('Y-m-d', strtotime('now -' . $paramPeriod . ' days')) . 'T00:00:00Z';
	$to = date ('Y-m-d', strtotime('now')) . 'T23:59:59Z';
	
	$canceledOrdersOzon = OrdersOzon::orderList($from, $to, 'cancelled');
	
	$cancelled = 0;
	$marked = 0;
	$notFound = 0;
	$notTouched = 0;
	
	if (count ($canceledOrdersOzon) > 0)
	{
		$postingNumbers = array_column ($canceledOrdersOzon, 'posting_number');
		$msOrders = OrdersMS::findOrdersByNames($postingNumbers);
		$namesMS = array_column ($msOrders, 'name');
		foreach ($postingNumbers as $postingNumber)
			if (!in_array ($postingNumber, $namesMS))
			{
				$logger -> write ('notFound postingNumber - ' . $postingNumber);
				$notFound++;
			}
		$postdata = array();
		foreach ($msOrders as $key => $msOrder)
		{
			$cancelKey = array_search (MS_CANCEL_ATTR, array_column ($msOrder['attributes'], 'id'));
			if ($msOrder['state']['meta']['href'] != MS_CANCEL_STATE || ($cancelKey !== false ? !$msOrder['attributes'][$cancelKey]['value'] : true))
			{
				if ($msOrder['state']['meta']['href'] == MS_NEW_STATE || $msOrder['state']['meta']['href'] == MS_MPNEW_STATE || $msOrder['state']['meta']['href'] == MS_CONFIRM_STATE || $msOrder['state']['meta']['href'] == MS_CONFIRMGOODS_STATE)
				{
					$postdata[] = array (
						'meta' => $msOrder['meta'],
						'state' => array(
							'meta' => array(
								'href' => MS_CANCEL_STATE,
								'type' => 'state',
								'mediaType' => 'application/json'
							)
						),
						'attributes' => array(
							0 => array (
								'id' => MS_CANCEL_ATTR,
								'value' => true
							)
						)
					);
					$cancelled++;
				}	
				else
				{
					$postdata[] = array (
						'meta' => $msOrder['meta'],
						'attributes' => array(
							0 => array (
								'id' => MS_CANCEL_ATTR,
								'value' => true
							)
						)
					);
					$marked++;
				}
			}
			else 
				$notTouched++;
			
		}
		
		if (count ($postdata))
			OrdersMS::updateOrderMass ($postdata);
	}
	echo 'cancelled: ' . $cancelled . ' marked: ' . $marked . ' not found: ' . $notFound . ' already cancelled: ' . $notTouched;
?>

