<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/productsOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	
	$barcodes = [];//["005-1118","005-1336","005-1119","120922005","005-1112","005-1114","005-1110","120921718","005-1111","120921791","005-1113","005-1518","005-1519","002-105","002-106","005-1520","002-103","002-100","002-098","002-099","002-101","002-102","005-1358","005-1359","005-1357"];
	
	$logger = new Log ('ozonKaori - updateProducts.log');
	$productsOzon = ProductsOzon::getOzonProducts(true);
	if (count ($productsOzon) > 0) {
		$filter = '';
		$productsMS = array();
		
		//$logger -> write ('productsOzon - ' . json_encode ($productsOzon, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$products = array();
		foreach ($productsOzon as $key => $productOzon)
		{
			$filter .= 'code=' . $productOzon['offer_id'] . ';';
			$products[$productOzon['offer_id']] = $productOzon;
			if (($key + 1) % 200 == 0 || $key + 1 == count ($productsOzon))
			{
				$productsMStmp = null;
				while ($productsMStmp == null)
					$productsMStmp = ProductsMS::getAssortment($filter);
				$productsMS = array_merge ($productsMS, $productsMStmp);
				$filter = '';
			}
		}

		//$logger -> write ('productsMS - ' . json_encode ($productsMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		if (count ($productsMS) == 0)
		{
			echo 'Prices and quantities 0 of ' . count($productsOzon) . ' goods are updated';
			return;
		}
		
		$prices = array('prices' => array());
		$stocks = array('stocks' => array());
		foreach ($productsMS as $key => $productMS)
		{
			$keyOzonPrice = false;
			$keyKaoriOzonPrice = false;
			foreach (array_column($productMS['salePrices'], 'priceType') as $pkey => $priceType)
			{
				if ($priceType['name'] == 'Цена Ozon Каори !')
					$keyKaoriOzonPrice = $pkey;
				if ($priceType['name'] == 'Цена Ozon')
					$keyOzonPrice = $pkey; 
			}
			
			$priceKaori = $keyKaoriOzonPrice !== false ? $productMS['salePrices'][$keyKaoriOzonPrice]['value'] / 100 : 0;
			$price = $keyOzonPrice !== false ? $productMS['salePrices'][$keyOzonPrice]['value'] / 100 : 0;
			$quantity = $keyOzonPrice !== false ? ($productMS['quantity'] < 0 ? 0 : $productMS['quantity']) : 0;
			if (in_array($productMS['code'], $barcodes))
			    $quantity = 0;
			// обновляем остаток для шампуня
			if ($productMS['code'] == '120922032')
				$quantity = 0;

			$price = $priceKaori == 0 ? $price : $priceKaori;
			
			array_push ($prices['prices'], array ('offer_id' => $productMS['code'], 'price' => (string)$price, 'old_price' => (string)(int)($price * 1.2)));
			
			array_push (
				$stocks['stocks'],
				array (
					'offer_id' => $productMS['code'],
					'product_id' => $products[$productMS['code']]['product_id'],
					'quant_size' => 1,
					'stock' => ($price == 0 ? 0 : $quantity),
					'warehouse_id' => OZON_ULLO_WEARHOUSE_MAIN
				)
			);
			
			if (count ($stocks['stocks']) == 100 || count ($productsMS) == ($key + 1))
			{
				ProductsOzon::updatePrices($prices, true);
				ProductsOzon::updateStock($stocks, true);
				$prices = array ('prices' => array());
				$stocks = array ('stocks' => array());
			}
		}
		
		//$logger = new Log ('tmp2.log');
		//$logger -> write ($blob);
		echo ('Prices and quantities ' . count ($productsMS) . ' of ' . count ($productsOzon) . ' goods are updated ');
	}
	else
		echo ('Couldn\'t find goods');
?>

