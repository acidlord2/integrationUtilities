<?php
// Start output buffering to prevent any accidental output
ob_start();

// Set error handler to catch all errors
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');

try {
    error_log('QR Generator: Starting process.php execution');
    
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    error_log('QR Generator: POST request validated');
    
    // Validate required fields
    if (!isset($_POST['gtin']) || empty($_POST['gtin'])) {
        throw new Exception('GTIN is required');
    }
    
    if (!isset($_POST['items_per_carton']) || empty($_POST['items_per_carton'])) {
        throw new Exception('Items per carton is required');
    }
    
    // Debug file uploads (can be removed in production)
    // error_log('QR Generator Debug - FILES array: ' . print_r($_FILES, true));
    // error_log('QR Generator Debug - POST array: ' . print_r($_POST, true));
    
    // Validate uploaded files
    if (!isset($_FILES['xlsx_file'])) {
        throw new Exception('Excel file field not found in upload');
    }
    
    if ($_FILES['xlsx_file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'Excel file upload error: ';
        switch ($_FILES['xlsx_file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $maxSize = ini_get('upload_max_filesize');
                $errorMessage .= "File too large. Maximum allowed size is $maxSize. Please reduce file size or contact administrator.";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage .= 'File exceeds MAX_FILE_SIZE';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage .= 'File was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage .= 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMessage .= 'Missing temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMessage .= 'Failed to write file to disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMessage .= 'Upload stopped by extension';
                break;
            default:
                $errorMessage .= 'Unknown error (' . $_FILES['xlsx_file']['error'] . ')';
        }
        throw new Exception($errorMessage);
    }
    
    if (!isset($_FILES['docx_template'])) {
        throw new Exception('Word template field not found in upload');
    }
    
    if ($_FILES['docx_template']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'Word template upload error: ';
        switch ($_FILES['docx_template']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $maxSize = ini_get('upload_max_filesize');
                $errorMessage .= "File too large. Maximum allowed size is $maxSize. Please reduce file size or contact administrator.";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage .= 'File exceeds MAX_FILE_SIZE';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage .= 'File was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage .= 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMessage .= 'Missing temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMessage .= 'Failed to write file to disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMessage .= 'Upload stopped by extension';
                break;
            default:
                $errorMessage .= 'Unknown error (' . $_FILES['docx_template']['error'] . ')';
        }
        throw new Exception($errorMessage);
    }
    
    // Get form data
    $gtin = trim($_POST['gtin']);
    $itemsPerCarton = intval($_POST['items_per_carton']);
    $barcodeType = $_POST['barcode_type'] ?? 'DATAMATRIX';
    
    // Validate GTIN
    if (!preg_match('/^\d{12,14}$/', $gtin)) {
        throw new Exception('GTIN must be 12-14 digits');
    }
    
    if ($itemsPerCarton < 1) {
        throw new Exception('Items per carton must be at least 1');
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/uploads';
    if (!is_dir($uploadsDir)) {
        if (!mkdir($uploadsDir, 0777, true)) {
            throw new Exception('Failed to create uploads directory: ' . $uploadsDir);
        }
        error_log('QR Generator: Created uploads directory at ' . $uploadsDir);
    }
    
    // Check if directory is writable
    if (!is_writable($uploadsDir)) {
        throw new Exception('Uploads directory is not writable: ' . $uploadsDir);
    }
    
    // Generate unique session ID for this processing
    $sessionId = uniqid('qr_', true);
    $sessionDir = $uploadsDir . '/' . $sessionId;
    mkdir($sessionDir, 0755, true);
    
    // Move uploaded files
    $xlsxPath = $sessionDir . '/input.xlsx';
    $docxPath = $sessionDir . '/template.docx';
    
    // Handle file uploads - use copy for testing or move_uploaded_file for real uploads
    if (is_uploaded_file($_FILES['xlsx_file']['tmp_name'])) {
        if (!move_uploaded_file($_FILES['xlsx_file']['tmp_name'], $xlsxPath)) {
            $error = error_get_last();
            throw new Exception('Failed to save Excel file. Error: ' . ($error ? $error['message'] : 'Unknown error'));
        }
    } else {
        // For testing purposes, copy the file
        if (!copy($_FILES['xlsx_file']['tmp_name'], $xlsxPath)) {
            $error = error_get_last();
            throw new Exception('Failed to copy Excel file. Error: ' . ($error ? $error['message'] : 'Unknown error'));
        }
    }
    
    if (is_uploaded_file($_FILES['docx_template']['tmp_name'])) {
        if (!move_uploaded_file($_FILES['docx_template']['tmp_name'], $docxPath)) {
            $error = error_get_last();
            throw new Exception('Failed to save Word template. Error: ' . ($error ? $error['message'] : 'Unknown error'));
        }
    } else {
        // For testing purposes, copy the file
        if (!copy($_FILES['docx_template']['tmp_name'], $docxPath)) {
            $error = error_get_last();
            throw new Exception('Failed to copy Word template. Error: ' . ($error ? $error['message'] : 'Unknown error'));
        }
    }
    
    // Validate file types
    $xlsxMime = mime_content_type($xlsxPath);
    $docxMime = mime_content_type($docxPath);
    
    $validXlsxMimes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'application/octet-stream',
        'text/csv',
        'application/csv',
        'text/plain'
    ];
    
    $validDocxMimes = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/octet-stream',
        'text/plain'
    ];
    
    if (!in_array($xlsxMime, $validXlsxMimes)) {
        throw new Exception('Invalid Excel file format');
    }
    
    if (!in_array($docxMime, $validDocxMimes)) {
        throw new Exception('Invalid Word document format');
    }
    
    // Process the files
    error_log('QR Generator: Starting QRProcessor loading...');
    require_once(__DIR__ . '/QRProcessor.php');
    error_log('QR Generator: QRProcessor loaded successfully');
    
    try {
        error_log('QR Generator: Creating QRProcessor instance...');
        $processor = new QRProcessor();
        error_log('QR Generator: QRProcessor instance created, starting processWithGTIN...');
        
        $result = $processor->processWithGTIN([
            'xlsx_path' => $xlsxPath,
            'docx_template_path' => $docxPath,
            'gtin' => $gtin,
            'items_per_carton' => $itemsPerCarton,
            'barcode_type' => $barcodeType,
            'session_dir' => $sessionDir
        ]);
        
        error_log('QR Generator: processWithGTIN completed successfully');
    } catch (Exception $processingError) {
        error_log('QR Generator: Processing error occurred: ' . $processingError->getMessage());
        error_log('QR Generator: Processing error file: ' . $processingError->getFile());
        error_log('QR Generator: Processing error line: ' . $processingError->getLine());
        throw new Exception('Error processing files: ' . $processingError->getMessage());
    }
    
    // Clean output buffer and return success response
    error_log('QR Generator: Preparing success response');
    ob_clean();
    $response = json_encode([
        'success' => true,
        'message' => 'QR codes generated successfully!',
        'download_url' => 'download.php?session=' . $sessionId,
        'filename' => 'qr_codes_output.docx',
        'stats' => $result
    ]);
    error_log('QR Generator: Success response prepared, sending...');
    echo $response;
    error_log('QR Generator: Success response sent');
    
} catch (Exception $e) {
    // Log error with full details
    error_log('QR Generator Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    error_log('QR Generator Stack trace: ' . $e->getTraceAsString());
    
    // Clean output buffer and return error response
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
} catch (Error $e) {
    // Catch fatal errors
    error_log('QR Generator Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>