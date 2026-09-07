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
		<title>Интеграции</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=5" />
		<script type="text/javascript" src="/js/integration.js"></script>
	</head>
	<body style="margin:0;padding:0">
		<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
		<div align="center">
			<div id="header">
				<div style="margin-bottom: 13px; margin-top: 14px; font-size: 200%; color:#F7971D;">
					Сервисы интеграции
				</div>
			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">Яндекс</p>
				<button class = "integration-button" onclick = "window.open('/beru-summit/updateStock', '_blank')">
					Обновление остатков Саммит
				</button>
				<button class = "integration-button" onclick = "window.open('/beru-summit/updatePrices', '_blank')">
					Обновление цен Саммит
				</button>
				<button class = "integration-button" onclick = "window.open('/beru-kosmos/updateStock', '_blank')">
					Обновление остатков Космос
				</button>
				<button class = "integration-button" onclick = "window.open('/beru-kosmos/updatePrices', '_blank')">
					Обновление цен Космос
				</button>
				<button class = "integration-button" onclick = "window.open('/beru-ullozza/updateStock', '_blank')">
					Обновление остатков Юлло
				</button>
				<button class = "integration-button" onclick = "window.open('/beru-ullozza/updatePrices', '_blank')">
					Обновление цен Юлло
				</button>
				<button class = "integration-button" onclick = "window.open('/ms-duplicates/remove-duplicates', '_blank')">
					Удаление дублей заказов
				</button>
			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">Интеграция с Ozon Юлло</p>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ozonUllo/updateProducts.php', '_blank')">
					Обновить остатки и цены Ozon
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ozonUllo/getNewOrders.php', '_blank')">
					Получить новые заказы Ozon
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ozonUllo/cancelOrders', '_blank')">
					Обновить статус по отмененным Ozon
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ozonUllo/updateBarcodes', '_blank')">
					Обновить Штрихкоды заказов
				</button>
			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">Интеграция с Ozon Каори</p>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ozonKaori/updateProducts.php', '_blank')">
					Обновить остатки и цены Ozon
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ozonKaori/getNewOrders.php', '_blank')">
					Получить новые заказы Ozon
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ozonKaori/cancelOrders', '_blank')">
					Обновить статус по отмененным Ozon
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ozonKaori/updateBarcodes', '_blank')">
					Обновить Штрихкоды заказов
				</button>
			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">Интеграция с Wildberries</p>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberries/kosmos/updateStock', '_blank')">
					Обновить остатки WB Kosmos
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberries/kosmos/updatePrices', '_blank')">
					Обновить цены WB Kosmos
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberries/kosmos/getNewOrders', '_blank')">
					Загрузить заказы WB Kosmos
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberriesKosmos/updateBarcodes', '_blank')">
					Загрузить штрихкоды WB Kosmos
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberries/ullo/updateStock', '_blank')">
					Обновить остатки WB Ullo
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberries/ullo/updatePrices', '_blank')">
					Обновить цены WB Ullo
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberries/ullo/getNewOrders', '_blank')">
					Загрузить заказы WB Ullo
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberriesUllo/updateBarcodes', '_blank')">
					Загрузить штрихкоды WB Ullo
				</button>
			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">Интеграция с сайтом ccd77</p>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ccd77/pullMissingOrders.php', '_blank')">
					Недостающие заказы ccd77
				</button>
			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">Диагностика</p>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/diagnostics/settings.php', '_blank')">
					Настройки сервера
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/diagnostics/apiHealth.php', '_blank')">
					Проверка доступности API
				</button>
			</div>
		</div>
		<script type="text/javascript" src="/js/upload.js"></script>
	</body>
</html>

