<?php
	/**
	 * Creates new order
	 *
	 * @author GPOLYAN <acidlord@yandex.ru>
	 */
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sbermegamarket/Order.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');

	$log = new Log('sbmm-ast1 - updatePricesStock.log');
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://4cleaning.ru/index.php?route=extension/feed/sbmmast2');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$response = curl_exec($ch);
	if(curl_errno($ch)){
		echo 'Curl error: ' . curl_error($ch);
	}
	//var_dump($response);
	$xml = simplexml_load_string($response);
	$shop = $xml->children()->shop;
	//echo count($shop->children()->offers->children());
	$codes = array();
	foreach ($shop->children()->offers->children() as $offer) {
		// Access each child element
		array_push($codes, (string)$offer['id']);
	}

	$productsMSClass = new ProductsMS();
	$products = array();
	foreach(array_chunk($codes, 100) as $chunk)
	{
		$log->write(__LINE__ . ' chunk - ' . json_encode ($chunk, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$productTmp = $productsMSClass->getAssortment($chunk);
		//$log->write (__LINE__ . ' productTmp - ' . json_encode ($productTmp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$products = array_merge($products, $productTmp);
	}
	
	$sbmmOrdersClass = new \Classes\Sbermegamarket\Order(SBMM_SHOP_AST2);

	foreach(array_chunk($products, 250, true) as $productsData)
	{
		// post body for search engine
		$sbmmPricesData = array(
			'data' => array (
				'prices' => array()
			)
		);
		$sbmmStockData = array(
			'data' => array (
				'stocks' => array()
			)
		);
		foreach($productsData as $product)
		{
			$price = array_values(array_filter(array_map(function($item) {
				return $item['priceType']['name'] == 'Цена СММ Альянс' ? $item['value']/100 : null;
			}, $product['salePrices']), function($item) {
				return $item !== null;}))[0];
			//$log->write(__LINE__ . ' price - ' . json_encode ($price, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$sbmmPricesData['data']['prices'][] = array ('offerId' => $product['code'], 'price' => (int)$price, 'isDeleted' => false);
			$sbmmStockData['data']['stocks'][] = array ('offerId' => $product['code'], 'quantity' => (int)$product['quantity'] < 0 ? 0 : (int)$product['quantity']);
		}
		// service request post
		$sbmmOrdersClass->updatePrices($sbmmPricesData);
		$sbmmOrdersClass->updateStock($sbmmStockData);
	}
	echo 'Updated ' . count($products) . ' prices and stock';
?>
