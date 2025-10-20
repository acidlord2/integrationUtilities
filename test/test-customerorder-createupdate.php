<?php
/**
 * Test file for CustomerorderApi createupdate functionality with real products
 * 
 * @author Georgy Polyan <acidlord@yandex.ru>
 */

// Increase memory limit for testing
ini_set('memory_limit', '512M');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once(dirname(__DIR__, 1) . '/docker-config.php');
require_once(dirname(__DIR__, 1) . '/classes/MS/v2/CustomerorderApi.php');
require_once(dirname(__DIR__, 1) . '/classes/MS/v2/Customerorder.php');
require_once(dirname(__DIR__, 1) . '/classes/MS/v2/CustomerorderIterator.php');

use MS\v2\CustomerorderApi;
use MS\v2\Customerorder;
use MS\v2\CustomerorderIterator;

class CustomerorderCreateUpdateTest
{
    private $api;
    private $testResults = [];
    private $createdOrders = []; // Store created orders for update tests
    
    public function __construct()
    {
        $this->api = new CustomerorderApi();
        echo "=== CustomerorderApi CreateUpdate Test Suite ===\n";
        echo "Initialized CustomerorderApi instance\n\n";
    }
    
    /**
     * Run all createupdate tests
     */
    public function runAllTests()
    {
        echo "🔍 Starting createupdate tests for CustomerorderApi\n";
        echo str_repeat("=", 60) . "\n\n";
        
        // CreateUpdate function tests
        $this->testCreateOrderApplicableFalse();
        $this->testUpdateExistingOrderApplicableFalse();
        
        // Print summary
        $this->printTestSummary();
    }
    
    /**
     * Test: Create 3 new orders with applicable = false using real products
     */
    private function testCreateOrderApplicableFalse()
    {
        echo "📋 Test 1: Create 3 new orders with applicable = false, real products, positions and attributes\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            // Real products from your MoySklad system (currently existing)
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
            
            // Create 3 test orders with positions and attributes using real products
            for ($i = 1; $i <= 3; $i++) {
                $uniqueId = date('YmdHis') . '_' . uniqid() . '_' . $i;
                
                // Select real products for this order
                $product1 = $realProducts[($i - 1) % count($realProducts)];
                $product2 = $realProducts[($i + 1) % count($realProducts)];
                
                // Create test positions with real products
                $positions = [
                    [
                        'quantity' => 2 + $i,
                        'price' => $product1['price'], // Use real product price
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
                        'price' => $product2['price'], // Use real product price
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
                
                // Create test attributes
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
                            'name' => 'Курьер 0' // Add name field for custom entity value
                        ]
                    ],
                    [
                        'meta' => [
                            'href' => MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/customerorder/metadata/attributes/' . MS_FIO_ATTR,
                            'type' => 'attributemetadata',
                            'mediaType' => 'application/json'
                        ],
                        'value' => "Test Customer " . $i
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
                        'value' => "Test Address " . $i . ", Test City, Test Region"
                    ]
                ];
                
                // Calculate order sum based on real product prices
                $position1Sum = $product1['price'] * (2 + $i) * (100 - (5 * $i)) / 100; // With discount
                $position2Sum = $product2['price'] * 1; // No discount
                $totalSum = $position1Sum + $position2Sum;
                
                // Create test order data
                $testOrderData = [
                    'name' => 'BULK_TEST_ORDER_' . $uniqueId,
                    'externalCode' => 'BULK_EXT_' . $uniqueId,
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
                
                echo "  📦 Creating test order $i:\n";
                echo "      Name: " . $testOrderData['name'] . "\n";
                echo "      External code: " . $testOrderData['externalCode'] . "\n";
                echo "      Applicable: " . ($testOrderData['applicable'] ? 'true' : 'false') . "\n";
                echo "      Product 1: " . $product1['name'] . " (x" . (2 + $i) . ")\n";
                echo "      Product 2: " . $product2['name'] . " (x1)\n";
                echo "      Positions: " . count($positions) . "\n";
                echo "      Attributes: " . count($attributes) . "\n";
                echo "      Sum: " . number_format($totalSum / 100, 2) . " RUB\n";
                
                // Create Customerorder object
                $testOrder = new Customerorder($testOrderData);
                $testOrders[] = $testOrder;
            }
            
            // Create iterator with all orders
            $iterator = new CustomerorderIterator($testOrders);
            
            echo "\n  🔍 Creating bulk orders with applicable = false...\n";
            
            // Test createupdate with bulk data
            $result = $this->api->createupdate($iterator);
            
            if ($result === false) {
                $this->logTest("Create bulk orders applicable=false", "FAILED", "CreateUpdate returned false");
                return;
            }
            
            if (!($result instanceof CustomerorderIterator)) {
                $this->logTest("Create bulk orders applicable=false", "FAILED", "Result is not CustomerorderIterator");
                return;
            }
            
            $createdOrders = $result->getCustomerorders();
            if (empty($createdOrders)) {
                $this->logTest("Create bulk orders applicable=false", "FAILED", "No orders in result");
                return;
            }
            
            // Store created orders for update test
            $this->createdOrders = $createdOrders;
            
            echo "  ✅ Bulk orders created successfully\n";
            echo "  📊 Created " . count($createdOrders) . " orders\n\n";
            
            $allApplicableFalse = true;
            foreach ($createdOrders as $index => $createdOrder) {
                $orderNum = $index + 1;
                echo "  📦 Order $orderNum:\n";
                echo "      ID: " . $createdOrder->getId() . "\n";
                echo "      Name: " . $createdOrder->getName() . "\n";
                echo "      Applicable: " . ($createdOrder->getApplicable() ? 'true' : 'false') . "\n";
                echo "      Positions count: " . $createdOrder->getPositionsCount() . "\n";
                echo "      Attributes count: " . $createdOrder->getAttributesCount() . "\n";
                echo "      Sum: " . number_format($createdOrder->getSum() / 100, 2) . " RUB\n";
                
                if ($createdOrder->getApplicable() !== false) {
                    $allApplicableFalse = false;
                }
            }
            
            // Verify all orders have applicable = false
            if ($allApplicableFalse) {
                $this->logTest("Create bulk orders applicable=false", "PASSED", "Created " . count($createdOrders) . " orders with applicable=false, real products, positions and attributes");
            } else {
                $this->logTest("Create bulk orders applicable=false", "WARNING", "Orders created but not all have applicable=false");
            }
            
        } catch (Exception $e) {
            $this->logTest("Create bulk orders applicable=false", "ERROR", $e->getMessage());
            echo "  🚨 Exception: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Test: Update created orders to set applicable = true
     */
    private function testUpdateExistingOrderApplicableFalse()
    {
        echo "📋 Test 2: Update created orders to set applicable = true\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            // Check if we have created orders to update
            if (empty($this->createdOrders)) {
                $this->logTest("Update created orders applicable=true", "SKIPPED", "No orders were created in previous test");
                echo "  ⏭️ Skipped: No orders were created in previous test\n\n";
                return;
            }
            
            echo "  📦 Found " . count($this->createdOrders) . " created orders to update\n";
            
            // Take the first created order for update test
            $orderToUpdate = $this->createdOrders[0];
            
            echo "  📦 Updating order: " . $orderToUpdate->getName() . "\n";
            echo "  📦 Current applicable: " . ($orderToUpdate->getApplicable() ? 'true' : 'false') . "\n";
            echo "  📦 Order ID: " . $orderToUpdate->getId() . "\n";
            
            // Create updated order data - change applicable from false to true
            $updatedOrderData = [
                'id' => $orderToUpdate->getId(),
                'meta' => $orderToUpdate->getMeta(),
                'name' => $orderToUpdate->getName() . '_UPDATED_' . date('His'),
                'externalCode' => $orderToUpdate->getExternalCode() . '_UPD',
                'applicable' => true,  // Change from false to true
                'moment' => $orderToUpdate->getMoment(),
                'organization' => $orderToUpdate->getOrganization(),
                'agent' => $orderToUpdate->getAgent(),
                'state' => $orderToUpdate->getState()
            ];
            
            // Create Customerorder object for update
            $updatedOrder = new Customerorder($updatedOrderData);
            
            // Create iterator with single order
            $orderArray = [$updatedOrder];
            $iterator = new CustomerorderIterator($orderArray);
            
            echo "  🔄 Updating order to set applicable = true...\n";
            
            // Test createupdate (should update existing order)
            $result = $this->api->createupdate($iterator);
            
            if ($result === false) {
                $this->logTest("Update created orders applicable=true", "FAILED", "CreateUpdate returned false");
                return;
            }
            
            if (!($result instanceof CustomerorderIterator)) {
                $this->logTest("Update created orders applicable=true", "FAILED", "Result is not CustomerorderIterator");
                return;
            }
            
            $updatedOrders = $result->getCustomerorders();
            if (empty($updatedOrders)) {
                $this->logTest("Update created orders applicable=true", "FAILED", "No orders in result");
                return;
            }
            
            $resultOrder = $updatedOrders[0];
            
            echo "  ✅ Order updated successfully\n";
            echo "  📦 Updated order ID: " . $resultOrder->getId() . "\n";
            echo "  📦 Updated order name: " . $resultOrder->getName() . "\n";
            echo "  📦 Updated order applicable: " . ($resultOrder->getApplicable() ? 'true' : 'false') . "\n";
            
            // Verify applicable is now true
            if ($resultOrder->getApplicable() === true) {
                $this->logTest("Update created orders applicable=true", "PASSED", "Order updated with applicable=true");
            } else {
                $this->logTest("Update created orders applicable=true", "WARNING", "Order updated but applicable is not true (got: " . var_export($resultOrder->getApplicable(), true) . ")");
            }
            
        } catch (Exception $e) {
            $this->logTest("Update created orders applicable=true", "ERROR", $e->getMessage());
            echo "  🚨 Exception: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * Log test result
     */
    private function logTest($testName, $status, $message)
    {
        $this->testResults[] = [
            'test' => $testName,
            'status' => $status,
            'message' => $message
        ];
        
        $statusEmoji = [
            'PASSED' => '✅',
            'FAILED' => '❌',
            'ERROR' => '🚨',
            'WARNING' => '⚠️',
            'INFO' => 'ℹ️',
            'SKIPPED' => '⏭️'
        ];
        
        $emoji = $statusEmoji[$status] ?? '❓';
        echo "  $emoji [$status] $testName: $message\n";
    }
    
    /**
     * Print test summary
     */
    private function printTestSummary()
    {
        echo str_repeat("=", 60) . "\n";
        echo "📊 TEST SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        
        $statusCounts = [];
        foreach ($this->testResults as $result) {
            $status = $result['status'];
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }
        
        $totalTests = count($this->testResults);
        echo "Total tests run: $totalTests\n";
        
        foreach ($statusCounts as $status => $count) {
            $statusEmoji = [
                'PASSED' => '✅',
                'FAILED' => '❌',
                'ERROR' => '🚨',
                'WARNING' => '⚠️',
                'INFO' => 'ℹ️',
                'SKIPPED' => '⏭️'
            ];
            
            $emoji = $statusEmoji[$status] ?? '❓';
            echo "$emoji $status: $count\n";
        }
        
        $passedTests = $statusCounts['PASSED'] ?? 0;
        $successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100) : 0;
        echo "\nSuccess Rate: {$successRate}%\n";
        
        if ($passedTests === $totalTests) {
            echo "\n🎉 All tests passed!\n";
        } elseif ($passedTests > 0) {
            echo "\n🎯 Some tests passed - check details above\n";
        } else {
            echo "\n⚠️ No tests passed - please review failures\n";
        }
        
        echo str_repeat("=", 60) . "\n";
    }
}

// Run the tests
try {
    $tester = new CustomerorderCreateUpdateTest();
    $tester->runAllTests();
    
} catch (Exception $e) {
    echo "🚨 Fatal error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>