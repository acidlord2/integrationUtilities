<?php
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	
	//require_once('classes/log.php')
	//var $log = new Log("log.txt");
//	log.write('aaa');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

	$orders = Orders::getList($shippingDate, $website, $goodsFlag, $beruFlag);
?>
<html>
	<head>
		<title>Неоплаченные заказы</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<script>
			if (location.search == "") {
				location.search = "?shippingDate=<?php echo $shippingDate; ?>&website=<?php echo $website; ?>&goodsFlag=<?php echo $goodsFlag; ?>&beruFlag=<?php echo $beruFlag; ?>";
			}
		</script>
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=2" />
	</head>
	<body>
		<div align="center">
			<div id="header">
				<?php require_once ($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
				<div class = "title">
					Список неоплаченных заказов
				</div>
				<div style="margin-bottom: 13px; margin-top: 14px;"> 
					Дата заказа от: <input type="date" id="shippingDateFrom" data-date-format="DD.MM.YYYY">
					Дата заказа до: <input type="date" id="shippingDateTo" data-date-format="DD.MM.YYYY">
					Организация: <select id="website" value="<?php echo $website; ?>">
						<option value="all" <?php echo $website=='all' ? ' selected' : ''; ?>>Все</option>
						<option value="4cleaning" <?php echo $website=='4cleaning' ? ' selected' : ''; ?>>4cleaning</option>
						<option value="10kids" <?php echo $website=='10kids' ? ' selected' : ''; ?>>10kids</option>
					</select>
					: <select id="goodsFlag" value="<?php echo $goodsFlag; ?>">
						<option value="2" <?php echo $goodsFlag=='2' ? ' selected' : ''; ?>>Все</option>
						<option value="0" <?php echo $goodsFlag=='0' ? ' selected' : ''; ?>>Не GOODS</option>
						<option value="1" <?php echo $goodsFlag=='1' ? ' selected' : ''; ?>>GOODS</option>
					</select>
					<button type="button" id = "filter_button" onclick="filterOrders()">Фильтр</button>			
				</div>
				<div style="margin-bottom: 5px; margin-top: 5px;"> 
					Всего заказов: <b><?php echo count ($orders); ?></b>
					Штрихкод заказа: <input type="text" id="barcodePack" size="20" onkeypress="searchBarcode(event)">
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
		
		<script src="/js/myjs.js?n=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>"></script>
		<script>
			function filterOrders() {
				var shippingDate = document.getElementById("shippingDate").value;
				var website = document.getElementById("website").value;
				var goodsFlag = document.getElementById("goodsFlag").value;
				var beruFlag = document.getElementById("beruFlag").value;
				location.replace ("?shippingDate=" + shippingDate + "&website=" + website + "&goodsFlag=" + goodsFlag + "&beruFlag=" + beruFlag);
			}

			window.onload = function() {
				var url = new URL(location);
				var shippingDate = url.searchParams.get("shippingDate");
				var website = url.searchParams.get("website");
				var goodsFlag = url.searchParams.get("goodsFlag");
				var beruFlag = url.searchParams.get("beruFlag");
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.open("GET", "renew_orders.php?shippingDate=" + shippingDate + "&website=" + website + "&goodsFlag=" + goodsFlag + "&beruFlag=" + beruFlag, true);
				xmlhttp.onloadstart = function () {
					document.getElementById("filter_button").disabled = true;
					showLoad('Загрузка данных... подождите пару секунд...');
				}	
				xmlhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						document.getElementById("orderTable").innerHTML = this.responseText;
						document.getElementById("filter_button").disabled = false;
						deleteLoad (window);
					}
				};
				xmlhttp.send();
			}

			function searchBarcode(event) {
				var x = event.charCode || event.keyCode;  // Get the Unicode value
				var y = String.fromCharCode(x);       // Convert the value into a character
				if (x==13) {
					var barcodeElement = document.getElementById("barcodePack");
					if (barcodeElement.value === "")
						return;
					var barcode = barcodeElement.value;
					barcodeElement.value = "";
					
					var b = document.getElementById(barcode);
					if (b == null) {
						window.alert ("На текущий день отсутствует заказ №" + barcode);
						return;
					}
					else
						b.click();
				}
			}

		</script>
		<script>
			popupWindow = null;

			function openOrder(orderId) {
				popupWindow = window.open("packOrder.php?orderId=" + orderId);
			}
			
			function check() {
				/* if(popupWindow && !popupWindow.closed)
					showLoad2('Открыто окно комплектации - завершите комплектацию'); */

			}
		</script>

	</body>
</html>


