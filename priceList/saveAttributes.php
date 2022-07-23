<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/products.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/priceList/Classes/ProductAttributes.php');
	
	$productAttributesClass = new ProductAttributes();

	$data = json_decode (file_get_contents('php://input'), true);
	$logger = new Log ('priceList-saveAttributes.log');
	$logger->write (json_encode ($data));
	foreach ($data as $productId => $attributes)
		foreach ($attributes as $attributeid => $attributeValue)
			$productAttributesClass->setProductAttributeValue ($productId, $attributeid, $attributeValue);
			
	return;
?>
