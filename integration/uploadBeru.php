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
	$target_file = $target_dir . basename($_FILES["fileToUploadBeru"]["name"]);
	$uploadOk = 1;
	$str = '';
	$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('uploadBeru.log');
	$logger->write ('uploadBeru.target_file - ' . $target_file);

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
		if (move_uploaded_file($_FILES["fileToUploadBeru"]["tmp_name"], $target_file)) {
			$str .= "Файл загружен ". basename( $_FILES["fileToUploadBeru"]["name"]). " успешно загружен.<br>";
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
	$logger->write ('uploadBeru.cells - ' . json_encode($cells, JSON_UNESCAPED_SLASHES));
	
	$return = array();
	$payments = array();
	$j = 0;
	$summcol = false;
	// parse file and update order
	foreach ($cells as $i => $row)
	{
		
			
		if (!$summcol)
		{
			foreach ($row as $cellnum => $celldata)
				if (strpos($celldata, "Платёж покупателя") !== false)
					$summcol = $cellnum;
			if (!$summcol)
				$summcol = 16;
		}
		
		//check order number
		preg_match ('/^\d{8,9}$/', $row[0], $matches);
		if (!$matches)
			continue;
			
		for ($l = $summcol; $l <= $summcol + 11; $l +=5)
			if ($cells[$i][$l])
			{
				$j++;
				//$logger->write ('cells[$i][$l+2] - ' . $cells[$i][$l+2] . ', l = ' . $l . ', i = ' . $i);
				$date = date_create_from_format ('d.m.Y', $cells[$i][$l+2]);
				if ($date)
					$payments[] = array (
						'orderNumber' => $cells[$i][0],
						'number' => $cells[$i][$l+1] . '-' . (string)$j,
						'incomingNumber' => $cells[$i][$l+1],
						'incomingDate' => $date->format('Y-m-d'),
						'date' => $date->format('Y-m-d'),
						'amount' => (int)$cells[$i][$l],
						'paymentType' => $cells[$i][3] . '-' . (($l - ($summcol - 5)) / 5)
					);
			}
		for ($l = $summcol + 15; $l <= $summcol + 26; $l +=5)
			if ($cells[$i][$l])
			{
				$j++;
				//$logger->write ('cells[$i][$l+2] - ' . $cells[$i][$l+2] . ', l = ' . $l . ', i = ' . $i);
				$date = date_create_from_format ('d.m.Y', $cells[$i][$l+2]);
				if ($date)
					$payments[] = array (
						'orderNumber' => $cells[$i][0],
						'number' => $cells[$i][$l+1] . '-' . (string)$j,
						'incomingNumber' => $cells[$i][$l+1],
						'incomingDate' => $date->format('Y-m-d'),
						'date' => $date->format('Y-m-d'),
						'amount' => - (int)$cells[$i][$l],
						'paymentType' => $cells[$i][3] . '-' . (($l - ($summcol - 20)) / 5)
					);
			}
	}
	// fill orders
 	//Orders::findOrders($payments);
	echo json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
