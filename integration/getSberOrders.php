<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
$log = new Log ('integration - getSberOrders.log');

$curl = curl_init('https://10kids.ru/index.php?route=extension/importorders/getSberOrders');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$ordersJson = curl_exec($curl);
curl_close($curl);

$orders = json_decode($ordersJson, TRUE);
$log->write(__LINE__ . ' ordersJson - ' . $ordersJson);

if (count($orders))
    echo $ordersJson;
else 
    echo '[]';
?>

