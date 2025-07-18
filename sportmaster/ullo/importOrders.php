<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/Order-v1.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sportmaster/order.php');

define('ORDER_STATUSES', ['FOR_PICKING']);

$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
$logName .= '.log';
$log = new \Classes\Common\Log($logName);

$clientId = SPORTMASTER_ULLO_CLIENT_ID;
$warehouseId = SPORTMASTER_ULLO_WAREHOUSE_ID;
$orderSportmasterClass = new \Classes\Sportmaster\v1\Order($clientId, $warehouseId);
$orders = $orderSportmasterClass->shipmentsList(ORDER_STATUSES);
$ordersMS = array();
$transformationClasses = array();
// If you want to use MS Products class, uncomment the following lines
foreach ($orders as $order) {
    $transformationClass = new \Sportmaster\Order\OrderTransformation($order);
    $transformationClasses[] = $transformationClass;
    $orderMS = $transformationClass->transformSportmasterToMS();
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
        echo count($ordersMS) . ' orders created successfully<br/>';
    } else {
        $log->write(__LINE__ . ' '. __FUNCTION__ . ' Failed to create orders in MS');
        echo 'Failed to create orders<br/>';
    }
} else {
    $log->write(__LINE__ . ' '. __FUNCTION__ . ' No orders to process');
    echo 'No orders to process<br/>';
}

$ordersMS = array();
$packages = array();
foreach($transformationClasses as $transformationClass) {
    $packages = $transformationClass->transformToPackageChangeRequest();
    if ($packages) {
        $sportMasterOrder = $transformationClass->getSportmasterOrder();
        $response = $orderSportmasterClass->shipmentChangePackages($sportMasterOrder['id'], $packages);
    } else {
        $log->write(__LINE__ . ' '. __FUNCTION__ . ' Failed to transform order to package: ' . json_encode($transformationClass, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        continue;
    }
    if(!$response) {
        $log->write(__LINE__ . ' '. __FUNCTION__ . ' Failed to change packages for order: ' . $sportMasterOrder['id']);
        echo 'Failed to change packages for order: ' . $sportMasterOrder['id'] . '<br/>';
    }
    $response = $orderSportmasterClass->shipmentGetLabel($sportMasterOrder['id']);
    if ($response && isset($response['fileName']) && $response['fileName'] != null) {
        $log->write(__LINE__ . ' '. __FUNCTION__ . ' Successfully fetched label for order: ' . $sportMasterOrder['id']);
        $msOrderId = $orderMSClass->findOrders('name=' . $sportMasterOrder['orderNumber'])[0]['id'];
        $orderSportmasterBarcode = $orderSportmasterClass->shipmentGet($sportMasterOrder['id'])['packages'][0]['barcode'];
        $orderMS = $transformationClass->addLabelToMsOrder($response, $msOrderId, $orderSportmasterBarcode);
        $ordersMS[] = $orderMS;
        // Save the label file
    } else {
        $log->write(__LINE__ . ' '. __FUNCTION__ . ' Failed to get label for order: ' . $sportMasterOrder['id']);
    }
}
if (count($ordersMS) > 0) {
    $result = $orderMSClass->createCustomerorder($ordersMS);
    if ($result) {
        $log->write(__LINE__ . ' '. __FUNCTION__ . ' Successfully updated ' . count($ordersMS) . ' orders in MS');
    } else {
        $log->write(__LINE__ . ' '. __FUNCTION__ . ' Failed to update orders in MS');
    }
} else {
    $log->write(__LINE__ . ' '. __FUNCTION__ . ' No orders to update');
}
