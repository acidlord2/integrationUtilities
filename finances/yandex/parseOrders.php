<?php

	function getNextCol ($currentRow, $shift)
	{
		$targetRowArray = array();
		$count = 0;
		$currentRowArray = array_reverse (str_split ($currentRow));
		foreach ($currentRowArray as $key => $char)
		{
			$code = ord ($char) - 64;
			$count = $count + $code * pow (26, $key);
		}
		
		$count += $shift;
		
		while ($count != 0)
		{
			$targetRowArray[] = chr (($count % 26 ? $count % 26 : 26) + 64);
			$count = $count % 26 ? intdiv ($count, 26) : intdiv ($count, 26) - 1;
		}
		return implode (array_reverse($targetRowArray));		
	}

	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/payments.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('finances-yandex-parse.log');
	
	$target_dir = $_SERVER['DOCUMENT_ROOT'] . '/finances/uploads/';
	
	
	$target_file = $target_dir . basename($_FILES["file"]["name"]);
	$uploadOk = 1;
	$str = '';
	$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
	$logger->write ('01-target_file - ' . $target_file);

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
		if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
			$str .= "Файл загружен ". basename( $_FILES["file"]["name"]). " успешно загружен.<br>";
		} else {
			$str .= "Что-то пошло не так.<br>";
			trigger_error('Die', E_ERROR);		
		}
	}

	require_once($_SERVER['DOCUMENT_ROOT'] . '/PhpSpreadsheet/vendor/autoload.php');	
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\IOFactory;

	$reader = IOFactory::createReader("Xlsx");
	$spreadsheet = $reader->load($_SERVER['DOCUMENT_ROOT'] . '/finances/uploads/' . $_FILES["file"]["name"]);
	$worksheets = $spreadsheet->getAllSheets();
	
	$payments = array();
	$payments['fileInfo']['fileName'] = $_FILES["file"]["name"];
	$cols = array();
	$sCols = array();
	foreach ($worksheets[0]->getRowIterator() as $row)
	{
		if (!isset ($startRow) || $startRow + 2 > $row->getRowIndex())
		{
			foreach ($row->getCellIterator() as $cell)
			{
				//$logger -> write ('cell - ' . $row->getRowIndex() . ',' . $cell->getColumn() . ' - ' . $cell->getCalculatedValue());
				
				if (strpos($cell->getCalculatedValue(), 'Магазин') !== false)
					$payments['fileInfo']['shop'] = str_replace('Магазин: ', '', $cell->getCalculatedValue());
				if (strpos($cell->getCalculatedValue(), 'За период') !== false)
					$payments['fileInfo']['period'] = $cell->getCalculatedValue();
				//if (strpos($cell->getCalculatedValue(), 'Заказов оформлено') !== false)
					//$fileInfo['ordersTotal'] = $worksheets[0]->getCell($cell->getColumn() . ($row->getRowIndex() + 1))->getCalculatedValue();
				//if (strpos($cell->getCalculatedValue(), 'Общая выручка по заказам') !== false)
					//$fileInfo['amountTotal'] = $worksheets[0]->getCell($cell->getColumn() . ($row->getRowIndex() + 1))->getCalculatedValue();
				if (strpos($cell->getCalculatedValue(), 'Номер заказа') !== false)
					$orderCol = $cell->getColumn();
				if (strpos($cell->getCalculatedValue(), 'Ваш SKU') !== false)
					$skuCol = $cell->getColumn();
				if (strpos($cell->getCalculatedValue(), 'Платёж покупателя') !== false)
				{
					$cols['1'] = $cell->getColumn();
					$startRow = $row->getRowIndex();
				}
				if (strpos($cell->getCalculatedValue(), 'Платёж за скидку маркетплейса') !== false)
					$cols['2'] = $cell->getColumn();
				if (strpos($cell->getCalculatedValue(), 'Платёж за скидку по бонусам СберСпасибо') !== false)
					$cols['3'] = $cell->getColumn();
				if (strpos($cell->getCalculatedValue(), 'Платёж за скидку по баллам Яндекс.Плюса') !== false)
					$cols['4'] = $cell->getColumn();
				if (strpos($cell->getCalculatedValue(), 'Возврат платежа покупателя') !== false)
					$sCols['1'] = $cell->getColumn();
				if (strpos($cell->getCalculatedValue(), 'Возврат платежа за скидку маркетплейса') !== false)
					$sCols['2'] = $cell->getColumn();
				if (strpos($cell->getCalculatedValue(), 'Возврат платежа за скидку по бонусам СберСпасибо') !== false)
					$sCols['3'] = $cell->getColumn();
				if (strpos($cell->getCalculatedValue(), 'Возврат платежа за скидку по баллам Яндекс.Плюса') !== false)
					$sCols['4'] = $cell->getColumn();
			}
			continue;
		}
		
		preg_match ('/^\d{8,9}$/', $worksheets[0]->getCell($orderCol . ($row->getRowIndex()))->getCalculatedValue(), $matches);
		
		if (!$matches)
			continue;
		
		foreach ($cols as $key=>$col)
		{
			$date = date_create_from_format ('d.m.Y', $worksheets[0]->getCell(getNextCol ($col, 2) . $row->getRowIndex())->getCalculatedValue());
			if (!$date)
				continue;
				$payments['payments'][] = array (
					'orderNumber' => $worksheets[0]->getCell($orderCol . ($row->getRowIndex()))->getCalculatedValue(),
					'incomingNumber' => $worksheets[0]->getCell(getNextCol ($col, 1) . $row->getRowIndex())->getCalculatedValue(),
					'incomingDate' => $date->format('Y-m-d'),
					'date' => $date->format('Y-m-d'),
					'amount' => (int)$worksheets[0]->getCell($col . $row->getRowIndex())->getCalculatedValue(),
					'paymentType' => $worksheets[0]->getCell($skuCol . $row->getRowIndex())->getCalculatedValue() . '-' . $key
				);
		}
		
		foreach ($sCols as $key=>$col)
		{
			$date = date_create_from_format ('d.m.Y', $worksheets[0]->getCell(getNextCol ($col, 2) . $row->getRowIndex())->getCalculatedValue());
			if (!$date)
				continue;
				$payments['payments'][] = array (
					'orderNumber' => $worksheets[0]->getCell($orderCol . ($row->getRowIndex()))->getCalculatedValue(),
					'incomingNumber' => $worksheets[0]->getCell(getNextCol ($col, 1) . $row->getRowIndex())->getCalculatedValue(),
					'incomingDate' => $date->format('Y-m-d'),
					'date' => $date->format('Y-m-d'),
					'amount' => - (int)$worksheets[0]->getCell($col . $row->getRowIndex())->getCalculatedValue(),
					'paymentType' => $worksheets[0]->getCell($skuCol . $row->getRowIndex())->getCalculatedValue() . '-' . $key
				);
			
		}
	}
	
	$orders = array_unique(array_column ($payments['payments'], 'orderNumber'));
	$payments['fileInfo']['totalOrders'] = count ($orders);
	$payments['fileInfo']['totalSum'] = array_sum(array_column ($payments['payments'], 'amount'));
	
	//$logger->write ('02-payments - ' . json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	echo json_encode (json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
?>
