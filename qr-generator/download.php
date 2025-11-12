<?php
//require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');

// Download handler for generated QR code files

if (!isset($_GET['session']) || empty($_GET['session'])) {
    http_response_code(400);
    die('Invalid session ID');
}

$sessionId = $_GET['session'];

// Validate session ID format
if (!preg_match('/^qr_[a-f0-9.]+$/', $sessionId)) {
    http_response_code(400);
    die('Invalid session ID format');
}

$sessionDir = __DIR__ . '/uploads/' . $sessionId;

if (!is_dir($sessionDir)) {
    http_response_code(404);
    die('Session not found or expired');
}

$outputFile = $sessionDir . '/output.html';
$batchedOutputFile = $sessionDir . '/batched_output.html';
$txtFile = $sessionDir . '/qr_data.txt';
$batchedTxtFile = $sessionDir . '/batched_qr_data.txt';

// Check what file to download
$type = $_GET['type'] ?? 'html';

switch ($type) {
    case 'html':
        // Try batched output first, fall back to regular output
        $fileToShow = file_exists($batchedOutputFile) ? $batchedOutputFile : $outputFile;
        
        if (file_exists($fileToShow)) {
            header('Content-Type: text/html');
            header('Content-Disposition: inline; filename="qr_report.html"');
            readfile($fileToShow);
        } else {
            http_response_code(404);
            die('Report file not found');
        }
        break;
        
    case 'txt':
        // Try batched txt first, fall back to regular txt
        $fileToShow = file_exists($batchedTxtFile) ? $batchedTxtFile : $txtFile;
        
        if (file_exists($fileToShow)) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="qr_data.txt"');
            readfile($fileToShow);
        } else {
            http_response_code(404);
            die('Text file not found');
        }
        break;
        
    case 'zip':
        // Create a ZIP file with all generated files
        $zipFile = $sessionDir . '/qr_codes.zip';
        
        if (!file_exists($zipFile)) {
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
                
                // Add all SVG files
                $files = glob($sessionDir . '/*.svg');
                foreach ($files as $file) {
                    $zip->addFile($file, basename($file));
                }
                
                // Add report files (prioritize batched versions)
                if (file_exists($batchedOutputFile)) {
                    $zip->addFile($batchedOutputFile, 'qr_report_batched.html');
                } elseif (file_exists($outputFile)) {
                    $zip->addFile($outputFile, 'qr_report.html');
                }
                
                if (file_exists($batchedTxtFile)) {
                    $zip->addFile($batchedTxtFile, 'qr_data_batched.txt');
                } elseif (file_exists($txtFile)) {
                    $zip->addFile($txtFile, 'qr_data.txt');
                }
                
                $zip->close();
            } else {
                http_response_code(500);
                die('Could not create ZIP file');
            }
        }
        
        if (file_exists($zipFile)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="qr_codes_' . date('Y-m-d_H-i-s') . '.zip"');
            header('Content-Length: ' . filesize($zipFile));
            readfile($zipFile);
        } else {
            http_response_code(404);
            die('ZIP file not found');
        }
        break;
        
    default:
        // Default to HTML report
        header('Location: ?session=' . $sessionId . '&type=html');
        break;
}

// Clean up old sessions (older than 1 hour)
$uploadsDir = __DIR__ . '/uploads';
if (is_dir($uploadsDir)) {
    $sessions = glob($uploadsDir . '/qr_*');
    foreach ($sessions as $session) {
        if (is_dir($session) && time() - filemtime($session) > 3600) {
            // Recursively delete old session directory
            function deleteDirectory($dir) {
                if (!is_dir($dir)) return false;
                $files = array_diff(scandir($dir), array('.', '..'));
                foreach ($files as $file) {
                    $path = $dir . DIRECTORY_SEPARATOR . $file;
                    is_dir($path) ? deleteDirectory($path) : unlink($path);
                }
                return rmdir($dir);
            }
            deleteDirectory($session);
        }
    }
}
?>