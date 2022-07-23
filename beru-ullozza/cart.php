<?php
	/**
	 * Return product information added in cart
	 *
	 * @class ControllerExtensionBeruCart
	 * @author GPOLYAN <acidlord@yandex.ru>
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/products.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
			
	// check auth-token
	if (isset($_GET['auth-token']) ? (string)$_GET['auth-token'] != Settings::getSettingsValues('auth-token') : true)
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
	$return['cart']['taxSystem'] = 'PSN';
	$return['cart']['items'] = array();

    $prodCodes = array_column ($data['cart']['items'], 'offerId');
    foreach ($prodCodes as $key => $prodCode)
        if (strpos($prodCodes[$key], '.') !== false)
            $prodCodes[$key] = explode('.', $prodCodes[$key])[1];
    
	$products = Products::getMSStock ($prodCodes);
	foreach ($data['cart']['items'] as $item)
	{
	    $pkey = array_search ($item['offerId'], array_column ($products, 'code'));
		if ($pkey !== false)
		{
			$product = $products[$pkey];
			//$priceKey = array_search ('Цена продажи', array_column ($product['salePrices'], 'priceType'));
			$beruPriceKey = array_search ('Цена Беру ullo', array_column ($product['salePrices'], 'priceType'));

			$return['cart']['items'][] = array(
				'offerId' => (string)$item['offerId'],
				'feedId' => $item['feedId'],
				'count' => $beruPriceKey !== false ? ((int)$product['quantity'] < 0 ? 0 : (int)$product['quantity']) : 0,
				//'count' => 0,
				//'price' => $beruPriceKey !== false ? (float)$product['salePrices'][$beruPriceKey]['value'] / 100 : 0,
				'vat' => 'NO_VAT',
				'delivery' => true
			);
		}
		else
		{
		    $return['cart']['items'][] = array(
		        'offerId' => (string)$item['offerId'],
		        'feedId' => $item['feedId'],
		        'count' => 0,
		        'vat' => 'NO_VAT',
		        'delivery' => true
		    );
		    // 			if ($ok)
// 			{
// 				$ok = false;
// 				header('HTTP/1.0 400 Bad Request');
// 			}
// 			echo 'Product sku ' . $item['offerId'] . ' did not found';
		}

	}

	if ($ok)
	{
		//$order_id = $this->model_checkout_order->addOrder($order_data);
		//$order_status_id = 2;
		//$this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
		header('Content-Type: application/json');
		echo json_encode($return);
	}
	return;
?>
