<?php
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	
	ini_set("display_errors", 1);
	error_reporting(E_ALL);
	//require_once('classes/log.php')
	//var $log = new Log("log.txt");
//	log.write('aaa');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	
	if(isset($_GET['dateFrom']))
		$dateFrom = $_GET['dateFrom'];
	else
		$dateFrom = Date ('Y-m-d', strtotime('-2 months'));

	date_default_timezone_set('Europe/Moscow');
	if(isset($_GET['dateTo']))
		$dateTo = $_GET['dateTo'];
	else
		$dateTo = Date ('Y-m-d', strtotime('now'));
	
	if(isset($_GET['agent']))
		$agent = $_GET["agent"];
	else
		$agent = 'Goods';

	if(isset($_GET['organization']))
		$organization = $_GET["organization"];
	else
		$organization = '4cleaning';
	
	//$orders = Orders::getList($shippingDate, $website, $goodsFlag, $beruFlag);
?>
<html>
	<head>
		<title>Комплектовщик</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<script>
			if (location.search == "") {
				location.search = "?dateFrom=<?php echo $dateFrom; ?>&dateTo=<?php echo $dateTo; ?>&agent=<?php echo $agent; ?>&organization=<?php echo $organization; ?>";
			}
		</script>
		<link rel = "stylesheet" type = "text/css"  href = "../css/styles.css?v=1" />
	</head>
	<body>
		<div align="center">
			<div id="header">
				<?php require_once ($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
				<div class = "title">
					Список претензионных заказов
				</div>
				<div style="margin-bottom: 13px; margin-top: 14px;"> 
					Дата заказа с: <input type="date" id="dateFrom" data-date-format="DD.MM.YYYY" value="<?php echo $dateFrom; ?>">
					Дата заказа по: <input type="date" id="dateTo" data-date-format="DD.MM.YYYY" value="<?php echo $dateTo; ?>">
					Организация: <select id="organization" value="<?php echo $organization; ?>">
						<option value="4cleaning" <?php echo $agent=='4cleaning' ? ' selected' : ''; ?>>4cleaning</option>
						<option value="10kids" <?php echo $agent=='10kids' ? ' selected' : ''; ?>>10kids</option>
						<option value="Ullo" <?php echo $agent=='Ullo' ? ' selected' : ''; ?>>Ullo</option>
						<option value="Kaori" <?php echo $agent=='Kaori' ? ' selected' : ''; ?>>Kaori</option>
					</select>
					Контрагент: <select id="agent" value="<?php echo $agent; ?>">
						<option value="Goods" <?php echo $agent=='Goods' ? ' selected' : ''; ?>>Goods</option>
						<option value="Beru" <?php echo $agent=='Beru' ? ' selected' : ''; ?>>Beru</option>
						<option value="Ozon" <?php echo $agent=='Ozon' ? ' selected' : ''; ?>>Ozon</option>
					</select>
					<button type="button" id = "filter_button" onclick="filterOrders()">Фильтр</button>			
				</div>
				<div id="total" style="margin-bottom: 5px; margin-top: 5px;"> 
					Всего заказов: <b>0</b>
				</div>
			</div>
			<table id="orderTable"></table>
		</div>

		<div id="myModal" class="modal">

		  <!-- Modal content -->
		  <div class="modal-content">
			<span class="close">&times;</span>
			<p id="modal-text">Данного товара нет в заказе</p>
		  </div>

		</div>
		
		<script src="../js/myjs.js?n=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>"></script>
		<script>
			async function filterOrders() {
				var dateFrom = document.getElementById("dateFrom").value;
				var dateTo = document.getElementById("dateTo").value;
				var agent = document.getElementById("agent").value;
				var organization = document.getElementById("organization").value;
				location.replace ("?dateFrom=" + dateFrom + "&dateTo=" + dateTo + "&agent=" + agent + "&organization=" + organization);
			}

			window.onload = async function() {
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var website = url.searchParams.get("website");
				var goodsFlag = url.searchParams.get("goodsFlag");
				var beruFlag = url.searchParams.get("beruFlag");

				document.getElementById("filter_button").disabled = true;
				showLoad('Загрузка данных... подождите пару секунд...');

				var resp = await fetch("renew_orders.php?dateFrom=" + dateFrom + "&dateTo=" + dateTo + "&agent=" + agent + "&organization=" + organization);
				
				if (resp.ok) {
					document.getElementById("orderTable").innerHTML = await resp.text();
					document.getElementById("filter_button").disabled = false;
					deleteLoad (window);
				}
				else
					console.log(resp.error);
			}

		</script>
	</body>
</html>


