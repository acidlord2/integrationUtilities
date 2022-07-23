<?php
	$url = file_get_contents('php://input');
	//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	//$logger = new Log ('tmp.log');

	//$logger->write ($url);
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
	$out = curl_exec($curl);
	//$logger->write ($out);
	curl_close($curl);
	echo ($out);
	
?>

