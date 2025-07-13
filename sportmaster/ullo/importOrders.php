<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/Order-v1.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sportmaster/order.php');

$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
$logName .= '.log';
$log = new \Classes\Common\Log($logName);

$clientId = SPORTMASTER_ULLO_CLIENT_ID;
$warehouseId = SPORTMASTER_ULLO_WAREHOUSE_ID;
$orderSportmasterClass = new \Classes\Sportmaster\v1\Order($clientId);
$orders = $orderSportmasterClass->shipmentsList($warehouseId, ['FOR_PICKING']);
$ordersMS = array();
// If you want to use MS Products class, uncomment the following lines
foreach ($orders as $order) {
    $orderCard = $orderSportmasterClass->shipmentGet($order['id']);
    $transfomationClass = new \Sportmaster\Order\OrderTransformation($orderCard);
    $orderMS = $transfomationClass->transformSportmasterToMS();
    if (!$orderMS) {
        $log->write(__LINE__ . ' '. __FUNCTION__ . ' Failed to transform order: ' . json_encode($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        continue;
    }
    $ordersMS[] = $orderMS;
}
if (count($ordersMS) > 0) {
    $orderMSClass = new OrdersMS();
    $result = $orderMSClass->createCustomerorder($ordersMS);
    if ($result) {
        $log->write(__LINE__ . ' '. __FUNCTION__ . ' Successfully created ' . count($ordersMS) . ' orders in MS');
        echo count($ordersMS) . ' orders created successfully';
    } else {
        $log->write(__LINE__ . ' '. __FUNCTION__ . ' Failed to create orders in MS');
        echo 'Failed to create orders';
    }
} else {
    $log->write(__LINE__ . ' '. __FUNCTION__ . ' No orders to process');
    echo 'No orders to process';
}