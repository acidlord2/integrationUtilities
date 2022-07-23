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
	$logger = new Log('beruRomashkaCart.log'); //just passed the file name as file_name.log
	$logger->write("_GET - " . json_encode ($_GET, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			
	// check auth-token
	if (isset($_GET['auth-token']) ? (string)$_GET['auth-token'] != Settings::getSettingsValues('romashka_beru_auth_token') : true)
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
	$logger->write("data - " . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

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
	foreach ($data['cart']['items'] as $item)
	{
	    $pkey = array_search ($item['offerId'], array_column ($products, 'code'));
		if ($pkey !== false)
		{
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
		        'count' => 0
		    );
		    
// 			if ($ok)
// 			{
// 				$ok = false;
// 				header('HTTP/1.0 400 Bad Request');
// 			}
// 			echo 'Product sku ' . $item['offerId'] . ' did not found';
		}

	}
	$logger->write("return - " . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

	if ($ok)
	{
		header('Content-Type: application/json');
		echo json_encode($return);
	}
	return;
?>
