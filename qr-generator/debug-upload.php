<?php
// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');

// Test the process.php with simulated file upload
echo "Testing process.php with simulated real upload...\n";

// Create a temporary test file in the uploads directory
$testExcelContent = "GTIN,Код\n1234567890123,TEST-CODE-001\n1234567890123,TEST-CODE-002";
$testWordContent = "Word template placeholder";

$uploadDir = '/var/www/html/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$testExcelPath = $uploadDir . '/test_excel_' . time() . '.csv';
$testWordPath = $uploadDir . '/test_word_' . time() . '.txt';

file_put_contents($testExcelPath, $testExcelContent);
file_put_contents($testWordPath, $testWordContent);

// Simulate $_POST and $_FILES
$_POST = [
    'gtin' => '1234567890123',
    'items_per_carton' => '2',
    'barcode_type' => 'DATAMATRIX'
];

$_FILES = [
    'xlsx_file' => [
        'name' => 'test.xlsx',
        'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'tmp_name' => $testExcelPath,
        'error' => 0,
        'size' => filesize($testExcelPath)
    ],
    'docx_template' => [
        'name' => 'template.docx',
        'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'tmp_name' => $testWordPath,
        'error' => 0,
        'size' => filesize($testWordPath)
    ]
];

$_SERVER['REQUEST_METHOD'] = 'POST';

// Start output buffering to capture the response
ob_start();

try {
    include '/var/www/html/qr-generator/process.php';
    $response = ob_get_contents();
    ob_end_clean();
    
    echo "Response received:\n";
    echo $response . "\n";
    
    // Try to parse as JSON
    $json = json_decode($response, true);
    if ($json === null) {
        echo "JSON parsing failed. Error: " . json_last_error_msg() . "\n";
        echo "Raw response length: " . strlen($response) . "\n";
        echo "Raw response (first 500 chars): " . substr($response, 0, 500) . "\n";
    } else {
        echo "JSON parsed successfully:\n";
        print_r($json);
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

// Clean up
unlink($testExcelPath);
unlink($testWordPath);

// Check error log
$errorLog = '/tmp/php_errors.log';
if (file_exists($errorLog)) {
    echo "\nPHP Error Log:\n";
    echo file_get_contents($errorLog);
}
?>