<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
$log = new Log ('integration - importSberOrders.log');

$orderNumbers = file_get_contents('php://input');

$curl = curl_init('https://10kids.ru/index.php?route=extension/importorders/importSberOrders');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $orderNumbers);
$ordersJson = curl_exec($curl);
curl_close($curl);

$orders = json_decode($ordersJson, TRUE);
$log->write(__LINE__ . ' ordersJson - ' . $ordersJson);

echo 'ok';
?>

