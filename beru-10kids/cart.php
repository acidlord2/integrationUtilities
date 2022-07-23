<?php
	/**
	 * Return product information added in cart
	 *
	 * @class Cart
	 * @author GPOLYAN <acidlord@yandex.ru>
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/products.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/geoData.php');
	
	$arr = array('07-05-2022', '08-05-2022', '09-05-2022');
	
	$daysCount = 1;
	$logger = new Log('beru-10kids - cart.log');
	$geoData = new GeoData();
	
	$logger->write(__LINE__ . ' _GET - ' . json_encode ($_GET, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			
	// check auth-token
	if (isset($_GET['auth-token']) ? (string)$_GET['auth-token'] != Settings::getSettingsValues('beru_auth_token_22064982') : true)
	{
		header('HTTP/1.0 403 Forbidden');
		echo 'You are forbidden!';
		return;
	}
	// check fake
	$fake = isset($_GET['fake']) ? (bool)$_GET['fake'] : false;

	if ($_SERVER['REQUEST_METHOD'] != 'POST')
	{
		header('HTTP/1.0 400 Bad Request');
		echo 'Request must be POST';
		return;
	}

	$data = json_decode (file_get_contents('php://input'), true);
	$logger->write(__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

	if (!isset ($data['cart']))
	{
		header('HTTP/1.0 400 Bad Request');
		echo 'Missing required parameter "cart"';
		return;
	}

	if (!isset ($data['cart']['items']))
	{
		header('HTTP/1.0 400 Bad Request');
		echo 'Missing required parameter "items"';
		return;
	}

	$ok = true;

	// prepare data order
	$return = array();
	$return['cart'] = array();
	$return['cart']['items'] = array();

    $prodCodes = array_column ($data['cart']['items'], 'offerId');
    foreach ($prodCodes as $key => $prodCode)
        if (strpos($prodCodes[$key], '.') !== false)
            $prodCodes[$key] = explode('.', $prodCodes[$key])[1];
    
	$products = Products::getMSStock ($prodCodes);
	//$logger->write("03 products - " . json_encode ($products, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	$amount = 0;
	foreach ($data['cart']['items'] as $item)
	{
	    $pkey = array_search ($item['offerId'], array_column ($products, 'code'));
		if ($pkey !== false)
		{
			foreach ($products[$pkey]['salePrices'] as $salePrice)
			{
				if ($salePrice['priceType'] == 'Цена 10kids/GOODS')
				{	
					$amount += $salePrice['value'] / 100;
					break;
				}
			}

			$product = $products[$pkey];
			$return['cart']['items'][] = array(
				'offerId' => (string)$item['offerId'],
				'feedId' => $item['feedId'],
				'count' => (int)$product['quantity'] < 0 ? 0 : (int)$product['quantity']
				//'count' => 0
			);
		}
		else
		{
		    $return['cart']['items'][] = array(
		        'offerId' => (string)$item['offerId'],
		        'feedId' => $item['feedId'],
		        //'count' => (int)$product['quantity'] < 0 ? 0 : (int)$product['quantity']
		        'count' => 0
		    );
		}
	}
	
	if ($ok)
	{
	    $fromDate = '';
		$toDate = '';
		$intervals = array();
		for ($i=1;$i<=$daysCount;$i++)
		{
		    //$logger->write(__LINE__ . ' date - ' . date('d-m-Y', strtotime('+' . $i . ' day')));
		    if (date('w', strtotime('+' . $i . ' day')) !== '6' && date('w', strtotime('+' . $i . ' day')) !== '0' && !in_array(date('d-m-Y', strtotime('+' . $i . ' day')), $arr))
			{	
				$intervals[] = array (
					'date' => date('d-m-Y', strtotime('+' . $i . ' day')),
					'fromTime' => '10:00',
					'toTime' => '21:00'
				);
				if ($fromDate == '')
					$fromDate = date('d-m-Y', strtotime('+' . $i . ' day'));
				if ($i == $daysCount)
					$toDate = date('d-m-Y', strtotime('+' . $i . ' day'));
			}
			else
				$daysCount ++;
		}

		$return['cart']['deliveryOptions'] = array (
		    0 => array (
		        'paymentAllow' => false,
		        'type' => 'PICKUP',
		        'serviceName' => 'PICKUP',
		        'price' => 0,
		        'outlets' => array (
		            0 => array (
		                'code' => BERU_10KIDS_OUTLET
		            )
		        ),
		        'dates' => array (
		            'fromDate' => $fromDate,
		            'toDate' => $toDate
		        )
		    )
		);
		
		/*if (isset ($data['cart']['delivery']['address']['lat']) && isset ($data['cart']['delivery']['address']['lon']))
		{
		    if ($geoData->isInsideMkad($data['cart']['delivery']['address']['lat'], $data['cart']['delivery']['address']['lon']) || $geoData->isInsideKhimki($data['cart']['delivery']['address']['lat'], $data['cart']['delivery']['address']['lon']) || strpos($data['cart']['delivery']['region']['name'], 'Реутов') !== false || strpos($data['cart']['delivery']['region']['name'], 'Мытищи') !== false)// || $geoData->getDistance($data['cart']['delivery']['address']['lat'], $data['cart']['delivery']['address']['lon']) < 10000)
				$return['cart']['deliveryOptions'] = array (
					0 => array (
						'paymentAllow' => false,
						'type' => 'DELIVERY',
						'serviceName' => 'DELIVERY',
						'price' => ($amount >= 1000 ? 0 : 99),
						'dates' => array (
							'fromDate' => $fromDate,
							'toDate' => $toDate,
							'intervals' => $intervals
						)
					),
					1 => array (
						'paymentAllow' => false,
						'type' => 'PICKUP',
						'serviceName' => 'PICKUP',
						'price' => 0,
						'outlets' => array (
							0 => array (
								'code' => BERU_10KIDS_OUTLET
							)
						),
						'dates' => array (
							'fromDate' => $fromDate,
							'toDate' => $toDate
							//'toDate' => date('d-m-Y', strtotime('+' . $daysCount . ' day'))
						)
					)
				);
			else
				$return['cart']['deliveryOptions'] = array (
					0 => array (
						'paymentAllow' => false,
						'type' => 'PICKUP',
						'serviceName' => 'PICKUP',
						'price' => 0,
						'outlets' => array (
							0 => array (
								'code' => BERU_10KIDS_OUTLET
							)
						),
						'dates' => array (
							'fromDate' => $fromDate,
							'toDate' => $toDate
						)
					)
				);
		
		}
		else
		{
		    if ($data['cart']['delivery']['region']['name'] == 'Москва' || strpos($data['cart']['delivery']['region']['name'], 'Химки') !== false || strpos($data['cart']['delivery']['region']['name'], 'Реутов') !== false || strpos($data['cart']['delivery']['region']['name'], 'Мытищи') !== false)
				$return['cart']['deliveryOptions'] = array (
					0 => array (
						'paymentAllow' => false,
						'type' => 'DELIVERY',
						'serviceName' => 'DELIVERY',
						'price' => ($amount >= 1000 ? 0 : 99),
						'dates' => array (
							'fromDate' => $fromDate,
							'toDate' => $toDate,
							'intervals' => $intervals
						)
					),
					1 => array (
						'paymentAllow' => false,
						'type' => 'PICKUP',
						'serviceName' => 'PICKUP',
						'price' => 0,
						'outlets' => array (
							0 => array (
								'code' => BERU_10KIDS_OUTLET
							)
						),
						'dates' => array (
							'fromDate' => $fromDate,
							'toDate' => $toDate
						)
					)
				);
			else
				$return['cart']['deliveryOptions'] = array (
					0 => array (
						'paymentAllow' => false,
						'type' => 'PICKUP',
						'serviceName' => 'PICKUP',
						'price' => 0,
						'outlets' => array (
							0 => array (
								'code' => BERU_10KIDS_OUTLET
							)
						),
						'dates' => array (
							'fromDate' => $fromDate,
							'toDate' => $toDate
						)
					)
				);
				
		}*/		
		
		$return['cart']['deliveryCurrency'] = 'RUR';
		
		$return['cart']['paymentMethods'] = array (
			0 => 'APPLE_PAY',
			1 => 'YANDEX',
			2 => 'GOOGLE_PAY',
			3 => 'CASH_ON_DELIVERY'
		);
		

		header('Content-Type: application/json');
		echo json_encode($return);
	}
	
	$logger->write(__LINE__ . ' return - ' . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	return;
?>
