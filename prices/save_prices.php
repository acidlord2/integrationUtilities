<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/products.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	
	$data = json_decode (file_get_contents('php://input'), true);
	$logger = new Log ('save_prices.log');
	$logger -> write (json_encode ($data));
	foreach ($data as $product => $prices)
	{
		//$logger -> write (json_encode ($product));
		//$logger -> write (json_encode ($prices));
		products::updateProduct ($product, $prices);		
	}
	return;
?>

