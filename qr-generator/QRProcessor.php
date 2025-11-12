<?php
require_once('/var/www/html/PhpSpreadsheet/vendor/autoload.php');
require_once('/var/www/html/tcpdf/tcpdf.php');
require_once('/var/www/html/tcpdf/tcpdf_barcodes_2d.php');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

class QRProcessor {
    private $sessionDir;
    private $qrStrings = [];
    private $barcodeType = 'DATAMATRIX';
    private $matchedRows = [];
    
    public function process($params) {
        $this->sessionDir = $params['session_dir'];
        $this->barcodeType = $params['barcode_type'];
        
        // Step 1: Read QR strings from Excel
        $this->readExcelFile($params['xlsx_path']);
        
        // Step 2: Generate QR codes
        $qrCodes = $this->generateQRCodes();
        
        // Step 3: Process Word template
        $outputPath = $this->processWordTemplate(
            $params['docx_template_path'],
            $qrCodes,
            $params['gtin'],
            $params['items_per_carton']
        );
        
        return [
            'total_strings' => count($this->qrStrings),
            'total_qr_codes' => count($qrCodes),
            'items_per_carton' => $params['items_per_carton'],
            'output_file' => $outputPath
        ];
    }
    
    public function processWithGTIN($params) {
        $this->sessionDir = $params['session_dir'];
        $this->barcodeType = $params['barcode_type'];
        
        // Step 1: Read Excel file and filter by GTIN
        $this->readExcelFileWithGTIN($params['xlsx_path'], $params['gtin']);
        
        // Step 2: Generate QR codes from filtered data
        $qrCodes = $this->generateQRCodesFromFilteredData();
        
        // Step 3: Process Word template with batching
        $outputPath = $this->processWordTemplateWithBatching(
            $params['docx_template_path'],
            $qrCodes,
            $params['gtin'],
            $params['items_per_carton']
        );
        
        return [
            'total_matched_rows' => count($this->matchedRows),
            'total_qr_codes' => count($qrCodes),
            'items_per_carton' => $params['items_per_carton'],
            'gtin' => $params['gtin'],
            'output_file' => $outputPath
        ];
    }
    
    private function readExcelFile($xlsxPath) {
        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($xlsxPath);
            
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            
            $this->qrStrings = [];
            
            // Read from column A, starting from row 1
            for ($row = 1; $row <= $highestRow; $row++) {
                $cellValue = $worksheet->getCell('A' . $row)->getCalculatedValue();
                if (!empty($cellValue) && is_string($cellValue)) {
                    $this->qrStrings[] = trim($cellValue);
                }
            }
            
            if (empty($this->qrStrings)) {
                throw new Exception('No QR code strings found in Excel file');
            }
            
        } catch (ReaderException $e) {
            throw new Exception('Error reading Excel file: ' . $e->getMessage());
        }
    }
    
    private function readExcelFileWithGTIN($xlsxPath, $targetGTIN) {
        try {
            // Check if file is actually an Excel file (ZIP-based) or CSV
            $isExcelFile = false;
            
            // Check if file is actually an Excel file (ZIP-based) or CSV/text
            $mimeType = mime_content_type($xlsxPath);
            $isExcelFile = false;
            
            // Check MIME type first - only consider it Excel if it's actually Excel MIME type
            if (in_array($mimeType, [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel'
            ])) {
                $isExcelFile = true;
            } else if (strpos($mimeType, 'text/') === 0 || $mimeType === 'text/csv' || $mimeType === 'text/plain') {
                // Definitely a text/CSV file - use fallback
                $isExcelFile = false;
            } else {
                // For other MIME types, check file signature
                $handle = fopen($xlsxPath, 'rb');
                if ($handle) {
                    $header = fread($handle, 4);
                    fclose($handle);
                    // Check for ZIP file signature (PK) - Excel files are ZIP-based
                    $isExcelFile = (substr($header, 0, 2) === 'PK');
                }
            }
            
            // Only try PhpSpreadsheet for actual Excel files
            if ($isExcelFile && class_exists('ZipArchive')) {
                $reader = IOFactory::createReader('Xlsx');
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($xlsxPath);
                
                $worksheet = $spreadsheet->getActiveSheet();
                $highestRow = $worksheet->getHighestRow();
                $highestCol = $worksheet->getHighestColumn();
                
                $this->matchedRows = [];
                $gtinColumn = null;
                $codeColumn = null;
                
                // Find header row and column positions
                $availableHeaders = [];
                foreach ($worksheet->getRowIterator() as $row) {
                    $rowHeaders = [];
                    if (!($gtinColumn && $codeColumn))
                        foreach ($row->getCellIterator() as $cell) {
                            $cellValue = $cell->getCalculatedValue();
                            if (!empty(trim($cellValue))) {
                                $rowHeaders[] = trim($cellValue);
                            }
                            $cellValueLower = strtolower(trim($cellValue));
                            if ($cellValueLower === 'gtin') {
                                $gtinColumn = $cell->getColumn();
                            }
                            if ($cellValueLower === 'код' || $cellValueLower === 'code') {
                                $codeColumn = $cell->getColumn();
                            }
                        }
                        if (!empty($rowHeaders)) {
                            $availableHeaders[] = "Row $row: " . implode(', ', $rowHeaders);
                        }
                    if ($gtinColumn && $codeColumn) {
                        $this->matchedRows[] = ['gtin' => $worksheet->getCell($gtinColumn . ($row->getRowIndex()))->getCalculatedValue(), 'code' => $worksheet->getCell($codeColumn . ($row->getRowIndex()))->getCalculatedValue()];
                    }
                }
            }
                
        } catch (ReaderException $e) {
            // If PhpSpreadsheet fails, try fallback reader
            throw new Exception('Error reading Excel file: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Error reading Excel file: ' . $e->getMessage());
        }
    }
    
    private function generateQRCodes() {
        $qrCodes = [];
        
        foreach ($this->qrStrings as $index => $qrString) {
            try {
                // Generate barcode
                $barcodeObj = new TCPDF2DBarcode($qrString, $this->barcodeType);
                
                // Get SVG code
                $svgCode = $barcodeObj->getBarcodeSVGcode(8, 8, 'black');
                
                // Save SVG file
                $svgFileName = 'qr_' . ($index + 1) . '.svg';
                $svgPath = $this->sessionDir . '/' . $svgFileName;
                file_put_contents($svgPath, $svgCode);
                
                // Convert SVG to PNG for better Word compatibility
                $pngPath = $this->convertSVGtoPNG($svgPath, $index + 1);
                
                $qrCodes[] = [
                    'index' => $index + 1,
                    'string' => $qrString,
                    'svg_path' => $svgPath,
                    'png_path' => $pngPath,
                    'filename' => basename($pngPath)
                ];
                
            } catch (Exception $e) {
                error_log("Error generating QR code for string '$qrString': " . $e->getMessage());
                // Continue with other codes
            }
        }
        
        return $qrCodes;
    }
    
    private function generateQRCodesFromFilteredData() {
        $qrCodes = [];
        
        foreach ($this->matchedRows as $index => $rowData) {
            try {
                $qrString = $rowData['code'];
                
                // Generate barcode
                $barcodeObj = new TCPDF2DBarcode($qrString, $this->barcodeType);
                
                // Get SVG code
                $svgCode = $barcodeObj->getBarcodeSVGcode(8, 8, 'black');
                
                // Save SVG file
                $svgFileName = 'qr_' . ($index + 1) . '.svg';
                $svgPath = $this->sessionDir . '/' . $svgFileName;
                file_put_contents($svgPath, $svgCode);
                
                $qrCodes[] = [
                    'index' => $index + 1,
                    'string' => $qrString,
                    'gtin' => $rowData['gtin'],
                    'svg_path' => $svgPath,
                    'filename' => basename($svgPath),
                    'row_data' => $rowData['data']
                ];
                
            } catch (Exception $e) {
                error_log("Error generating QR code for string '{$rowData['code']}': " . $e->getMessage());
                // Continue with other codes
            }
        }
        
        return $qrCodes;
    }
    
    private function convertSVGtoPNG($svgPath, $index) {
        // For now, we'll keep the SVG files and return the path
        // In a production environment, you might want to use ImageMagick or similar
        // to convert SVG to PNG for better Word document compatibility
        
        $pngPath = $this->sessionDir . '/qr_' . $index . '.png';
        
        // Simple SVG to PNG conversion using TCPDF (limited but works)
        try {
            // Read SVG content
            $svgContent = file_get_contents($svgPath);
            
            // Use TCPDF to convert SVG to PNG
            $pdf = new TCPDF('P', 'mm', array(50, 50), true, 'UTF-8', false);
            $pdf->SetAutoPageBreak(false);
            $pdf->AddPage();
            
            // This is a simplified approach - in production you'd want proper SVG to PNG conversion
            // For now, we'll just copy the SVG and rename it
            copy($svgPath, $pngPath . '.svg');
            
            return $svgPath; // Return SVG path for now
            
        } catch (Exception $e) {
            error_log("SVG to PNG conversion failed: " . $e->getMessage());
            return $svgPath; // Fall back to SVG
        }
    }
    
    private function processWordTemplate($templatePath, $qrCodes, $gtin, $itemsPerCarton) {
        // For now, create a simple HTML report
        // In production, you'd use PHPWord or similar to process the actual DOCX template
        
        $outputPath = $this->sessionDir . '/output.html';
        
        $html = $this->generateHTMLReport($qrCodes, $gtin, $itemsPerCarton);
        file_put_contents($outputPath, $html);
        
        // Also create a simple text file with the data
        $txtPath = $this->sessionDir . '/qr_data.txt';
        $txtContent = "GTIN: $gtin\n";
        $txtContent .= "Items per carton: $itemsPerCarton\n";
        $txtContent .= "Total QR codes: " . count($qrCodes) . "\n\n";
        $txtContent .= "QR Code Strings:\n";
        
        foreach ($qrCodes as $qr) {
            $txtContent .= $qr['index'] . ". " . $qr['string'] . "\n";
        }
        
        file_put_contents($txtPath, $txtContent);
        
        return $outputPath;
    }
    
    private function processWordTemplateWithBatching($templatePath, $qrCodes, $gtin, $itemsPerCarton) {
        // Create batches based on items per carton
        $batches = array_chunk($qrCodes, $itemsPerCarton);
        
        // Output Word document path
        $outputPath = $this->sessionDir . '/output_document.docx';
        
        try {
            // Create new Word document
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);
            
            // Process each batch as a separate page
            foreach ($batches as $batchIndex => $batch) {
                // Add a new section (page) for each batch
                $section = $phpWord->addSection([
                    'marginTop' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2),
                    'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2),
                    'marginLeft' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2),
                    'marginRight' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2),
                ]);
                
                // If template exists, read and clone its content
                if (file_exists($templatePath)) {
                    try {
                        $templateReader = \PhpOffice\PhpWord\IOFactory::createReader('Word2007');
                        $templateDoc = $templateReader->load($templatePath);
                        
                        // Copy template content to current section
                        foreach ($templateDoc->getSections() as $templateSection) {
                            foreach ($templateSection->getElements() as $element) {
                                $section->addElement(clone $element);
                            }
                        }
                    } catch (Exception $e) {
                        // If template loading fails, add basic header
                        $section->addText("Batch " . ($batchIndex + 1), ['bold' => true, 'size' => 14]);
                        $section->addTextBreak();
                    }
                } else {
                    // No template - add basic header
                    $section->addText("Batch " . ($batchIndex + 1) . " - GTIN: " . $gtin, ['bold' => true, 'size' => 14]);
                    $section->addTextBreak();
                }
                
                // Add batch information
                $section->addText("Carton " . ($batchIndex + 1) . " - Items: " . count($batch), ['bold' => true]);
                $section->addTextBreak();
                
                // Add QR codes for this batch
                $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
                $codesPerRow = 3; // Number of QR codes per row
                
                for ($i = 0; $i < count($batch); $i += $codesPerRow) {
                    $table->addRow();
                    
                    for ($j = 0; $j < $codesPerRow && ($i + $j) < count($batch); $j++) {
                        $qrCode = $batch[$i + $j];
                        $cell = $table->addCell(2000);
                        
                        // Add QR code image if SVG file exists
                        if (file_exists($qrCode['svg_path'])) {
                            try {
                                // Convert SVG content to image data for Word
                                $svgContent = file_get_contents($qrCode['svg_path']);
                                $base64Data = base64_encode($svgContent);
                                
                                // Add image to cell (using base64 data)
                                $cell->addImage($qrCode['svg_path'], [
                                    'width' => 80,
                                    'height' => 80,
                                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
                                ]);
                            } catch (Exception $e) {
                                // Fallback: add text if image fails
                                $cell->addText('QR Code #' . $qrCode['index'], ['size' => 8]);
                            }
                        }
                        
                        // Add code text below image
                        $cell->addTextBreak();
                        $cell->addText($qrCode['string'], ['size' => 8, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    }
                }
                
                // Add page break except for last batch
                if ($batchIndex < count($batches) - 1) {
                    $section->addPageBreak();
                }
            }
            
            // Save the document
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($outputPath);
            
            return $outputPath;
            
        } catch (Exception $e) {
            error_log("Word document generation failed: " . $e->getMessage());
            
            // Fallback to HTML if Word processing fails
            $htmlPath = $this->sessionDir . '/batched_output.html';
            $html = $this->generateBatchedHTMLReport($qrCodes, $batches, $gtin, $itemsPerCarton);
            file_put_contents($htmlPath, $html);
            
            return $htmlPath;
        }
    }
}