<?php
//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Wildberries/Products.php');
//$productsWBClass = new \Classes\Wildberries\v1\Products('Kaori');
//$productsWB = $productsWBClass->cardList();
//$codes = array();
//foreach ($productsWB as $product)
//{
//    if (isset($product['nomenclatures'][0]['variations'][0]['chrtId']))
//        $codes[] = $product['nomenclatures'][0]['variations'][0]['chrtId'];
//    else 
//        $codes[] = null;
//}
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
$logger = new Log('test.log');

$data = json_decode (file_get_contents('php://input'), true);
$logger->write(__LINE__ . ' _post - ' . file_get_contents('php://input'));

$output = array(
    'number' => $data['number'] * 2,
    'text' => 'Результат умножения = ' . ($data['number'] * 2)
);
header('Content-Type: application/json');
echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


/*
	$i = 1;
	$a = date('w', strtotime('+' . $i . ' day'));
	$a = date('d-m-Y');
	
	$b = (int)date('G');
	//var_dump($i);
	var_dump($a);
	var_dump($b); */
	
/* 	require_once($_SERVER['DOCUMENT_ROOT'] . '/priceList/Classes/ProductAttributes.php');
	
	$p = new ProductAttributes();
	
	echo json_encode ($p->getProductAttributes(1), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); */
	
	//echo date ('d.m.Y H:i:s', 1629472732);
	
    //$mem = new Memcached();
    //$ret = $mem-> (array(array('localhost',11211)));
    
    //$ret = $mem->add('a', array(1,23,43,23,4,543));
    //echo json_encode ($ret, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    //echo json_encode ($mem->getResultCode(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    //$ret2 = $mem->get('a');
    //echo json_encode ($ret2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
 	
    //require 'redis';
 	//$r = new Redis;
 	//$r->set('foo',array(1,23,43,23,4,543));
 	//echo json_encode($r->get('foo'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
 	
 	
?>