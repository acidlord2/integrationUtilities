<?php
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
/* 	
	
	ini_set("display_errors", 1);
	error_reporting(E_ALL);
	//require_once('classes/log.php')
	//var $log = new Log("log.txt");
//	log.write('aaa');
	
	require_once('classes/users.php');
	require_once('header.php');

	if ($userRoles = Users::getUserRoles($_SESSION["user"])) { */
?>

<html>
	<head>
		<title>Wildberries</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=5" />
	</head>
	<body style="margin:0;padding:0">
		<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
		<div align="center">
			<div class = "integration-block">
				<p class = "integration-block-header">Загрузка таблицы соответствия товаров Wildberries</p>
				<form id="upload_form" action="/wildberries/upload.php" method="post" enctype="multipart/form-data" target="upload_iframe">
					Выберите CSV для загрузки:
					<input type="file" name="fileToUpload" id="fileToUpload">
					<input type="button" id="uploadSubmit" name="uploadSubmit" value="Загрузить" onclick="redirectWildberries()">
					<div id= "process" class= "progress"></div>
					<iframe id="upload_iframe" class="upload_iframe" name="upload_iframe" src=""></iframe>
					<div id="loading" class="loading"></div>
					<div id="loading2" class="loading2"></div>
				</form>			
			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">Выгрузка налогов для товаров</p>
				<input type="button" id="downloadWBvat" name="downloadWBvat" value="Загрузить" onclick="downloadWBvat()">
			</div>
		</div>
		<script type="text/javascript" src="/js/upload.js"></script>
	</body>
</html>


