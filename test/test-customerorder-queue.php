<?php
/**
 * Test CustomerorderApi Queue Integration
 * 
 * Tests the updated queue functionality with proper transaction ID handling
 * and correct payload structure matching the Sportmaster API pattern.
 */

require_once(__DIR__ . '/../docker-config.php');
require_once(__DIR__ . '/../classes/MS/v2/CustomerorderApi.php');
require_once(__DIR__ . '/../classes/MS/v2/CustomerorderIterator.php');
require_once(__DIR__ . '/../classes/MS/v2/Customerorder.php');
require_once(__DIR__ . '/../classes/Queue/Queue.php');

use MS\v2\CustomerorderApi;
use MS\v2\CustomerorderIterator;
use MS\v2\Customerorder;
use Queue\Queue;

echo "=== CustomerorderApi Queue Integration Test ===\n\n";

try {
    // Test 1: Constructor without transaction ID should fail when addToQueue is true
    echo "1. Testing constructor validation...\n";
    try {
        $api = new CustomerorderApi(true); // addToQueue=true, transactionId=null - should fail
        echo "ERROR: Constructor should have thrown an exception!\n";
    } catch (InvalidArgumentException $e) {
        echo "✓ Constructor correctly validates transaction ID: " . $e->getMessage() . "\n";
    }
    
    // Test 2: Generate proper transaction ID
    echo "\n2. Generating transaction ID...\n";
    $transactionId = 'txn_' . uniqid() . '_' . time();
    echo "✓ Generated transaction ID: " . $transactionId . "\n";
    
    // Test 3: Constructor with transaction ID
    echo "\n3. Testing constructor with transaction ID...\n";
    $api = new CustomerorderApi(true, $transactionId);
    echo "✓ Constructor accepts transaction ID: " . $transactionId . "\n";
    
    // Test 4: Create test orders for queueing with real products, positions and attributes
    echo "\n4. Creating test orders for queue with real products...\n";
    
    // Real products from MoySklad system (same as createupdate test)
    $realProducts = [
        [
            'id' => '0077dc4a-d5b7-11eb-0a80-046e000dbf42',
            'name' => 'Vivienne Sabo Гель для бровей и ресниц фиксирующий Fixateur 01, коричневый, 6 мл',
            'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/product/0077dc4a-d5b7-11eb-0a80-046e000dbf42',
            'price' => 12900 // 129.00 RUB
        ],
        [
            'id' => '0079ad2f-bba6-11ef-0a80-1154003325d6',
            'name' => 'Ayoume Маска на тканевой основе для лица от мешков под глазами «Проспала на работу», 23 мл',
            'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/product/0079ad2f-bba6-11ef-0a80-1154003325d6',
            'price' => 2800 // 28.00 RUB
        ]
    ];
    
    $testOrders = [];
    for ($i = 1; $i <= 3; $i++) {
        $uniqueId = date('YmdHis') . '_' . uniqid() . '_' . $i;
        
        // Select real products for this order
        $product1 = $realProducts[($i - 1) % count($realProducts)];
        $product2 = $realProducts[($i + 1) % count($realProducts)];
        
        // Create positions with real products
        $positions = [
            [
                'quantity' => 2 + $i,
                'price' => $product1['price'],
                'discount' => 5 * $i, // 5%, 10%, 15%
                'vat' => 20,
                'vatEnabled' => true,
                'reserve' => 2 + $i, // Reserve equals quantity
                'assortment' => [
                    'meta' => [
                        'href' => $product1['href'],
                        'type' => 'product',
                        'mediaType' => 'application/json'
                    ]
                ]
            ],
            [
                'quantity' => 1,
                'price' => $product2['price'],
                'discount' => 0,
                'vat' => 20,
                'vatEnabled' => true,
                'reserve' => 1, // Reserve equals quantity
                'assortment' => [
                    'meta' => [
                        'href' => $product2['href'],
                        'type' => 'product',
                        'mediaType' => 'application/json'
                    ]
                ]
            ]
        ];
        
        // Create attributes (same as createupdate test)
        $attributes = [
            [
                'meta' => [
                    'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/customerorder/metadata/attributes/' . MS_SHIPTYPE_ATTR_ID,
                    'type' => 'attributemetadata',
                    'mediaType' => 'application/json'
                ],
                'value' => [
                    'meta' => [
                        'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/customerorder/metadata/attributes/' . MS_SHIPTYPE_ATTR_ID . '/customentityvalues/' . MS_SHIPTYPE_CURIER0_ID,
                        'type' => 'customentityvalue',
                        'mediaType' => 'application/json'
                    ],
                    'name' => 'Курьер 0'
                ]
            ],
            [
                'meta' => [
                    'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/customerorder/metadata/attributes/' . MS_FIO_ATTR,
                    'type' => 'attributemetadata',
                    'mediaType' => 'application/json'
                ],
                'value' => "Queue Test Customer " . $i
            ],
            [
                'meta' => [
                    'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/customerorder/metadata/attributes/' . MS_PHONE_ATTR,
                    'type' => 'attributemetadata',
                    'mediaType' => 'application/json'
                ],
                'value' => "+7999000000" . $i
            ],
            [
                'meta' => [
                    'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/customerorder/metadata/attributes/' . MS_ADDRESS_ATTR,
                    'type' => 'attributemetadata',
                    'mediaType' => 'application/json'
                ],
                'value' => "Queue Test Address " . $i . ", Test City, Test Region"
            ]
        ];
        
        // Calculate order sum based on real product prices
        $position1Sum = $product1['price'] * (2 + $i) * (100 - (5 * $i)) / 100; // With discount
        $position2Sum = $product2['price'] * 1; // No discount
        $totalSum = $position1Sum + $position2Sum;
        
        $orderData = [
            'name' => 'QUEUE_TEST_ORDER_' . $uniqueId,
            'externalCode' => 'QUEUE_EXT_' . $uniqueId,
            'applicable' => false,
            'moment' => date('Y-m-d H:i:s'),
            'sum' => $totalSum,
            'vatEnabled' => true,
            'vatIncluded' => true,
            'organization' => [
                'meta' => [
                    'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/organization/cb72811a-5fac-11ea-0a80-01a1000989c6',
                    'type' => 'organization',
                    'mediaType' => 'application/json'
                ]
            ],
            'agent' => [
                'meta' => [
                    'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/counterparty/b05fbd35-dd08-11e8-9107-5048001507ff',
                    'type' => 'counterparty', 
                    'mediaType' => 'application/json'
                ]
            ],
            'state' => [
                'meta' => [
                    'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/customerorder/metadata/states/d75a2136-edd0-11e8-9ff4-34e8000d3e7b',
                    'type' => 'state',
                    'mediaType' => 'application/json'
                ]
            ],
            'positions' => $positions,
            'attributes' => $attributes
        ];
        
        $testOrders[] = new Customerorder($orderData);
        echo "✓ Created test order: " . $orderData['name'] . " (" . $orderData['externalCode'] . ")\n";
        echo "    - Product 1: " . $product1['name'] . " (x" . (2 + $i) . ")\n";
        echo "    - Product 2: " . $product2['name'] . " (x1)\n";
        echo "    - Positions: " . count($positions) . ", Attributes: " . count($attributes) . "\n";
        echo "    - Sum: " . number_format($totalSum / 100, 2) . " RUB\n";
    }
    
    // Test 5: Queue the orders
    echo "\n5. Testing queue functionality...\n";
    $iterator = new CustomerorderIterator($testOrders);
    
    // Use fresh transaction ID for this test
    $queueTransactionId = 'queue_test_' . uniqid() . '_' . time();
    $queueApi = new CustomerorderApi(true, $queueTransactionId);
    
    echo "✓ Queue API created with transaction ID: " . $queueTransactionId . "\n";
    echo "✓ Processing " . $iterator->count() . " orders through queue...\n";
    
    // This should add orders to queue instead of making API calls
    $result = $queueApi->createupdate($iterator);
    
    if ($result !== false) {
        echo "✓ Orders successfully queued!\n";
        echo "✓ Result iterator contains " . $result->count() . " placeholder results\n";
        
        // Show placeholder results
        foreach ($result->getCustomerorders() as $index => $order) {
            if (is_array($order) && isset($order['queued']) && $order['queued']) {
                echo "  - Placeholder " . ($index + 1) . ": ID=" . $order['id'] . 
                     ", QueueID=" . $order['queueId'] . 
                     ", Chunk=" . $order['chunkNumber'] . "\n";
            }
        }
    } else {
        echo "ERROR: Failed to queue orders\n";
    }
    
    // Test 6: Verify queue entries
    echo "\n6. Verifying queue entries...\n";
    $queue = new Queue();
    $items = $queue->findByTransactionId($queueTransactionId);
    
    if ($items && !empty($items)) {
        echo "✓ Found " . count($items) . " items in queue for transaction ID\n";
        
        foreach ($items as $item) {
            $payload = $item['payload'];
            
            // Handle payload - it should already be decoded by Queue class
            if ($payload === null) {
                echo "  - Queue ID: " . $item['id'] . " - ERROR: Failed to decode payload\n";
            } else {
                echo "  - Queue ID: " . $item['id'] . 
                     ", API: " . ($payload['api'] ?? 'N/A') . 
                     ", Method: " . ($payload['method'] ?? 'N/A') . 
                     ", Orders: " . (isset($payload['body']) && is_array($payload['body']) ? count($payload['body']) : 0) . "\n";
            }
        }
    } else {
        echo "WARNING: No queue items found for transaction ID\n";
    }
    
    echo "\n=== Test completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>