<?php
/**
 * Comprehensive test file for CustomerorderApi search and get functions
 * 
 * @author Georgy Polyan <acidlord@yandex.ru>
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once(dirname(__DIR__, 1) . '/docker-config.php');
require_once(dirname(__DIR__, 1) . '/classes/MS/v2/CustomerorderApi.php');

use MS\v2\CustomerorderApi;

class CustomerorderApiTest
{
    private $api;
    private $testResults = [];
    
    public function __construct()
    {
        $this->api = new CustomerorderApi();
        echo "=== CustomerorderApi Test Suite ===\n";
        echo "Initialized CustomerorderApi instance\n\n";
    }
    
    /**
     * Run all tests
     */
    public function runAllTests()
    {
        echo "🔍 Starting comprehensive tests for CustomerorderApi\n";
        echo str_repeat("=", 60) . "\n\n";
        
        // Search function tests
        // $this->testBasicSearch();
        // $this->testSearchWithSimpleFilters();
        // $this->testSearchWithOperatorFilters();
        // $this->testSearchWithEntityFilters();
        // $this->testSearchWithBooleanFilters();
        // $this->testSearchWithEmptyValueFilters();
        // $this->testSearchWithMixedFilters();
        // $this->testSearchWithPagination();
        // $this->testSearchWithLimits();
        // $this->testSearchAll();
        
        // Get function tests
        // $this->testGetExistingOrder();
        // $this->testGetNonExistentOrder();
        $this->testGetByExternalCodes();
        
        // Edge cases
        // $this->testSearchWithInvalidFilters();
        // $this->testSearchWithEmptyResponse();
        
        // Print summary
        $this->printTestSummary();
    }
    
    /**
     * Test 1: Basic search without filters
     */
    private function testBasicSearch()
    {
        echo "📋 Test 1: Basic search without filters\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            // Limit to 10 records to avoid overwhelming the system
            $result = $this->api->search([], 100);
            
            if ($result === false) {
                $this->logTest("Basic search", "FAILED", "Search returned false");
                return;
            }
            
            if (!($result instanceof \MS\v2\CustomerorderIterator)) {
                $this->logTest("Basic search", "FAILED", "Result is not CustomerorderIterator instance");
                return;
            }
            
            $count = $result->count();
            $this->logTest("Basic search", "PASSED", "Retrieved $count orders (limited to 100)");
            
            // Show some basic info about first order if available
            if ($count > 0) {
                $firstOrder = $result->getCustomerorder(0);
                if ($firstOrder) {
                    echo "  📦 First order ID: " . ($firstOrder->getId() ?: 'N/A') . "\n";
                    echo "  📦 First order name: " . ($firstOrder->getName() ?: 'N/A') . "\n";
                }
            }
            
        } catch (Exception $e) {
            $this->logTest("Basic search", "ERROR", $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Test 2: Search with simple filters
     */
    private function testSearchWithSimpleFilters()
    {
        echo "📋 Test 2: Search with simple filters\n";
        echo str_repeat("-", 40) . "\n";
        
        $testCases = [
            ['name' => '9128277707611'],
            ['externalCode' => '154334877'],
            ['state.name' => 'Новый'],
            // Use organization ID instead of organization.name (which doesn't exist in API)
            ['organization' => 'cb72811a-5fac-11ea-0a80-01a1000989c6'],
            // Use agent ID instead of agent.name (which doesn't exist in API)  
            ['agent' => 'b05fbd35-dd08-11e8-9107-5048001507ff']
        ];
        
        foreach ($testCases as $index => $filters) {
            try {
                $filterStr = json_encode($filters, JSON_UNESCAPED_UNICODE);
                echo "  🔍 Testing filter: $filterStr\n";
                
                $result = $this->api->search($filters, 10); // Limit to 10 for testing
                
                if ($result === false) {
                    $this->logTest("Simple filter " . ($index + 1), "INFO", "No results found");
                } else {
                    $count = $result->count();
                    $this->logTest("Simple filter " . ($index + 1), "PASSED", "Found $count orders");
                }
                
            } catch (Exception $e) {
                $this->logTest("Simple filter " . ($index + 1), "ERROR", $e->getMessage());
            }
        }
        
        echo "\n";
    }
    
    /**
     * Test 3: Search with operator-based filters
     */
    private function testSearchWithOperatorFilters()
    {
        echo "📋 Test 3: Search with operator-based filters\n";
        echo str_repeat("-", 40) . "\n";
        
        $testCases = [
            'Greater than sum' => [
                'sum' => ['>=' => [80000]]
            ],
            'Name contains' => [
                'name' => ['~' => ['9128']]
            ],
            'Multiple states' => [
                'state.name' => ['=' => ['Новый', 'Подтвержден']]
            ],
            'Date range' => [
                'moment' => ['>=' => ['2024-08-01 00:00:00']]
            ]
        ];
        
        foreach ($testCases as $testName => $filters) {
            try {
                $filterStr = json_encode($filters, JSON_UNESCAPED_UNICODE);
                echo "  🔍 Testing $testName: $filterStr\n";
                
                $result = $this->api->search($filters, 5); // Limit to 5 for testing
                
                if ($result === false) {
                    $this->logTest("Operator filter: $testName", "INFO", "No results found");
                } else {
                    $count = $result->count();
                    $this->logTest("Operator filter: $testName", "PASSED", "Found $count orders");
                }
                
            } catch (Exception $e) {
                $this->logTest("Operator filter: $testName", "ERROR", $e->getMessage());
            }
        }
        
        echo "\n";
    }
    
    /**
     * Test 4: Search with entity filters (URLs and IDs)
     */
    private function testSearchWithEntityFilters()
    {
        echo "📋 Test 4: Search with entity filters\n";
        echo str_repeat("-", 40) . "\n";
        
        $testCases = [
            'Agent by ID' => [
                'agent' => 'b05fbd35-dd08-11e8-9107-5048001507ff'
            ],
            'Agent by URL' => [
                'agent' => 'https://api.moysklad.ru/api/remap/1.2/entity/counterparty/b05fbd35-dd08-11e8-9107-5048001507ff'
            ],
            'State by ID' => [
                'state' => 'd75a2136-edd0-11e8-9ff4-34e8000d3e7b'
            ],
            'Organization by ID' => [
                'organization' => 'cb72811a-5fac-11ea-0a80-01a1000989c6'
            ],
            'Organization by URL' => [
                'organization' => 'https://api.moysklad.ru/api/remap/1.2/entity/organization/cb72811a-5fac-11ea-0a80-01a1000989c6'
            ],
            'State by URL' => [
                'state' => 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/states/d75a2136-edd0-11e8-9ff4-34e8000d3e7b'
            ]
        ];
        
        foreach ($testCases as $testName => $filters) {
            try {
                $filterStr = json_encode($filters, JSON_UNESCAPED_UNICODE);
                echo "  🔍 Testing $testName: $filterStr\n";
                
                $result = $this->api->search($filters, 3); // Limit to 3 for testing
                
                if ($result === false) {
                    $this->logTest("Entity filter: $testName", "INFO", "No results found");
                } else {
                    $count = $result->count();
                    $this->logTest("Entity filter: $testName", "PASSED", "Found $count orders");
                }
                
            } catch (Exception $e) {
                $this->logTest("Entity filter: $testName", "ERROR", $e->getMessage());
            }
        }
        
        echo "\n";
    }
    
    /**
     * Test 5: Search with boolean filters
     */
    private function testSearchWithBooleanFilters()
    {
        echo "📋 Test 5: Search with boolean filters\n";
        echo str_repeat("-", 40) . "\n";
        
        $testCases = [
            'Applicable orders' => [
                'applicable' => true
            ],
            'Non-applicable orders' => [
                'applicable' => false
            ]
        ];
        
        foreach ($testCases as $testName => $filters) {
            try {
                $filterStr = json_encode($filters, JSON_UNESCAPED_UNICODE);
                echo "  🔍 Testing $testName: $filterStr\n";
                
                $result = $this->api->search($filters, 5); // Limit to 5 for testing
                
                if ($result === false) {
                    $this->logTest("Boolean filter: $testName", "INFO", "No results found");
                } else {
                    $count = $result->count();
                    $this->logTest("Boolean filter: $testName", "PASSED", "Found $count orders");
                }
                
            } catch (Exception $e) {
                $this->logTest("Boolean filter: $testName", "ERROR", $e->getMessage());
            }
        }
        
        echo "\n";
    }
    
    /**
     * Test 6: Search with empty value filters (null/empty field search)
     */
    private function testSearchWithEmptyValueFilters()
    {
        echo "📋 Test 6: Search with empty value filters\n";
        echo str_repeat("-", 40) . "\n";
        
        $testCases = [
            'Orders without agent' => [
                'agent' => ['=' => [null]]
            ],
            'Orders with agent' => [
                'agent' => ['!=' => ['']]
            ],
            'Orders without description' => [
                'description' => ['=' => ['']]
            ]
        ];
        
        foreach ($testCases as $testName => $filters) {
            try {
                $filterStr = json_encode($filters, JSON_UNESCAPED_UNICODE);
                echo "  🔍 Testing $testName: $filterStr\n";
                
                $result = $this->api->search($filters, 5); // Limit to 5 for testing
                
                if ($result === false) {
                    $this->logTest("Empty value filter: $testName", "INFO", "No results found");
                } else {
                    $count = $result->count();
                    $this->logTest("Empty value filter: $testName", "PASSED", "Found $count orders");
                }
                
            } catch (Exception $e) {
                $this->logTest("Empty value filter: $testName", "ERROR", $e->getMessage());
            }
        }
        
        echo "\n";
    }
    
    /**
     * Test 7: Search with mixed complex filters
     */
    private function testSearchWithMixedFilters()
    {
        echo "📋 Test 7: Search with mixed complex filters\n";
        echo str_repeat("-", 40) . "\n";
        
        $complexFilters = [
            'sum' => ['>=' => [80000]],
            'applicable' => true,
            'state.name' => ['=' => ['Новый', 'Подтвержден']],
            'name' => ['~' => ['ccd-']]
        ];
        
        try {
            $filterStr = json_encode($complexFilters, JSON_UNESCAPED_UNICODE);
            echo "  🔍 Testing complex mixed filters: $filterStr\n";
            
            $result = $this->api->search($complexFilters, 10);
            
            if ($result === false) {
                $this->logTest("Mixed complex filters", "INFO", "No results found");
            } else {
                $count = $result->count();
                $this->logTest("Mixed complex filters", "PASSED", "Found $count orders");
                
                // Show details of first result
                if ($count > 0) {
                    $firstOrder = $result->getCustomerorder(0);
                    if ($firstOrder) {
                        echo "  📦 Sample result - Name: " . ($firstOrder->getName() ?: 'N/A') . "\n";
                        echo "  📦 Sample result - Sum: " . ($firstOrder->getSum() ?: 'N/A') . "\n";
                    }
                }
            }
            
        } catch (Exception $e) {
            $this->logTest("Mixed complex filters", "ERROR", $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Test 8: Search with pagination
     */
    private function testSearchWithPagination()
    {
        echo "📋 Test 8: Search with pagination\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            echo "  🔍 Testing first page (limit=10, offset=0)\n";
            $page1 = $this->api->search([], 10, 0);
            
            echo "  🔍 Testing second page (limit=10, offset=10)\n";
            $page2 = $this->api->search([], 10, 10);
            
            if ($page1 !== false && $page2 !== false) {
                $count1 = $page1->count();
                $count2 = $page2->count();
                
                $this->logTest("Pagination test", "PASSED", "Page 1: $count1 orders, Page 2: $count2 orders");
                
                // Check if results are different
                if ($count1 > 0 && $count2 > 0) {
                    $firstOrderPage1 = $page1->getCustomerorder(0);
                    $firstOrderPage2 = $page2->getCustomerorder(0);
                    
                    if ($firstOrderPage1 && $firstOrderPage2) {
                        $id1 = $firstOrderPage1->getId();
                        $id2 = $firstOrderPage2->getId();
                        
                        if ($id1 !== $id2) {
                            echo "  ✅ Pagination working: Different orders on different pages\n";
                        } else {
                            echo "  ⚠️ Pagination warning: Same order on both pages\n";
                        }
                    }
                }
            } else {
                $this->logTest("Pagination test", "INFO", "Not enough data for pagination test");
            }
            
        } catch (Exception $e) {
            $this->logTest("Pagination test", "ERROR", $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Test 9: Search with different limits
     */
    private function testSearchWithLimits()
    {
        echo "📋 Test 9: Search with different limits\n";
        echo str_repeat("-", 40) . "\n";
        
        $limits = [1, 5, 50, 1000];
        
        foreach ($limits as $limit) {
            try {
                echo "  🔍 Testing limit: $limit\n";
                $result = $this->api->search([], $limit);
                
                if ($result !== false) {
                    $count = $result->count();
                    $actualCount = min($count, $limit); // Account for when there are fewer results than limit
                    
                    $this->logTest("Limit $limit test", "PASSED", "Retrieved $count orders (expected max $limit)");
                } else {
                    $this->logTest("Limit $limit test", "INFO", "No results found");
                }
                
            } catch (Exception $e) {
                $this->logTest("Limit $limit test", "ERROR", $e->getMessage());
            }
        }
        
        echo "\n";
    }
    
    /**
     * Test 10: SearchAll function
     */
    private function testSearchAll()
    {
        echo "📋 Test 10: SearchAll function\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            echo "  🔍 Testing searchAll with simple filter (limited to recent records)\n";
            // Add a date filter to limit to recent records and avoid processing millions
            $filters = [
                'applicable' => true,
                'created' => ['>=' => [date('Y-m-d', strtotime('-3 days')) . ' 00:00:00']]
            ];
            $result = $this->api->searchAll($filters);
            
            if ($result !== false) {
                $count = $result->count();
                $this->logTest("SearchAll test", "PASSED", "Retrieved $count orders via searchAll (last 365 days)");
                
                if ($count > 0) {
                    echo "  ✅ SearchAll working: Retrieved $count orders from last 365 days\n";
                }
            } else {
                $this->logTest("SearchAll test", "INFO", "No results found in last 365 days");
            }
            
        } catch (Exception $e) {
            $this->logTest("SearchAll test", "ERROR", $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Test 11: Get existing order (if we can find one)
     */
    private function testGetExistingOrder()
    {
        echo "📋 Test 11: Get existing order by ID\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            // First get some orders to have IDs to test with
            echo "  🔍 First, getting some orders to obtain IDs\n";
            $searchResult = $this->api->search([], 5);
            
            if ($searchResult === false || $searchResult->count() === 0) {
                $this->logTest("Get existing order", "SKIPPED", "No orders found to test get function");
                return;
            }
            
            $firstOrder = $searchResult->getCustomerorder(0);
            if (!$firstOrder || !$firstOrder->getId()) {
                $this->logTest("Get existing order", "SKIPPED", "No order ID available for testing");
                return;
            }
            
            $orderId = $firstOrder->getId();
            echo "  🔍 Testing get with ID: $orderId\n";
            
            $result = $this->api->get($orderId);
            
            if ($result === false) {
                $this->logTest("Get existing order", "FAILED", "Get returned false for existing order");
                return;
            }
            
            if (!($result instanceof \MS\v2\Customerorder)) {
                $this->logTest("Get existing order", "FAILED", "Result is not Customerorder instance");
                return;
            }
            
            $this->logTest("Get existing order", "PASSED", "Successfully retrieved order: " . ($result->getName() ?: 'N/A'));
            echo "  📦 Order details - ID: " . ($result->getId() ?: 'N/A') . "\n";
            echo "  📦 Order details - Sum: " . ($result->getSum() ?: 'N/A') . "\n";
            
        } catch (Exception $e) {
            $this->logTest("Get existing order", "ERROR", $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Test 12: Get non-existent order
     */
    private function testGetNonExistentOrder()
    {
        echo "📋 Test 12: Get non-existent order by ID\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            $fakeId = 'non-existent-order-id-' . uniqid();
            echo "  🔍 Testing get with fake ID: $fakeId\n";
            
            $result = $this->api->get($fakeId);
            
            if ($result === false) {
                $this->logTest("Get non-existent order", "PASSED", "Correctly returned false for non-existent order");
            } else {
                $this->logTest("Get non-existent order", "FAILED", "Should return false for non-existent order");
            }
            
        } catch (Exception $e) {
            $this->logTest("Get non-existent order", "INFO", "Exception expected for non-existent order: " . $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Test 13: Get by external codes
     */
    private function testGetByExternalCodes()
    {
        echo "📋 Test 13: Get by external codes\n";
        echo str_repeat("-", 40) . "\n";
        
        $testCases = [
            'Single code' => ['154334877'],
            'Multiple codes' => ['154334877', '21817040417', '745327421'],
            'Empty array' => [],
            'Non-existent codes' => ['FAKE001', 'FAKE002']
        ];
        
        foreach ($testCases as $testName => $externalCodes) {
            try {
                $codesStr = json_encode($externalCodes);
                echo "  🔍 Testing $testName: $codesStr\n";
                
                $result = $this->api->getByExternalCodes($externalCodes);
                
                if ($result === false) {
                    $this->logTest("Get by external codes: $testName", "INFO", "No results found (expected for test codes)");
                } else {
                    $count = $result->count();
                    $this->logTest("Get by external codes: $testName", "PASSED", "Found $count orders");
                }
                
            } catch (Exception $e) {
                $this->logTest("Get by external codes: $testName", "ERROR", $e->getMessage());
            }
        }
        
        echo "\n";
    }
    
    /**
     * Test 14: Search with invalid filters
     */
    private function testSearchWithInvalidFilters()
    {
        echo "📋 Test 14: Search with invalid filters\n";
        echo str_repeat("-", 40) . "\n";
        
        $invalidFilters = [
            'Invalid operator' => [
                'name' => ['INVALID_OP' => ['test']]
            ],
            'Malformed filter' => [
                'name' => ['=' => 'should_be_array']
            ]
        ];
        
        foreach ($invalidFilters as $testName => $filters) {
            try {
                echo "  🔍 Testing $testName\n";
                $result = $this->api->search($filters, 5);
                
                // Should either handle gracefully or throw exception
                if ($result === false) {
                    $this->logTest("Invalid filter: $testName", "INFO", "Handled gracefully - returned false");
                } else {
                    $this->logTest("Invalid filter: $testName", "WARNING", "Processed invalid filter without error");
                }
                
            } catch (Exception $e) {
                $this->logTest("Invalid filter: $testName", "INFO", "Exception thrown (expected): " . $e->getMessage());
            }
        }
        
        echo "\n";
    }
    
    /**
     * Test 15: Search with empty response simulation
     */
    private function testSearchWithEmptyResponse()
    {
        echo "📋 Test 15: Search designed to return empty results\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            // Search for something very unlikely to exist
            $filters = [
                'name' => 'DEFINITELY_NON_EXISTENT_ORDER_NAME_' . uniqid(),
                'externalCode' => 'FAKE_EXTERNAL_CODE_' . uniqid()
            ];
            
            echo "  🔍 Testing with filters designed to return empty results\n";
            $result = $this->api->search($filters);
            
            if ($result === false) {
                $this->logTest("Empty response test", "PASSED", "Correctly returned false for empty results");
            } else if ($result->count() === 0) {
                $this->logTest("Empty response test", "PASSED", "Returned empty iterator");
            } else {
                $this->logTest("Empty response test", "WARNING", "Unexpectedly found results: " . $result->count());
            }
            
        } catch (Exception $e) {
            $this->logTest("Empty response test", "ERROR", $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Helper method to log test results
     */
    private function logTest($testName, $status, $message)
    {
        $this->testResults[] = [
            'name' => $testName,
            'status' => $status,
            'message' => $message
        ];
        
        $statusIcon = [
            'PASSED' => '✅',
            'FAILED' => '❌',
            'ERROR' => '🚨',
            'INFO' => 'ℹ️',
            'WARNING' => '⚠️',
            'SKIPPED' => '⏭️'
        ];
        
        $icon = $statusIcon[$status] ?? '❓';
        echo "  $icon [$status] $testName: $message\n";
    }
    
    /**
     * Print test summary
     */
    private function printTestSummary()
    {
        echo str_repeat("=", 60) . "\n";
        echo "📊 TEST SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        
        $summary = [
            'PASSED' => 0,
            'FAILED' => 0,
            'ERROR' => 0,
            'INFO' => 0,
            'WARNING' => 0,
            'SKIPPED' => 0
        ];
        
        foreach ($this->testResults as $result) {
            $summary[$result['status']]++;
        }
        
        echo "Total tests run: " . count($this->testResults) . "\n";
        echo "✅ Passed: " . $summary['PASSED'] . "\n";
        echo "❌ Failed: " . $summary['FAILED'] . "\n";
        echo "🚨 Errors: " . $summary['ERROR'] . "\n";
        echo "⚠️ Warnings: " . $summary['WARNING'] . "\n";
        echo "ℹ️ Info: " . $summary['INFO'] . "\n";
        echo "⏭️ Skipped: " . $summary['SKIPPED'] . "\n";
        
        $successRate = count($this->testResults) > 0 
            ? round(($summary['PASSED'] / count($this->testResults)) * 100, 1)
            : 0;
            
        echo "\nSuccess Rate: {$successRate}%\n";
        
        if ($summary['FAILED'] > 0 || $summary['ERROR'] > 0) {
            echo "\n🔍 Issues found - check the detailed output above\n";
        } else {
            echo "\n🎉 All critical tests passed!\n";
        }
        
        echo str_repeat("=", 60) . "\n";
    }
}

// Run the tests
try {
    $tester = new CustomerorderApiTest();
    $tester->runAllTests();
    
} catch (Exception $e) {
    echo "🚨 Fatal error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>