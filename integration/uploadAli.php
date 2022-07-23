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
	$target_file = $target_dir . basename($_FILES["fileToUploadAli"]["name"]);
	$uploadOk = 1;
	$str = '';
	$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('uploadAli.log');
	$logger->write ('target_file - ' . $target_file);

	// Allow certain file formats
	if($imageFileType != "csv") {
		$str .= "Только файлы csv могут быть загружены.<br>";
		$uploadOk = 0;
	}
	// Check if $uploadOk is set to 0 by an error
	if ($uploadOk == 0) {
		$str .= "Файл не загружен.<br>";
	// if everything is ok, try to upload file
	} else {
		if (move_uploaded_file($_FILES["fileToUploadAli"]["tmp_name"], $target_file)) {
			$str .= "Файл загружен ". basename( $_FILES["fileToUploadAli"]["name"]). " успешно загружен.<br>";
		} else {
			$str .= "Что-то пошло не так.<br>";
			trigger_error('Die', E_ERROR);		
		}
	}
	
	require_once($_SERVER['DOCUMENT_ROOT'] . '/SimpleExcel/SimpleExcel.php');
	use SimpleExcel\SimpleExcel;
	$excel = new SimpleExcel('CSV');
	$excel->parser->setDelimiter(',');
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
	$logger->write ('cells - ' . json_encode($cells, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ));
	$payments = array();
	$startrow = false;
	// parse file and update order
	foreach ($cells as $row)
	{
		//$logger->write ('row - ' . json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ));
		//$logger->write ('startrow - ' . json_encode($startrow, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ));
		//$logger->write ('row[0] - ' . json_encode($row[0], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ));
		//$logger->write ('row[1] - ' . json_encode($row[1], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ));

		if (!$startrow && $row[0] == 'Время' && $row[1] == 'Тип')
		{
			$startrow = true;
			continue;
		}
		else if (!$startrow) 
			continue;
		if ($startrow)
		{
			$pattern = '/(.*)(\t)(.*)(\t)(.*)(\t)(.*)=([0-9]+)/';
			$result = preg_match($pattern, $row[7], $matches);
			if ($result === 1)
			{
				$logger->write ('matches - ' . json_encode($matches, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ));
				$date = date_create_from_format ('Y-m-d', substr($row[0], 0, 10));
				if ($row[1] == 'Распределение средств')
					$payments[] = array (
						'orderNumber' => $matches[8],
						'number' => str_replace("\t", '', $row[2]),
						'incomingNumber' => str_replace("\t", '', $row[2]),
						'incomingDate' => $date->format('Y-m-d'),
						'date' => $date->format('Y-m-d'),
						'trComm' => -(float)$row[4],
						'paymentType' => 1
					);
				else
					$payments[] = array (
						'orderNumber' => $matches[8],
						'number' => str_replace("\t", '', $row[2]),
						'incomingNumber' => str_replace("\t", '', $row[2]),
						'incomingDate' => $date->format('Y-m-d'),
						'date' => $date->format('Y-m-d'),
						'amount' => (float)$row[4],
						'paymentType' => 1
					);
			}
			else
				$logger->write ('matches (no match) - ' . json_encode($row[7], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ));
			
		}
		
	}
	$logger->write ('payments - ' . json_encode($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ));
	echo json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
