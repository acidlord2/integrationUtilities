<?php

class SimpleExcelReader {
    
    /**
     * Read Excel file using alternative method without ZipArchive
     * This will attempt to read the file in different ways
     */
    public static function readExcelFile($filePath, $gtinFilter = null) {
        $data = [];
        
        // First try: Check if it's actually a CSV file with .xlsx extension
        if (self::isCSVFile($filePath)) {
            return self::readCSVFile($filePath, $gtinFilter);
        }
        
        // Second try: Try to read as XML (Excel 2003 format)
        try {
            return self::readXMLExcel($filePath, $gtinFilter);
        } catch (Exception $e) {
            // Continue to next method
        }
        
        // Third try: Use simple text parsing
        try {
            return self::readTextBased($filePath, $gtinFilter);
        } catch (Exception $e) {
            // Continue to next method
        }
        
        throw new Exception("Unable to read Excel file with available methods. Please convert to CSV format or ensure ZIP extension is installed.");
    }
    
    private static function isCSVFile($filePath) {
        $handle = fopen($filePath, 'r');
        if (!$handle) return false;
        $firstLine = fgets($handle);
        fclose($handle);
        if ($firstLine === false) return false;
        // Look for actual tab character, not the literal sequence "\t"
        return (strpos($firstLine, ',') !== false || strpos($firstLine, ';') !== false || strpos($firstLine, "\t") !== false || strpos($firstLine, '|') !== false);
    }
    
    private static function readCSVFile($filePath, $gtinFilter = null) {
        $data = [];
        $headers = [];
        if (!is_readable($filePath)) return $data;
        $handle = fopen($filePath, 'r');
        if (!$handle) return $data;

        // Peek first non-empty line to detect delimiter
        $firstDataLine = '';
        $pos = ftell($handle);
        while (($line = fgets($handle)) !== false) {
            if (trim($line) !== '') { $firstDataLine = $line; break; }
        }
        // Rewind to start
        fseek($handle, 0);
        $delimiters = [',',';','\t','|'];
        $bestDelimiter = ',';
        $maxParts = 0;
        foreach ($delimiters as $delim) {
            $parts = explode($delim, $firstDataLine);
            if (count($parts) > $maxParts) { $maxParts = count($parts); $bestDelimiter = $delim; }
        }
        error_log('SimpleExcelReader: chosen delimiter='.( $bestDelimiter === "\t" ? 'TAB' : $bestDelimiter ).' parts='.$maxParts.' line_snip='.substr(trim($firstDataLine),0,120));

        $rowNumber = 0;
        while (($row = fgetcsv($handle, 0, $bestDelimiter)) !== false) {
            $rowNumber++;
            // Skip filter line (first line) if it only has one column but contains "Фильтр(" or starts with 'Filter('
            if ($rowNumber == 1 && count($row) <= 2 && (stripos($row[0], 'фильтр(') !== false || stripos($row[0], 'filter(') !== false)) {
                continue; // treat next line as headers
            }
            if (empty($headers)) {
                // Normalize headers trimming and removing BOM
                $headers = array_map(function($h){ return trim(str_replace("\xEF\xBB\xBF", '', $h)); }, $row);
                continue;
            }
            if (count($row) != count($headers)) {
                // Attempt to pad shorter row
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                } else {
                    $row = array_slice($row, 0, count($headers));
                }
            }
            $rowAssoc = array_combine($headers, $row);

            if ($gtinFilter !== null) {
                $matches = false;
                // Prefer explicit GTIN header
                foreach ($rowAssoc as $k=>$v) {
                    $kLower = strtolower($k);
                    if ($kLower === 'gtin' || $kLower === 'ean' || $kLower === 'barcode') {
                        if (trim($v) === trim($gtinFilter)) { $matches = true; break; }
                    }
                }
                if (!$matches) {
                    // Fallback: search inside code cells containing embedded GTIN after 01 prefix
                    foreach ($rowAssoc as $k=>$v) {
                        if (stripos($k, 'код') !== false || stripos($k,'code') !== false) {
                            $val = trim($v);
                            if (strlen($val) > 16 && substr($val,0,2)==='01') {
                                $embedded = substr($val,2,14); // 14-digit GTIN
                                if ($embedded === trim($gtinFilter) || substr($embedded,1)===trim($gtinFilter)) { $matches = true; break; }
                            }
                        }
                    }
                }
                if (!$matches) continue;
            }
            $data[] = $rowAssoc;
        }
        fclose($handle);
        return $data;
    }
    
    private static function readXMLExcel($filePath, $gtinFilter = null) {
        // This would handle Excel XML format
        // For now, just throw exception
        throw new Exception("XML Excel format not yet implemented");
    }
    
    private static function readTextBased($filePath, $gtinFilter = null) {
        // Try to read as tab-separated or other delimited format
        $content = file_get_contents($filePath);
        if (!$content) {
            throw new Exception("Cannot read file content");
        }
        
        // Try different line endings
        $lines = preg_split('/\r\n|\r|\n/', $content);
        if (count($lines) < 2) {
            throw new Exception("File doesn't appear to have tabular data");
        }
        
        $data = [];
        $headers = [];
        
        foreach ($lines as $lineNumber => $line) {
            if (empty(trim($line))) continue;
            
            // Try different separators
            $separators = ["\t", ",", ";", "|"];
            $bestSeparator = "\t";
            $maxColumns = 0;
            
            foreach ($separators as $sep) {
                $columns = explode($sep, $line);
                if (count($columns) > $maxColumns) {
                    $maxColumns = count($columns);
                    $bestSeparator = $sep;
                }
            }
            
            $columns = explode($bestSeparator, $line);
            
            if (empty($headers)) {
                $headers = $columns;
                continue;
            }
            
            if (count($columns) != count($headers)) {
                continue; // Skip malformed rows
            }
            
            $rowData = array_combine($headers, $columns);
            
            // Apply GTIN filter if specified
            if ($gtinFilter !== null) {
                $gtinFound = false;
                foreach ($rowData as $key => $value) {
                    if (stripos($key, 'gtin') !== false || stripos($key, 'код') !== false) {
                        if (trim($value) == trim($gtinFilter)) {
                            $gtinFound = true;
                            break;
                        }
                    }
                }
                if (!$gtinFound) continue;
            }
            
            $data[] = $rowData;
        }
        
        return $data;
    }
    
    /**
     * Convert Excel file to CSV format
     */
    public static function convertToCSV($excelPath, $csvPath) {
        try {
            $data = self::readExcelFile($excelPath);
            
            if (empty($data)) {
                throw new Exception("No data found in Excel file");
            }
            
            $handle = fopen($csvPath, 'w');
            if (!$handle) {
                throw new Exception("Cannot create CSV file");
            }
            
            // Write headers
            $headers = array_keys($data[0]);
            fputcsv($handle, $headers);
            
            // Write data
            foreach ($data as $row) {
                fputcsv($handle, array_values($row));
            }
            
            fclose($handle);
            return true;
            
        } catch (Exception $e) {
            throw new Exception("CSV conversion failed: " . $e->getMessage());
        }
    }
}

?>