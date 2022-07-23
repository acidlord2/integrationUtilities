<?php
	/**
	* Return json stock info
	*
	* @class ControllerExtensionBeruStocks
	* @author GPOLYAN <acidlord@yandex.ru>
	*/
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log('beruRomashkaStocks.log'); //just passed the file name as file_name.log
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

	if (!isset ($data['warehouseId']))
	{
		header('HTTP/1.0 400 Bad Request');
		echo 'Missing required parameter "warehouseId"';
		return;
	}

	if (!isset ($data['skus']))
	{
		header('HTTP/1.0 400 Bad Request');
		echo 'Missing required parameter "skus"';
		return;
	}

	$ok = true;
	$return = array();

	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/products.php');

	date_default_timezone_set('Europe/Moscow');
	$products = Products::getMSStock ($data['skus']);
	foreach ($data['skus'] as $sku)
	{
		$idKey = array_search ($sku, array_column ($products, 'code'));
		if ($idKey !== false)
		{
			$product = $products[$idKey];
			$beruPriceKey = array_search ('Цена Беру ullo', array_column ($product['salePrices'], 'priceType'));
			$skus = array (
				'sku' => (string)$product['code'],
				'warehouseId' => (string)$data['warehouseId'],
				'items' => array (
					0 => array (
						'type' => 'FIT',
						'count' => $beruPriceKey !== false ? (string)$product['quantity'] : '0',
						//'count' => '0',
						'updatedAt' => date ('Y-m-d\TH:i:sP', strtotime("now"))
					)
				)
			);
			$return['skus'][] = $skus;
		}
		else
		{
			$skus = array (
				'sku' => $sku,
				'warehouseId' => (string)$data['warehouseId'],
				'items' => array (
					0 => array (
						'type' => 'FIT',
						'count' => "0",
						'updatedAt' => date ('Y-m-d\TH:i:sP', strtotime("now"))
					)
				)
			);
			$return['skus'][] = $skus;
		}

	}
	$logger->write("return - " . json_encode ($return, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	if ($ok)
	{
		header('Content-Type: application/json');
		echo json_encode($return);
	}
?>
