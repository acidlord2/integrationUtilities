<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/ProductsOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

	//$barcodes = ["005-1118","005-1336","005-1119","120922005","005-1112","005-1114","005-1110","120921718","005-1111","120921791","005-1113","005-1518","005-1519","002-105","002-106","005-1520","002-103","002-100","002-098","002-099","002-101","002-102","005-1358","005-1359","005-1357"];
    
	$productOzonClass = new ProductsOzon('ullo');
	$log = new Log ('ozonUlloDbs - UpdateProducts.log');
	$productsOzon = $productOzonClass->getProducts();
	//$productsOzon = ProductsOzon::getOzonProducts(true);
	//if (count ($productsOzon) > 0) {
	if (!count ($productsOzon)) {
	    echo ('Couldn\'t find ozon goods');
	    return;
	}
	
	$productMSClass = new ProductsMS();
    $filter = '';
	$productsMS = array();
	
	$log->write(__LINE__ . ' productsOzon - ' . json_encode ($productsOzon, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	foreach ($productsOzon as $key => $productOzon)
	//foreach ($barcodes as $key => $barcode)
	{
	    $filter .= 'code=' . $productOzon['offer_id'] . ';';
	    //$filter .= 'code=' . $barcode . ';';
	    if (($key + 1) % 200 == 0 || $key + 1 == count ($productsOzon))
	    //if (($key + 1) % 200 == 0 || $key + 1 == count ($barcodes))
		{
			$productsMStmp = null;
			while ($productsMStmp == null)
			    $productsMStmp = $productMSClass->getAssortment($filter);
			$productsMS = array_merge ($productsMS, $productsMStmp);
			$filter = '';
		}
	}

	//$logger -> write ('productsMS - ' . json_encode ($productsMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

	if (!count ($productsMS))
	{
	    //echo 'Prices and quantities 0 of ' . count($productsOzon) . ' goods are updated';
	    //echo 'Prices and quantities 0 of ' . count($barcodes) . ' goods are updated';
	    echo 'Couldn\'t find MS goods' ;
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
			if ($priceType['name'] == 'Цена Ozon')
				$keyOzonPrice = $pkey; 
		}
		
		$price = $keyOzonPrice !== false ? $productMS['salePrices'][$keyOzonPrice]['value'] / 100 : 0;
				
		array_push ($prices['prices'], array ('offer_id' => $productMS['code'], 'price' => (string)$price));
		array_push ($stocks['stocks'], array ('offer_id' => $productMS['code'], 'stock' => ($price == 0 ? 0 : $productMS['quantity']), 'warehouse_id' => OZON_ULLO_WEARHOUSE1_ID));
		//array_push ($stocks['stocks'], array ('offer_id' => $productMS['code'], 'stock' => 0, 'warehouse_id' => OZON_WEARHOUSE1_ID));
		
		if (count ($stocks['stocks']) == 100 || count ($productsMS) == ($key + 1))
		{
		    $productOzonClass->updatePrices($prices);
		    $productOzonClass->updateStock($stocks);
			$prices = array ('prices' => array());
			$stocks = array ('stocks' => array());
		}
	}
	
	//$logger = new Log ('tmp2.log');
	//$logger -> write ($blob);
	echo ('Prices and quantities ' . count ($productsMS) . ' of ' . count ($productsOzon) . ' goods are updated ');
	//echo ('Prices and quantities ' . count ($productsMS) . ' of ' . count ($barcodes) . ' goods are updated ');
?>

