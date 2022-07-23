<?php

	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');

	ini_set("display_errors", 1);
	error_reporting(E_ALL);
	try {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/orders.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	}
	catch(Exception $e) {    
		echo "Message : " . $e->getMessage();
		echo "Code : " . $e->getCode();
	}						
	$orderId = $_GET["orderId"];
	$order = Orders::getOrder($orderId);
?>
<html>
	<head>
		<title>Комплектовщик</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=2" />
	</head>
	<body style="margin:0;padding:0">
		<div align="center" style="padding-bottom:15">
			<div id="header">
				<div style="margin-bottom: 13px; margin-top: 14px; font-size: 200%; color:#F7971D;">
					Комплектация заказа <?php echo $order['name']; ?>
				</div>
				<div style="margin-bottom: 13px; margin-top: 14px; font-size: 110%"> 
					Номер заказа: <b><?php echo $order['name']; ?></b>
					Контрагент: <b><?php echo $order['agent']['name']; ?></b>
					Дата заказа: <b><?php echo $order['moment']; ?></b>
					Дата отгрузки: <b><?php echo date("Y-m-d", strtotime ($order['deliveryPlannedMoment'])); ?></b>
					Сумма заказа: <b><?php echo $order['sum'] / 100; ?></b>
				</div>
			</div>
			<table id="orderTable">
				<tr>
					<th style="width:70%">Код</th>
					<th style="width:200%">Наименование</th>
					<th>Штрихкод</th>
					<th>Количество заказанное</th>
					<th>Остаток на складе</th>
					<th>Количество скомплектованное</th>
					<th>Сумма</th>
					<th> </th>
				</tr>
<?php
					if (count ($order['positions2']) > 0)
						foreach ($order['positions2'] as $position)
						{ ?>
							<tr id="r<?php echo $position['assortment']['productBarcode']; ?>">
								<td><?php echo $position['assortment']['productCode']; ?></td>
								<td><?php echo $position['assortment']['productName']; ?></td>
								<td id="bc<?php echo $position['assortment']['productBarcode']; ?>"><?php echo $position['assortment']['productBarcode']; ?></td>
								<td id="q<?php echo $position['assortment']['productBarcode']; ?>"><?php echo $position['quantity']; ?></td>
								<td id="stock<?php echo $position['assortment']['productBarcode']; ?>"><?php echo $position['stock']; ?></td>
								<td id="qp<?php echo $position['assortment']['productBarcode']; ?>"><?php echo $position['quantityPack']; ?></td>
								<td><?php echo $position['quantity'] * ($position['price'] / 100) * (1 - ($position['discount'] / 100.0)); ?></td>
								<td> <button type="button" id = "<?php echo $position['assortment']['productBarcode']; ?>" onclick="clearCount('<?php echo $position['assortment']['productBarcode']; ?>')">Очистить</button></td>
							</tr>
<?php					}

?>		
			</table> 
		</div>
		<div align="left" style = "width:80%;margin: 0 auto">
			Штрихкод: <input type="text" id="barcodePack" size="20" onkeypress="searchBarcode(event)">
			<button type="button" id = "rep" onclick="rep()">Распечатать счет</button>
			<button type="button" id = "ship" onclick="ship()" style="visibility:hidden">Изменить статус заказа (в доставку)</button>
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

			order = JSON.parse ('<?php echo str_replace(array("\u0022", "\\n"),array ("\\\\\"", "\\\\n"), json_encode($order, JSON_HEX_QUOT)); ?>');
			console.log(order);

			window.onload = function() {
				document.getElementById("barcodePack").focus();
			};

			function showButtons() {
				document.getElementById("ship").style.visibility = "visible";
			}

			function hideButtons() {
				document.getElementById("ship").style.visibility = "hidden";
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
					
					var p = null;
					for (var position in order.positions2) {
						var nid = order.positions2[position].assortment.productBarcode;
						if (nid.search (barcode)>=0) {
							p = nid;
							order.positions2[position].quantityPack ++;
							break;
						}
					}
					var t = false;
					for (var position in order.positions2) {
						var t = order.positions2[position].quantityPack != order.positions2[position].quantity;
						if (t)
							break;
					}
					
					if (t) {
						hideButtons();
					} else {
						showButtons();
					}
						
					if (p == null) {
						var audio = new Audio('OOGAhorn.mp3');
						audio.play();
						showModal("В заказе отсутствует товар со штрихкодом " + barcode);
						return;
					}
					var s = document.getElementById("r" + p);
					var quantity = parseInt(document.getElementById("q" + p).innerHTML);
					var quantityPack = parseInt(document.getElementById("qp" + p).innerHTML);
					quantityPack ++;
					document.getElementById("qp" + p).innerHTML = quantityPack;
					if (quantity == quantityPack) {
						s.style = "background-color: lightgreen";
					}
					if (quantity < quantityPack) {
						s.style = "background-color: red";
						var audio = new Audio('OOGAhorn.mp3');
						audio.play();
					}
				}
			}

			function clearCount(productBarcode) {
				document.getElementById("qp" + productBarcode).innerHTML = 0;
				document.getElementById("r" + productBarcode).removeAttribute("style");
				for (var position in order.positions2) {
					var nid = order.positions2[position].assortment.productBarcode;
					if (nid.search (productBarcode)>=0) {
						order.positions2[position].quantityPack = 0;
						break;
					}
				}
				hideButtons();
				document.getElementById("barcodePack").focus();
			}

			function rep() {
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.open("GET", "report.php?orderId=" + order.id, true);
				xmlhttp.onloadstart = function (e) {
					document.getElementById("rep").disabled = true;
					showLoad('Загрузка данных... подождите пару секунд...');
				}
				xmlhttp.onload = function() {
					if (this.status == 200) {
						var blob = xmlhttp.response;
						var link = document.createElement("a");
						link.href = blob;
						link.download = "order_" + order.name + ".pdf";

						document.body.appendChild(link);

						link.click();

						document.body.removeChild(link);
						document.getElementById("rep").disabled = false;
						deleteLoad (window);
						document.getElementById("barcodePack").focus();
					}
				};
				xmlhttp.send();
				
			}

			function ship() {
				var x = window.confirm ("Вы уверены, что хотите изменить статус заказа?");
				if (!x) return;
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.open("GET", "ship.php?orderId=" + order.id, true);
				xmlhttp.onload = function() {
					if (this.status == 200) {
						alert("Статус изменен");
						parent.location.reload();
						window.close();
					}
				};
				xmlhttp.send();
				
			}

			/* window.addEventListener('beforeunload', function (e) {
				deleteLoad2(opener);
			})
 */
		</script>
	</body>
</html>


