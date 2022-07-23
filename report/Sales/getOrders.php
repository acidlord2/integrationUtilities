<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/demandsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
	$log = new \Classes\Common\Log('reports - Sales - getOrders.log');
	
	$data = json_decode(file_get_contents('php://input'), true);
	$log->write(__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	$startDate = $data['startDate'];
	$endDate = $data['endDate'];
	$page = $data['page'];

	
	$filter = '';
	//$_SESSION['products'][$index] = array();

	$filter .= 'moment%3E=' . $startDate . '%2000:00:00;';
	$filter .= 'moment%3C=' . $endDate . '%2023:59:59;';
	
	$ordersClass = new \DemandsMS();
	$orders = $ordersClass->findDemands($filter, $page);
	
	echo json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	return; 
	
	//$log->write(' orders - ' . json_encode ($_SESSION['orders'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	//echo json_encode ($_SESSION['orders'][$shippingDate . $agent . $org], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

