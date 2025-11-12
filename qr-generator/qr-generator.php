<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/tcpdf/tcpdf.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/tcpdf/tcpdf_barcodes_2d.php');

try {
    // Use longer text to force a larger Data Matrix with 4 finder patterns
    $longText = '0104901301453334215+Ma-x';
    $barcodeobj = new TCPDF2DBarcode($longText, 'DATAMATRIX');
    
    
    // Get SVG code directly without letting it set headers
    ob_start();
    $svg = $barcodeobj->getBarcodeSVGcode(8, 8, 'black');
    ob_end_clean();
    // Use SVG since PNG methods don't work properly
    header('Content-Type: image/svg+xml');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    echo $svg;

} catch (Exception $e) {
    header('Content-Type: text/plain');
    echo 'Error generating barcode: ' . $e->getMessage();
}
