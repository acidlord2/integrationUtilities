<?php
 	require_once($_SERVER['DOCUMENT_ROOT'] . '/PhpSpreadsheet/vendor/autoload.php');	
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/productsWB.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('exportWBvat.log');
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\IOFactory;

	$reader = IOFactory::createReader("Xlsx");
	$spreadsheet = $reader->load($_SERVER['DOCUMENT_ROOT'] . '/XLStemplates/uploadWBvat.xlsx');
	//$workbook = $spreadsheet->getSheetByName('Ассортимент');
	
	$productsWB = ProductsWB::getProducts();
	$productCodes = array_column($productsWB, 'code');
	$filter = '';
	foreach ($productCodes as $productCode)
		$filter .= 'code=' . $productCode . ';';
	$productsMS = ProductsMS::getAssortment($filter);

	$productCodesMS = array_column($productsMS, 'code');
	//$logger->write ('products - ' . json_encode ($products, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	$cell = 2;
	foreach ($productsWB as $productWB)
	{
		$productMSKey = array_search($productWB['code'], $productCodesMS);
		//$logger->write ('products - ' . json_encode (array_column($product['salePrices'], 'priceType'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		if ($productMSKey === false)
		{
			$logger->write ('not found - ' . json_encode ($productWB, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			continue;
		}
		//$logger -> write ('priceKey - ' . $priceKey);
		if (isset($productsMS[$productMSKey]['vat']) || isset($productsMS[$productMSKey]['effectiveVat']))
		{
			//$spreadsheet->getActiveSheet()->getStyle('A'.$cell)->getNumberFormat()->setFormatCode('#');
			$spreadsheet->getActiveSheet()->getCell('A'.$cell)->setValueExplicit((string)$productWB['barcode'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			//$spreadsheet->getActiveSheet()->setCellValue('A'.$cell, (string)$productWB['barcode']);
			$spreadsheet->getActiveSheet()->setCellValue('B'.$cell, isset($productsMS[$productMSKey]['vat']) ? $productsMS[$productMSKey]['vat'] : $productsMS[$productMSKey]['effectiveVat']);
			$cell++;
		}
	}
	$writer = IOFactory::createWriter($spreadsheet, "Xlsx");
	$writer->save($_SERVER['DOCUMENT_ROOT'] . '/download/exportWBvat.xlsx');
	//echo json_encode ($dataArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	echo '/download/exportWBvat.xlsx';
?>