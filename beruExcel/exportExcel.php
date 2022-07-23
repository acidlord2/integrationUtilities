<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/PhpSpreadsheet/vendor/autoload.php');	
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/productsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('exportExcel.log');
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\IOFactory;

	$reader = IOFactory::createReader("Xlsx");
	$spreadsheet = $reader->load("template.xlsx");
	//$workbook = $spreadsheet->getSheetByName('Ассортимент');
	
	$products = ProductsMS::getAssortment();
	//$logger->write ('products - ' . json_encode ($products, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	$cell = 5;
	foreach ($products as $product)
	{
		//$logger->write ('products - ' . json_encode (array_column($product['salePrices'], 'priceType'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$priceTypes = array_column($product['salePrices'], 'priceType');
		$priceKey = array_search('Цена Беру.ру', array_column($priceTypes, 'name'));
		//$logger -> write ('priceKey - ' . $priceKey);
		if ($priceKey !== false ? $product['salePrices'][$priceKey]['value'] > 0 : false)
		{
			$spreadsheet->getSheetByName('Ассортимент')->setCellValue('B'.$cell, $product['code']);
			$spreadsheet->getSheetByName('Ассортимент')->setCellValue('AD'.$cell, $product['salePrices'][$priceKey]['value'] / 100);
			$cell++;
		}
	}
	
	$writer = IOFactory::createWriter($spreadsheet, "Xlsx");
	$writer->save("export.xlsx");
	//echo json_encode ($dataArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<html>
	<head/>
	<body>
		<p><a href="export.xlsx">Download excel</a></p>
	</body>
</html>