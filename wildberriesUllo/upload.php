<?php
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
//	ini_set('max_execution_time', 0);
//	set_time_limit(0);
//	ignore_user_abort(true);
	//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/payments.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/db.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/productsWB.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('wbUpload.log');

	function replace_unicode_escape_sequence($match) {
		return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
	}
	
	function unicode_decode($str) {
		return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $str);
	}
	
	$target_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
	$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
	//echo json_encode ($_FILES, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	if ($_FILES["fileToUpload"]['error'] !== 0)
	{
		echo 'no files';
		return;
	}
	
	$uploadOk = 1;
	$str = '';
	$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
	$logger->write ('target_file - ' . $target_file);

	// Allow certain file formats
	if($imageFileType != "xlsx") {
		$str .= "Только файлы xlsx могут быть загружены.<br>";
		$uploadOk = 0;
	}
	// Check if $uploadOk is set to 0 by an error
	if ($uploadOk == 0) {
		$str .= "Файл не загружен.<br>";
	// if everything is ok, try to upload file
	} else {
		if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
			$str .= "Файл загружен ". basename($_FILES["fileToUpload"]["name"]). " успешно загружен.<br>";
		} else {
			$str .= "Что-то пошло не так.<br>";
			trigger_error('Die', E_ERROR);		
		}
	}
	
	require_once($_SERVER['DOCUMENT_ROOT'] . '/PhpSpreadsheet/vendor/autoload.php');	
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\IOFactory;

	$reader = IOFactory::createReader("Xlsx");
	$spreadsheet = $reader->load($_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $_FILES["fileToUpload"]["name"]);
	$worksheets = $spreadsheet->getAllSheets();
	foreach ($worksheets as $worksheet)
	{
		$data = $worksheet->rangeToArray(
			'A1:' . $worksheet->getHighestColumn() . $worksheet->getHighestRow(),     // The worksheet range that we want to retrieve
			'',        // Value that should be returned for empty cells
			TRUE,        // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
			TRUE,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
			TRUE         // Should the array be indexed by cell row and cell column
		);
		
		$chrt_idKey = false;
		$nmldKey = false;
		$codeKey = false;
		$codeimtKey = false;
		$barcodeKey = false;
		$count = 0;
		
		$productsWB = ProductsWB::getProducts();
		
		foreach ($data as $row)
		{
			if ($chrt_idKey && $nmldKey && $codeKey && $codeimtKey && $barcodeKey)
			{
				$recordExists = array_search($row[$codeKey], array_column($productsWB, 'code'));
				if ($recordExists !== false)
					$sql = 'update product_mapping_wildberries set chrt_id = ' . $row[$chrt_idKey] . ', nmld = ' . $row[$nmldKey]	 . ', barcode = "' . $row[$barcodeKey]	 . '", codeimt = "' . $row[$codeimtKey] . '" where code = "' . $row[$codeKey] . '"';
				else
					$sql = 'insert into product_mapping_wildberries values (' . $row[$chrt_idKey] . ', ' . $row[$nmldKey] . ', "' . $row[$codeKey] . '", "' . $row[$codeimtKey] . '", "' . $row[$barcodeKey] . '")';
				Db::exec_query ($sql);
				$count++;
				$logger->write ('sql - ' . $sql);
			}
			else
			{
				$chrt_idKey = array_search ('Код размера (chrt_id)', $row);
				$codeimtKey = array_search ('Артикул ИМТ', $row);
				$nmldKey = array_search ('Артикул WB', $row);
				$codeKey = array_search ('Артикул поставщика', $row);
				$barcodeKey = array_search ('Баркод', $row);
			}
		}
	}
	
	// fill orders
 	//Orders::findOrders($payments);
	echo $count;
?>
