<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/api/apiMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Yandex/ordersYandex.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Ozon/OrdersOzon.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	// logger class
	$log = new Log ('mswh - whChangeAssortment.log');
	// msapi class
	$apiMSClass = new APIMS();
	// yandex class
	
	$content = json_decode (file_get_contents('php://input'), true);
	$log->write(__LINE__ . ' content - ' . file_get_contents('php://input'));

?>