<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/Order-v1.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/CustomerorderApi.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/CustomerorderIterator.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/Customerorder.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Queue/Queue.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sportmaster/order.php');

use MS\v2\CustomerorderApi;
use MS\v2\CustomerorderIterator;
use MS\v2\Customerorder;
use Queue\Queue;

define('ORDER_STATUSES', ['FOR_PICKING']);

$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
$logName .= '.log';
$log = new \Classes\Common\Log($logName);

$clientId = SPORTMASTER_ULLO_CLIENT_ID;
$warehouseId = SPORTMASTER_ULLO_WAREHOUSE_ID;
$orderSportmasterClass = new \Classes\Sportmaster\v1\Order($clientId, $warehouseId);
$orders = $orderSportmasterClass->shipmentsList(ORDER_STATUSES);

// Generate transaction ID for queue tracking
$transactionId = 'sportmaster_import_' . uniqid() . '_' . time();
$log->write(__LINE__ . ' '. __METHOD__ . ' Starting import with transaction ID: ' . $transactionId);

$ordersMS = array();
$transformationClasses = array();

// Transform Sportmaster orders to MS format
foreach ($orders as $order) {
    $transformationClass = new \Sportmaster\Order\OrderTransformation($order);
    $transformationClasses[] = $transformationClass;
    $orderMS = $transformationClass->transformSportmasterToMS();
    if (!$orderMS) {
        $log->write(__LINE__ . ' '. __METHOD__ . ' Failed to transform order: ' . json_encode($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        continue;
    }
    
    // Convert to Customerorder object
    $customerOrder = new Customerorder($orderMS);
    $ordersMS[] = $customerOrder;
}

if (count($ordersMS) > 0) {
    // Use new queue-based CustomerorderApi
    $orderIterator = new CustomerorderIterator($ordersMS);
    $customerorderApi = new CustomerorderApi(true, $transactionId); // Enable queue with transaction ID
    
    $result = $customerorderApi->createupdate($orderIterator);
    
    if ($result !== false) {
        $log->write(__LINE__ . ' '. __METHOD__ . ' Successfully queued ' . count($ordersMS) . ' orders for processing');
        
        // Return immediate response with transaction ID for JavaScript polling
        echo json_encode([
            'status' => 'queued',
            'transactionId' => $transactionId,
            'ordersCount' => count($ordersMS),
            'message' => count($ordersMS) . ' orders queued for processing',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } else {
        $log->write(__LINE__ . ' '. __METHOD__ . ' Failed to queue orders');
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to queue orders for processing'
        ]);
    }
} else {
    $log->write(__LINE__ . ' '. __METHOD__ . ' No orders to process');
    echo json_encode([
        'status' => 'no_orders',
        'message' => 'No orders to process'
    ]);
}

// TODO: Second part (package changes and labels) should be handled separately
// This part will be processed after the initial orders are created via queue
/*
$ordersMS = array();
$packages = array();
foreach($transformationClasses as $transformationClass) {
    $packages = $transformationClass->transformToPackageChangeRequest();
    if ($packages) {
        $sportMasterOrder = $transformationClass->getSportmasterOrder();
        $response = $orderSportmasterClass->shipmentChangePackages($sportMasterOrder['id'], $packages);
    } else {
        $log->write(__LINE__ . ' '. __METHOD__ . ' Failed to transform order to package: ' . json_encode($transformationClass, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        continue;
    }
    if(!$response) {
        $log->write(__LINE__ . ' '. __METHOD__ . ' Failed to change packages for order: ' . $sportMasterOrder['id']);
        echo 'Failed to change packages for order: ' . $sportMasterOrder['id'] . '<br/>';
    }
    $response = $orderSportmasterClass->shipmentGetLabel($sportMasterOrder['id']);
    if ($response && isset($response['fileName']) && $response['fileName'] != null) {
        $log->write(__LINE__ . ' '. __METHOD__ . ' Successfully fetched label for order: ' . $sportMasterOrder['id']);
        $msOrderId = $orderMSClass->findOrders('name=' . $sportMasterOrder['orderNumber'])[0]['id'];
        $orderSportmasterBarcode = $orderSportmasterClass->shipmentGet($sportMasterOrder['id'])['packages'][0]['barcode'];
        $orderMS = $transformationClass->addLabelToMsOrder($response, $msOrderId, $orderSportmasterBarcode);
        $ordersMS[] = $orderMS;
        // Save the label file
    } else {
        $log->write(__LINE__ . ' '. __METHOD__ . ' Failed to get label for order: ' . $sportMasterOrder['id']);
    }
}
if (count($ordersMS) > 0) {
    $result = $orderMSClass->createCustomerorder($ordersMS);
    if ($result) {
        $log->write(__LINE__ . ' '. __METHOD__ . ' Successfully updated ' . count($ordersMS) . ' orders in MS');
    } else {
        $log->write(__LINE__ . ' '. __METHOD__ . ' Failed to update orders in MS');
    }
} else {
    $log->write(__LINE__ . ' '. __METHOD__ . ' No orders to update');
}
*/
