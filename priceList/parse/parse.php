<?php

	require_once($_SERVER['DOCUMENT_ROOT'] . '/priceList/Classes/ParsePrices.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('priceList - yandex - parse.log');
	
	$target_dir = $_SERVER['DOCUMENT_ROOT'] . '/priceList/uploads/';
	
	
	$target_file = $target_dir . basename($_FILES["file"]["name"]);
	$uploadOk = 1;
	$str = '';
	$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
	$logger->write (__LINE__ . ' target_file - ' . $target_file);

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
	
	$ParsePrices = new ParsePrices();
	$ParsePrices->parseFile($target_file);
	$prices = array (
	    'fileInfo' => $ParsePrices->getFileInfo(),
	    'prices' => $ParsePrices->getData()
	);
	
	$logger->write (__LINE__ . ' prices - ' . json_encode ($prices, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	echo json_encode (json_encode ($prices, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
?>
