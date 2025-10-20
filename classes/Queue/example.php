<?php
/**
 * Example usage of the Queue class
 */

// Set document root for CLI usage
if (!isset($_SERVER['DOCUMENT_ROOT']) || empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Queue/Queue.php');

// Create an instance of the Queue class
$queue = new \Queue\Queue();

// Generate a transaction ID
$transactionId = \Queue\Queue::generateTransactionId();

echo "=== Queue Class Example ===\n\n";
echo "Testing Queue class functionality...\n";

// Test basic operations
$payload = ['test' => 'data', 'timestamp' => time()];
$id = $queue->create($transactionId, $payload);
echo "✅ Created queue item with ID: $id\n";

$item = $queue->read($id);
echo "✅ Read queue item successfully\n";

$count = $queue->count();
echo "✅ Total queue items: $count\n";

$queue->delete($id);
echo "✅ Deleted test item\n";

echo "\n=== Example completed ===\n";
?>