<?php
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	
	ini_set("display_errors", 1);
	error_reporting(E_ALL);
	//require_once('classes/log.php')
	//var $log = new Log("log.txt");
//	log.write('aaa');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
?>
<html>
	<head>
		<title>Комплектовщик</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel = "stylesheet" type = "text/css" href = "../css/styles.css?v=2" />
	</head>
	<body>
		<div align="center">
			<div id="header">
				<?php require_once ($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
				<div class = "title">
					Отгрузка заказов
				</div>
				<div style="margin-bottom: 5px; margin-top: 5px;"> 
					Штрихкод заказа: <input type="text" id="barcodePack" size="20" onkeypress="searchBarcode(event)">
				</div>
			</div>
			<table id="orderTable"></table>
		</div>

		<div id="myModal" class="modal">

		  <!-- Modal content -->
		  <div class="modal-content">
			<span class="close">&times;</span>
			<p id="modal-text">Заказ не найден</p>
		  </div>

		</div>
		
		<script src="../js/myjs.js?n=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>"></script>
		<script>

			async function searchBarcode(event) {
				var x = event.charCode || event.keyCode;  // Get the Unicode value
				var y = String.fromCharCode(x);       // Convert the value into a character
				if (x==13) {
					var barcodeElement = document.getElementById("barcodePack");
					if (barcodeElement.value === "")
						return;
					var barcode = barcodeElement.value;
					barcodeElement.select();
					
					showLoad('Загрузка данных... подождите пару секунд...');

					var resp = await fetch('renew_orders.php?order=' + barcode.split("%").join("%25"));
					//i++;
					if (resp.ok) {
						document.getElementById("orderTable").innerHTML = await resp.text();
						//document.getElementById("filter_button").disabled = false;
						deleteLoad (window);
					}
				}
			}

		</script>
	</body>
</html>


