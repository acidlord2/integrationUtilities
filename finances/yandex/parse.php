<?php

	require_once($_SERVER['DOCUMENT_ROOT'] . '/finances/yandex/parsePayment.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/payments.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('finances - yandex - parse.log');
	
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
	
	$parsePayment = new ParsePayment();
	$parsePayment->parseFile($_SERVER['DOCUMENT_ROOT'] . '/finances/uploads/' . $_FILES["file"]["name"]);
	$payments = array (
		'fileInfo' => array_merge ($parsePayment->getFileInfo(), $parsePayment->getTotals()),
		'payments' => array_merge ($parsePayment->getChargeData(), $parsePayment->getStornoData())
	);
	
	$logger->write ('02-payments - ' . json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	echo json_encode (json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
?>
