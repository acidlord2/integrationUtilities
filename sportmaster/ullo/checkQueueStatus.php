<?php
/**
 * Queue Status Check API
 * 
 * Returns the current status of a queue transaction
 * Usage: POST /sportmaster/ullo/checkQueueStatus.php
 * Body: {"transactionId": "your_transaction_id"}
 * 
 * @author Integration Helper
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Queue/Queue.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

use Queue\Queue;

$logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
$logName .= '.log';
$log = new \Classes\Common\Log($logName);

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['transactionId']) || empty($input['transactionId'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Transaction ID is required'
        ]);
        exit;
    }
    
    $transactionId = $input['transactionId'];
    $log->write(__LINE__ . ' ' . __METHOD__ . ' Checking status for transaction: ' . $transactionId);
    
    // Initialize Queue
    $queue = new Queue();
    
    // Find queue items by transaction ID
    $queueItems = $queue->findByTransactionId($transactionId);
    
    if (empty($queueItems)) {
        // No queue items found - either never existed or already processed
        $log->write(__LINE__ . ' ' . __METHOD__ . ' No queue items found for transaction: ' . $transactionId);
        
        echo json_encode([
            'status' => 'completed',
            'message' => 'Transaction completed successfully',
            'transactionId' => $transactionId,
            'pendingTasks' => 0,
            'totalTasks' => 0,
            'progress' => 100,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Count total tasks and pending tasks
    $totalTasks = count($queueItems);
    $pendingTasks = $totalTasks; // All found items are still pending
    $completedTasks = 0;
    
    // Calculate progress
    $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;
    
    $log->write(__LINE__ . ' ' . __METHOD__ . ' Transaction status: ' . $pendingTasks . ' pending, ' . $completedTasks . ' completed');
    
    // Get details of first queue item for additional info
    $firstItem = $queueItems[0];
    $payload = $firstItem['payload'];
    $ordersCount = isset($payload['body']) && is_array($payload['body']) ? count($payload['body']) : 0;
    
    echo json_encode([
        'status' => 'processing',
        'message' => 'Transaction is being processed',
        'transactionId' => $transactionId,
        'pendingTasks' => $pendingTasks,
        'totalTasks' => $totalTasks,
        'completedTasks' => $completedTasks,
        'progress' => $progress,
        'ordersPerTask' => $ordersCount,
        'estimatedTimeRemaining' => $pendingTasks * 2, // Estimate 2 seconds per task
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    $log->write(__LINE__ . ' ' . __METHOD__ . ' Error: ' . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to check queue status: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>