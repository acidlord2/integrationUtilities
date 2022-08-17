<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/products.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/productsOzon.php');
	//require_once('classes/log.php');
	$products_ozon = ProductsOzon::getOzonProducts();
	if (count ($products_ozon) > 0) {
		$ozoncodes = array ();
		foreach ($products_ozon as $product_ozon)
			array_push($ozoncodes, $product_ozon ['offer_id']);

		$products_ms = Products::getMSStock($ozoncodes);
		
		if (count ($products_ms) > 0)
			Products::updateOzonProducts($products_ms);
		
		//$logger = new Log ('tmp2.log');
		//$logger -> write ($blob);
		echo ('Prices and quantities of ' . (count ($products_ozon) > count ($products_ms) ? count ($products_ms) : count ($products_ozon)) . ' goods are updated ');
	}
	else
		echo ('Couldn\'t find goods');
?>

