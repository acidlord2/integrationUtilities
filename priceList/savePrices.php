<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/products.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMetadataMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
	
	$productsMSClass = new ProductsMS();
	$productsMetadataMSClass = new ProductsMetadataMS();
	$apiMSClass = new APIMS();
	
	$data = json_decode (file_get_contents('php://input'), true);
	$logger = new Log ('priceList - savePrices.log');
	$logger->write (__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	$postData = array();
	
	$logger -> write (__LINE__ . ' ' . __METHOD__ . ' $_SESSION[productPriceTypes] - ' . json_encode ($_SESSION['productPriceTypes'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	foreach ($data as $product => $prices)
	{
		$salesPrices = array();
		foreach ($prices as $priceId => $price)
		{
			$priceTypeName = '';
			foreach ($_SESSION['productPriceTypes'] as $priceType)
				if ($priceId == $priceType['priceType_id'])
				{
					$priceTypeName = $priceType['ms_price_name'];
					break;
				}
				
			if ($priceTypeName == '')
			{
				$logger -> write (__LINE__ . ' не найдена цена ' . $priceId . ' в prices - ' . json_encode ($prices, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); 
				continue;
			}
			
			$priceTypeMS = $productsMetadataMSClass->getPriceTypeByName($priceTypeName);
			
			if (!$priceTypeMS)
			{
				$logger -> write (__LINE__ . ' не найдена цена ' . $priceTypeName . ' в MS');
				continue;
			}
			
			$salesPrices[] = array (
				'priceType' => array (
					'meta' => $priceTypeMS['meta']
				),
				'value' => $price * 100

			);
		}
    	if (!$salesPrices)
    	{
    		$logger->write(__LINE__ . ' salesPrices - ' . json_encode($salesPrices, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    		continue;
    	}
    	$postData[] = array (
    		'meta' => $apiMSClass->createMeta(MS_API_BASE_URL . MS_API_VERSION_1_2 . MS_API_PRODUCT . '/' . $product, 'product'),
    		'salePrices' => $salesPrices
    	);
	}
	
	//$logger->write(__LINE__ . ' salesPrices - ' . json_encode($salesPrices, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

	$chunks = array_chunk($postData, 50);
	foreach ($chunks as $chunk)
		$productsMSClass->createUpgradeProducts ($chunk);
	
	return;
?>

