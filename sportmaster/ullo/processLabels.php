<?php
/**
 * Process Sportmaster Labels - Separate Process
 * 
 * This script finds MoySklad orders from Sportmaster/Ullo without labels
 * and processes package changes and label fetching for them.
 * 
 * Algorithm:
 * 1. Find all MS orders from agent=sportmaster and organization=ullo with MS_BARCODE2_ATTR empty
 * 2. For each order implement the same label processing code
 * 
 * @author Integration Helper
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/CustomerorderApi.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/FilterBuilder.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/sportmaster/classes/Order.php');

use Classes\Common\Log;
use MS\v2\CustomerorderApi;
use MS\v2\FilterBuilder;
use Sportmaster\Order as OrderSportmaster;

$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
$logName .= '.log';
$log = new Log($logName);

$log->write(__LINE__ . ' ' . __METHOD__ . ' Starting label processing for Sportmaster orders');

try {
    // Initialize APIs
    $customerorderApi = new CustomerorderApi(false); // Direct mode, no queue
    $orderSportmasterClass = new OrderSportmaster();
    
    // Step 1: Find all MS orders from agent=sportmaster and organization=ullo
    $log->write(__LINE__ . ' ' . __METHOD__ . ' Searching for Sportmaster orders');
    
    $filters = [
        'agent' => MS_AGENT_SPORTMASTER,
        'organization' => MS_ORGANIZATION_ULLO
    ];
    
    $ordersResult = $customerorderApi->search($filters, 1000, 0);
    
    if ($ordersResult === false || $ordersResult->isEmpty()) {
        $log->write(__LINE__ . ' ' . __METHOD__ . ' No Sportmaster orders found');
        echo json_encode([
            'status' => 'no_orders',
            'message' => 'No Sportmaster orders found'
        ]);
        exit;
    }
    
    // Filter orders without labels (check MS_BARCODE2_ATTR attribute)
    $ordersWithoutLabels = [];
    $orders = $ordersResult->getCustomerorders();
    
    foreach ($orders as $order) {
        $hasLabel = false;
        $attributes = $order->getAttributes();
        
        if ($attributes) {
            foreach ($attributes as $attribute) {
                if ($attribute->getId() === MS_BARCODE2_ATTR && !empty($attribute->getValue())) {
                    $hasLabel = true;
                    break;
                }
            }
        }
        
        if (!$hasLabel) {
            $ordersWithoutLabels[] = $order;
        }
    }
    
    if (empty($ordersWithoutLabels)) {
        $log->write(__LINE__ . ' ' . __METHOD__ . ' No orders found without labels');
        echo json_encode([
            'status' => 'no_orders',
            'message' => 'No orders found that need label processing'
        ]);
        exit;
    }
    
    $log->write(__LINE__ . ' ' . __METHOD__ . ' Found ' . count($ordersWithoutLabels) . ' orders without labels out of ' . count($orders) . ' total orders');
    
    $processedCount = 0;
    $errorCount = 0;
    
    // Step 2: Process each order without labels
    foreach ($ordersWithoutLabels as $order) {
        $orderName = $order->getName();
        $log->write(__LINE__ . ' ' . __METHOD__ . ' Processing order: ' . $orderName);
        
        try {
            // Get Sportmaster order ID from the order name (assuming it contains the Sportmaster order number)
            $sportmasterOrderId = null;
            
            // Try to extract Sportmaster order ID from order name or external code
            $externalCode = $order->getExternalCode();
            if ($externalCode) {
                $sportmasterOrderId = $externalCode;
            } else {
                // Try to extract from order name if it follows a pattern
                if (preg_match('/(\d+)/', $orderName, $matches)) {
                    $sportmasterOrderId = $matches[1];
                }
            }
            
            if (!$sportmasterOrderId) {
                $log->write(__LINE__ . ' ' . __METHOD__ . ' Could not determine Sportmaster order ID for: ' . $orderName);
                $errorCount++;
                continue;
            }
            
            $log->write(__LINE__ . ' ' . __METHOD__ . ' Processing Sportmaster order ID: ' . $sportmasterOrderId);
            
            // Get Sportmaster order details
            $sportmasterOrder = $orderSportmasterClass->shipmentGet($sportmasterOrderId);
            if (!$sportmasterOrder) {
                $log->write(__LINE__ . ' ' . __METHOD__ . ' Could not fetch Sportmaster order: ' . $sportmasterOrderId);
                $errorCount++;
                continue;
            }
            
            // Load transformation class (we'll need to create a simpler version for this context)
            require_once($_SERVER['DOCUMENT_ROOT'] . '/sportmaster/ullo/classes/OrderTransformation.php');
            
            $transformationClass = new \Sportmaster\Ullo\OrderTransformation($sportmasterOrder, $order);
            
            // Process package changes
            $packages = $transformationClass->transformToPackageChangeRequest();
            if ($packages) {
                $response = $orderSportmasterClass->shipmentChangePackages($sportmasterOrderId, $packages);
                
                if (!$response) {
                    $log->write(__LINE__ . ' ' . __METHOD__ . ' Failed to change packages for order: ' . $sportmasterOrderId);
                    $errorCount++;
                    continue;
                }
                
                $log->write(__LINE__ . ' ' . __METHOD__ . ' Successfully changed packages for order: ' . $sportmasterOrderId);
                
                // Get label for the order
                $labelResponse = $orderSportmasterClass->shipmentGetLabel($sportmasterOrderId);
                if ($labelResponse && isset($labelResponse['fileName']) && $labelResponse['fileName'] != null) {
                    $log->write(__LINE__ . ' ' . __METHOD__ . ' Successfully fetched label for order: ' . $sportmasterOrderId);
                    
                    // Get barcode from the updated Sportmaster order
                    $updatedSportmasterOrder = $orderSportmasterClass->shipmentGet($sportmasterOrderId);
                    $orderSportmasterBarcode = null;
                    
                    if ($updatedSportmasterOrder && isset($updatedSportmasterOrder['packages'][0]['barcode'])) {
                        $orderSportmasterBarcode = $updatedSportmasterOrder['packages'][0]['barcode'];
                    }
                    
                    // Update MS order with label information
                    $updatedOrderData = $transformationClass->addLabelToMsOrder($labelResponse, $order->getId(), $orderSportmasterBarcode);
                    
                    if ($updatedOrderData) {
                        // Create updated order object
                        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/Customerorder.php');
                        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/v2/CustomerorderIterator.php');
                        
                        $updatedOrder = new \MS\v2\Customerorder($updatedOrderData);
                        $orderIterator = new \MS\v2\CustomerorderIterator([$updatedOrder]);
                        
                        // Update the order directly (no queue)
                        $updateResult = $customerorderApi->createupdate($orderIterator);
                        
                        if ($updateResult !== false) {
                            $log->write(__LINE__ . ' ' . __METHOD__ . ' Successfully updated MS order with label: ' . $orderName);
                            $processedCount++;
                        } else {
                            $log->write(__LINE__ . ' ' . __METHOD__ . ' Failed to update MS order with label: ' . $orderName);
                            $errorCount++;
                        }
                    } else {
                        $log->write(__LINE__ . ' ' . __METHOD__ . ' Failed to prepare label data for MS order: ' . $orderName);
                        $errorCount++;
                    }
                } else {
                    $log->write(__LINE__ . ' ' . __METHOD__ . ' Failed to get label for order: ' . $sportmasterOrderId);
                    $errorCount++;
                }
            } else {
                $log->write(__LINE__ . ' ' . __METHOD__ . ' Failed to transform order to package: ' . $sportmasterOrderId);
                $errorCount++;
            }
            
        } catch (Exception $e) {
            $log->write(__LINE__ . ' ' . __METHOD__ . ' Error processing order ' . $orderName . ': ' . $e->getMessage());
            $errorCount++;
        }
    }
    
    // Log final results
    $log->write(__LINE__ . ' ' . __METHOD__ . ' Label processing completed. Processed: ' . $processedCount . ', Errors: ' . $errorCount);
    
    echo json_encode([
        'status' => 'completed',
        'message' => 'Label processing completed',
        'totalOrdersFound' => count($orders),
        'ordersWithoutLabels' => count($ordersWithoutLabels),
        'processedCount' => $processedCount,
        'errorCount' => $errorCount,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    $log->write(__LINE__ . ' ' . __METHOD__ . ' Fatal error: ' . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Fatal error during label processing: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>