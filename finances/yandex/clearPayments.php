<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/paymentsMS.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('finances-yandex-clearPayments.log');
	
	$paymentsMSClass = new PaymentsMS ();
	
	$fileInfo = json_decode (file_get_contents('php://input'), true);
	$logger->write ('01-fileInfo - ' . json_encode ($fileInfo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	
	$payments = $paymentsMSClass->getPayments ('incomingNumber=' . 
	$fileInfo['incomingNumber'] . ';incomingDate=' . DateTime::createFromFormat('d.m.Y', $fileInfo['incomingDate'])->format('Y-m-d'));
	
	$logger->write ('02-payments - ' . json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

	$postData = array ();
	foreach ($payments as $payment)
	{
		$postData [] = array ('meta' => $payment ['meta']);
	}
	
	$paymentsMSClass->deletePayments($postData);
	
	//$logger->write ('02-payments - ' . json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	echo json_encode (json_encode ($payments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
?>
