<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/aliApi.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/products.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
	
	$logger = new Log ('updatePrices.log');
	//$logger -> write (json_encode ($data));
	
	$sql = "select * from product_mapping where ext_account = 'ali-ru1386043510mwbr'";
	$products = Db::exec_query_array ($sql);
	
	$logger -> write ('products - ' . json_encode ($products, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	$stock = Products::getMSStock (array_column ($products, 'sku'));
	$params = array(
		'setMutipleProductUpdateList' => array()
	);
	$i = 0;
	foreach ($products as $key => $product)
	{
		$idKey = array_search ($product['sku'], array_column ($stock, 'code'));
		if ($idKey === false)
		{
			$logger -> write ('Не найден код товара в МС - ' . $product['sku']);
			continue;
		}
		$priceKey = array_search ('Цена ALI', array_column ($stock[$idKey]['salePrices'], 'priceType'));
		if ($priceKey === false)
		{
			$logger -> write ('Для товара не задана цена ALI в МС - ' . $product['sku']);
			continue;
		}
		$i++;
		$params['setMutipleProductUpdateList'][] = array (
			'product_id' => $product['ext_id'],
			'multiple_sku_update_list' => array (
				'sku_code' => $product['sku'],
				'price' => $stock[$idKey]['salePrices'][$priceKey]['value'] / 100
			)
		);
		if ($i == 20 || $key + 1 == count ($products))
		{
			AliAPI::getAliData('AliexpressSolutionBatchProductPriceUpdateRequest', $params, $jsonOut, $arrayOut);
			$logger -> write ('params - ' . json_encode ($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$logger -> write ('arrayOut - ' . json_encode ($arrayOut, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$params = array(
				'setMutipleProductUpdateList' => array()
			);
			$i = 0;
		}
	}
	
	echo 'Updated ' . count ($products) . ' products';
?>

