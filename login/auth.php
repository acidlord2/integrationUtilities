<?php
	session_start();
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	if(empty($_SESSION["authenticated"]) || $_SESSION["authenticated"] != 'true') {
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
		$host = $_SERVER['HTTP_HOST'];
		$base_url = $protocol . $host . '/';
		
		header('Location: ' . $base_url . 'login/login.php?url=' . substr ($_SERVER['REQUEST_URI'], 1));
	}
	
?>

