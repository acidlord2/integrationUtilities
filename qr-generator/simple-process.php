<?php
ob_start();
header('Content-Type: application/json');

try {
    // Very basic test - just check if we can handle a POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Check if files are uploaded
    if (!isset($_FILES['xlsx_file'])) {
        throw new Exception('Excel file field not found in upload');
    }
    
    if (!isset($_FILES['docx_template'])) {
        throw new Exception('Word template field not found in upload');
    }
    
    // Check for upload errors
    if ($_FILES['xlsx_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Excel file upload error: ' . $_FILES['xlsx_file']['error']);
    }
    
    if ($_FILES['docx_template']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Word template upload error: ' . $_FILES['docx_template']['error']);
    }
    
    // Basic validation passed
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Files uploaded successfully',
        'debug' => [
            'xlsx_file' => [
                'name' => $_FILES['xlsx_file']['name'],
                'size' => $_FILES['xlsx_file']['size'],
                'type' => $_FILES['xlsx_file']['type']
            ],
            'docx_template' => [
                'name' => $_FILES['docx_template']['name'],
                'size' => $_FILES['docx_template']['size'],
                'type' => $_FILES['docx_template']['type']
            ],
            'gtin' => $_POST['gtin'] ?? 'not set',
            'items_per_carton' => $_POST['items_per_carton'] ?? 'not set'
        ]
    ]);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>