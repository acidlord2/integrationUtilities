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
				<p class = "integration-block-header">4cleaning</p>
				<button class = "integration-button" onclick = "window.open('https://4cleaning.ru/index.php?route=extension/prices/impprices', '_blank')">
					Обновить цены 4cleaning
				</button>
				<button class = "integration-button" onclick = "window.open('https://4cleaning.ru/index.php?route=extension/impquan/impquan', '_blank')">
					Обновить остатки 4cleaning
				</button>
				<button class = "integration-button" onclick = "window.open('https://4cleaning.ru/index.php?route=extension/impproductdata/imp', '_blank')">
					Обновить штрихкоды и весогабариты товаров 4cleaning
				</button>
			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">10kids</p>
				<button class = "integration-button" onclick = "window.open('https://10kids.ru/index.php?route=extension/prices/impprices', '_blank')">
					Обновить цены 10kids
				</button>
				<button class = "integration-button" onclick = "window.open('https://10kids.ru/index.php?route=extension/impquan/impquan', '_blank')">
					Обновить остатки 10kids
				</button>
				<button class = "integration-button" onclick = "window.open('https://10kids.ru/index.php?route=extension/impquan/impbarcode', '_blank')">
					Обновить штрихкоды товаров 10kids
				</button>
			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">Яндекс</p>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ms-duplicates/remove-duplicates', '_blank')">
					Удаление дублей заказов
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/beru-summit/updateStock', '_blank')">
					Обновление остатков Саммит
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/beru-summit/updatePrices', '_blank')">
					Обновление цен Саммит
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/beru-kosmos/updateStock', '_blank')">
					Обновление остатков Космос
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/beru-kosmos/updatePrices', '_blank')">
					Обновление цен Космос
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/beru-ullozza/updateStock', '_blank')">
					Обновление остатков Юлло
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/beru-ullozza/updatePrices', '_blank')">
					Обновление цен Юлло
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
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberriesKosmos/updateStock', '_blank')">
					Обновить остатки WB Kosmos
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberriesKosmos/updatePrices', '_blank')">
					Обновить цены WB Kosmos
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberriesKosmos/getNewOrders', '_blank')">
					Загрузить заказы WB Kosmos
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberriesUllo/updateStock', '_blank')">
					Обновить остатки WB Ullo
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberriesUllo/updatePrices', '_blank')">
					Обновить цены WB Ullo
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberriesUllo/getNewOrders', '_blank')">
					Загрузить заказы WB Ullo
				</button>
			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">Интеграция со Спортмастер</p>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/sportmaster/Ullo/updateStock', '_blank')">
					Обновить остатки Спортмастер Ullo
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/sportmaster/Ullo/updatePrices', '_blank')">
					Обновить цены Спортмастер Ullo
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/sportmaster/Ullo/importOrders', '_blank')">
					Загрузить заказы Спортмастер Ullo
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/sportmaster/Ullo/cancelOrders', '_blank')">
					Отмененные заказы Спортмастер Ullo
				</button>
			</div>
		</div>
		<script type="text/javascript" src="/js/upload.js"></script>
		<script>
			function openImgUrl () {
				var period = document.getElementById("period").value;
				var shipping = document.getElementById("shipping").value;
				var baseUrl = 'https://4cleaning.ru/index.php?route=extension/importorders/img';
				if (period != "all") {
					baseUrl = baseUrl + '&from=' + period + '&to=' + period;
				}
				baseUrl = baseUrl + '&status=' + shipping;
				window.open(baseUrl, '_blank') ;
			}
			//document.getElementById("submit").onclick = function (e) {
			//	e.preventDefault();
			//}
		</script>
	</body>
</html>

