<?php
	function getNextCol ($currentCol, $shift)
	{
		$targetRowArray = array();
		$count = 0;
		$currentColArray = array_reverse (str_split ($currentCol));
		foreach ($currentColArray as $key => $char)
		{
			$code = ord ($char) - 64;
			$count = $count + $code * pow (26, $key);
		}
		
		$count += $shift;
		
		while ($count != 0)
		{
			$targetColArray[] = chr (($count % 26 ? $count % 26 : 26) + 64);
			$count = $count % 26 ? intdiv ($count, 26) : intdiv ($count, 26) - 1;
		}
		return implode (array_reverse($targetColArray));		
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
	if($imageFileType != "xlsx" && $imageFileType != "xls") {
		$str .= "Только файлы xlsx и xls могут быть загружены.<br>";
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
	
	if ($imageFileType == "xlsx")
		$reader = IOFactory::createReader("Xlsx");
	else if ($imageFileType == "xls")
		$reader = IOFactory::createReader("Xls");
	
	$spreadsheet = $reader->load($_SERVER['DOCUMENT_ROOT'] . '/finances/uploads/' . $_FILES["file"]["name"]);
	$worksheets = $spreadsheet->getAllSheets();
	
	$payments = array();
	$payments['fileInfo']['fileName'] = $_FILES["file"]["name"];
	$commission1 = (float)0;
	$commission2 = (float)0;
	$commission3 = (float)0;
	$commission4 = (float)0;
	foreach ($worksheets[0]->getRowIterator() as $row)
	{
		if (!isset ($startRow))
		{
			foreach ($row->getCellIterator() as $cell)
			{
				//$logger -> write ('cell - ' . $row->getRowIndex() . ',' . $cell->getColumn() . ' - ' . $cell->getCalculatedValue());
				
				if (strpos($cell->getCalculatedValue(), 'Продавец:') !== false)
					$payments['fileInfo']['shop'] = $worksheets[0]->getCell(getNextCol ($cell->getColumn(), 2) . $row->getRowIndex())->getCalculatedValue();
				if (strpos($cell->getCalculatedValue(), 'Договор:') !== false)
					$payments['fileInfo']['contract'] = str_replace('Договор: ', '', $worksheets[0]->getCell(getNextCol ($cell->getColumn(), 2) . $row->getRowIndex())->getCalculatedValue());
				if (strpos($cell->getCalculatedValue(), 'Номер п/п:') !== false)
					$payments['fileInfo']['paymentNumber'] = $worksheets[0]->getCell(getNextCol ($cell->getColumn(), 2) . $row->getRowIndex())->getCalculatedValue();
				if (strpos($cell->getCalculatedValue(), 'Дата п/п:') !== false)
					$payments['fileInfo']['paymentDate'] = $worksheets[0]->getCell(getNextCol ($cell->getColumn(), 2) . $row->getRowIndex())->getCalculatedValue();
				if (strpos($cell->getCalculatedValue(), 'Перечислено:') !== false)
					$payments['fileInfo']['payments'] = $worksheets[0]->getCell(getNextCol ($cell->getColumn(), 2) . $row->getRowIndex())->getCalculatedValue();
				//if (strpos($cell->getCalculatedValue(), 'Заказов оформлено') !== false)
					//$fileInfo['ordersTotal'] = $worksheets[0]->getCell($cell->getColumn() . ($row->getRowIndex() + 1))->getCalculatedValue();
				//if (strpos($cell->getCalculatedValue(), 'Общая выручка по заказам') !== false)
					//$fileInfo['amountTotal'] = $worksheets[0]->getCell($cell->getColumn() . ($row->getRowIndex() + 1))->getCalculatedValue();
				if (strpos($cell->getCalculatedValue(), 'Отправление Маркетплейс') !== false)
					$orderCol = $cell->getColumn();
				if (strpos($cell->getCalculatedValue(), 'Классификатор') !== false)
					$classCol = $cell->getColumn();
				if (strpos($cell->getCalculatedValue(), 'Долг компании') !== false)
				{
					$mpCol = $cell->getColumn();
					$startRow = $row->getRowIndex();
				}
				if (strpos($cell->getCalculatedValue(), 'Долг продавца') !== false)
				{
					$sellerCol = $cell->getColumn();
					$startRow = $row->getRowIndex();
				}
			}
			continue;
		}
		
		preg_match ('/^\d{9,10}$/', $worksheets[0]->getCell($orderCol . ($row->getRowIndex()))->getCalculatedValue(), $matches);
		
		if (!$matches)
			continue;
		
		$date = date_create_from_format ('d.m.Y', $payments['fileInfo']['paymentDate']);
		
		if ($worksheets[0]->getCell($classCol . $row->getRowIndex())->getCalculatedValue() == 'Товары продавцов')
		{
			$amount = (int)$worksheets[0]->getCell($mpCol . $row->getRowIndex())->getCalculatedValue() ? (int)$worksheets[0]->getCell($mpCol . $row->getRowIndex())->getCalculatedValue() : - (int)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue();
			$paymentType = '1';
		}
		if ($worksheets[0]->getCell($classCol . $row->getRowIndex())->getCalculatedValue() == 'Комиссия за доставку')
		{
			$amount = (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() ? (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() : - (float)$worksheets[0]->getCell($mpCol . $row->getRowIndex())->getCalculatedValue();
			$paymentType = '3';
			$commission1 += $amount;
		}
		if ($worksheets[0]->getCell($classCol . $row->getRowIndex())->getCalculatedValue() == 'Комиссия за товарную категорию')
		{
			$amount = (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() ? (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() : - (float)$worksheets[0]->getCell($mpCol . $row->getRowIndex())->getCalculatedValue();
			$paymentType = '4';
			$commission2 += $amount;
			$logger->write ('order - ' . $worksheets[0]->getCell($orderCol . ($row->getRowIndex()))->getCalculatedValue());
		}
		if ($worksheets[0]->getCell($classCol . $row->getRowIndex())->getCalculatedValue() == 'Комиссия за транзакции')
		{
			$amount = (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() ? (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() : - (float)$worksheets[0]->getCell($mpCol . $row->getRowIndex())->getCalculatedValue();
			$paymentType = '5';
			$commission3 += $amount;
		}
		if ($worksheets[0]->getCell($classCol . $row->getRowIndex())->getCalculatedValue() == 'Комиссия за перенос даты отгрузки')
		{
		    $amount = (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() ? (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() : - (float)$worksheets[0]->getCell($mpCol . $row->getRowIndex())->getCalculatedValue();
		    $paymentType = '6';
		    $commission3 += $amount;
		}
		if ($worksheets[0]->getCell($classCol . $row->getRowIndex())->getCalculatedValue() == 'Комиссия за логистику')
		{
		    $amount = (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() ? (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() : - (float)$worksheets[0]->getCell($mpCol . $row->getRowIndex())->getCalculatedValue();
		    $paymentType = '7';
		    $commission1 += $amount;
		}
		if ($worksheets[0]->getCell($classCol . $row->getRowIndex())->getCalculatedValue() == 'Комиссия за отказ при комплектации')
		{
		    $amount = (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() ? (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() : - (float)$worksheets[0]->getCell($mpCol . $row->getRowIndex())->getCalculatedValue();
		    $paymentType = '8';
		    $commission3 += $amount;
		}
		if ($worksheets[0]->getCell($classCol . $row->getRowIndex())->getCalculatedValue() == 'Вознаграждение за предоставление поощрения')
		{
			$amount = (int)$worksheets[0]->getCell($mpCol . $row->getRowIndex())->getCalculatedValue() ? (int)$worksheets[0]->getCell($mpCol . $row->getRowIndex())->getCalculatedValue() : - (int)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue();
			$commission4 += $amount;
			$paymentType = '2';
		}
		if ($worksheets[0]->getCell($classCol . $row->getRowIndex())->getCalculatedValue() == 'Вознаграждение оператора ПЛ')
		{
			$amount = (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() ? (float)$worksheets[0]->getCell($sellerCol . $row->getRowIndex())->getCalculatedValue() : - (float)$worksheets[0]->getCell($mpCol . $row->getRowIndex())->getCalculatedValue();
			$paymentType = '6';
			$commission4 += $amount;
		}
		
		$payments['payments'][] = array (
			'orderNumber' => $worksheets[0]->getCell($orderCol . ($row->getRowIndex()))->getCalculatedValue(),
			'incomingNumber' => $payments['fileInfo']['paymentNumber'],
			'incomingDate' => $date->format('Y-m-d'),
			'date' => $date->format('Y-m-d'),
			'amount' => $amount,
			'paymentType' => $paymentType
		);
		
	}
	
	$orders = array_unique(array_column ($payments['payments'], 'orderNumber'));
	$logger->write ('02-orders - ' . json_encode ($orders, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	$payments['fileInfo']['totalOrders'] = count ($orders);
	//$payments['fileInfo']['totalSum'] = array_sum(array_column ($payments['payments'], 'amount'));
	$payments['fileInfo']['totalCommission1'] = $commission1;
	$payments['fileInfo']['totalCommission2'] = $commission2;
	$payments['fileInfo']['totalCommission3'] = $commission3;
	$payments['fileInfo']['totalCommission4'] = $commission4;
	
	//$logger->write ('03-payments - ' . json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	echo json_encode (json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
?>
