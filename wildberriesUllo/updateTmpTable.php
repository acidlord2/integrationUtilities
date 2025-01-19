<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Products.php');
	
	$log = new \Classes\Common\Log ('wildberriesKaori - updateTmpTable.log');
	$wbProductsClass = new \Classes\Wildberries\v1\Products('Kaori');
	
	$products = $wbProductsClass->cardList();
	
	$dbClass = new \Classes\Common\Db();
	$dbClass->truncate('product_mapping_wildberries');
	
	$processed = 0;
	$notProcessed = 0;
	foreach ($products as $product) {
	    if (!isset($product['supplierVendorCode']) ? true : $product['supplierVendorCode'] == '') {
	        $log->write(__LINE__ . ' supplierVendorCode not found - ' . $product['id']);
	        $notProcessed++;
	        continue;
	    }
	    
	    $code = $product['supplierVendorCode'];
	    $chrtId = isset ($product['nomenclatures'][0]['variations'][0]['chrtId']) ? $product['nomenclatures'][0]['variations'][0]['chrtId'] : NULL;
	    $nmId = isset ($product['nomenclatures'][0]['nmId']) ? $product['nomenclatures'][0]['nmId'] : NULL;
	    $imtId = isset ($product['imtId']) ? $product['imtId'] : NULL;
	    $barcode = isset ($product['nomenclatures'][0]['variations'][0]['barcodes'][0]) ? $product['nomenclatures'][0]['variations'][0]['barcodes'][0] : NULL;
	    
	    $dbClass->insert('product_mapping_wildberries', ['chrt_id', 'nmId', 'code', 'codeimt', 'barcode'], [$chrtId, $nmId, $code, $imtId, $barcode]);
	    $processed++;
	}
	
	echo 'Processed ' . $processed . ', not processed ' . $notProcessed . ' from total ' . count($products);
?>

