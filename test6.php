<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/aliApi.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/aliapi/TopSdk.php');

$c = new TopClient;
$c->appkey = '31422861';
$c->secretKey = '6f2e8e80a97089d9af1a4f3519e73718';
$req = new AliexpressSolutionOrderInfoGetRequest;
$param1 = new OrderDetailQuery;
$param1->order_id="5013374986134593";
$req->setParam1(json_encode($param1));
$resp = $c->execute($req, '50002600b29gjQbAozmuhedgVgivDyOFeFBSCnttl17d13abejHLp3DtxyozTuFgN7w');

echo json_encode($resp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>