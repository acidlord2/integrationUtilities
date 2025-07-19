<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/Order-v1.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sportmaster/order.php');

define('ORDER_STATUSES', ['CANCELLED']);

$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
$logName .= '.log';
$log = new \Classes\Common\Log($logName);

$clientId = SPORTMASTER_ULLO_CLIENT_ID;
$warehouseId = SPORTMASTER_ULLO_WAREHOUSE_ID;
$orderSportmasterClass = new \Classes\Sportmaster\v1\Order($clientId, $warehouseId);
$orders = $orderSportmasterClass->shipmentsList(ORDER_STATUSES);

$orderMSClass = new OrdersMS();
$ordersMS = array();
$transformationClasses = array();

foreach ($orders as $order) {
    $transformationClass = new \Sportmaster\Order\OrderTransformation($order);
    $orderMS = $orderMSClass->findOrders('name=' . $order['orderNumber']);
    if(count($orderMS) > 0) {
        $cancelledOrderMS = $transformationClass->transformSportmasterToMSCancelled($orderMS[0]);
    } else {
        $log->write(__LINE__ . ' '. __FUNCTION__ . ' Order not found in MS: ' . $order['orderNumber']);
        continue;
    }
    $ordersMS[] = $cancelledOrderMS;
}
if (count($ordersMS) > 0) {
    $result = $orderMSClass->createCustomerorder($ordersMS);
    if ($result) {
        $log->write(__LINE__ . ' '. __FUNCTION__ . ' Successfully cancelled ' . count($ordersMS) . ' orders in MS');
        echo count($ordersMS) . ' orders cancelled successfully<br/>';
    } else {
        $log->write(__LINE__ . ' '. __FUNCTION__ . ' Failed to cancel orders in MS');
        echo 'Failed to cancel orders<br/>';
    }
} else {
    $log->write(__LINE__ . ' '. __FUNCTION__ . ' No orders to process');
    echo 'No orders to process<br/>';
}
