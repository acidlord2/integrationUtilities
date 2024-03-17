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
				<p class = "integration-block-header">Сбермегамаркет</p>
				<!-- <button class = "integration-button" onclick = "window.open('https://10kids.ru/index.php?route=extension/importorders/importorders10kids', '_blank')"> -->
				<button class = "integration-button" onclick = "getSberOrders(this)">
					Ипортировать заказы Каори
				</button>
				<button class = "integration-button" onclick = "window.open('https://10kids.ru/index.php?route=extension/goods/updatePrices', '_blank')">
					Обновить цены Каори
				</button>
				<button class = "integration-button" onclick = "window.open('https://10kids.ru/index.php?route=extension/goods/updateStocks', '_blank')">
					Обновить остатки Каори
				</button>
				<button class = "integration-button" onclick = "window.open('https://4cleaning.ru/index.php?route=extension/importorders/importorders', '_blank')">
					Ипортировать заказы Юлло
				</button>
				<button class = "integration-button" onclick = "window.open('https://4cleaning.ru/index.php?route=extension/goods/updatePrices', '_blank')">
					Обновить цены Юлло
				</button>
				<button class = "integration-button" onclick = "window.open('https://4cleaning.ru/index.php?route=extension/goods/updateStocks', '_blank')">
					Обновить остатки Юлло
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/sbmm-ast1/importOrders', '_blank')">
					Ипортировать заказы Альянс
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/sbmm-ast1/updatePricesStock', '_blank')">
					Обновить цены и остатки Альянс
				</button>
				<div id="sberInfo"></div>

			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">Яндекс</p>
				<button class = "integration-button" onclick = "window.open('http://kids-universe.ru/beru/readytoship', '_blank')">
					Подтверждение заказов Яндекс ЮЛЛО
				</button>
				<button class = "integration-button" onclick = "window.open('https://4cleaning.ru/index.php?route=extension/beru/readytoship', '_blank')">
					Подтверждение заказов Яндекс 4cleaning
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ms-duplicates/remove-duplicates', '_blank')">
					Удаление дублей заказов
				</button>
			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">Интеграция с Ozon Юлло</p>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ozon/updateProducts.php', '_blank')">
					Обновить остатки и цены Ozon
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ozon/getNewOrders.php', '_blank')">
					Получить новые заказы Ozon
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ozon/cancelOrder', '_blank')">
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
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ozonKaori/cancelOrder', '_blank')">
					Обновить статус по отмененным Ozon
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/ozonKaori/updateBarcodes', '_blank')">
					Обновить Штрихкоды заказов
				</button>
			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">Интеграция с Aliexpress</p>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/aliexpress/syncProducts.php', '_blank')">
					Синхронизировать товары (если на али добавлены)
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/aliexpress/updateQuantities', '_blank')">
					Обновить остатки
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/aliexpress/updatePrices', '_blank')">
					Обновить цены
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/aliexpress/importOrders', '_blank')">
					Загрузить заказы
				</button>
			</div>
			<div class = "integration-block">
				<p class = "integration-block-header">Интеграция с Wildberries</p>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberriesKaori/updateStock', '_blank')">
					Обновить остатки
				</button>
				<button class = "integration-button" onclick = "window.open('https://kids-universe.ru/wildberriesKaori/updatePrices', '_blank')">
					Обновить цены
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


