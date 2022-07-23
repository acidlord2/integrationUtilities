<?php
	session_start();
	require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
	if(empty($_SESSION["authenticated"]) || $_SESSION["authenticated"] != 'true') {
		header('Location: ' . HTTP_SERVER . 'login/login.php?url=' . substr ($_SERVER['REQUEST_URI'], 1));
	}
	
?>

