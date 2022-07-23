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
?>
<html>
	<head>
		<title>Обработка возвратов</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=<?php echo date("Y-m-d-H-i-s", strtotime("now")); ?>" />
	</head>
	<body style="margin:0;padding:0">
		<div align="center" style="padding-bottom:15">
			<div id="header">
				<?php require_once ($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
				<div style="margin-bottom: 13px; margin-top: 14px; font-size: 200%; color:#F7971D;">
					Обработка возвратов
				</div>
				Штрихкод заказа: <input type="text" id="barcodePack" size="20" onkeypress="searchBarcode(event)">
			</div>
			<table id="orderTable">
				<tr id = "teble_header">
					<th>N п/п</th>
					<th>Номер заказа</th>
					<th>Дата отгрузки</th>
					<th>Сумма заказа</th>
					<th>Клиент</th>
					<th>Статус</th>
					<th>Состав заказа</th>
					<th>Оформить возврат?</th>
				</tr>
			</table> 
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

			/*order = JSON.parse ('<?php echo str_replace(array("\u0022", "\\n"),array ("\\\\\"", "\\\\n"), json_encode($order, JSON_HEX_QUOT)); ?>');
			console.log(order);*/
			
			var a = 0;
			
			window.onload = function() {
				document.getElementById("barcodePack").focus();
			};

			function showButtons() {
				document.getElementById("ship").style.visibility = "visible";
			}

			function hideButtons() {
				document.getElementById("ship").style.visibility = "hidden";
			}


			async function createReturn(orderId, url) {
				var xmlhttp = new XMLHttpRequest();
			xmlhttp.open("GET", "create_return.php?url=" + url + "&order=" + orderId, true);
				xmlhttp.onloadstart = function (e) {
					document.getElementById("button_" + orderId).disabled = true;
					showLoad('Загрузка данных... подождите пару секунд...');
				}
				xmlhttp.onload = function() {
					if (this.readyState == 4 && this.status == 200) {
						if (this.responseText.trim() != 'Ok')
						{
							playAudio('OOGAhorn.mp3');
							showModal(this.responseText);
							document.getElementById("button_" + orderId).disabled = true;
							deleteLoad (window);
							return;
						}

						button = document.getElementById("button_" + orderId);
						td = button.parentNode;
						button.visible = false;
						td.textContent = 'Ok';
						td.style = "background-color: lightgreen";
						deleteLoad (window);
						document.getElementById("barcodePack").focus();
					}
				};
				xmlhttp.send();
				
			}

 			function addRow (url, id, number, orderNumber, orderDate, orderAmount, orderCustomer, orderStatus, positions) {
				
				var tr = document.createElement("tr");
				tr.setAttribute ("id", "tr_" + id);
				
				var td = document.createElement("td");
				td.innerHTML = (number);
				tr.appendChild(td);

				td = document.createElement("td");
				td.innerHTML = (orderNumber);
				tr.appendChild(td);
				
				td = document.createElement("td");
				td.innerHTML = (orderDate);
				tr.appendChild(td);

				td = document.createElement("td");
				td.innerHTML = (orderAmount / 100);
				tr.appendChild(td);

				td = document.createElement("td");
				td.innerHTML = (orderCustomer);
				tr.appendChild(td);
				
				td = document.createElement("td");
				td.innerHTML = (orderStatus);
				tr.appendChild(td);

				td = document.createElement("td");
				td.innerHTML = (positions);
				tr.appendChild(td);

				td = document.createElement("td");
				button = document.createElement("button")
				button.textContent = ("Оформить возврат");
				button.setAttribute ("onclick", "createReturn ('" + id + "', '" + url + "');");
				button.setAttribute ("type", "button");
				button.setAttribute ("id", "button_" + id);
				td.appendChild(button);
				tr.appendChild(td);
				
				document.getElementById ("orderTable").appendChild(tr);
			}
			
			async function getOrder (orderNumber) {
				showLoad('Загрузка данных... подождите пару секунд...');
				let resp = await fetch("order_info.php?orderNumber=" + encodeURI(orderNumber.trim()));

				if (resp.ok) {
					var order = await resp.json();
					
					deleteLoad (window);
					return order;
				}
			}
			
			async function searchBarcode(event) {
				var x = event.charCode || event.keyCode;  // Get the Unicode value
				var y = String.fromCharCode(x);       // Convert the value into a character
				if (x==13) {

					var orderNumber = document.getElementById('barcodePack').value;
					var order = await getOrder (orderNumber);
					
					// check if order number is exists
					if (!order)
					{
						playAudio('OOGAhorn.mp3');
						showModal("Отсутствует заказ №" + orderNumber);
						//window.alert ("Отсутствует заказ №" + orderNumber);
						document.getElementById("barcodePack").value = "";
						document.getElementById("barcodePack").focus();
						return;
					}

					if (document.getElementById("tr_" + order.id) != null)
					{
						playAudio('OOGAhorn.mp3');
						showModal("Заказ на возврат уже обработан");
						//window.alert ("Заказ на возврат уже обработан");
						document.getElementById("barcodePack").value = "";
						document.getElementById("barcodePack").focus();
						return;
					}
					
					if (!("demands" in order))
					{
						playAudio('OOGAhorn.mp3');
						showModal("У заказа отсутствует отгрузка. Проверьте корректность оформленной отгрузки");
						//window.alert ("У заказа отсутствует отгрузка. Проверьте корректность оформленной отгрузки");
						document.getElementById("barcodePack").value = "";
						document.getElementById("barcodePack").focus();
						return;
					}
					
					a++;
					addRow (order.demands[0].meta.href, order.id, a, order.name, order.deliveryPlannedMoment, order.sum, order.agent.name, order.state.name, order.positions2);
					playAudio('okay-1.mp3');
					document.getElementById("barcodePack").value = "";
					document.getElementById("barcodePack").focus();
				}
			}

		</script>
	</body>
</html>


