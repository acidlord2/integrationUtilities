<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/Assortment-v2.php');
	$assortmentClass = new \Classes\MS\v2\Assortment();
	
	$assortmentClass->updateAssortment();
	echo json_encode($assortmentClass->getAssortment());
?>

