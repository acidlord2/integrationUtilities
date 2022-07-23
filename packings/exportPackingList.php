<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
 	require_once($_SERVER['DOCUMENT_ROOT'] . '/PhpSpreadsheet/vendor/autoload.php');	
	//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/productsWB.php');
	//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('packings-exportPackingList.log');
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\IOFactory;

	$shippingDate = $_REQUEST["shippingDate"];
	$agent = $_REQUEST["agent"];
	$org = $_REQUEST["org"];
	$goodstype = $_REQUEST["goodstype"];
	
	$index = $shippingDate . $agent . $org . $goodstype;

	$reader = IOFactory::createReader("Xlsx");
	$spreadsheet = $reader->load($_SERVER['DOCUMENT_ROOT'] . '/XLStemplates/packingList.xlsx');
	//$workbook = $spreadsheet->getSheetByName('Ассортимент');

	$logger -> write ('products - ' . json_encode ($_SESSION['products'][$index], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	foreach ($spreadsheet->getActiveSheet()->getRowIterator() as $row)
	{
		foreach ($row->getCellIterator() as $cell)
		{
			$logger -> write ('cell - ' . $row->getRowIndex() . ',' . $cell->getColumn() . ' - ' . $cell->getValue());
			if ($cell->getValue() == '{category}')
				$cell->setValueExplicit($goodstype, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			if ($cell->getValue() == '{agent}')
				$cell->setValueExplicit($agent, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			if ($cell->getValue() == '{org}')
				$cell->setValueExplicit($org, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			if ($cell->getValue() == '{name}'){
				if (isset ($_SESSION['products'][$index]) ? count ($_SESSION['products'][$index]) : false)
				{
					$spreadsheet->getActiveSheet()->insertNewRowBefore ($row->getRowIndex() + 1, count ($_SESSION['products'][$index])); 
					foreach ($_SESSION['products'][$index] as $key => $product)
					{
						foreach ($row->getCellIterator() as $cell2)
						{
							if ($cell2->getValue() == '{name}')
								$spreadsheet->getActiveSheet()->getCell($cell2->getColumn() . ($row->getRowIndex() + $key + 1))->setValueExplicit ($product['name'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
							if ($cell2->getValue() == '{quantity}')
								$spreadsheet->getActiveSheet()->getCell($cell2->getColumn() . ($row->getRowIndex() + $key + 1))->setValueExplicit ($product['quantity'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
							if ($cell2->getValue() == '{code}')
								$spreadsheet->getActiveSheet()->getCell($cell2->getColumn() . ($row->getRowIndex() + $key + 1))->setValueExplicit ($product['code'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
							if ($cell2->getValue() == '{barcode}')
								$spreadsheet->getActiveSheet()->getCell($cell2->getColumn() . ($row->getRowIndex() + $key + 1))->setValueExplicit ('`' . implode(',', $product['barcodes']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
						}
					}
					
				}
				$spreadsheet->getActiveSheet()->removeRow($row->getRowIndex());
				break;
			}
		}
	}
/* 	foreach ($productsWB as $productWB)
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
	} */
	$writer = IOFactory::createWriter($spreadsheet, "Xlsx");
	$writer->save($_SERVER['DOCUMENT_ROOT'] . '/packings/files/packingList.xlsx');
	//echo json_encode ($dataArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	echo '/packings/files/packingList.xlsx';
?>