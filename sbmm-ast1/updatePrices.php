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

	$log = new Log('sbmm-ast1 - updatePrices.log');
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://4cleaning.ru/index.php?route=extension/feed/sbmmast1');
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
		$productTmp = $productsMSClass->getAssortment($chunk);
		array_merge($product, $productTmp);
	}

	$sbmmOrdersClass == new \Classes\Sbermegamarket\Order(SBMM_SHOP_AST1);

	foreach(array_chunk($products, 250, true) as $productsData)
	{
		// post body for search engine
		$sbmmData = array(
			'data' => array (
				'prices' => array()
			)
		);
		foreach($productsData as $product)
		{
			$price = array_filter(array_map(function($item) {
				return $item['priceType']['name'] == 'Цена СММ для Юлло' ? $item['value']/100 : null;
			}, $product['salePrices']), function($item) {
				return $item !== null;})[0];
			$sbmmData['data']['prices'][] = array ('offerId' => $product['code'], 'price' => (int)$price, 'isDeleted' => false);
		}
		// service request post
		$sbmmOrdersClass->updatePrices($sbmmData);
	}
	echo 'Updated ' . count($products) . ' prices';
?>
