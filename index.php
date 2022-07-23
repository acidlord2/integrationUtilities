<?php
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	
	ini_set("display_errors", 1);
	error_reporting(E_ALL);
	//require_once('classes/log.php')
	//var $log = new Log("log.txt");
//	log.write('aaa');
	
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/users.php');

	if ($userRoles = Users::getUserRoles($_SESSION["user"])) {
?>

<html>
	<head>
		<title>Помощь по складу</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=2" />
	</head>
	<body style="margin:0;padding:0">
		<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
		<div align="center">
			<div id="header">
				<div style="margin-bottom: 13px; margin-top: 14px; font-size: 200%; color:#F7971D;">
					Главное меню
				</div>
			</div>
			<div class = "button-wraper">
<?php 
	while ($userRole =  mysqli_fetch_assoc($userRoles)) {
?>
				<?php if ($userRole['role_id'] == 1) { ?>
				<button class = "main-menu-button" onclick = "window.location.href='sklad.php'">
					Комплектовщик
				</button>	
				<?php } if ($userRole['role_id'] == 2) { ?>
				<button class = "main-menu-button" onclick = "window.location.href='curier.php'">
					Курьер
				</button>
				<?php } if ($userRole['role_id'] == 3) { ?>
				<button class = "main-menu-button" onclick = "window.location.href='integration/integration.php'">
					Интеграции
				</button>
				<?php } if ($userRole['role_id'] == 4) { ?>
				<button class = "main-menu-button" onclick = "window.location.href='priceList/priceList'">
					Изменение цен
				</button>
				<?php } if ($userRole['role_id'] == 5) { ?>
				<button class = "main-menu-button" onclick = "window.location.href='returns/returns.php'">
					Возвраты
				</button>
				<?php } if ($userRole['role_id'] == 6) { ?>
				<button class = "main-menu-button" onclick = "window.location.href='marketing.php'">
					Анализатор цен
				</button>
				<?php } if ($userRole['role_id'] == 1) { ?>
				<button class = "main-menu-button" onclick = "window.location.href='feedbacks.php'">
					Поиск отзывов
				</button>
				<?php } if ($userRole['role_id'] == 1) { ?>
				<button class = "main-menu-button" onclick = "window.location.href='shipping2/shiplist'">
					Отгрузка
				</button>
				<?php } if ($userRole['role_id'] == 7) { ?>
				<button class = "main-menu-button" onclick = "window.location.href='print/printList'">
					Печать заказов
				</button>
				<?php } if ($userRole['role_id'] == 1 || $userRole['role_id'] == 3) { ?>
				<button class = "main-menu-button" onclick = "window.location.href='finances/finance'">
					Разноска финансов
				</button>
				<?php } if ($userRole['role_id'] == 8) { ?>
				<button class = "main-menu-button" onclick = "window.location.href='packings/packList'">
					Сборочный лист
				</button>
				<?php } ?>			
<?php } ?>			
			</div>
		</div>
	</body>
</html>
		
<?php
	}
?>		
	

