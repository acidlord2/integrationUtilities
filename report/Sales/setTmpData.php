<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/Api.php');
	$table = 'report_sales';
	$fields = ['product_id', 'product_code', 'product', 'project', 'quantity', 'sum'];
	
	$log = new \Classes\Common\Log('reports - Sales - setTmpData.log');
	
	$data = json_decode(file_get_contents('php://input'), true);
	$log->write(__LINE__ . ' data - ' . json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		
	$dbClass = new \Classes\Common\Db();
	$msApiClass = new \Classes\MS\Api();
	
	foreach ($data as $demand)
	{
	    
	    $project = isset($demand['project']['meta']['href']) ? \Classes\MS\Api::getIdFromHref($demand['project']['meta']['href']) : '';
	    $positions = $msApiClass->getData($demand['positions']['meta']['href']);
	    foreach ($positions['rows'] as $position)
	    {
	        $assortment = $msApiClass->getData($position['assortment']['meta']['href']);
	        $dbClass->insert($table, $fields, [$assortment['id'], $assortment['code'], $assortment['name'], $project, $position['quantity'], ($position['price']*$position['quantity'])/100]);
	    }
	}

	//echo json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	return; 
	
?>

