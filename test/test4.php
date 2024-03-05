<?php
    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/aliApi.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/aliapi/TopSdk.php');
    date_default_timezone_set('Europe/Moscow');
    
    //$params['setOrderId'] = "5013288824349531";
    
    //AliAPI::getAliData('AliexpressLogisticsRedefiningGetonlinelogisticsservicelistbyorderidRequest', $params, $jsonOut, $arrayOut);
    
    $c = new TopClient;
    $c->appkey = "31422861";
    $c->secretKey = "6f2e8e80a97089d9af1a4f3519e73718";
    $c->format = 'json';
    $req = new AliexpressLogisticsGetpdfsbycloudprintRequest;
    $req->setPrintDetail("true");
    $warehouse_order_query_d_t_os = new AeopWarehouseOrderQueryPdfRequest;
    $warehouse_order_query_d_t_os->id=3200004090220;
    $warehouse_order_query_d_t_os->international_logistics_id="99880002113700";
    //$warehouse_order_query_d_t_os->extend_data="[{\"imageUrl\":\"http://xxxxxx\",\"productDescription\":\"ALIBAB\r\nALIBABA\r\nALIBABA\"}]";
    $req->setWarehouseOrderQueryDTOs(json_encode($warehouse_order_query_d_t_os));
    $resp = $c->execute($req, "50002600b29gjQbAozmuhedgVgivDyOFeFBSCnttl17d13abejHLp3DtxyozTuFgN7w");
    
    echo json_encode($resp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
	
	
?>