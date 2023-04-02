<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/productsOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	
	$logger = new Log ('ozonUllo - updateProducts.log');
	$productsOzon = ProductsOzon::getOzonProducts(false);
	if (count ($productsOzon) > 0) {
		$filter = '';
		$productsMS = array();
		//$logger -> write ('productsOzon - ' . json_encode ($productsOzon, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
		foreach ($productsOzon as $key => $productOzon)
		{
			$filter .= 'code=' . $productOzon['offer_id'] . ';';
			if (($key + 1) % 200 == 0 || $key + 1 == count ($productsOzon))
			{
				$productsMStmp = null;
				while ($productsMStmp == null)
					$productsMStmp = ProductsMS::getAssortment($filter);
				$productsMS = array_merge ($productsMS, $productsMStmp);
				$filter = '';
			}
		}

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
			foreach (array_column($productMS['salePrices'], 'priceType') as $pkey => $priceType)
				if ($priceType['name'] == 'Цена Ozon')
					$keyOzonPrice = $pkey; 
			
			$price = $keyOzonPrice !== false ? $productMS['salePrices'][$keyOzonPrice]['value'] / 100 : 0;
			$quantity = $keyOzonPrice !== false ? ($productMS['quantity'] < 0 ? 0 : $productMS['quantity']) : 0;

			array_push ($prices['prices'], array ('offer_id' => $productMS['code'], 'price' => (string)$price));
			array_push ($stocks['stocks'], array ('offer_id' => $productMS['code'], 'stock' => ($price == 0 ? 0 : $quantity)));
			
			if (count ($stocks['stocks']) == 100 || count ($productsMS) == ($key + 1))
			{
				ProductsOzon::updatePrices($prices, false);
				ProductsOzon::updateStock($stocks, false);
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

