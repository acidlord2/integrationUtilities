<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/aliApi.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/products.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
	
	$logger = new Log ('aliexpress-updateQuantities.log');
	//$logger -> write (json_encode ($data));
	
	$sql = "select * from product_mapping where ext_account = 'ali-ru1386043510mwbr'";
	$products = Db::exec_query_array ($sql);
	
	$logger -> write ('01 products - ' . json_encode ($products, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	$stock = Products::getMSStock (array_column ($products, 'sku'));
	//$logger -> write ('stock - ' . json_encode ($stock, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
			//continue;
		}
		else
			$params['setMutipleProductUpdateList'][] = array (
				'product_id' => $product['ext_id'],
				'multiple_sku_update_list' => array (
					'sku_code' => $product['sku'],
					'inventory' => $stock[$idKey]['quantity'] < 0 ? 0 : $stock[$idKey]['quantity']
					//'inventory' => 0
				)
			);
		
		if (($key + 1) % 20 == 0 || $key + 1 == count ($products))
		{
			AliAPI::getAliData('AliexpressSolutionBatchProductInventoryUpdateRequest', $params, $jsonOut, $arrayOut);
			$logger -> write ('01 params - ' . json_encode ($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$logger -> write ('01 arrayOut - ' . json_encode ($arrayOut, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$params = array(
				'setMutipleProductUpdateList' => array()
			);
		}
	}
	
	echo 'Updated ' . count ($products) . ' products';
?>

