<?php
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
//	ini_set('max_execution_time', 0);
//	set_time_limit(0);
//	ignore_user_abort(true);
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/payments.php');

	function replace_unicode_escape_sequence($match) {
		return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
	}
	
	function unicode_decode($str) {
		return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $str);
	}
	
	$target_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
	$target_file = $target_dir . basename($_FILES["fileToUploadOzon"]["name"]);
	$uploadOk = 1;
	$str = '';
	$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('uploadOzon.log');
	$logger->write ('target_file - ' . $target_file);

	// Allow certain file formats
	if($imageFileType != "csv" && $imageFileType != "xlsx") {
		$str .= "Только файлы csv могут быть загружены.<br>";
		$uploadOk = 0;
	}
	// Check if $uploadOk is set to 0 by an error
	if ($uploadOk == 0) {
		$str .= "Файл не загружен.<br>";
	// if everything is ok, try to upload file
	} else {
		if (move_uploaded_file($_FILES["fileToUploadOzon"]["tmp_name"], $target_file)) {
			$str .= "Файл загружен ". basename( $_FILES["fileToUploadOzon"]["name"]). " успешно загружен.<br>";
		} else {
			$str .= "Что-то пошло не так.<br>";
			trigger_error('Die', E_ERROR);		
		}
	}
	
	require_once($_SERVER['DOCUMENT_ROOT'] . '/SimpleExcel/SimpleExcel.php');
	use SimpleExcel\SimpleExcel;
	$excel = new SimpleExcel('CSV');
	$excel->parser->setDelimiter(';');
	$excel->parser->loadFile($target_file);
	$cells = array();
	$i = 0;
	while (true) 
	{
		$i++;
		if ($excel->parser->isRowExists($i))
		{
			$cells[] = $excel->parser->getRow($i);
		}
		else 
			break;
	}
	//echo unicode_decode(json_encode($cells));
	$logger->write ('cells - ' . json_encode($cells, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	$payments = array();
	$j = 0;
	$summcol = false;
	$sellcol = false;
	$ordercol = false;
	$date = false;
	// parse file and update order
	foreach ($cells as $i => $row)
	{
		if (!$date)
			foreach ($row as $cellnum => $celldata)
				if (strpos($celldata, "Отчет реализации") !== false)
				{
					preg_match ('/^Отчет реализации № \d+ от (\d{2}.\d{2}.\d{4})$/', $celldata, $matches);
					if (isset($matches[1]))
						$date = date_create_from_format ('d.m.Y', $matches[1]);
				}
		
			
		if (!$sellcol)
		{
			foreach ($row as $cellnum => $celldata)
			{
				if (strpos($celldata, "Код товара продавца") !== false)
					$itemcol = $cellnum;
				if (strpos($celldata, "Реализовано") !== false)
					$sellcol = $cellnum;
				if (strpos($celldata, "Возвращено клиентом") !== false)
					$retcol = $cellnum;
				if (strpos($celldata, "Отправление") !== false)
					$otprcol = $cellnum;
			}
		}
		if ($sellcol && !$summcol)
		{
			foreach ($row as $cellnum => $celldata)
			{
				if ($cellnum >= $sellcol && $cellnum < $retcol && strpos($celldata, "Сумма, руб.") !== false)
					$summcol = $cellnum;
				if ($cellnum >= $sellcol && $cellnum < $retcol && strpos($celldata, "Ком-я, руб.") !== false)
					$commcol = $cellnum;
				if ($cellnum >= $retcol && $cellnum < $otprcol && strpos($celldata, "Сумма, руб.") !== false)
					$stornosummcol = $cellnum;
				if ($cellnum >= $retcol && $cellnum < $otprcol && strpos($celldata, "Ком-я, руб.") !== false)
					$stornocommcol = $cellnum;
				if ($cellnum >= $otprcol && strpos($celldata, "Номер") !== false)
					$ordercol = $cellnum;
				if ($cellnum >= $otprcol && strpos($celldata, "Дата") !== false)
					$datecol = $cellnum;
			}
		}
		
		if (!$ordercol)
			continue;
		
		//check order number
		preg_match ('/^\d{8}-\d{4}-\d{1}$/', $row[$ordercol], $matches);
		if (!$matches)
			continue;
		
		$amount = str_replace (',', '.', str_replace (' ', '', $row[$summcol]));
		$commision = str_replace (',', '.', str_replace (' ', '', $row[$commcol]));
		$stornoamount = str_replace (',', '.', str_replace (' ', '', $row[$stornosummcol]));
		$stornocommision = str_replace (',', '.', str_replace (' ', '', $row[$stornocommcol]));
		
		$payments[] = array (
			'orderNumber' => $row[$ordercol],
			'orderNumber2' => substr ($row[$ordercol], 0, -2),
			'number' => '',
			'incomingNumber' => $row[$ordercol] . '|' . $row[$itemcol],
			'incomingDate' => date_create_from_format ('d.m.Y', $row[$datecol])->format('Y-m-d'),
			'date' => $date->format('Y-m-d'),
			'amount' => (int)$amount !== 0 ? (int)$amount : -(int)$stornoamount,
			'trComm' => (float)$commision !== 0 ? (float)$commision : -(float)$stornocommision,
			'paymentType' => $row[$itemcol]
		);
		
	}
	// fill orders
 	//Orders::findOrders($payments);
	echo json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
