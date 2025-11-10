<?php
require_once('tcpdf/tcpdf.php');
require_once('tcpdf/tcpdf_barcodes_2d.php');

$barcodeobj = new TCPDF2DBarcode('Hello World', 'DATAMATRIX');
$barcodeobj->getBarcodePNG(4, 4, array(0,0,0)); // Returns PNG image data as string

$pngData = $barcodeobj->getBarcodePngData(4, 4, array(0,0,0)); // Returns PNG image data as string
// echo png file headers
header('Content-Type: image/png');
echo $pngData;